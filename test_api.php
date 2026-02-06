<?php
/**
 * Test task_assignment_api.php directly
 * ลบทิ้งหลังแก้ไขเสร็จ!
 */
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h2>❌ ยังไม่ได้ Login</h2>";
    echo "<p>กรุณา <a href='login.php'>Login</a> ก่อนแล้วกลับมาหน้านี้</p>";
    exit;
}

echo "<h2>🔍 Test Task Assignment API</h2>";
echo "<p>Logged in as User #{$_SESSION['user_id']} (role: {$_SESSION['role']})</p>";

// Test 1: Direct GET call
echo "<h3>Test 1: GET get_available_users (NAS service, request 37)</h3>";
echo "<pre>";

$url = "http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/admin/api/task_assignment_api.php?action=get_available_users&service_code=NAS&request_id=37";
echo "URL: $url\n\n";

// Use cURL to call the API with session cookie
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($error) echo "cURL Error: $error\n";
echo "Response length: " . strlen($response) . " bytes\n";
echo "Response: " . htmlspecialchars($response) . "\n";
echo "</pre>";

// Test 2: Direct POST call
echo "<h3>Test 2: POST assign_task (DRY RUN - ไม่ได้ส่งจริง)</h3>";
echo "<pre>";

$post_url = "http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/admin/api/task_assignment_api.php";
$postData = [
    'action' => 'assign_task',
    'request_id' => '37',
    'assigned_to' => '11',
    'priority' => 'normal',
    'due_date' => '',
    'notes' => 'Test from debug page'
];

echo "URL: $post_url\n";
echo "POST data: " . json_encode($postData) . "\n\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $post_url);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error2 = curl_error($ch2);
curl_close($ch2);

echo "HTTP Status: $httpCode2\n";
if ($error2) echo "cURL Error: $error2\n";
echo "Response length: " . strlen($response2) . " bytes\n";
echo "Response: " . htmlspecialchars($response2) . "\n";

// Check hex of response
echo "\nHex dump (first 50 bytes): ";
for ($i = 0; $i < min(50, strlen($response2)); $i++) {
    echo sprintf('%02x ', ord($response2[$i]));
}
echo "\n";
echo "</pre>";

// Test 3: Direct include test
echo "<h3>Test 3: Direct PHP include check</h3>";
echo "<pre>";

// Check if file exists
$api_file = __DIR__ . '/admin/api/task_assignment_api.php';
echo "File path: $api_file\n";
echo "File exists: " . (file_exists($api_file) ? 'YES' : 'NO') . "\n";
if (file_exists($api_file)) {
    echo "File size: " . filesize($api_file) . " bytes\n";
    echo "File writable: " . (is_writable($api_file) ? 'YES' : 'NO') . "\n";
    
    // Check for BOM
    $content = file_get_contents($api_file);
    $first3 = substr($content, 0, 3);
    $hasBOM = ($first3 === "\xEF\xBB\xBF");
    echo "Has BOM: " . ($hasBOM ? 'YES ⚠️' : 'NO') . "\n";
    
    // Check first bytes
    echo "First 10 bytes hex: ";
    for ($i = 0; $i < min(10, strlen($content)); $i++) {
        echo sprintf('%02x ', ord($content[$i]));
    }
    echo "\n";
    echo "First line: " . htmlspecialchars(strtok($content, "\n")) . "\n";
    
    // Check database.php too
    $db_file = __DIR__ . '/config/database.php';
    if (file_exists($db_file)) {
        $db_content = file_get_contents($db_file);
        $db_first3 = substr($db_content, 0, 3);
        $db_hasBOM = ($db_first3 === "\xEF\xBB\xBF");
        echo "\nconfig/database.php:\n";
        echo "  Has BOM: " . ($db_hasBOM ? 'YES ⚠️ (นี่คือสาเหตุ!)' : 'NO') . "\n";
        echo "  First 10 bytes hex: ";
        for ($i = 0; $i < min(10, strlen($db_content)); $i++) {
            echo sprintf('%02x ', ord($db_content[$i]));
        }
        echo "\n";
    }
}

echo "</pre>";
echo "<p style='color:red; font-weight:bold;'>⚠️ ลบไฟล์นี้หลังแก้ไขปัญหาเสร็จ!</p>";
?>
