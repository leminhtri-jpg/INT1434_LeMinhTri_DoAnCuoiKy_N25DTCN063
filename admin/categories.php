<?php
require __DIR__.'/../includes/init.php';
require_admin();
$pdo=db();
$errors=[];
$action=(string)($_POST['action']??'');
if(is_post()) {
    // init.php đã kiểm CSRF, đồng thời kiểm lại ở đây để dễ đọc bài.
    verify_csrf();
    $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT) ?: 0;
    $name=trim((string)($_POST['name']??''));
    if($action==='add'||$action==='edit') {
        if(mb_strlen($name)<2 || mb_strlen($name)>100) $errors[]='Tên danh mục phải từ 2 đến 100 ký tự.';
    }
    if(($action==='edit'||$action==='delete') && $id<1) $errors[]='Mã danh mục không hợp lệ.';
    if(!$errors) {
        try {
            if($action==='add') {
                $stmt=$pdo->prepare('INSERT INTO categories (name) VALUES (?)');
                $stmt->execute([$name]);
                set_flash('success','Đã thêm danh mục.');
            } elseif($action==='edit') {
                $stmt=$pdo->prepare('UPDATE categories SET name=? WHERE id=?');
                $stmt->execute([$name,$id]);
                set_flash('success','Đã cập nhật danh mục.');
            } elseif($action==='delete') {
                $stmt=$pdo->prepare('DELETE FROM categories WHERE id=?');
                $stmt->execute([$id]);
                set_flash($stmt->rowCount()?'success':'error',$stmt->rowCount()?'Đã xóa danh mục.':'Danh mục không tồn tại.');
            } else { $errors[]='Thao tác không hợp lệ.'; }
        } catch(PDOException $exception) {
            if((string)$exception->getCode()==='23000') $errors[]='Danh mục đã có sản phẩm hoặc tên bị trùng; không thể xóa/thêm/sửa như yêu cầu.';
            else { error_log($exception->getMessage()); $errors[]='Không xử lý được danh mục.'; }
        }
    }
    if(!$errors) redirect('admin/categories.php');
}
$categories=$pdo->query('SELECT c.id,c.name,COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id,c.name ORDER BY c.id')->fetchAll();
$pageTitle='Quản lý danh mục';$activePage='admin/categories.php';
require __DIR__.'/../includes/header.php';
?>
<section class="admin-page">
<h2>Quản lý danh mục</h2>
<div class="admin-panel-links">
  <a class="btn secondary-button" href="<?= e(url('admin/dashboard.php')) ?>">Tổng quan</a>
  <a class="btn secondary-button" href="<?= e(url('admin/products.php')) ?>">Sản phẩm</a>
</div>
<?php foreach($errors as $error): ?><p role="alert" class="alert alert-error"><?= e($error) ?></p><?php endforeach; ?>
<form class="admin-filter" method="post" action="<?= e(url('admin/categories.php')) ?>">
<?= csrf_field() ?><input type="hidden" name="action" value="add">
<label for="new-name">Thêm danh mục</label>
<input id="new-name" name="name" minlength="2" maxlength="100" required placeholder="Ví dụ: Thiết bị bàn học">
<button type="submit">Thêm danh mục</button>
</form>
<div class="table-scroll"><table class="cart-table"><thead><tr><th>Mã</th><th>Tên danh mục</th><th>Số sản phẩm</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach($categories as $cat): ?>
<tr>
<td><?= (int)$cat['id'] ?></td>
<td><form id="edit-cat-<?= (int)$cat['id'] ?>" class="admin-filter" method="post" action="<?= e(url('admin/categories.php')) ?>">
<?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
<label class="sr-only" for="cat-<?= (int)$cat['id'] ?>">Tên danh mục</label>
<input id="cat-<?= (int)$cat['id'] ?>" type="text" name="name" minlength="2" maxlength="100" required value="<?= e($cat['name']) ?>"></form></td>
<td><?= (int)$cat['product_count'] ?></td>
<td><div class="admin-actions"><button type="submit" form="edit-cat-<?= (int)$cat['id'] ?>">Lưu tên</button>
<form method="post" action="<?= e(url('admin/categories.php')) ?>" data-confirm="Bạn chắc muốn xóa danh mục này?">
<?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
<button type="submit" class="danger-button" <?= (int)$cat['product_count']>0?'disabled':'' ?>>Xóa</button>
</form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<p class="muted">Danh mục có sản phẩm sẽ không xóa được.</p>
</section>
<?php require __DIR__.'/../includes/footer.php'; ?>
