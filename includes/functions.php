<?php
// Các hàm dùng chung (Lab 3 + Lab 4).
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_price($amount): string
{
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

function url(string $path = ''): string
{
    return BASE_URL . ($path === '' ? '' : '/' . ltrim($path, '/'));
}


function image_url(string $file): string
{
    // Ảnh tải lên từ trang quản trị
    if (str_starts_with($file, 'uploads/')) {
        return url('uploads/' . rawurlencode(basename($file)));
    }

    // Ghép ảnh thật với từng sản phẩm trong cửa hàng
    $imageMap = [
        // Sản phẩm 1: Đế tản nhiệt laptop RGB
        'Sàn phầm 01.jpg' => 'Sàn phầm 06.jpg',

        // Sản phẩm 2: Chuột không dây
        'Sàn phầm 02.jpg' => 'Sàn phầm 01.jpg',

        // Sản phẩm 3: Bàn phím cơ RGB K2
        'Sàn phầm 03.jpg' => 'Sàn phầm 02.jpg',

        // Sản phẩm 4: Tai nghe chống ồn
        'Sàn phầm 04.webp' => 'Sàn phầm 03.jpg',

        // Sản phẩm 5: USB lưu trữ 64GB
        'Sàn phầm 05.jpg' => 'Sàn phầm 04.webp',

        // Sản phẩm 6: Webcam Full HD
        'Sàn phầm 06.jpg' => 'Sàn phầm 05.jpg',

        // Sản phẩm 7: Giá đỡ laptop
        'san-pham-07.jpg' => 'Sản phẩm 7.jpg',

        // Sản phẩm 8: Bàn di chuột
        'san-pham-08.jpg' => 'Sản phẩm 8.jpg',

        // Sản phẩm 9: Đèn bàn LED
        'san-pham-09.jpg' => 'Sản phẩm 9.jpg',

        // Sản phẩm 10: Hub USB-C
        'san-pham-10.jpg' => 'Sản phẩm 10.jpg',

        // Sản phẩm 11: Micro USB
        'san-pham-11.jpg' => 'Sản phẩm 11.webp',

        // Sản phẩm 12: Giá đỡ điện thoại
        'san-pham-12.jpg' => 'Sản phẩm 12.jpg',
    ];

    // Lấy tên ảnh đang lưu trong cơ sở dữ liệu
    $filename = basename($file);

    // Chọn đúng ảnh thật nếu có trong danh sách
    $filename = $imageMap[$filename] ?? $filename;

    // Trả về đường dẫn ảnh cho website
    return url('images/' . rawurlencode($filename));
}


function redirect(string $path): void
{
    header('Location: ' . url($path), true, 303);
    exit;
}

// Thông báo một lần sau khi chuyển trang (Post/Redirect/Get).
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

// Chống giả mạo yêu cầu POST từ website khác.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        $pageTitle='Yêu cầu bị từ chối';
        $activePage='';
        require __DIR__.'/header.php';
        echo '<section class="empty-message"><h2>403 – Yêu cầu không hợp lệ</h2>';
        echo '<p>Mã xác thực CSRF bị thiếu hoặc không chính xác. Vui lòng tải lại trang.</p></section>';
        require __DIR__.'/footer.php';
        exit;
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) && !empty($user['id']) ? $user : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        // Ghi nhớ nơi người dùng muốn vào (chỉ các đường dẫn nội bộ cho phép).
        $path = basename((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
        if (in_array($path, ['checkout.php', 'orders.php', 'order-detail.php', 'profile.php', 'change-password.php'], true)) {
            $target = $path;
            if ($path === 'order-detail.php') {
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if ($id && $id > 0) $target .= '?id=' . $id;
            }
            $_SESSION['login_redirect'] = $target;
        }
        set_flash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

// Lấy giá và tên từ cơ sở dữ liệu, không tin vào giá gửi từ trình duyệt.
// Kết quả: [danh sách sản phẩm, tổng tiền].
function get_cart_items(?PDO $pdo = null): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [[], 0];
    }

    $pdo = $pdo ?? db();
    $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($id) => $id > 0));
    if (!$ids) {
        return [[], 0];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders) ORDER BY id");
    $stmt->execute($ids);

    $items = [];
    $total = 0;
    foreach ($stmt->fetchAll() as $product) {
        $quantity = (int)($cart[(int)$product['id']] ?? 0);
        if ($quantity < 1) {
            continue;
        }
        $product['quantity'] = $quantity;
        $product['line_total'] = $quantity * (int)$product['price'];
        $total += $product['line_total'];
        $items[] = $product;
    }
    return [$items, $total];
}

function order_statuses(): array
{
    return [
        'pending' => 'Chờ xử lý',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];
}

function order_status_label(string $status): string
{
    return order_statuses()[$status] ?? 'Không xác định';
}

function valid_phone(string $phone): bool
{
    return (bool)preg_match('/^[0-9]{9,11}$/', $phone);
}


// LAB 5.1 - Chỉ quản trị viên được vào trang quản trị.
function require_admin(): void
{
    require_login();
    $stmt = db()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)current_user()['id']]);
    $role = $stmt->fetchColumn();
    if ($role !== 'admin') {
        if ($role !== false) $_SESSION['user']['role'] = $role;
        http_response_code(403);
        $pageTitle='Không có quyền truy cập';
        $activePage='';
        require __DIR__.'/header.php';
        echo '<section class="empty-message"><h2>403 – Không có quyền truy cập</h2>';
        echo '<p>Tài khoản này không có quyền quản trị cửa hàng.</p></section>';
        require __DIR__.'/footer.php';
        exit;
    }
    $_SESSION['user']['role'] = 'admin';
}

// LAB 5.4 - Xuất input token cho form POST (không in trực tiếp trong hàm).
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// LAB 5.2 - Chỉ lưu ảnh thật; không tin tên và MIME do trình duyệt gửi.
// Trả về null khi không chọn file, đường dẫn uploads/... khi thành công.
// Ném RuntimeException để hiển thị lỗi khi file không hợp lệ.
function upload_image(array $file): ?string
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Tải ảnh thất bại (mã lỗi ' . $error . ').');
    }
    if (!isset($file['size'], $file['tmp_name']) ||
        (int)$file['size'] <= 0 || (int)$file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ảnh phải nhỏ hơn hoặc bằng 2 MB.');
    }
    $tmp = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Tệp tải lên không hợp lệ.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime]) || @getimagesize($tmp) === false) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Không tạo được thư mục uploads.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($tmp, $dir . '/' . $filename)) {
        throw new RuntimeException('Không lưu được ảnh lên máy chủ.');
    }
    return 'uploads/' . $filename;
}


// A/B/D - Giữ bộ lọc khi phân trang.
function page_url(string $path, array $params): string
{
    return url($path . '?' . http_build_query($params));
}

// D7 - Chỉ được xóa ảnh sinh ra bởi tính năng upload của dự án.
function delete_old_uploaded_image(string $image): void
{
    if (!preg_match('~^uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $image)) {
        return;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE image = ?');
    $stmt->execute([$image]);
    if ((int)$stmt->fetchColumn() > 0) return;
    $base = realpath(dirname(__DIR__) . '/uploads');
    $file = realpath(dirname(__DIR__) . '/' . $image);
    if ($base && $file && dirname($file) === $base && is_file($file)) {
        unlink($file);
    }
}

// D10 - Giới hạn thử đăng nhập trên máy chủ, gắn với email.
function login_attempt_key(string $email): string
{
    return hash('sha256', mb_strtolower(trim($email)));
}

function login_block_seconds(string $email): int
{
    $stmt = db()->prepare('SELECT locked_until FROM login_attempts WHERE email_key=?');
    $stmt->execute([login_attempt_key($email)]);
    $locked = $stmt->fetchColumn();
    return $locked ? max(0, strtotime((string)$locked) - time()) : 0;
}

function register_login_failure(string $email): void
{
    $key = login_attempt_key($email);
    $stmt = db()->prepare(
        'INSERT INTO login_attempts(email_key, fail_count, locked_until)
         VALUES (?, 1, NULL)
         ON DUPLICATE KEY UPDATE
         locked_until = IF(fail_count + 1 >= 5, DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL),
         fail_count = fail_count + 1'
    );
    $stmt->execute([$key]);
}

function clear_login_failures(string $email): void
{
    $stmt = db()->prepare('DELETE FROM login_attempts WHERE email_key=?');
    $stmt->execute([login_attempt_key($email)]);
}
