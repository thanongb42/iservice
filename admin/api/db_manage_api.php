<?php
/**
 * DB Manage API
 * backup / truncate / restore request tables
 */
require_once '../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ── Constants ────────────────────────────────────────────────────────────────
define('BACKUP_DIR', __DIR__ . '/../../storage/backups/requests/');

// Tables to backup/truncate — order matters for truncate (FK deps last→first)
// For restore, they will be inserted in REVERSE order (parent → child)
const TRUNCATE_ORDER = [
    'task_history',
    'task_assignments',
    'request_email_details',
    'request_internet_details',
    'request_it_support_details',
    'request_led_details',
    'request_mc_details',
    'request_nas_details',
    'request_photography_details',
    'request_printer_details',
    'request_qrcode_details',
    'request_webdesign_details',
    'service_requests',
];

// ── Helpers ──────────────────────────────────────────────────────────────────

function tableExists(mysqli $conn, string $table): bool {
    $r = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $r && $r->num_rows > 0;
}

function getTableRows(mysqli $conn, string $table): int {
    if (!tableExists($conn, $table)) return -1;
    return (int)$conn->query("SELECT COUNT(*) as c FROM `$table`")->fetch_assoc()['c'];
}

/**
 * Generate SQL INSERT statements for a table's data
 */
function dumpTableSQL(mysqli $conn, string $table): string {
    if (!tableExists($conn, $table)) return "-- Table `$table` does not exist, skipped\n";
    $result = $conn->query("SELECT * FROM `$table`");
    if (!$result || $result->num_rows === 0) {
        return "-- Table `$table` is empty\n";
    }
    $sql = "-- Table: $table ({$result->num_rows} rows)\n";
    while ($row = $result->fetch_assoc()) {
        $cols = '`' . implode('`, `', array_keys($row)) . '`';
        $vals = array_map(function($v) use ($conn) {
            if ($v === null) return 'NULL';
            return "'" . $conn->real_escape_string($v) . "'";
        }, array_values($row));
        $sql .= "INSERT INTO `$table` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
    }
    return $sql;
}

function ensureBackupDir(): void {
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
}

function listBackupFiles(): array {
    ensureBackupDir();
    $files = glob(BACKUP_DIR . 'requests_backup_*.sql');
    if (!$files) return [];
    // Sort newest first
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    $result = [];
    foreach ($files as $f) {
        $name = basename($f);
        $result[] = [
            'filename' => $name,
            'size'     => filesize($f),
            'size_fmt' => formatBytes(filesize($f)),
            'created'  => date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
    return $result;
}

function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function createBackup(mysqli $conn): array {
    ensureBackupDir();
    $filename  = 'requests_backup_' . date('Ymd_His') . '.sql';
    $filepath  = BACKUP_DIR . $filename;

    $sql  = "-- iService Request Data Backup\n";
    $sql .= "-- Created  : " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Tables   : " . implode(', ', TRUNCATE_ORDER) . "\n";
    $sql .= "-- Restore  : run via admin > ตั้งค่าระบบ > จัดการข้อมูล > Restore\n";
    $sql .= str_repeat('-', 60) . "\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    // Dump in RESTORE order (parent first: service_requests → children)
    $restoreOrder = array_reverse(TRUNCATE_ORDER);
    $totalRows = 0;
    foreach ($restoreOrder as $table) {
        $sql .= dumpTableSQL($conn, $table) . "\n";
        $count = getTableRows($conn, $table);
        if ($count > 0) $totalRows += $count;
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql .= "-- END OF BACKUP (total rows: $totalRows)\n";

    if (file_put_contents($filepath, $sql) === false) {
        return ['success' => false, 'message' => 'ไม่สามารถเขียนไฟล์ backup ได้'];
    }

    return [
        'success'  => true,
        'filename' => $filename,
        'size_fmt' => formatBytes(filesize($filepath)),
        'rows'     => $totalRows,
        'message'  => "สำรองข้อมูลสำเร็จ: $filename ($totalRows rows)",
    ];
}

function truncateAll(mysqli $conn): void {
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    foreach (TRUNCATE_ORDER as $table) {
        if (tableExists($conn, $table)) {
            $conn->query("TRUNCATE TABLE `$table`");
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}

function restoreFromFile(mysqli $conn, string $filename): array {
    // Validate filename (prevent path traversal)
    if (!preg_match('/^requests_backup_\d{8}_\d{6}\.sql$/', $filename)) {
        return ['success' => false, 'message' => 'ชื่อไฟล์ไม่ถูกต้อง'];
    }
    $filepath = BACKUP_DIR . $filename;
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => "ไม่พบไฟล์: $filename"];
    }

    $sql = file_get_contents($filepath);
    if ($sql === false) {
        return ['success' => false, 'message' => 'ไม่สามารถอ่านไฟล์ backup ได้'];
    }

    // Clear all tables first
    truncateAll($conn);

    // Execute backup SQL statement by statement
    $conn->query("SET NAMES utf8mb4");
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Split by semicolons but not those inside quoted strings
    $statements = array_filter(
        array_map('trim', explode(";\n", $sql)),
        fn($s) => strlen($s) > 0 && $s[0] !== '-' && !str_starts_with($s, 'SET') && !str_starts_with($s, '--')
    );

    $inserted = 0;
    $errors   = [];
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt) || $stmt[0] === '-') continue;
        if (!$conn->query($stmt)) {
            $errors[] = substr($stmt, 0, 60) . '... → ' . $conn->error;
        } else {
            $inserted++;
        }
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    if (!empty($errors)) {
        return [
            'success' => false,
            'message' => 'Restore เสร็จบางส่วน มีข้อผิดพลาด ' . count($errors) . ' รายการ',
            'errors'  => array_slice($errors, 0, 5),
        ];
    }

    return [
        'success'  => true,
        'message'  => "Restore สำเร็จ: $inserted statements จากไฟล์ $filename",
        'inserted' => $inserted,
    ];
}

// ── Router ───────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── List backups ────────────────────────────────────────────────────────
    case 'list_backups':
        echo json_encode([
            'success' => true,
            'backups' => listBackupFiles(),
        ]);
        break;

    // ── Table row counts ────────────────────────────────────────────────────
    case 'table_counts':
        $counts = [];
        $total  = 0;
        foreach (TRUNCATE_ORDER as $t) {
            $c = getTableRows($conn, $t);
            $counts[$t] = $c;
            if ($c > 0) $total += $c;
        }
        echo json_encode(['success' => true, 'counts' => $counts, 'total' => $total]);
        break;

    // ── Create backup only ──────────────────────────────────────────────────
    case 'create_backup':
        echo json_encode(createBackup($conn));
        break;

    // ── Backup then TRUNCATE ────────────────────────────────────────────────
    case 'truncate_requests':
        $confirm = $_POST['confirm'] ?? '';
        if ($confirm !== 'DELETE_ALL_REQUESTS') {
            echo json_encode(['success' => false, 'message' => 'ต้องยืนยันรหัส DELETE_ALL_REQUESTS']);
            break;
        }
        // 1. backup first
        $bk = createBackup($conn);
        if (!$bk['success']) {
            echo json_encode(['success' => false, 'message' => 'สร้าง backup ล้มเหลว: ' . $bk['message']]);
            break;
        }
        // 2. truncate
        truncateAll($conn);
        echo json_encode([
            'success'  => true,
            'message'  => 'ล้างข้อมูลสำเร็จ',
            'backup'   => $bk['filename'],
            'backup_rows' => $bk['rows'],
        ]);
        break;

    // ── Restore from backup ─────────────────────────────────────────────────
    case 'restore_backup':
        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุชื่อไฟล์ backup']);
            break;
        }
        echo json_encode(restoreFromFile($conn, $filename));
        break;

    // ── Delete backup file ──────────────────────────────────────────────────
    case 'delete_backup':
        $filename = $_POST['filename'] ?? '';
        if (!preg_match('/^requests_backup_\d{8}_\d{6}\.sql$/', $filename)) {
            echo json_encode(['success' => false, 'message' => 'ชื่อไฟล์ไม่ถูกต้อง']);
            break;
        }
        $filepath = BACKUP_DIR . $filename;
        if (!file_exists($filepath)) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์']);
            break;
        }
        unlink($filepath);
        echo json_encode(['success' => true, 'message' => "ลบไฟล์ $filename แล้ว"]);
        break;

    // ── Download backup ─────────────────────────────────────────────────────
    case 'download_backup':
        $filename = $_GET['filename'] ?? '';
        if (!preg_match('/^requests_backup_\d{8}_\d{6}\.sql$/', $filename)) {
            http_response_code(400); echo 'Invalid filename'; exit;
        }
        $filepath = BACKUP_DIR . $filename;
        if (!file_exists($filepath)) {
            http_response_code(404); echo 'File not found'; exit;
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
}
