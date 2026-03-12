<?php
/**
 * WooCommerce MoMo Payment Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_MoMo_Gateway extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'momo';
        $this->icon = MOMO_WC_PLUGIN_URL . 'assets/images/momo-logo.png';
        $this->has_fields = false;
        $this->method_title = __('MoMo QR Payment', 'momo-woocommerce');
        $this->method_description = __('Thanh toán qua MoMo QR Code và tự động cập nhật đơn hàng', 'momo-woocommerce');
        
        // Hỗ trợ refund
        $this->supports = array(
            'products',
            'refunds'
        );
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Get settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->partner_code = $this->get_option('partner_code');
        $this->access_key = $this->get_option('access_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->testmode = 'yes' === $this->get_option('testmode', 'yes');
        $this->auto_complete = 'yes' === $this->get_option('auto_complete', 'yes');
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }
    
    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'momo-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Bật thanh toán MoMo', 'momo-woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Title', 'momo-woocommerce'),
                'type'        => 'text',
                'description' => __('Tiêu đề hiển thị cho khách hàng khi thanh toán', 'momo-woocommerce'),
                'default'     => __('Thanh toán MoMo', 'momo-woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'momo-woocommerce'),
                'type'        => 'textarea',
                'description' => __('Mô tả phương thức thanh toán', 'momo-woocommerce'),
                'default'     => __('Quét mã QR để thanh toán qua ví MoMo', 'momo-woocommerce'),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'momo-woocommerce'),
                'label'       => __('Bật chế độ test', 'momo-woocommerce'),
                'type'        => 'checkbox',
                'description' => __('Sử dụng môi trường test của MoMo', 'momo-woocommerce'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'partner_code' => array(
                'title'       => __('Partner Code', 'momo-woocommerce'),
                'type'        => 'text',
                'description' => __('Nhập Partner Code từ MoMo', 'momo-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'access_key' => array(
                'title'       => __('Access Key', 'momo-woocommerce'),
                'type'        => 'text',
                'description' => __('Nhập Access Key từ MoMo', 'momo-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'secret_key' => array(
                'title'       => __('Secret Key', 'momo-woocommerce'),
                'type'        => 'password',
                'description' => __('Nhập Secret Key từ MoMo', 'momo-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'auto_complete' => array(
                'title'       => __('Auto Complete Order', 'momo-woocommerce'),
                'label'       => __('Tự động hoàn thành đơn hàng', 'momo-woocommerce'),
                'type'        => 'checkbox',
                'description' => __('Tự động chuyển trạng thái đơn hàng sang "Completed" khi thanh toán thành công', 'momo-woocommerce'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'webhook_url' => array(
                'title'       => __('Webhook URL', 'momo-woocommerce'),
                'type'        => 'text',
                'description' => __('Copy URL này và cấu hình trong MoMo Partner Portal', 'momo-woocommerce'),
                'default'     => home_url('/momo-webhook/'),
                'custom_attributes' => array('readonly' => 'readonly'),
                'desc_tip'    => true,
            ),
        );
    }
    
    /**
     * Process the payment and return the result
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        // Gọi API MoMo để tạo thanh toán
        $momo_api = new MoMo_API($this->partner_code, $this->access_key, $this->secret_key, $this->testmode);
        $result = $momo_api->create_payment($order);
        
        if ($result && isset($result['payUrl'])) {
            // Lưu thông tin giao dịch
            $order->update_meta_data('_momo_order_id', $result['orderId']);
            $order->update_meta_data('_momo_request_id', $result['requestId']);
            $order->save();
            
            // Mark as on-hold
            $order->update_status('on-hold', __('Chờ thanh toán MoMo', 'momo-woocommerce'));
            
            // Reduce stock levels
            wc_reduce_stock_levels($order_id);
            
            // Remove cart
            WC()->cart->empty_cart();
            
            // Return redirect
            return array(
                'result'   => 'success',
                'redirect' => $result['payUrl']
            );
        } else {
            $error_message = isset($result['message']) ? $result['message'] : __('Không thể kết nối với MoMo', 'momo-woocommerce');
            wc_add_notice($error_message, 'error');
            return array(
                'result' => 'fail',
                'redirect' => ''
            );
        }
    }
    
    /**
     * Output for the order received page
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order->get_status() === 'on-hold') {
            echo '<div class="woocommerce-info">';
            echo '<p>' . __('Vui lòng quét mã QR hoặc mở ứng dụng MoMo để hoàn tất thanh toán.', 'momo-woocommerce') . '</p>';
            echo '<p>' . __('Đơn hàng sẽ tự động cập nhật khi thanh toán thành công.', 'momo-woocommerce') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add content to the WC emails
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($this->id !== $order->get_payment_method() || $sent_to_admin || $order->get_status() !== 'on-hold') {
            return;
        }
        
        if ($plain_text) {
            echo __('Vui lòng hoàn tất thanh toán qua MoMo để xử lý đơn hàng.', 'momo-woocommerce') . "\n\n";
        } else {
            echo '<p>' . __('Vui lòng hoàn tất thanh toán qua MoMo để xử lý đơn hàng.', 'momo-woocommerce') . '</p>';
        }
    }
    
    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', __('Đơn hàng không hợp lệ', 'momo-woocommerce'));
        }
        
        $momo_api = new MoMo_API($this->partner_code, $this->access_key, $this->secret_key, $this->testmode);
        $result = $momo_api->refund_payment($order, $amount, $reason);
        
        if ($result && isset($result['resultCode']) && $result['resultCode'] == 0) {
            $order->add_order_note(sprintf(__('Hoàn tiền MoMo: %s VNĐ. Lý do: %s', 'momo-woocommerce'), number_format($amount), $reason));
            return true;
        } else {
            $error_message = isset($result['message']) ? $result['message'] : __('Hoàn tiền thất bại', 'momo-woocommerce');
            return new WP_Error('refund_failed', $error_message);
        }
    }
}
