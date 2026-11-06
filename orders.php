<?php
require __DIR__ . '/includes/init.php';
require_login();
$stmt = db()->prepare(
    'SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC'
);
$stmt->execute([(int)current_user()['id']]);
$orders = $stmt->fetchAll();
$pageTitle = 'Đơn hàng của tôi';
$activePage = 'orders.php';
require __DIR__ . '/includes/header.php';
?>
<section>
    <h2>Lịch sử đơn hàng</h2>
    <?php if (!$orders): ?>
        <p class="empty-message">Bạn chưa có đơn hàng nào. <a href="<?= e(url('index.php')) ?>">Xem sản phẩm</a></p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="cart-table">
                <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th>Chi tiết</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= (int)$order['id'] ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                            <td><?= format_price($order['total']) ?></td>
                            <td><?= e(order_status_label($order['status'])) ?></td>
                            <td><a href="<?= e(url('order-detail.php?id=' . (int)$order['id'])) ?>">Xem</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
