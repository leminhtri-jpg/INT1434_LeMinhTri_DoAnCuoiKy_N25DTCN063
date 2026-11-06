<?php
require __DIR__ . '/includes/init.php';
$pageTitle='Giới thiệu'; $activePage='about.php';
require __DIR__.'/includes/header.php';
?>
<section class="info-page">
  <p class="eyebrow">VỀ CỬA HÀNG</p>
  <h2>Học tốt hơn với những thiết bị phù hợp</h2>
  <p><?= e(APP_NAME) ?> là gian hàng chuyên phụ kiện máy tính, thiết bị âm thanh và dụng cụ góc học tập.</p>
  <p>Mỗi sản phẩm được mô tả rõ ràng, có giá, thông tin tồn kho và phương thức đặt hàng thuận tiện.</p>
  <h3>Cam kết phục vụ</h3>
  <ul class="info-list">
    <li>Thông tin giá và tồn kho lấy trực tiếp từ cơ sở dữ liệu.</li>
    <li>Cho phép tìm kiếm, lọc và so sánh giá sản phẩm.</li>
    <li>Khách hàng có thể kiểm tra lịch sử đơn và trạng thái giao hàng.</li>
  </ul>
  <p class="muted">Bài tập môn học. Dữ liệu và sản phẩm mẫu cần được cá nhân hóa trước khi nộp.</p>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
