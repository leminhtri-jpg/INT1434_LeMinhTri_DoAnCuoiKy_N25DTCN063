<?php
require __DIR__ . '/includes/init.php';
require_login();

[$items, $total] = get_cart_items();
if (!$items) {
    set_flash('error', 'Giỏ hàng trống, hãy chọn sản phẩm trước khi đặt hàng.');
    redirect('cart.php');
}

$stmt = db()->prepare('SELECT fullname, phone, address FROM users WHERE id = ?');
$stmt->execute([(int)current_user()['id']]);
$account = $stmt->fetch();
if (!$account) {
    unset($_SESSION['user']);
    redirect('login.php');
}

$old = [
    'fullname' => (string)$account['fullname'],
    'phone' => (string)($account['phone'] ?? ''),
    'address' => (string)($account['address'] ?? ''),
    'note' => '',
];
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach ($old as $key => $_) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }
    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) {
        $errors['fullname'] = 'Họ tên phải có từ 2 đến 100 ký tự.';
    }
    if (!valid_phone($old['phone'])) {
        $errors['phone'] = 'Số điện thoại phải có 9–11 chữ số.';
    }
    if (mb_strlen($old['address']) < 5 || mb_strlen($old['address']) > 255) {
        $errors['address'] = 'Địa chỉ phải có từ 5 đến 255 ký tự.';
    }
    if (mb_strlen($old['note']) > 500) {
        $errors['note'] = 'Ghi chú không vượt quá 500 ký tự.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $checked = [];
            $serverTotal = 0;
            $cart = $_SESSION['cart'] ?? [];
            // Khóa hàng theo ID để tránh đặt vượt tồn kho khi nhiều người mua.
            ksort($cart, SORT_NUMERIC);
            $lock = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE');
            foreach ($cart as $id => $quantity) {
                $quantity = (int)$quantity;
                $lock->execute([(int)$id]);
                $product = $lock->fetch();
                $lock->closeCursor();
                if (!$product || $quantity < 1 || $quantity > (int)$product['stock']) {
                    throw new RuntimeException('Một sản phẩm đã hết hàng hoặc không đủ số lượng. Hãy cập nhật giỏ hàng.');
                }
                $checked[] = ['product' => $product, 'quantity' => $quantity];
                $serverTotal += (int)$product['price'] * $quantity;
            }
            if (!$checked) {
                throw new RuntimeException('Giỏ hàng trống.');
            }

            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (user_id, fullname, phone, address, note, total) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insertOrder->execute([
                (int)current_user()['id'], $old['fullname'], $old['phone'],
                $old['address'], $old['note'], $serverTotal,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)'
            );
            $updateStock = $pdo->prepare(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );
            foreach ($checked as $entry) {
                $product = $entry['product'];
                $quantity = $entry['quantity'];
                $insertItem->execute([
                    $orderId, (int)$product['id'], $product['name'],
                    (int)$product['price'], $quantity,
                ]);
                $updateStock->execute([$quantity, (int)$product['id'], $quantity]);
                if ($updateStock->rowCount() !== 1) {
                    throw new RuntimeException('Không đủ hàng để hoàn thành đơn.');
                }
            }
            $pdo->commit();
            unset($_SESSION['cart']);
            set_flash('success', 'Đặt hàng thành công! Mã đơn hàng: #' . $orderId);
            redirect('order-detail.php?id=' . $orderId);
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['order'] = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Checkout failed: ' . $exception->getMessage());
            $errors['order'] = 'Không thể đặt hàng. Hãy kiểm tra cấu trúc database và thử lại.';
        }
    }
}

$pageTitle = 'Đặt hàng';
$activePage = 'cart.php';
require __DIR__ . '/includes/header.php';
?>
<section class="checkout-page">
    <h2>Xác nhận đặt hàng</h2>
    <?php if (isset($errors['order'])): ?><p class="alert alert-error" role="alert"><?= e($errors['order']) ?></p><?php endif; ?>
    <div class="table-scroll">
        <table class="cart-table">
            <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr><td><?= e($item['name']) ?></td><td><?= format_price($item['price']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td><td><?= format_price($item['line_total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3">Tổng cộng</th><th><?= format_price($total) ?></th></tr></tfoot>
        </table>
    </div>
    <p class="muted center-note">Giá và tồn kho sẽ được kiểm tra lại khi bạn nhấn Đặt hàng.</p>
    <h3>Thông tin nhận hàng</h3>
    <form class="form-card" method="post" action="<?= e(url('checkout.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach (['fullname' => 'Họ và tên', 'phone' => 'Số điện thoại', 'address' => 'Địa chỉ nhận hàng'] as $key => $label): ?>
            <label for="<?= e($key) ?>"><?= e($label) ?></label>
            <?php if ($key === 'address'): ?>
                <textarea id="address" name="address" required minlength="5" maxlength="255" rows="3"><?= e($old['address']) ?></textarea>
            <?php else: ?>
                <input id="<?= e($key) ?>" name="<?= e($key) ?>" type="<?= $key === 'phone' ? 'tel' : 'text' ?>"
                       value="<?= e($old[$key]) ?>" <?= $key === 'phone' ? 'pattern="[0-9]{9,11}"' : '' ?> required>
            <?php endif; ?>
            <?php if (isset($errors[$key])): ?><span class="form-error"><?= e($errors[$key]) ?></span><?php endif; ?>
        <?php endforeach; ?>
        <label for="note">Ghi chú (không bắt buộc)</label>
        <textarea id="note" name="note" rows="2" maxlength="500"><?= e($old['note']) ?></textarea>
        <?php if (isset($errors['note'])): ?><span class="form-error"><?= e($errors['note']) ?></span><?php endif; ?>
        <button type="submit" class="add-to-cart">Đặt hàng</button>
        <p><a href="<?= e(url('cart.php')) ?>">Quay lại giỏ hàng</a></p>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
