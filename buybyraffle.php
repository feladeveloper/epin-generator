<?php
/**
 * Plugin Name:       BuyByRaffle
 * Plugin URI:        [your-website-or-plugin-uri]
 * Description:       Integrates raffles into the WooCommerce shopping experience, providing an exciting and engaging way for customers to participate in raffles while shopping.
 * Version:           1.0.0
 * Requires at least: [minimum-WordPress-version]
 * Requires PHP:      [minimum-PHP-version]
 * Author:            SGS TEAM
 * Author URI:        [author-website]
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buybyraffle
 * Domain Path:       /languages
 */

// Your plugin's main code starts here.



// Prevent direct file access.
defined('WPINC') or die;

// Define plugin version for easy management of scripts, styles, and other assets.
define('BUYBYRAFFLE_VERSION', '1.0.0');

// Include Composer's autoloader to manage dependencies.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Enqueues admin-specific scripts.
 *
 * Loads JavaScript for admin interactions, specifically on the 'product' post type screen.
 */
function enqueue_admin_scripts() {
    $screen = get_current_screen();
    if ('product' === $screen->id) {
        wp_enqueue_script(
            'buybyraffle-custom-script',
            plugin_dir_url(__FILE__) . 'js/scripts.js',
            array('jquery'),
            BUYBYRAFFLE_VERSION,
            true
        );

        // Localize script for AJAX requests, providing the AJAX URL and a nonce for security.
        wp_localize_script(
            'buybyraffle-custom-script',
            'my_ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('my_ajax_nonce')
            )
        );
    }
}
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

/**
 * Resets plugin version upon deactivation.
 *
 * This callback is hooked to the deactivation process, resetting the plugin version
 * in environments that are marked as safe for such operations.
 */
function buybyraffle_deactivation() {
    $allowed_ips = ['127.0.0.1', '::1', '138.68.91.147']; // Localhost and staging IPs.

    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $current_host = $_SERVER['HTTP_HOST'] ?? '';

    if (in_array($current_ip, $allowed_ips) || false !== strpos($current_host, 'localhost')) {
        delete_option('BUYBYRAFFLE_VERSION');
    }
}
register_deactivation_hook(__FILE__, 'buybyraffle_deactivation');

/**
 * Main plugin class
 *
 * Initializes the plugin and includes the necessary hooks for operation.
 */
class BuyByRaffle {
    /**
     * Handles raffle cycle processes.
     * 
     * @var object
     */
    private $cycleHandler;

    /**
     * Constructor.
     *
     * Sets the default timezone and registers activation hooks for various components.
     */
    public function __construct() {
        // Sets the default timezone for the plugin.
        date_default_timezone_set('Africa/Lagos');

        // Registers activation hooks for creating custom tables and product tags.
        $this->register_activation_hooks();

        // Initializes admin handlers if in the admin area and WooCommerce is active.
        if (is_admin() && $this->is_woocommerce_active()) {
            $this->initialize_admin_handlers();
        }       
        
    }

    /**
     * Registers activation hooks for the plugin.
     */
    private function register_activation_hooks() {
        // Registers hooks for installing custom tables, product tags, and raffle classes.
        register_activation_hook(__FILE__, ['\Sgs\Buybyraffle\BuyByRaffleTableInstallerHandler', 'install']);
        register_activation_hook(__FILE__, ['\Sgs\Buybyraffle\BuyByRaffleProductTagCreateHandler', 'install']);
        register_activation_hook(__FILE__, ['\Sgs\Buybyraffle\BuyByRaffleRaffleClassMgr', 'init_raffle_classes']);
    }

    /**
     * Initializes admin-specific handlers.
     */
    private function initialize_admin_handlers() {
        // Create and store the instance of BuyByRaffleCycleHandler.
        $this->cycleHandler = new \Sgs\Buybyraffle\BuyByRaffleCycleHandler();

        // Instantiate handlers related to the admin area.
        new \Sgs\Buybyraffle\BuyByRaffleHeroProductHandler($this->cycleHandler);
        new \Sgs\Buybyraffle\BuyByRaffleOrderStatusManager();
        new \Sgs\Buybyraffle\BuyByRaffleDrawScheduler();
        new \Sgs\Buybyraffle\BuyByRaffleBaitHeroAssociationHandler();
        new \Sgs\Buybyraffle\BuyByRaffleWinnerHandler();
        new \Sgs\Buybyraffle\BuyByRaffleRaffleTicketHandler();
        new \Sgs\Buybyraffle\BuyByRaffleApiDbConnector();
    }

    /**
     * Checks if WooCommerce is active.
     *
     * @return bool True if WooCommerce is active, false otherwise.
     */
    public static function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true);
    }

    /**
     * Returns the current date and time in MySQL format.
     *
     * @return string Date and time in 'Y-m-d H:i:s' format.
     */
    public static function current_mysql_date() {
        return date('Y-m-d H:i:s');
    }
}

// Initialize the plugin.
if (is_admin()) {
    new BuyByRaffle();
} 
// Instantiate PostToPubSub and gist cashtoken
new \Sgs\Buybyraffle\BuyByRaffleQueuePubSub();
new \Sgs\Buybyraffle\BuyByRaffleCashTokenGifting();

// Handle plugin deactivation if WooCommerce is not active.
if (!is_admin() && !BuyByRaffle::is_woocommerce_active()) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_die(__('This plugin requires WooCommerce to be activated.', 'buybyraffle'));
}
