<?php
require __DIR__ . '/includes/init.php';
if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Chỉ chấp nhận POST.');
}
verify_csrf();
$action = (string)($_POST['action'] ?? 'update');
if ($action === 'clear') {
    if (!empty($_SESSION['cart'])) {
        unset($_SESSION['cart']);
        set_flash('success', 'Đã xóa toàn bộ giỏ hàng.');
    }
    redirect('cart.php');
}
if ($action === 'remove') {
    $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    if ($id && $id > 0) {
        unset($_SESSION['cart'][$id]);
        set_flash('success', 'Đã xóa sản phẩm khỏi giỏ.');
    }
    redirect('cart.php');
}
if ($action !== 'update') {
    http_response_code(400);
    exit('Thao tác giỏ hàng không hợp lệ.');
}
$raw = $_POST['qty'] ?? [];
if (!is_array($raw)) {
    set_flash('error', 'Dữ liệu số lượng không hợp lệ.');
    redirect('cart.php');
}
// Chỉ chấp nhận các sản phẩm đã có trong giỏ.
$cart = $_SESSION['cart'] ?? [];
[$products] = get_cart_items();
$stockById = [];
foreach ($products as $item) {
    $stockById[(int)$item['id']] = (int)$item['stock'];
}
$newCart = [];
foreach ($cart as $id => $oldQty) {
    $id = (int)$id;
    $quantity = filter_var($raw[$id] ?? null, FILTER_VALIDATE_INT);
    if ($quantity === false || $quantity === null || $quantity < 0 || $quantity > 1000) {
        set_flash('error', 'Số lượng phải là số nguyên từ 0 đến 1000.');
        redirect('cart.php');
    }
    if ($quantity > ($stockById[$id] ?? 0)) {
        set_flash('error', 'Có sản phẩm vượt quá số lượng tồn kho.');
        redirect('cart.php');
    }
    if ($quantity > 0) {
        $newCart[$id] = $quantity;
    }
}
$_SESSION['cart'] = $newCart;
set_flash('success', 'Đã cập nhật giỏ hàng.');
redirect('cart.php');
