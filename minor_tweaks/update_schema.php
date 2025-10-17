<?php
require 'db.php';

// Add phone column to users table
$sql = "ALTER TABLE users ADD COLUMN phone VARCHAR(15)";
if ($conn->query($sql) === TRUE) {
    echo "Added phone to users successfully.<br>";
} else {
    echo "Error adding phone to users: " . $conn->error . "<br>";
}


echo "Database schema update complete!";
$conn->close();
?>
