<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Chỉ chấp nhận POST.');
}
verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    set_flash('error', 'Mã sản phẩm không hợp lệ.');
    redirect('admin/products.php');
}

try {
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Đã xóa sản phẩm #' . $id . '.');
    } else {
        set_flash('error', 'Sản phẩm không tồn tại.');
    }
} catch (PDOException $exception) {
    if ((string)$exception->getCode() === '23000') {
        set_flash('error', 'Sản phẩm đã nằm trong đơn hàng, không thể xóa.');
    } else {
        error_log('Delete product: ' . $exception->getMessage());
        set_flash('error', 'Không thể xóa sản phẩm.');
    }
}
redirect('admin/products.php');
