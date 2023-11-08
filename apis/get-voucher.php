<?php
// Register a custom REST API route to get voucher details by voucher_pin
function get_voucher_details($request) {
    // Get the voucher_pin from the request parameters
    $voucher_pin = $request->get_param('voucher_pin');
    if (empty($voucher_pin)) {
        return new WP_REST_Response('voucher_pin parameter is required', 404);
    }
    // Query the database to fetch the voucher details
    global $wpdb;
    $voucher_table_name = $wpdb->prefix . 'epin_vouchers';
    $voucher_details = $wpdb->get_row(
        $wpdb->prepare("SELECT status, batch_id, date_created FROM $voucher_table_name WHERE voucher_pin = %s", $voucher_pin),
        ARRAY_A
    );

    if (empty($voucher_details)) {
        return new WP_REST_Response('Voucher not found', 404);
    }

    // Return the voucher details as a JSON response
    return new WP_REST_Response($voucher_details, 200);
}

// Register the custom REST API endpoint
function register_retrieve_voucher_routes() {
    register_rest_route('pgs/v1', '/voucher', array(
        'method' => 'GET',
        'callback' => 'get_voucher_details',
        'permission_callback' => '__return_true'
         
    ));
}
add_action('rest_api_init', 'register_retrieve_voucher_routes');
