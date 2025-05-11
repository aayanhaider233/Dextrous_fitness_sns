<?php
session_start();
require 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

$email = $_SESSION["user_email"];
$message = '';

// Store referrer URL in session if not already set
if (!isset($_SESSION['previous_page']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = trim($_POST['content']);
    
    // Validate text content
    if (empty($content)) {
        $message = "Please add a caption to your post.";
    } else {
        // Check if image was uploaded
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
            // Create directory if it doesn't exist
            if (!file_exists('post_images')) {
                mkdir('post_images', 0777, true);
            }
            
            // Get file info
            $file_name = $_FILES['post_image']['name'];
            $file_size = $_FILES['post_image']['size'];
            $file_tmp = $_FILES['post_image']['tmp_name'];
            $file_type = $_FILES['post_image']['type'];
            
            // Generate unique filename
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_name = md5(time() . $file_name) . '.' . $file_ext;
            $file_path = 'post_images/' . $unique_name;
            
            // Allowed file types
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($file_ext, $allowed_extensions)) {
                // No file size limit check
                if (move_uploaded_file($file_tmp, $file_path)) {
                    try {
                        // Insert post data into database
                        $stmt = $pdo->prepare("INSERT INTO posts (user_email, content, image_path) VALUES (?, ?, ?)");
                        if ($stmt->execute([$email, $content, $unique_name])) {
                            $message = "Post created successfully!";
                            
                            // Redirect to previous page after successful post
                            $redirect_to = isset($_SESSION['previous_page']) ? $_SESSION['previous_page'] : 'account.php';
                            unset($_SESSION['previous_page']); // Clear the stored URL
                            header("Location: $redirect_to");
                            exit();
                        } else {
                            $message = "Failed to create post. Please try again.";
                        }
                    } catch (PDOException $e) {
                        $message = "Database error: " . $e->getMessage();
                    }
                } else {
                    $message = "Failed to upload image. Please try again.";
                }
            } else {
                $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        } else {
            // If no image, just save the text content
            try {
                $stmt = $pdo->prepare("INSERT INTO posts (user_email, content) VALUES (?, ?)");
                if ($stmt->execute([$email, $content])) {
                    $message = "Post created successfully!";
                    
                    // Redirect to previous page after successful post
                    $redirect_to = isset($_SESSION['previous_page']) ? $_SESSION['previous_page'] : 'account.php';
                    unset($_SESSION['previous_page']); // Clear the stored URL
                    header("Location: $redirect_to");
                    exit();
                } else {
                    $message = "Failed to create post. Please try again.";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html data-wf-page="667187163156a5df09557ed5">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1" name="viewport" />
  <title>Dextrous - Create post</title>
  <link href="css/normalize.css" rel="stylesheet" type="text/css" />
  <link href="css/layout.css" rel="stylesheet" type="text/css" />
  <link href="images/favicon.png" rel="shortcut icon" type="image/x-icon" />
  <link href="images/webclip.png" rel="apple-touch-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background-color: #000;
      color: #fff;
      overflow-x: hidden;
    }
    
    /* Fixed header */
    .header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      background-color: transparent;
    }
    
    .header-inner {
      background-color: transparent !important;
      border: none !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 15px 20px !important;
      position: relative !important;
      width: 100% !important;
    }

    .file-input-label span {
      font-weight: 400; /* Normal weight (can also use 300 for lighter) */
    }

    /* Style for logo */
    .logo {
      font-size: 24px !important;
      font-weight: bold !important;
      color: #ff7f50 !important;
      text-decoration: none !important;
      display: flex !important;
      align-items: center !important;
      margin-left: 100px !important;
      position: fixed !important;
      top: 50px !important;
      left: -20px !important;
      z-index: 1003;
    }

    /* Hamburger Menu Button */
    .hamburger-menu-button {
      font-size: 24px;
      color: #fff;
      cursor: pointer;
      z-index: 1002;
      position: fixed;
      top: 50px;
      right: 40px;
    }
    
    /* Sliding Menu */
    .sliding-menu {
      position: fixed;
      top: 0;
      right: -280px;
      width: 280px;
      height: 100%;
      background-color: #222;
      z-index: 1001;
      transition: right 0.3s ease;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.5);
      padding-top: 70px;
      overflow-y: auto;
    }
    
    .sliding-menu.open {
      right: 0;
    }
    
    .menu-items {
      display: flex;
      flex-direction: column;
    }
    
    .menu-item {
      padding: 15px 25px;
      color: #fff;
      text-decoration: none;
      font-size: 16px;
      transition: background-color 0.2s;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .menu-item:hover, .menu-item.current {
      background-color: rgba(255, 127, 80, 0.3);
      color: #ff7f50;
    }
    
    .menu-item i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    
    /* Overlay */
    .menu-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
      backdrop-filter: blur(3px);
    }
    
    .menu-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    
    .post-form-container {
        max-width: 550px; /* wider container */
        margin: 120px auto 40px;
        padding: 40px;
        background-color: transparent; /* fully transparent */
        border-radius: 20px;
        backdrop-filter: none; /* remove blur effect */
        box-shadow: none; /* remove box shadow */
    }
    
    .post-form h2 {
      text-align: center;
      margin-bottom: 25px;
    }
    
    .message {
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
      text-align: center;
    }
    
    .success {
      background-color: rgba(76, 175, 80, 0.1);
      border: 1px solid rgba(76, 175, 80, 0.5);
      color: #4CAF50;
    }
    
    .error {
      background-color: rgba(244, 67, 54, 0.1);
      border: 1px solid rgba(244, 67, 54, 0.5);
      color: #F44336;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 15px;
      margin-left: 2px;
      font-weight: 500;
    }
    
    .form-group textarea {
      width: 100%;
      padding: 15px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background-color: rgba(255, 255, 255, 0.05);
      color: #fff;
      min-height: 120px;
      resize: vertical;
    }
    
    .file-input-container {
      position: relative;
      margin-bottom: 20px;
    }
    
    .file-input-label {
      display: block;
      padding: 15px;
      background-color: rgba(255, 127, 80, 0.2);
      border: 1px dashed #ff7f50;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .file-input-label:hover {
      background-color: rgba(255, 127, 80, 0.3);
    }
    
    .file-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .button-container {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }
    
    .cancel-button {
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s;
      text-decoration: none;
      text-align: center;
    }
    
    .cancel-button:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }
    
    .post-button {
      background-color: #ff7f50;
      color: #fff;
      border: none;
      padding: 12px 30px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .post-button:hover {
      background-color: #ff6b3c;
    }
    
    .image-preview {
      margin-top: 15px;
      display: none;
      max-width: 100%;
      border-radius: 10px;
      overflow: hidden;
    }
    
    .image-preview img {
      width: 100%;
      height: auto;
      display: block;
    }
    
    .preview-container {
      position: relative;
    }
    
    .remove-preview {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: rgba(0, 0, 0, 0.5);
      color: white;
      border: none;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
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
  </style>
</head>
<body>
<style> 
  body, html {
      box-sizing: border-box;
      cursor: default;
    }
  a, button, .menu-item, .file-input-label, .remove-preview {
    cursor: pointer;
  }
</style>

<div class="header">
  <div class="w-layout-blockcontainer main-container w-container">
    <div id="home" class="header-inner w-nav">
      <a href="index.php" class="logo w-inline-block w--current">Dextrous</a>
      <div class="hamburger-menu-button" id="menuToggle">
        <i class="fas fa-bars"></i>
      </div>
    </div>
  </div>
</div>

<!-- Sliding Menu -->
<div class="sliding-menu" id="slidingMenu">
  <div class="menu-items">
  <a href="index.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
    <a href="explore.php" class="menu-item"><i class="fas fa-compass"></i> Explore</a>
    <a href="gyms.php" class="menu-item"><i class="fas fa-dumbbell"></i> Gyms</a>
    <a href="workout_library.php" class="menu-item"><i class="fas fa-running"></i> Workouts</a>
    <a href="account.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="edit-account.php" class="menu-item"><i class="fas fa-cog"></i> Edit Profile</a>
    <a href="login.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<!-- Overlay -->
<div class="menu-overlay" id="menuOverlay"></div>

  <!-- Post Form Section -->
  <div class="post-form-container">
    <div class="post-form">
      <h2>Create New Post</h2>
      
      <?php if (!empty($message)) : ?>
        <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <form action="post.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <textarea id="content" name="content" placeholder="Write your caption here..."><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
        </div>
        
        <div class="file-input-container">
          <label class="file-input-label">
            <span id="fileText">Choose an image</span>
            <input type="file" name="post_image" id="post_image" class="file-input" accept=".jpg, .jpeg, .png, .gif">
          </label>
        </div>
        
        <div class="image-preview" id="imagePreview">
          <div class="preview-container">
            <img id="preview" src="#">
            <button type="button" class="remove-preview" id="removePreview">×</button>
          </div>
        </div>
        
        <div class="button-container">
          <a href="index.php" class="cancel-button">Cancel</a>
          <button type="submit" class="post-button">Create Post</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/jquery.min.js" type="text/javascript"></script>
  <script src="js/plugins.js" type="text/javascript"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Menu Toggle Functionality
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
      
      // Handle file selection
      const fileInput = document.getElementById('post_image');
      const fileText = document.getElementById('fileText');
      const imagePreview = document.getElementById('imagePreview');
      const preview = document.getElementById('preview');
      const removePreview = document.getElementById('removePreview');
      
      fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
          const file = this.files[0];
          fileText.textContent = file.name;
          
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
            imagePreview.style.display = 'block';
          }
          reader.readAsDataURL(file);
        }
      });
      
      // Remove preview
      removePreview.addEventListener('click', function() {
        imagePreview.style.display = 'none';
        fileInput.value = '';
        fileText.textContent = 'Choose an image';
      });
    });
  </script>
</body>
</html>
