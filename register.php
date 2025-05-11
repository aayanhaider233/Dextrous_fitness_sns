<?php
require 'db_config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Location: ppreg.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"> 
  <meta charset="UTF-8" />
  <title>Dextrous - Register</title>
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
      padding: 40px; /* Increased side padding */
      border-radius: 10px;
      width: 100%;
      text-align: left;
    }

    input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
      display: block;
      margin: 13px 0 !important; /* Spacing between inputs */
      width: 100%;
      padding: 12px; /* Input padding */
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

    input.valid {
      border: 2px solid green;
    }

    input.invalid {
      border: 2px solid red;
    }

    button {
      background-color: darkorange;
      border: none;
      padding: 12px; /* Button padding */
      width: 100%;
      border-radius: 5px;
      color: white;
      font-size: 1.1rem;
      margin-top: 18px; /* Space before button */
      font-weight: bold;
      transition: background-color 0.2s;
      cursor: pointer;
    }

    button:hover {
      background-color: #cc7000;
    }

    .title {
      font-size: 2.8rem;
      color: #ff7f50;
      font-weight: bold;
      margin-bottom: 30px;
      margin-top: 20px;
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
      
      input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
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
      
      input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
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
    <div class="overlay">
      <form id="registerForm" action="ppreg.php" method="POST">
        <input type="text" id="firstName" name="firstName" required placeholder="First Name">
        <input type="text" id="lastName" name="lastName" required placeholder="Last Name">
        <input type="text" id="username" name="username" required placeholder="Username">
        <input type="email" id="email" name="email" required placeholder="Email">
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
        <div id="passwordFormatError" class="error" style="display: none;">Password must be 8-20 characters and contain only letters, numbers, or '@'.</div>
        <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Confirm Password">
        <input type="number" id="age" name="age" required placeholder="Age" min="1" step="1">
        <div id="ageError" class="error" style="display: none;">Age must be a positive integer.</div>
        <input type="number" id="weight" name="weight" required placeholder="Weight (kg)" min="1" step="1">
        <div id="weightError" class="error" style="display: none;">Weight must be a positive integer.</div>
        <input type="number" id="height" name="height" required placeholder="Height (cm)" min="1" step="1">
        <div id="heightError" class="error" style="display: none;">Height must be a positive integer.</div>
        <input type="text" id="location" name="location" required placeholder="Location">
        <select id="gender" name="gender" required>
          <option value="" disabled selected>Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
        <select id="position" name="position" required>
          <option value="" disabled selected>Position</option>
          <option value="Trainer">Trainer</option>
          <option value="Trainee">Trainee</option>
        </select>
        <!-- Add hidden field to indicate this is from the registration form -->
        <input type="hidden" name="register_form" value="1">
        <!-- Changed button text from "Register" to "Next" -->
        <button type="submit">Next</button>
        <div id="passwordError" class="error" style="display: none;">Passwords do not match.</div>
        <div id="emailError" class="error" style="display: none;">Invalid email format.</div>
      </form>
      <a class="link" href="login.php">Already have an account? Log In</a>
    </div>
  </div>
  <script>
    const togglePassword = document.getElementById("togglePassword");
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirmPassword");
    const passwordError = document.getElementById("passwordError");
    const passwordFormatError = document.getElementById("passwordFormatError");
    const ageError = document.getElementById("ageError");
    const weightError = document.getElementById("weightError");
    const heightError = document.getElementById("heightError");
    const emailError = document.getElementById("emailError");
    const form = document.getElementById("registerForm");
    const inputs = form.querySelectorAll("input, select");
    
    const ageInput = document.getElementById("age");
    const weightInput = document.getElementById("weight");
    const heightInput = document.getElementById("height");
  
    function validateInput(input) {
      if (input.type === "email") {
        validateEmail(input);
      } else if (input.id === "password") {
        validatePassword(input);
      } else if (input.id === "age") {
        validatePositiveInteger(input, ageError, "Age");
      } else if (input.id === "weight") {
        validatePositiveInteger(input, weightError, "Weight");
      } else if (input.id === "height") {
        validatePositiveInteger(input, heightError, "Height");
      } else {
        if (input.value.trim() !== "") {
          input.classList.remove("invalid");
          input.classList.add("valid");
        } else {
          input.classList.remove("valid");
          input.classList.add("invalid");
        }
      }
    }
    
    function validatePositiveInteger(input, errorElement, fieldName) {
      const value = input.value.trim();
      const intValue = parseInt(value);
      
      if (value === "" || isNaN(intValue) || intValue < 1 || !Number.isInteger(intValue) || value.indexOf(".") !== -1) {
        input.classList.remove("valid");
        input.classList.add("invalid");
        errorElement.style.display = "block";
        errorElement.textContent = `${fieldName} must be a positive integer.`;
        return false;
      } else {
        input.classList.remove("invalid");
        input.classList.add("valid");
        errorElement.style.display = "none";
        return true;
      }
    }
    
    function validatePassword(input) {
      const password = input.value.trim();
      const passwordRegex = /^[a-zA-Z0-9@]{8,20}$/;
      
      if (passwordRegex.test(password)) {
        input.classList.remove("invalid");
        input.classList.add("valid");
        passwordFormatError.style.display = "none";
        return true;
      } else {
        input.classList.remove("valid");
        input.classList.add("invalid");
        passwordFormatError.style.display = "block";
        return false;
      }
    }
  
    function validatePasswordMatch() {
      if (passwordField.value !== confirmPasswordField.value) {
        confirmPasswordField.classList.add("invalid");
        passwordError.style.display = "block";
        return false;
      } else {
        confirmPasswordField.classList.remove("invalid");
        passwordError.style.display = "none";
        return true;
      }
    }
  
    function validateEmail(input) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (emailRegex.test(input.value.trim())) {
        input.classList.remove("invalid");
        input.classList.add("valid");
        emailError.style.display = "none";
        return true;
      } else {
        input.classList.remove("valid");
        input.classList.add("invalid");
        emailError.style.display = "block";
        return false;
      }
    }
    
    inputs.forEach(input => {
      input.addEventListener("input", () => validateInput(input));
    });
    
    const numericInputs = [ageInput, weightInput, heightInput];
    numericInputs.forEach(input => {
      input.addEventListener("input", function() {
        this.value = this.value.replace(/\./g, '');
      });
    });
  
    confirmPasswordField.addEventListener("input", validatePasswordMatch);
  
    togglePassword.addEventListener("click", function () {
      const type = passwordField.type === "password" ? "text" : "password";
      passwordField.type = type;
      confirmPasswordField.type = type; 
    });
  
    form.addEventListener("submit", function (e) {
      let valid = true;
      
      inputs.forEach(input => {
        if (input.id === "password") {
          if (!validatePassword(input)) valid = false;
        } else if (input.id === "age") {
          if (!validatePositiveInteger(input, ageError, "Age")) valid = false;
        } else if (input.id === "weight") {
          if (!validatePositiveInteger(input, weightError, "Weight")) valid = false;
        } else if (input.id === "height") {
          if (!validatePositiveInteger(input, heightError, "Height")) valid = false;
        } else if (input.type === "email") {
          if (!validateEmail(input)) valid = false;
        } else if (input.value.trim() === "") {
          input.classList.add("invalid");
          valid = false;
        }
      });
  
      if (!validatePasswordMatch()) {
        valid = false;
      }
  
      if (!valid) {
        e.preventDefault();
      }
    });
  </script>
  
</body>
</html>