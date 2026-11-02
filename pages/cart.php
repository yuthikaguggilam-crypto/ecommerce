<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// 1. కార్ట్ లోపల క్వాంటిటీని అప్‌డేట్ చేసే PHP లాజిక్ (న్యూ ఫీచర్)
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $new_qty = intval($_POST['quantity']);

    if ($new_qty > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$new_qty, $cart_id])) {
            header("Location: cart.php?msg=updated");
            exit();
        }
    } else {
        // క్వాంటిటీ 0 లేదా అంతకంటే తక్కువ ఇస్తే ఆటోమేటిక్‌గా డిలీట్ అయిపోతుంది
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        header("Location: cart.php?msg=removed");
        exit();
    }
}

// 2. కార్ట్ నుండి సింగిల్ ప్రొడక్ట్ ని డిలీట్ చేయడం
if (isset($_GET['remove_id'])) {
    $cart_id = $_GET['remove_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$cart_id, $user_id])) {
        header("Location: cart.php?msg=removed");
        exit();
    }
}

// 3. కార్ట్ మొత్తాన్ని ఖాళీ చేయడం
if (isset($_GET['clear']) && $_GET['clear'] == 'all') {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    if ($stmt->execute([$user_id])) {
        header("Location: cart.php?msg=cleared");
        exit();
    }
}

// అలర్ట్ మెసేజ్ లు చూపించడం కోసం
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') $message = "<p class='success-msg'>✓ Cart updated successfully.</p>";
    if ($_GET['msg'] == 'removed') $message = "<p class='success-msg'>✓ Item removed from cart.</p>";
    if ($_GET['msg'] == 'cleared') $message = "<p class='success-msg'>✓ Cart cleared successfully.</p>";
}

// డేటాబేస్ నుండి ఈ యూజర్ కార్ట్ డేటాని లోడ్ చేయడం
$stmt = $conn->prepare("
    SELECT cart.id AS cart_id, cart.quantity, products.name, products.price, products.image 
    FROM cart 
    JOIN products ON cart.product_id = products.id 
    WHERE cart.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grand_total = 0;
foreach ($cart_items as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}

$table_rows = '';
if (count($cart_items) > 0) {
    foreach ($cart_items as $item) {
        $c_img = !empty($item['image']) ? $item['image'] : 'placeholder.jpg';
        $item_total = $item['price'] * $item['quantity'];
        $table_rows .= "
        <tr>
            <td>
                <img src='../images/" . htmlspecialchars($c_img) . "' class='cart-img' onerror=\"this.src='https://placehold.co';\">
            </td>
            <td style='font-weight: 500;'>" . htmlspecialchars($item['name']) . "</td>
            <td>$" . number_format($item['price'], 2) . "</td>
            <td>
                <!-- క్వాంటిటీని మార్చడానికి ఇన్పుట్ ఫార్మ్ -->
                <form method='POST' action='cart.php' style='display:flex; gap:5px; align-items:center;'>
                    <input type='hidden' name='cart_id' value='{$item['cart_id']}'>
                    <input type='number' name='quantity' value='{$item['quantity']}' min='1' class='qty-input'>
                    <button type='submit' name='update_quantity' class='btn-update-qty'>Update</button>
                </form>
            </td>
            <td style='font-weight: 600;'>$" . number_format($item_total, 2) . "</td>
            <td><a href='cart.php?remove_id={$item['cart_id']}' class='btn-remove' onclick=\"return confirm('Remove item?');\">Remove</a></td>
        </tr>";
    }
} else {
    $table_rows = "<tr><td colspan='6' class='empty-cart'><h3>Your cart is empty!</h3><a href='../index.php' class='btn btn-checkout'>Continue Shopping</a></td></tr>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        :root { --bg-main: #f8fafc; --panel-bg: #ffffff; --primary: #2563eb; --primary-hover: #1d4ed8; --text-main: #0f172a; --text-muted: #64748b; --border: #e2e8f0; --danger: #ef4444; --success: #10b981; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg-main); margin: 0; padding: 0; }
        .navbar { background-color: var(--panel-bg); border-bottom: 1px solid var(--border); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .navbar .logo { font-size: 24px; font-weight: 700; color: var(--primary); text-decoration: none; }
        .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 600; font-size: 15px; margin-left: 20px; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .card { background: var(--panel-bg); padding: 30px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        h2 { margin-top: 0; font-size: 24px; font-weight: 700; border-bottom: 2px solid var(--border); padding-bottom: 10px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        .cart-table th { color: var(--text-muted); font-weight: 600; padding: 12px; border-bottom: 2px solid var(--border); }
        .cart-table td { padding: 16px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .cart-img { width: 50px; height: 50px; object-fit: contain; background: #f8fafc; border: 1px solid var(--border); border-radius: 4px; }
        .qty-input { width: 50px; padding: 6px; border: 1px solid var(--border); border-radius: 4px; text-align: center; font-weight: 600; }
        .btn-update-qty { background-color: #f1f5f9; border: 1px solid var(--border); padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; color: var(--primary); transition: all 0.2s; }
        .btn-update-qty:hover { background-color: var(--primary); color: white; border-color: var(--primary); }
        .btn-remove { color: var(--danger); text-decoration: none; font-weight: 600; }
        .cart-summary { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--border); }
        .total-amount { font-size: 22px; font-weight: 700; color: var(--success); }
        .btn { padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; cursor: pointer; border: none; }
        .btn-clear { background-color: #f1f5f9; color: var(--text-muted); }
        .btn-checkout { background-color: var(--primary); color: white; }
        .empty-cart { text-align: center; padding: 40px; color: var(--text-muted); }
        .success-msg { background-color: #ecfdf5; color: var(--success); padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="../index.php" class="logo">E-Commerce</a>
    <div class="nav-links"><a href="../index.php">Home</a><a href="cart.php" style="color: var(--primary);">Cart (<?= count($cart_items); ?>)</a></div>
</nav>
<div class="container">
    <div class="card">
        <h2>Your Shopping Cart</h2>
        <?= $message; ?>
        <?php if (count($cart_items) > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $table_rows; ?>
                </tbody>
            </table>
            <div class="cart-summary">
                <div class="total-amount">Grand Total: $<?= number_format($grand_total, 2); ?></div>
                <div>
                    <a href="cart.php?clear=all" class="btn btn-clear">Clear Cart</a>
                    <button class="btn btn-checkout" onclick="alert('Order placed!')">Proceed to Checkout</button>
                </div>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <tbody>
                    <?php echo $table_rows; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
