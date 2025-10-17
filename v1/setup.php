<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "lost_and_founds";

$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db($dbname);

$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        points INT DEFAULT 0,
        level VARCHAR(50) DEFAULT 'Beginner'
    )",
    "CREATE TABLE IF NOT EXISTS locations (
        location_id INT AUTO_INCREMENT PRIMARY KEY,
        location_name VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(50) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS lost_items (
        lost_item_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        item_name VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        location_id INT,
        category_id INT,
        date_lost DATE NOT NULL,
        image_path VARCHAR(255) DEFAULT NULL,
        status ENUM('open', 'claimed', 'closed') DEFAULT 'open',
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (location_id) REFERENCES locations(location_id),
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
    )",
    "CREATE TABLE IF NOT EXISTS found_items (
        found_item_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        lost_item_id INT,
        item_name VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        location_id INT,
        category_id INT,
        date_found DATE NOT NULL,
        image_path VARCHAR(255) DEFAULT NULL,
        contact_number VARCHAR(20) DEFAULT NULL,
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (lost_item_id) REFERENCES lost_items(lost_item_id),
        FOREIGN KEY (location_id) REFERENCES locations(location_id),
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
    )",
    "CREATE TABLE IF NOT EXISTS alerts (
        alert_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        lost_item_id INT,
        message TEXT NOT NULL,
        date_sent DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (lost_item_id) REFERENCES lost_items(lost_item_id)
    )"
];

foreach ($tables as $table_sql) {
    if ($conn->query($table_sql) === TRUE) {
        echo "Table created successfully or already exists.<br>";
    } else {
        die("Error creating table: " . $conn->error);
    }
}

$categories = [
    'Electronics', 'Clothing', 'Jewelry', 'Books', 'Others'
];
$stmt = $conn->prepare(query: "INSERT IGNORE INTO categories (category_name) VALUES (?)");
foreach ($categories as $category) {
    $stmt->bind_param("s", $category);
    $stmt->execute();
}
$stmt->close();

echo "Database and tables set up successfully!";
$conn->close();

?>
