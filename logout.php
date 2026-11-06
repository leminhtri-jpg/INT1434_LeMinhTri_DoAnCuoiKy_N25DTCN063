<?php
require __DIR__ . '/includes/init.php';
if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Chỉ chấp nhận POST.');
}
verify_csrf();
// Xóa phiên đăng nhập, giỏ hàng của phiên vẫn được giữ cho người dùng.
unset($_SESSION['user']);
session_regenerate_id(true);
set_flash('success', 'Bạn đã đăng xuất.');
redirect('index.php');
