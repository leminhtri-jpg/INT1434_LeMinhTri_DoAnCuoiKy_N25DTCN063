<?php
require __DIR__.'/../includes/init.php';require_admin();
$pdo=db();
$totals=$pdo->query('SELECT status,COUNT(*) AS n FROM orders GROUP BY status')->fetchAll();
$counts=array_fill_keys(array_keys(order_statuses()),0);
foreach($totals as $row) $counts[$row['status']]=(int)$row['n'];
$revenue=(int)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed'")->fetchColumn();
$products=(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$categories=(int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$lowStock=$pdo->query('SELECT id,name,stock,price FROM products ORDER BY stock ASC,id ASC LIMIT 5')->fetchAll();
$pageTitle='Tổng quan quản trị';$activePage='admin/dashboard.php';
require __DIR__.'/../includes/header.php';
?>
<section class="admin-page">
<p class="eyebrow">TRANG DÀNH CHO QUẢN TRỊ VIÊN</p>
<h2>Tổng quan hoạt động cửa hàng</h2>
<div class="admin-stats">
<article class="stat-card"><span>Tổng sản phẩm</span><strong><?= $products ?></strong></article>
<article class="stat-card"><span>Danh mục</span><strong><?= $categories ?></strong></article>
<article class="stat-card"><span>Đơn hoàn thành</span><strong><?= $counts['completed'] ?></strong></article>
<article class="stat-card"><span>Doanh thu đơn hoàn thành</span><strong><?= format_price($revenue) ?></strong></article>
</div>
<div class="admin-panel-links">
<a class="btn" href="<?= e(url('admin/products.php')) ?>">Quản lý sản phẩm</a>
<a class="btn" href="<?= e(url('admin/categories.php')) ?>">Quản lý danh mục</a>
<a class="btn" href="<?= e(url('admin/orders.php')) ?>">Quản lý đơn hàng</a>
</div>
<h3>Số đơn theo trạng thái</h3>
<div class="admin-stats">
<?php foreach(order_statuses() as $key=>$label): ?>
<article class="stat-card"><span><?= e($label) ?></span><strong><?= $counts[$key] ?></strong></article>
<?php endforeach; ?>
</div>
<h3>Năm sản phẩm sắp hết hàng</h3>
<div class="table-scroll"><table class="cart-table"><thead><tr><th>Mã</th><th>Tên sản phẩm</th><th>Tồn kho</th><th>Giá</th><th>Quản lý</th></tr></thead><tbody>
<?php foreach($lowStock as $product): ?><tr>
<td><?= (int)$product['id'] ?></td><td><?= e($product['name']) ?></td><td><?= (int)$product['stock'] ?></td><td><?= format_price($product['price']) ?></td>
<td><a href="<?= e(url('admin/product-form.php?id='.(int)$product['id'])) ?>">Sửa tồn kho</a></td>
</tr><?php endforeach; ?></tbody></table></div>
</section>
<?php require __DIR__.'/../includes/footer.php'; ?>
