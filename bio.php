<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['registration_data'])) {
    header("Location: register.php");
    exit();
}

if (isset($_SESSION['cropped_image_data']) && !empty($_SESSION['cropped_image_data'])) {
    // Get the base64 part of the image (remove data:image/png;base64,)
    $image_parts = explode(";base64,", $_SESSION['cropped_image_data']);
    if (count($image_parts) > 1) {
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        
        // Generate unique filename
        $filename = uniqid() . '_profile.' . $image_type;
        $upload_dir = 'user_dp/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $upload_dir . $filename;
        
        // Save the image
        if (file_put_contents($file, $image_base64)) {
            // Store the filename, not the full path
            $_SESSION['profile_image'] = $filename;
        } else {
            $_SESSION['profile_image'] = 'default.jpg';
        }
    } else {
        $_SESSION['profile_image'] = 'default.jpg';
    }
} else {
    $_SESSION['profile_image'] = 'default.jpg';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_registration'])) {
    $bio = isset($_POST['user_bio']) ? trim($_POST['user_bio']) : '';
    
    // Additional validation for bio - ensure it doesn't exceed 150 characters
    if (strlen($bio) > 150) {
        $bio = substr($bio, 0, 150);
    }
    
    // Additional validation for bio - ensure it doesn't have more than 5 lines
    $bio_lines = explode("\n", $bio);
    if (count($bio_lines) > 5) {
        // Keep only the first 5 lines
        $bio_lines = array_slice($bio_lines, 0, 5);
        $bio = implode("\n", $bio_lines);
    }
    
    $profile_pic = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default.jpg';
    
    $registrationData = $_SESSION['registration_data'];
    
    $hashed_password = password_hash($registrationData['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$registrationData['email']]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<script>
                alert('Email is already registered.');
                window.location.href = 'register.php';
              </script>";
    } else {
        // Prepare SQL statement to insert data
        $stmt = $pdo->prepare("INSERT INTO users (fname, lname, username, email, password, age, weight, height, location, position, gender, profile_pic, bio) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        try {
            // Execute the query with the provided data
            $stmt->execute([
                $registrationData['fname'],
                $registrationData['lname'],
                $registrationData['username'],
                $registrationData['email'],
                $hashed_password,
                $registrationData['age'],
                $registrationData['weight'],
                $registrationData['height'],
                $registrationData['location'],
                $registrationData['position'],
                $registrationData['gender'],
                $profile_pic,
                $bio
            ]);

            // Clear session data
            unset($_SESSION['registration_data']);
            unset($_SESSION['cropped_image_data']);
            unset($_SESSION['profile_image']);
            
            // Redirect with a success message
            echo "<script>
                    alert('Registration successful!');
                    window.location.href = 'login.php';
                  </script>";
        } catch (Exception $e) {
            // If an error occurs, reload the page and display an error message
            echo "<script>
                    alert('Error: " . $e->getMessage() . "');
                    window.location.href = 'register.php';
                  </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
  <meta charset="UTF-8" />
  <title>Fitbook - Add Bio</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    * {
      box-sizing: border-box;
      cursor: pointer !important;
    }

    body {
      background: url('images/hero-bg.jpg') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 20px;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      font-family: Arial, sans-serif;
    }

    .container {
      width: 100%;
      max-width: 500px;
    }

    .overlay {
      background-color: #2e2e2e;
      padding: 40px;
      border-radius: 10px;
      width: 100%;
      text-align: center;
    }

    .title {
      font-size: 2.5rem;
      color: darkorange;
      font-weight: bold;
      margin-bottom: 20px;
      text-align: center;
    }

    .subtitle {
      font-size: 1.2rem;
      color: white;
      margin-bottom: 25px;
    }

    textarea {
      width: 100%;
      height: 150px;
      padding: 12px;
      border: none;
      border-radius: 5px;
      background: #444;
      color: white;
      font-size: 1rem;
      resize: none;
      margin-bottom: 10px;
      font-family: Arial, sans-serif;
      line-height: 1.5;
    }

    .counter-container {
      display: flex;
      justify-content: space-between;
      color: #ccc;
      font-size: 0.9rem;
      margin-bottom: 20px;
    }

    .character-counter, .line-counter {
      color: #ccc;
      font-size: 0.9rem;
    }

    .character-counter.limit-reached, .line-counter.limit-reached {
      color: red;
    }

    .error {
      color: red;
      font-size: 0.85rem;
      margin-top: 8px;
      text-align: left;
    }

    .buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }

    button {
      background-color: darkorange;
      border: none;
      padding: 12px 25px;
      border-radius: 5px;
      color: white;
      font-size: 1.1rem;
      font-weight: bold;
      transition: background-color 0.2s;
    }

    button:hover {
      background-color: #cc7000;
    }

    .skip-btn {
      background-color: #555;
    }

    .skip-btn:hover {
      background-color: #666;
    }

    /* Responsive adjustments */
    @media (max-height: 700px) {
      .container {
        max-width: 450px;
      }
      
      .title {
        font-size: 2.2rem;
        margin-bottom: 15px;
      }
      
      .subtitle {
        font-size: 1rem;
        margin-bottom: 20px;
      }
      
      textarea {
        height: 120px;
      }
      
      .overlay {
        padding: 30px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="title">Fitbook</div>
    <div class="overlay">
      <h2 class="subtitle">Add Your Bio</h2>
      
      <form id="bioForm" action="bio.php" method="POST">
        <textarea id="userBio" name="user_bio" placeholder="Write a short bio about yourself (maximum 150 characters and 5 lines)..." maxlength="150"></textarea>
        <div class="counter-container">
          <div class="line-counter" id="lineCount"><span id="lineCounter">0</span>/5 lines</div>
          <div class="character-counter" id="charCount"><span id="charCounter">0</span>/150</div>
        </div>
        <div id="bioError" class="error" style="display: none;"></div>
        
        <input type="hidden" name="complete_registration" value="1">
        
        <div class="buttons">
          <button type="button" class="skip-btn" id="skipBtn">Skip</button>
          <button type="submit" id="completeBtn">Complete Registration</button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    const userBio = document.getElementById('userBio');
    const charCounter = document.getElementById('charCounter');
    const charCount = document.getElementById('charCount');
    const lineCounter = document.getElementById('lineCounter');
    const lineCount = document.getElementById('lineCount');
    const bioError = document.getElementById('bioError');
    const bioForm = document.getElementById('bioForm');
    const skipBtn = document.getElementById('skipBtn');
    
    // Character and line counter
    userBio.addEventListener('input', updateCounters);
    
    // Prevent new line after 5 lines
    userBio.addEventListener('keydown', function(e) {
      const lines = userBio.value.split('\n');
      if (e.key === 'Enter' && lines.length >= 5) {
        e.preventDefault();
        bioError.textContent = "Maximum 5 lines allowed";
        bioError.style.display = "block";
        setTimeout(() => {
          bioError.style.display = "none";
        }, 2000);
      }
    });
    
    function updateCounters() {
      // Update character count
      const length = userBio.value.length;
      charCounter.textContent = length;
      
      // Update line count
      const lines = userBio.value.split('\n').length;
      lineCounter.textContent = lines;
      
      // Change color when approaching the limits
      if (length >= 150) {
        charCount.classList.add("limit-reached");
      } else {
        charCount.classList.remove("limit-reached");
      }
      
      if (lines >= 5) {
        lineCount.classList.add("limit-reached");
      } else {
        lineCount.classList.remove("limit-reached");
      }
      
      // Validate bio
      validateBio();
    }
    
    function validateBio() {
      // Check character length
      if (userBio.value.length > 150) {
        userBio.value = userBio.value.substring(0, 150);
        bioError.textContent = "Bio cannot exceed 150 characters";
        bioError.style.display = "block";
        return false;
      }
      
      // Check line count
      const lines = userBio.value.split('\n');
      if (lines.length > 5) {
        // Keep only the first 5 lines
        userBio.value = lines.slice(0, 5).join('\n');
        bioError.textContent = "Bio cannot exceed 5 lines";
        bioError.style.display = "block";
        return false;
      }
      
      bioError.style.display = "none";
      return true;
    }
    
    // Skip bio
    skipBtn.addEventListener('click', function() {
      // Clear the bio text
      userBio.value = '';
      // Submit the form
      bioForm.submit();
    });
    
    // Form submission validation
    bioForm.addEventListener('submit', function(e) {
      if (!validateBio()) {
        e.preventDefault();
      }
    });
    
    // Initialize counters
    updateCounters();
  </script>
</body>
</html>