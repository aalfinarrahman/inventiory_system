<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    $query = "SELECT id, name FROM categories ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($categories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>