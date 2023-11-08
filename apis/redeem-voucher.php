<?php
/**
 * Redeems a voucher using a provided voucher pin.
 * 
 * This function handles the request to redeem a voucher by changing its status
 * from 'active' to 'used'. It checks if the voucher exists and is active before 
 * proceeding with the update operation.
 *
 * @param WP_REST_Request $request The request object containing 'voucher_pin'.
 * @return WP_REST_Response The response object with the operation result.
 */
function pgs_redeem_voucher($request) {
    $voucher_pin = sanitize_text_field($request->get_param('voucher_pin'));

    global $wpdb;
    $voucher_table_name = $wpdb->prefix . 'epin_vouchers';

    // Check if the voucher exists and is active.
    $existing_voucher = $wpdb->get_row(
        $wpdb->prepare("SELECT id FROM $voucher_table_name WHERE voucher_pin = %s AND status = 'active'", $voucher_pin),
        ARRAY_A
    );

    // Respond with an error if the voucher is not found or already used.
    if (is_null($existing_voucher)) {
        return new WP_REST_Response('Voucher not found or already used', 404);
    }

    // Attempt to update the voucher status to 'used'.
    $updated = $wpdb->update(
        $voucher_table_name,
        array('status' => 'used'),
        array('voucher_pin' => $voucher_pin)
    );

    // Respond with an error if the update failed.
    if ($updated === false) {
        return new WP_REST_Response('Failed to redeem voucher', 500);
    }

    // Return a success message if the voucher was redeemed.
    return new WP_REST_Response('Voucher redeemed successfully', 200);
}

/**
 * Registers the redeem voucher route with the WordPress REST API.
 * 
 * This function sets up the REST API route for redeeming vouchers. It defines the HTTP method,
 * the callback to be used, the permission callback to check for user capabilities, and the arguments 
 * for the request parameters.
 */
function register_redeem_voucher_route() {
    register_rest_route('pgs/v1', '/redeem-voucher', array(
        'methods' => ['POST'],
        'callback' => 'pgs_redeem_voucher',
        'permission_callback' => function () {
            // Check if the current user has the 'manage_options' capability.
            return current_user_can('manage_options');
        },
        'args' => array(
            'voucher_pin' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_redeem_voucher_route');
