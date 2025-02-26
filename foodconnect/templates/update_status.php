<?php
// Database Connection
$conn = new mysqli("localhost", "root", "", "foodconnect");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Listings
$sql = "SELECT * FROM food_listings";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Listings</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Available Food Listings</h1>
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <li>
                <strong><?php echo $row['food_name']; ?></strong> - Status: 
                <span id="status-<?php echo $row['id']; ?>"><?php echo $row['status']; ?></span>
                
                <?php if ($_SESSION['role'] == 'ngo' && $row['status'] == 'pending'): ?>
                    <button onclick="acceptOrder(<?php echo $row['id']; ?>)">Accept Order</button>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'restaurant' && $row['status'] == 'waiting for approval'): ?>
                    <button onclick="approveOrder(<?php echo $row['id']; ?>)">Approve Order</button>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>

    <script>
        function acceptOrder(listingId) {
            $.post("update_status.php", { id: listingId, status: "waiting for approval" }, function(response) {
                if (response.success) {
                    $("#status-" + listingId).text("Waiting for Approval");
                }
            }, "json");
        }

        function approveOrder(listingId) {
            $.post("update_status.php", { id: listingId, status: "approved" }, function(response) {
                if (response.success) {
                    $("#status-" + listingId).text("Approved");
                }
            }, "json");
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>

<?php
// update_status.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE food_listings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $success = $stmt->execute();
    
    echo json_encode(["success" => $success]);
    
    $stmt->close();
    $conn->close();
    exit();
}
?>