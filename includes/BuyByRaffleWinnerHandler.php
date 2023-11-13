<?php
/**
 * BuyByRaffleWinnerHandler Class
 *
 * This class is responsible for handling winners in the BuyByRaffle application.
 * It has methods for recording winners into the database.
 *
 * Features:
 * - Record winners in the 'raffle_winners' table.
 * - Could be extended to handle notifications, auditing, and analytics.
 *
 * @author Terungwa
 */
namespace Sgs\Buybyraffle;
class BuyByRaffleWinnerHandler {
    
    /**
     * Record Winner
     *
     * This method records a winner in the 'raffle_winners' table.
     * It takes in the raffle ID, user ID, and product ID as arguments.
     * These details are then inserted into the 'raffle_winners' table.
     *
     * @param int $raffle_id The ID of the raffle that was won.
     * @param int $user_id The ID of the user who won the raffle.
     * @param int $product_id The ID of the product that was won (either Bait or Hero).
     */
    public function recordWinner($raffle_id, $user_id, $product_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'raffle_winners';
        
        // Insert the winner details into the 'raffle_winners' table
        $wpdb->insert(
            $table_name,
            array(
                'raffle_id' => $raffle_id,
                'user_id' => $user_id,
                'product_id' => $product_id
            ),
            array('%d', '%d', '%d')
        );
    }
}
