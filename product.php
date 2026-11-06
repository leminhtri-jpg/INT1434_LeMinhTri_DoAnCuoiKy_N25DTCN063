<?php
require __DIR__ . '/includes/init.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = false;
if ($id && $id > 0) {
    $stmt = db()->prepare(
        'SELECT p.id, p.name, p.price, p.description, p.image, p.stock, p.category_id,
                c.name AS category_name
         FROM products p INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm';
    $activePage = 'product.php';
    require __DIR__ . '/includes/header.php';
    echo '<h2>Không tìm thấy sản phẩm</h2>';
    echo '<p>Đường dẫn không hợp lệ hoặc sản phẩm đã bị xóa.</p>';
    echo '<p><a class="btn" href="' . e(url('index.php')) . '">Quay về trang chủ</a></p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$relatedStmt = db()->prepare(
    'SELECT id, name, price, image FROM products
     WHERE category_id = ? AND id <> ? ORDER BY id LIMIT 3'
);
$relatedStmt->execute([$product['category_id'], $product['id']]);
$related = $relatedStmt->fetchAll();

$pageTitle = $product['name'];
$activePage = 'product.php';
require __DIR__ . '/includes/header.php';
?>
<article class="detail-page">
    <h2>Chi tiết sản phẩm</h2>
    <div class="product-detail">
        <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
        <div class="product-info">
            <h3><?= e($product['name']) ?></h3>
            <p class="price"><?= format_price($product['price']) ?></p>
            <p><?= nl2br(e($product['description'])) ?></p>
            <p>Tình trạng: <strong><?= (int)$product['stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?></strong></p>
            <form method="post" action="<?= e(url('cart-add.php')) ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>"
                       aria-label="Số lượng cần mua" class="detail-quantity" required>
                <button type="submit" class="add-to-cart"
                    <?= (int)$product['stock'] === 0 ? 'disabled' : '' ?>>Thêm vào giỏ</button>
            </form>
        </div>
    </div>
    <h3>Thông số sản phẩm</h3>
    <table class="spec-table">
        <tbody>
            <tr><th>Tên sản phẩm</th><td><?= e($product['name']) ?></td></tr>
            <tr><th>Mã sản phẩm</th><td>SP<?= str_pad((string)$product['id'], 2, '0', STR_PAD_LEFT) ?></td></tr>
            <tr><th>Danh mục</th><td><?= e($product['category_name']) ?></td></tr>
            <tr><th>Giá</th><td><?= format_price($product['price']) ?></td></tr>
            <tr><th>Tồn kho</th><td><?= (int)$product['stock'] ?></td></tr>
        </tbody>
    </table>
    <h3>Sản phẩm cùng danh mục</h3>
    <?php if (!$related): ?>
        <p>Chưa có sản phẩm cùng danh mục.</p>
    <?php else: ?>
        <div class="related-list">
            <?php foreach ($related as $item): ?>
                <a class="related-card" href="<?= e(url('product.php?id=' . (int)$item['id'])) ?>">
                    <img src="<?= e(image_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                    <strong><?= e($item['name']) ?></strong>
                    <span><?= format_price($item['price']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p><a class="btn" href="<?= e(url('index.php')) ?>">Quay lại danh sách</a></p>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
