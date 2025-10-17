<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$require_contact = false;
$lost_item_location_id = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_found'])) {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $location_name = $_POST['location'];
    $category_id = (int)$_POST['category_id'];
    $date_found = $_POST['date_found'];
    $lost_item_id = !empty($_POST['lost_item_id']) ? (int)$_POST['lost_item_id'] : null;
    $contact_number = !empty($_POST['contact_number']) ? $_POST['contact_number'] : null;
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

    if ($contact_number && !preg_match("/^[0-9]{10,15}$/", $contact_number)) {
        $error = "Invalid contact number. Use 10-15 digits.";
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

        if (!$lost_item_id) {
            $stmt = $conn->prepare("SELECT lost_item_id, user_id FROM lost_items WHERE location_id = ? AND status = 'open' LIMIT 1");
            $stmt->bind_param("i", $location_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $lost_item_id = $row['lost_item_id'];
                $lost_item_user_id = $row['user_id'];
                $require_contact = true;
            }
        }

        if ($require_contact && !$contact_number) {
            $error = "Contact number is required for items found at a location matching a lost item.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO found_items (user_id, lost_item_id, item_name, description, location_id, category_id, date_found, image_path, contact_number, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iissiisss", $user_id, $lost_item_id, $item_name, $description, $location_id, $category_id, $date_found, $image_path, $contact_number);
                if ($stmt->execute()) {
                    $found_item_id = $conn->insert_id;
                    if ($lost_item_id) {
                        $stmt = $conn->prepare("UPDATE lost_items SET status = 'claimed' WHERE lost_item_id = ?");
                        $stmt->bind_param("i", $lost_item_id);
                        $stmt->execute();
                        if ($contact_number && isset($lost_item_user_id)) {
                            $stmt = $conn->prepare("INSERT INTO alerts (user_id, lost_item_id, message, date_sent) 
                                                    VALUES (?, ?, ?, NOW())");
                            $message = "Found item '$item_name' matches your lost item near $location_name. Contact: $contact_number";
                            $stmt->bind_param("iis", $lost_item_user_id, $lost_item_id, $message);
                            $stmt->execute();
                        }
                    }
                    $conn->commit();
                    $success = "Found item reported successfully!";
                } else {
                    throw new Exception("Failed to report found item.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to report found item.";
            }
        }
        $stmt->close();
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
    $lost_item_id = $_POST['lost_item_id'];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE found_items fi JOIN lost_items li ON fi.lost_item_id = li.lost_item_id 
                                SET fi.status = 'confirmed', li.status = 'closed' 
                                WHERE fi.lost_item_id = ? AND li.user_id = ?");
        $stmt->bind_param("ii", $lost_item_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt = $conn->prepare("UPDATE users u JOIN found_items fi ON u.user_id = fi.user_id 
                                    SET u.points = u.points + 10, 
                                        u.level = CASE 
                                            WHEN u.points + 10 <= 50 THEN 'Beginner'
                                            WHEN u.points + 10 <= 100 THEN 'Intermediate'
                                            ELSE 'Expert'
                                        END
                                    WHERE fi.lost_item_id = ?");
            $stmt->bind_param("i", $lost_item_id);
            $stmt->execute();
            $conn->commit();
            $success = "Item confirmed! Finder awarded 10 points.";
        } else {
            throw new Exception("Failed to confirm item.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to confirm item.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item</title>
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
        <h2 class="text-primary">Report Found Item</h2>
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
                <label for="date_found" class="form-label">Date Found</label>
                <input type="date" class="form-control" id="date_found" name="date_found" required>
            </div>
            <div class="mb-3">
                <label for="item_image" class="form-label">Upload Image (Optional)</label>
                <input type="file" class="form-control" id="item_image" name="item_image" accept="image/*">
            </div>
            <div class="mb-3">
                <label for="lost_item_id" class="form-label">Link to Lost Item (Optional)</label>
                <select class="form-control" id="lost_item_id" name="lost_item_id">
                    <option value="">Select Lost Item</option>
                    <?php
                    $stmt = $conn->prepare("SELECT lost_item_id, item_name FROM lost_items WHERE status = 'open'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['lost_item_id']}'>" . htmlspecialchars($row['item_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="contact_number" class="form-label">Contact Number (Required if location matches a lost item)</label>
                <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="e.g., 1234567890">
            </div>
            <button type="submit" name="report_found" class="btn btn-primary">Report Found</button>
        </form>
        <h3 class="mt-4 text-primary">Your Found Items</h3>
        <div class="row">
            <?php
            $stmt = $conn->prepare("SELECT fi.found_item_id, fi.item_name, fi.description, fi.image_path, l.location_name, c.category_name, fi.date_found, fi.status, fi.contact_number 
                                    FROM found_items fi 
                                    JOIN locations l ON fi.location_id = l.location_id 
                                    JOIN categories c ON fi.category_id = c.category_id 
                                    WHERE fi.user_id = ? ORDER BY fi.date_found DESC");
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
                echo "<p><strong>Found On:</strong> " . date('d-m-Y', strtotime($row['date_found'])) . "</p>";
                echo "<p><strong>Contact Number:</strong> " . ($row['contact_number'] ? htmlspecialchars($row['contact_number']) : 'N/A') . "</p>";
                echo "<p><strong>Status:</strong> " . $row['status'] . "</p>";
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
            $('#date_found').on('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                if (selectedDate > today) {
                    alert('Date cannot be in the future.');
                    this.value = '';
                }
            });
            $('#location').on('change', function() {
                const location = this.value;
                $.post('check_location.php', { location: location }, function(data) {
                    if (data.match) {
                        $('#contact_number').prop('required', true);
                        alert('This location matches a lost item. Please provide a contact number.');
                    } else {
                        $('#contact_number').prop('required', false);
                    }
                });
            });
        });
    </script>
</body>
</html>