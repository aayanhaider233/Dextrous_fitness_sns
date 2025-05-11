
<?php
session_start();
require 'db_config.php';  // PDO connection setup

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION["user_email"]; // User's email stored in session
$enroll_message = null;

// Check for enrollment message in session (from redirect)
if (isset($_SESSION['enroll_message'])) {
    $enroll_message = $_SESSION['enroll_message'];
    // Clear the message so it only shows once
    unset($_SESSION['enroll_message']);
}

// Handle enrollment if form is submitted
if (isset($_POST['enroll']) && isset($_POST['gym_name'])) {
    $gym_name = $_POST['gym_name'];
    
    try {
        // First check if user is already enrolled in ANY gym
        $check_any_gym_stmt = $pdo->prepare("SELECT * FROM usergym WHERE user_email = ?");
        $check_any_gym_stmt->execute([$user_email]);
        
        if ($check_any_gym_stmt->rowCount() > 0) {
            // User is already enrolled in a gym
            $enrolled_gym = $check_any_gym_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if it's the same gym they're trying to enroll in
            if ($enrolled_gym['gym_name'] === $gym_name) {
                $_SESSION['enroll_message'] = "You are already enrolled in this gym.";
            } else {
                $_SESSION['enroll_message'] = "You are already enrolled in " . $enrolled_gym['gym_name'] . ". You can only be enrolled in one gym at a time.";
            }
        } else {
            // Insert new enrollment
            $enroll_stmt = $pdo->prepare("INSERT INTO usergym (user_email, gym_name) VALUES (?, ?)");
            $enroll_stmt->execute([$user_email, $gym_name]);
            $_SESSION['enroll_message'] = "Successfully enrolled in $gym_name!";
        }
    } catch (PDOException $e) {
        $_SESSION['enroll_message'] = "Error: " . $e->getMessage();
    }
    
    // Redirect back to the same page to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all gyms
try {
    $stmt = $pdo->query("SELECT * FROM gym ORDER BY gym_name");
    $gyms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching gyms: " . $e->getMessage();
    exit();
}

// Prepare array to hold gyms with their locations
$gyms_with_locations = [];

// For each gym, fetch its locations
foreach ($gyms as $gym) {
    try {
        $loc_stmt = $pdo->prepare("SELECT location FROM gymlocations WHERE gym_name = ?");
        $loc_stmt->execute([$gym['gym_name']]);
        $locations = $loc_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Add locations to gym data
        $gym['locations'] = $locations;
        $gyms_with_locations[] = $gym;
    } catch (PDOException $e) {
        echo "Error fetching locations: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html data-wf-page="667187163156a5df09557ed5">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dextrous - Gyms</title>
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
    
    /* Main container */
    .main-container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    /* Gyms Grid Container */
    .gyms-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      padding: 0 20px;
      position: relative;
    }
    
    /* Gym Card */
    .gym-card {
      position: relative;
      height: 200px;
      background-color: #333;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
      cursor: pointer;
      z-index: 1;
    }
    
    .gym-card.expanded {
      position: fixed;
      width: 600px;
      height: 400px;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 1500;
      cursor: default;
    }
    
    .gym-preview {
      height: 100%;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.8));
      padding: 20px;
      position: relative;
    }
    
    /* Hide the preview when expanded */
    .expanded .gym-preview {
      display: none;
    }
    
    .gym-name {
      font-size: 24px;
      font-weight: bold;
      color: #fff;
      text-align: center;
      z-index: 2;
    }
    
    .gym-hover-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 80px;
      background-image: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
      display: flex;
      justify-content: center;
      align-items: flex-end;
      padding-bottom: 15px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .gym-card:hover .gym-hover-overlay {
      opacity: 1;
    }
    
    .show-more-btn {
      background-color: #ff7f50;
      color: #fff;
      border: none;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .show-more-btn:hover {
      background-color: #ff6b3c;
    }
    
    /* Gym Details */
    .gym-details {
      display: none;
      padding: 20px;
      height: 100%;
      background-color: #333;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .expanded .gym-details {
      display: flex;
    }
    
    /* Gym details header */
    .gym-details-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .gym-details-name {
      font-size: 24px;
      font-weight: bold;
      color: #ff7f50;
      margin: 0;
    }
    
    .gym-details-info {
      font-size: 14px;
      line-height: 1.5;
      margin-bottom: 10px;
    }
    
    .gym-details-label {
      font-weight: 600;
      color: #ddd;
    }
    
    .enroll-btn {
      background-color: #ff7f50;
      color: #fff;
      border: none;
      padding: 10px;
      border-radius: 5px;
      width: 100%;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .enroll-btn:hover {
      background-color: #ff6b3c;
    }
    
    .close-btn {
      font-size: 18px;
      color: #ddd;
      background: none;
      border: none;
      cursor: pointer;
    }
    
    .close-btn:hover {
      color: #fff;
    }
    
    /* Expanded Card Background Overlay */
    .expanded-card-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1400;
      display: none;
    }
    
    .expanded-card-overlay.active {
      display: block;
    }
    
    /* Enrollment Message */
    .enrollment-message {
      position: fixed;
      top: 100px;
      left: 50%;
      transform: translateX(-50%);
      background-color: rgba(0, 0, 0, 0.8);
      color: #fff;
      padding: 15px 25px;
      border-radius: 5px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      z-index: 1100;
      display: none;
    }
    
    .enrollment-message.success {
      border-left: 4px solid #4CAF50;
    }
    
    .enrollment-message.error {
      border-left: 4px solid #f44336;
    }
    
    /* Confirmation Modal */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 2000;
      visibility: hidden;
      opacity: 0;
      transition: opacity 0.3s, visibility 0.3s;
    }
    
    .modal-overlay.active {
      visibility: visible;
      opacity: 1;
    }
    
    .confirmation-modal {
      background-color: #333;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .modal-title {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 15px;
      color: #ff7f50;
    }
    
    .modal-message {
      margin-bottom: 20px;
      line-height: 1.5;
    }
    
    .modal-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    .modal-btn {
      padding: 8px 15px;
      border-radius: 5px;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    
    .confirm-btn {
      background-color: #ff7f50;
      color: #fff;
      border: none;
    }
    
    .confirm-btn:hover {
      background-color: #ff6b3c;
    }
    
    .cancel-btn {
      background-color: transparent;
      color: #ddd;
      border: 1px solid #ddd;
    }
    
    .cancel-btn:hover {
      background-color: #444;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .gyms-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      }
      
      .logo {
        margin-left: 20px !important; 
      }
      
      .main-container {
        padding-top: 100px;
      }
      
      .gym-card.expanded {
        width: 90%;
        height: 380px;
      }
    }
    
    @media (max-width: 480px) {
      .gyms-grid {
        grid-template-columns: 1fr;
      }
      
      .gym-card.expanded {
        width: 95%;
        height: 350px;
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
  a, button, .menu-item, .gym-card {
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

<div class="sliding-menu" id="slidingMenu">
  <div class="menu-items">
    <a href="index.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
    <a href="explore.php" class="menu-item"><i class="fas fa-compass"></i> Explore</a>
    <a href="gyms.php" class="menu-item current"><i class="fas fa-dumbbell"></i> Gyms</a>
    <a href="workout_library.php" class="menu-item"><i class="fas fa-running"></i> Workouts</a>
    <a href="account.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="edit-account.php" class="menu-item"><i class="fas fa-cog"></i> Edit Profile</a>
    <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="menu-overlay" id="menuOverlay"></div>

<div class="expanded-card-overlay" id="expandedCardOverlay"></div>

<div class="w-layout-blockcontainer main-container w-container">
  <h1 class="page-title">Find Your Perfect Gym</h1>
  
  <div class="gyms-grid">
    <?php foreach ($gyms_with_locations as $gym): ?>
      <div class="gym-card" data-gym-name="<?php echo htmlspecialchars($gym['gym_name']); ?>">
        <div class="gym-preview">
          <h3 class="gym-name"><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
          <div class="gym-hover-overlay">
            <button class="show-more-btn">Show details</button>
          </div>
        </div>
        
        <div class="gym-details">
          <div class="gym-details-header">
            <h3 class="gym-details-name"><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
            <button class="close-btn"><i class="fas fa-times"></i></button>
          </div>
          <div>
            <p class="gym-details-info">
              <span class="gym-details-label">Founded:</span> 
              <?php echo htmlspecialchars($gym['founding_date']); ?>
            </p>
            <p class="gym-details-info">
              <span class="gym-details-label">Locations:</span> 
              <?php echo htmlspecialchars(implode('; ', $gym['locations'])); ?>
            </p>
            <p class="gym-details-info">
              <span class="gym-details-label">Owner:</span> 
              <?php echo htmlspecialchars($gym['owner']); ?>
            </p>
          </div>
          <button class="enroll-btn" data-gym-name="<?php echo htmlspecialchars($gym['gym_name']); ?>">Enroll</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="enrollment-message" id="enrollmentMessage"></div>

<div class="modal-overlay" id="confirmationModal">
  <div class="confirmation-modal">
    <h3 class="modal-title">Confirm Enrollment</h3>
    <p class="modal-message">Are you sure you want to enroll in <span id="modalGymName"></span>?</p>
    <div class="modal-buttons">
      <button class="modal-btn cancel-btn" id="cancelEnroll">Cancel</button>
      <form method="post" id="enrollForm">
        <input type="hidden" name="gym_name" id="enrollGymName">
        <button type="submit" name="enroll" class="modal-btn confirm-btn">Confirm</button>
      </form>
    </div>
  </div>
</div>

<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/plugins.js" type="text/javascript"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Save original positions of all cards for animation
    function saveCardPositions() {
      const cards = document.querySelectorAll('.gym-card');
      cards.forEach(card => {
        const rect = card.getBoundingClientRect();
        card.dataset.originalPosition = `${rect.left},${rect.top},${rect.width},${rect.height}`;
      });
    }
    
    // Call on load and on window resize
    saveCardPositions();
    window.addEventListener('resize', saveCardPositions);
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
    
    // Expanded Card Overlay
    const expandedCardOverlay = document.getElementById('expandedCardOverlay');
    
    // Gym Card Functionality
    const gymCards = document.querySelectorAll('.gym-card');
    let currentExpandedCard = null;
    
    gymCards.forEach(card => {
      // Show more button functionality
      const showMoreBtn = card.querySelector('.show-more-btn');
      showMoreBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // If there's already an expanded card, close it first
        if (currentExpandedCard && currentExpandedCard !== card) {
          currentExpandedCard.classList.remove('expanded');
        }
        
        // Get the card's current position before expanding
        const rect = card.getBoundingClientRect();
        const startX = rect.left;
        const startY = rect.top;
        const startWidth = rect.width;
        const startHeight = rect.height;
        
        // Set initial position for smooth animation
        card.style.position = 'fixed';
        card.style.top = startY + 'px';
        card.style.left = startX + 'px';
        card.style.width = startWidth + 'px';
        card.style.height = startHeight + 'px';
        card.style.margin = '0';
        card.style.zIndex = '1500';
        
        // Force reflow to ensure the initial position is applied
        void card.offsetWidth;
        
        // Now add the expanded class and animate to center
        card.classList.add('expanded');
        card.style.transition = 'all 0.3s ease';
        card.style.top = '50%';
        card.style.left = '50%';
        card.style.width = '600px';
        card.style.height = '400px';
        card.style.transform = 'translate(-50%, -50%)';
        
        expandedCardOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        currentExpandedCard = card;
      });
      
      // Close button functionality
      const closeBtn = card.querySelector('.close-btn');
      closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // Get the original position of the card in the grid
        const originalCard = Array.from(gymCards).find(c => c === card);
        const originalRect = originalCard.getBoundingClientRect();
        
        // Animate back to original position
        if (card === currentExpandedCard) {
          // Get the original position
          const gridPos = card.dataset.originalPosition;
          if (gridPos) {
            const [left, top, width, height] = gridPos.split(',').map(Number);
            
            // Animate back
            card.style.top = top + 'px';
            card.style.left = left + 'px';
            card.style.width = width + 'px';
            card.style.height = height + 'px';
            card.style.transform = 'none';
            
            // Wait for animation to complete before removing fixed position
            setTimeout(() => {
              card.classList.remove('expanded');
              card.style.position = '';
              card.style.top = '';
              card.style.left = '';
              card.style.width = '';
              card.style.height = '';
              card.style.margin = '';
              card.style.transform = '';
              card.style.zIndex = '';
            }, 300);
          } else {
            // Fallback if no position data
            card.classList.remove('expanded');
            card.style.position = '';
            card.style.top = '';
            card.style.left = '';
            card.style.width = '';
            card.style.height = '';
            card.style.margin = '';
            card.style.transform = '';
            card.style.zIndex = '';
          }
        }
        
        expandedCardOverlay.classList.remove('active');
        document.body.style.overflow = '';
        currentExpandedCard = null;
      });
      
      // Enroll button functionality
      const enrollBtn = card.querySelector('.enroll-btn');
      enrollBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const gymName = this.getAttribute('data-gym-name');
        showConfirmationModal(gymName);
      });
    });
    
    // Close expanded card when clicking on overlay
    expandedCardOverlay.addEventListener('click', function() {
      if (currentExpandedCard) {
        // Find the close button and trigger a click on it
        const closeBtn = currentExpandedCard.querySelector('.close-btn');
        if (closeBtn) {
          closeBtn.click();
        } else {
          // Fallback
          currentExpandedCard.classList.remove('expanded');
          currentExpandedCard.style.position = '';
          currentExpandedCard.style.top = '';
          currentExpandedCard.style.left = '';
          currentExpandedCard.style.width = '';
          currentExpandedCard.style.height = '';
          currentExpandedCard.style.margin = '';
          currentExpandedCard.style.transform = '';
          currentExpandedCard.style.zIndex = '';
          expandedCardOverlay.classList.remove('active');
          document.body.style.overflow = '';
          currentExpandedCard = null;
        }
      }
    });
    
    // Confirmation Modal Functionality
    const confirmationModal = document.getElementById('confirmationModal');
    const modalGymName = document.getElementById('modalGymName');
    const enrollGymName = document.getElementById('enrollGymName');
    const cancelEnroll = document.getElementById('cancelEnroll');
    
    function showConfirmationModal(gymName) {
      modalGymName.textContent = gymName;
      enrollGymName.value = gymName;
      confirmationModal.classList.add('active');
    }
    
    cancelEnroll.addEventListener('click', function() {
      confirmationModal.classList.remove('active');
    });
    
    // Close modal when clicking outside
    confirmationModal.addEventListener('click', function(e) {
      if (e.target === confirmationModal) {
        confirmationModal.classList.remove('active');
      }
    });
    
    // Enrollment Message
    <?php if (isset($enroll_message)): ?>
      const enrollmentMessage = document.getElementById('enrollmentMessage');
      enrollmentMessage.textContent = "<?php echo $enroll_message; ?>";
      enrollmentMessage.classList.add('<?php echo (strpos($enroll_message, 'Error') !== false) ? "error" : "success"; ?>');
      enrollmentMessage.style.display = 'block';
      
      // Hide message after 5 seconds
      setTimeout(function() {
        enrollmentMessage.style.display = 'none';
      }, 5000);
    <?php endif; ?>
  });
</script>
</body>
</html>