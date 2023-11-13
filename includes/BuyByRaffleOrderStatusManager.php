<?php 
/**
 * Class BuyByRaffleOrderStatusManager
 *
 * This class is responsible for updating the ticket status in the `wp_buybyraffle_tickets` table
 * when an order status is changed.
 *
 * @author Terungwa
 */
namespace Sgs\Buybyraffle;
class BuyByRaffleOrderStatusManager {

    /**
     * Constructor
     *
     * Registers the methods to the appropriate WooCommerce hooks.
     */
    public function __construct() {
        add_action('woocommerce_order_status_changed', array($this, 'change_ticket_status'), 10, 3);
    }

    /**
     * Change Ticket Status
     *
     * This method changes the status of the raffle tickets associated with the order.
     *
     * @param int $order_id The ID of the order being changed.
     * @param string $old_status The old order status.
     * @param string $new_status The new order status.
     */
    public function change_ticket_status($order_id, $old_status, $new_status) {
        global $wpdb;

        // Determine the new ticket status based on the order status
        $ticket_status = null;
        if ('completed' === $new_status) {
            // If the order is completed, we set the ticket status to 1
            $ticket_status = 1;
        } elseif ('completed' !== $new_status) {
            // If the order is changed from completed to any other status, we set the ticket status to 0
            $ticket_status = 0;
        } // Add any other conditions for status 2 if necessary

        // Proceed only if ticket_status is determined
        if (null !== $ticket_status) {
            $result = $wpdb->update(
                "{$wpdb->prefix}buybyraffle_tickets",
                array('status' => $ticket_status), // Set status based on the order status
                array('ticket_id' => $order_id, 'status' => 1), // Where the ticket_id is the order_id and current status is 1
                array('%d'), // Format of the 'status' value
                array('%d', '%d') // Formats of the 'ticket_id' and 'status' values
            );

            if (false === $result) {
                // Handle the error, maybe log it
                error_log("Failed to update ticket status for Order ID {$order_id}.");
            } else {
                // Success, maybe log it or take other actions
            }
        }
    }
}


