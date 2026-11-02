<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🌟 సెక్యూరిటీ లాక్: యూజర్ లాగిన్ అవ్వకపోతే నేరుగా లాగిన్ పేజీకి పంపించేస్తుంది!
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

include 'includes/db.php';

// Add to Cart బటన్ ప్రెస్ చేసినప్పుడు రన్ అయ్యే డేటాబేస్ కోడ్
if (isset($_POST['add_to_cart'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = 1;

    $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check_stmt->execute([$user_id, $product_id]);
    $existing_item = $check_stmt->fetch();

    if ($existing_item) {
        $new_qty = $existing_item['quantity'] + 1;
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update_stmt->execute([$new_qty, $existing_item['id']]);
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert_stmt->execute([$user_id, $product_id, $quantity]);
    }

    header("Location: pages/cart.php");
    exit();
}

// ప్రొడక్ట్స్ ని డేటాబేస్ నుండి తెచ్చే కోడ్
$stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HTML లోపల చూపించడానికి ప్రొడక్ట్ గ్రిడ్ తయారు చేయడం
$grid_html = '';
if (count($products) > 0) {
    foreach ($products as $product) {
        $image_name = !empty($product['image']) ? $product['image'] : 'placeholder.jpg';
        $grid_html .= "
        <div class='product-card'>
            <h3>" . htmlspecialchars($product['name']) . "</h3>
            <div class='price'>Price: $" . number_format($product['price'], 2) . "</div>
            <div class='desc'>" . htmlspecialchars($product['description']) . "</div>
            <div class='img-box'>
                <img src='images/" . htmlspecialchars($image_name) . "' onerror=\"this.src='https://placehold.co';\">
            </div>
            <form method='POST' action='index.php'>
                <input type='hidden' name='product_id' value='{$product['id']}'>
                <button type='submit' name='add_to_cart' class='add-to-cart-btn'>Add to Cart</button>
            </form>
        </div>";
    }
} else {
    $grid_html = "<div style='grid-column: 1/-1; text-align:center; color:#95a5a6;'><h3>No products found!</h3></div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Store</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f3f7f9; margin: 0; padding: 0; }
        .header-container { display: flex; justify-content: space-between; align-items: center; background-color: #1a252f; color: white; padding: 10px 40px; }
        .header-container h1 { margin: 0; font-size: 24px; }
        .header-container nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; font-size: 14px; text-transform: uppercase; }
        .products-section { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .products-section h2 { font-size: 28px; color: #2c3e50; margin-bottom: 20px; }
        .products-grid { display: flex; flex-wrap: wrap; gap: 30px; }
        .product-card { background: #fff; border: 1px solid #e1e8ed; border-radius: 8px; padding: 25px; width: 220px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s ease; }
        .product-card:hover { transform: translateY(-5px); }
        .product-card h3 { margin: 10px 0 5px 0; font-size: 20px; color: #2c3e50; }
        .product-card .price { color: #7f8c8d; font-size: 15px; margin: 5px 0; font-weight: bold; }
        .product-card .desc { color: #95a5a6; font-size: 14px; margin: 10px 0; min-height: 40px; }
        .img-box { height: 160px; display: flex; justify-content: center; align-items: center; margin-bottom: 15px; overflow: hidden; }
        .img-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .add-to-cart-btn { background-color: #27ae60; color: white; border: none; padding: 12px 15px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; font-size: 15px; transition: background 0.2s; }
        .add-to-cart-btn:hover { background-color: #219653; }
    </style>
</head>
<body>
<div class="header-container">
    <h1>Welcome to Our Store - Hello, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="pages/cart.php">🛒 CART</a>
        <a href="pages/logout.php" style="background-color: #e74c3c; padding: 6px 12px; border-radius: 4px;">LOGOUT</a>
    </nav>
</div>
<div class="products-section">
    <h2>Products</h2>
    <div class="products-grid">
        <?php echo $grid_html; ?>
    </div>
</div>
</body>
</html>
