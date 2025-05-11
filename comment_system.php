<?php
session_start();
require 'db_config.php';  // Contains PDO connection setup

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$current_user_email = $_SESSION["user_email"]; 

// Function to get user information
function getUserInfo($pdo, $email) {
    $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get current user info
try {
    $current_user = getUserInfo($pdo, $current_user_email);
    if (!$current_user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    // Log request data for debugging
    error_log("Comment submission attempt: " . print_r($_POST, true));
    
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';
    
    // Validate data
    if (!$post_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit();
    }
    
    if (empty($comment_text)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Comment text cannot be empty']);
        exit();
    }
    
    try {
        // Check if the post exists
        $stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit();
        }
        
        // Insert comment with prepared statement
        $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_email, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        $result = $stmt->execute([$post_id, $current_user_email, $comment_text]);
        
        if (!$result) {
            throw new PDOException("Failed to insert comment");
        }
        
        $comment_id = $pdo->lastInsertId();
        
        // Prepare response with new comment details
        $profile_pic_path = (!empty($current_user['profile_pic'])) ? "user_dp/" . $current_user['profile_pic'] : "user_dp/default.jpg";
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'comment_id' => $comment_id,
            'username' => $current_user['username'],
            'profile_pic' => $profile_pic_path,
            'comment_text' => htmlspecialchars($comment_text),
            'created_at' => date('F j, Y \a\t g:i a'),
            'user_email' => $current_user_email
        ]);
        exit();
    } catch (PDOException $e) {
        error_log("Database error when adding comment: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    } catch (Exception $e) {
        error_log("General error when adding comment: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit();
    }
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    
    if (!$comment_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit();
    }
    
    try {
        // Check if the comment belongs to the current user
        $stmt = $pdo->prepare("SELECT comment_id FROM post_comments WHERE comment_id = ? AND user_email = ?");
        $stmt->execute([$comment_id, $current_user_email]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authorized to delete this comment']);
            exit();
        }
        
        // Delete the comment
        $stmt = $pdo->prepare("DELETE FROM post_comments WHERE comment_id = ?");
        $result = $stmt->execute([$comment_id]);
        
        if (!$result) {
            throw new PDOException("Failed to delete comment");
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
        exit();
    } catch (PDOException $e) {
        error_log("Database error when deleting comment: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Fetch comments for a specific post
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['post_id'])) {
    $post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
    
    if (!$post_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit();
    }
    
    try {
        // Check if post exists
        $check_stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ?");
        $check_stmt->execute([$post_id]);
        if (!$check_stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit();
        }
        
        $query = "SELECT 
                    c.comment_id, 
                    c.user_email, 
                    c.comment_text, 
                    c.created_at,
                    u.username,
                    u.profile_pic
                  FROM post_comments c
                  JOIN users u ON c.user_email = u.email
                  WHERE c.post_id = ?
                  ORDER BY c.created_at ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process comments to add profile pic path and format timestamp
        foreach ($comments as &$comment) {
            $comment['profile_pic'] = (!empty($comment['profile_pic'])) ? "user_dp/" . $comment['profile_pic'] : "user_dp/default.jpg";
            $timestamp = strtotime($comment['created_at']);
            $comment['created_at_formatted'] = date('F j, Y \a\t g:i a', $timestamp);
            // Add a flag if the comment belongs to the current user
            $comment['is_own_comment'] = ($comment['user_email'] === $current_user_email);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'comments' => $comments]);
        exit();
    } catch (PDOException $e) {
        error_log("Database error when fetching comments: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// If we reach here, no valid action was specified
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request or action']);
exit();
?>