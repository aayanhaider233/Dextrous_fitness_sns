
<?php
session_start(); // Ensure session is started at the very beginning

// Include database connection
include_once('db_config.php'); // This should define $pdo

// Initialize variables for filtering, sorting, and pagination
$muscle_filter = isset($_GET['muscle']) ? trim($_GET['muscle']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 6; // Number of workouts per page
$offset = ($page - 1) * $per_page;

// --- Query Building ---
$query_params = []; // For use with prepared statements if refactored

// Base query
$base_query = " FROM workouts";
$where_conditions = [];

if (!empty($muscle_filter)) {
    $where_conditions[] = "muscle_group = :muscle_filter";
    $query_params[':muscle_filter'] = $muscle_filter;
}
if (!empty($search)) {
    $where_conditions[] = "(name LIKE :search_term OR muscle_group LIKE :search_term)";
    $query_params[':search_term'] = '%' . $search . '%';
}

$sql_where = "";
if (!empty($where_conditions)) {
    $sql_where = " WHERE " . implode(' AND ', $where_conditions);
}

// Sorting logic
$order_by_sql = " ORDER BY name ASC"; // Default sort
switch ($sort_by) {
    case 'best_pr':
        $order_by_sql = " ORDER BY CAST(REPLACE(best_pr, ' lbs', '') AS DECIMAL(10,2)) DESC, name ASC"; // Example if PR includes units like 'lbs' or 'reps'
        break;
    case 'community_avg':
        $order_by_sql = " ORDER BY CAST(REPLACE(community_avg, ' lbs', '') AS DECIMAL(10,2)) DESC, name ASC"; // Similar handling for community_avg
        break;
    case 'name':
    default:
        $order_by_sql = " ORDER BY name ASC";
        break;
}

// Final query for fetching workouts
$query = "SELECT *" . $base_query . $sql_where . $order_by_sql . " LIMIT :offset, :per_page";
$stmt = $pdo->prepare($query);
foreach ($query_params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_query = "SELECT COUNT(*)" . $base_query . $sql_where;
$count_stmt = $pdo->prepare($count_query);
foreach ($query_params as $key => $value) { 
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_workouts = $count_stmt->fetchColumn();
$total_pages = ceil($total_workouts / $per_page);


if (isset($_POST['add_favorite'])) {
    if (isset($_SESSION['user_email'])) {
        $user_email = $_SESSION['user_email'];
        $workout_name = $_POST['workout_name'];
        
        $check_query = "SELECT * FROM favworkout WHERE user_email = :user_email AND workout_name = :workout_name";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([':user_email' => $user_email, ':workout_name' => $workout_name]);
        
        if ($check_stmt->rowCount() == 0) {
            $insert_query = "INSERT INTO favworkout (user_email, workout_name) VALUES (:user_email, :workout_name)";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([':user_email' => $user_email, ':workout_name' => $workout_name]);
            $message = htmlspecialchars($workout_name) . " added to favorites!";
        } else {
            $delete_query = "DELETE FROM favworkout WHERE user_email = :user_email AND workout_name = :workout_name";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->execute([':user_email' => $user_email, ':workout_name' => $workout_name]);
            $message = htmlspecialchars($workout_name) . " removed from favorites!";
        }
        // Redirect to avoid form resubmission, keeping GET parameters
        header("Location: workout_library.php?" . http_build_query($_GET) . "&fav_message=" . urlencode($message));
        exit();
    } else {
        $message = "Please log in to manage favorites.";
    }
}
if(isset($_GET['fav_message'])) { // Display message after redirect
    $message = $_GET['fav_message'];
}


$favorites = [];
if (isset($_SESSION['user_email'])) {
    $user_email = $_SESSION['user_email'];
    $favorites_query = "SELECT workout_name FROM favworkout WHERE user_email = :user_email";
    $favorites_stmt = $pdo->prepare($favorites_query);
    $favorites_stmt->execute([':user_email' => $user_email]);
    $favorites = $favorites_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

$muscle_groups_query = "SELECT DISTINCT muscle_group FROM workouts WHERE muscle_group IS NOT NULL AND muscle_group != '' ORDER BY muscle_group";
$muscle_groups_stmt = $pdo->query($muscle_groups_query); // Using query() as it's a simple select
$muscle_groups_data = $muscle_groups_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dextrous - Workouts</title>
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
        .page-title { font-size: 28px; margin: 0 0 30px 0; font-weight: bold; }
        
        .search-filters { margin-bottom: 20px; }
        
        .filter-options { display: flex; gap: 15px; margin-bottom: 20px; /* Consistent margin */ flex-wrap: wrap; /* Allow wrapping on smaller screens */ }
        .filter-options select, .filter-options .filter-button { padding: 10px; background-color: var(--card-bg); color: var(--text-color); border: 1px solid var(--card-border); border-radius: 4px; }
        .filter-options .filter-button { background-color: var(--accent-color); color: white; cursor: pointer; }
        
        .alert { padding: 15px; background-color: var(--secondary-bg); color: var(--text-color); border: 1px solid var(--card-border); border-left: 5px solid var(--accent-color); margin-bottom: 20px; border-radius: 4px; }

        .workout-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px; }
        .workout-card { background-color: var(--card-bg); border-radius: 8px; overflow: hidden; transition: transform 0.3s ease; border: 1px solid var(--card-border); display: flex; flex-direction: column; }
        .workout-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2); }
        .workout-image { height: 160px; background-color: #333; display: flex; align-items: center; justify-content: center; color: var(--text-muted); /* background-size: cover; background-position: center; */ } /* Uncomment for actual images */
        .workout-image span { font-size: 1.2em; }
        .workout-content { padding: 20px; flex-grow: 1; }
        .workout-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .workout-muscle { color: var(--accent-color); font-size: 14px; margin-bottom: 10px; }
        .workout-stats { display: flex; justify-content: space-between; margin-top: 15px; color: var(--text-muted); font-size: 14px; }
        .workout-action { text-align: center; padding: 15px; background-color: rgba(255, 127, 80, 0.1); cursor: pointer; transition: background-color 0.3s, color 0.3s; border: none; color: var(--accent-color); width: 100%; margin-top: auto; }
        .workout-action:hover { background-color: var(--accent-color); color: white; }
        .workout-action.favorited { background-color: var(--accent-color); color: white; }
        .workout-action.favorited:hover { background-color: #e0683a; }

        .no-results { text-align: center; padding: 40px; color: var(--text-muted); font-size: 18px; grid-column: 1 / -1; }

        .add-workout { position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; border-radius: 50%; background-color: var(--accent-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3); border: none; z-index: 999; text-decoration: none; }
        
        .pagination { display: flex; justify-content: center; margin-top: 40px; padding-bottom: 20px; }
        .pagination a { padding: 10px 15px; margin: 0 5px; background-color: var(--card-bg); color: var(--text-color); text-decoration: none; border-radius: 4px; transition: background-color 0.2s, color 0.2s; }
        .pagination a:hover { background-color: var(--secondary-bg); }
        .pagination a.active { background-color: var(--accent-color); color: white; }
        
        @media (max-width: 768px) {
            .workout-grid { grid-template-columns: 1fr; }
            .filter-options { flex-direction: column; }
            .filter-options select, .filter-options .filter-button { width: 100%; }
            .header-inner .logo { font-size: 24px !important; }
            .hamburger-menu-button { font-size: 20px; }
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
            <a href="workout_library.php" class="menu-item current"><i class="fas fa-running"></i> Workouts</a>
            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="account.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
                <a href="edit-account.php" class="menu-item"><i class="fas fa-cog"></i> Edit Profile</a>
                <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="menu-item"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="menu-overlay" id="menuOverlay"></div>
    
    <div class="container">
        <div class="page-title">Workout Library</div>
        
        <form method="GET" action="workout_library.php" class="search-filters">
            
            <div class="filter-options">
                <select name="muscle" id="muscle-group-filter">
                    <option value="">All Muscle Groups</option>
                    <?php foreach ($muscle_groups_data as $muscle): ?>
                    <option value="<?php echo htmlspecialchars($muscle); ?>" <?php echo ($muscle_filter == $muscle) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($muscle)); // Capitalize for display ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="sort" id="sort-by">
                    <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>Sort by Name</option>
                    <option value="best_pr" <?php echo ($sort_by == 'best_pr') ? 'selected' : ''; ?>>Sort by Best PR</option>
                    <option value="community_avg" <?php echo ($sort_by == 'community_avg') ? 'selected' : ''; ?>>Sort by Community Average</option>
                </select>
                
                <button type="submit" class="filter-button">Apply Filters</button>
            </div>
        </form>
        
        <?php if (!empty($message)): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="workout-grid">
            <?php if (count($workouts) > 0): ?>
                <?php foreach ($workouts as $workout): ?>
                    <?php $is_favorite = in_array($workout['name'], $favorites); ?>
                    <div class="workout-card">
                        <div class="workout-image" <?php echo !empty($workout['image_url']) ? 'style="background-image: url(\'' . htmlspecialchars($workout['image_url']) . '\');"' : ''; ?>>
                            <?php if (empty($workout['image_url'])): ?>
                                <span><?php echo htmlspecialchars($workout['name']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="workout-content">
                            <div class="workout-title"><?php echo htmlspecialchars($workout['name']); ?></div>
                            <div class="workout-muscle"><?php echo htmlspecialchars(ucfirst($workout['muscle_group'])); ?></div>
                            <div>Recommended: <?php echo htmlspecialchars($workout['rec_set_reps'] ?? 'Not specified'); ?></div>
                            <div class="workout-stats">
                                <span>Best PR: <?php echo htmlspecialchars(!empty($workout['best_pr']) ? $workout['best_pr'] : 'N/A'); ?></span>
                                <span>Avg: <?php echo htmlspecialchars(!empty($workout['community_avg']) ? $workout['community_avg'] : 'N/A'); ?></span>
                            </div>
                        </div>
                        <?php if (isset($_SESSION['user_email'])): ?>
                        <form method="POST" action="workout_library.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page])); ?>">
                            <input type="hidden" name="workout_name" value="<?php echo htmlspecialchars($workout['name']); ?>">
                            <button type="submit" name="add_favorite" class="workout-action <?php echo $is_favorite ? 'favorited' : ''; ?>">
                                <?php echo $is_favorite ? 'Favorited' : 'Add to Favorites'; ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">No workouts found matching your criteria. Try adjusting your filters or search term.</div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            // Build base URL for pagination links, preserving existing filters
            $pagination_params = $_GET;
            unset($pagination_params['fav_message']); // Don't carry over fav_message in pagination
            
            if ($page > 1):
                $prev_page_params = array_merge($pagination_params, ['page' => $page - 1]); ?>
                <a href="?<?php echo http_build_query($prev_page_params); ?>">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++):
                $current_page_params = array_merge($pagination_params, ['page' => $i]); ?>
                <a href="?<?php echo http_build_query($current_page_params); ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages):
                $next_page_params = array_merge($pagination_params, ['page' => $page + 1]); ?>
                <a href="?<?php echo http_build_query($next_page_params); ?>">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['user_email'])): ?>
        <?php 
        $is_trainer = false; // Default to false
        if (isset($pdo)) { // Ensure $pdo is available
            try {
                $trainer_check_stmt = $pdo->prepare("SELECT 1 FROM trainers WHERE email = :user_email");
                $trainer_check_stmt->execute([':user_email' => $_SESSION['user_email']]);
                if ($trainer_check_stmt->fetchColumn()) {
                    $is_trainer = true;
                }
            } catch (PDOException $e) {
                error_log("Error checking trainer status: " . $e->getMessage()); // Log error
            }
        }
        if ($is_trainer): 
        ?>
        <a href="add_workout.php" class="add-workout" title="Add New Workout">+</a>
        <?php endif; ?>
    <?php endif; ?>
    
    <script>
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

            const muscleFilter = document.getElementById('muscle-group-filter');
            const sortByFilter = document.getElementById('sort-by');
            if(muscleFilter) muscleFilter.addEventListener('change', function() { this.form.submit(); });
            if(sortByFilter) sortByFilter.addEventListener('change', function() { this.form.submit(); });

        });
    </script>
</body>
</html>
