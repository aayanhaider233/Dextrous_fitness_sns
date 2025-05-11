<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$logged_in_email = $_SESSION["user_email"]; 
$profile_email = isset($_GET['user']) ? $_GET['user'] : $logged_in_email;

$data = [];

try {
    $stmt = $pdo->prepare("SELECT position FROM users WHERE email = ?");
    $stmt->execute([$profile_email]);
    $position = $stmt->fetchColumn();
    
    if ($position !== false) {
        $data['position'] = $position;
    }
    
    $stmt = $pdo->prepare("SELECT CONCAT(fname, ' ', lname) FROM users WHERE email = ?");
    $stmt->execute([$profile_email]);
    $name = $stmt->fetchColumn();
    
    if ($name !== false) {
        $data['name'] = $name;
    }

    $stmt = $pdo->prepare("SELECT gym_name FROM usergym WHERE user_email = ?");
    $stmt->execute([$profile_email]);
    $gym_name = $stmt->fetchColumn();
    
    if ($gym_name) {
        $data['gym_name'] = $gym_name;
    }
    
    $stmt = $pdo->prepare("SELECT workout_name FROM favworkout WHERE user_email = ?");
    $stmt->execute([$profile_email]);
    $workouts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($workouts)) {
        $data['favorite_workouts'] = implode(', ', $workouts);
    }
    
    if ($position === 'trainee') {
        $stmt = $pdo->prepare("SELECT u.fname, u.lname FROM users u JOIN assigned a ON u.email = a.trainer_email WHERE a.trainee_email = ?");
        $stmt->execute([$profile_email]);
        $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trainer) {
            $data['trainer_name'] = $trainer['fname'] . ' ' . $trainer['lname'];
        }
    }
    
    if ($position === 'trainer') {
        $stmt = $pdo->prepare("SELECT total_trainees FROM trainers WHERE email = ?");
        $stmt->execute([$profile_email]);
        $total_trainees = $stmt->fetchColumn();
        
        if ($total_trainees !== false) {
            $data['total_trainees'] = $total_trainees;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>