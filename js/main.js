'use strict';
// Buổi 4: giỏ hàng chính thức lưu trong $_SESSION trên PHP.
// JavaScript chỉ dùng để xác nhận thao tác và hiển thị tổng tiền dự kiến.
// KHÔNG sử dụng localStorage cho giỏ hàng nữa.

// Xác nhận trước khi xóa giỏ hoặc hủy đơn.
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!window.confirm(form.dataset.confirm || 'Bạn có chắc chắn?')) {
            event.preventDefault();
        }
    });
});

// Kiểm tra nhập lại mật khẩu ở trình duyệt (PHP vẫn kiểm tra lần nữa).
const registerForm = document.querySelector('#register-form');
if (registerForm) {
    registerForm.addEventListener('submit', function (event) {
        const password = registerForm.querySelector('#password');
        const password2 = registerForm.querySelector('#password2');
        const error = registerForm.querySelector('#password-error');
        if (password && password2 && password.value !== password2.value) {
            event.preventDefault();
            if (error) error.textContent = 'Hai mật khẩu không khớp nhau.';
            password2.focus();
        } else if (error) {
            error.textContent = '';
        }
    });
}

// Xem trước tổng tiền; chỉ lưu khi nhấn Cập nhật giỏ hàng.
const cartTable = document.querySelector('.cart-table');
if (cartTable && document.querySelector('#cart-update-form')) {
    const formatPrice = function (price) {
        return Number(price).toLocaleString('vi-VN') + ' đ';
    };
    cartTable.addEventListener('input', function (event) {
        if (!event.target.matches('.qty')) return;
        let total = 0;
        cartTable.querySelectorAll('tbody tr[data-price]').forEach(function (row) {
            const price = Number(row.dataset.price);
            const qtyInput = row.querySelector('.qty');
            const quantity = Number(qtyInput.value);
            if (!Number.isInteger(quantity) || quantity < 0) return;
            const line = price * quantity;
            const cell = row.querySelector('.line-total');
            if (cell) cell.textContent = formatPrice(line);
            total += line;
        });
        const heading = document.querySelector('#cart-total');
        if (heading) heading.textContent = 'Tổng tạm tính: ' + formatPrice(total);
    });
}


// A7 - Kiểm tra độ dài nội dung liên hệ; PHP vẫn kiểm tra phía máy chủ.
const contactForm = document.querySelector('form[action$="contact.php"]');
if (contactForm) {
    contactForm.addEventListener('submit', function (event) {
        const message = contactForm.querySelector('[name="message"]');
        if (message && message.value.trim().length < 10) {
            event.preventDefault();
            message.setCustomValidity('Nội dung phải có ít nhất 10 ký tự.');
            message.reportValidity();
        }
    });
    const message = contactForm.querySelector('[name="message"]');
    if (message) message.addEventListener('input', () => message.setCustomValidity(''));
}

// In hóa đơn bằng hệ thống in của trình duyệt.
const printInvoice = document.querySelector('#print-invoice');
if (printInvoice) printInvoice.addEventListener('click', () => window.print());
