<?php
require __DIR__ . '/includes/init.php';
if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Chỉ chấp nhận POST.');
}
verify_csrf();
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$quantity = $quantity === null ? 1 : $quantity;
if (!$productId || $productId < 1 || !$quantity || $quantity < 1 || $quantity > 1000) {
    set_flash('error', 'Sản phẩm hoặc số lượng không hợp lệ.');
    redirect('cart.php');
}

$stmt = db()->prepare('SELECT id, name, stock FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
$current = (int)($_SESSION['cart'][$productId] ?? 0);
if (!$product || $current + $quantity > (int)$product['stock']) {
    set_flash('error', 'Sản phẩm không tồn tại hoặc không đủ hàng.');
    redirect('cart.php');
}
$_SESSION['cart'][$productId] = $current + $quantity;
set_flash('success', 'Đã thêm ' . $product['name'] . ' vào giỏ hàng.');
redirect('cart.php');
