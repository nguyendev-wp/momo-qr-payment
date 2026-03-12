<?php
/**
 * MoMo API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class MoMo_API {
    
    private $partner_code;
    private $access_key;
    private $secret_key;
    private $endpoint;
    
    public function __construct($partner_code, $access_key, $secret_key, $testmode = true) {
        $this->partner_code = $partner_code;
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        
        // Endpoint
        if ($testmode) {
            $this->endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
        } else {
            $this->endpoint = 'https://payment.momo.vn/v2/gateway/api/create';
        }
    }
    
    /**
     * Tạo thanh toán MoMo
     */
    public function create_payment($order) {
        $order_id = $order->get_id();
        $amount = $order->get_total();
        $order_info = 'Thanh toán đơn hàng #' . $order_id;
        
        // Tạo request ID và order ID
        $request_id = time() . '';
        $momo_order_id = time() . '';
        
        // URLs
        $redirect_url = $order->get_checkout_order_received_url();
        $ipn_url = home_url('/momo-webhook/');
        
        // Chuẩn bị dữ liệu
        $raw_data = "accessKey=" . $this->access_key .
                    "&amount=" . $amount .
                    "&extraData=" .
                    "&ipnUrl=" . $ipn_url .
                    "&orderId=" . $momo_order_id .
                    "&orderInfo=" . $order_info .
                    "&partnerCode=" . $this->partner_code .
                    "&redirectUrl=" . $redirect_url .
                    "&requestId=" . $request_id .
                    "&requestType=captureWallet";
        
        // Tạo signature
        $signature = hash_hmac("sha256", $raw_data, $this->secret_key);
        
        // Dữ liệu gửi đi
        $data = array(
            'partnerCode' => $this->partner_code,
            'partnerName' => get_bloginfo('name'),
            'storeId' => $this->partner_code,
            'requestId' => $request_id,
            'amount' => $amount,
            'orderId' => $momo_order_id,
            'orderInfo' => $order_info,
            'redirectUrl' => $redirect_url,
            'ipnUrl' => $ipn_url,
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => 'captureWallet',
            'signature' => $signature
        );
        
        // Gọi API
        $response = wp_remote_post($this->endpoint, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            error_log('MoMo API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        // Log
        error_log('MoMo Create Payment Response: ' . print_r($result, true));
        
        if (isset($result['resultCode']) && $result['resultCode'] == 0) {
            return $result;
        }
        
        return false;
    }
    
    /**
     * Xác thực chữ ký từ MoMo webhook
     */
    public function verify_signature($data) {
        $raw_hash = "accessKey=" . $this->access_key .
                    "&amount=" . $data['amount'] .
                    "&extraData=" . $data['extraData'] .
                    "&message=" . $data['message'] .
                    "&orderId=" . $data['orderId'] .
                    "&orderInfo=" . $data['orderInfo'] .
                    "&orderType=" . $data['orderType'] .
                    "&partnerCode=" . $data['partnerCode'] .
                    "&payType=" . $data['payType'] .
                    "&requestId=" . $data['requestId'] .
                    "&responseTime=" . $data['responseTime'] .
                    "&resultCode=" . $data['resultCode'] .
                    "&transId=" . $data['transId'];
        
        $signature = hash_hmac("sha256", $raw_hash, $this->secret_key);
        
        return $signature === $data['signature'];
    }
    
    /**
     * Hoàn tiền
     */
    public function refund_payment($order, $amount, $reason) {
        $endpoint = str_replace('/create', '/refund', $this->endpoint);
        
        $order_id = $order->get_meta('_momo_order_id');
        $trans_id = $order->get_meta('_momo_trans_id');
        $request_id = time() . '';
        
        if (!$trans_id) {
            return false;
        }
        
        // Chuẩn bị dữ liệu
        $raw_data = "accessKey=" . $this->access_key .
                    "&amount=" . $amount .
                    "&description=" . $reason .
                    "&orderId=" . $order_id .
                    "&partnerCode=" . $this->partner_code .
                    "&requestId=" . $request_id .
                    "&transId=" . $trans_id;
        
        $signature = hash_hmac("sha256", $raw_data, $this->secret_key);
        
        $data = array(
            'partnerCode' => $this->partner_code,
            'orderId' => $order_id,
            'requestId' => $request_id,
            'amount' => $amount,
            'transId' => $trans_id,
            'lang' => 'vi',
            'description' => $reason,
            'signature' => $signature
        );
        
        // Gọi API
        $response = wp_remote_post($endpoint, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            error_log('MoMo Refund API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        error_log('MoMo Refund Response: ' . print_r($result, true));
        
        return $result;
    }
}
