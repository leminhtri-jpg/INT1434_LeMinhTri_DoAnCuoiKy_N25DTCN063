<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$pdo = db();

// LAB 5.3 - Chỉ cho phép chuyển trạng thái hợp lệ.
$transitions = [
    'pending' => ['shipping', 'cancelled'],
    'shipping' => ['completed'],
    'completed' => [],
    'cancelled' => [],
];

if (is_post()) {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $newStatus = (string)($_POST['status'] ?? '');

    if (!$id || $id < 1 || !isset(order_statuses()[$newStatus])) {
        set_flash('error', 'Mã đơn hàng hoặc trạng thái không hợp lệ.');
        redirect('admin/orders.php');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id=? FOR UPDATE');
        $stmt->execute([$id]);
        $currentStatus = $stmt->fetchColumn();
        $stmt->closeCursor();

        if ($currentStatus === false ||
            !in_array($newStatus, $transitions[$currentStatus] ?? [], true)) {
            throw new RuntimeException('Không thể chuyển sang trạng thái này.');
        }

        // Nếu admin hủy đơn đang chờ: hoàn tồn kho cùng giao dịch.
        if ($newStatus === 'cancelled') {
            $stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=?');
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();
            $restore = $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');
            foreach ($items as $item) {
                $restore->execute([(int)$item['quantity'], (int)$item['product_id']]);
                if ($restore->rowCount() !== 1) {
                    throw new RuntimeException('Không thể hoàn kho cho sản phẩm.');
                }
            }
        }

        $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
        $stmt->execute([$newStatus, $id]);
        $pdo->commit();
        set_flash('success', 'Đã cập nhật đơn #' . $id . ' thành ' . order_status_label($newStatus) . '.');
    } catch (RuntimeException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', $exception->getMessage());
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Admin order update: ' . $exception->getMessage());
        set_flash('error', 'Không thể cập nhật đơn hàng.');
    }
    redirect('admin/orders.php');
}

$statusFilter = (string)($_GET['status'] ?? '');
if ($statusFilter !== '' && !array_key_exists($statusFilter, order_statuses())) {
    $statusFilter = '';
}
if ($statusFilter !== '') {
    $stmt = $pdo->prepare('SELECT id, fullname, total, status, created_at FROM orders WHERE status = ? ORDER BY id DESC');
    $stmt->execute([$statusFilter]);
} else {
    $stmt = $pdo->query('SELECT id, fullname, total, status, created_at FROM orders ORDER BY id DESC');
}
$orders = $stmt->fetchAll();

$pageTitle = 'Quản lý đơn hàng';
$activePage = 'admin/orders.php';
require __DIR__ . '/../includes/header.php';
?>
<section class="admin-page">
    <h2>Quản lý đơn hàng</h2>
<div class="admin-panel-links"><a class="btn secondary-button" href="<?= e(url('admin/dashboard.php')) ?>">Tổng quan</a><a class="btn secondary-button" href="<?= e(url('admin/categories.php')) ?>">Danh mục</a></div>
    <div class="admin-toolbar">
        <a class="btn secondary-button" href="<?= e(url('admin/products.php')) ?>">Quản lý sản phẩm</a>
    </div>
    <nav class="category-bar" aria-label="Lọc trạng thái đơn">
        <a class="<?= $statusFilter === '' ? 'active' : '' ?>" href="<?= e(url('admin/orders.php')) ?>">Tất cả</a>
        <?php foreach (order_statuses() as $code => $label): ?>
            <a class="<?= $statusFilter === $code ? 'active' : '' ?>"
               href="<?= e(url('admin/orders.php?' . http_build_query(['status' => $code]))) ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="table-scroll">
        <table class="cart-table admin-table">
            <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Người nhận</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <?php $available = $transitions[$order['status']] ?? []; ?>
                <tr>
                    <td><a href="<?= e(url('admin/order-detail.php?id=' . (int)$order['id'])) ?>">#<?= (int)$order['id'] ?></a></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                    <td><?= e($order['fullname']) ?></td>
                    <td><?= format_price($order['total']) ?></td>
                    <td><?= e(order_status_label($order['status'])) ?></td>
                    <td>
                        <?php if ($available): ?>
                            <form method="post" action="<?= e(url('admin/orders.php')) ?>" class="admin-actions"
                                  data-confirm="Bạn xác nhận đổi trạng thái đơn hàng?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <select name="status" aria-label="Trạng thái mới cho đơn <?= (int)$order['id'] ?>">
                                    <?php foreach ($available as $value): ?>
                                        <option value="<?= e($value) ?>"><?= e(order_status_label($value)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">Lưu</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">Đã kết thúc</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="6">Chưa có đơn hàng.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
