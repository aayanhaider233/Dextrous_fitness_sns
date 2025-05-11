<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

$logged_in_email = $_SESSION["user_email"]; 

$profile_email = isset($_GET['user']) ? $_GET['user'] : $logged_in_email;

$is_own_profile = ($profile_email === $logged_in_email);

try {
    $stmt = $pdo->prepare("SELECT username, email, profile_pic, bio, position FROM users WHERE email = ?");
    $stmt->execute([$profile_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: profile.php');
        exit();
    }

    $username = $user['username'];
    $email = $user['email'];
    $profile_pic = $user['profile_pic'];
    $bio = $user['bio'];
    $position = $user['position'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

$is_following = false;
if (!$is_own_profile) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_email = ? AND followed_email = ?");
        $stmt->execute([$logged_in_email, $profile_email]);
        $is_following = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
    }
}

$profile_pic_path = (!empty($profile_pic)) ? "user_dp/" . htmlspecialchars($profile_pic) : "user_dp/default.jpg";

try {
    $stmt = $pdo->prepare("SELECT post_id, content, image_path, created_at FROM posts WHERE user_email = ? ORDER BY created_at DESC");
    $stmt->execute([$profile_email]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching posts: " . $e->getMessage();
    $posts = []; 
}
?>

<!DOCTYPE html>
<html data-wf-page="667187163156a5df09557ed5">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dextrous - Profile</title>
<link href="images/favicon.png" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" href="assets/css/style.css"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
            --primary-bg: #000000;
            --secondary-bg: #1a1a1a;
            --card-bg: #222222;
            --accent-color: #FF7F50;
            --text-color: #ffffff;
            --text-muted: #aaaaaa;
            --card-border: #333333;
        }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: var(--primary-bg); color: var(--text-color); padding-top: 70px; box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }
        a, button, .menu-item { cursor: pointer; }

        .header { position: fixed; top: 0; left: 0; width: 100%; z-index: 1002; background-color: var(--primary-bg); border-bottom: 1px solid var(--card-border); }
        .header-inner { display: flex; align-items: center; justify-content: space-between; padding: 0 20px; max-width: 1200px; margin: 0 auto; height: 70px; }
        .header-inner .logo { font-size: 30px !important; font-weight: bold !important; color: var(--accent-color) !important; text-decoration: none !important; z-index: 1003; }
        .hamburger-menu-button { font-size: 24px; color: var(--text-color); cursor: pointer; z-index: 1003; background: none; border: none; padding: 10px; }
        
        .sliding-menu { position: fixed; top: 0; right: -280px; width: 280px; height: 100%; background-color: var(--card-bg); z-index: 1005; transition: right 0.3s ease; box-shadow: -2px 0 10px rgba(0, 0, 0, 0.5); padding-top: 70px; overflow-y: auto; }
        .sliding-menu.open { right: 0; }
        .menu-items { display: flex; flex-direction: column; }
        .menu-item { padding: 15px 25px; color: var(--text-color); text-decoration: none; font-size: 16px; transition: background-color 0.2s, color 0.2s; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .menu-item:hover, .menu-item.current { background-color: rgba(255, 127, 80, 0.2); color: var(--accent-color); }
        .menu-item i { margin-right: 10px; width: 20px; text-align: center; }

        .menu-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1004; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .menu-overlay.active { opacity: 1; pointer-events: auto; }

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-title { color: var(--accent-color); font-size: 28px; margin: 30px 0 30px 0; font-weight: bold; text-align: center;}

  .user-list-item {
    display: flex; 
    align-items: center;
    margin-bottom: 15px;
    padding: 10px 15px;
    background-color: #444;
    border-radius: 8px; 
    text-decoration: none;
    color: #fff; 
    transition: background-color 0.2s;
  }

  .user-list-item:hover {
    background-color: #555; 
  }

  .user-list-profile-pic {
    width: 40px; /* Adjust size as needed */
    height: 40px; /* Adjust size as needed */
    border-radius: 50%; /* Makes the image circular */
    object-fit: cover; /* Ensures the image covers the area without distortion */
    margin-right: 15px; /* Space between pic and username */
    border: 1px solid #ff7f50; /* Optional: adds a border like the main profile pic */
  }

  .user-list-username {
    font-size: 16px; /* Adjust as needed */
    color: #fff;
    transition: color 0.2s;
  }

  .user-list-item:hover .user-list-username {
    color: #ff7f50;  
  }

  .user-list-username:hover {
    color: #ff7f50 !important; /* Use !important to ensure override if necessary */
    text-decoration: none; /* Keep text decoration consistent */
  }

  /* Ensure the link itself doesn't have default blue color or underline */
  .user-list-item a, .user-list-item a:visited, .user-list-item a:hover, .user-list-item a:active {
      text-decoration: none;
      color: inherit; /* Inherit color from parent, which will be #fff for .user-list-username */
  }

    .follow-btn {
      margin-top: 25px;
      padding: 4px 10px;
      font-size: 0.9rem;
      border: 1px solid #888;
      background-color: #f0f0f0;
      color: #333;
      border-radius: 4px;
      cursor: pointer;
    }

    .follow-btn.following {
      background-color: #ccc;
      color: #000;
    }

    .profile-nav {
      margin-top: 30px;
      width: 200px;
      border-top: 20px solid rgba(255, 255, 255, 0.1);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      position: relative;
      z-index: 100;
      display: block; /* Ensure it's displayed as a block */
      visibility: visible; /* Make sure it's visible */
    }

    .profile-nav-button {
      padding: 15px 10px;
      color: #fff;
      position: relative;
      cursor: pointer;
      overflow: hidden;
      transition: color 0.3s;
      display: block; /* Ensure buttons are displayed */
      z-index: 101;
    }

    .profile-nav-button::before {
      display: none;
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background-color: #fff;
      transition: transform 0.3s ease;
      z-index: -1;
    }

    .profile-nav-button:hover::before,
    .profile-nav-button.active::before {
      transform: translateX(100%);
    }

    .profile-nav-button:hover,
    .profile-nav-button.active {
      background-color: #fff;
      color: #000;
    }

    .content-wrapper {
      position: relative;
      overflow: visible;
      width: 100%;
      height: auto;
    }

    .main-content-area {
      -ms-overflow-style: none;  
      scrollbar-width: none;     
    }

    .main-content-area::-webkit-scrollbar {
      display: none;             
    }

    .content-section {
      position: absolute;
      width: 100%;
      height: auto;
      transition: transform 0.5s ease, opacity 0.5s ease;
      opacity: 0;
      transform: translateX(100%);
      display: block;
      top: 100px;
      left: 0;
    }

    .content-section.active {
      opacity: 1;
      transform: translateX(0);
      z-index: 5;
    }

    .loading-spinner {
      text-align: center;
      padding: 40px;
      font-size: 20px;
      color: #ff7f50;
    }

    .section-title {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 20px;
      padding-bottom: 10px;
    }

    .info-item {
      margin-bottom: 15px;
      padding: 10px 15px;
      background-color: #444;
      border-radius: 8px;
    }

    .info-label {
      color: #ff7f50;
      margin-bottom: 5px;
      display: block;
    }

    .info-value {
      color: #fff;
      font-size: 16px;
    }

    /* Animation classes for page transitions */
    .slide-out {
      animation: slideOut 0.5s forwards;
      z-index: 1;
    }

    .slide-in {
      animation: slideIn 0.5s forwards;
      z-index: 5;
    }

    @keyframes slideOut {
      0% {
        transform: translateX(0);
        opacity: 1;
      }
      100% {
        transform: translateX(-100%);
        opacity: 0;
      }
    }

    @keyframes slideIn {
      0% {
        transform: translateX(100%);
        opacity: 0;
      }
      100% {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .floating-btn {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background-color: #ff7f50;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      transition: transform 0.2s, background-color 0.2s;
      z-index: 999;
      text-decoration: none;
    }
    
    .floating-btn:hover {
      transform: scale(1.05);
      background-color: #ff6b3c;
    }
    
    .floating-btn .plus-icon {
      color: white;
      font-size: 32px;
      font-weight: bold;
      margin-top: -2px; /* Slight adjustment for vertical centering */
    }
    
    /* Post action buttons */
    .post-actions-wrapper {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      clear: both;
      margin-top: 10px;
    }

    .post-actions {
      display: flex;
      justify-content: flex-start;
      gap: 10px;
      float: left; /* Make it float left */
      margin-top: 12px; /* Match the post-time's margin-top */
    }

    .edit-post-btn, .delete-post-btn {
      background-color: transparent;
      border: none;
      color: #999;
      margin-left: -5px;
      font-size: 14px;
      cursor: pointer;
      transition: color 0.2s;
      border-radius: 5px;
    }

    .edit-post-btn:hover {
      color: #4a90e2;
      background-color: rgba(74, 144, 226, 0.1);
    }

    .delete-post-btn:hover {
      color: #e74c3c;
      background-color: rgba(231, 76, 60, 0.1);
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1100;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(5px);
    }

    .modal-content {
      background-color: #333;
      margin: 10% auto;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
      width: 90%;
      max-width: 500px;
      position: relative;
    }

    .close-modal {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close-modal:hover {
      color: #ff7f50;
    }

    .modal h3 {
      margin-top: 0;
      color: #ff7f50;
    }

    .modal textarea {
      width: 100%;
      padding: 10px;
      margin: 15px 0;
      border-radius: 5px;
      background-color: #444;
      color: #fff;
      border: 1px solid #555;
      resize: vertical;
    }

    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 15px;
    }

    .cancel-btn, .save-btn, .delete-btn {
      padding: 8px 15px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
    }

    .cancel-btn {
      background-color: #555;
      color: #fff;
    }

    .save-btn {
      background-color: #4a90e2;
      color: #fff;
    }

    .delete-btn {
      background-color: #e74c3c;
      color: #fff;
    }

    .cancel-btn:hover {
      background-color: #666;
    }

    .save-btn:hover {
      background-color: #5a9ee2;
    }

    .delete-btn:hover {
      background-color: #f74c3c;
    }

    .profile-static {
      position: fixed;
      top: 110px;
      left: 0;
      width: 440px; 
      z-index: 10;
      padding-left: 50px;
      background-color: #000;
      display: flex;
      flex-direction: column;
    }

    .profile-container {
      text-align: left;
      display: flex;
      align-items: flex-start;
    }
    
    .profile-image {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ff7f50;
      margin-right: 50px;
      flex-shrink: 0;
    }
    
    .profile-info {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .profile-username {
      margin-top: 10px;
      margin-bottom: 15px;
      font-size: 20px;
      color: #fff;
    }
    
    .bio-container {
      position: relative;
      overflow: hidden;
      max-height: 2.5em; 
      transition: max-height 0.3s ease;
      color: #ddd;
      max-width: 100%; 
    }
    
    .bio-container.expanded {
      max-height: 1000px; 
    }
    
    .bio-toggle {
      color: rgba(203, 203, 203, 0.83);
      cursor: pointer;
      display: inline-block;
      margin-top: 10px;
      font-size: 16px;
    }
    
    .bio-toggle:hover {
      text-decoration: underline;
    }
    
    .bio-full {
      margin-top: 0px;
      font-size: 16px; 
      line-height: 1.4;
      color: #fff;
    }
    
    .bio-short .bio-toggle {
      display: none;
    }
    
    .main-content-area {
      margin-left: 400px;
      margin-right: 50px;
      width: calc(100% - 540px); 
      height: 100vh;
      overflow-y: auto;
      position: fixed;
      top: 0;
      right: 0;
    }
    
    .posts-section {
      background-color: #333;
      padding: 20px 30px;
      box-shadow: -5px 0 20px rgba(0, 0, 0, 0.3);
      width: 70%;
      min-height: calc(100vh - 80px);
      margin-left: 90px;
      overflow-y: visible;
    }

    .post-item {
      background-color: #444;
      border-radius: 10px;
      padding: 16px;
      margin-bottom: 20px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden; /* Ensure content doesn't overflow */
      clear: both; /* Clear any floats */
      display: block;
    }
    
    .post-item::after {
      content: "";
      display: table;
      clear: both;
    }

    .post-content {
      font-size: 16px;
      line-height: 1.5;
      margin-bottom: 15px;
      color: #fff;
    }
    
    .post-image {
      width: 100%;
      border-radius: 8px;
      margin-bottom: 10px;
      max-height: 500px;
      object-fit: contain;
    }
    
    .post-caption {
      font-size: 14px;
      color: #ddd;
      line-height: 1.4;
      margin-bottom: 10px;
      width: 100%;
      clear: both;
      position: relative;
      display: block;
      overflow: hidden; /* Prevent text overflow */
      word-wrap: break-word; /* Break long words */
    }
    
    .post-time {
      float: right;
      font-size: 12px;
      color: #999;
      display: block;
      text-align: right;
      margin-top: 12px;
    }
    
    .footer {
      position: fixed;
      bottom: 20px;
      left: 20px;
      z-index: 999;
      margin: 0;
      padding: 0;
      color: #777;
      font-size: 14px;
      background: none;
      box-shadow: none;
    }

    /* Main container to hold everything */
    .main-content {
      min-height: 100vh;
      display: flex;
    }
    @media (max-width: 768px) {
      .header-inner .logo { font-size: 24px !important; }

    .profile-static {
        position: relative; /* Becomes part of normal flow */
        width: 100%;
        top: auto;
        margin-top: 20px; /* Adjusted from 100px, body padding handles header space */
        padding-bottom: 20px;
        padding-left: 20px; /* Adjusted from 50px for smaller screens */
        height: auto;
        background-color: transparent; /* Or var(--primary-bg) if preferred */
    }

    .main-content-area {
        margin-left: auto; /* Centering */
        margin-right: auto; /* Centering */
        margin-top: 20px; /* Spacing below profile-static */
        max-width: 90%;
        width: 90%; /* Use more of the screen */
        padding-top: 0; /* Removed extra padding-top as content flows naturally */
        position: relative; /* Ensure it's not fixed */
        height: auto; /* Ensure it's not fixed height */
        top: auto; /* Ensure it's not fixed top */
        /* The width calc and margin-left/right from desktop are overridden here */
    }

    .posts-section { /* Example adjustment if it's inside main-content-area */
        width: 100%; /* Full width within its container */
        margin-left: 0; /* Reset desktop margin */
        padding: 15px; /* Adjust padding for mobile */
    }
    }
  </style>
</head>
<body>

<div class="header">
        <div class="header-inner">
            <a href="index.php" class="logo">Dextrous</a>
            <button class="hamburger-menu-button" id="menuToggle" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <div class="sliding-menu" id="slidingMenu">
        <div class="menu-items">
            <a href="index.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
            <a href="explore.php" class="menu-item"><i class="fas fa-compass"></i> Explore</a>
            <a href="gyms.php" class="menu-item"><i class="fas fa-dumbbell"></i> Gyms</a>
            <a href="workout_library.php" class="menu-item"><i class="fas fa-running"></i> Workouts</a>
            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="account.php" class="menu-item current"><i class="fas fa-user"></i> Profile</a>
                <a href="edit-account.php" class="menu-item"><i class="fas fa-cog"></i> Edit Profile</a>
                <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="menu-item"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>

<div class="menu-overlay" id="menuOverlay"></div>

<div class="main-content">
  <div class="profile-static">
    <div class="profile-container">
      <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" class="profile-image">
      <div class="profile-info">
        <h2 class="profile-username"><?php echo htmlspecialchars($username); ?></h2>
        <div class="bio-container" id="bioContainer">
          <p class="bio-full"><?php echo nl2br(htmlspecialchars($bio)); ?></p>
        </div>
        <div class="bio-toggle" id="bioToggle">See more</div>
      </div>
    </div>
    
    <?php if (!$is_own_profile): ?>
        <button id="followBtn"
                class="follow-btn <?= $is_following ? 'following' : '' ?>"
                data-email="<?= htmlspecialchars($profile_email) ?>">
            <?= $is_following ? 'Unfollow' : 'Follow' ?>
        </button>
    <?php endif; ?>
    <div class="profile-nav">
      <div class="profile-nav-button active" data-section="posts">
        <span class="button-text">Posts</span>
      </div>
      <div class="profile-nav-button" data-section="info">
        <span class="button-text">Info</span>
      </div>
      <div class="profile-nav-button" data-section="followers">
        <span class="button-text">Follower List</span>
      </div>
      <div class="profile-nav-button" data-section="following">
        <span class="button-text">Following List</span>
      </div>
    </div>
  </div>

  <div class="main-content-area">
    <div class="content-wrapper">
      <div class="content-section active" id="postsSection">
        <div class="posts-section">
          <?php if (empty($posts)): ?>
            <div class="post-item">
              <p class="post-content">No posts yet.</p>
            </div>
          <?php else: ?>
            <?php foreach ($posts as $post): ?>
              <div class="post-item">
                <?php if (!empty($post['image_path'])): ?>
                  <!-- Image post -->
                  <img src="post_images/<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
                  <p class="post-caption"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php else: ?>
                  <!-- Text-only post -->
                  <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php endif; ?>
                <?php if ($is_own_profile): ?>
                <div class="post-actions">
                  <button class="edit-post-btn" data-post-id="<?php echo $post['post_id']; ?>" 
                    data-content="<?php echo htmlspecialchars($post['content']); ?>">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="delete-post-btn" data-post-id="<?php echo $post['post_id']; ?>">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </div>
                <?php endif; ?>
                <span class="post-time">
                  <?php 
                    $timestamp = strtotime($post['created_at']);
                    echo date('F j, Y \a\t g:i a', $timestamp); 
                  ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="content-section" id="infoSection">
        <div class="posts-section">
          <div class="section-title">
            <h2>User Information</h2>
          </div>
          <div class="info-content">
            <div class="loading-spinner">
              <i class="fas fa-spinner fa-spin"></i> Loading information...
            </div>
            <div class="info-data" style="display: none;">
            </div>
          </div>
        </div>
      </div>
      
      
      <div class="content-section" id="followersSection">
        <div class="posts-section">
          <div class="section-title">
            <h2>Followers</h2>
          </div>
          <div class="followers-list">
            <p>This user is currently not followed by anyone.</p>
          </div>
        </div>
      </div>
      
      <!-- Following Section -->
      <div class="content-section" id="followingSection">
        <div class="posts-section">
          <div class="section-title">
            <h2>Following</h2>
          </div>
          <div class="following-list">
            <p>This user currently does not follow anyone.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

<!-- Floating Action Button -->
<a href="post.php" class="floating-btn">
  <span class="plus-icon">+</span>
</a>

<?php if ($is_own_profile): ?>
<!-- Edit Post Modal -->
<div id="editPostModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3 style="font-weight: 550;">Edit Post</h3>
    <form id="editPostForm" method="post">
      <input type="hidden" id="edit_post_id" name="post_id">
      <textarea id="edit_content" name="content" rows="4" placeholder="Edit your caption..."></textarea>
      <div class="modal-buttons">
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deletePostModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3 style="font-weight: 550;">Delete Post</h3>
    <p style="font-size: 16px; margin-bottom: 15px;">Are you sure you want to delete this post? This action cannot be undone.</p>
    <form id="deletePostForm" method="post">
      <input type="hidden" id="delete_post_id" name="post_id">
      <div class="modal-buttons">
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="submit" class="delete-btn">Delete Post</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
  const profileEmailForJs = "<?php echo htmlspecialchars($profile_email); ?>";
</script>
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/plugins.js" type="text/javascript"></script>
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const slidingMenu = document.getElementById('slidingMenu');
    const menuOverlay = document.getElementById('menuOverlay');

    if (menuToggle && slidingMenu && menuOverlay) {
        menuToggle.addEventListener('click', function() {
            slidingMenu.classList.toggle('open');
            menuOverlay.classList.toggle('active');
            document.body.style.overflow = slidingMenu.classList.contains('open') ? 'hidden' : '';
        });
        
        menuOverlay.addEventListener('click', function() {
            slidingMenu.classList.remove('open');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // --- Follow/Unfollow Functionality ---
    const followBtn = document.getElementById('followBtn');
    if (followBtn) {
      followBtn.addEventListener('click', function () {
        const btn = this;
        const targetEmail = btn.dataset.email;
        const action = btn.classList.contains('following') ? 'unfollow' : 'follow';

        fetch('follow_handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=${action}&user_email=${encodeURIComponent(targetEmail)}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            if (action === 'follow') {
              btn.textContent = 'Unfollow';
              btn.classList.add('following');
            } else {
              btn.textContent = 'Follow';
              btn.classList.remove('following');
            }
            loadFollowLists(); // Refresh follower/following lists
          } else {
            // Consider using a more user-friendly notification system instead of alert
            console.error('Follow/Unfollow Error:', data.message);
            alert(data.message || 'Something went wrong.');
          }
        })
        .catch(err => {
          console.error('Follow/Unfollow Request failed', err);
          alert('An error occurred. Please try again.');
        });
      });
    }

    // --- Post Edit/Delete Modals Functionality ---
    const editPostModal = document.getElementById('editPostModal');
    const deletePostModal = document.getElementById('deletePostModal');
    const closeButtons = document.querySelectorAll('.close-modal, .cancel-btn');
    const editPostForm = document.getElementById('editPostForm');
    const deletePostForm = document.getElementById('deletePostForm');

    document.querySelectorAll('.edit-post-btn').forEach(button => {
      button.addEventListener('click', function() {
        const postId = this.getAttribute('data-post-id');
        const content = this.getAttribute('data-content');
        // Ensure the modal and form elements exist before trying to set values
        if (document.getElementById('edit_post_id')) {
            document.getElementById('edit_post_id').value = postId;
        }
        if (document.getElementById('edit_content')) {
            document.getElementById('edit_content').value = content;
        }
        if(editPostModal) editPostModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
      });
    });

    document.querySelectorAll('.delete-post-btn').forEach(button => {
      button.addEventListener('click', function() {
        const postId = this.getAttribute('data-post-id');
         if (document.getElementById('delete_post_id')) {
            document.getElementById('delete_post_id').value = postId;
        }
        if(deletePostModal) deletePostModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
      });
    });

    closeButtons.forEach(button => {
      button.addEventListener('click', function() {
        if(editPostModal) editPostModal.style.display = 'none';
        if(deletePostModal) deletePostModal.style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
      });
    });

    window.addEventListener('click', function(event) {
      if (event.target === editPostModal && editPostModal) {
        editPostModal.style.display = 'none';
        document.body.style.overflow = '';
      }
      if (event.target === deletePostModal && deletePostModal) {
        deletePostModal.style.display = 'none';
        document.body.style.overflow = '';
      }
    });

    if(editPostForm){
        editPostForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const postId = document.getElementById('edit_post_id').value;
          const content = document.getElementById('edit_content').value;

          fetch('edit_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${encodeURIComponent(postId)}&content=${encodeURIComponent(content)}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update the post content directly on the page
              const postElements = document.querySelectorAll('.post-item');
              postElements.forEach(post => {
                const editBtn = post.querySelector('.edit-post-btn');
                if (editBtn && editBtn.getAttribute('data-post-id') === postId) {
                  const contentElement = post.querySelector('.post-content, .post-caption');
                  if (contentElement) {
                    // Update text content safely, use textContent for plain text
                    // If content can be HTML, ensure it's sanitized server-side before display
                    contentElement.textContent = content; // Or .innerHTML if content is HTML and sanitized
                    // Update the data-content attribute for future edits
                    editBtn.setAttribute('data-content', content);
                  }
                }
              });
              if(editPostModal) editPostModal.style.display = 'none';
              document.body.style.overflow = '';
            } else {
              alert('Failed to update post: ' + (data.message || 'Unknown error'));
            }
          })
          .catch(error => {
            console.error('Error updating post:', error);
            alert('An error occurred while updating the post. Please try again.');
          });
        });
    }

    if(deletePostForm){
        deletePostForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const postId = document.getElementById('delete_post_id').value;

          fetch('delete_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${encodeURIComponent(postId)}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Remove the post element from the page
              const postElements = document.querySelectorAll('.post-item');
              postElements.forEach(post => {
                const deleteBtn = post.querySelector('.delete-post-btn'); // Check if it's the delete button or the post ID on the item
                if (deleteBtn && deleteBtn.getAttribute('data-post-id') === postId) {
                  post.remove();
                }
              });
              if(deletePostModal) deletePostModal.style.display = 'none';
              document.body.style.overflow = '';
            } else {
              alert('Failed to delete post: ' + (data.message || 'Unknown error'));
            }
          })
          .catch(error => {
            console.error('Error deleting post:', error);
            alert('An error occurred while deleting the post. Please try again.');
          });
        });
    }

    // --- Bio Toggle Functionality ---
    const bioContainer = document.getElementById('bioContainer');
    const bioToggle = document.getElementById('bioToggle');
    if (bioContainer && bioToggle) {
        const bioText = bioContainer.querySelector('.bio-full');
        function checkBioHeight() {
          if (!bioText) return;
          // Ensure styles are computed before getting lineHeight
          const computedStyle = window.getComputedStyle(bioText);
          const lineHeight = parseInt(computedStyle.lineHeight);

          // Temporarily expand to get full scroll height
          const originalMaxHeight = bioContainer.style.maxHeight;
          bioContainer.style.maxHeight = 'none';
          const fullBioTextHeight = bioText.scrollHeight;
          bioContainer.style.maxHeight = originalMaxHeight; // Reset

          // Compare full height to approx 2.5 lines (or whatever your CSS sets)
          // It's better to get the initial max-height from CSS if possible
          // For this example, assuming 2.5 lines is the threshold
          if (fullBioTextHeight <= lineHeight * 2.5 + 5) { // Added a small buffer
            bioToggle.style.display = 'none';
            bioContainer.classList.add('bio-short');
          } else {
            bioToggle.style.display = 'inline-block';
            bioContainer.classList.remove('bio-short');
          }
        }

        bioToggle.addEventListener('click', function() {
          bioContainer.classList.toggle('expanded');
          if (bioContainer.classList.contains('expanded')) {
            bioToggle.textContent = 'See less';
          } else {
            bioToggle.textContent = 'See more';
          }
        });
        // Call checkBioHeight after a short delay to ensure content is rendered
        setTimeout(checkBioHeight, 150);
        // Also consider calling on window resize if layout changes
        window.addEventListener('resize', checkBioHeight);
    }

    // --- Navigation and Content Switching ---
    const navButtons = document.querySelectorAll('.profile-nav-button');
    const contentSections = {
      'posts': document.getElementById('postsSection'),
      'info': document.getElementById('infoSection'),
      'followers': document.getElementById('followersSection'),
      'following': document.getElementById('followingSection')
    };
    let currentSection = 'posts'; // Default active section
    let infoDataLoaded = false;

    // Function to create an info item (used for the Info section)
    function createInfoItem(label, value) {
      const itemDiv = document.createElement('div');
      itemDiv.className = 'info-item';
      const labelSpan = document.createElement('span');
      labelSpan.className = 'info-label';
      labelSpan.textContent = label;
      const valueDiv = document.createElement('div');
      valueDiv.className = 'info-value';
      valueDiv.textContent = value;
      itemDiv.appendChild(labelSpan);
      itemDiv.appendChild(valueDiv);
      return itemDiv;
    }

    // Function to load user information for the Info section
    function loadUserInfo() {
      if (infoDataLoaded) return; // Don't reload if already loaded

      const infoSection = contentSections['info'];
      if (!infoSection) return;

      const loadingSpinner = infoSection.querySelector('.loading-spinner');
      const infoDataContainer = infoSection.querySelector('.info-data');

      if (!loadingSpinner || !infoDataContainer) return;

      // profileEmailForJs should be defined globally in your PHP-generated script tag
      const fetchUrl = profileEmailForJs ? `user_info.php?user=${encodeURIComponent(profileEmailForJs)}` : 'user_info.php';

      loadingSpinner.style.display = 'block';
      infoDataContainer.style.display = 'none';

      fetch(fetchUrl)
        .then(response => response.json())
        .then(result => {
          infoDataContainer.innerHTML = ''; // Clear previous data
          if (result.success && result.data) {
            const data = result.data;
            let hasData = false;
            // Add items if data exists
            if (data.name) { infoDataContainer.appendChild(createInfoItem('Name', data.name)); hasData = true; }
            if (data.position) { infoDataContainer.appendChild(createInfoItem('Position', data.position)); hasData = true; }
            if (data.gym_name) { infoDataContainer.appendChild(createInfoItem('Gym', data.gym_name)); hasData = true; }
            if (data.favorite_workouts) { infoDataContainer.appendChild(createInfoItem('Favorite Workouts', data.favorite_workouts)); hasData = true; }
            if (data.trainer_name) { infoDataContainer.appendChild(createInfoItem('Trainer', data.trainer_name)); hasData = true; }
            if (data.total_trainees) { infoDataContainer.appendChild(createInfoItem('Total Trainees', data.total_trainees.toString())); hasData = true; }

            if (!hasData) {
              const p = document.createElement('p');
              p.className = 'no-info';
              p.textContent = 'No additional information available.';
              infoDataContainer.appendChild(p);
            }
            infoDataLoaded = true; // Mark as loaded
          } else {
            const p = document.createElement('p');
            p.className = 'error-message';
            p.textContent = `Failed to load information: ${result.message || 'Unknown error'}`;
            infoDataContainer.appendChild(p);
          }
        })
        .catch(error => {
          console.error('Error loading user info:', error);
          infoDataContainer.innerHTML = '';
          const p = document.createElement('p');
          p.className = 'error-message';
          p.textContent = 'An error occurred while loading information.';
          infoDataContainer.appendChild(p);
        })
        .finally(() => {
            loadingSpinner.style.display = 'none';
            infoDataContainer.style.display = 'block';
        });
    }

    function createUserCard(user) {
        const cardLink = document.createElement('a');
        cardLink.href = `account.php?user=${encodeURIComponent(user.email || '')}`;
        cardLink.className = 'user-list-item'; // Apply the new style
        const profilePic = document.createElement('img');
        profilePic.className = 'user-list-profile-pic';
        profilePic.src = user.profile_pic ? `user_dp/${user.profile_pic}` : 'user_dp/default.jpg';
        profilePic.alt = `${user.username || 'User'}'s profile picture`;
        profilePic.onerror = function() { // Fallback if image fails to load
            this.src = 'user_dp/default.jpg'; // Path to your default profile picture
        };

        // Username
        const usernameSpan = document.createElement('span');
        usernameSpan.className = 'user-list-username';
        usernameSpan.textContent = user.username || 'Unnamed User';

        // Append image and username to the card link
        cardLink.appendChild(usernameSpan);

        return cardLink;
    }
    // --- END OF UPDATED createUserCard FUNCTION ---


    // Function to load follower and following lists
    function loadFollowLists() {
      // profileEmailForJs should be defined globally
      if (profileEmailForJs) {
        fetch(`follow_list.php?user=${encodeURIComponent(profileEmailForJs)}`)
          .then(response => response.json())
          .then(result => {
            const followersListContainer = document.querySelector('#followersSection .followers-list');
            const followingListContainer = document.querySelector('#followingSection .following-list');

            if (!followersListContainer || !followingListContainer) {
                console.error('Follow list containers not found.');
                return;
            }

            followersListContainer.innerHTML = ''; // Clear previous content
            followingListContainer.innerHTML = ''; // Clear previous content

            if (result.success && result.data) {
              const followers = result.data.followers || [];
              const following = result.data.following || [];

              if (followers.length > 0) {
                followers.forEach(user => {
                    // Ensure 'user' object has email, username, and profile_pic
                    if(user && user.email && user.username) { // Basic check
                        followersListContainer.appendChild(createUserCard(user));
                    }
                });
              } else {
                const p = document.createElement('p');
                p.textContent = 'This user is currently not followed by anyone.';
                followersListContainer.appendChild(p);
              }

              if (following.length > 0) {
                following.forEach(user => {
                    if(user && user.email && user.username) { // Basic check
                        followingListContainer.appendChild(createUserCard(user));
                    }
                });
              } else {
                const p = document.createElement('p');
                p.textContent = 'This user currently does not follow anyone.';
                followingListContainer.appendChild(p);
              }
            } else {
              const errorMsg = `Error loading lists: ${result.message || 'Failed to load'}`;
              if(followersListContainer) followersListContainer.innerHTML = `<p>${errorMsg}</p>`;
              if(followingListContainer) followingListContainer.innerHTML = `<p>${errorMsg}</p>`;
            }
          })
          .catch(error => {
            console.error('Follow list fetch error:', error);
            const followersListContainer = document.querySelector('#followersSection .followers-list');
            const followingListContainer = document.querySelector('#followingSection .following-list');
            if(followersListContainer) followersListContainer.innerHTML = '<p>Error loading followers. Please try again.</p>';
            if(followingListContainer) followingListContainer.innerHTML = '<p>Error loading users followed. Please try again.</p>';
          });
      } else {
        console.error('Profile user email (profileEmailForJs) not available to fetch follow lists.');
        const followersListContainer = document.querySelector('#followersSection .followers-list');
        const followingListContainer = document.querySelector('#followingSection .following-list');
        if(followersListContainer) followersListContainer.innerHTML = '<p>Could not load followers: User context missing.</p>';
        if(followingListContainer) followingListContainer.innerHTML = '<p>Could not load following: User context missing.</p>';
      }
    }

    // Function to switch between content sections (Posts, Info, Followers, Following)
    function switchSection(newSectionId) {
      if (newSectionId === currentSection && contentSections[newSectionId]?.classList.contains('active')) {
        // If the target section is already active, and it's followers/following, refresh its content
        if (newSectionId === 'followers' || newSectionId === 'following') {
            loadFollowLists();
        } else if (newSectionId === 'info' && !infoDataLoaded) { // Reload info if not loaded, or if you want to refresh
            loadUserInfo();
        }
        return;
      }


      navButtons.forEach(button => {
        button.classList.toggle('active', button.dataset.section === newSectionId);
      });

      const currentSectionElement = contentSections[currentSection];
      const newSectionElement = contentSections[newSectionId];

      if (currentSectionElement) {
          currentSectionElement.classList.add('slide-out');
          // Remove active class after a short delay to allow animation to start
          setTimeout(() => {
            currentSectionElement.classList.remove('active');
            currentSectionElement.classList.remove('slide-out'); // Clean up class
          }, 300); // Match this with your CSS animation duration for slide-out
      }


      if (newSectionElement) {
        // Ensure it's not sliding out if it was the previous section or due to other logic
        newSectionElement.classList.remove('slide-out');
        // Add active and slide-in. Active should be added first or simultaneously.
        newSectionElement.classList.add('active');
        newSectionElement.classList.add('slide-in');


        // Load content for the new section
        if (newSectionId === 'info') {
          loadUserInfo();
        } else if (newSectionId === 'followers' || newSectionId === 'following') {
          loadFollowLists();
        }

        setTimeout(() => {
          newSectionElement.classList.remove('slide-in');
        }, 500); // Match this with your CSS animation duration for slide-in
      }
      currentSection = newSectionId;
    }
    navButtons.forEach(button => {
      button.addEventListener('click', function() {
        const sectionId = this.dataset.section;
        switchSection(sectionId);
      });
    });
    if (contentSections[currentSection]?.classList.contains('active')) {
        if (currentSection === 'info') {
            loadUserInfo();
        } else if (currentSection === 'followers' || currentSection === 'following') {
            loadFollowLists();
        }
    } else if (document.querySelector('.profile-nav-button.active')) {
        const activeNavButton = document.querySelector('.profile-nav-button.active');
        if (activeNavButton) {
            const activeSectionId = activeNavButton.dataset.section;
            switchSection(activeSectionId); // This will also handle initial load
        }
    }
    const postsContainer = document.getElementById('postsContainer');
    const postsSectionTarget = document.getElementById('postsSection'); // Target should be the content area within postsSection
    if (postsContainer && postsSectionTarget && postsContainer !== postsSectionTarget.querySelector('.posts-section')) {
        const actualPostDisplayArea = postsSectionTarget.querySelector('.posts-section');
        if(actualPostDisplayArea) {
            actualPostDisplayArea.innerHTML = postsContainer.innerHTML;
        }
    }
  });
</script>

<a href="post.php" class="floating-btn">
  <span class="plus-icon">+</span>
</a>
<?php if ($is_own_profile): ?>
<div id="editPostModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3 style="font-weight: 550;">Edit Post</h3>
    <form id="editPostForm" method="post">
      <input type="hidden" id="edit_post_id" name="post_id">
      <textarea id="edit_content" name="content" rows="4" placeholder="Edit your caption..."></textarea>
      <div class="modal-buttons">
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($is_own_profile): ?>
<div id="deletePostModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3 style="font-weight: 550;">Delete Post</h3>
    <p style="font-size: 16px; margin-bottom: 15px;">Are you sure you want to delete this post? This action cannot be undone.</p>
    <form id="deletePostForm" method="post">
      <input type="hidden" id="delete_post_id" name="post_id">
      <div class="modal-buttons">
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="submit" class="delete-btn">Delete Post</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
</body>
</html>