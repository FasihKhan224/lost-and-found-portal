<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, points, level FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle search and category filter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$sql = "SELECT li.lost_item_id, li.item_name, li.description, li.image_path, l.location_name, li.date_lost, c.category_name 
        FROM lost_items li 
        JOIN locations l ON li.location_id = l.location_id 
        JOIN categories c ON li.category_id = c.category_id 
        WHERE li.status = 'open'";
$params = [];
$types = "";
if ($search_query) {
    $sql .= " AND (li.item_name LIKE ? OR l.location_name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}
if ($category_id) {
    $sql .= " AND li.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}
$sql .= " ORDER BY li.date_lost DESC LIMIT 5";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$lost_items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lost & Found</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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
        <h1 class="display-4 text-primary">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Your Profile</h5>
                <p><strong>Points:</strong> <?php echo $user['points']; ?></p>
                <p><strong>Level:</strong> <?php echo $user['level']; ?></p>
            </div>
        </div>
        <h3 class="mb-3">Search Lost Items</h3>
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by item name or location" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <select name="category_id" class="form-control">
                        <option value="0">All Categories</option>
                        <?php
                        $stmt = $conn->query("SELECT category_id, category_name FROM categories");
                        while ($cat = $stmt->fetch_assoc()) {
                            $selected = $cat['category_id'] == $category_id ? 'selected' : '';
                            echo "<option value='{$cat['category_id']}' $selected>" . htmlspecialchars($cat['category_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
        </form>
        <h3 class="mb-3">Recent Lost Items</h3>
        <?php if ($lost_items->num_rows == 0) { ?>
            <p>No lost items found.</p>
        <?php } else { ?>
            <div id="lostItemsCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $first = true;
                    while ($row = $lost_items->fetch_assoc()) {
                        $active = $first ? 'active' : '';
                        echo "<div class='carousel-item $active'>";
                        echo "<div class='card mx-auto' style='max-width: 600px;'>";
                        if ($row['image_path']) {
                            echo "<img src='" . htmlspecialchars($row['image_path']) . "' class='card-img-top' alt='Item Image' style='height: 300px; object-fit: cover;'>";
                        } else {
                            echo "<img src='https://via.placeholder.com/600x300' class='card-img-top' alt='No Image'>";
                        }
                        echo "<div class='card-body'>";
                        echo "<h5 class='card-title'>" . htmlspecialchars($row['item_name']) . "</h5>";
                        echo "<p class='card-text'>" . htmlspecialchars($row['description']) . "</p>";
                        echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location_name']) . "</p>";
                        echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category_name']) . "</p>";
                        echo "<p><strong>Lost On:</strong> " . $row['date_lost'] . "</p>";
                        echo "</div></div></div>";
                        $first = false;
                    }
                    $stmt->close();
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#lostItemsCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#lostItemsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        <?php } ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
