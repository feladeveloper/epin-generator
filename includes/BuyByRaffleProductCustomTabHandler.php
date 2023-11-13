<?php 
/**
 * BuyByRaffleCustomTabHandler Class
 *
 * This class manages custom fields for WooCommerce products related to Hero associations.
 *
 * @package    BuyByRaffle
 * @subpackage WooCommerce
 * @since      1.0.0
 * @author     Mzer Michael
 */
namespace Sgs\Buybyraffle;
class BuyByRaffleProductCustomTabHandler {
    /**
     * Constructor.
     *
     * Initializes hooks for adding and saving custom fields.
     */
    public function __construct() {
        add_action('woocommerce_process_product_meta', array($this, 'saveCustomFieldForHeroAssociation'), 11);
        add_filter('woocommerce_product_data_tabs', array($this, 'addCustomDataTabs'), 50);
        add_action('woocommerce_product_data_panels', array($this, 'customDataTabContent'));
    }

   

    /**
     * Adds custom data tabs for Hero Association in WooCommerce products.
     * 
     * @param array $tabs Existing tabs.
     * @return array Updated tabs.
     */
    public function addCustomDataTabs($tabs) {
        $tabs['custom_data'] = array(
            'label' => __('Linked Hero', 'woocommerce'),
            'target' => 'link_hero_data_options',
            'class' => array('show_if_simple', 'show_if_variable'),
            'priority' => 60,
        );
        
        return $tabs;
    }

    /**
     * Outputs the content for our custom product data tab.
     */
    public function customDataTabContent() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'buybyraffle_hero_products';
        $hero_products = $wpdb->get_results("SELECT product_id FROM $table_name WHERE status='open'");
    
        if ($wpdb->last_error) {
            error_log("Database error: " . $wpdb->last_error);
            return;
        }
    
        if (empty($hero_products)) {
            error_log("No open hero products found.");
            return;
        }
    
        $options = array('' => 'Select a Hero Product');
        foreach ($hero_products as $product) {
            $options[$product->product_id] = $product->product_id;
        }
    
        echo '<div id="link_hero_data_options" class="panel woocommerce_options_panel">';
        woocommerce_wp_select(
            array(
                'id' => 'hero_product_id',
                'label' => __('Associated Hero Product ID', 'woocommerce'),
                'options' => $options,
            )
        );
        echo '</div>';
    }
    

    /**
     * Save Custom Field for Hero
     *
     * Saves the custom field value (Hero Product ID) when the product is saved.
     *
     * @param int $post_id The post ID of the product.
     */
    public function saveCustomFieldForHeroAssociation($post_id) {
        try {
            if (!isset($_POST['hero_product_id']) || empty($post_id)) {
                throw new Exception("Missing or invalid POST data or Post ID");
            }

            $hero_product_id = sanitize_text_field($_POST['hero_product_id']);
            if (empty($hero_product_id)) {
                throw new Exception("Hero Product ID cannot be empty");
            }

            update_post_meta($post_id, 'hero_product_id', esc_attr($hero_product_id));

        } catch (Exception $e) {
            error_log("Caught exception in saveCustomFieldForHero in BuyByRaffleProductCustomTabHandler class: " . $e->getMessage());
        }
    }

}
