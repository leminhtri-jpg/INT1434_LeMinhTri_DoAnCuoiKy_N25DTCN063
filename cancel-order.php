<?php
require __DIR__ . '/includes/init.php';
require_login();
if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Chỉ chấp nhận POST.');
}
verify_csrf();
$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
if (!$orderId || $orderId < 1) {
    set_flash('error', 'Mã đơn hàng không hợp lệ.');
    redirect('orders.php');
}
$pdo = db();
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
    $stmt->execute([$orderId, (int)current_user()['id']]);
    $order = $stmt->fetch();
    $stmt->closeCursor();
    if (!$order || $order['status'] !== 'pending') {
        throw new RuntimeException('Chỉ hủy được đơn hàng của bạn đang ở trạng thái Chờ xử lý.');
    }
    $stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    $addStock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
    foreach ($items as $item) {
        $addStock->execute([(int)$item['quantity'], (int)$item['product_id']]);
    }
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$orderId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Đơn hàng không thể hủy.');
    }
    $pdo->commit();
    set_flash('success', 'Đã hủy đơn hàng #' . $orderId . ' và hoàn lại số lượng tồn kho.');
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    set_flash('error', $exception->getMessage());
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Cancel order failed: ' . $exception->getMessage());
    set_flash('error', 'Không thể hủy đơn hàng lúc này.');
}
redirect('order-detail.php?id=' . $orderId);
