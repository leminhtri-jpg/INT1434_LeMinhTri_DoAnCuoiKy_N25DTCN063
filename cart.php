<?php
require __DIR__ . '/includes/init.php';
[$items, $total] = get_cart_items();
$pageTitle = 'Giỏ hàng';
$activePage = 'cart.php';
require __DIR__ . '/includes/header.php';
?>
<section class="cart-page">
    <h2>Giỏ hàng của bạn</h2>
    <?php if (!$items): ?>
        <p class="empty-message">Giỏ hàng đang trống. <a href="<?= e(url('index.php')) ?>">Xem sản phẩm</a></p>
    <?php else: ?>
        <form id="cart-update-form" method="post" action="<?= e(url('cart-update.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="update">
        </form>
        <div class="table-scroll">
            <table class="cart-table">
                <thead><tr><th>STT</th><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                        <tr data-price="<?= (int)$item['price'] ?>">
                            <td><?= $index + 1 ?></td>
                            <td><?= e($item['name']) ?></td>
                            <td><?= format_price($item['price']) ?></td>
                            <td>
                                <input class="qty" type="number" min="0" step="1" max="<?= (int)$item['stock'] ?>"
                                       name="qty[<?= (int)$item['id'] ?>]" form="cart-update-form"
                                       value="<?= (int)$item['quantity'] ?>"
                                       aria-label="Số lượng <?= e($item['name']) ?>">
                            </td>
                            <td class="line-total"><?= format_price($item['line_total']) ?></td>
                            <td>
                                <form method="post" action="<?= e(url('cart-update.php')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                                    <button class="remove-item" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <h3 id="cart-total">Tổng tiền: <?= format_price($total) ?></h3>
        <p class="muted center-note">Thay đổi số lượng rồi nhấn “Cập nhật giỏ hàng” để lưu trên máy chủ. Nhập 0 để xóa sản phẩm.</p>
        <div class="cart-actions">
            <button type="submit" form="cart-update-form">Cập nhật giỏ hàng</button>
            <form method="post" action="<?= e(url('cart-update.php')) ?>" class="inline-form" data-confirm="Bạn có chắc muốn xóa toàn bộ giỏ hàng?">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="danger-button">Xóa giỏ hàng</button>
            </form>
            <a class="btn" href="<?= e(url('checkout.php')) ?>">Đặt hàng</a>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
