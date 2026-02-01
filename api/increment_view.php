<?php
/**
 * Increment View Count API
 * Increments view count for resources or news
 */

require_once '../config/database.php';

header('Content-Type: application/json');

$response = ['success' => false];

try {
    $id = intval($_GET['id'] ?? 0);
    $type = $_GET['type'] ?? '';

    if ($id <= 0) {
        throw new Exception('Invalid ID');
    }

    if ($type === 'resource') {
        $stmt = $conn->prepare("UPDATE learning_resources SET view_count = view_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $response['success'] = true;
    } elseif ($type === 'news') {
        $stmt = $conn->prepare("UPDATE tech_news SET view_count = view_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $response['success'] = true;
    } else {
        throw new Exception('Invalid type');
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>
