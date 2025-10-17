<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $location_name = $_POST['location'];
    $category_id = (int)$_POST['category_id'];
    $date_lost = $_POST['date_lost'];
    $image_path = null;

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['item_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $image_info = getimagesize($_FILES['item_image']['tmp_name']);
            if ($image_info !== false) {
                $new_filename = uniqid() . "." . $ext;
                $upload_dir = 'Uploads/';
                $image_path = $upload_dir . $new_filename;
                if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $image_path)) {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Uploaded file is not a valid image.";
            }
        } else {
            $error = "Invalid image format. Allowed: jpg, jpeg, png, gif.";
        }
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("SELECT location_id FROM locations WHERE location_name = ?");
        $stmt->bind_param("s", $location_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $location_id = $row['location_id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO locations (location_name) VALUES (?)");
            $stmt->bind_param("s", $location_name);
            $stmt->execute();
            $location_id = $conn->insert_id;
        }
        $stmt = $conn->prepare("INSERT INTO lost_items (user_id, item_name, description, location_id, category_id, date_lost, image_path, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'open')");
        $stmt->bind_param("issiiss", $user_id, $item_name, $description, $location_id, $category_id, $date_lost, $image_path);
        if ($stmt->execute()) {
            $lost_item_id = $conn->insert_id;
            $stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $cat = $stmt->get_result()->fetch_assoc();
            $category_name = $cat['category_name'];
            $stmt = $conn->prepare("INSERT INTO alerts (user_id, lost_item_id, message, date_sent) 
                                    SELECT user_id, ?, ?, NOW() FROM users WHERE user_id != ?");
            $message = "Lost item '$item_name' ($category_name) reported near $location_name!";
            $stmt->bind_param("isi", $lost_item_id, $message, $user_id);
            $stmt->execute();
            $success = "Lost item reported successfully!";
        } else {
            $error = "Failed to report lost item.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
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
        <h2 class="text-primary">Report Lost Item</h2>
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" enctype="multipart/form-data" class="form-signin shadow-sm p-4 bg-white rounded">
            <div class="mb-3">
                <label for="item_name" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="item_name" name="item_name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php
                    $stmt = $conn->query("SELECT category_id, category_name FROM categories");
                    while ($row = $stmt->fetch_assoc()) {
                        echo "<option value='{$row['category_id']}'>" . htmlspecialchars($row['category_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="date_lost" class="form-label">Date Lost</label>
                <input type="date" class="form-control" id="date_lost" name="date_lost" required>
            </div>
            <div class="mb-3">
                <label for="item_image" class="form-label">Upload Image (Optional)</label>
                <input type="file" class="form-control" id="item_image" name="item_image" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Report Lost</button>
        </form>

        <h3 class="mt-4 text-primary">Your Lost Items</h3>
        <div class="row">
            <?php
            $stmt = $conn->prepare("SELECT li.lost_item_id, li.item_name, li.description, li.image_path, l.location_name, c.category_name, li.date_lost, li.status 
                                    FROM lost_items li 
                                    JOIN locations l ON li.location_id = l.location_id 
                                    JOIN categories c ON li.category_id = c.category_id 
                                    WHERE li.user_id = ? ORDER BY li.date_lost DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                echo "<div class='col-md-4 mb-3'>";
                echo "<div class='card h-100 shadow-sm'>";
                if ($row['image_path']) {
                    echo "<img src='" . htmlspecialchars($row['image_path']) . "' class='card-img-top' alt='Item Image' style='height: 200px; object-fit: cover;'>";
                } else {
                    echo "<img src='https://via.placeholder.com/300x200' class='card-img-top' alt='No Image'>";
                }
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>" . htmlspecialchars($row['item_name']) . "</h5>";
                echo "<p class='card-text'>" . htmlspecialchars($row['description']) . "</p>";
                echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location_name']) . "</p>";
                echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category_name']) . "</p>";
                echo "<p><strong>Lost On:</strong> " . $row['date_lost'] . "</p>";
                echo "<p><strong>Status:</strong> " . $row['status'] . "</p>";

                if ($row['status'] == 'claimed') {
                    // Get founder info
                    $found_stmt = $conn->prepare("
                        SELECT u.username, u.phone 
                        FROM found_items f 
                        JOIN users u ON f.user_id = u.user_id 
                        WHERE f.lost_item_id = ? AND f.status IN ('confirmed', 'pending') 
                        LIMIT 1
                    ");
                    $found_stmt->bind_param("i", $row['lost_item_id']);
                    $found_stmt->execute();
                    $found_result = $found_stmt->get_result();

                    if ($founder = $found_result->fetch_assoc()) {
                        echo "<div class='alert alert-info'><strong>Item Claimed!</strong><br>";
                        echo "Finder: " . htmlspecialchars($founder['username']) . "<br>";
                        echo "Contact: " . htmlspecialchars($founder['phone']) . "</div>";
                    } else {
                        echo "<div class='alert alert-warning'>Claimed, but founder info not available.</div>";
                    }

                    echo "<form method='POST' action='found.php'>";
                    echo "<input type='hidden' name='lost_item_id' value='" . $row['lost_item_id'] . "'>";
                    echo "<button type='submit' name='confirm' class='btn btn-success'>Confirm Found</button>";
                    echo "</form>";

                    $found_stmt->close();
                }

                echo "</div></div></div>";
            }
            $stmt->close();
            ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#date_lost').on('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                if (selectedDate > today) {
                    alert('Date cannot be in the future.');
                    this.value = '';
                }
            });
        });
    </script>
</body>
</html>
