<?php
/**
 * MoMo Webhook Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class MoMo_Webhook {
    
    /**
     * Xử lý request từ MoMo webhook
     */
    public function handle_request() {
        // Đọc raw data
        $raw_data = file_get_contents('php://input');
        
        // Log request
        error_log('MoMo Webhook Request: ' . $raw_data);
        
        // Parse JSON
        $data = json_decode($raw_data, true);
        
        if (!$data || !isset($data['orderId'])) {
            $this->send_response(array(
                'status' => 'error',
                'message' => 'Invalid request data'
            ));
            return;
        }
        
        // Lấy gateway settings
        $gateway = WC()->payment_gateways->payment_gateways()['momo'];
        
        if (!$gateway) {
            error_log('MoMo Gateway not found');
            $this->send_response(array(
                'status' => 'error',
                'message' => 'Gateway not found'
            ));
            return;
        }
        
        // Xác thực signature
        $momo_api = new MoMo_API(
            $gateway->partner_code,
            $gateway->access_key,
            $gateway->secret_key,
            $gateway->testmode
        );
        
        if (!$momo_api->verify_signature($data)) {
            error_log('MoMo Webhook: Invalid signature');
            $this->send_response(array(
                'status' => 'error',
                'message' => 'Invalid signature'
            ));
            return;
        }
        
        // Tìm đơn hàng
        $order = $this->find_order_by_momo_id($data['orderId']);
        
        if (!$order) {
            error_log('MoMo Webhook: Order not found - ' . $data['orderId']);
            $this->send_response(array(
                'status' => 'error',
                'message' => 'Order not found'
            ));
            return;
        }
        
        // Xử lý kết quả thanh toán
        $this->process_payment_result($order, $data, $gateway);
        
        // Trả về response thành công
        $this->send_response(array(
            'status' => 'success',
            'message' => 'Webhook processed'
        ));
    }
    
    /**
     * Tìm đơn hàng theo MoMo Order ID
     */
    private function find_order_by_momo_id($momo_order_id) {
        global $wpdb;
        
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_momo_order_id' 
            AND meta_value = %s 
            LIMIT 1",
            $momo_order_id
        ));
        
        if ($order_id) {
            return wc_get_order($order_id);
        }
        
        return false;
    }
    
    /**
     * Xử lý kết quả thanh toán
     */
    private function process_payment_result($order, $data, $gateway) {
        $order_id = $order->get_id();
        
        // Kiểm tra trạng thái đơn hàng hiện tại
        $current_status = $order->get_status();
        
        // Nếu đã xử lý rồi thì không xử lý lại
        if (in_array($current_status, array('processing', 'completed', 'refunded', 'cancelled'))) {
            error_log("MoMo Webhook: Order #{$order_id} already processed with status: {$current_status}");
            return;
        }
        
        // Lưu thông tin giao dịch
        $order->update_meta_data('_momo_trans_id', $data['transId']);
        $order->update_meta_data('_momo_result_code', $data['resultCode']);
        $order->update_meta_data('_momo_payment_time', $data['responseTime']);
        $order->update_meta_data('_momo_raw_data', json_encode($data));
        $order->save();
        
        // Kiểm tra kết quả
        if ($data['resultCode'] == 0) {
            // Thanh toán thành công
            $order->add_order_note(
                sprintf(
                    __('Thanh toán MoMo thành công. Mã giao dịch: %s. Số tiền: %s VNĐ', 'momo-woocommerce'),
                    $data['transId'],
                    number_format($data['amount'])
                )
            );
            
            // Cập nhật trạng thái đơn hàng
            if ($gateway->auto_complete && $order->has_downloadable_item() === false && $order->needs_shipping() === false) {
                // Tự động hoàn thành nếu không cần ship
                $order->update_status('completed', __('Đơn hàng hoàn thành tự động sau khi thanh toán MoMo thành công', 'momo-woocommerce'));
            } else {
                // Chuyển sang processing
                $order->payment_complete($data['transId']);
            }
            
            // Gửi email thông báo
            do_action('woocommerce_payment_complete', $order_id);
            
        } else {
            // Thanh toán thất bại
            $order->update_status('failed', sprintf(
                __('Thanh toán MoMo thất bại. Mã lỗi: %s. Thông báo: %s', 'momo-woocommerce'),
                $data['resultCode'],
                $data['message']
            ));
        }
    }
    
    /**
     * Gửi response về MoMo
     */
    private function send_response($response) {
        status_header(200);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
