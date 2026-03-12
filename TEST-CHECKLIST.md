# TEST CHECKLIST

## Chuẩn bị môi trường test

### 1. Yêu cầu
- [ ] WordPress 5.8+ đã cài đặt
- [ ] WooCommerce 5.0+ đã kích hoạt
- [ ] PHP 7.4+ 
- [ ] SSL certificate (HTTPS) cho production
- [ ] Tài khoản MoMo Business đã được phê duyệt

### 2. Cài đặt Plugin
- [ ] Upload plugin vào wp-content/plugins/
- [ ] Kích hoạt plugin
- [ ] Kiểm tra không có error trong error log

### 3. Cấu hình MoMo Test Credentials

Đối với môi trường TEST, sử dụng thông tin từ MoMo Developer Portal:

```
Test Endpoint: https://test-payment.momo.vn/v2/gateway/api/create
Partner Code: [từ MoMo test account]
Access Key: [từ MoMo test account]
Secret Key: [từ MoMo test account]
```

## Test Cases

### Test 1: Cấu hình Plugin
- [ ] Vào WooCommerce → Settings → Payments
- [ ] Tìm thấy "MoMo QR Payment"
- [ ] Click "Manage"
- [ ] Điền đầy đủ thông tin test
- [ ] Save settings thành công
- [ ] Không có error message

### Test 2: Thanh toán thành công
1. [ ] Tạo sản phẩm test giá 10,000 VNĐ
2. [ ] Thêm vào giỏ hàng
3. [ ] Checkout
4. [ ] Chọn "Thanh toán MoMo"
5. [ ] Click "Place Order"
6. [ ] Được chuyển đến trang MoMo
7. [ ] Quét QR code (hoặc dùng test payment)
8. [ ] Thanh toán thành công
9. [ ] Quay lại trang thank you
10. [ ] Kiểm tra đơn hàng:
    - [ ] Trạng thái = "Processing" hoặc "Completed"
    - [ ] Order note có ghi "Thanh toán MoMo thành công"
    - [ ] Meta data có _momo_trans_id
11. [ ] Kiểm tra email được gửi đến khách hàng

### Test 3: Thanh toán thất bại
1. [ ] Tạo đơn hàng mới
2. [ ] Chọn thanh toán MoMo
3. [ ] Không thanh toán (cancel)
4. [ ] Hoặc thanh toán thất bại
5. [ ] Kiểm tra đơn hàng:
    - [ ] Trạng thái = "Failed"
    - [ ] Order note có ghi lý do thất bại

### Test 4: Webhook
1. [ ] Bật debug log trong wp-config.php:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
2. [ ] Tạo thanh toán mới
3. [ ] Kiểm tra file wp-content/debug.log:
    - [ ] Có log "MoMo Webhook Request"
    - [ ] Có log "MoMo Create Payment Response"
    - [ ] Signature verification passed
4. [ ] Kiểm tra đơn hàng cập nhật tự động

### Test 5: Webhook Security
1. [ ] Gửi webhook giả mạo (signature sai):
   ```bash
   curl -X POST https://yoursite.com/momo-webhook/ \
   -H "Content-Type: application/json" \
   -d '{"orderId":"fake123","signature":"wrong"}'
   ```
2. [ ] Kiểm tra:
    - [ ] Request bị reject
    - [ ] Log có "Invalid signature"
    - [ ] Đơn hàng không bị cập nhật

### Test 6: Refund (Hoàn tiền)
1. [ ] Tạo đơn hàng và thanh toán thành công
2. [ ] Vào order admin
3. [ ] Click "Refund"
4. [ ] Nhập số tiền và lý do
5. [ ] Click "Refund via MoMo"
6. [ ] Kiểm tra:
    - [ ] Hoàn tiền thành công
    - [ ] Order note ghi lại thông tin refund
    - [ ] Trạng thái đơn hàng cập nhật

### Test 7: Multiple Orders
1. [ ] Tạo 3-5 đơn hàng đồng thời
2. [ ] Thanh toán tất cả
3. [ ] Kiểm tra:
    - [ ] Tất cả đơn hàng cập nhật đúng
    - [ ] Không có đơn hàng nào bị nhầm
    - [ ] Webhook xử lý đúng từng đơn

### Test 8: Auto Complete
1. [ ] Bật "Auto Complete Order"
2. [ ] Tạo đơn hàng sản phẩm digital (không cần ship)
3. [ ] Thanh toán thành công
4. [ ] Kiểm tra:
    - [ ] Trạng thái = "Completed" (không phải "Processing")

### Test 9: Different Amount
1. [ ] Test với các mức giá khác nhau:
    - [ ] 1,000 VNĐ
    - [ ] 50,000 VNĐ
    - [ ] 500,000 VNĐ
    - [ ] 5,000,000 VNĐ
2. [ ] Kiểm tra tất cả thanh toán thành công

### Test 10: Edge Cases
1. [ ] Thử thanh toán khi hết hạn QR (timeout)
2. [ ] Thử thanh toán 2 lần cùng 1 đơn hàng
3. [ ] Thử truy cập webhook URL trực tiếp
4. [ ] Thử với connection timeout
5. [ ] Kiểm tra xử lý lỗi gracefully

## Production Testing

### Before Go Live:
- [ ] Tắt Test mode
- [ ] Cập nhật Production credentials
- [ ] Cập nhật webhook URL trong MoMo Portal
- [ ] Kiểm tra SSL certificate hợp lệ
- [ ] Test với số tiền nhỏ (1,000 VNĐ)
- [ ] Monitor logs trong 24h đầu
- [ ] Kiểm tra email notifications
- [ ] Test refund một lần

### After Go Live:
- [ ] Monitor error logs hàng ngày
- [ ] Kiểm tra tỷ lệ thanh toán thành công
- [ ] Thu thập feedback từ khách hàng
- [ ] Kiểm tra reconciliation với MoMo monthly

## Performance Testing

### Load Test:
- [ ] 10 đơn hàng đồng thời
- [ ] 50 đơn hàng trong 1 phút
- [ ] 100 webhook requests đồng thời

### Response Time:
- [ ] Payment creation < 3s
- [ ] Webhook processing < 1s
- [ ] Order update < 2s

## Security Testing

- [ ] SQL Injection test
- [ ] XSS test
- [ ] CSRF test
- [ ] Signature verification test
- [ ] Replay attack test

## Documentation Check

- [ ] README.md đầy đủ
- [ ] HUONG-DAN.md dễ hiểu
- [ ] Code comments rõ ràng
- [ ] API examples chính xác

---

**Note**: Đánh dấu [x] vào các test đã pass
