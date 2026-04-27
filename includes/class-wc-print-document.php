<?php
/**
 * Print Document Class
 *
 * Handles document generation and PDF creation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Document {

    /**
     * Order object
     */
    public $order;

    /**
     * Document type
     */
    public $document_type;

    /**
     * Document title
     */
    public $title;

    /**
     * Constructor
     */
    public function __construct( $order = null, $document_type = 'invoice' ) {
        $this->order         = $order;
        $this->document_type = $document_type;
        $this->title         = $this->get_document_title();
    }

    /**
     * Get document title from settings
     */
    private function get_document_title() {
        $documents = get_option( 'wcp_documents', array() );
        if ( isset( $documents[ $this->document_type ]['title'] ) ) {
            return $documents[ $this->document_type ]['title'];
        }
        return ucfirst( $this->document_type );
    }

    /**
     * Get invoice number for order
     */
    public function get_invoice_number() {
        $order_id = $this->order->get_id();
        
        // Get stored invoice number or generate new one
        $invoice_number = get_post_meta( $order_id, '_wcp_invoice_number', true );
        
        if ( ! $invoice_number ) {
            $reset_yearly = get_option( 'wcp_invoice_reset_yearly', 'no' );
            $start_number = get_option( 'wcp_invoice_start', 1 );
            $year_key     = date( 'Y' );
            
            // Check for yearly reset
            if ( 'yes' === $reset_yearly ) {
                $transient_key = 'wcp_invoice_number_' . $year_key;
                $next_number   = get_transient( $transient_key );
                
                if ( false === $next_number ) {
                    $next_number = $start_number;
                }
            } else {
                $transient_key = 'wcp_invoice_number_global';
                $next_number   = get_transient( $transient_key );
                
                if ( false === $next_number ) {
                    $next_number = $start_number;
                }
            }
            
            $invoice_number = $this->generate_invoice_number( $next_number );
            
            // Store and increment
            update_post_meta( $order_id, '_wcp_invoice_number', $invoice_number );
            
            $next_number++;
            if ( 'yes' === $reset_yearly ) {
                set_transient( 'wcp_invoice_number_' . $year_key, $next_number, YEAR_IN_SECONDS );
            } else {
                set_transient( 'wcp_invoice_number_global', $next_number, 0 );
            }
        }
        
        return apply_filters( 'wcp_invoice_number', $invoice_number, $this->order );
    }

    /**
     * Generate invoice number with placeholders
     */
    private function generate_invoice_number( $number ) {
        $format = get_option( 'wcp_invoice_format', '{year}{month}{next_number}' );
        
        $replacements = array(
            '{year}'        => date( 'Y' ),
            '{month}'      => date( 'm' ),
            '{next_number}' => str_pad( $number, 4, '0', STR_PAD_LEFT ),
            '{order_number}'=> $this->order->get_order_number(),
            '{customer}'    => $this->order->get_billing_first_name(),
        );
        
        $invoice_number = str_replace(
            array_keys( $replacements ),
            array_values( $replacements ),
            $format
        );
        
        return $invoice_number;
    }

    /**
     * Get invoice date
     */
    public function get_invoice_date() {
        $date = get_post_meta( $this->order->get_id(), '_wcp_invoice_date', true );
        
        if ( ! $date ) {
            $date = $this->order->get_date_created()->format( 'Y-m-d H:i:s' );
            update_post_meta( $this->order->get_id(), '_wcp_invoice_date', $date );
        }
        
        return apply_filters( 'wcp_invoice_date', $date, $this->order );
    }

    /**
     * Get shop info
     */
    public function get_shop_info() {
        return array(
            'name'     => get_option( 'wcp_shop_name', get_bloginfo( 'name' ) ),
            'address'  => get_option( 'wcp_shop_address', '' ),
            'phone'    => get_option( 'wcp_shop_phone', '' ),
            'email'    => get_option( 'wcp_shop_email', '' ),
            'logo'     => get_option( 'wcp_logo_url', '' ),
        );
    }

    /**
     * Get billing address
     */
    public function get_billing_address() {
        return array(
            'first_name' => $this->order->get_billing_first_name(),
            'last_name'  => $this->order->get_billing_last_name(),
            'company'    => $this->order->get_billing_company(),
            'address_1'  => $this->order->get_billing_address_1(),
            'address_2'  => $this->order->get_billing_address_2(),
            'city'       => $this->order->get_billing_city(),
            'state'      => $this->order->get_billing_state(),
            'postcode'   => $this->order->get_billing_postcode(),
            'country'    => $this->order->get_billing_country(),
            'email'      => $this->order->get_billing_email(),
            'phone'      => $this->order->get_billing_phone(),
        );
    }

    /**
     * Get shipping address
     */
    public function get_shipping_address() {
        return array(
            'first_name' => $this->order->get_shipping_first_name(),
            'last_name'  => $this->order->get_shipping_last_name(),
            'company'    => $this->order->get_shipping_company(),
            'address_1'  => $this->order->get_shipping_address_1(),
            'address_2'  => $this->order->get_shipping_address_2(),
            'city'       => $this->order->get_shipping_city(),
            'state'      => $this->order->get_shipping_state(),
            'postcode'   => $this->order->get_shipping_postcode(),
            'country'    => $this->order->get_shipping_country(),
        );
    }

    /**
     * Get order items
     */
    public function get_order_items() {
        $items = $this->order->get_items();
        $data  = array();
        
        foreach ( $items as $item_id => $item ) {
            $product = $item->get_product();
            
            $data[] = array(
                'id'          => $item_id,
                'name'        => $item->get_name(),
                'quantity'    => $item->get_quantity(),
                'total'       => $item->get_total(),
                'price'       => $product ? ( function_exists( 'wc_get_price_excluding_html' ) ? wc_get_price_excluding_html( $product ) : $product->get_price() ) : 0,
                'sku'         => $product ? $product->get_sku() : '',
                'image_id'    => $product ? $product->get_image_id() : 0,
            );
        }
        
        return apply_filters( 'wcp_order_items', $data, $this->order, $this->document_type );
    }

    /**
     * Get order totals
     */
    public function get_order_totals() {
        return array(
            'subtotal'      => $this->order->get_subtotal(),
            'shipping'      => $this->order->get_shipping_total(),
            'tax'           => $this->order->get_total_tax(),
            'discount'      => $this->order->get_total_discount(),
            'total'         => $this->order->get_total(),
            'currency'      => $this->order->get_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol( $this->order->get_currency() ),
        );
    }

    /**
     * Get payment method
     */
    public function get_payment_method() {
        return $this->order->get_payment_method_title();
    }

    /**
     * Get shipping method
     */
    public function get_shipping_method() {
        $items = $this->order->get_items( 'shipping' );
        $methods = array();
        
        foreach ( $items as $item ) {
            $methods[] = $item->get_name();
        }
        
        return implode( ', ', $methods );
    }

    /**
     * Get customer note
     */
    public function get_customer_note() {
        return $this->order->get_customer_note();
    }

    /**
     * Get order data for template
     */
    public function get_template_data() {
        $data = array(
            'document_type'   => $this->document_type,
            'title'           => $this->title,
            'invoice_number'  => $this->get_invoice_number(),
            'invoice_date'    => $this->get_invoice_date(),
            'order_id'        => $this->order->get_id(),
            'order_number'    => $this->order->get_order_number(),
            'order_date'      => $this->order->get_date_created()->format( 'Y-m-d' ),
            'shop'            => $this->get_shop_info(),
            'billing'         => $this->get_billing_address(),
            'shipping'        => $this->get_shipping_address(),
            'items'           => $this->get_order_items(),
            'totals'          => $this->get_order_totals(),
            'payment_method'  => $this->get_payment_method(),
            'shipping_method' => $this->get_shipping_method(),
            'customer_note'   => $this->get_customer_note(),
        );
        
        return apply_filters( 'wcp_template_data', $data, $this->order, $this->document_type );
    }

    /**
     * Render HTML document
     */
    public function render_html() {
        $data = $this->get_template_data();
        
        ob_start();
        
        // Include template
        $template_path = $this->locate_template( $this->document_type );
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            include plugin_dir_path( __FILE__ ) . '../templates/document-default.php';
        }
        
        return ob_get_clean();
    }

    /**
     * Locate template file
     */
    public function locate_template( $type ) {
        // Check theme override first
        $theme_template = locate_template( array( "wcp/document-{$type}.php" ) );
        
        if ( $theme_template ) {
            return $theme_template;
        }
        
        // Check plugin templates
        $plugin_template = plugin_dir_path( __FILE__ ) . "../templates/document-{$type}.php";
        
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
        
        // Return default template
        return plugin_dir_path( __FILE__ ) . '../templates/document-default.php';
    }

    /**
     * Get PDF generate URL
     */
    public function get_pdf_url() {
        return add_query_arg( array(
            'wcp_action'     => 'generate_pdf',
            'document_type'  => $this->document_type,
            'order_id'       => $this->order->get_id(),
            'nonce'          => wp_create_nonce( 'wcp_pdf_' . $this->order->get_id() ),
        ), home_url( '/' ) );
    }

    /**
     * Generate PDF
     * Uses TCPDF or Dompdf if available, otherwise returns HTML
     */
    public function generate_pdf( $output = 'browser' ) {
        $html = $this->render_html();
        
        // Try to use TCPDF if available
        if ( class_exists( 'TCPDF' ) ) {
            return $this->generate_tcpdf_pdf( $html, $output );
        }
        
        // Try to use Dompdf if available
        if ( class_exists( 'Dompdf\Dompdf' ) ) {
            return $this->generate_dompdf_pdf( $html, $output );
        }
        
        // Fallback: Return HTML with print CSS
        return $html;
    }

    /**
     * Generate PDF using TCPDF
     */
    private function generate_tcpdf_pdf( $html, $output ) {
        // Default to HTML output if TCPDF not available
        return $this->generate_html_fallback( $html, $output );
    }

    /**
     * Generate PDF using Dompdf
     */
    private function generate_dompdf_pdf( $html, $output ) {
        // Default to HTML output if Dompdf not available
        return $this->generate_html_fallback( $html, $output );
    }

    /**
     * HTML fallback with print styles
     */
    private function generate_html_fallback( $html, $output ) {
        // Wrap with print-friendly CSS
        $print_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html( $this->title ) . ' - #' . esc_html( $this->order->get_order_number() ) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 0; padding: 20px; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body onload="window.print();">
' . $html . '
</body>
</html>';
        
        return $print_html;
    }

    /**
     * Save PDF to file
     */
    public function save_pdf() {
        $upload_dir = wp_upload_dir();
        $print_dir  = $upload_dir['basedir'] . '/wcp-documents';
        
        if ( ! file_exists( $print_dir ) ) {
            wp_mkdir_p( $print_dir );
        }
        
        $filename = sprintf(
            '%s-%s-%s.pdf',
            $this->document_type,
            $this->order->get_order_number(),
            date( 'Y-m-d' )
        );
        
        $filepath = $print_dir . '/' . $filename;
        
        // Save PDF content
        $pdf_content = $this->generate_pdf();
        
        // If HTML fallback, don't save as PDF
        if ( strpos( $pdf_content, '<!DOCTYPE' ) !== false ) {
            return false;
        }
        
        // Save the file
        $result = file_put_contents( $filepath, $pdf_content );
        
        if ( $result ) {
            update_post_meta( $this->order->get_id(), '_wcp_pdf_' . $this->document_type, $filepath );
            return $filepath;
        }
        
        return false;
    }

    /**
     * Get saved PDF path
     */
    public function get_saved_pdf_path() {
        return get_post_meta( $this->order->get_id(), '_wcp_pdf_' . $this->document_type, true );
    }

    /**
     * Check if PDF exists
     */
    public function pdf_exists() {
        $path = $this->get_saved_pdf_path();
        return $path && file_exists( $path );
    }
}

/**
 * Helper function to get document object
 */
function wcp_get_document( $order, $type = 'invoice' ) {
    return new WC_Print_Document( $order, $type );
}