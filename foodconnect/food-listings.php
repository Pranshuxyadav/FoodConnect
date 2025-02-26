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

// Fetch Listings
$sql = "SELECT * FROM food_listings";
$result = $pdo->query($sql);


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
        $imagePath = '';

        // Handle image upload
        if (!empty($_FILES['food_image']['name'])) {
            $targetDir = "uploads/";
            
            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = basename($_FILES['food_image']['name']);
            $targetFilePath = $targetDir . time() . "_" . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Allow only certain file formats
            $allowTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileType, $allowTypes)) {
                if (move_uploaded_file($_FILES['food_image']['tmp_name'], $targetFilePath)) {
                    $imagePath = $targetFilePath;
                } else {
                    $message = "Image upload failed.";
                    $messageType = "error";
                }
            } else {
                $message = "Invalid image format. Only JPG, JPEG, PNG, and GIF allowed.";
                $messageType = "error";
            }
        }

        if (!empty($name) && !empty($time_available) && !empty($contact_number) &&
            !empty($food_amount) && !empty($location) && !empty($food_type)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO food_listings (name, time_available, contact_number, 
                                     food_amount, location, food_type, user_id, image) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([$name, $time_available, $contact_number,
                                        $food_amount, $location, $food_type, $userId, $imagePath]);
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

    if ($_POST['action'] == 'update_veg' && $isRestaurant) {
        $listing_id = filter_var($_POST['listing_id'] ?? '', FILTER_VALIDATE_INT);
        $veg_option = $_POST['veg_option'] ?? '';

        if ($listing_id && in_array($veg_option, ['veg', 'non-veg'])) {
            try {
                // Ensure only the owner can update their listing
                $stmt = $pdo->prepare("UPDATE food_listings SET veg_option = ? WHERE id = ? AND user_id = ?");
                $result = $stmt->execute([$veg_option, $listing_id, $userId]);

                if ($stmt->rowCount() > 0) {
                    $message = "Veg/Non-Veg classification updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "You can only update your own listings.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Error updating classification: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "Invalid input for classification update.";
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

    // HANDLE food status
    if ($_POST['action'] == 'update_status') {
        $listing_id = filter_var($_POST['listing_id'], FILTER_VALIDATE_INT);
        $new_status = $_POST['status_option'] ?? '';

        // Ensure the user is a restaurant and owns the listing
        if ($isRestaurant && $listing_id && in_array($new_status, ['available', 'delivered', 'expired', 'assigned'])) {
            try {
                // Check if the listing belongs to the logged-in restaurant
                $stmt = $pdo->prepare("SELECT id FROM food_listings WHERE id = ? AND user_id = ?");
                $stmt->execute([$listing_id, $userId]);
                $listing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($listing) {
                    // Update the status
                    $updateStmt = $pdo->prepare("UPDATE food_listings SET status = ? WHERE id = ?");
                    $updateStmt->execute([$new_status, $listing_id]);

                    if ($updateStmt->rowCount() > 0) {
                        $message = "Food status updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Failed to update the status. Try again.";
                        $messageType = "error";
                    }
                } else {
                    $message = "You can only update the status of your own listings.";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "Error updating status: " . $e->getMessage();
                $messageType = "error";
            }
        } else {
            $message = "Invalid action or insufficient permissions.";
            $messageType = "error";
        }
    }
}


// Fetch target users for rating
if ($isRestaurant || $isNGO) {
    $targetUsersQuery = $pdo->prepare("SELECT id, name FROM users WHERE user_type = ?");
    $targetUsersQuery->execute([$isRestaurant ? 'ngo' : 'restaurant']);
    $targetUsers = $targetUsersQuery->fetchAll(PDO::FETCH_ASSOC);
} else {
    $targetUsers = [];
}

// Handle filters and search
$whereClauses = [];
$queryParams = [];

if (!empty($_GET['search_query'])) {
    $whereClauses[] = "(f.name LIKE ? OR f.location LIKE ?)";
    $searchQuery = '%' . $_GET['search_query'] . '%';
    $queryParams[] = $searchQuery;
    $queryParams[] = $searchQuery;
}

if (!empty($_GET['food_type'])) {
    $whereClauses[] = "f.food_type = ?";
    $queryParams[] = $_GET['food_type'];
}

if (!empty($_GET['veg_option'])) {
    $whereClauses[] = "f.veg_option = ?";
    $queryParams[] = $_GET['veg_option'];
}

if (!empty($_GET['status'])) {
    $whereClauses[] = "f.status = ?";
    $queryParams[] = $_GET['status'];
}

if (!empty($_GET['food_amount'])) {
    $whereClauses[] = "f.food_amount >= ?";
    $queryParams[] = $_GET['food_amount'];
}

// Combine WHERE clauses for SQL query
$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    $query = $pdo->prepare("SELECT f.*, u.name as restaurant_name
                            FROM food_listings f
                            JOIN users u ON f.user_id = u.id
                            $whereSQL
                            ORDER BY f.time_available DESC");
    $query->execute($queryParams);
    $food_listings = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching listings: " . $e->getMessage();
    $messageType = "error";
    $food_listings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Surplus Food - FoodConnect</title>
    <link rel="stylesheet" href="/foodconnect/statics/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                <form action="food-listings.php" method="POST" class="add-listing-form" enctype="multipart/form-data">
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
                    
                    <div class="form-group">
                        <label for="food_image">Food Image:</label>
                        <input type="file" id="food_image" name="food_image" accept="image/*" onchange="previewImage(this)">
                        <img id="image_preview" class="image-preview" alt="Image preview">
                    </div>

                    <button type="submit" class="submit-btn">Add Food Listing</button>
                </form>
            </div>
        <?php endif; ?>

        <h1>Available Surplus Food</h1>
        
        <h2>Filter and Search Listings</h2>
        <form action="food-listings.php" method="GET" class="filter-form">
            <div class="form-group">
                <label for="search_query">Search by Name or Location:</label>
                <input type="text" id="search_query" name="search_query" placeholder="Search for food or location" value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="food_type">Food Type:</label>
                <select id="food_type" name="food_type">
                    <option value="">All</option>
                    <option value="Eating" <?= (isset($_GET['food_type']) && $_GET['food_type'] == 'Eating') ? 'selected' : '' ?>>Eating</option>
                    <option value="Decompose" <?= (isset($_GET['food_type']) && $_GET['food_type'] == 'Decompose') ? 'selected' : '' ?>>Decompose</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <option value="available" <?= (isset($_GET['status']) && $_GET['status'] == 'available') ? 'selected' : '' ?>>Available</option>
                    <option value="delivered" <?= (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : '' ?>>Delivered</option>
                    <option value="expired" <?= (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'selected' : '' ?>>Expired</option>
                    <option value="assigned" <?= (isset($_GET['status']) && $_GET['status'] == 'assigned') ? 'selected' : '' ?>>Assigned</option>
                </select>
            </div>
            <div class="form-group">
                <label for="food_amount">Food Amount (Minimum Servings):</label>
                <input type="number" id="food_amount" name="food_amount" min="1" value="<?= htmlspecialchars($_GET['food_amount'] ?? '') ?>">
            </div>
            <button type="submit">Apply Filters</button>
        </form>

        <div class="food_cards">
            <?php if (empty($food_listings)): ?>
                <p class="no-listings">No food listings available at the moment.</p>
            <?php else: ?>
                <?php foreach ($food_listings as $listing): ?>
                    <div class="food_card">
                        <!-- Dynamically apply the non-veg-header class -->
                        <h3 class="<?= $listing['veg_option'] === 'non-veg' ? 'non-veg-header' : '' ?>">
                            <?= htmlspecialchars($listing['name']) ?>
                        </h3>
                        <p class="restaurant-name">Posted by: <?= htmlspecialchars($listing['restaurant_name']) ?></p>
                        
                        <?php if (!empty($listing['image'])): ?>
                            <img src="<?= htmlspecialchars($listing['image']) ?>" alt="Food Image" class="food-image">
                        <?php endif; ?>
                        
                        <p class="time-available"><strong>Time Available:</strong> <?= htmlspecialchars(date('Y-m-d H:i', strtotime($listing['time_available']))) ?></p>
                        <p class="contact-number"><strong>Contact Number:</strong> <?= htmlspecialchars($listing['contact_number']) ?></p>
                        <p class="food-amount"><strong>Food Amount:</strong> <?= htmlspecialchars($listing['food_amount']) ?> servings</p>
                        <p class="food-type"><strong>Food Usage Type:</strong> <?= htmlspecialchars($listing['food_type']) ?></p>
                        <p class="food-status"><strong>Status:</strong> <?= ucfirst(htmlspecialchars($listing['status'])) ?></p>
                        <div class="veg-option">
                            <strong>Veg/Non-Veg:</strong>
                            <?php if ($isRestaurant && $userId == $listing['user_id']): ?>
                                <!-- Allow the owner to edit -->
                                <form action="food-listings.php" method="POST" class="veg-form">
                                    <input type="hidden" name="listing_id" value="<?= htmlspecialchars($listing['id']) ?>">
                                    <select name="veg_option" class="veg-dropdown" required>
                                        <option value="veg" <?= $listing['veg_option'] == 'veg' ? 'selected' : '' ?>>veg</option>
                                        <option value="non-veg" <?= $listing['veg_option'] == 'non-veg' ? 'selected' : '' ?>>non-veg</option>
                                    </select>
                                    <button type="submit" name="action" value="update_veg">Update</button>
                                </form>
                            <?php else: ?>
                                <!-- Display the value if not the owner -->
                                <?= htmlspecialchars(ucfirst($listing['veg_option'])) ?>
                            <?php endif; ?>
                        </div>

                        <p class="location"><strong>Location:</strong> <?= htmlspecialchars($listing['location']) ?></p>
                        <?php if ($isRestaurant && $userId == $listing['user_id']): ?>
                            <form action="food-listings.php" method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="listing_id" value="<?= htmlspecialchars($listing['id']) ?>">
                                <button type="submit">Delete This Listing</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($isRestaurant && $listing['user_id'] == $userId): ?>
                            <form action="food-listings.php" method="POST" class="food-status-form">
                                <input type="hidden" name="listing_id" value="<?= htmlspecialchars($listing['id']) ?>">
                                <label for="status_option">Update Status:</label>
                                <select name="status_option" class="status-dropdown" required>
                                    <option value="available" <?= $listing['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                    <option value="delivered" <?= $listing['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="expired" <?= $listing['status'] == 'expired' ? 'selected' : '' ?>>Expired</option>
                                    <option value="assigned" <?= $listing['status'] == 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                </select>
                                <button type="submit" name="action" value="update_status">Update</button>
                            </form>
                        <?php endif; ?>

                        

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

    <script>
        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>
