<?php
/**
 * Plugin Name: WooCommerce Print Documents
 * Plugin URI: https://example.com/woocommerce-print-documents
 * Description: Generate, print and email invoices, receipts, delivery notes, packing slips and credit notes for WooCommerce orders.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: woocommerce-print-documents
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class
 */
final class WC_Print_Documents {

    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Plugin path
     */
    public $plugin_path;

    /**
     * Plugin URL
     */
    public $plugin_url;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url  = plugin_dir_url( __FILE__ );

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        // Include required files
        $this->includes();

        // Initialize components
        $this->init_components();
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once $this->plugin_path . 'includes/class-wc-print-settings.php';
        require_once $this->plugin_path . 'includes/class-wc-print-document.php';
        require_once $this->plugin_path . 'includes/class-wc-print-templates.php';
        require_once $this->plugin_path . 'includes/class-wc-print-admin.php';
        require_once $this->plugin_path . 'includes/class-wc-print-frontend.php';
        require_once $this->plugin_path . 'includes/class-wc-print-emails.php';
        require_once $this->plugin_path . 'includes/class-wc-print-ajax.php';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        WC_Print_Settings::get_instance();
        WC_Print_Admin::get_instance();
        WC_Print_Frontend::get_instance();
        WC_Print_Emails::get_instance();
        WC_Print_Ajax::get_instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'wcp_documents' => array(
                'invoice'   => array( 'active' => true, 'title' => 'Invoice' ),
                'receipt'   => array( 'active' => true, 'title' => 'Receipt' ),
                'delivery'  => array( 'active' => true, 'title' => 'Delivery Note' ),
                'packing'   => array( 'active' => true, 'title' => 'Packing Slip' ),
                'credit'    => array( 'active' => false, 'title' => 'Credit Note' ),
            ),
            'wcp_invoice_start' => 1,
            'wcp_shop_name'    => get_bloginfo( 'name' ),
            'wcp_sanitize'    => true,
        );

        foreach ( $default_options as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $print_dir  = $upload_dir['basedir'] . '/wcp-documents';

        if ( ! file_exists( $print_dir ) ) {
            wp_mkdir_p( $print_dir );
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'WooCommerce Print Documents requires WooCommerce to be installed and active.', 'woocommerce-print-documents' ); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woocommerce-print-documents',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Get plugin path
     */
    public function plugin_path() {
        return $this->plugin_path;
    }

    /**
     * Get plugin URL
     */
    public function plugin_url() {
        return $this->plugin_url;
    }
}

/**
 * Get plugin instance
 */
function wc_print_documents() {
    return WC_Print_Documents::get_instance();
}

// Initialize plugin
wc_print_documents();