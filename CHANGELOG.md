# Changelog

## [1.0.0] - 2025-02-15

### Added
- Tích hợp thanh toán MoMo QR Code
- Webhook tự động cập nhật trạng thái đơn hàng
- Hỗ trợ môi trường test và production
- Tự động hoàn thành đơn hàng (tùy chọn)
- Hỗ trợ hoàn tiền (refund) qua API MoMo
- Xác thực signature HMAC SHA256
- Ghi log chi tiết cho debugging
- Lưu trữ thông tin giao dịch trong order meta
- Trang cấu hình trong WooCommerce Settings
- Hỗ trợ đa ngôn ngữ (sẵn sàng dịch)

### Security
- Xác thực HMAC SHA256 cho mọi webhook
- Sanitize và validate input
- Prevent SQL injection
- Prevent XSS attacks
- Secure credential storage

### Documentation
- README.md với hướng dẫn đầy đủ
- HUONG-DAN.md hướng dẫn nhanh tiếng Việt
- Inline code documentation
- API integration examples
