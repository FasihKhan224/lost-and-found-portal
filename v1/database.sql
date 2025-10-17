
CREATE OR REPLACE DATABASE lost_and_found;
USE lost_and_found;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    points INT DEFAULT 0,
    level VARCHAR(50) DEFAULT 'Beginner'
);

CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL
);

CREATE TABLE lost_items (
    lost_item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location_id INT,
    date_lost DATE NOT NULL,
    status ENUM('open', 'claimed', 'closed') DEFAULT 'open',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

CREATE TABLE found_items (
    found_item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    lost_item_id INT,
    item_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location_id INT,
    date_found DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (lost_item_id) REFERENCES lost_items(lost_item_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

CREATE TABLE alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    lost_item_id INT,
    message TEXT NOT NULL,
    date_sent DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (lost_item_id) REFERENCES lost_items(lost_item_id)
);
```