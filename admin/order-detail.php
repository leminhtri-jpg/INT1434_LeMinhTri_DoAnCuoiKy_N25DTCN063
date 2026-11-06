<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$stmt = db()->prepare(
    'SELECT o.id, o.fullname, o.phone, o.address, o.note,
            o.total, o.status, o.created_at, u.email
     FROM orders o
     JOIN users u ON u.id=o.user_id
     WHERE o.id=?'
);
$stmt->execute([$id ?: 0]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    $pageTitle='Không tìm thấy đơn hàng'; $activePage='admin/orders.php';
    require __DIR__.'/../includes/header.php';
    echo '<section class="empty-message"><h2>Không tìm thấy đơn hàng</h2></section>';
    require __DIR__.'/../includes/footer.php';
    exit;
}
$stmt = db()->prepare(
    'SELECT product_name, price, quantity FROM order_items WHERE order_id=? ORDER BY id'
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();
$pageTitle = 'Chi tiết đơn #' . $id;
$activePage = 'admin/orders.php';
require __DIR__ . '/../includes/header.php';
?>
<section>
    <h2>Chi tiết đơn hàng #<?= (int)$order['id'] ?></h2>
    <p><strong>Khách hàng:</strong> <?= e($order['fullname']) ?></p>
    <p><strong>Email:</strong> <?= e($order['email']) ?></p>
    <p><strong>Số điện thoại:</strong> <?= e($order['phone']) ?></p>
    <p><strong>Địa chỉ:</strong> <?= e($order['address']) ?></p>
    <p><strong>Ghi chú:</strong> <?= nl2br(e($order['note'] ?? '')) ?></p>
    <p><strong>Ngày đặt:</strong> <?= e($order['created_at']) ?></p>
    <p><strong>Trạng thái:</strong> <?= e(order_status_label($order['status'])) ?></p>
    <div class="table-scroll">
        <table class="cart-table">
            <thead><tr><th>Sản phẩm</th><th>Giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= format_price($item['price']) ?></td>
                    <td><?= (int)$item['quantity'] ?></td>
                    <td><?= format_price((int)$item['price'] * (int)$item['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3">Tổng</th><th><?= format_price($order['total']) ?></th></tr></tfoot>
        </table>
    </div>
    <p><a class="btn" href="<?= e(url('admin/orders.php')) ?>">Quay lại quản lý đơn</a></p>
<p class="no-print"><a class="btn" href="<?= e(url('invoice.php?id=' . (int)$order['id'])) ?>">In hóa đơn</a></p>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
