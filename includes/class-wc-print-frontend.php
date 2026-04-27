<?php
/**
 * Frontend Class
 *
 * Handles frontend functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Frontend {

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
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_print_request' ), 1 );
        add_action( 'woocommerce_view_order', array( $this, 'display_print_buttons' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'display_print_buttons' ) );
        add_action( 'woocommerce_order_item_meta_end', array( $this, 'maybe_hide_item_meta' ), 10, 3 );
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^wcp-document/([^/]+)/?$',
            'index.php?wcp_document_type=$matches[1]&wcp_order_id=$matches[2]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'wcp_action';
        $vars[] = 'wcp_document_type';
        $vars[] = 'wcp_order_id';
        $vars[] = 'document_type';
        $vars[] = 'order_id';
        $vars[] = 'wcp_token';
        
        return $vars;
    }

    /**
     * Handle print request
     */
    public function handle_print_request() {
        // Check for our custom query vars
        $action = isset( $_GET['wcp_action'] ) ? sanitize_text_field( $_GET['wcp_action'] ) : '';
        
        if ( empty( $action ) ) {
            return;
        }

        if ( 'generate_pdf' === $action ) {
            $this->handle_pdf_request();
        } elseif ( 'view_document' === $action ) {
            $this->handle_view_request();
        }
    }

    /**
     * Handle PDF request
     */
    private function handle_pdf_request() {
        // Verify nonce
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $type     = isset( $_GET['document_type'] ) ? sanitize_text_field( $_GET['document_type'] ) : 'invoice';
        $nonce    = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : '';

        // Validate nonce
        if ( ! wp_verify_nonce( $nonce, 'wcp_pdf_' . $order_id ) ) {
            wp_die( esc_html__( 'Invalid security token.', 'woocommerce-print-documents' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'woocommerce-print-documents' ) );
        }

        // Create document
        $document = new WC_Print_Document( $order, $type );

        // Output PDF
        $this->output_pdf( $document );

        exit;
    }

    /**
     * Handle view request
     */
    private function handle_view_request() {
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $type     = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'invoice';
        $token    = isset( $_GET['wcp_token'] ) ? sanitize_text_field( $_GET['wcp_token'] ) : '';

        // Check for guest token (secure token for guest access)
        if ( ! is_user_logged_in() && ! empty( $token ) ) {
            // Validate guest token
            $stored_token = get_post_meta( $order_id, '_wcp_guest_token', true );
            if ( $stored_token !== $token ) {
                wp_die( esc_html__( 'Invalid or expired token.', 'woocommerce-print-documents' ) );
            }
        } elseif ( ! current_user_can( 'view_order', $order_id ) ) {
            wp_die( esc_html__( 'You do not have permission to view this document.', 'woocommerce-print-documents' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die( esc_html__( 'Order not found.', 'woocommerce-print-documents' ) );
        }

        // Create document
        $document = new WC_Print_Document( $order, $type );

        // Output HTML document
        $this->output_html( $document );

        exit;
    }

    /**
     * Output PDF
     */
    private function output_pdf( $document ) {
        $pdf_content = $document->generate_pdf();

        if ( strpos( $pdf_content, '<!DOCTYPE' ) !== false ) {
            // HTML fallback - just output HTML with print CSS
            echo $pdf_content;
        } else {
            // Actual PDF
            header( 'Content-Type: application/pdf' );
            header( 'Content-Disposition: inline; filename="' . $document->title . '.pdf"' );
            echo $pdf_content;
        }
    }

    /**
     * Output HTML
     */
    private function output_html( $document ) {
        $html = $document->render_html();
        
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html( $document->title ) . '</title>
    <link rel="stylesheet" href="' . includes_url( 'css/dashicons.min.css' ) . '">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body onload="window.print();">
' . $html . '
</body>
</html>';
    }

    /**
     * Display print buttons on My Account page
     */
    public function display_print_buttons( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $documents = get_option( 'wcp_documents', array() );
        $documents_enabled = array_filter( $documents, function( $doc ) {
            return isset( $doc['active'] ) && 'yes' === $doc['active'];
        } );

        if ( empty( $documents_enabled ) ) {
            return;
        }

        // Check order status for document availability
        $order_status = $order->get_status();

        echo '<div class="wcp-document-buttons" style="margin-top: 20px;">';
        echo '<h4>' . esc_html__( 'Print Documents', 'woocommerce-print-documents' ) . '</h4>';

        foreach ( $documents_enabled as $type => $data ) {
            $print_url = add_query_arg( array(
                'action'   => 'view_document',
                'type'     => $type,
                'order_id' => $order->get_id(),
                'nonce'    => wp_create_nonce( 'wcp_view_' . $order->get_id() ),
            ), home_url( '/wcp-document/' . $type . '/' . $order->get_id() . '/' ) );

            // For logged in users
            if ( is_user_logged_in() ) {
                $print_url = add_query_arg( array(
                    'wcp_action'  => 'view_document',
                    'type'        => $type,
                    'order_id'    => $order->get_id(),
                    'nonce'       => wp_create_nonce( 'wcp_view_' . $order->get_id() ),
                ), home_url( '/' ) );
            }

            printf(
                '<a href="%s" target="_blank" class="button" style="margin-right: 10px; margin-bottom: 10px;">%s</a>',
                esc_url( $print_url ),
                esc_html( $data['title'] )
            );
        }

        echo '</div>';
    }

    /**
     * Generate guest token for order
     */
    public static function generate_guest_token( $order_id ) {
        $token = wp_hash( $order_id . '_' . time() . '_guest' );
        update_post_meta( $order_id, '_wcp_guest_token', $token );
        return $token;
    }

    /**
     * Get guest print URL
     */
    public static function get_guest_print_url( $order, $type ) {
        $token = get_post_meta( $order->get_id(), '_wcp_guest_token', true );
        
        if ( ! $token ) {
            $token = self::generate_guest_token( $order->get_id() );
        }

        return add_query_arg( array(
            'wcp_action'  => 'view_document',
            'type'        => $type,
            'order_id'    => $order->get_id(),
            'wcp_token'   => $token,
        ), home_url( '/' ) );
    }
}