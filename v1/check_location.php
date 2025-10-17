<?php
require 'db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['location'])) {
    $location_name = trim($_POST['location']);
    $stmt = $conn->prepare("SELECT location_id FROM locations WHERE location_name = ?");
    $stmt->bind_param("s", $location_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $location_id = $row['location_id'];
    } else {
        echo json_encode(['match' => false]);
        exit;
    }
    $stmt = $conn->prepare("SELECT lost_item_id FROM lost_items WHERE location_id = ? AND status = 'open'");
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['match' => $result->num_rows > 0]);
    $stmt->close();
} else {
    echo json_encode(['match' => false]);
   
}
?>