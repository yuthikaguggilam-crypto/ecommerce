<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $_POST['image']; 
    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $price, $description, $image])) {
        $message = "<div class='success-msg'>✓ Product added successfully!</div>";
    } else {
        $message = "<div class='error-msg'>✗ Failed to add product.</div>";
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        header("Location: dashboard.php?msg=deleted");
        exit();
    } else {
        $message = "<div class='error-msg'>✗ Failed to delete product.</div>";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = "<div class='success-msg'>✓ Product deleted successfully!</div>";
}

if (isset($_POST['update_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $_POST['image'];
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
    if ($stmt->execute([$name, $price, $description, $image, $id])) {
        $message = "<div class='success-msg'>✓ Product updated successfully!</div>";
    } else {
        $message = "<div class='error-msg'>✗ Failed to update product.</div>";
    }
}

$edit_product = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
$form_html = '';

if ($edit_product) {
    $form_html = "
    <h2>Edit Product Details</h2>
    $message
    <form method='POST' action='dashboard.php'>
        <input type='hidden' name='id' value='{$edit_product['id']}'>
        <div class='form-group'>
            <label>Product Name</label>
            <input type='text' name='name' value='" . htmlspecialchars($edit_product['name']) . "' required>
        </div>
        <div class='form-group'>
            <label>Price ($)</label>
            <input type='number' name='price' step='0.01' value='{$edit_product['price']}' required>
        </div>
        <div class='form-group'>
            <label>Description</label>
            <textarea name='description' rows='4' required>" . htmlspecialchars($edit_product['description']) . "</textarea>
        </div>
        <div class='form-group'>
            <label>Image Filename</label>
            <input type='text' name='image' value='" . htmlspecialchars($edit_product['image']) . "'>
        </div>
        <button type='submit' name='update_product' class='action-btn update-btn'>Update Product</button>
        <a href='dashboard.php' class='cancel-btn'>Cancel Modifications</a>
    </form>";
} else {
    $form_html = "
    <h2>Add New Product</h2>
    $message
    <form method='POST' action='dashboard.php'>
        <div class='form-group'>
            <label>Product Name</label>
            <input type='text' name='name' required placeholder='e.g. Wireless Mouse'>
        </div>
        <div class='form-group'>
            <label>Price ($)</label>
            <input type='number' name='price' step='0.01' required placeholder='e.g. 29.99'>
        </div>
        <div class='form-group'>
            <label>Description</label>
            <textarea name='description' rows='4' required placeholder='Enter specifications...'></textarea>
        </div>
        <div class='form-group'>
            <label>Image Filename</label>
            <input type='text' name='image' placeholder='e.g. mouse.jpg'>
        </div>
        <button type='submit' name='add_product' class='action-btn submit-btn'>Add Product</button>
    </form>";
}

$table_rows = '';
foreach ($products as $prod) {
    $table_rows .= "
    <tr>
        <td>#{$prod['id']}</td>
        <td style='font-weight: 500; color: #0f172a;'>" . htmlspecialchars($prod['name']) . "</td>
        <td style='font-weight: 600; color: #10b981;'>$" . number_format($prod['price'], 2) . "</td>
        <td style='color: #64748b;'>" . htmlspecialchars($prod['description']) . "</td>
        <td style='text-align: center; white-space: nowrap;'>
            <a href='dashboard.php?edit_id={$prod['id']}' class='btn-badge btn-edit'>Edit</a>
            <a href='dashboard.php?delete_id={$prod['id']}' class='btn-badge btn-delete' onclick=\"return confirm('Delete this product?');\">Delete</a>
        </td>
    </tr>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>
    <style>
        :root { --bg-color: #f4f6f9; --panel-bg: #ffffff; --primary: #2563eb; --primary-hover: #1d4ed8; --success: #10b981; --text-main: #1e293b; --text-muted: #64748b; --border: #e2e8f0; --warning: #f59e0b; --danger: #ef4444; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; }
        .header { background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 22px; }
        .header span { color: #38bdf8; font-weight: bold; }
        .nav-buttons { display: flex; gap: 12px; }
        .header a { color: white; text-decoration: none; padding: 8px 18px; border-radius: 6px; font-weight: 600; font-size: 14px; }
        .btn-store { background-color: var(--primary); }
        .btn-logout { background-color: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger); color: #f87171; }
        .container { max-width: 1300px; margin: 40px auto; padding: 0 20px; display: flex; gap: 30px; }
        .card { background: var(--panel-bg); padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .form-card { width: 360px; height: fit-content; }
        .list-card { flex: 1; }
        .card h2 { margin-top: 0; border-bottom: 2px solid var(--border); padding-bottom: 10px; font-size: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-muted); }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; }
        .action-btn { color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; font-size: 15px; text-align: center; }
        .submit-btn { background-color: var(--success); }
        .update-btn { background-color: var(--primary); }
        .cancel-btn { display: block; text-align: center; margin-top: 12px; color: var(--text-muted); text-decoration: none; }
        .prod-table { width: 100%; border-collapse: collapse; }
        .prod-table th { background-color: #f8fafc; color: var(--text-muted); padding: 14px 16px; border-bottom: 2px solid var(--border); text-align: left; }
        .prod-table td { padding: 16px; border-bottom: 1px solid var(--border); }
        .btn-badge { padding: 6px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-block; }
        .btn-edit { background-color: rgba(245, 158, 11, 0.15); color: var(--warning); margin-right: 5px; }
        .btn-delete { background-color: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .success-msg { background-color: #ecfdf5; color: #10b981; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-weight: bold; }
        .error-msg { background-color: #fef2f2; color: #ef4444; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
<div class="header">
    <h1>Admin Control Panel - Welcome, <span><?php echo $admin_name; ?></span></h1>
    <div class="nav-buttons">
        <a href="../index.php" class="btn-store">View Store</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>
<div class="container">
    <div class="card form-card">
        <?php echo $form_html; ?>
    </div>
    <div class="card list-card">
        <h2>Inventory / Current Products</h2>
        <table class="prod-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Title</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $table_rows; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
