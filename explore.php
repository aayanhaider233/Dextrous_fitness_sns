<?php
session_start();
require 'db_config.php';  // Contains PDO connection setup

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

$current_user_email = $_SESSION["user_email"]; // Current user's email

// Fetch user data for the current user
try {
    $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE email = ?");
    $stmt->execute([$current_user_email]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_user) {
        echo "User not found.";
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

try {
    // SQL query to join posts and users tables
    // Also check if current user has liked each post
    $query = "SELECT 
                p.post_id, 
                p.content, 
                p.image_path, 
                p.created_at, 
                p.user_email,
                p.likes,
                u.username, 
                u.profile_pic,
                CASE WHEN pl.id IS NOT NULL THEN 1 ELSE 0 END as user_has_liked,
                (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) as comment_count
              FROM posts p
              JOIN users u ON p.user_email = u.email
              LEFT JOIN post_likes pl ON p.post_id = pl.post_id AND pl.user_email = ?
              ORDER BY p.created_at DESC
              LIMIT 50"; // Limit to recent 50 posts for performance
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$current_user_email]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching posts: " . $e->getMessage();
    $posts = []; // Empty array in case of error
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

    
    .search-container {
      position: relative;
      max-width: 600px;
      margin: 100px auto 20px;
      z-index: 990;
    }
  
  .search-bar {
    display: flex;
    align-items: center;
    background-color: #333;
    border-radius: 25px;
    padding: 8px 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    margin-top: -30px;
    border: 1px solid #444;
    transition: all 0.3s ease;
  }
  
  .search-bar:focus-within {
    border-color: #ff7f50;
    box-shadow: 0 0 0 2px rgba(255, 127, 80, 0.2);
  }
  
  .search-icon {
    color: #888;
    margin-right: 10px;
    font-size: 16px;
  }
  
  #userSearchInput {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 15px;
    width: 100%;
    padding: 8px 0;
    outline: none;
  }
  
  #userSearchInput::placeholder {
    color: #777;
  }
  
  .search-clear {
    color: #777;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
    padding: 5px;
  }
  
  .search-clear.visible {
    opacity: 1;
  }
  
  .search-clear:hover {
    color: #ff7f50;
  }
  
  .search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: #333;
    border-radius: 10px;
    margin-top: 5px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 995;
    border: 1px solid #444;
  }
  
  .search-result {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #444;
    transition: background-color 0.2s;
  }
  
  .search-result:last-child {
    border-bottom: none;
  }
  
  .search-result:hover {
    background-color: #444;
  }
  
  .search-result-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
    border: 1px solid #555;
  }
  
  .search-result-name {
    font-weight: 500;
    color: #fff;
  }
  
  .search-no-results {
    padding: 15px;
    text-align: center;
    color: #777;
    font-style: italic;
  }
  
  .search-loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 15px;
    color: #777;
  }
  
  .search-loading .loading-spinner {
    width: 15px;
    height: 15px;
    margin-right: 10px;
  }
  
  @media (max-width: 767px) {
    .search-container {
      margin: 80px 15px 15px;
    }
  }

    .main-content-area {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      padding-top: 20px;
      padding-bottom: 50px;
    }

    .page-title {
      text-align: center;
      margin-bottom: 30px;
      color: #ff7f50;
      font-size: 32px;
    }
    
    .feed-container {
      width: 100%;
    }
    
    .post-item {
      background-color: #333;
      border-radius: 10px;
      margin-bottom: 25px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
    
    .post-header {
      display: flex;
      align-items: center;
      padding: 15px;
      border-bottom: 1px solid #444;
    }
    
    .post-user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 12px;
      border: 2px solid #ff7f50;
    }
    
    .post-user-name {
      font-weight: bold;
      color: #fff;
      text-decoration: none;
      transition: color 0.2s;
    }
    
    .post-user-name:hover {
      color: #ff7f50;
    }
    
    .post-content-container {
      padding: 15px;
    }
    
    .post-image {
      width: 100%;
      max-height: 500px;
      object-fit: contain;
      border-radius: 5px;
      margin-bottom: 10px;
    }
    
    .post-text {
      margin-bottom: 15px;
      line-height: 1.5;
      white-space: pre-line;
      word-break: break-word;
    }
    
    .post-footer {
      display: flex;
      justify-content: space-between;
      padding: 10px 15px;
      border-top: 1px solid #444;
      color: #999;
      font-size: 14px;
    }
    
    .post-actions {
      display: flex;
      gap: 20px;
    }
    
    .post-action {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
      transition: color 0.2s;
    }
    
    .post-action:hover {
      color: #ff7f50;
    }
    
    .post-timestamp {
      color: #777;
    }
    
    .empty-feed {
      text-align: center;
      padding: 50px 20px;
      background-color: #333;
      border-radius: 10px;
      color: #999;
    }
    
    .empty-feed i {
      font-size: 40px;
      margin-bottom: 15px;
      color: #555;
    }
    
    /* Floating action button */
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
      margin-top: -2px;
    }
    
    .like-button {
      color: #999;
      cursor: pointer;
      transition: color 0.2s;
    }
    
    .like-button.liked {
      color: #e74c3c;
    }
    
    .like-count {
      margin-left: 5px;
    }
    
    .comments-section {
      background-color: #2a2a2a;
      padding: 15px;
      border-top: 1px solid #444;
      display: none;
    }
    
    .comment-form {
      display: flex;
      margin-bottom: 15px;
    }
    
    .comment-input {
      flex: 1;
      background-color: #444;
      border: none;
      border-radius: 20px;
      padding: 10px 15px;
      color: #fff;
      font-size: 14px;
    }
    
    .comment-input:focus {
      outline: none;
      background-color: #555;
    }
    
    .comment-submit {
      background-color: #ff7f50;
      color: white;
      border: none;
      border-radius: 20px;
      padding: 8px 15px;
      margin-left: 10px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .comment-submit:hover {
      background-color: #ff6b3c;
    }
    
    .comment-submit:disabled {
      background-color: #777;
      cursor: not-allowed;
    }
    
    .comments-list {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .comment-item {
      display: flex;
      margin-bottom: 12px;
      position: relative;
    }
    
    .comment-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
    }
    
    .comment-content {
      flex: 1;
      background-color: #444;
      border-radius: 10px;
      padding: 10px 12px;
      position: relative;
    }
    
    .comment-username {
      font-weight: bold;
      color: #ff7f50;
      font-size: 14px;
      margin-bottom: 4px;
    }
    
    .comment-text {
      color: #fff;
      font-size: 14px;
      line-height: 1.4;
      word-break: break-word;
    }
    
    .comment-time {
      color: #777;
      font-size: 12px;
      margin-top: 5px;
    }
    
    .comment-delete {
      color: #999;
      cursor: pointer;
      font-size: 12px;
      position: absolute;
      top: 8px;
      right: 10px;
      transition: color 0.2s;
    }
    
    .comment-delete:hover {
      color: #ff4136;
    }
    
    .no-comments {
      text-align: center;
      color: #777;
      padding: 15px 0;
      font-style: italic;
    }
    
    .comment-count {
      margin-left: 5px;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: #ff7f50;
      animation: spin 1s ease-in-out infinite;
      margin: 0 auto;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .loading-comments {
      text-align: center;
      padding: 15px 0;
      color: #777;
    }
    
    /* Custom Delete Confirmation Modal */
.delete-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 1010;
  backdrop-filter: blur(3px);
}

.delete-modal-content {
  background-color: #333;
  border-radius: 10px;
  width: 90%;
  max-width: 400px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  animation: fadeIn 0.3s ease;
}

.delete-modal-content h3 {
  color: #ff7f50;
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 20px;
}

.delete-modal-content p {
  margin-bottom: 20px;
  color: #fff;
}

.delete-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.cancel-button {
  background-color: #555;
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 8px 15px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.delete-button {
  background-color: #e74c3c;
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 8px 15px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.cancel-button:hover {
  background-color: #666;
}

.delete-button:hover {
  background-color: #c0392b;
}

/* Toast Notifications */
#toast-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1020;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.toast {
  display: flex;
  align-items: center;
  background-color: #333;
  color: #fff;
  border-radius: 5px;
  padding: 12px 15px;
  min-width: 250px;
  max-width: 350px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  transform: translateX(100%);
  opacity: 0;
  transition: transform 0.3s, opacity 0.3s;
}

.toast.show {
  transform: translateX(0);
  opacity: 1;
}

.toast-icon {
  margin-right: 12px;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast-message {
  flex: 1;
}

.toast-success {
  border-left: 4px solid #2ecc71;
}

.toast-success .toast-icon {
  color: #2ecc71;
}

.toast-error {
  border-left: 4px solid #e74c3c;
}

.toast-error .toast-icon {
  color: #e74c3c;
}

.toast-info {
  border-left: 4px solid #3498db;
}

.toast-info .toast-icon {
  color: #3498db;
}

.toast-loading {
  border-left: 4px solid #f39c12;
}

.loading-spinner-small {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,.1);
  border-radius: 50%;
  border-top-color: #f39c12;
  animation: spin 1s ease-in-out infinite;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}

@media (max-width: 767px) {
  #toast-container {
    left: 20px;
    right: 20px;
    bottom: 20px;
  }
  
  .toast {
    width: 100%;
    max-width: 100%;
  }
}

    /* Responsive adjustments */
    @media (max-width: 767px) {
      .main-content-area {
        padding: 100px 15px 30px;
      }
      
      .page-title {
        font-size: 24px;
        margin-bottom: 20px;
      }
      
      .post-actions {
        gap: 15px;
      }
      
      .floating-btn {
        right: 15px;
        width: 50px;
        height: 50px;
      }
      
      .comment-form {
        flex-direction: column;
      }
      
      .comment-submit {
        margin-left: 0;
        margin-top: 10px;
        width: 100%;
      }
    }
  </style>
</head>
<body>
<style> 
  body, html {
      box-sizing: border-box;
      cursor: default;
    }
  a, button, .menu-item, .floating-btn, .post-action {
    cursor: pointer;
  }
</style>

<div class="header">
  <div class="w-layout-blockcontainer main-container w-container">
    <div id="home" class="header-inner w-nav">
      <a href="index.php" class="logo w-inline-block">Dextrous</a>
      <div class="hamburger-menu-button" id="menuToggle">
        <i class="fas fa-bars"></i>
      </div>
    </div>
  </div>
</div>

<div class="search-container">
  <div class="search-bar">
    <i class="fas fa-search search-icon"></i>
    <input type="text" id="userSearchInput" placeholder="Search users..." autocomplete="off">
    <div class="search-clear" id="clearSearch"><i class="fas fa-times"></i></div>
  </div>
  <div id="searchResults" class="search-results"></div>
</div>
<!-- Sliding Menu -->
<div class="sliding-menu" id="slidingMenu">
  <div class="menu-items">
    <a href="index.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
    <a href="explore.php" class="menu-item current"><i class="fas fa-compass"></i> Explore</a>
    <a href="gyms.php" class="menu-item"><i class="fas fa-dumbbell"></i> Gyms</a>
    <a href="workout_library.php" class="menu-item"><i class="fas fa-running"></i> Workouts</a>
    <a href="account.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="edit-account.php" class="menu-item"><i class="fas fa-cog"></i> Edit Profile</a>
    <a href="login.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<!-- Overlay -->
<div class="menu-overlay" id="menuOverlay"></div>

<!-- Main content -->
<div class="main-content-area">
  <h1 class="page-title">Explore</h1>
  
  <div class="feed-container">
    <?php if (empty($posts)): ?>
      <div class="empty-feed">
        <i class="fas fa-search"></i>
        <p>No posts found. Be the first to share something!</p>
      </div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?php 
          // Get user profile picture or use default
          $profile_pic_path = (!empty($post['profile_pic'])) ? "user_dp/" . htmlspecialchars($post['profile_pic']) : "user_dp/default.jpg";
        ?>
        <div class="post-item">
          <div class="post-header">
            <a href="account.php?user=<?php echo urlencode($post['user_email']); ?>">
              <img src="<?php echo $profile_pic_path; ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="post-user-avatar">
            </a>
            <a href="account.php?user=<?php echo urlencode($post['user_email']); ?>" class="post-user-name">
              <?php echo htmlspecialchars($post['username']); ?>
            </a>
          </div>
          
          <div class="post-content-container">
            <?php if (!empty($post['image_path'])): ?>
              <img src="post_images/<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
            <?php endif; ?>
            
            <div class="post-text">
              <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
          </div>
          
          <div class="post-footer">
            <div class="post-actions">
              <div class="post-action like-button <?php echo ($post['user_has_liked'] == 1) ? 'liked' : ''; ?>" data-post-id="<?php echo $post['post_id']; ?>">
                <i class="<?php echo ($post['user_has_liked'] == 1) ? 'fas' : 'far'; ?> fa-heart"></i>
                <span class="like-count"><?php echo $post['likes']; ?></span>
              </div>
              <div class="post-action comment-toggle" data-post-id="<?php echo $post['post_id']; ?>">
                <i class="far fa-comment"></i>
                <span>Comments</span>
                <span class="comment-count">(<?php echo $post['comment_count']; ?>)</span>
              </div>
              <!--
                <div class="post-action">
                    <i class="far fa-share-square"></i>
                    <span>Share</span>
                </div>
                -->
            </div>
            
            <div class="post-timestamp">
              <?php 
                $timestamp = strtotime($post['created_at']);
                echo date('F j, Y \a\t g:i a', $timestamp); 
              ?>
            </div>
          </div>
          
          <!-- Comments Section -->
          <div class="comments-section" id="comments-section-<?php echo $post['post_id']; ?>">
            <div class="comment-form">
              <input type="text" class="comment-input" placeholder="Write a comment..." data-post-id="<?php echo $post['post_id']; ?>">
              <button class="comment-submit" disabled data-post-id="<?php echo $post['post_id']; ?>">Post</button>
            </div>
            
            <div class="loading-comments" id="loading-comments-<?php echo $post['post_id']; ?>">
              <div class="loading-spinner"></div>
              <p>Loading comments...</p>
            </div>
            
            <div class="comments-list" id="comments-list-<?php echo $post['post_id']; ?>">
              <!-- Comments will be loaded here dynamically -->
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- New Post button -->
<a href="post.php" class="floating-btn">
  <span class="plus-icon">+</span>
</a>

<script src="js/jquery.min.js" type="text/javascript"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
  
    const searchInput = document.getElementById('userSearchInput');
    const searchResults = document.getElementById('searchResults');
    const clearSearch = document.getElementById('clearSearch');
    let searchTimeout;
    
    // Show/hide clear button based on input content
    searchInput.addEventListener('input', function() {
      const query = this.value.trim();
      
      // Toggle clear button visibility
      if (query.length > 0) {
        clearSearch.classList.add('visible');
      } else {
        clearSearch.classList.remove('visible');
        searchResults.style.display = 'none';
      }
      
      // Debounce search to prevent too many requests
      clearTimeout(searchTimeout);
      
      if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
      }
      
      // Show loading indicator
      searchResults.style.display = 'block';
      searchResults.innerHTML = `
        <div class="search-loading">
          <div class="loading-spinner"></div>
          <span>Searching...</span>
        </div>
      `;
      
      // Wait a short time before sending request
      searchTimeout = setTimeout(() => {
        fetchSearchResults(query);
      }, 300);
    });
    
    // Clear search input
    clearSearch.addEventListener('click', function() {
      searchInput.value = '';
      searchInput.focus();
      this.classList.remove('visible');
      searchResults.style.display = 'none';
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
      if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
      }
    });
    
    function fetchSearchResults(query) {
      fetch('search_users.php?q=' + encodeURIComponent(query))
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response failed');
          }
          return response.json();
        })
        .then(data => {
          // Clear results
          searchResults.innerHTML = '';
          
          if (data.error) {
            searchResults.innerHTML = `<div class="search-no-results">Error: ${data.error}</div>`;
            return;
          }
          
          if (data.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">No users found</div>';
            return;
          }
          
          // Display results
          data.forEach(user => {
            const profilePic = user.profile_pic ? `user_dp/${user.profile_pic}` : 'user_dp/default.jpg';
            const resultItem = document.createElement('div');
            resultItem.className = 'search-result';
            resultItem.innerHTML = `
              <img src="${profilePic}" alt="${user.username}" class="search-result-avatar">
              <div class="search-result-name">${user.username}</div>
            `;
            
            resultItem.addEventListener('click', () => {
              window.location.href = `account.php?user=${encodeURIComponent(user.email)}`;
            });
            
            searchResults.appendChild(resultItem);
          });
        })
        .catch(error => {
          console.error('Search error:', error);
          searchResults.innerHTML = '<div class="search-no-results">Error fetching results</div>';
        });
    }
  });
  const menuToggle = document.getElementById('menuToggle');
  const slidingMenu = document.getElementById('slidingMenu');
  const menuOverlay = document.getElementById('menuOverlay');
  
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
  
  // Like button functionality - UPDATED
  const likeButtons = document.querySelectorAll('.like-button');
  likeButtons.forEach(button => {
    // Initialize heart icon based on server data
    if (button.classList.contains('liked')) {
      const heartIcon = button.querySelector('i');
      heartIcon.classList.remove('far');
      heartIcon.classList.add('fas');
    }
    
    button.addEventListener('click', function() {
      const postId = this.getAttribute('data-post-id');
      const likeCount = this.querySelector('.like-count');
      const heartIcon = this.querySelector('i');
      
      // Toggle like status
      if (this.classList.contains('liked')) {
        // Unlike - optimistic UI update
        this.classList.remove('liked');
        heartIcon.classList.remove('fas');
        heartIcon.classList.add('far');
        likeCount.textContent = Math.max(0, parseInt(likeCount.textContent) - 1);
        
        // Send unlike request
        sendLikeRequest(postId, false);
      } else {
        // Like - optimistic UI update
        this.classList.add('liked');
        heartIcon.classList.remove('far');
        heartIcon.classList.add('fas');
        likeCount.textContent = parseInt(likeCount.textContent) + 1;
        
        // Send like request
        sendLikeRequest(postId, true);
      }
    });
  });

  // Function to handle like/unlike AJAX request
  function sendLikeRequest(postId, isLike) {
    // Create form data
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('like', isLike ? 1 : 0);
    
    fetch('like_post.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Update UI with the correct like count from server
        const likeButton = document.querySelector(`.like-button[data-post-id="${postId}"]`);
        if (likeButton) {
          const likeCount = likeButton.querySelector('.like-count');
          const heartIcon = likeButton.querySelector('i');
          
          likeCount.textContent = data.likeCount;
          
          // Ensure heart icon matches the server's isLiked state
          if (data.isLiked) {
            likeButton.classList.add('liked');
            heartIcon.classList.remove('far');
            heartIcon.classList.add('fas');
          } else {
            likeButton.classList.remove('liked');
            heartIcon.classList.remove('fas');
            heartIcon.classList.add('far');
          }
        }
      } else {
        console.error('Error updating like status:', data.message);
        // Revert UI changes if server request failed
        revertLikeUI(postId, isLike);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      // Revert UI changes if fetch failed
      revertLikeUI(postId, isLike);
    });
  }

  // Function to revert UI changes if the server request fails
  function revertLikeUI(postId, wasLiking) {
    const likeButton = document.querySelector(`.like-button[data-post-id="${postId}"]`);
    if (likeButton) {
      const likeCount = likeButton.querySelector('.like-count');
      const heartIcon = likeButton.querySelector('i');
      
      if (wasLiking) {
        // Revert like
        likeButton.classList.remove('liked');
        heartIcon.classList.remove('fas');
        heartIcon.classList.add('far');
        likeCount.textContent = Math.max(0, parseInt(likeCount.textContent) - 1);
      } else {
        // Revert unlike
        likeButton.classList.add('liked');
        heartIcon.classList.remove('far');
        heartIcon.classList.add('fas');
        likeCount.textContent = parseInt(likeCount.textContent) + 1;
      }
    }
  }
  
  // COMMENT FUNCTIONALITY - IMPROVED CODE

// Comment toggle functionality
const commentToggles = document.querySelectorAll('.comment-toggle');
commentToggles.forEach(toggle => {
  toggle.addEventListener('click', function() {
    const postId = this.getAttribute('data-post-id');
    const commentsSection = document.getElementById(`comments-section-${postId}`);
    
    // Toggle display
    if (commentsSection.style.display === 'block') {
      commentsSection.style.display = 'none';
    } else {
      commentsSection.style.display = 'block';
      loadComments(postId);
    }
  });
});

// Comment input functionality
const commentInputs = document.querySelectorAll('.comment-input');
commentInputs.forEach(input => {
  input.addEventListener('input', function() {
    const postId = this.getAttribute('data-post-id');
    const submitButton = document.querySelector(`.comment-submit[data-post-id="${postId}"]`);
    
    // Enable/disable submit button based on input
    if (this.value.trim() !== '') {
      submitButton.removeAttribute('disabled');
    } else {
      submitButton.setAttribute('disabled', 'disabled');
    }
  });
  
  // Submit on Enter key
  input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && this.value.trim() !== '') {
      const postId = this.getAttribute('data-post-id');
      const submitButton = document.querySelector(`.comment-submit[data-post-id="${postId}"]`);
      submitButton.click();
    }
  });
});

// Comment submit functionality
const commentSubmits = document.querySelectorAll('.comment-submit');
commentSubmits.forEach(submit => {
  submit.addEventListener('click', function() {
    const postId = this.getAttribute('data-post-id');
    const inputField = document.querySelector(`.comment-input[data-post-id="${postId}"]`);
    const commentText = inputField.value.trim();
    
    if (commentText !== '') {
      submitComment(postId, commentText, inputField);
    }
  });
});

// Function to load comments for a post
function loadComments(postId) {
  const commentsList = document.getElementById(`comments-list-${postId}`);
  const loadingIndicator = document.getElementById(`loading-comments-${postId}`);
  
  // Show loading indicator
  loadingIndicator.style.display = 'block';
  commentsList.style.display = 'none';
  
  // Fetch comments from server (use absolute path)
  fetch(`comment_system.php?post_id=${postId}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin' // This ensures cookies (session) are sent with request
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Server responded with status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      loadingIndicator.style.display = 'none';
      commentsList.style.display = 'block';
      if (data.success) {
        if (data.comments && data.comments.length > 0) {
          // Render comments
          commentsList.innerHTML = '';
          data.comments.forEach(comment => {
            commentsList.appendChild(createCommentElement(comment));
          });
        } else {
          // No comments
          commentsList.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
        }
      } else {
        commentsList.innerHTML = `<div class="no-comments">Error loading comments: ${data.message || 'Unknown error'}</div>`;
        console.error('Error loading comments:', data);
      }
    })
    .catch(error => {
      loadingIndicator.style.display = 'none';
      commentsList.style.display = 'block';
      commentsList.innerHTML = '<div class="no-comments">Error loading comments. Please try again later.</div>';
      console.error('Error fetching comments:', error);
    });
}

// Function to submit a new comment
function submitComment(postId, commentText, inputField) {
  const commentsList = document.getElementById(`comments-list-${postId}`);
  const submitButton = document.querySelector(`.comment-submit[data-post-id="${postId}"]`);
  
  // Disable submit button while processing
  submitButton.setAttribute('disabled', 'disabled');
  
  // Create form data
  const formData = new FormData();
  formData.append('action', 'add_comment');
  formData.append('post_id', postId);
  formData.append('comment_text', commentText);
  
  fetch('comment_system.php', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin' // This ensures cookies (session) are sent with request
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Server responded with status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Comment response:', data); // Debug
      
      if (data.success) {
        // Clear input field
        inputField.value = '';
        
        // Remove "no comments" message if it exists
        const noComments = commentsList.querySelector('.no-comments');
        if (noComments) {
          noComments.remove();
        }
        
        // Create and add the new comment to the list
        const commentElement = createCommentElement({
          comment_id: data.comment_id,
          username: data.username,
          profile_pic: data.profile_pic,
          comment_text: data.comment_text,
          created_at_formatted: data.created_at,
          is_own_comment: true
        });
        
        commentsList.appendChild(commentElement);
        
        // Update comment count
        const commentCountSpan = document.querySelector(`.comment-toggle[data-post-id="${postId}"] .comment-count`);
        let currentCount = parseInt(commentCountSpan.textContent.match(/\d+/)[0]);
        commentCountSpan.textContent = `${currentCount + 1}`;
      } else {
        console.error('Error posting comment:', data);
        alert('Error posting comment: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Error posting comment:', error);
      alert('Failed to post comment. Please try again.');
    })
    .finally(() => {
      // Re-enable submit button if input still has content
      if (inputField.value.trim() !== '') {
        submitButton.removeAttribute('disabled');
      }
    });
}

// Function to create a comment element
function createCommentElement(comment) {
  const commentItem = document.createElement('div');
  commentItem.className = 'comment-item';
  commentItem.dataset.commentId = comment.comment_id;
  
  // Use htmlEscaped output to prevent XSS
  const username = escapeHtml(comment.username);
  const commentText = escapeHtml(comment.comment_text);
  const timestamp = escapeHtml(comment.created_at_formatted);
  
  commentItem.innerHTML = `
    <img src="${comment.profile_pic}" alt="${username}" class="comment-avatar">
    <div class="comment-content">
      <div class="comment-username">${username}</div>
      <div class="comment-text">${commentText}</div>
      <div class="comment-time">${timestamp}</div>
      ${comment.is_own_comment ? '<div class="comment-delete" data-comment-id="' + comment.comment_id + '"><i class="fas fa-trash-alt"></i></div>' : ''}
    </div>
  `;
  
  // Add delete functionality if it's the user's own comment
  if (comment.is_own_comment) {
    const deleteButton = commentItem.querySelector('.comment-delete');
    deleteButton.addEventListener('click', function() {
      const commentId = this.getAttribute('data-comment-id');
      const postId = commentItem.closest('.comments-section').id.replace('comments-section-', '');
      deleteComment(commentId, postId, commentItem);
    });
  }
  
  return commentItem;
}

// HTML escape function to prevent XSS
function escapeHtml(text) {
  if (!text) return '';
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

// Function to delete a comment
function deleteComment(commentId, postId, commentElement) {
  // We'll use our custom modal instead of browser confirm
  showDeleteConfirmation(commentId, postId, commentElement);
}

// Function to show a custom delete confirmation modal
function showDeleteConfirmation(commentId, postId, commentElement) {
  // Create modal overlay if it doesn't exist
  if (!document.getElementById('delete-confirmation-modal')) {
    const modalHtml = `
      <div id="delete-confirmation-modal" class="delete-modal-overlay">
        <div class="delete-modal-content">
          <h3>Delete Comment</h3>
          <p>Are you sure you want to delete this comment?</p>
          <div class="delete-modal-actions">
            <button id="cancel-delete" class="cancel-button">Cancel</button>
            <button id="confirm-delete" class="delete-button">Delete</button>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Add event listener for Cancel button
    document.getElementById('cancel-delete').addEventListener('click', function() {
      document.getElementById('delete-confirmation-modal').style.display = 'none';
    });
  }
  
  // Show the modal
  const modal = document.getElementById('delete-confirmation-modal');
  modal.style.display = 'flex';
  
  // Remove any previous event listener for the delete button
  const deleteButton = document.getElementById('confirm-delete');
  const newDeleteButton = deleteButton.cloneNode(true);
  deleteButton.parentNode.replaceChild(newDeleteButton, deleteButton);
  
  // Add event listener for Delete button
  newDeleteButton.addEventListener('click', function() {
    // Hide modal
    modal.style.display = 'none';
    
    // Show loading indicator
    const loadingToast = showToast('Deleting comment...', 'loading');
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'delete_comment');
    formData.append('comment_id', commentId);
    
    fetch('comment_system.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Server responded with status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      // Hide loading toast
      hideToast(loadingToast);
      
      if (data.success) {
        // Show success toast
        showToast('Comment deleted successfully', 'success', 2000);
        
        // Remove comment from DOM
        commentElement.remove();
        
        // Update comment count
        const commentCountSpan = document.querySelector(`.comment-toggle[data-post-id="${postId}"] .comment-count`);
        let currentCount = parseInt(commentCountSpan.textContent.match(/\d+/)[0]);
        commentCountSpan.textContent = `(${Math.max(0, currentCount - 1)})`;
        
        // If no comments left, show the "no comments" message
        const commentsList = document.getElementById(`comments-list-${postId}`);
        if (commentsList.children.length === 0) {
          commentsList.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
        }
      } else {
        // Show error toast
        showToast('Error deleting comment: ' + (data.message || 'Unknown error'), 'error');
        console.error('Error deleting comment:', data);
      }
    })
    .catch(error => {
      // Hide loading toast
      hideToast(loadingToast);
      
      // Show error toast
      showToast('Failed to delete comment. Please try again.', 'error');
      console.error('Error deleting comment:', error);
    });
  });
}

// Helper function to show toast messages
function showToast(message, type = 'info', duration = 3000) {
  // Create toast container if it doesn't exist
  if (!document.getElementById('toast-container')) {
    const toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    document.body.appendChild(toastContainer);
  }
  
  // Create toast element
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  // Create icon based on type
  let icon = '';
  switch(type) {
    case 'success':
      icon = '<i class="fas fa-check-circle"></i>';
      break;
    case 'error':
      icon = '<i class="fas fa-exclamation-circle"></i>';
      break;
    case 'loading':
      icon = '<div class="loading-spinner-small"></div>';
      break;
    default:
      icon = '<i class="fas fa-info-circle"></i>';
  }
  
  toast.innerHTML = `
    <div class="toast-icon">${icon}</div>
    <div class="toast-message">${message}</div>
  `;
  
  // Add to container
  const container = document.getElementById('toast-container');
  container.appendChild(toast);
  
  // Show with animation
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);
  
  // Auto remove after duration (except for loading toasts)
  if (type !== 'loading' && duration > 0) {
    setTimeout(() => {
      hideToast(toast);
    }, duration);
  }
  
  return toast;
}

// Helper function to hide toast
function hideToast(toast) {
  if (!toast) return;
  
  toast.classList.remove('show');
  setTimeout(() => {
    if (toast.parentNode) {
      toast.parentNode.removeChild(toast);
    }
  }, 300); // Match the CSS transition time
}

// Update the event delegation to use our new function
document.addEventListener('click', function(e) {
  if (e.target.closest('.comment-delete')) {
    const deleteButton = e.target.closest('.comment-delete');
    const commentId = deleteButton.getAttribute('data-comment-id');
    const commentItem = deleteButton.closest('.comment-item');
    const postId = commentItem.closest('.comments-section').id.replace('comments-section-', '');
    
    // Use our improved delete function
    deleteComment(commentId, postId, commentItem);
  }
});
</script>
</body>
</html>