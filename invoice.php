<?php
require __DIR__.'/includes/init.php';require_login();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
// Admin được xem mọi hóa đơn; khách chỉ xem hóa đơn chính mình.
if(is_admin()) {
  $stmt=db()->prepare('SELECT * FROM orders WHERE id=?');$stmt->execute([$id]);
} else {
  $stmt=db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');$stmt->execute([$id,(int)current_user()['id']]);
}
$order=$stmt->fetch();
if(!$order){http_response_code(404);$pageTitle='Không có hóa đơn';require __DIR__.'/includes/header.php';echo '<h2>Không tìm thấy hóa đơn</h2>';require __DIR__.'/includes/footer.php';exit;}
$stmt=db()->prepare('SELECT product_name,price,quantity FROM order_items WHERE order_id=? ORDER BY id');
$stmt->execute([$id]);$items=$stmt->fetchAll();
$pageTitle='Hóa đơn #'.$id;$activePage='orders.php';require __DIR__.'/includes/header.php';
?>
<section class="invoice-paper">
<div class="invoice-top"><div><p class="eyebrow">HÓA ĐƠN BÁN HÀNG</p><h2><?= e(APP_NAME) ?></h2><p>Chủ cửa hàng: <?= e(STUDENT_NAME) ?></p><p>MSSV: <?= e(STUDENT_ID) ?></p></div><div><strong>Hóa đơn #<?= $id ?></strong><p>Ngày đặt: <?= e(date('d/m/Y H:i',strtotime($order['created_at']))) ?></p><p>Trạng thái: <?= e(order_status_label($order['status'])) ?></p></div></div>
<h3>Thông tin khách hàng</h3>
<p>Người nhận: <?= e($order['fullname']) ?></p>
<p>Điện thoại: <?= e($order['phone']) ?></p>
<p>Địa chỉ: <?= e($order['address']) ?></p>
<div class="table-scroll"><table class="cart-table"><thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= format_price($item['price']) ?></td><td><?= (int)$item['quantity'] ?></td><td><?= format_price((int)$item['price']*(int)$item['quantity']) ?></td></tr><?php endforeach; ?>
</tbody><tfoot><tr><th colspan="3">TỔNG CỘNG</th><th><?= format_price($order['total']) ?></th></tr></tfoot></table></div>
<p class="muted">Bản in hóa đơn mẫu phục vụ đồ án môn học. Chưa phải hóa đơn thuế.</p>
<div class="no-print"><button type="button" id="print-invoice">In / lưu PDF</button> <a class="btn secondary-button" href="<?= e(url(is_admin()?'admin/orders.php':'orders.php')) ?>">Quay lại đơn hàng</a></div>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
