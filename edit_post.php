<?php
session_start();
require 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if required data is present
if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$post_id = $_POST['post_id'];
$content = $_POST['content'];
$user_email = $_SESSION['user_email'];

try {
    // First verify that this post belongs to the current user
    $stmt = $pdo->prepare("SELECT user_email FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post || $post['user_email'] !== $user_email) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    
    // Update the post content
    $stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE post_id = ?");
    $result = $stmt->execute([$content, $post_id]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}