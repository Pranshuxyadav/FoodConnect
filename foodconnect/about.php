<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FoodConnect</title>
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
        session_start();
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
        <h2>Our Mission</h2>
        <p>Reducing food waste and hunger through technology and community.</p>
        <h3>Our Story</h3>
        <p>The story behind FoodConnect and its founders...</p>
        <h3>Meet the Team</h3>
        <p>Brief bios and photos of the team members...</p>
        
    </section>

    <footer>
        <p>&copy; 2024 FoodConnect</p>
    </footer>
</body>
</html>
