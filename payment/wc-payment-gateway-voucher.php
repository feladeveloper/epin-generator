<?php
/**
 * WooCommerce Payment Gateway - Pay with Voucher
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * WC_Gateway_PGS_Voucher class.
 *
 * Defines a WooCommerce Payment Gateway for paying with a voucher.
 */
class WC_Gateway_PGS_Voucher extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'pgs_voucher';
        $this->icon               = apply_filters('woocommerce_pgs_voucher_icon', '');
        $this->has_fields         = true;
        $this->method_title       = __('PGS Voucher', 'woocommerce');
        $this->method_description = __('Allows payments with voucher e-pins.', 'woocommerce');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_pgs_voucher', array($this, 'thankyou_page'));
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable PGS Voucher Payment', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default'     => __('PGS Voucher Payment', 'woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on the checkout page.', 'woocommerce'),
                'default'     => __('Enter your voucher pin to pay for your order.', 'woocommerce'),
            ),
            'instructions' => array(
                'title'       => __('Instructions', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                'default'     => __('Enter your voucher pin to pay for your order.', 'woocommerce'),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page() {
        if ($this->instructions) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
    }

    
    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $voucher_pin = isset($_POST['voucher_pin']) ? sanitize_text_field($_POST['voucher_pin']) : '';

        // Call the validate and redeem voucher method from the PGS Vouchers plugin.
        $redeem_result = pgs_redeem_voucher($voucher_pin);

        if ($redeem_result['success']) {
            // Mark as completed (payment has been made)
            $order->update_status('completed', __('Voucher payment completed', 'wc-gateway-pgs'));

            // Reduce ticket stock levels and remove cart
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();

            // Return success and redirect to the thank you page
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            // Voucher redeeming failed, add an error notice
            wc_add_notice($redeem_result['message'], 'error');
            return array(
                'result'   => 'failure'
            );
        }
    }



    /**
     * Payment fields on the checkout page.
     */
    public function payment_fields() {
        echo '<div id="pgs_voucher_payment_fields" class="form-row form-row-wide">';
        echo '<fieldset>';
        
        if ($this->description) {
            echo '<p>' . esc_html($this->description) . '</p>';
        }

        woocommerce_form_field('voucher_pin', array(
            'type'        => 'text',
            'label'       => __('Voucher PIN', 'woocommerce'),
            'required'    => true,
            'placeholder' => __('Enter your voucher pin here', 'woocommerce'),
        ), '');

        echo '</fieldset>';
        echo '</div>';
    }


        // Additional methods and hooks can be added as needed.
}

/**
 * Add the new gateway to WooCommerce
 */
function wc_add_pgs_voucher_gateway($methods) {
    $methods[] = 'WC_Gateway_PGS_Voucher';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'wc_add_pgs_voucher_gateway');
