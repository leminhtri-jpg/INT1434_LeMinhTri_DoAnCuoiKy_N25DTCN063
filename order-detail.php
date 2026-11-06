<?php
require __DIR__ . '/includes/init.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = db()->prepare('SELECT id, user_id, fullname, phone, address, note, total, status, created_at FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$id ?: 0, (int)current_user()['id']]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy đơn hàng';
    $activePage = 'orders.php';
    require __DIR__ . '/includes/header.php';
    echo '<h2>Không tìm thấy đơn hàng</h2><p>Đơn hàng không tồn tại hoặc không thuộc tài khoản của bạn.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$stmt = db()->prepare('SELECT product_name, price, quantity FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
$pageTitle = 'Đơn hàng #' . $id;
$activePage = 'orders.php';
require __DIR__ . '/includes/header.php';
?>
<section>
    <h2>Đơn hàng #<?= (int)$order['id'] ?></h2>
    <p><strong>Ngày đặt:</strong> <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
    <p><strong>Trạng thái:</strong> <span class="status status-<?= e($order['status']) ?>"><?= e(order_status_label($order['status'])) ?></span></p>
    <p><strong>Người nhận:</strong> <?= e($order['fullname']) ?></p>
    <p><strong>Số điện thoại:</strong> <?= e($order['phone']) ?></p>
    <p><strong>Địa chỉ:</strong> <?= e($order['address']) ?></p>
    <?php if ($order['note'] !== ''): ?><p><strong>Ghi chú:</strong> <?= nl2br(e($order['note'])) ?></p><?php endif; ?>
    <h3>Danh sách sản phẩm</h3>
    <div class="table-scroll">
        <table class="cart-table">
            <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
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
            <tfoot><tr><th colspan="3">Tổng cộng</th><th><?= format_price($order['total']) ?></th></tr></tfoot>
        </table>
    </div>
    <?php if ($order['status'] === 'pending'): ?>
        <form method="post" action="<?= e(url('cancel-order.php')) ?>" class="inline-form"
              data-confirm="Bạn có chắc muốn hủy đơn hàng này?">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <button type="submit" class="danger-button">Hủy đơn hàng</button>
        </form>
    <?php endif; ?>
    <p><a href="<?= e(url('orders.php')) ?>">Quay lại lịch sử đơn hàng</a></p>
<p class="no-print"><a class="btn" href="<?= e(url('invoice.php?id=' . (int)$order['id'])) ?>">In hóa đơn</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
