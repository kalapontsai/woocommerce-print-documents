<?php
/**
 * AJAX Class
 *
 * Handles AJAX requests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Ajax {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get instance
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
        add_action( 'wp_ajax_wcp_print_document', array( $this, 'handle_print_document' ) );
        add_action( 'wp_ajax_wcp_email_document', array( $this, 'handle_email_document' ) );
        add_action( 'wp_ajax_wcp_preview_document', array( $this, 'handle_preview_document' ) );
        add_action( 'wp_ajax_wcp_save_invoice_number', array( $this, 'handle_save_invoice_number' ) );
        add_action( 'wp_ajax_wcp_save_invoice_date', array( $this, 'handle_save_invoice_date' ) );
        add_action( 'wp_ajax_wcp_get_document_html', array( $this, 'handle_get_document_html' ) );
        add_action( 'wp_ajax_nopriv_wcp_print_document', array( $this, 'handle_guest_print' ) );
        add_action( 'wp_ajax_nopriv_wcp_email_document', array( $this, 'handle_guest_email' ) );
    }

    /**
     * Verify nonce with multiple action types
     */
    private function verify_nonce( $nonce, $order_id = 0 ) {
        if ( empty( $nonce ) ) {
            return false;
        }

        // List of valid nonce actions
        $actions = array(
            'wcp_print',
            'wcp_admin_nonce',
            'wcp_view_' . $order_id,
        );

        foreach ( $actions as $action ) {
            if ( wp_verify_nonce( $nonce, $action ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle print document AJAX
     */
    public function handle_print_document() {
        // Get order ID
        $order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
        $type     = isset( $_REQUEST['type'] ) ? sanitize_text_field( $_REQUEST['type'] ) : 'invoice';

        if ( ! $order_id ) {
            wp_die( esc_html__( 'Invalid order ID.', 'woocommerce-print-documents' ) );
        }

        // Verify nonce - try multiple actions
        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '';
        
        if ( ! empty( $nonce ) && ! $this->verify_nonce( $nonce, $order_id ) ) {
            // If nonce is invalid but user is logged in and has proper capabilities, allow
            if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_order', $order_id ) ) {
                wp_die( esc_html__( 'Security check failed.', 'woocommerce-print-documents' ) );
            }
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'woocommerce-print-documents' ) );
        }

        // Create document
        $document = new WC_Print_Document( $order, $type );

        // Output document
        $document->render_html();

        exit;
    }

    /**
     * Handle email document AJAX
     */
    public function handle_email_document() {
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $type     = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'invoice';
        $nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'woocommerce-print-documents' ) ) );
        }

        // Verify nonce
        if ( ! empty( $nonce ) && ! $this->verify_nonce( $nonce, $order_id ) ) {
            if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_order', $order_id ) ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-print-documents' ) ) );
            }
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce-print-documents' ) ) );
        }

        // Get email recipient
        $recipient = $order->get_billing_email();

        // Send email
        $sent = wcp_send_document_email( $order, $type, $recipient );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Document sent successfully!', 'woocommerce-print-documents' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send email.', 'woocommerce-print-documents' ) ) );
        }
    }

    /**
     * Handle preview document AJAX
     */
    public function handle_preview_document() {
        $type  = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'invoice';
        $nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : '';

        // For preview, verify nonce if provided, or allow admin users
        if ( ! empty( $nonce ) ) {
            $verified = $this->verify_nonce( $nonce );
            // Also allow wcp_preview nonce action
            if ( ! $verified && ! wp_verify_nonce( $nonce, 'wcp_preview' ) ) {
                if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
                    wp_die( esc_html__( 'Security check failed.', 'woocommerce-print-documents' ) );
                }
            }
        } elseif ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'woocommerce-print-documents' ) );
        }

        // Create a demo order object for preview
        $preview_html = $this->get_preview_html( $type );

        echo $preview_html;

        exit;
    }

    /**
     * Get preview HTML
     */
    private function get_preview_html( $type ) {
        $documents = get_option( 'wcp_documents', array() );
        $title = isset( $documents[ $type ]['title'] ) ? $documents[ $type ]['title'] : ucfirst( $type );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html( $title ); ?> - Preview</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f0f0f0; }
                .preview-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .preview-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #ddd; }
                .preview-footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="preview-container">
                <div class="preview-header">
                    <h1><?php echo esc_html( $title ); ?></h1>
                    <p style="color:#666;">This is a preview of your <?php echo esc_html( $type ); ?> document template.</p>
                    <p style="color:#999; font-size:12px;">Configure the settings to see a more accurate preview.</p>
                </div>
                
                <div style="padding: 20px;">
                    <h3>Shop Information</h3>
                    <p><strong>Shop Name:</strong> <?php echo esc_html( get_option( 'wcp_shop_name', 'Your Shop Name' ) ); ?></p>
                    <p><strong>Address:</strong> <?php echo esc_html( get_option( 'wcp_shop_address', '123 Shop Street, City' ) ); ?></p>
                    
                    <h3>Order Details (Sample)</h3>
                    <p><strong>Invoice #:</strong> INV-2024-0001</p>
                    <p><strong>Date:</strong> <?php echo date( 'Y-m-d' ); ?></p>
                    
                    <h3>Customer Address</h3>
                    <p>John Doe<br>123 Customer Street<br>City, State 12345<br>Country</p>
                    
                    <h3>Items (Sample)</h3>
                    <table style="width:100%; border-collapse: collapse;">
                        <tr style="border-bottom:1px solid #ddd;">
                            <th style="text-align:left; padding:10px;">Product</th>
                            <th style="text-align:right; padding:10px;">Qty</th>
                            <th style="text-align:right; padding:10px;">Price</th>
                        </tr>
                        <tr>
                            <td style="padding:10px;">Sample Product 1</td>
                            <td style="text-align:right; padding:10px;">1</td>
                            <td style="text-align:right; padding:10px;">$100.00</td>
                        </tr>
                        <tr>
                            <td style="padding:10px;">Sample Product 2</td>
                            <td style="text-align:right; padding:10px;">2</td>
                            <td style="text-align:right; padding:10px;">$50.00</td>
                        </tr>
                        <tr style="font-weight:bold;">
                            <td colspan="2" style="padding:10px; text-align:right;">Total:</td>
                            <td style="text-align:right; padding:10px;">$200.00</td>
                        </tr>
                    </table>
                </div>
                
                <div class="preview-footer">
                    Generated by WooCommerce Print Documents
                </div>
            </div>
        </body>
        </html>
        <?php

        return ob_get_clean();
    }

    /**
     * Handle save invoice number AJAX
     */
    public function handle_save_invoice_number() {
        $order_id      = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $invoice_number = isset( $_POST['invoice_number'] ) ? sanitize_text_field( $_POST['invoice_number'] ) : '';
        $nonce          = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'woocommerce-print-documents' ) ) );
        }

        // Verify nonce
        if ( ! empty( $nonce ) && ! $this->verify_nonce( $nonce, $order_id ) ) {
            if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_order', $order_id ) ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-print-documents' ) ) );
            }
        }

        update_post_meta( $order_id, '_wcp_invoice_number', $invoice_number );

        wp_send_json_success( array( 'message' => __( 'Invoice number saved.', 'woocommerce-print-documents' ) ) );
    }

    /**
     * Handle save invoice date AJAX
     */
    public function handle_save_invoice_date() {
        $order_id     = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $invoice_date = isset( $_POST['invoice_date'] ) ? sanitize_text_field( $_POST['invoice_date'] ) : '';
        $nonce        = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'woocommerce-print-documents' ) ) );
        }

        // Verify nonce
        if ( ! empty( $nonce ) && ! $this->verify_nonce( $nonce, $order_id ) ) {
            if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_order', $order_id ) ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-print-documents' ) ) );
            }
        }

        update_post_meta( $order_id, '_wcp_invoice_date', $invoice_date );

        wp_send_json_success( array( 'message' => __( 'Invoice date saved.', 'woocommerce-print-documents' ) ) );
    }

    /**
     * Handle get document HTML AJAX
     */
    public function handle_get_document_html() {
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $type     = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'invoice';
        $nonce    = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'woocommerce-print-documents' ) ) );
        }

        // Verify nonce
        if ( ! empty( $nonce ) && ! $this->verify_nonce( $nonce, $order_id ) ) {
            if ( ! is_user_logged_in() || ! current_user_can( 'edit_shop_order', $order_id ) ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-print-documents' ) ) );
            }
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce-print-documents' ) ) );
        }

        $document = new WC_Print_Document( $order, $type );
        $html     = $document->render_html();

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Handle guest print
     */
    public function handle_guest_print() {
        // For guests, verify secure token
        $order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
        $token    = isset( $_REQUEST['wcp_token'] ) ? sanitize_text_field( $_REQUEST['wcp_token'] ) : '';

        if ( ! $order_id ) {
            wp_die( esc_html__( 'Invalid order ID.', 'woocommerce-print-documents' ) );
        }

        // Verify guest token
        if ( ! empty( $token ) ) {
            $stored_token = get_post_meta( $order_id, '_wcp_guest_token', true );
            if ( $stored_token !== $token ) {
                wp_die( esc_html__( 'Invalid or expired token.', 'woocommerce-print-documents' ) );
            }
        } else {
            wp_die( esc_html__( 'Security check failed.', 'woocommerce-print-documents' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'woocommerce-print-documents' ) );
        }

        $type     = isset( $_REQUEST['type'] ) ? sanitize_text_field( $_REQUEST['type'] ) : 'invoice';
        $document = new WC_Print_Document( $order, $type );
        $document->render_html();

        exit;
    }

    /**
     * Handle guest email
     */
    public function handle_guest_email() {
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $token    = isset( $_POST['wcp_token'] ) ? sanitize_text_field( $_POST['wcp_token'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'woocommerce-print-documents' ) ) );
        }

        // Verify guest token
        if ( ! empty( $token ) ) {
            $stored_token = get_post_meta( $order_id, '_wcp_guest_token', true );
            if ( $stored_token !== $token ) {
                wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-print-documents' ) ) );
            }
        } else {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woocommerce-print-documents' ) ) );
        }

        $type   = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'invoice';
        $order  = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'woocommerce-print-documents' ) ) );
        }

        $recipient = $order->get_billing_email();
        $sent      = wcp_send_document_email( $order, $type, $recipient );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Document sent successfully!', 'woocommerce-print-documents' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send email.', 'woocommerce-print-documents' ) ) );
        }
    }
}
