<?php
/**
 * Emails Class
 *
 * Handles email attachments and integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Emails {

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
        add_filter( 'woocommerce_email_attachments', array( $this, 'attach_documents_to_emails' ), 10, 4 );
        add_filter( 'woocommerce_email_headers', array( $this, 'add_email_headers' ), 10, 3 );
    }

    /**
     * Attach documents to emails
     */
    public function attach_documents_to_emails( $attachments, $email_id, $order, $email ) {
        // Get email settings
        $attach_to_emails = get_option( 'wcp_attach_to_emails', array() );
        
        if ( empty( $attach_to_emails ) || ! in_array( $email_id, $attach_to_emails, true ) ) {
            return $attachments;
        }

        // Check if order
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return $attachments;
        }

        // Get document type to attach
        $document_type = get_option( 'wcp_attachment_type', 'invoice' );

        // Generate PDF
        $document = new WC_Print_Document( $order, $document_type );
        $pdf_path = $document->save_pdf();

        if ( $pdf_path && file_exists( $pdf_path ) ) {
            $attachments[] = $pdf_path;
        }

        return $attachments;
    }

    /**
     * Add custom headers
     */
    public function add_email_headers( $headers, $email_id, $order ) {
        // Add custom headers if needed
        return $headers;
    }

    /**
     * Send document email manually
     */
    public function send_document_email( $order, $document_type, $recipient ) {
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return false;
        }

        $document = new WC_Print_Document( $order, $document_type );
        
        // Generate PDF
        $pdf_content = $document->generate_pdf();
        
        if ( empty( $pdf_content ) ) {
            return false;
        }

        // Save PDF to temp file for attachment
        $upload_dir = wp_upload_dir();
        $temp_dir   = $upload_dir['basedir'] . '/wcp-documents';
        
        if ( ! file_exists( $temp_dir ) ) {
            wp_mkdir_p( $temp_dir );
        }

        $filename = sprintf(
            '%s-%s-%s.pdf',
            $document_type,
            $order->get_order_number(),
            date( 'Y-m-d-His' )
        );

        $temp_path = $temp_dir . '/' . $filename;

        // Save PDF
        if ( strpos( $pdf_content, '<!DOCTYPE' ) !== false ) {
            // HTML fallback - don't save as PDF
            return false;
        }

        file_put_contents( $temp_path, $pdf_content );

        // Prepare email
        $subject = sprintf(
            __( '[%s] Your %s - Order #%s', 'woocommerce-print-documents' ),
            wp_specialchars_decode( get_option( 'wcp_shop_name', get_bloginfo( 'name' ) ) ),
            $document->title,
            $order->get_order_number()
        );

        $message = sprintf(
            __( 'Please find attached your %s for order #%s.', 'woocommerce-print-documents' ),
            $document->title,
            $order->get_order_number()
        );

        // Add custom message if set
        $custom_message = get_option( 'wcp_email_message', '' );
        if ( ! empty( $custom_message ) ) {
            $message .= "\n\n" . $custom_message;
        }

        // Send email
        $sent = wp_mail(
            $recipient,
            $subject,
            nl2br( $message ),
            array(
                'Content-Type: text/html',
                'From: ' . get_option( 'wcp_shop_name' ) . ' <' . get_option( 'admin_email' ) . '>',
            ),
            array( $temp_path )
        );

        // Clean up temp file
        @unlink( $temp_path );

        return $sent;
    }
}

/**
 * Helper function to send document email
 */
function wcp_send_document_email( $order, $type, $recipient = null ) {
    if ( ! $recipient ) {
        $recipient = $order->get_billing_email();
    }
    
    return WC_Print_Emails::get_instance()->send_document_email( $order, $type, $recipient );
}