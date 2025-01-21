<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'foodconnect';
$dbuser = 'root';
$dbpassword = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize message variable for feedback
$message = '';
$messageType = '';

// Check user session variables
$isRestaurant = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'restaurant';
$isNGO = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'ngo';
$userId = $_SESSION['user_id'] ?? null;

// Verify user is logged in
if (!$userId) {
    $message = "You must be logged in to perform this action.";
    $messageType = "error";
}

// Handle form submission for adding a food listing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' && $isRestaurant) {
        // Sanitize and validate input
        $name = trim($_POST['name'] ?? '');
        $time_available = trim($_POST['time_available'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $food_amount = trim($_POST['food_amount'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $food_type = trim($_POST['food_type'] ?? '');

        if (!empty($name) && !empty($time_available) && !empty($contact_number) &&
            !empty($food_amount) && !empty($location) && !empty($food_type)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO food_listings (name, time_available, contact_number, 
                                     food_amount, location, food_type, user_id) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([$name, $time_available, $contact_number,
                                        $food_amount, $location, $food_type, $userId]);
                if ($result) {
                    $message = "Food listing added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to add food listing.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Error adding listing: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "All fields are required to add a food listing.";
            $messageType = "error";
        }
    }

    // Handle deletion of food listing
    if ($_POST['action'] == 'delete' && $isRestaurant) {
        $listing_id = filter_var($_POST['listing_id'] ?? '', FILTER_VALIDATE_INT);

        if ($listing_id !== false) {
            try {
                // First check if the listing belongs to the logged-in user
                $stmt = $pdo->prepare("DELETE FROM food_listings WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$listing_id, $userId]);

                if ($stmt->rowCount() > 0) {
                    $message = "Food listing deleted successfully!";
                    $messageType = "success";
                } else {
                    $message = "You can only delete your own listings.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Error deleting listing: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "Invalid listing ID.";
            $messageType = "error";
        }
    }
}

// Fetch food listings
try {
    $query = $pdo->query("SELECT f.*, u.name as restaurant_name 
                         FROM food_listings f 
                         JOIN users u ON f.user_id = u.id 
                         ORDER BY f.time_available DESC");
    $food_listings = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching listings: " . $e->getMessage();
    $messageType = "error";
    $food_listings = [];
}

// Fetch target users for rating
if ($isRestaurant || $isNGO) {
    $targetUsersQuery = $pdo->prepare("SELECT id, name FROM users WHERE user_type = ?");
    $targetUsersQuery->execute([$isRestaurant ? 'ngo' : 'restaurant']);
    $targetUsers = $targetUsersQuery->fetchAll(PDO::FETCH_ASSOC);
} else {
    $targetUsers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Surplus Food - FoodConnect</title>
    <link rel="stylesheet" href="templates/assets/styles.css">
</head>
<body>
    <header>
        <h1>FoodConnect</h1>
        <nav>
            <ul>
                <li><a href="main.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="food-listings.php" class="active">Food Listings</a></li>
                <li><a href="create_user.php">New User</a></li>
                <li><a href="login.php">Existing User</a></li>
            </ul>
        </nav>
        <div class="user-info">
            <?php if (isset($_SESSION['name'])): ?>
                <div class="user-profile">
                    <span class="username">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
                    <a href="logout.php" class="logout-link">Logout</a>
                </div>
            <?php else: ?>
                <div class="auth-links">
                    <a href="login.php" class="login-link">Login</a>
                    <a href="create_user.php" class="signup-link">Sign-Up</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <section>
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($isRestaurant): ?>
            <div class="add-listing-section">
                <h2>Add New Food Listing</h2>
                <form action="food-listings.php" method="POST" class="add-listing-form">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Food Name:</label>
                        <input type="text" id="name" name="name" placeholder="e.g., Chicken Rice" required>
                    </div>

                    <div class="form-group">
                        <label for="time_available">Time Available:</label>
                        <input type="datetime-local" id="time_available" name="time_available" required>
                    </div>

                    <div class="form-group">
                        <label for="food_type">Food Usage Type:</label>
                        <input type="text" id="food_type" name="food_type" placeholder="e.g., Eating/Decompose" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number:</label>
                        <input type="tel" id="contact_number" name="contact_number" placeholder="e.g., 012-3456789" required>
                    </div>

                    <div class="form-group">
                        <label for="food_amount">Food Amount (servings):</label>
                        <input type="number" id="food_amount" name="food_amount" min="1" placeholder="Number of servings" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" placeholder="Enter pickup location" required>
                    </div>

                    <button type="submit" class="submit-btn">Add Food Listing</button>
                </form>
            </div>
        <?php endif; ?>

        <h2>Available Surplus Food</h2>
        <div class="food_cards">
            <?php if (empty($food_listings)): ?>
                <p class="no-listings">No food listings available at the moment.</p>
            <?php else: ?>
                <?php foreach ($food_listings as $listing): ?>
                    <div class="food_card">
                        <h3><?= htmlspecialchars($listing['name']) ?></h3>
                        <p class="restaurant-name">Posted by: <?= htmlspecialchars($listing['restaurant_name']) ?></p>
                        <p><strong>Time Available:</strong> <?= htmlspecialchars(date('Y-m-d H:i', strtotime($listing['time_available']))) ?></p>
                        <p><strong>Contact Number:</strong> <?= htmlspecialchars($listing['contact_number']) ?></p>
                        <p><strong>Food Amount:</strong> <?= htmlspecialchars($listing['food_amount']) ?> servings</p>
                        <p><strong>Food Usage Type:</strong> <?= htmlspecialchars($listing['food_type']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($listing['location']) ?></p>
                        
                        <!-- Display Reviews -->
                        <h4>Reviews:</h4>
                        <?php
                        $reviewQuery = $pdo->prepare("SELECT r.rating_score, r.comment, u.name AS reviewer_name 
                                                      FROM user_ratings r
                                                      JOIN users u ON r.rater_id = u.id
                                                      WHERE r.rated_user_id = ?");
                        $reviewQuery->execute([$listing['user_id']]);
                        $reviews = $reviewQuery->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($reviews)): ?>
                            <ul>
                                <?php foreach ($reviews as $review): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong> rated:
                                        <?= htmlspecialchars($review['rating_score']) ?>/5
                                        <p><?= htmlspecialchars($review['comment']) ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No reviews available.</p>
                        <?php endif; ?>

                        <!-- Add Review Form -->
                        <h4>Add a Review:</h4>
                        <form action="submit_rating.php" method="POST">
                            <input type="hidden" name="rated_user_id" value="<?= htmlspecialchars($listing['user_id']) ?>">
                            <label for="rating_score">Rating:</label>
                            <select name="rating_score" id="rating_score" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                            <label for="comment">Comment:</label>
                            <textarea name="comment" id="comment" placeholder="Leave a comment" required></textarea>
                            <button type="submit">Submit Review</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </section>

    <footer>
        <p>&copy; 2024 FoodConnect</p>
    </footer>
</body>
</html>
