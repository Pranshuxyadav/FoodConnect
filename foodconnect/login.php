<?php
session_start();  // Only one session_start() at the very beginning

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if user exists
    $query = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $query->execute(['email' => $email]);
    $user = $query->fetch();

    // Validate credentials
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];

        header('Location: main.php');
        exit();
    } else {
        $error_message = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FoodConnect</title>
    <link rel="stylesheet" href="templates/assets/styles.css">
</head>
<body>
    <header>
        <h1>FoodConnect</h1>
        <nav>
            <ul>
                <li><a href="main.php" class="active">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="food-listings.php">Food Listings</a></li>
                <li><a href="create_user.php">New User</a></li>
                <li><a href="login.php">existing user</a></li>
            </ul>
        </nav>
        <div class="user-info">
            <?php
            if (isset($_SESSION['name'])) {
                echo '<div class="user-profile">';
                echo '<span class="username">Welcome, ' . htmlspecialchars($_SESSION['name']) . '</span>';
                echo '<a href="logout.php" class="logout-link">Logout</a>';
                echo '</div>';
            } else {
                echo '<div class="auth-links">';
                echo '<a href="login.php" class="login-link">Login</a>';
                echo '<a href="create_user.php" class="signup-link">Sign Up</a>';
                echo '</div>';
            }
            ?>
        </div>
    </header>

    <section>
        <h2>Login to Your Account</h2>
        <?php if (isset($error_message)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Login</button>
        </form>
    </section>

    <footer>
        <p>&copy; 2024 FoodConnect</p>
    </footer>
</body>
</html>