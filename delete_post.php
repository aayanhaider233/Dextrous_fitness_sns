<?php
session_start();
require 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if post_id is provided
if (!isset($_POST['post_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing post ID']);
    exit();
}

$post_id = $_POST['post_id'];
$user_email = $_SESSION['user_email'];

try {
    // First verify that this post belongs to the current user
    $stmt = $pdo->prepare("SELECT user_email, image_path FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post || $post['user_email'] !== $user_email) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete the post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
    $result = $stmt->execute([$post_id]);
    
    // If post had an image, we could delete the file too (optional)
    if ($result && !empty($post['image_path'])) {
        $image_path = 'post_images/' . $post['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}