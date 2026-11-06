<?php
require __DIR__.'/includes/init.php';
http_response_code(404);
$pageTitle='Không tìm thấy trang'; $activePage='';
require __DIR__.'/includes/header.php';
?>
<section class="empty-message"><h2>404 – Không tìm thấy trang</h2>
<p>Địa chỉ bạn nhập không tồn tại hoặc đã được thay đổi.</p>
<p><a class="btn" href="<?= e(url('index.php')) ?>">Quay về cửa hàng</a></p></section>
<?php require __DIR__.'/includes/footer.php'; ?>
