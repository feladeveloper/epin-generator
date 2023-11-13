<?php
/**
 * Class BuyByRaffleHeroProductHandler
 * Handles 'Hero' products in the "BuyByRaffle Product Group" attribute.
 * @author Terungwa
 */
namespace Sgs\Buybyraffle;
use WP_Post;
use Exception;
use WP_Query;
use PDOException;
use WP_Error;
class BuyByRaffleHeroProductHandler {
    /**
     * Constructor
     * Adds WordPress actions and filters.
     * @author Terungwa
     */
    private $cycleHandler;
    public function __construct($cycleHandler) {
        $this->cycleHandler = $cycleHandler;
        // Remove Hero products from archives
        add_action('pre_get_posts', array($this, 'remove_from_archives_and_search'));

        // Make Hero products non-purchasable
        add_filter('woocommerce_is_purchasable', array($this, 'make_non_purchasable'), 10, 2);

        // Registers the create_product_configuration method to the save_post action hook.
        add_action("save_post_product", array($this, "save_bbr_config_custom_fields"), 10, 3); // Priority 10
        //add_action('woocommerce_product_data_panels', 'bbr_config_product_data_fields');
        add_action('woocommerce_product_data_panels', array($this, 'bbr_config_product_data_fields'));

        //add_filter('woocommerce_product_data_tabs', 'add_bbr_config_product_data_tab');
        add_filter('woocommerce_product_data_tabs', array($this, 'add_bbr_config_product_data_tab'));

        add_action('save_post_product', array($this, 'associate_baits_with_hero'), 20, 3);

        // Prevent deletion of Hero products with certain statuses
        add_action('before_delete_post', array($this, 'prevent_hero_deletion'));

        //add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_fetch_hero_products', array($this, 'fetch_hero_products_ajax_handler'));
        

    }
    
    public function add_bbr_config_product_data_tab($product_data_tabs) {
        $product_data_tabs['bbr_config_tab'] = array(
            'label' => __('BBR Config', 'your-domain'),
            'target' => 'bbr_config_product_data',
            'class' => array(),
            'priority' => 21,
        );
        return $product_data_tabs;
    }
    public function save_bbr_config_custom_fields() {
        //error_log(print_r($_POST, true));
        // If the save_post action is triggered without a POST request, return early.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Prevent execution during WordPress's auto-save routine
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
            return;
        }
         // Add additional checks for bulk or quick edit
        if (doing_action('bulk_edit') || doing_action('quick_edit')) {
            return;
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        $post_id = $_POST['post_ID'];
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Verify the nonce to ensure the request originated from the correct screen
        if (!isset($_POST['bbr_config_nonce'])) {
            error_log("Nonce verification is not set in save_bbr_config_custom_fields.");
            return;
        }  
        if (!wp_verify_nonce($_POST['bbr_config_nonce'], 'bbr_config_nonce_action')) {
            error_log("Nonce verification failed in save_bbr_config_custom_fields.");
            return;
        }       
       
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            error_log("User lacks permission in save_bbr_config_custom_fields.");
            return;
        }
        
        try {
            // Sanitize and save the custom fields using the existing meta keys
            if (isset($_POST['product-tag'])) {
                $tag = sanitize_text_field($_POST['product-tag']);
                update_post_meta($post_id, 'product-tag', $tag);
            }
            // Sanitize and save the custom fields using the existing meta keys
            if (isset($_POST['product-tag'])) {
                $tag = sanitize_text_field($_POST['product-tag']);
                // Update the product-tag post meta
                update_post_meta($post_id, 'product-tag', $tag);

                // Use wp_set_post_terms to set the product tag term
                // Assuming 'product_tag' is the taxonomy slug for your product tags
                wp_set_post_terms($post_id, [$tag], 'product_tag', false);
            }
            
               // Ensure that the 'associated_hero_id' is present when 'bait' is selected
            if ($tag === 'bait') {
                if (isset($_POST['associated_hero_id'])) {
                    //error_log($_POST['associated_hero_id']);
                    $hero_id = intval($_POST['associated_hero_id']);
                    $raffle_class_id = BuyByRaffleRaffleClassMgr::get_raffle_class_id_by_name('bait');
                    if ($hero_id <= 0) {
                        throw new Exception('Invalid Hero Product ID provided.');
                    }
                    update_post_meta($post_id, 'associated_hero_id', $hero_id);
                   $raffle_cycle_id_bait = $this->create_product_configuration($post_id, $raffle_class_id, get_post($post_id), $this->cycleHandler, false);
                    error_log($raffle_cycle_id_bait);
                // Assuming create_product_configuration returns false or a null-equivalent value on failure
                if ($raffle_cycle_id_bait) {
                    // Function was successful, proceed to the next step
                    $this->associate_baits_with_hero($post_id, $hero_id, $raffle_cycle_id_bait);
                } else {
                    // Function failed, handle the error accordingly
                    // For example, you might log the error or take some other action
                    $message = "Failed to create product configuration for Product ID: $post_id therefore association also failed";
                    \sgs\BuyByRaffle\BuyByRaffleLogger::log($message, "Adding a bait product configuration");
                }

                } else {
                    throw new Exception('Hero Product ID must be selected when "bait" tag is selected.');
                }
            } elseif ($tag === 'hero') {
                //error_log('POST Data oooo: ' . print_r(get_post($post_id), true));
                // Handle the hero logic if required, potentially setting $hero_id to $post_id
                $hero_id = $post_id;
                // Retrieve the raffle class ID for 'bait'
                $raffle_class_id = BuyByRaffleRaffleClassMgr::get_raffle_class_id_by_name('hero');
                $this->create_product_configuration($post_id, $raffle_class_id, get_post($post_id), $this->cycleHandler, false);
                //update_post_meta($post_id, 'associated_hero_id', $hero_id);
            }elseif ($tag === 'solo') {
                // Ensure no association for 'solo' tags
                delete_post_meta($post_id, 'associated_hero_id');
            }
        } catch (Exception $e) {
            error_log("Exception caught in save_bbr_config_custom_fields: " . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error is-dismissible">';
                echo "<p>Error: " . esc_html($e->getMessage()) . "</p>";
                echo '</div>';
            });
            // Don't throw a new exception here. Handle the error gracefully.
        }
    }
    
    public function bbr_config_product_data_fields() {
        global $post;
    
        // Check if $post is a valid object and of type WP_Post
        if (!is_object($post) || !is_a($post, 'WP_Post')) {
            error_log('Invalid post object in BuyByRaffleHeroProductHandler.');
            // Handle the error appropriately, possibly with a user-facing error message or by exiting the function
            echo '<div class="error"><p>Invalid post object. Please make sure you are editing a valid product.</p></div>';
            return; // Exit the function if $post is not a valid WP_Post object
        }
    
        // Fetch the current values if $post is valid
        $current_tag = get_post_meta($post->ID, 'product-tag', true);
        $current_hero_id = get_post_meta($post->ID, 'associated_hero_id', true);
    
        // Get hero products for the dropdown
        $hero_products = $this->get_hero_products();
        ?>
        <div id="bbr_config_product_data" class="panel woocommerce_options_panel">
            <div class='options_group'>
                <?php
                // Inside the bbr_config_product_data_fields method, just before or after the select fields
                wp_nonce_field('bbr_config_nonce_action', 'bbr_config_nonce');

                woocommerce_wp_select(array(
                    'id' => 'product-tag',
                    'label' => __('Product Tag', 'your-domain'),
                    'options' => array(
                        '' => 'Select a tag',
                        'hero' => 'Hero',
                        'bait' => 'Bait',
                        'solo' => 'Solo' // Add the new 'solo' option
                    ),
                    'value' => $current_tag,
                    'custom_attributes' => array('required' => 'required'), // Ensure the select is always required
                ));
    
                woocommerce_wp_select(array(
                    'id' => 'associated_hero_id',
                    'label' => __('Select Hero Product', 'your-domain'),
                    'options' => array_reduce($hero_products, function ($options, $hero_product) {
                        // Use array syntax instead of object property syntax
                        $options[$hero_product['ID']] = $hero_product['name']; // or $hero_product['post_title'] if that is the correct index
                        return $options;
                    }, array('' => 'Select a Hero Product')),
                    'value' => $current_hero_id,
                ));

                ?>
            </div>
        </div>
        <?php
    }
    
    
    public function fetch_hero_products_ajax_handler() {
        global $wpdb;
        check_ajax_referer('my_ajax_nonce', 'nonce'); // Check nonce for security
    
        // Prepare SQL with JOIN to get the product names from wp_posts table
        $query = "
            SELECT h.product_id, p.post_title AS product_name
            FROM wp_buybyraffle_product_config h
            JOIN wp_posts p ON h.product_id = p.ID
            WHERE h.status = 'open' AND p.post_status = 'publish' AND p.post_type = 'product'
        ";
        
        $hero_products = $wpdb->get_results($query);
        
        // Check if the products were found
        if(!empty($hero_products)) {
            wp_send_json_success($hero_products);
        } else {
            error_log('No hero products found.');
            wp_send_json_error('No hero products found.');
           
        }
    }
    
    public function get_hero_products() {
        global $wpdb;
        // Define the table name
        $table_name = $wpdb->prefix . 'buybyraffle_product_config';
    
        // Write a SQL query to get all hero IDs where the status is 'active'
        $sql = $wpdb->prepare("SELECT DISTINCT product_id FROM $table_name WHERE status = %s AND raffle_class_id = %d", 'open', 2);
    
        // Execute the query and get the results
        $hero_ids = $wpdb->get_col($sql);
        //error_log(print_r($hero_ids, true));
        // Check for any database errors
        if ($wpdb->last_error) {
            error_log("Database error: " . $wpdb->last_error);
            return array();
        }
    
        // If hero IDs are found, get the corresponding post objects
        if (!empty($hero_ids)) {
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'post__in' => $hero_ids,
                'posts_per_page' => -1, // Get all hero products
            );
    
            $query = new WP_Query($args);
    
            $hero_products = array();
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $hero_products[] = array(
                        'ID' => get_the_ID(),
                        'name' => get_the_title(),
                        // Add other product fields as needed
                    );
                }
                wp_reset_postdata();
            }
            return $hero_products;
        } else {
            // No hero products found
            return array();
        }
    }
    
   /**
     * Prevent Deletion of Hero Products
     * 
     * @param int $post_id The ID of the post being deleted.
     * @author Terungwa
     */
    public function prevent_hero_deletion($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'buybyraffle_hero_products';
        $existing_product = $wpdb->get_var("SELECT status FROM {$table_name} WHERE product_id = $post_id");

        // Prevent deletion if the status is 'running' or 'redeemed'
        if ($existing_product && in_array($existing_product, ['running', 'redeemed'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo "<p>You cannot delete this Hero product because its status is either 'running' or 'redeemed'.</p>";
                echo '</div>';
            });
            return; // Exit the function early
        }
    }

    /**
     * Add Hero Product
     *
     * Adds a new entry to the buybyraffle_hero_products table each time a Hero product is created.
     *
     * @param int     $post_ID The ID of the post being saved.
     * @param int     $raffle_class_id The raffle class ID for the product.
     * @param WP_Post $post The post object.
     * @param bool    $update Whether this is an existing post being updated or not.
     */
    public function create_product_configuration($post_ID, $raffle_class_id, $post, $cycleHandler, $update) {
        // If it's not a product or if it's a WordPress autosave, return early
        //error_log(print_r($post, true));
        if ($post->post_type !== 'product' || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return $post_ID;
        }            

        try{
            global $wpdb;
            $table_name = $wpdb->prefix . 'buybyraffle_product_config';
            // Using 'product_tag' taxonomy to check for 'Hero' tag
            $terms = get_the_terms($post_ID, 'product_tag');
            //error_log('Terms Data here: ' . print_r($terms, true));
            if (is_wp_error($terms)) {
                throw new Exception("Error retrieving the tags: " . $terms->get_error_message());
            }

            
           // Check if the $post_ID is already in the database with any status other than 'redeemed'
            $existing_open_or_running_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %d AND status NOT IN ('redeemed')", $post_ID));

            if ($existing_open_or_running_entry) {
                // Entry exists with a status other than 'redeemed' - log as a duplicate request
                $message = "Duplicate request: Entry already exists for product ID: $post_ID with status not 'redeemed'.";
                \Sgs\Buybyraffle\BuyByRaffleLogger::log($message, 'Creating a product');

            } else {
                // Use the cycleHandler to insert and get the ID
                // Data to insert into the external database
                $insertData = [
                    'product_id' => $post_ID,
                    'status' => 'pending', // Define your status
                    'raffle_class_id' => $raffle_class_id,
                ];
                //('insertData Data here: ' . print_r($insertData, true));
                $raffle_cycle_id = $this->cycleHandler->createRaffleCycle($insertData);
                //error_log(print_r($raffle_cycle_id), true);
                if (intval($raffle_cycle_id[1]) !== 200 && intval($raffle_cycle_id[1]) !== 201){
                    $message = "Raffle Cycle ID was not created. HTTP Error coder: " . $raffle_cycle_id[1];
                    \Sgs\Buybyraffle\BuyByRaffleLogger::log($message, 'Creating a product');
                   
                    return;
                }else{
                    $raffle_cycle_id = $raffle_cycle_id[0];
                    // Either no entry exists, or existing entries are only 'redeemed'
                    // Prepare the data for a new entry or update
                    $data = array(
                        'product_id' => $post_ID,
                        'raffle_cycle_id' => $raffle_cycle_id,
                        'status' => 'open',
                        'raffle_class_id' => $raffle_class_id
                    );
                    $format = array('%d', '%d', '%s', '%d');

                    // Check if an entry with status 'redeemed' exists
                    $existing_redeemed_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %d AND status = %s", $post_ID, 'redeemed'));

                    if ($existing_redeemed_entry) {
                        // Update the existing 'redeemed' entry to 'open'
                        $wpdb->update($table_name, $data, array('product_id' => $post_ID, 'status' => 'redeemed'), $format, array('%d', '%s'));
                        $message = "Updated entry from 'redeemed' to 'open' for product ID: $post_ID with raffle cycle ID: $raffle_cycle_id";                       
                        \Sgs\Buybyraffle\BuyByRaffleLogger::log($message, $user_action = 'Creating a product');
                    } else {
                        // Insert a new entry as no 'open' or 'running' entry exists for this product ID
                        $wpdb->insert($table_name, $data, $format);
                        error_log("Created a new entry for product ID: $post_ID with raffle cycle ID: $raffle_cycle_id");
                    }
                    return $raffle_cycle_id;

                    // Check for database errors
                    if ($wpdb->last_error) {
                        error_log("Database error: " . $wpdb->last_error);
                    }
                }
            }

            
        } catch (PDOException $e) {
            // Handle external database errors
            error_log("Connection failed in create_product_configuration method: " . $e->getMessage());
            return new WP_Error('external_db_error in create_product_configuration', $e->getMessage());
        } catch (Exception $e) {
            // Handle all other exceptions
            error_log("Caught exception in create_product_configuration method: " . $e->getMessage());
            return new WP_Error('hero_product_error in create_product_configuration', $e->getMessage());
        }
    }


    /**
     * Remove Hero products from archives and search.
     * 
     * @param \WP_Query $query WordPress Query object.
     */
    public function remove_from_archives_and_search($query) {
        if ($query->is_main_query() && !is_admin()) {
            $tax_query = array(
                array(
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => 'hero',
                    'operator' => 'NOT IN',
                ),
            );
            $query->set('tax_query', $tax_query);
        }
    }

    /**
     * Make Hero products non-purchasable.
     * 
     * @param bool       $purchasable Whether the product is purchasable.
     * @param \WC_Product $product     WooCommerce Product object.
     * @return bool
     */
    public function make_non_purchasable($purchasable, $product) {
        $terms = get_the_terms($product->get_id(), 'product_tag');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                if ($term->slug === 'hero') {
                    return false;
                }
            }
        }
        return $purchasable;
    }

    /**
     * Update Bait-Hero Association
     *
     * This method is called whenever a post is saved. It checks if the post is of type 'bait',
     * and updates the bait-hero association accordingly.
     *
     * @param int     $post_ID The ID of the post being saved.
     * @param WP_Post $post    The post object.
     * @param bool    $update  Whether this is an existing post being updated or not.
     */
    public function associate_baits_with_hero($post_ID, $hero_product_id, $raffle_cycle_id_bait) {
        global $wpdb;
        // Add additional checks for bulk or quick edit
        if (doing_action('bulk_edit') || doing_action('quick_edit')) {
            return;
        }
        if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return $post_ID;
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (wp_is_post_revision($post_ID)) {
            return;
        }
        
        try { 
            // First, check if it's a Hero product to avoid running this logic erroneously
            if ('hero' === get_post_meta($post_ID, 'product-tag', true)) {
                return; // Exit if it's a Hero product
            }
            $post = get_post($post_ID); // Get the post object to check its status.
            if (!$post || $post->post_status === 'auto-draft') {
                // It's an auto-draft, so we should stop further execution.
                return;
            }
            
            // Check if the product is a bait product
            //error_log("Post ID: ".$post_ID);
            if ($this->is_bait_product($post_ID)) {
                //throw new Exception("Product ID: $post_ID is not a bait product, and therefore cant be added to the wp_buybyraffle_bait_hero_association table.");
                 // Validate the input field when "bait" is selected
            
                //$hero_product_id = $_POST['hero_product_id'] ?? '';
                if (empty($hero_product_id)  || !isset($hero_product_id)) {
                    // Add a WordPress admin notice
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible">';
                        echo '<p>Error: You must fill in the Hero Product ID field when selecting the "bait" attribute.</p>';
                        echo '</div>';
                    });
                    
                    // Throw an exception to halt the save process
                    throw new Exception('Hero Product ID must be selected when "bait" tag is selected.');
                }
            
            }

            // Assuming the associated hero product ID is stored in post meta with key 'associated_hero_id'
            $hero_id = get_post_meta($post_ID, 'associated_hero_id', true);
            if (empty($hero_id)) {
                throw new Exception("Hero product ID is not set for this bait product: $post_ID");
            }
            // Check if the hero product status is 'open'
            //$hero_status = $wpdb->get_var("SELECT status FROM wp_buybyraffle_product_config WHERE hero_id = $hero_id");
            $hero_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM wp_buybyraffle_product_config WHERE product_id = %d AND status = %s AND raffle_class_id = %d", $hero_id, 'open', 2));

            if ($hero_status === null) {
                // Log or handle the case where hero product is not found
                throw new Exception("No hero product was associated to this bait product or you attempted to use one that is not open for association.");
            } 

            // Check if an association already exists
            $existing_association = $wpdb->get_var("SELECT id FROM wp_buybyraffle_bait_hero_association WHERE bait_id = $post_ID AND hero_id = $hero_id");

            if (null === $existing_association) {
                // Insert new association
                $wpdb->insert(
                    'wp_buybyraffle_bait_hero_association',
                    array(
                        'bait_id' => $post_ID,
                        'hero_id' => $hero_id,
                        'raffle_cycle_id_bait' => $raffle_cycle_id_bait,
                        'updated_date' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s')
                );
            } else {
                // Update existing association
                $wpdb->update(
                    'wp_buybyraffle_bait_hero_association',
                    array('updated_date' => current_time('mysql')),
                    array('id' => $existing_association),
                    array('%s'),
                    array('%d')
                );
            }
        } catch (Exception $e) {
            error_log("Caught exception in associate_baits_with_hero: " . $e->getMessage());
        }
    }

    /**
     * Check if a Product is a Bait Product
     *
     * This internal method checks if a given product ID represents a bait product.
     *
     * @param int $product_id The ID of the product to check.
     * @return bool True if the product is a bait product, false otherwise.
     */
        private function is_bait_product($product_id) {
        try {
            //error_log('This product ID is : '.$product_id);
            $tags = wp_get_post_terms($product_id, 'product_tag'); // Assuming 'product_tag' is the taxonomy
            //error_log(print_r($tags, true));
            foreach ($tags as $tag) {
                if ('bait' === strtolower($tag->name)) {
                    //error_log('This product is a bait: '.$product_id);
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Caught exception in is_bait_product: " . $e->getMessage());
            throw $e;
        }
    }
    
}
