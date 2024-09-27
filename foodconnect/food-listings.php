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

// Debug session data
//var_dump($_SESSION);

// Check if user is logged in and is a restaurant
$isRestaurant = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'restaurant';

// Handle form submission for adding a food listing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isRestaurant) {
    $name = $_POST['name'] ?? '';
    $time_available = $_POST['time_available'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $food_amount = $_POST['food_amount'] ?? '';
    $location = $_POST['location'] ?? '';

    if (!empty($name) && !empty($time_available) && !empty($contact_number) && !empty($food_amount) && !empty($location)) {
        // Insert the food listing into the database
        $stmt = $pdo->prepare("INSERT INTO food_listings (name, time_available, contact_number, food_amount, location, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $time_available, $contact_number, $food_amount, $location, $_SESSION['user_id']]);
        echo "<p style='color:green;'>Food listing added successfully!</p>";
    } else {
        echo "<p style='color:red;'>All fields are required to add a food listing.</p>";
    }
}

// Fetch existing food listings
$query = $pdo->prepare("SELECT * FROM food_listings");
$query->execute();
$food_listings = $query->fetchAll(PDO::FETCH_ASSOC);
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
                <li><a href="main.html" class="active">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="food-listings.php">Food Listings</a></li>
                <li><a href="create_user.php">New User</a></li>
                <li><a href="login.php">existing user</a></li>
            </ul>
        </nav>
    </header>

    <section>
        <h2>Available Surplus Food</h2>

        <div class="food_cards">
            <?php foreach ($food_listings as $listing): ?>
            <div class="food_card">
                <h3><?php echo htmlspecialchars($listing['name']); ?></h3>
                <p>Time Available: <?php echo htmlspecialchars($listing['time_available']); ?></p>
                <p>Contact Number: <?php echo htmlspecialchars($listing['contact_number']); ?></p>
                <p>Food Amount: <?php echo htmlspecialchars($listing['food_amount']); ?> people</p>
                <p>Location: <?php echo htmlspecialchars($listing['location']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($isRestaurant): ?>
        <h2>Add New Food Listing</h2>
        <form action="food-listings.php" method="POST">
            <label for="name">Food Name:</label>
            <input type="text" id="name" name="name" required><br>

            <label for="time_available">Time Available:</label>
            <input type="text" id="time_available" name="time_available" required><br>

            <label for="contact_number">Contact Number:</label>
            <input type="text" id="contact_number" name="contact_number" required><br>

            <label for="food_amount">Food Amount (people):</label>
            <input type="number" id="food_amount" name="food_amount" required><br>

            <label for="location">Location:</label>
            <input type="text" id="location" name="location" required><br>

            <button type="submit">Add Food Listing</button>
        </form>
        <?php else: ?>
        <p>You must be a restaurant to add a new food listing.</p>
        <?php endif; ?>
    </section>

    <footer>
        <p>&copy; 2024 FoodConnect</p>
    </footer>
</body>
</html>
