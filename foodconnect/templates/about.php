<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FoodConnect</title>
    <link rel="stylesheet" href="/foodconnect/statics/styles.css">
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
    <p>At FoodConnect, our mission is to bridge the gap between surplus food and those in need. Through innovative technology and the power of community, we aim to eliminate food waste while combating hunger.</p>
    
    <h3>Our Story</h3>
    <p>FoodConnect was born from a shared vision to make a meaningful difference. What started as a small idea among passionate founders has grown into a thriving platform, connecting restaurants, NGOs, and individuals to create a more sustainable future. Every meal saved is a step closer to a hunger-free world.</p>
    
    <h3>Meet the Team</h3>
    <p>Behind FoodConnect is a team of dedicated changemakers, each bringing unique skills and a shared commitment to our mission. From tech innovators to community builders, our team works tirelessly to ensure every surplus meal finds a new purpose. Stay tuned for their inspiring stories and photos!</p>
</section>


    <footer>
        <p>&copy; 2024 FoodConnect</p>
    </footer>
</body>
</html>
