<?php
session_start();

require 'db_config.php';

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE username LIKE ? ORDER BY CASE WHEN username LIKE ? THEN 1 WHEN username LIKE ? THEN 2 ELSE 3 END LIMIT 10");
    $stmt->execute([
        '%' . $query . '%',  
        $query . '%',        
        '%' . $query         
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>