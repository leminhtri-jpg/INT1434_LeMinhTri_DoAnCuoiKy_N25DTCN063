
<?php
$pageTitle = $pageTitle ?? 'Trang chủ';
$activePage = $activePage ?? 'index.php';
$flash = get_flash();

// ========================================
// TỰ ĐỘNG TÌM ẢNH CHÂN DUNG SINH VIÊN
// ========================================

$portraitFile = null;

$portraitNames = [
    'chan-dung.jpg',
    'chan dung.jpg',
    'chan-dung.jpeg',
    'chan dung.jpeg',
    'chan-dung.png',
    'chan dung.png',
    'chan-dung.webp',
    'chan dung.webp',
];

foreach ($portraitNames as $filename) {
    $imagePath = dirname(__DIR__) . '/images/' . $filename;

    if (is_file($imagePath)) {
        $portraitFile = $filename;
        break;
    }
}

$portraitAvailable = $portraitFile !== null;

// ========================================
// DANH SÁCH MENU WEBSITE
// ========================================

$navItems = [
    'index.php'   => 'Cửa hàng',
    'about.php'   => 'Giới thiệu',
    'cart.php'    => 'Giỏ hàng',
    'contact.php' => 'Liên hệ',
];
?>

<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="description"
        content="Phụ kiện công nghệ, sản phẩm học tập và làm việc do cửa hàng <?= e(STUDENT_NAME) ?> giới thiệu."
    >

    <title>
        <?= e($pageTitle) ?> | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= e(url('css/style.css')) ?>"
    >
</head>

<body>

<!-- ========================================
     HEADER - THÔNG TIN CỬA HÀNG
======================================== -->

<header class="site-header">

    <div class="brand-area">

        <!-- ẢNH CHÂN DUNG SINH VIÊN -->
        <?php if ($portraitAvailable): ?>

            <img
                class="portrait-logo"
                src="<?= e(url('images/' . rawurlencode($portraitFile))) ?>"
                alt="Ảnh chân dung chủ cửa hàng <?= e(STUDENT_NAME) ?>"
            >

        <?php else: ?>

            <div
                class="portrait-logo portrait-missing"
                role="img"
                aria-label="Cần thêm ảnh chân dung thật"
            >
                THÊM ẢNH<br>
                CHÂN DUNG
            </div>

        <?php endif; ?>

        <!-- THÔNG TIN CỬA HÀNG -->
        <div class="brand-copy">

            <p class="brand-kicker">
                GÓC CÔNG NGHỆ &amp; HỌC TẬP
            </p>

            <h1>
                <a href="<?= e(url('index.php')) ?>">
                    <?= e(APP_NAME) ?>
                </a>
            </h1>

            <p class="brand-desc">
                Chọn thiết bị phù hợp cho việc học
                và làm việc mỗi ngày
            </p>

        </div>

    </div>

    <!-- ========================================
         MENU ĐIỀU HƯỚNG
    ======================================== -->

    <nav class="main-nav" aria-label="Menu chính">

        <?php foreach ($navItems as $file => $label): ?>

            <a
                href="<?= e(url($file)) ?>"
                class="<?= $activePage === $file ? 'active' : '' ?>"
                <?= $activePage === $file ? 'aria-current="page"' : '' ?>
            >

                <?= e($label) ?>

                <?php if ($file === 'cart.php'): ?>

                    <span class="cart-bubble">
                        <?= cart_count() ?>
                    </span>

                <?php endif; ?>

            </a>

        <?php endforeach; ?>

        <!-- MENU QUẢN TRỊ -->
        <?php if (is_admin()): ?>

            <a
                href="<?= e(url('admin/dashboard.php')) ?>"
                class="<?= str_starts_with($activePage, 'admin/') ? 'active' : '' ?>"
            >
                Quản trị
            </a>

        <?php endif; ?>

        <!-- MENU KHI ĐÃ ĐĂNG NHẬP -->
        <?php if (is_logged_in()): ?>

            <a
                href="<?= e(url('orders.php')) ?>"
                class="<?= $activePage === 'orders.php' ? 'active' : '' ?>"
            >
                Đơn hàng
            </a>

            <a
                href="<?= e(url('profile.php')) ?>"
                class="<?= $activePage === 'profile.php' ? 'active' : '' ?>"
            >
                Tài khoản
            </a>

            <form
                action="<?= e(url('logout.php')) ?>"
                method="post"
                class="nav-logout"
            >

                <?= csrf_field() ?>

                <button type="submit">
                    Đăng xuất
                </button>

            </form>

        <?php else: ?>

            <!-- MENU KHI CHƯA ĐĂNG NHẬP -->
            <a href="<?= e(url('login.php')) ?>">
                Đăng nhập
            </a>

            <a href="<?= e(url('register.php')) ?>">
                Đăng ký
            </a>

        <?php endif; ?>

    </nav>

</header>

<!-- ========================================
     NỘI DUNG CHÍNH
======================================== -->

<main class="container" id="main-content">

    <!-- HIỂN THỊ THÔNG BÁO -->
    <?php if ($flash): ?>

        <div
            class="alert alert-<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>"
            role="status"
        >

            <?= e($flash['message'] ?? '') ?>

        </div>

    <?php endif; ?>
