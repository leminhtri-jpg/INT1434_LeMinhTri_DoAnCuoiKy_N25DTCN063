<?php
require __DIR__.'/includes/init.php';
$pdo=db();
$categoryId=filter_input(INPUT_GET,'category',FILTER_VALIDATE_INT) ?: 0;
$keyword=trim((string)($_GET['q']??''));
if (mb_strlen($keyword)>100) $keyword=mb_substr($keyword,0,100);
$sort=(string)($_GET['sort']??'default');
$sortSql=['default'=>'p.id ASC','price_asc'=>'p.price ASC, p.id ASC','price_desc'=>'p.price DESC, p.id ASC'];
if (!isset($sortSql[$sort])) $sort='default';
$page=max(1,filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT)?:1);
$perPage=8;
$categories=$pdo->query('SELECT id,name FROM categories ORDER BY id')->fetchAll();
$where=[]; $params=[];
if ($categoryId>0) { $where[]='p.category_id=:category'; $params[':category']=$categoryId; }
if ($keyword!=='') { $where[]='(p.name LIKE :name OR p.description LIKE :description)'; $params[':name']='%'.$keyword.'%';$params[':description']='%'.$keyword.'%'; }
$whereSql=$where?' WHERE '.implode(' AND ',$where):'';
$countStmt=$pdo->prepare('SELECT COUNT(*) FROM products p'.$whereSql);
$countStmt->execute($params);
$count=(int)$countStmt->fetchColumn();
$totalPages=max(1,(int)ceil($count/$perPage));
$page=min($page,$totalPages);
$sql='SELECT p.id,p.name,p.price,p.image,p.stock,c.name AS category_name
      FROM products p JOIN categories c ON c.id=p.category_id'.$whereSql.
    ' ORDER BY '.$sortSql[$sort].' LIMIT :lim OFFSET :off';
$stmt=$pdo->prepare($sql);
foreach($params as $key=>$value) $stmt->bindValue($key,$value,is_int($value)?PDO::PARAM_INT:PDO::PARAM_STR);
$stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
$stmt->bindValue(':off',($page-1)*$perPage,PDO::PARAM_INT);
$stmt->execute();
$products=$stmt->fetchAll();
$pageTitle='Trang chủ';$activePage='index.php';
require __DIR__.'/includes/header.php';
?>
<section class="hero-section">
  <div class="hero-content">
    <span class="eyebrow">BỘ SƯU TẬP DÀNH CHO SINH VIÊN</span>
    <h2>Thiết bị nhỏ, hiệu quả lớn.</h2>
    <p>Lựa chọn phụ kiện làm việc, học tập và sáng tạo trong không gian của riêng bạn.</p>
    <a href="#danh-sach" class="btn">Khám phá sản phẩm</a>
  </div>
  <div class="hero-ornament" aria-hidden="true">⌘<span>+</span>✦</div>
</section>
<section id="danh-sach" class="catalog-section">
  <div class="section-heading"><div><span class="eyebrow">TẤT CẢ SẢN PHẨM</span><h2>Danh mục sản phẩm</h2></div><p class="muted">Bộ sưu tập thiết bị được chọn lọc</p></div>
  <form class="search-form" method="get" action="<?= e(url('index.php')) ?>">
    <label class="sr-only" for="q">Tìm kiếm sản phẩm</label>
    <input id="q" name="q" type="search" placeholder="Tìm theo tên hoặc mô tả..." value="<?= e($keyword) ?>">
    <?php if($categoryId>0): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
    <label for="sort">Sắp xếp</label>
    <select name="sort" id="sort">
      <option value="default" <?= $sort==='default'?'selected':'' ?>>Mặc định</option>
      <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Giá tăng dần</option>
      <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Giá giảm dần</option>
    </select>
    <button type="submit">Tìm kiếm</button>
  </form>
  <nav class="category-bar" aria-label="Danh mục sản phẩm">
    <a class="<?= $categoryId===0?'active':'' ?>" href="<?= e(page_url('index.php',['q'=>$keyword,'sort'=>$sort])) ?>">Tất cả</a>
    <?php foreach($categories as $cat): ?>
      <a class="<?= $categoryId===(int)$cat['id']?'active':'' ?>" href="<?= e(page_url('index.php',['category'=>$cat['id'],'q'=>$keyword,'sort'=>$sort])) ?>"><?= e($cat['name']) ?></a>
    <?php endforeach; ?>
  </nav>
  <p class="result-count">Tìm thấy <?= $count ?> sản phẩm · Trang <?= $page ?>/<?= $totalPages ?></p>
  <?php if(!$products): ?><p class="empty-message">Chưa có sản phẩm khớp điều kiện tìm kiếm.</p><?php else: ?>
    <div class="product-list">
      <?php foreach($products as $product): ?>
        <article class="product-card">
          <a href="<?= e(url('product.php?id='.(int)$product['id'])) ?>" class="product-photo-link">
            <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
          </a>
          <div class="product-card-content">
            <span class="product-category"><?= e($product['category_name']) ?></span>
            <h3><?= e($product['name']) ?></h3>
            <p class="price"><?= format_price($product['price']) ?></p>
            <p class="stock"><?= (int)$product['stock']>0?'Còn '.(int)$product['stock'].' sản phẩm':'Hết hàng' ?></p>
            <div class="card-actions">
              <a class="btn secondary-button" href="<?= e(url('product.php?id='.(int)$product['id'])) ?>">Chi tiết</a>
              <form method="post" action="<?= e(url('cart-add.php')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="add-to-cart" <?= (int)$product['stock']===0?'disabled':'' ?>>Thêm +</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if($totalPages>1): ?>
  <nav class="pagination" aria-label="Phân trang sản phẩm">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
      <a class="<?= $i===$page?'active':'' ?>" <?= $i===$page?'aria-current="page"':'' ?> href="<?= e(page_url('index.php',['q'=>$keyword,'sort'=>$sort,'category'=>$categoryId,'page'=>$i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
