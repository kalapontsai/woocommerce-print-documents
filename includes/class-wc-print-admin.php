<?php
/**
 * Admin Class
 *
 * Handles admin-specific functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Admin {

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
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_columns' ) );
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $this, 'add_order_actions_meta_box' ) );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts( $hook ) {
        global $post_type;

        if ( 'shop_order' !== $post_type && 'woocommerce_page_wc-print-documents' !== $hook ) {
            return;
        }

        $plugin_url = wc_print_documents()->plugin_url();

        wp_enqueue_style(
            'wcp-admin-styles',
            $plugin_url . 'assets/css/admin.css',
            array(),
            WC_Print_Documents::VERSION
        );

        wp_enqueue_script(
            'wcp-admin-scripts',
            $plugin_url . 'assets/js/admin.js',
            array( 'jquery' ),
            WC_Print_Documents::VERSION,
            true
        );

        wp_localize_script( 'wcp-admin-scripts', 'wcp_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wcp_admin_nonce' ),
        ) );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wcp_print_documents',
            __( 'Print Documents', 'woocommerce-print-documents' ),
            array( $this, 'render_meta_box' ),
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Render meta box content
     */
    public function render_meta_box( $post ) {
        $order = wc_get_order( $post->ID );
        if ( ! $order ) {
            return;
        }

        $documents = get_option( 'wcp_documents', array() );
        $documents_enabled = array_filter( $documents, function( $doc ) {
            return isset( $doc['active'] ) && $doc['active'];
        } );

        if ( empty( $documents_enabled ) ) {
            echo '<p>' . esc_html__( 'No documents are enabled. Please enable at least one document type in settings.', 'woocommerce-print-documents' ) . '</p>';
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wc-print-documents' ) ) . '">' . esc_html__( 'Go to Settings', 'woocommerce-print-documents' ) . '</a></p>';
            return;
        }
        ?>

        <div class="wcp-meta-box">
            <p><strong><?php esc_html_e( 'Print Document:', 'woocommerce-print-documents' ); ?></strong></p>

            <?php foreach ( $documents_enabled as $type => $data ) : ?>
                <div class="wcp-document-btn">
                    <button type="button" class="button wcp-print-btn" data-type="<?php echo esc_attr( $type ); ?>" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
                        <?php echo esc_html( $data['title'] ); ?>
                    </button>
                    <button type="button" class="button wcp-email-btn" data-type="<?php echo esc_attr( $type ); ?>" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
                        <?php esc_html_e( 'Email', 'woocommerce-print-documents' ); ?>
                    </button>
                </div>
            <?php endforeach; ?>

            <hr />

            <p>
                <a href="#" class="button wcp-bulk-print-link" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
                    <?php esc_html_e( 'View Bulk Print Options', 'woocommerce-print-documents' ); ?>
                </a>
            </p>
        </div>

        <style>
            .wcp-meta-box { padding: 10px 0; }
            .wcp-document-btn { margin-bottom: 10px; display: flex; gap: 5px; }
            .wcp-document-btn .button { flex: 1; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.wcp-print-btn').on('click', function() {
                var type = $(this).data('type');
                var order_id = $(this).data('order');
                var url = '<?php echo admin_url( 'admin-ajax.php' ); ?>?action=wcp_print_document&type=' + type + '&order_id=' + order_id + '&nonce=<?php echo wp_create_nonce( 'wcp_print' ); ?>';
                window.open(url, '_blank');
            });

            $('.wcp-email-btn').on('click', function() {
                var type = $(this).data('type');
                var order_id = $(this).data('order');
                
                if (confirm('<?php esc_html_e( 'Send this document to customer via email?', 'woocommerce-print-documents' ); ?>')) {
                    $.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                        type: 'POST',
                        data: {
                            action: 'wcp_email_document',
                            type: type,
                            order_id: order_id,
                            nonce: '<?php echo wp_create_nonce( 'wcp_email' ); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php esc_html_e( 'Document sent successfully!', 'woocommerce-print-documents' ); ?>');
                            } else {
                                alert('<?php esc_html_e( 'Error sending document.', 'woocommerce-print-documents' ); ?>');
                            }
                        }
                    });
                }
            });
        });
        </script>

        <?php
    }

    /**
     * Add order columns
     */
    public function add_order_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            if ( 'order_status' === $key ) {
                $new_columns['wcp_documents'] = __( 'Documents', 'woocommerce-print-documents' );
            }
        }

        return $new_columns;
    }

    /**
     * Render order columns
     */
    public function render_order_columns( $column, $post_id ) {
        if ( 'wcp_documents' !== $column ) {
            return;
        }

        $order = wc_get_order( $post_id );
        if ( ! $order ) {
            return;
        }

        $documents = get_option( 'wcp_documents', array() );
        $documents_enabled = array_filter( $documents, function( $doc ) {
            return isset( $doc['active'] ) && $doc['active'];
        } );

        echo '<div class="wcp-doc-links">';
        foreach ( $documents_enabled as $type => $data ) {
            $url = add_query_arg( array(
                'action'  => 'wcp_print_document',
                'type'    => $type,
                'order_id' => $order->get_id(),
                'nonce'   => wp_create_nonce( 'wcp_print' ),
            ), admin_url( 'admin-ajax.php' ) );

            echo '<a href="' . esc_url( $url ) . '" target="_blank" class="button button-small" title="' . esc_attr( $data['title'] ) . '">';
            echo esc_html( strtoupper( substr( $type, 0, 1 ) ) );
            echo '</a> ';
        }
        echo '</div>';

        echo '<style>
            .wcp-doc-links .button { padding: 0 8px; height: 24px; line-height: 22px; margin-right: 2px; }
        </style>';
    }

    /**
     * Add order actions meta box
     */
    public function add_order_actions_meta_box() {
        add_meta_box(
            'wcp_order_actions',
            __( 'Document Actions', 'woocommerce-print-documents' ),
            array( $this, 'render_order_actions_meta_box' ),
            'shop_order',
            'side',
            'core'
        );
    }

    /**
     * Render order actions meta box
     */
    public function render_order_actions_meta_box( $post ) {
        $order = wc_get_order( $post->ID );
        if ( ! $order ) {
            return;
        }

        $invoice_number = get_post_meta( $order->get_id(), '_wcp_invoice_number', true );
        $invoice_date   = get_post_meta( $order->get_id(), '_wcp_invoice_date', true );

        ?>
        <div class="wcp-order-actions">
            <p>
                <label><?php esc_html_e( 'Invoice Number:', 'woocommerce-print-documents' ); ?></label>
                <input type="text" id="wcp_invoice_number" value="<?php echo esc_attr( $invoice_number ); ?>" class="regular-text" />
                <button type="button" class="button" id="wcp_save_invoice"><?php esc_html_e( 'Save', 'woocommerce-print-documents' ); ?></button>
            </p>
            <p>
                <label><?php esc_html_e( 'Invoice Date:', 'woocommerce-print-documents' ); ?></label>
                <input type="date" id="wcp_invoice_date" value="<?php echo $invoice_date ? esc_attr( date( 'Y-m-d', strtotime( $invoice_date ) ) ) : ''; ?>" class="regular-text" />
                <button type="button" class="button" id="wcp_save_date"><?php esc_html_e( 'Save', 'woocommerce-print-documents' ); ?></button>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#wcp_save_invoice').on('click', function() {
                var invoice_number = $('#wcp_invoice_number').val();
                var order_id = <?php echo $order->get_id(); ?>;
                
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: {
                        action: 'wcp_save_invoice_number',
                        invoice_number: invoice_number,
                        order_id: order_id,
                        nonce: '<?php echo wp_create_nonce( 'wcp_save_invoice' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php esc_html_e( 'Invoice number saved!', 'woocommerce-print-documents' ); ?>');
                        }
                    }
                });
            });

            $('#wcp_save_date').on('click', function() {
                var invoice_date = $('#wcp_invoice_date').val();
                var order_id = <?php echo $order->get_id(); ?>;
                
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: {
                        action: 'wcp_save_invoice_date',
                        invoice_date: invoice_date,
                        order_id: order_id,
                        nonce: '<?php echo wp_create_nonce( 'wcp_save_date' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php esc_html_e( 'Invoice date saved!', 'woocommerce-print-documents' ); ?>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}