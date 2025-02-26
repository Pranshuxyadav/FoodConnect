<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Invalid access.");
}

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

$rater_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$rating_id = $_POST['rating_id'] ?? null;
$rated_user_id = $_POST['rated_user_id'] ?? null;
$rating_score = (int)($_POST['rating_score'] ?? 0);
$comment = $_POST['comment'] ?? '';

if ($action === 'add' || $action === 'edit') {
    // Validate rating score
    if ($rating_score < 1 || $rating_score > 5) {
        die("Invalid rating score.");
    }

    if ($action === 'add') {
        // Insert a new rating
        $stmt = $pdo->prepare("INSERT INTO user_ratings (rater_id, rated_user_id, rating_score, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$rater_id, $rated_user_id, $rating_score, $comment]);
        $message = "Rating submitted successfully!";
    } elseif ($action === 'edit' && $rating_id) {
        // Update an existing rating
        $stmt = $pdo->prepare("UPDATE user_ratings SET rating_score = ?, comment = ? WHERE id = ? AND rater_id = ?");
        $stmt->execute([$rating_score, $comment, $rating_id, $rater_id]);
        $message = "Rating updated successfully!";
    }
} elseif ($action === 'delete' && $rating_id) {
    // Delete a review
    $stmt = $pdo->prepare("DELETE FROM user_ratings WHERE id = ? AND rater_id = ?");
    $stmt->execute([$rating_id, $rater_id]);
    $message = "Rating deleted successfully!";
}

header("Location: food-listings.php?message=" . urlencode($message));
exit;
?>