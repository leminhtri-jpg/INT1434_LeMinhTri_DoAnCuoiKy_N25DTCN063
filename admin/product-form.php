<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$editing = $id > 0;
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$allowedCategoryIds = array_map('intval', array_column($categories, 'id'));

$previousImage = '';
$product = [
    'category_id' => 0,
    'name' => '',
    'price' => '',
    'stock' => '0',
    'description' => '',
    'image' => '',
];

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        $pageTitle='Không tìm thấy sản phẩm'; $activePage='admin/products.php';
        require __DIR__.'/../includes/header.php';
        echo '<section class="empty-message"><h2>Không tìm thấy sản phẩm cần sửa</h2></section>';
        require __DIR__.'/../includes/footer.php';
        exit;
    }
    $product = $found;
    $previousImage = (string)$found['image'];
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $product['name'] = trim((string)($_POST['name'] ?? ''));
    $product['description'] = trim((string)($_POST['description'] ?? ''));
    $product['category_id'] = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: 0;
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_INT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $product['price'] = (string)($_POST['price'] ?? '');
    $product['stock'] = (string)($_POST['stock'] ?? '');

    if (mb_strlen($product['name']) < 2 || mb_strlen($product['name']) > 100) {
        $errors[] = 'Tên sản phẩm cần từ 2 đến 100 ký tự.';
    }
    if (!in_array((int)$product['category_id'], $allowedCategoryIds, true)) {
        $errors[] = 'Hãy chọn danh mục hợp lệ.';
    }
    if ($price === false || $price === null || $price < 1000 || $price > 1000000000) {
        $errors[] = 'Giá phải là số nguyên từ 1.000 đến 1.000.000.000.';
    }
    if ($stock === false || $stock === null || $stock < 0 || $stock > 1000000) {
        $errors[] = 'Tồn kho phải là số nguyên từ 0 đến 1.000.000.';
    }
    if (mb_strlen($product['description']) > 5000) {
        $errors[] = 'Mô tả tối đa 5.000 ký tự.';
    }

    $hasNewFile = isset($_FILES['image']) &&
        (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!$editing && !$hasNewFile) {
        $errors[] = 'Khi thêm sản phẩm phải chọn một ảnh.';
    }

    if (!$errors) {
        $newUploadedPath = null;
        try {
            if ($hasNewFile) {
                $newUploadedPath = upload_image($_FILES['image']);
                $product['image'] = $newUploadedPath;
            }

            if ($editing) {
                $stmt = $pdo->prepare(
                    'UPDATE products SET category_id=?, name=?, price=?, stock=?, description=?, image=? WHERE id=?'
                );
                $stmt->execute([
                    (int)$product['category_id'], $product['name'], $price, $stock,
                    $product['description'], $product['image'], $id,
                ]);
                // D7: chỉ xóa ảnh upload cũ sau khi cập nhật MySQL thành công.
                if ($newUploadedPath !== null && $previousImage !== $newUploadedPath) {
                    delete_old_uploaded_image($previousImage);
                }
                set_flash('success', 'Đã cập nhật sản phẩm #' . $id . '.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (category_id, name, price, stock, description, image)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    (int)$product['category_id'], $product['name'], $price, $stock,
                    $product['description'], $product['image'],
                ]);
                set_flash('success', 'Đã thêm sản phẩm mới.');
            }
            redirect('admin/products.php');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log('Product form error: ' . $exception->getMessage());
            $errors[] = 'Lỗi lưu sản phẩm. Hãy kiểm tra dữ liệu và thử lại.';
        }
        // Nếu SQL lỗi sau khi đã upload, xóa file mới tránh rác.
        if ($newUploadedPath !== null) {
            @unlink(dirname(__DIR__) . '/' . $newUploadedPath);
            if ($editing) {
                // Khôi phục đường dẫn ảnh trước đó để hiển thị.
                $stmt = $pdo->prepare('SELECT image FROM products WHERE id=?');
                $stmt->execute([$id]);
                $product['image'] = (string)$stmt->fetchColumn();
            } else {
                $product['image'] = '';
            }
        }
    }
}

$pageTitle = $editing ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
$activePage = 'admin/products.php';
require __DIR__ . '/../includes/header.php';
?>
<section class="form-page">
    <h2><?= e($pageTitle) ?></h2>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form-card"
          action="<?= e(url('admin/product-form.php' . ($editing ? '?id=' . $id : ''))) ?>">
        <?= csrf_field() ?>
        <label for="name">Tên sản phẩm</label>
        <input type="text" id="name" name="name" required minlength="2" maxlength="100"
               value="<?= e($product['name']) ?>">

        <label for="category_id">Danh mục</label>
        <select id="category_id" name="category_id" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int)$category['id'] ?>"
                    <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="price">Giá (đ)</label>
        <input type="number" id="price" name="price" min="1000" max="1000000000" step="1" required
               value="<?= e($product['price']) ?>">

        <label for="stock">Tồn kho</label>
        <input type="number" id="stock" name="stock" min="0" max="1000000" step="1" required
               value="<?= e($product['stock']) ?>">

        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="5" maxlength="5000"><?= e($product['description']) ?></textarea>

        <label for="image">Ảnh sản phẩm (JPG, PNG, WEBP; tối đa 2 MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
               <?= $editing ? '' : 'required' ?>>
        <?php if ($editing && $product['image']): ?>
            <p>Ảnh hiện tại:</p>
            <img class="preview-image" src="<?= e(image_url($product['image'])) ?>" alt="Ảnh hiện tại">
            <p class="muted">Để trống ô ảnh nếu không muốn thay.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit"><?= $editing ? 'Lưu thay đổi' : 'Thêm sản phẩm' ?></button>
            <a class="btn secondary-button" href="<?= e(url('admin/products.php')) ?>">Quay lại</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
