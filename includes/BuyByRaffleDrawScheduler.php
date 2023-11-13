<?php

/**
 * Class BuyByRaffleDrawScheduler
 * 
 * Handles the scheduling of buy by raffle draw events when product stock status changes.
 */
namespace Sgs\Buybyraffle;
class BuyByRaffleDrawScheduler {

  
  /**
   * Constructor for the BuyByRaffleDrawScheduler class.
   * 
   * Sets up the action hook for when the product stock status changes.
   */
  public function __construct() {
    add_action( 'woocommerce_product_stock_status_changed', array( $this, 'woocommerce_product_stock_status_changed' ), 10, 3 );
  }

  /**
   * Handles the stock status change action.
   * 
   * Checks if the product has the "bait" tag and if the stock status has changed to "out of stock".
   * If conditions are met, it creates a draw event in the database and publishes a draw event message to Google Pub/Sub.
   *
   * @param int    $product_id        The ID of the product whose stock status has changed.
   * @param string $old_stock_status  The previous stock status of the product.
   * @param string $new_stock_status  The new stock status of the product.
   */
  public function woocommerce_product_stock_status_changed( $product_id, $old_stock_status, $new_stock_status ) {
    global $wpdb;

    // Check if the product has a "bait" tag and the new stock status is 'outofstock'.
    if ( has_term( 'bait', 'product_tag', $product_id ) && 'outofstock' === $new_stock_status ) {
      // Insert a draw event into the wp_buybyraffle_queued_raffles table.
      $insert_result = $wpdb->insert(
        'wp_buybyraffle_queued_raffles',
        array(
          'raffle_cycle_id' => $product_id,
          'status'          => 'waiting',
          'created_date'    => current_time( 'mysql' ),
          'updated_date'    => current_time( 'mysql' )
        ),
        array( '%d', '%s', '%s', '%s' )
      );

      // Check if the insert was successful before posting to Google Pub/Sub.
      if ( $insert_result ) {
        $insert_id = $wpdb->insert_id;
        // Define the draw event message.
        $draw_event_message = array(
          'id' => $insert_id
        );

        // Define the remote POST arguments.
        $api_key = 'YOUR_API_KEY'; // Replace with your actual API key.
        $post_args = array(
          'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
          ),
          'body' => json_encode( $draw_event_message )
        );

       // Publish the draw event message to the draw engine topic.
      wp_remote_post( 'https://pubsub.googleapis.com/v1/projects/PROJECT_ID/topics/draw-engine', $post_args );

      // Publish the draw event message to the notification topic.
      wp_remote_post( 'https://pubsub.googleapis.com/v1/projects/PROJECT_ID/topics/notification', $post_args );
      }
    }
  }
}



