# HƯỚNG DẪN CÀI ĐẶT NHANH

## 1. Cài đặt Plugin

### Cách 1: Upload qua WordPress Admin
```
1. Nén thư mục "momo-woocommerce-payment" thành file ZIP
2. Vào WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Chọn file ZIP và click "Install Now"
5. Click "Activate Plugin"
```

### Cách 2: Upload qua FTP
```
1. Upload thư mục "momo-woocommerce-payment" vào: wp-content/plugins/
2. Vào WordPress Admin → Plugins
3. Tìm "MoMo QR Payment for WooCommerce"
4. Click "Activate"
```

## 2. Lấy thông tin API từ MoMo

### Đăng ký MoMo Business:
```
1. Truy cập: https://business.momo.vn
2. Click "Đăng ký" và chọn loại hình doanh nghiệp
3. Điền thông tin doanh nghiệp
4. Upload giấy tờ xác minh (GPKD, CMND/CCCD)
5. Chờ MoMo phê duyệt (1-3 ngày làm việc)
```

### Lấy API Keys:
```
1. Đăng nhập vào MoMo Business Portal
2. Vào mục "Quản lý ứng dụng" hoặc "App Management"
3. Tạo ứng dụng mới hoặc chọn ứng dụng hiện có
4. Copy các thông tin:
   - Partner Code
   - Access Key
   - Secret Key
```

## 3. Cấu hình Plugin

```
1. Vào WooCommerce → Settings → Payments
2. Tìm "MoMo QR Payment" và click "Manage"
3. Điền thông tin:

   [✓] Enable MoMo QR Payment
   
   Title: Thanh toán MoMo
   Description: Quét mã QR để thanh toán qua ví MoMo
   
   [✓] Test mode (bật khi đang test)
   
   Partner Code: [Dán Partner Code từ MoMo]
   Access Key: [Dán Access Key từ MoMo]
   Secret Key: [Dán Secret Key từ MoMo]
   
   [✓] Auto Complete Order (tùy chọn)
   
   Webhook URL: https://yourdomain.com/momo-webhook/

4. Click "Save changes"
```

## 4. Cấu hình Webhook trong MoMo Portal

```
1. Vào MoMo Business Portal
2. Chọn ứng dụng của bạn
3. Tìm mục "IPN URL" hoặc "Webhook URL"
4. Dán URL: https://yourdomain.com/momo-webhook/
5. Lưu cấu hình
```

## 5. Test thanh toán

### Môi trường Test:
```
1. Bật "Test mode" trong cấu hình plugin
2. Sử dụng thông tin test từ MoMo
3. Tạo đơn hàng test
4. Chọn thanh toán MoMo
5. Quét mã QR test
6. Kiểm tra đơn hàng tự động cập nhật
```

### Môi trường Production:
```
1. Tắt "Test mode"
2. Đảm bảo đã cấu hình webhook đúng
3. Đảm bảo site có SSL (HTTPS)
4. Thực hiện thanh toán thật
5. Kiểm tra đơn hàng tự động cập nhật
```

## 6. Kiểm tra Webhook hoạt động

### Cách 1: Kiểm tra log
```php
// Thêm vào wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Xem log tại: wp-content/debug.log
```

### Cách 2: Test webhook thủ công
```
1. Tạo đơn hàng test
2. Thanh toán qua MoMo
3. Kiểm tra file debug.log có dòng:
   "MoMo Webhook Request: ..."
4. Kiểm tra đơn hàng có tự động cập nhật không
```

## 7. Xử lý lỗi thường gặp

### Lỗi: Webhook không nhận được
```
✓ Kiểm tra URL webhook trong MoMo Portal
✓ Kiểm tra firewall/security plugin
✓ Kiểm tra site có SSL (HTTPS)
✓ Kiểm tra file .htaccess có block request không
✓ Liên hệ hosting kiểm tra có block POST request không
```

### Lỗi: Signature không hợp lệ
```
✓ Kiểm tra Secret Key đã đúng chưa
✓ Kiểm tra không có khoảng trắng thừa khi copy
✓ Kiểm tra đã Save settings chưa
✓ Clear cache plugin/browser
```

### Lỗi: Đơn hàng không tự động cập nhật
```
✓ Kiểm tra webhook URL đã cấu hình chưa
✓ Kiểm tra log có nhận webhook không
✓ Kiểm tra database permission
✓ Kiểm tra PHP error log
```

## 8. Bật chế độ Production

```
Khi đã test thành công:

1. Tắt Test mode
2. Đổi sang API keys Production từ MoMo
3. Cập nhật webhook URL trong MoMo Portal
4. Test lại một lần nữa với số tiền nhỏ
5. Monitoring đơn hàng đầu tiên cẩn thận
```

## 9. Tối ưu

### Để tăng tốc độ:
```
✓ Sử dụng caching plugin
✓ Tối ưu database
✓ Sử dụng CDN
✓ Enable PHP OPcache
```

### Để tăng bảo mật:
```
✓ Luôn sử dụng HTTPS
✓ Cập nhật WordPress/WooCommerce thường xuyên
✓ Sử dụng Wordfence hoặc security plugin
✓ Backup database định kỳ
✓ Giới hạn login attempts
```

## 10. Hỗ trợ

### Tài liệu MoMo:
- Developer Docs: https://developers.momo.vn
- Business Portal: https://business.momo.vn
- Hotline: 1900 5454 58

### WordPress/WooCommerce:
- WooCommerce Docs: https://woocommerce.com/documentation/
- WordPress Support: https://wordpress.org/support/

---

**Chúc bạn cài đặt thành công! 🎉**
