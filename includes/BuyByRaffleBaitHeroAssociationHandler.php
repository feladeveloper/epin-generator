<?php
/**
 * Class BuyByRaffleBaitHeroAssociationHandler
 *
 * Handles bait-hero associations and updates raffle statuses.
 *
 * @author Terungwa
 */
namespace Sgs\Buybyraffle;
class BuyByRaffleBaitHeroAssociationHandler {
    /**
     * Constructor
     *
     * Registers the methods to the appropriate WordPress hooks.
     */
    public function __construct() {
        add_action('before_delete_post', array($this, 'remove_bait_hero_association'));
        add_action('transition_post_status', array($this, 'handle_unpublish'), 10, 3);
    }




    /**
     * Remove Bait-Hero Association
     *
     * Removes the bait-hero association when a bait or hero product is deleted.
     *
     * @param int $post_ID The ID of the post being deleted.
     */
    public function remove_bait_hero_association($post_ID) {
        global $wpdb;
        $post_type = get_post_type($post_ID);
        if ('product' === $post_type) {
            // Update the status of the bait-hero association for this bait product to 'inactive' or 'deleted'
            $wpdb->update(
                'wp_buybyraffle_bait_hero_association',
                array('status' => 'deleted'),  // or 'deleted', depending on your status taxonomy
                array('bait_id' => $post_ID),
                array('%s'),  // value type
                array('%d')   // where type
            );
        }
    }

   /**
     * Handle Unpublish Event
     *
     * Called when a product is unpublished, to update the status of any stale bait-hero associations to 'inactive'.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post object.
     */
    public function handle_unpublish($new_status, $old_status, $post) {
        global $wpdb;
        if ($post->post_type !== 'product') return;

        if ($new_status !== 'publish' && $old_status === 'publish') {
            // Update the status of the bait-hero association for this bait product to 'inactive'
            $wpdb->update(
                'wp_buybyraffle_bait_hero_association',
                array('status' => 'unpublished'),  // or 'deleted', depending on your status taxonomy
                array('bait_id' => $post->ID),
                array('%s'),  // value type
                array('%d')   // where type
            );
        }
    }

}

