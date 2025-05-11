<?php
session_start();
require 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

$userEmail = $_SESSION['user_email'];
$action = $_POST['actionSelector'] ?? '';

// Handle profile picture upload
if ($action === 'profile_pic' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'user_dp/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['profile_pic'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error_message'] = "Only JPG, PNG and GIF images are allowed.";
        header('Location: edit-account.php');
        exit();
    }
    
    if ($file['size'] > $maxSize) {
        $_SESSION['error_message'] = "File size cannot exceed 5MB.";
        header('Location: edit-account.php');
        exit();
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Upload failed. Please try again.";
        header('Location: edit-account.php');
        exit();
    }
    
    // Process cropped image data if provided
    if (isset($_POST['croppedImageData']) && !empty($_POST['croppedImageData'])) {
        $imageData = $_POST['croppedImageData'];
        
        // Remove the data URL prefix and decode base64 data
        list($type, $imageData) = explode(';', $imageData);
        list(, $imageData) = explode(',', $imageData);
        $imageData = base64_decode($imageData);
        
        // Generate unique filename based on user email and timestamp
        $fileExtension = str_replace('data:image/', '', $type);
        $fileName = md5($userEmail . time()) . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Retrieve the current profile picture path from the database
        $sql = "SELECT profile_pic FROM users WHERE email = :userEmail";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':userEmail', $userEmail);
        $stmt->execute();
        $currentProfilePic = $stmt->fetchColumn();
        
        // Save the cropped image
        if (file_put_contents($filePath, $imageData)) {
            // If there's an existing profile picture, remove it from the directory
            if ($currentProfilePic && file_exists($uploadDir . $currentProfilePic)) {
                unlink($uploadDir . $currentProfilePic);
            }

            // Update database with new profile picture path
            $sql = "UPDATE users SET profile_pic = :profile_pic WHERE email = :userEmail";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':profile_pic', $fileName);
            $stmt->bindParam(':userEmail', $userEmail);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Profile picture updated successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to update profile picture in database.";
                // Delete the uploaded file if database update fails
                unlink($filePath);
            }
        } else {
            $_SESSION['error_message'] = "Failed to save profile picture.";
        }
    } else {
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = md5($userEmail . time()) . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Retrieve the current profile picture path from the database
        $sql = "SELECT profile_pic FROM users WHERE email = :userEmail";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':userEmail', $userEmail);
        $stmt->execute();
        $currentProfilePic = $stmt->fetchColumn();
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // If there's an existing profile picture, remove it from the directory
            if ($currentProfilePic && file_exists($uploadDir . $currentProfilePic)) {
                unlink($uploadDir . $currentProfilePic);
            }

            // Update database with new profile picture path
            $sql = "UPDATE users SET profile_pic = :profile_pic WHERE email = :userEmail";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':profile_pic', $fileName);
            $stmt->bindParam(':userEmail', $userEmail);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Profile picture updated successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to update profile picture in database.";
                // Delete the uploaded file if database update fails
                unlink($filePath);
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload profile picture.";
        }
    }
    
    header('Location: edit-account.php');
    exit();
}
// Handle bio update
elseif ($action === 'bio' && isset($_POST['bio'])) {
    $newBio = trim($_POST['bio']);
    
    // Truncate bio if it somehow exceeds character limit (client-side validation should prevent this)
    if (strlen($newBio) > 150) {
        $newBio = substr($newBio, 0, 150);
    }
    
    $sql = "UPDATE users SET bio = :newBio WHERE email = :userEmail";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':newBio', $newBio);
    $stmt->bindParam(':userEmail', $userEmail);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Bio updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update bio.";
    }
    
    header('Location: edit-account.php');
    exit();
}
elseif ($action === 'email' && !empty($_POST['email'])) {
    $newEmail = trim($_POST['email']); 

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header('Location: edit-account.php');
        exit();
    }

    $sql = "UPDATE users SET email = :newEmail WHERE email = :userEmail";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':newEmail', $newEmail);
    $stmt->bindParam(':userEmail', $userEmail);

    if ($stmt->execute()) {
        $_SESSION['user_email'] = $newEmail;
        $_SESSION['success_message'] = "Email updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update email.";
    }

    header('Location: edit-account.php');
    exit();
}
elseif ($action === 'password' && !empty($_POST['old_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "New passwords do not match.";
        header('Location: edit-account.php');
        exit();
    }

    $sql = "SELECT password FROM users WHERE email = :userEmail";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':userEmail', $userEmail);
    $stmt->execute();
    $currentHashedPassword = $stmt->fetchColumn();
    
    // Check if old password matches
    if (!password_verify($oldPassword, $currentHashedPassword)) {
        $_SESSION['error_message'] = "Old password is incorrect.";
        header('Location: edit-account.php');
        exit();
    }

    // Only proceed with update if old password was correct
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password = :newPassword WHERE email = :userEmail";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':newPassword', $newHashedPassword);
    $stmt->bindParam(':userEmail', $userEmail);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Password updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update password.";
    }

    header('Location: edit-account.php');
    exit();
}

// Fetch user data including bio
$sql = "SELECT profile_pic, bio FROM users WHERE email = :userEmail";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':userEmail', $userEmail);
$stmt->execute();
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePicture = "user_dp/" . $userData['profile_pic'] ?? 'user_dp/default.png';
$userBio = $userData['bio'] ?? '';
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dextrous - Edit Profile</title>
<link href="images/favicon.png" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
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

    .container { width: 100%; max-width: 440px; }
    .overlay {
      background-color: #2e2e2e;
      padding: 40px;
      border-radius: 10px;
      width: 100%;
      text-align: left;
    }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
      display: block;
      margin: 13px 0 !important;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 5px;
      font-size: 1rem;
      background: #444;
      color: white;
      transition: border 0.3s;
    }
    
    
    .menu-item i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    input::placeholder { color: #ccc; }
    input.valid { border: 2px solid green; }
    input.invalid { border: 2px solid red; }
    button {
      background-color: darkorange;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 5px;
      color: white;
      font-size: 1.1rem;
      margin-top: 18px;
      font-weight: bold;
      transition: background-color 0.2s;
      cursor: pointer;
    }
    button:hover { background-color: #cc7000; }

    .title {
      font-size: 2.8rem;
      color: darkorange;
      font-weight: bold;
      margin-top: 60px;
      justify-content: center;
      justify-items: center;
      margin-bottom: 18px;
      text-align: center;
    }
    .input-container {
      position: relative;
      margin: 13px 0 !important;
    }
    .eye-icon {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      width: 18px;
      height: 18px;
      cursor: pointer;
      color: #ccc;
    }
    .error {
      color: red;
      font-size: 0.85rem;
      margin-top: 8px;
    }
    .message {
      font-size: 1rem;
      margin-bottom: 12px;
      text-align: center;
      background: none;
      background-color: transparent;
      border: none;
      padding: 5px;
    }
    .success-message {
      color: green;
    }
    .error-message {
      color: red;
    }
    .profile-picture-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 20px 0;
    }
    .profile-picture {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      margin: 15px 0;
      border: 3px solid darkorange;
    }
    .file-input-container {
      position: relative;
      margin-top: 10px;
      width: 100%;
    }
    .file-input-label {
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #444;
      color: white;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      margin-bottom: 10px;
    }
    .file-input-label:hover {
      background-color: #555;
    }
    input[type="file"] {
      opacity: 0;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    .file-name {
      margin-top: 5px;
      color: #ccc;
      font-size: 0.85rem;
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    .image-editor {
      display: none;
      flex-direction: column;
      align-items: center;
      width: 100%;
      margin-top: 20px;
    }
    .cropper-container {
      width: 100%;
      max-width: 300px;
      height: 300px;
      background-color: #444;
      margin-bottom: 15px;
    }
    .cropper-controls {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 15px;
      width: 100%;
    }
    .crop-btn {
      background-color: #444;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
    }
    .crop-btn:hover, .zoom-btn:hover, .rotate-btn:hover {
      background-color: #555;
    }
    .crop-btn {
      background-color: darkorange;
    }
    .crop-btn:hover {
      background-color: #cc7000;
    }
    @media (max-width: 480px) {
      .cropper-container {
        max-width: 250px;
        height: 250px;
      }
      .overlay {
        padding: 20px;
      }
    }
    textarea {
      display: block;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 5px;
      font-size: 1rem;
      background: #444;
      color: white;
      resize: vertical;
      min-height: 80px;
      margin: 13px 0 !important;
      font-family: Arial, sans-serif;
    }

    textarea::placeholder {
      color: #ccc;
    }

    .character-counter {
      text-align: right;
      color: #ccc;
      font-size: 0.85rem;
      margin-top: 5px;
    }

    .character-counter.limit-reached {
      color: red;
    }
    body, html {
      box-sizing: border-box;
      cursor: default;
    }
  a, button, .menu-item, .floating-btn {
    cursor: pointer;
  }
  </style>
</head>

<body>
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
<div class="container">
  <div class="title";>Edit profile</div>
  <!-- Sliding Menu -->
<div class="sliding-menu" id="slidingMenu">
  <div class="menu-items">
    <a href="index.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
    <a href="explore.php" class="menu-item"><i class="fas fa-compass"></i> Explore</a>
    <a href="gyms.php" class="menu-item"><i class="fas fa-dumbbell"></i> Gyms</a>
    <a href="workout_library.php" class="menu-item"><i class="fas fa-running"></i> Workouts</a>
    <a href="account.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="edit-account.php" class="menu-item current"><i class="fas fa-cog"></i> Edit Profile</a>
    <a href="login.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>
<div class="menu-overlay" id="menuOverlay"></div>
  <!-- Success Message -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div id="successMessage" class="message success-message">
    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if (isset($_SESSION['error_message'])): ?>
    <div id="errorMessage" class="message error-message">
    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
    <?php endif; ?>

  <div class="overlay">
    <form id="editForm" method="POST" action="edit-account.php" enctype="multipart/form-data">
        <select id="actionSelector" name="actionSelector" required>
          <option value="" disabled selected>Select Action</option>
          <option value="profile_pic">Change Profile Picture</option>
          <option value="bio">Update Bio</option>
          <option value="email">Update Email</option>
          <option value="password">Change Password</option>
        </select>
      
        <div id="profilePictureSection" style="display: none;">
          <div class="profile-picture-container">
            <img id="profileImage" class="profile-picture" src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture">
            <div class="file-input-container">
              <label class="file-input-label">
                Choose Image
                <input type="file" id="profile_pic" name="profile_pic" accept="image/jpeg, image/png, image/gif">
              </label>
              <div id="fileName" class="file-name">No file chosen</div>
            </div>
            <div id="imageError" class="error" style="display: none;">Please select a valid image file (JPG, PNG, GIF).</div>
          </div>
          
          <!-- Image Editor for cropping/resizing -->
          <div id="imageEditor" class="image-editor">
            <div class="cropper-container">
              <img id="cropperImage" src="" alt="Image to crop" style="max-width: 100%;">
            </div>
            <button type="button" class="crop-btn" id="cropBtn">Apply Changes</button>
          </div>
          
          <!-- Hidden input for cropped image data -->
          <input type="hidden" id="croppedImageData" name="croppedImageData">
        </div>

        <div id="bioSection" style="display: none;">
          <textarea id="bio" name="bio" placeholder="Your bio" maxlength="150" rows="5"><?php echo htmlspecialchars($userBio); ?></textarea>
          <div class="character-counter"><span id="charCount">0</span>/150</div>
          <div id="bioError" class="error" style="display: none;">Bio cannot exceed 150 characters.</div>
        </div>
      
        <div id="emailSection" style="display: none;">
          <input type="email" id="email" name="email" placeholder="New Email">
          <div id="emailError" class="error" style="display: none;">Invalid email format.</div>
        </div>
      
        <div id="passwordSection" style="display: none;">
          <input type="password" id="old_password" name="old_password" placeholder="Old Password">
          <input type="password" id="new_password" name="new_password" placeholder="New Password">
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password">
          <div id="passwordError" class="error" style="display: none;">New passwords do not match.</div>
          <div id="emptyPasswordError" class="error" style="display: none;">Please fill in all password fields.</div>
        </div>
      
        <button type="submit" id="submitBtn">Save changes</button>
        <a href="javascript:history.back()"><button type="button" id="backBtn">Cancel</button></a>
      </form>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script>
  const menuToggle = document.getElementById('menuToggle');
  const slidingMenu = document.getElementById('slidingMenu');
  const menuOverlay = document.getElementById('menuOverlay');
  const actionSelector = document.getElementById("actionSelector");
  const profilePictureSection = document.getElementById("profilePictureSection");
  const emailSection = document.getElementById("emailSection");
  const passwordSection = document.getElementById("passwordSection");

  const profilePictureInput = document.getElementById("profile_pic");
  const profileImage = document.getElementById("profileImage");
  const fileName = document.getElementById("fileName");
  const imageError = document.getElementById("imageError");
  
  const oldPasswordField = document.getElementById("old_password");
  const newPasswordField = document.getElementById("new_password");
  const confirmPasswordField = document.getElementById("confirm_password");
  const emailField = document.getElementById("email");
  const form = document.getElementById("editForm");
  const passwordError = document.getElementById("passwordError");
  const emptyPasswordError = document.getElementById("emptyPasswordError");
  const emailError = document.getElementById("emailError");
  const successMessage = document.getElementById("successMessage");
  const errorMessage = document.getElementById("errorMessage");
  
  // Image Editor Elements
  const imageEditor = document.getElementById("imageEditor");
  const cropperImage = document.getElementById("cropperImage");
  const zoomInBtn = document.getElementById("zoomIn");
  const zoomOutBtn = document.getElementById("zoomOut");
  const rotateLeftBtn = document.getElementById("rotateLeft");
  const rotateRightBtn = document.getElementById("rotateRight");
  const cropBtn = document.getElementById("cropBtn");
  const croppedImageData = document.getElementById("croppedImageData");
  const submitBtn = document.getElementById("submitBtn");
  const backBtn = document.getElementById("backBtn");
  
  let cropper = null;

  const bioSection = document.getElementById("bioSection");
  const bioField = document.getElementById("bio");
  const charCount = document.getElementById("charCount");
  const bioError = document.getElementById("bioError");

  // Hide success/error messages after 5 seconds
  if (successMessage || errorMessage) {
    setTimeout(() => {
      if (successMessage) successMessage.style.display = 'none';
      if (errorMessage) errorMessage.style.display = 'none';
    }, 5000);
  }

  actionSelector.addEventListener("change", function() {
    // Hide all sections first
    profilePictureSection.style.display = "none";
    emailSection.style.display = "none";
    passwordSection.style.display = "none";
    bioSection.style.display = "none";
    
    // Show the selected section
    if (this.value === "profile_pic") {
      profilePictureSection.style.display = "block";
    } else if (this.value === "email") {
      emailSection.style.display = "block";
    } else if (this.value === "password") {
      passwordSection.style.display = "block";
    } else if (this.value === "bio") {
      bioSection.style.display = "block";
      // Initialize character count
      updateCharCount();
    }
  });

  bioField.addEventListener("input", updateCharCount);

  function updateCharCount() {
    const length = bioField.value.length;
    charCount.textContent = length;
    
    if (length >= 150) {
      charCount.parentElement.classList.add("limit-reached");
    } else {
      charCount.parentElement.classList.remove("limit-reached");
    }
    
    validateBioLength();
    validateLineCount();
  }

  function validateLineCount() {
    const lines = bioField.value.split('\n');
    if (lines.length > 5) {
      bioField.value = lines.slice(0, 5).join('\n');
      bioError.textContent = "Bio cannot exceed 5 lines.";
      bioError.style.display = "block";
      
      setTimeout(() => {
        if (bioError.textContent === "Bio cannot exceed 5 lines.") {
          bioError.style.display = "none";
        }
      }, 5000);
      
      return false;
    } else if (bioError.textContent === "Bio cannot exceed 5 lines.") {
      bioError.style.display = "none";
    }
    return true;
  }

  function validateBioLength() {
    if (bioField.value.length > 150) {
      bioField.value = bioField.value.substring(0, 150);
      bioError.textContent = "Bio cannot exceed 150 characters.";
      bioError.style.display = "block";
      
      setTimeout(() => {
        if (bioError.textContent === "Bio cannot exceed 150 characters.") {
          bioError.style.display = "none";
        }
      }, 5000);
      
      return false;
    } else if (bioError.textContent === "Bio cannot exceed 150 characters.") {
      bioError.style.display = "none";
    }
    return true;
  }

  profilePictureInput.addEventListener("change", function() {
    const file = this.files[0];
    
    if (file) {
      fileName.textContent = file.name;
      
      // Validate file type
      const fileType = file.type;
      const validImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
      
      if (validImageTypes.includes(fileType)) {
        // Read the file for cropper
        const reader = new FileReader();
        reader.onload = function(e) {
          // Set up cropper
          cropperImage.src = e.target.result;
          
          // Show the image editor
          imageEditor.style.display = "flex";
          
          // Initialize cropper after a small delay to ensure the image is loaded
          setTimeout(() => {
            if (cropper) {
              cropper.destroy();
            }
            
            cropper = new Cropper(cropperImage, {
              aspectRatio: 1, // Square aspect ratio for profile picture
              viewMode: 1,    // Restrict the crop box to not exceed the size of the canvas
              dragMode: 'move', // Allow moving the image
              guides: true,
              highlight: true,
              background: false,
              autoCropArea: 0.8, // Define the automatic cropping area size
              responsive: true,
              cropBoxResizable: true,
              cropBoxMovable: true,
              ready: function() {
                updateCroppedData();
              },
              crop: function() {
                updateCroppedData();
              }
            });
          }, 100);
        };
        reader.readAsDataURL(file);
        imageError.style.display = "none";
      } else {
        imageError.style.display = "block";
        // Reset the file input
        this.value = '';
        fileName.textContent = "No file chosen";
        
        // Hide the image editor
        imageEditor.style.display = "none";
      }
    } else {
      fileName.textContent = "No file chosen";
      
      // Hide the image editor
      imageEditor.style.display = "none";
    }
  });
  
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
  
  // Crop button
  cropBtn.addEventListener("click", function() {
    if (cropper) {
      // Get the cropped canvas
      const canvas = cropper.getCroppedCanvas({
        width: 300,   // Output width
        height: 300,  // Output height
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
      });
      
      // Convert canvas to data URL
      const dataURL = canvas.toDataURL('image/jpeg');
      
      // Set the data URL to the hidden input
      croppedImageData.value = dataURL;
      
      // Update the profile image preview
      profileImage.src = dataURL;
      
      // Hide the image editor
      imageEditor.style.display = "none";
    }
  });

  function validatePasswordFields() {
    if (oldPasswordField.value.trim() === "" || newPasswordField.value.trim() === "" || confirmPasswordField.value.trim() === "") {
      emptyPasswordError.style.display = "block";
      return false;
    } else {
      emptyPasswordError.style.display = "none";
      return true;
    }
  }

  function validatePasswordMatch() {
    if (newPasswordField.value !== confirmPasswordField.value) {
      confirmPasswordField.classList.add("invalid");
      passwordError.style.display = "block";
      return false;
    } else {
      confirmPasswordField.classList.remove("invalid");
      passwordError.style.display = "none";
      return true;
    }
  }

  function validateEmailFormat() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (emailRegex.test(emailField.value.trim())) {
      emailField.classList.add("valid");
      emailField.classList.remove("invalid");
      emailError.style.display = "none";
      return true;
    } else {
      emailField.classList.add("invalid");
      emailField.classList.remove("valid");
      emailError.style.display = "block";
      return false;
    }
  }

  function validateProfilePicture() {
    if (actionSelector.value === "profile_pic") {
      // If we have cropped image data, no need to validate the file input
      if (croppedImageData.value) {
        return true;
      }
      
      if (profilePictureInput.files.length === 0) {
        imageError.textContent = "Please select an image file.";
        imageError.style.display = "block";
        return false;
      }
      
      const file = profilePictureInput.files[0];
      const fileType = file.type;
      const validImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
      
      if (!validImageTypes.includes(fileType)) {
        imageError.textContent = "Please select a valid image file (JPG, PNG, GIF).";
        imageError.style.display = "block";
        return false;
      }
      
      const maxSize = 5 * 1024 * 1024; // 5MB
      if (file.size > maxSize) {
        imageError.textContent = "File size cannot exceed 5MB.";
        imageError.style.display = "block";
        return false;
      }
    }
    
    imageError.style.display = "none";
    return true;
  }

  emailField.addEventListener("input", validateEmailFormat);
  confirmPasswordField.addEventListener("input", validatePasswordMatch);
  
  // Add input event listeners to password fields to check for empty fields
  oldPasswordField.addEventListener("input", validatePasswordFields);
  newPasswordField.addEventListener("input", validatePasswordFields);
  confirmPasswordField.addEventListener("input", validatePasswordFields);

  form.addEventListener("submit", function (e) {
    let isValid = true;

    if (actionSelector.value === "profile_pic") {
      if (!validateProfilePicture()) {
        isValid = false;
      }
    } else if (actionSelector.value === "email") {
      if (!validateEmailFormat()) {
        isValid = false;
      }
    } else if (actionSelector.value === "password") {
      // Check both validations for passwords
      const passwordsValid = validatePasswordFields();
      const passwordsMatch = validatePasswordMatch();
      
      if (!passwordsValid || !passwordsMatch) {
        isValid = false;
      }
    } else if (actionSelector.value === "bio") {
        const bioLengthValid = validateBioLength();
        const bioLinesValid = validateLineCount();
        if (!bioLengthValid || !bioLinesValid) {
          isValid = false;
        }
    } else {
      // No option selected
      isValid = false;
    }

    if (!isValid) {
      e.preventDefault();
    }
  });
  bioField.addEventListener("keyup", validateLineCount);
  if (bioSection.style.display === "block") {
    updateCharCount();
  }
</script>

</body>
</html>
