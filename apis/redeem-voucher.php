<?php
// Register a custom REST API route to use a voucher
function pgs_redeem_voucher($request) {
    // Get the voucher_pin from the request parameters
    $voucher_pin = $request->get_param('voucher_pin');

    // Query the database to check if the voucher exists and is active
    global $wpdb;
    $voucher_table_name = $wpdb->prefix . 'epin_vouchers';

    $existing_voucher = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $voucher_table_name WHERE voucher_pin = %s AND status = 'active'", $voucher_pin),
        ARRAY_A
    );

    if (empty($existing_voucher)) {
        return new WP_REST_Response('Voucher not found or already used', 404);
    }

    // Update the voucher status to "used"
    $wpdb->update(
        $voucher_table_name,
        array('status' => 'used'),
        array('voucher_pin' => $voucher_pin)
    );

    // Return a success message
    return new WP_REST_Response('Voucher redeemed successfully', 200);
}

// Register the custom REST API endpoint
function register_redeem_voucher_route() {
    register_rest_route('pgs/v1', '/redeem-voucher', array(
        'methods' => 'POST',
        'callback' => 'pgs_redeem_voucher',
        'args' => array(
            'voucher_pin' => array(
                'required' => true,
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_redeem_voucher_route');
