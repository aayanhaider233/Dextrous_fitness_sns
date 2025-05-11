<?php
require 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        session_start();
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["username"] = $user["username"];
        header("Location: account.php");
        exit();
    } else {
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
}

// Start the session to access session variables for error messages
session_start();

// Check for error parameter in URL and set corresponding session error message
if (isset($_GET['error']) && $_GET['error'] == 'invalid_credentials') {
    $_SESSION['error_message'] = "Invalid email or password. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dextrous - Login</title>
  <link href="images/favicon.png" rel="shortcut icon" type="image/x-icon" />
  <link rel="stylesheet" href="css/style.css">
  <style>
    * {
      box-sizing: border-box;
      cursor: default;
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
      max-width: 440px; 
    }

    .overlay {
      background-color: #2e2e2e;
      padding: 40px; 
      border-radius: 10px;
      width: 100%;
      text-align: left;
    }

    input[type="email"], input[type="password"] {
      display: block;
      margin: 13px 0;
      width: 100%;
      padding: 15px;
      border: none;
      border-radius: 5px;
      font-size: 1rem;
      background: #444;
      color: white;
      transition: border 0.3s;
      cursor: pointer;
    }

    input::placeholder {
      color: #ccc;
    }

    input.invalid {
      border: 2px solid red;
    }

    input.valid {
      border: 2px solid green;
    }

    button {
      background-color: darkorange;
      border: none;
      padding: 20px;
      width: 100%;
      border-radius: 5px;
      color: white;
      font-size: 1.1rem;
      margin-top: 18px;
      font-weight: bold;
      transition: background-color 0.2s;
      cursor: pointer;
    }

    button:hover {
      background-color: #cc7000;
    }

    .title {
      font-size: 2.8rem;
      color: darkorange;
      font-weight: bold;
      margin-bottom: 18px;
      text-align: center;
    }

    .link {
      display: block;
      margin-top: 18px;
      font-size: 0.95rem;
      color: darkorange;
      text-decoration: none;
      text-align: center;
      cursor: pointer;
    }

    .link:hover {
      text-decoration: underline;
    }

    .input-container {
      position: relative;
      margin: 13px 0;
    }

    input[type="password"],
    input[type="text"] {
      width: 100%; /* Ensure both types take up the same width */
      padding: 12px; /* Same padding for both */
      padding-right: 40px; /* Space for the eye icon */
      background: #444; /* Keep the same background */
      color: white; /* Consistent text color */
      border: none; /* No border */
      border-radius: 5px; /* Rounded corners */
      font-size: 1rem; /* Consistent font size */
      transition: border 0.3s;
      appearance: none; /* Prevent default browser styling */
      box-sizing: border-box; /* Include padding in width calculation */
      height: 38px;
    }

    input::placeholder {
      color: #ccc; /* Placeholder color */
    }

    .eye-icon {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #ccc;
      width: 20px; /* Maintain a consistent size for the icon */
      height: 20px;
    }
    
    /* Message styles (matching edit-account.php) */
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

    /* Add responsive adjustments */
    @media (max-height: 780px) {
      .container {
        max-width: 420px;
      }
      
      .title {
        font-size: 2.4rem;
        margin-bottom: 15px;
      }
      
      .overlay {
        padding: 35px;
      }
    }

    @media (max-height: 720px) {
      .container {
        max-width: 400px;
      }
      
      .title {
        font-size: 2.2rem;
      }
      input[type="email"], input[type="password"] {
        margin: 10px 0;
        padding: 10px;
      }
      
      .input-container {
        margin: 10px 0;
      }
      
      button {
        margin-top: 14px;
        padding: 10px;
      }
      
      .overlay {
        padding: 30px;
      }
    }

    /* For very small heights */
    @media (max-height: 680px) {
      .container {
        max-width: 380px;
      }
      
      .title {
        font-size: 2rem;
        margin-bottom: 10px;
      }
      
      input[type="email"], input[type="password"] {
        margin: 8px 0;
        padding: 8px;
      }
      
      .input-container {
        margin: 8px 0;
      }
      
      .overlay {
        padding: 25px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="title">Dextrous</div>
    
    <!-- Error Message - matching the style from edit-account.php -->
    <?php if (isset($_SESSION['error_message'])): ?>
    <div id="errorMessage" class="message error-message">
      <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
    <?php endif; ?>
    
    <div class="overlay">
      <form action="login.php" method="POST">
        <input type="email" name="email" required placeholder="Email">
        <div class="input-container">
          <input type="password" id="password" name="password" required placeholder="Password">
          <span class="eye-icon" id="togglePassword">
            <!-- Minimal SVG Eye Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M1 12s3-7 11-7 11 7 11 7-3 7-11 7-11-7-11-7z"></path>
            </svg>
          </span>
        </div>
        <button type="submit">Login</button>
      </form>

      <a class="link" href="register.php">Don't have an account? Register</a>
    </div>
  </div>

  <script>
    const togglePassword = document.getElementById("togglePassword");
    const passwordField = document.getElementById("password");
    const form = document.querySelector("form"); // Fixed to target the correct form
  
    // Toggle password visibility
    togglePassword.addEventListener("click", function () {
      const type = passwordField.type === "password" ? "text" : "password";
      passwordField.type = type;
      const svg = togglePassword.querySelector('svg');
      if (passwordField.type === "password") {
        svg.innerHTML = '<circle cx="12" cy="12" r="3"></circle><path d="M1 12s3 7 11 7 11-7 11-7-3-7-11-7-11 7-11 7z"></path>';
      } else {
        svg.innerHTML = '<circle cx="12" cy="12" r="3"></circle><path d="M1 12s3-7 11-7 11 7 11 7-3 7-11 7-11-7-11-7z"></path>';
      }
    });
  
    // Validation logic for the input fields (if needed, for other fields)
    const inputs = form.querySelectorAll("input"); // Target inputs in the login form only
    inputs.forEach(input => {
      input.addEventListener("input", () => {
        if (input.value.trim() !== "") {
          input.classList.remove("invalid");
          input.classList.add("valid");
        } else {
          input.classList.remove("valid");
          input.classList.add("invalid");
        }
      });
    });
    
    // Auto-hide error message after 5 seconds
    const errorMessage = document.getElementById("errorMessage");
    if (errorMessage) {
      setTimeout(() => {
        errorMessage.style.display = 'none';
      }, 5000);
    }
  </script>
</body>
</html>