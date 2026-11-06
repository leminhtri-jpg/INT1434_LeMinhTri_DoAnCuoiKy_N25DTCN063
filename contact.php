<?php
require __DIR__ . '/includes/init.php';
$errors = [];
$old = ['fullname' => '', 'email' => '', 'subject' => '', 'message' => ''];
if (is_post()) {
    verify_csrf();
    foreach ($old as $field => $_) {
        $old[$field] = trim((string)($_POST[$field] ?? ''));
    }
    if (mb_strlen($old['fullname']) < 2 || mb_strlen($old['fullname']) > 100) {
        $errors['fullname'] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $errors['email'] = 'Email không hợp lệ.';
    }
    if (mb_strlen($old['subject']) < 3 || mb_strlen($old['subject']) > 200) {
        $errors['subject'] = 'Chủ đề phải từ 3 đến 200 ký tự.';
    }
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 5000) {
        $errors['message'] = 'Nội dung phải từ 10 đến 5000 ký tự.';
    }
    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO contacts (fullname, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$old['fullname'], $old['email'], $old['subject'], $old['message']]);
        set_flash('success', 'Đã nhận liên hệ và lưu vào cơ sở dữ liệu.');
        redirect('contact.php');
    }
}
$pageTitle = 'Liên hệ';
$activePage = 'contact.php';
require __DIR__ . '/includes/header.php';
?>
<section class="form-page">
    <h2>Liên hệ cửa hàng</h2>
    <div class="contact-details">
        <p>Địa chỉ: [BẠN ĐIỀN ĐỊA CHỈ THẬT]</p>
        <p>Email: [BẠN ĐIỀN EMAIL LIÊN HỆ]</p>
        <p>Số điện thoại: [BẠN ĐIỀN SỐ LIÊN HỆ]</p>
    </div>
    <h3>Gửi tin nhắn</h3>
    <form method="post" class="form-card" action="<?= e(url('contact.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="fullname">Họ tên</label>
        <input id="fullname" name="fullname" type="text" required minlength="2" maxlength="100" value="<?= e($old['fullname']) ?>">
        <?php if (isset($errors['fullname'])): ?><span class="form-error"><?= e($errors['fullname']) ?></span><?php endif; ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="150" value="<?= e($old['email']) ?>">
        <?php if (isset($errors['email'])): ?><span class="form-error"><?= e($errors['email']) ?></span><?php endif; ?>
        <label for="subject">Chủ đề</label>
        <input id="subject" name="subject" type="text" required minlength="3" maxlength="200" value="<?= e($old['subject']) ?>">
        <?php if (isset($errors['subject'])): ?><span class="form-error"><?= e($errors['subject']) ?></span><?php endif; ?>
        <label for="message">Nội dung</label>
        <textarea id="message" name="message" rows="5" required minlength="10" maxlength="5000"><?= e($old['message']) ?></textarea>
        <?php if (isset($errors['message'])): ?><span class="form-error"><?= e($errors['message']) ?></span><?php endif; ?>
        <button type="submit">Gửi liên hệ</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
