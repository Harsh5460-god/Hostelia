```php
<?php
require_once "admin_check.php";
require_once "db.php";

$message = "";
$messageType = "success";

if (empty($_SESSION["food_csrf"])) {
    $_SESSION["food_csrf"] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION["food_csrf"];

// Image upload helper
function uploadFoodImage($file) {
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed.");
    }

    if ($file["size"] > 3 * 1024 * 1024) {
        throw new Exception("Image must be smaller than 3 MB.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime])) {
        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
    }

    $folder = __DIR__ . "/uploads/food/";

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        throw new Exception("Could not create food image folder.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];

    if (!move_uploaded_file($file["tmp_name"], $folder . $filename)) {
        throw new Exception("Could not save uploaded image.");
    }

    return "uploads/food/" . $filename;
}

function deleteFoodImage($path) {
    if (!$path) {
        return;
    }

    $base = realpath(__DIR__ . "/uploads/food");
    $file = realpath(__DIR__ . "/" . $path);

    if ($base && $file && strpos($file, $base . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
        unlink($file);
    }
}

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$categories = [
    "Breakfast",
    "Lunch",
    "Dinner",
    "Snacks",
    "Beverages"
];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {
        if (!hash_equals($csrf, $_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid request. Please refresh the page.");
        }

        // Add new item
        if (isset($_POST["add_item"])) {

            $name = trim($_POST["item_name"] ?? "");
            $category = $_POST["category"] ?? "";
            $description = trim($_POST["description"] ?? "");
            $price = $_POST["price"] ?? "";

            if (
                $name === "" ||
                !in_array($category, $categories, true) ||
                !is_numeric($price) ||
                (float)$price <= 0
            ) {
                throw new Exception("Please enter valid food details.");
            }

            $image = uploadFoodImage($_FILES["image"] ?? null);

            $stmt = $conn->prepare("
                INSERT INTO food_items
                (item_name, category, description, price, image, is_available)
                VALUES (?, ?, ?, ?, ?, 1)
            ");

            $price = (float)$price;
            $stmt->bind_param("sssds", $name, $category, $description, $price, $image);
            $stmt->execute();
            $stmt->close();

            $message = "Food item added successfully!";
        }

        // Edit existing item
        elseif (isset($_POST["update_item"])) {

            $id = (int)($_POST["item_id"] ?? 0);
            $name = trim($_POST["item_name"] ?? "");
            $category = $_POST["category"] ?? "";
            $description = trim($_POST["description"] ?? "");
            $price = $_POST["price"] ?? "";

            if (
                $id <= 0 ||
                $name === "" ||
                !in_array($category, $categories, true) ||
                !is_numeric($price) ||
                (float)$price <= 0
            ) {
                throw new Exception("Please enter valid food details.");
            }

            $stmt = $conn->prepare("SELECT image FROM food_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$old) {
                throw new Exception("Food item not found.");
            }

            $newImage = uploadFoodImage($_FILES["image"] ?? null);
            $image = $newImage ?? $old["image"];

            $stmt = $conn->prepare("
                UPDATE food_items
                SET item_name = ?, category = ?, description = ?,
                    price = ?, image = ?
                WHERE id = ?
            ");

            $price = (float)$price;
            $stmt->bind_param(
                "sssdsi",
                $name,
                $category,
                $description,
                $price,
                $image,
                $id
            );

            $stmt->execute();
            $stmt->close();

            if ($newImage !== null) {
                deleteFoodImage($old["image"]);
            }

            $message = "Food item updated successfully!";
        }

        // Change availability
        elseif (isset($_POST["toggle_item"])) {

            $id = (int)($_POST["item_id"] ?? 0);
            $availability = (int)($_POST["availability"] ?? -1);

            if ($id <= 0 || !in_array($availability, [0, 1], true)) {
                throw new Exception("Invalid availability request.");
            }

            $stmt = $conn->prepare("
                UPDATE food_items SET is_available = ? WHERE id = ?
            ");

            $stmt->bind_param("ii", $availability, $id);
            $stmt->execute();
            $stmt->close();

            $message = "Food availability updated!";
        }

        // Delete item only if it has no order history
        elseif (isset($_POST["delete_item"])) {

            $id = (int)($_POST["item_id"] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid food item.");
            }

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM food_order_items
                WHERE food_item_id = ?
            ");

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $used = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ((int)$used["total"] > 0) {
                throw new Exception(
                    "This item has order history. Mark it unavailable instead of deleting it."
                );
            }

            $stmt = $conn->prepare("SELECT image FROM food_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM food_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            if ($old) {
                deleteFoodImage($old["image"]);
            }

            $message = "Food item deleted successfully!";
        }

    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch food items
$result = $conn->query("
    SELECT *
    FROM food_items
    ORDER BY FIELD(category, 'Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Beverages'),
    item_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Food Items | Hostelia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
          rel="stylesheet">

    <style>
        body {
            background: #f7f3eb;
            color: #40382f;
            font-family: 'Poppins', sans-serif;
        }

        h2, h4 {
            font-family: 'Playfair Display', serif;
        }

        .food-container {
            max-width: 1150px;
            margin: 40px auto;
        }

        .food-card {
            background: #fffdf8;
            border: 1px solid #e2d8c9;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(64, 56, 47, 0.05);
        }

        .form-control, .form-select {
            border-color: #d8cbbb;
            padding: 10px;
        }

        .btn-brown {
            background: #40382f;
            color: white;
        }

        .btn-brown:hover {
            background: #5d4c39;
            color: white;
        }

        .food-image {
            width: 75px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
        }

        .table {
            vertical-align: middle;
        }

        .edit-form {
            min-width: 240px;
        }
    </style>
</head>

<body>

<div class="container food-container">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2>Extra Food Management</h2>
            <p class="text-secondary mb-0">
                Manage food items, prices, images and availability.
            </p>
        </div>

        <a href="admin.php" class="btn btn-brown">
            Back to Dashboard
        </a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?= safe($messageType) ?>">
            <?= safe($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Food -->

    <div class="food-card">

        <h4 class="mb-4">Add New Food Item</h4>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="csrf_token" value="<?= safe($csrf) ?>">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Food Name</label>
                    <input type="text" name="item_name" class="form-control"
                           maxlength="100" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= safe($category) ?>">
                                <?= safe($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" name="price" class="form-control"
                           min="0.01" step="0.01" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Food Image</label>
                    <input type="file" name="image" class="form-control"
                           accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"
                              rows="2"></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" name="add_item" class="btn btn-brown">
                        Add Food Item
                    </button>
                </div>

            </div>

        </form>

        <small class="text-secondary d-block mt-3">
            Allowed images: JPG, PNG, WEBP. Maximum size: 3 MB.
        </small>

    </div>

    <!-- Existing Food Items -->

    <div class="food-card">

        <h4 class="mb-4">Manage Existing Food Items</h4>

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Food Details / Edit</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($item = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php if (!empty($item["image"])): ?>
                                    <img class="food-image"
                                         src="<?= safe($item["image"]) ?>"
                                         alt="Food">
                                <?php else: ?>
                                    <span class="text-secondary">No image</span>
                                <?php endif; ?>
                            </td>

                            <td>

                                <form method="POST" enctype="multipart/form-data"
                                      class="edit-form">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= safe($csrf) ?>">

                                    <input type="hidden" name="item_id"
                                           value="<?= (int)$item["id"] ?>">

                                    <label class="form-label small">Food Name</label>
                                    <input type="text" name="item_name"
                                           class="form-control form-control-sm mb-2"
                                           value="<?= safe($item["item_name"]) ?>"
                                           maxlength="100" required>

                                    <label class="form-label small">Category</label>
                                    <select name="category"
                                            class="form-select form-select-sm mb-2">

                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= safe($category) ?>"
                                                <?= $item["category"] === $category ? "selected" : "" ?>>
                                                <?= safe($category) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                    <label class="form-label small">Description</label>
                                    <textarea name="description"
                                              class="form-control form-control-sm mb-2"
                                              rows="2"><?= safe($item["description"]) ?></textarea>

                                    <label class="form-label small">Price (₹)</label>
                                    <input type="number" name="price"
                                           class="form-control form-control-sm mb-2"
                                           min="0.01" step="0.01"
                                           value="<?= safe($item["price"]) ?>" required>

                                    <label class="form-label small">Replace Image (optional)</label>
                                    <input type="file" name="image"
                                           class="form-control form-control-sm mb-2"
                                           accept="image/jpeg,image/png,image/webp">

                                    <button type="submit" name="update_item"
                                            class="btn btn-brown btn-sm">
                                        Save Changes
                                    </button>

                                </form>

                            </td>

                            <td>
                                <?php if ((int)$item["is_available"] === 1): ?>
                                    <span class="badge bg-success">Available</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unavailable</span>
                                <?php endif; ?>

                                <form method="POST" class="mt-2">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= safe($csrf) ?>">

                                    <input type="hidden" name="item_id"
                                           value="<?= (int)$item["id"] ?>">

                                    <input type="hidden" name="availability"
                                           value="<?= (int)$item["is_available"] === 1 ? 0 : 1 ?>">

                                    <button type="submit" name="toggle_item"
                                            class="btn btn-warning btn-sm">
                                        <?= (int)$item["is_available"] === 1
                                            ? "Mark Unavailable"
                                            : "Mark Available" ?>
                                    </button>

                                </form>
                            </td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this food item?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= safe($csrf) ?>">

                                    <input type="hidden" name="item_id"
                                           value="<?= (int)$item["id"] ?>">

                                    <button type="submit" name="delete_item"
                                            class="btn btn-danger btn-sm">
                                        Delete
                                    </button>

                                </form>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="text-center">
                            No food items added yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
```