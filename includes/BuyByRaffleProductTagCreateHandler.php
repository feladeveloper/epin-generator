<?php
/**
 * Class BuyByRaffleProductTagCreateHandler
 *
 * Handles product tags for the BuyByRaffle plugin.
 *
 * @author Terungwa
 */
namespace Sgs\Buybyraffle;
use Exception;
class BuyByRaffleProductTagCreateHandler {

    /**
     * BuyByRaffleProductTagCreateHandler constructor.
     *
     * Initializes tag properties.
     */
    public function __construct() {
        // Initialization logic here, if needed
    }

    /**
     * Installation method for the plugin.
     *
     * Calls the create_product_tags method during plugin activation.
     *
     * @throws Exception If an error occurs
     */
    public static function install() {
        try {
            $instance = new self();
            $instance->create_product_tags();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates new custom product tags.
     *
     * Executes tag creation.
     *
     * @throws Exception If any of the operations fail
     */
    public function create_product_tags() {
        try {
            $this->insert_tags();
        } catch (Exception $e) {
            error_log("Caught exception in create_product_tags of BuyByRaffleProductTagCreateHandler class: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Inserts terms (Hero and Bait) for the custom tags.
     *
     * @throws Exception If term insertion fails
     */
    protected function insert_tags() {
        if (!term_exists('Hero', 'product_tag')) {
            $result = wp_insert_term('Hero', 'product_tag');
            if (is_wp_error($result)) {
                throw new Exception("Failed to insert 'Hero' tag: " . $result->get_error_message());
            }
        }

        if (!term_exists('Bait', 'product_tag')) {
            $result = wp_insert_term('Bait', 'product_tag');
            if (is_wp_error($result)) {
                throw new Exception("Failed to insert 'Bait' tag: " . $result->get_error_message());
            }
        }
    }
}

// Hook to run on plugin activation
//register_activation_hook(__FILE__, array('BuyByRaffleProductTagCreateHandler', 'install'));
