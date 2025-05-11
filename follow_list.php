<?php
require_once 'db_config.php';
header('Content-Type: application/json');

$user_email = $_GET['user'] ?? null;

if (!$user_email) {
    echo json_encode(['success' => false, 'message' => 'User email not provided']);
    exit;
}

try {
    global $pdo;

    // Get followers: who follows this user
    $stmt1 = $pdo->prepare("
        SELECT users.email, users.username
        FROM followerlist
        JOIN users ON followerlist.follower = users.email
        WHERE followerlist.user_email = ?
    ");
    $stmt1->execute([$user_email]);
    $followers = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Get followed: who this user is following
    $stmt2 = $pdo->prepare("
        SELECT users.email, users.username
        FROM follows
        JOIN users ON follows.followed_email = users.email
        WHERE follows.follower_email = ?
    ");
    $stmt2->execute([$user_email]);
    $following = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'followers' => $followers,
            'following' => $following
        ]
    ]);
} catch (PDOException $e) {
    error_log("Follow list fetch error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
