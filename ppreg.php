<?php
session_start();

// Process form data from registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_form'])) {
    // Store all form data in session to use later when finalizing registration
    $_SESSION['registration_data'] = [
        'fname' => ucfirst(strtolower($_POST["firstName"])),
        'lname' => ucfirst(strtolower($_POST["lastName"])),
        'username' => $_POST["username"],
        'email' => $_POST["email"],
        'password' => $_POST["password"], 
        'age' => $_POST["age"],
        'weight' => $_POST["weight"],
        'height' => $_POST["height"],
        'location' => ucwords(strtolower($_POST["location"])),
        'position' => $_POST["position"],
        'gender' => $_POST["gender"]
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finalize_registration'])) {
    if (isset($_POST['croppedImageData']) && !empty($_POST['croppedImageData'])) {
        $_SESSION['cropped_image_data'] = $_POST['croppedImageData'];
    } else {
        $_SESSION['cropped_image_data'] = '';
    }
    header("Location: bio.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
  <meta charset="UTF-8" />
  <title>Dextrous - Upload profile picture</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="images/favicon.png" rel="shortcut icon" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
  <style>
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
      justify-items: center;
      width: 100%;
      max-width: 500px;
    }

    .overlay {
      justify-content: center;
      text-align: center;
      background-color: #2e2e2e;
      border-radius: 10px;
      width: 100%;
    }

    .title {
      font-size: 2.5rem;
      color: #ff7f50;
      font-weight: bold;
      margin-bottom: 20px;
      text-align: center;
    }

    .subtitle {
      font-size: 1.2rem;
      color: white;
      margin-bottom: 25px;
    }

    .file-upload {
      margin: 20px 0;
      text-align: center;
    }

    .file-upload-btn {
      background-color: #444;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      display: inline-block;
      margin-bottom: 15px;
    }

    .file-upload-btn:hover {
      background-color: #555;
    }

    .image-preview {
      max-width: 100%;
      margin: 20px 0;
      position: relative;
      height: 300px;
      border: 2px solid #444;
      display: none;
    }

    .image-preview img {
      max-width: 100%;
      max-height: 100%;
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

    .cropper-container {
      width: 100%;
      height: 300px;
      overflow: hidden;
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
      
      .image-preview {
        height: 250px;
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
      <h2 class="subtitle">Upload Profile Picture</h2>
      
      <form id="profilePicForm" action="ppreg.php" method="POST">
        <div class="file-upload">
          <label for="profilePicInput" class="file-upload-btn">Choose Image</label>
          <input type="file" id="profilePicInput" accept="image/*" style="display: none;">
        </div>
        
        <div class="image-preview" id="imagePreview">
          <img id="previewImage" src="" alt="Preview">
        </div>
        
        <div id="cropperContainer" class="cropper-container" style="display: none;">
          <img id="cropperImage" src="" alt="Crop Image">
        </div>
        
        <!-- Hidden field to store the cropped image data -->
        <input type="hidden" name="croppedImageData" id="croppedImageData">
        <input type="hidden" name="finalize_registration" value="1">
        
        <div class="buttons">
          <button type="button" class="skip-btn" id="skipBtn">Skip</button>
          <button type="submit" id="submitBtn">Next</button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    let cropper;
    const profilePicInput = document.getElementById('profilePicInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const cropperContainer = document.getElementById('cropperContainer');
    const cropperImage = document.getElementById('cropperImage');
    const croppedImageData = document.getElementById('croppedImageData');
    const skipBtn = document.getElementById('skipBtn');
    const submitBtn = document.getElementById('submitBtn');
    const profilePicForm = document.getElementById('profilePicForm');
    
    // Initialize image upload
    profilePicInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      
      if (file) {
        // Create a FileReader to read the image
        const reader = new FileReader();
        
        reader.onload = function(event) {
          // Display the cropper
          cropperContainer.style.display = 'block';
          imagePreview.style.display = 'none';
          
          // Set the image source
          cropperImage.src = event.target.result;
          
          // Initialize Cropper.js after the image is loaded
          cropperImage.onload = function() {
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
                // Update the hidden field with the cropped data when ready
                updateCroppedData();
              },
              crop: function() {
                // Update the hidden field when cropping changes
                updateCroppedData();
              }
            });
          };
        };
        
        reader.readAsDataURL(file);
      }
    });
    
    // Update the hidden field with the cropped image data
    function updateCroppedData() {
      if (cropper) {
        // Get the cropped canvas
        const canvas = cropper.getCroppedCanvas({
          width: 300,
          height: 300,
          fillColor: '#fff'
        });
        
        if (canvas) {
          // Convert the canvas to a data URL
          const dataURL = canvas.toDataURL('image/jpeg', 0.8);
          croppedImageData.value = dataURL;
          
          // For debugging
          console.log("Cropped image data updated");
        }
      }
    }
    
    // Handle form submission with default profile picture (skip)
    skipBtn.addEventListener('click', function() {
      // Clear any image data
      croppedImageData.value = '';
      profilePicForm.submit();
    });

    profilePicForm.addEventListener('submit', function(e) {
      console.log("Form submitted with image data: " + (croppedImageData.value ? "Yes" : "No"));
    });
  </script>
</body>
</html>