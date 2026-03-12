# MoMo QR Payment for WooCommerce

Plugin WordPress tích hợp thanh toán MoMo QR Code và tự động cập nhật đơn hàng WooCommerce.

## Tính năng

- ✅ Tích hợp thanh toán MoMo QR Code
- ✅ Tự động cập nhật trạng thái đơn hàng khi thanh toán thành công
- ✅ Hỗ trợ webhook từ MoMo để cập nhật real-time
- ✅ Hỗ trợ hoàn tiền (refund)
- ✅ Chế độ test và production
- ✅ Tự động hoàn thành đơn hàng (tùy chọn)
- ✅ Ghi log chi tiết cho debugging

## Yêu cầu

- WordPress 5.8 trở lên
- WooCommerce 5.0 trở lên
- PHP 7.4 trở lên
- Tài khoản MoMo Business (đăng ký tại https://business.momo.vn)

## Cài đặt

### Bước 1: Tải plugin

1. Tải toàn bộ thư mục plugin
2. Nén thành file ZIP (nếu cần)
3. Vào WordPress Admin → Plugins → Add New → Upload Plugin
4. Chọn file ZIP và cài đặt
5. Kích hoạt plugin

### Bước 2: Đăng ký tài khoản MoMo Business

1. Truy cập https://business.momo.vn
2. Đăng ký tài khoản doanh nghiệp
3. Hoàn tất xác minh doanh nghiệp
4. Tạo ứng dụng mới trong MoMo Partner Portal
5. Lấy thông tin:
   - Partner Code
   - Access Key
   - Secret Key

### Bước 3: Cấu hình plugin

1. Vào WooCommerce → Settings → Payments
2. Tìm "MoMo QR Payment" và click "Manage"
3. Cấu hình các thông tin:

#### Cài đặt cơ bản:

- **Enable/Disable**: Bật/tắt phương thức thanh toán
- **Title**: Tiêu đề hiển thị (VD: "Thanh toán MoMo")
- **Description**: Mô tả cho khách hàng

#### Cài đặt API:

- **Test mode**: Bật chế độ test khi đang phát triển
- **Partner Code**: Nhập Partner Code từ MoMo
- **Access Key**: Nhập Access Key từ MoMo
- **Secret Key**: Nhập Secret Key từ MoMo

#### Cài đặt nâng cao:

- **Auto Complete Order**: Tự động hoàn thành đơn hàng sau khi thanh toán thành công
- **Webhook URL**: Copy URL này để cấu hình trong MoMo Portal

### Bước 4: Cấu hình Webhook trong MoMo Portal

1. Đăng nhập vào MoMo Partner Portal
2. Vào mục "Cấu hình Webhook" hoặc "IPN URL"
3. Dán URL webhook từ plugin (VD: `https://yoursite.com/momo-webhook/`)
4. Lưu cấu hình

## Cách hoạt động

### Luồng thanh toán:

1. **Khách hàng chọn thanh toán MoMo** tại trang checkout
2. **Đơn hàng được tạo** với trạng thái "On Hold"
3. **Khách hàng được chuyển** đến trang thanh toán MoMo
4. **Quét mã QR** hoặc mở ứng dụng MoMo để thanh toán
5. **MoMo gửi webhook** về server khi thanh toán thành công/thất bại
6. **Plugin tự động cập nhật** trạng thái đơn hàng:
   - Thành công → "Processing" hoặc "Completed"
   - Thất bại → "Failed"
7. **Email thông báo** được gửi đến khách hàng

### Xác thực bảo mật:

- Sử dụng HMAC SHA256 để ký và xác thực dữ liệu
- Kiểm tra signature từ MoMo webhook
- Chống replay attack
- Ghi log tất cả giao dịch

## API Endpoints

Plugin tạo custom endpoint để nhận webhook:

```
https://yoursite.com/momo-webhook/
```

Endpoint này:

- Nhận POST request từ MoMo
- Xác thực signature
- Tìm đơn hàng tương ứng
- Cập nhật trạng thái đơn hàng
- Gửi response về MoMo

## Cấu trúc dữ liệu

### Order Meta Data

Plugin lưu các thông tin sau vào đơn hàng:

- `_momo_order_id`: ID đơn hàng trong MoMo
- `_momo_request_id`: ID request
- `_momo_trans_id`: Mã giao dịch MoMo
- `_momo_result_code`: Mã kết quả
- `_momo_payment_time`: Thời gian thanh toán
- `_momo_raw_data`: Dữ liệu raw từ webhook

## Testing

### Chế độ Test:

1. Bật "Test mode" trong cấu hình
2. Sử dụng thông tin test từ MoMo
3. Test thanh toán trên môi trường test của MoMo

### Thông tin test MoMo:

Truy cập tài liệu MoMo để lấy thông tin test:
https://developers.momo.vn

## Xử lý lỗi

### Debugging:

Plugin ghi log vào WordPress debug log. Để bật debug:

```php
// Thêm vào wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Log file: `wp-content/debug.log`

### Lỗi thường gặp:

1. **Webhook không hoạt động**:
   - Kiểm tra URL webhook trong MoMo Portal
   - Kiểm tra firewall/security plugin có chặn không
   - Kiểm tra SSL certificate (cần HTTPS)

2. **Signature không hợp lệ**:
   - Kiểm tra Secret Key đã đúng chưa
   - Kiểm tra thời gian server (cần đồng bộ)

3. **Đơn hàng không tự động cập nhật**:
   - Kiểm tra webhook URL
   - Kiểm tra log để xem có nhận được webhook không
   - Kiểm tra quyền ghi database

## Hoàn tiền (Refund)

Plugin hỗ trợ hoàn tiền trực tiếp từ WooCommerce:

1. Vào đơn hàng cần hoàn tiền
2. Click "Refund"
3. Nhập số tiền và lý do
4. Click "Refund via MoMo"
5. Plugin sẽ gọi API MoMo để hoàn tiền

## Bảo mật

- ✅ Xác thực HMAC SHA256
- ✅ Kiểm tra signature mọi webhook
- ✅ Không lưu thông tin nhạy cảm trong database
- ✅ Sanitize và validate mọi input
- ✅ Chống SQL injection
- ✅ Chống XSS

## Hỗ trợ

- Email: support@example.com
- Website: https://example.com
- Tài liệu MoMo: https://developers.momo.vn

## License

GPL v2 or later

## Changelog

### Version 1.0.0

- Phiên bản đầu tiên
- Tích hợp thanh toán MoMo QR
- Webhook tự động cập nhật đơn hàng
- Hỗ trợ hoàn tiền

## Credits

Developed by Huynh Nguyen Dev
MoMo API Integration based on MoMo Developer Documentation
