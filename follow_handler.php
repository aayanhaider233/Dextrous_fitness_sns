<?php
session_start();
require_once 'db_config.php'; 

function returnError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_email'])) {
    returnError('You must be logged in to follow users');
}

if (!isset($_POST['action']) || !isset($_POST['user_email'])) {
    returnError('Invalid request parameters');
}

$action = trim($_POST['action']);
$follower_email = trim($_SESSION['user_email']);
$followed_email = trim($_POST['user_email']);

error_log("Follow operation: '$follower_email' trying to $action '$followed_email'");

if ($action !== 'follow' && $action !== 'unfollow') {
    returnError('Invalid action');
}

if ($follower_email === $followed_email) {
    returnError('You cannot follow yourself');
}

try {
    global $pdo;

    // Validate both users exist
    $check_follower = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
    $check_follower->execute([$follower_email]);
    if ($check_follower->fetchColumn() === false) {
        error_log("Follower user not found: '$follower_email'");
        returnError('Your user account could not be found');
    }

    $check_followed = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
    $check_followed->execute([$followed_email]);
    if ($check_followed->fetchColumn() === false) {
        error_log("Followed user not found: '$followed_email'");

        // Debug: list all users
        $flexible_check = $pdo->prepare("SELECT email FROM users");
        $flexible_check->execute();
        $all_emails = $flexible_check->fetchAll(PDO::FETCH_COLUMN);
        error_log("All emails in database: " . implode(", ", $all_emails));

        returnError('The user ' . $followed_email . ' does not exist');
    }

    if ($action === 'follow') {
        // Check if already following
        $check_stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_email = ? AND followed_email = ?");
        $check_stmt->execute([$follower_email, $followed_email]);

        if ($check_stmt->fetchColumn() !== false) {
            returnError('You are already following this user');
        }

        // Perform follow
        $pdo->beginTransaction();
        try {
            $stmt1 = $pdo->prepare("INSERT INTO follows (follower_email, followed_email) VALUES (?, ?)");
            $stmt1->execute([$follower_email, $followed_email]);

            $stmt2 = $pdo->prepare("INSERT INTO followerlist (user_email, follower) VALUES (?, ?)");
            $stmt2->execute([$followed_email, $follower_email]);

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'action' => 'follow',
                'message' => 'Successfully followed user'
            ]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            returnError('Database error during follow');
        }

    } elseif ($action === 'unfollow') {
        // Check if currently following
        $check_stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_email = ? AND followed_email = ?");
        $check_stmt->execute([$follower_email, $followed_email]);

        if ($check_stmt->fetchColumn() === false) {
            returnError('You are not following this user');
        }

        // Perform unfollow
        $pdo->beginTransaction();
        try {
            $stmt1 = $pdo->prepare("DELETE FROM follows WHERE follower_email = ? AND followed_email = ?");
            $stmt1->execute([$follower_email, $followed_email]);

            $stmt2 = $pdo->prepare("DELETE FROM followerlist WHERE user_email = ? AND follower = ?");
            $stmt2->execute([$followed_email, $follower_email]);

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'action' => 'unfollow',
                'message' => 'Successfully unfollowed user'
            ]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            returnError('Database error during unfollow');
        }
    }

} catch (PDOException $e) {
    error_log("Database error in follow_handler: " . $e->getMessage());
    returnError('Database error: ' . $e->getMessage());
}