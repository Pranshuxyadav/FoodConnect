<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New User</title>
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
        <h2>Sign Up</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Capture POST data
            $name = $_POST['name'] ?? '';
            $contact_info = $_POST['contact_info'] ?? '';
            $user_type = $_POST['user_type'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validate the input
            if (empty($name) || empty($contact_info) || empty($user_type) || empty($email) || empty($password)) {
                echo '<p style="color:red;">All fields are required.</p>';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Database connection
                $servername = "localhost";
                $username = "root";
                $dbpassword = "";
                $dbname = "foodconnect";

                // Create connection
                $conn = new mysqli($servername, $username, $dbpassword, $dbname);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Check if the user already exists
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo '<p style="color:red;">User already exists!</p>';
                } else {
                    // Insert the new user into the database
                    $stmt = $conn->prepare("INSERT INTO users (name, contact_info, user_type, email, password) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $name, $contact_info, $user_type, $email, $hashed_password);

                    if ($stmt->execute()) {
                        echo '<p style="color:green;">Account created successfully!</p>';
                        // Optionally, redirect to another page
                        header('Location: main.php');
                        exit();
                    } else {
                        echo '<p style="color:red;">Error creating account: ' . $stmt->error . '</p>';
                    }
                }

                $stmt->close();
                $conn->close();
            }
        }
        ?>

        <form action="create_user.php" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required><br>

            <label for="contact_info">Contact Info:</label>
            <input type="text" id="contact_info" name="contact_info" required><br>

            <label for="user_type">User Type:</label>
            <select id="user_type" name="user_type" required>
                <option value="ngo">NGO</option>
                <option value="restaurant">Restaurant</option>
            </select><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br>

            <button type="submit">Create Account</button>
        </form>
    </section>

    <footer>
        <p>&copy; 2024 FoodConnect</p>
    </footer>
</body>
</html>
