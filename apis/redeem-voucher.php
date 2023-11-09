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
function pgs_redeem_voucher($voucher_pin) {
    global $wpdb;
    $voucher_table_name = $wpdb->prefix . 'epin_vouchers';

    // Check if the voucher exists and is active.
    $voucher = $wpdb->get_row($wpdb->prepare(
        "SELECT id, status FROM $voucher_table_name WHERE voucher_pin = %s", 
        $voucher_pin
    ));

    // Return an error if the voucher is not found or already used.
    if (!$voucher || $voucher->status !== 'active') {
        return array(
            'success' => false,
            'message' => 'Voucher not found or already used'
        );
    }

    // Attempt to update the voucher status to 'used'.
    $updated = $wpdb->update(
        $voucher_table_name,
        array('status' => 'used'),
        array('id' => $voucher->id)
    );

    // Return an error if the update failed.
    if ($updated === false) {
        return array(
            'success' => false,
            'message' => 'Failed to redeem voucher'
        );
    }

    // Voucher was redeemed successfully.
    return array(
        'success' => true,
        'message' => 'Voucher redeemed successfully'
    );
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
