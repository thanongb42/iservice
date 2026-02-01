<?php
/**
 * Get Single Resource API
 * Fetch learning resource data for editing
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID ไม่ถูกต้อง');
    }

    $stmt = $conn->prepare("SELECT * FROM learning_resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $resource = $result->fetch_assoc();
        echo json_encode($resource, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(null);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
