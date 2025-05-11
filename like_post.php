<?php
// like_post.php
session_start();
require 'db_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if required parameters are set
if (!isset($_POST['post_id']) || !isset($_POST['like'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$post_id = $_POST['post_id'];
$is_like = (int)$_POST['like']; // 1 for like, 0 for unlike
$user_email = $_SESSION['user_email'];

try {
    $pdo->beginTransaction();
    
    // First check if the user has already liked this post
    $check_stmt = $pdo->prepare("SELECT * FROM post_likes WHERE post_id = ? AND user_email = ?");
    $check_stmt->execute([$post_id, $user_email]);
    $existing_like = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($is_like == 1) {
        // User wants to like the post
        if (!$existing_like) {
            // Insert new like record
            $insert_stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_email) VALUES (?, ?)");
            $insert_stmt->execute([$post_id, $user_email]);
            
            // Update the likes count in posts table
            $update_stmt = $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE post_id = ?");
            $update_stmt->execute([$post_id]);
        }
    } else {
        // User wants to unlike the post
        if ($existing_like) {
            // Remove like record
            $delete_stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_email = ?");
            $delete_stmt->execute([$post_id, $user_email]);
            
            // Update the likes count in posts table
            $update_stmt = $pdo->prepare("UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE post_id = ?");
            $update_stmt->execute([$post_id]);
        }
    }
    
    $pdo->commit();
    
    // Get updated like count
    $stmt = $pdo->prepare("SELECT likes FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if the user has liked this post after the operation
    $check_liked_stmt = $pdo->prepare("SELECT COUNT(*) as liked FROM post_likes WHERE post_id = ? AND user_email = ?");
    $check_liked_stmt->execute([$post_id, $user_email]);
    $is_liked = (bool)$check_liked_stmt->fetch(PDO::FETCH_ASSOC)['liked'];
    
    echo json_encode([
        'success' => true, 
        'likeCount' => $result['likes'],
        'isLiked' => $is_liked
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>