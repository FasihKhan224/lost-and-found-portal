<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lost & Found</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="lost.php">Report Lost</a></li>
                    <li class="nav-item"><a class="nav-link" href="found.php">Report Found</a></li>
                    <li class="nav-item"><a class="nav-link" href="alerts.php">Alerts</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Your Alerts</h2>
        <?php
        $stmt = $conn->prepare("SELECT a.message, a.date_sent, li.item_name, fi.contact_number 
                                FROM alerts a 
                                JOIN lost_items li ON a.lost_item_id = li.lost_item_id 
                                LEFT JOIN found_items fi ON fi.lost_item_id = li.lost_item_id 
                                WHERE a.user_id = ? ORDER BY a.date_sent DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            echo "<p>No alerts at this time.</p>";
        } else {
            while ($row = $result->fetch_assoc()) {
                echo "<div class='card mb-2'><div class='card-body'>";
                echo "<h5 class='card-title'>" . htmlspecialchars($row['item_name']) . "</h5>";
                echo "<p class='card-text'>" . htmlspecialchars($row['message']) . "</p>";
                if ($row['contact_number']) {
                    echo "<p><strong>Contact Number:</strong> " . htmlspecialchars($row['contact_number']) . "</p>";
                }
                echo "<p><strong>Sent On:</strong> " . $row['date_sent'] . "</p>";
                echo "</div></div>";
            }
        }
        $stmt->close();
        ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>