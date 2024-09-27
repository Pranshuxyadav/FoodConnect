<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header>
        <h1>Login</h1>
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
        <h2>Login to Your Account</h2>
        <?php
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
        // Start a session or manage login state if necessary
        session_start();
        $_SESSION['user_id'] = $user['id'];
        
        // Redirect to homepage
        header('Location: main.html');
        exit();  // Ensure script execution stops after redirection
    } else {
        echo '<p style="color:red;">Invalid email or password.</p>';
    }
}
?>


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
