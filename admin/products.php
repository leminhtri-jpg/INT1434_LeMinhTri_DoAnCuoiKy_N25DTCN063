<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$q = trim((string)($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 10;

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :search)';
    $params[':search'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category';
    $params[':category'] = $categoryId;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM products p' . $whereSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT p.id, p.name, p.price, p.stock, p.image, c.name AS category_name
        FROM products p JOIN categories c ON c.id = p.category_id'
        . $whereSql . ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

function admin_product_page_link(int $page, string $q, int $categoryId): string
{
    return url('admin/products.php?' . http_build_query([
        'page' => $page, 'q' => $q, 'category' => $categoryId,
    ]));
}

$pageTitle = 'Quản lý sản phẩm';
$activePage = 'admin/products.php';
require __DIR__ . '/../includes/header.php';
?>
<section class="admin-page">
    <h2>Quản lý sản phẩm</h2>
<div class="admin-panel-links"><a class="btn secondary-button" href="<?= e(url('admin/dashboard.php')) ?>">Tổng quan</a><a class="btn secondary-button" href="<?= e(url('admin/categories.php')) ?>">Danh mục</a></div>
    <div class="admin-toolbar">
        <a class="btn" href="<?= e(url('admin/product-form.php')) ?>">+ Thêm sản phẩm</a>
        <a class="btn secondary-button" href="<?= e(url('admin/orders.php')) ?>">Quản lý đơn hàng</a>
    </div>
    <form method="get" action="<?= e(url('admin/products.php')) ?>" class="admin-filter">
        <input type="search" name="q" placeholder="Tìm tên sản phẩm" value="<?= e($q) ?>">
        <select name="category" aria-label="Lọc danh mục">
            <option value="0">Tất cả danh mục</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int)$category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Lọc</button>
        <a href="<?= e(url('admin/products.php')) ?>">Đặt lại</a>
    </form>
    <p>Tìm thấy <?= $totalRows ?> sản phẩm.</p>
    <div class="table-scroll">
        <table class="cart-table admin-table">
            <thead><tr><th>ID</th><th>Ảnh</th><th>Tên</th><th>Danh mục</th><th>Giá</th><th>Tồn kho</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= (int)$product['id'] ?></td>
                    <td><img class="thumb" src="<?= e(image_url($product['image'])) ?>" alt=""></td>
                    <td><?= e($product['name']) ?></td>
                    <td><?= e($product['category_name']) ?></td>
                    <td><?= format_price($product['price']) ?></td>
                    <td><?= (int)$product['stock'] ?></td>
                    <td>
                        <div class="admin-actions">
                            <a class="btn" href="<?= e(url('admin/product-form.php?id=' . (int)$product['id'])) ?>">Sửa</a>
                            <form method="post" action="<?= e(url('admin/product-delete.php')) ?>"
                                  data-confirm="Xóa sản phẩm này? Không thể khôi phục.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                <button type="submit" class="danger-button">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr><td colspan="7">Không có sản phẩm phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav class="pagination" aria-label="Phân trang sản phẩm">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= e(admin_product_page_link($i, $q, $categoryId)) ?>"
               class="<?= $i === $page ? 'active' : '' ?>"
               <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
    </nav>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
