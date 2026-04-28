<?php
/**
 * Settings Class
 *
 * Handles plugin settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Settings {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'wc-print-documents';

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
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Print Documents', 'woocommerce-print-documents' ),
            __( 'Print Documents', 'woocommerce-print-documents' ),
            'manage_woocommerce',
            self::PAGE_SLUG,
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General Settings
        register_setting( 'wcp_settings_group', 'wcp_shop_name', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_shop_address', array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_shop_phone', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_shop_email', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_logo_url', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_invoice_start', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_invoice_reset_yearly', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_documents', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_documents' ),
        ) );

        register_setting( 'wcp_settings_group', 'wcp_paper_size', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'wcp_settings_group', 'wcp_paper_orientation', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        // Document settings sections
        add_settings_section(
            'wcp_general_section',
            __( 'General Settings', 'woocommerce-print-documents' ),
            array( $this, 'general_section_callback' ),
            self::PAGE_SLUG
        );

        add_settings_section(
            'wcp_documents_section',
            __( 'Document Settings', 'woocommerce-print-documents' ),
            array( $this, 'documents_section_callback' ),
            self::PAGE_SLUG
        );

        add_settings_section(
            'wcp_template_section',
            __( 'Template Settings', 'woocommerce-print-documents' ),
            array( $this, 'template_section_callback' ),
            self::PAGE_SLUG
        );

        // General fields
        add_settings_field( 'wcp_shop_name', __( 'Shop Name', 'woocommerce-print-documents' ), array( $this, 'shop_name_field' ), self::PAGE_SLUG, 'wcp_general_section' );
        add_settings_field( 'wcp_shop_address', __( 'Shop Address', 'woocommerce-print-documents' ), array( $this, 'shop_address_field' ), self::PAGE_SLUG, 'wcp_general_section' );
        add_settings_field( 'wcp_shop_phone', __( 'Shop Phone', 'woocommerce-print-documents' ), array( $this, 'shop_phone_field' ), self::PAGE_SLUG, 'wcp_general_section' );
        add_settings_field( 'wcp_shop_email', __( 'Shop Email', 'woocommerce-print-documents' ), array( $this, 'shop_email_field' ), self::PAGE_SLUG, 'wcp_general_section' );
        add_settings_field( 'wcp_logo_url', __( 'Shop Logo', 'woocommerce-print-documents' ), array( $this, 'logo_url_field' ), self::PAGE_SLUG, 'wcp_general_section' );

        // Invoice settings
        add_settings_field( 'wcp_invoice_start', __( 'Invoice Start Number', 'woocommerce-print-documents' ), array( $this, 'invoice_start_field' ), self::PAGE_SLUG, 'wcp_documents_section' );
        add_settings_field( 'wcp_invoice_reset_yearly', __( 'Reset Invoice Number Yearly', 'woocommerce-print-documents' ), array( $this, 'invoice_reset_yearly_field' ), self::PAGE_SLUG, 'wcp_documents_section' );

        // Template fields
        add_settings_field( 'wcp_paper_size', __( 'Paper Size', 'woocommerce-print-documents' ), array( $this, 'paper_size_field' ), self::PAGE_SLUG, 'wcp_template_section' );
        add_settings_field( 'wcp_paper_orientation', __( 'Paper Orientation', 'woocommerce-print-documents' ), array( $this, 'paper_orientation_field' ), self::PAGE_SLUG, 'wcp_template_section' );
    }

    /**
     * Sanitize documents array
     */
    public function sanitize_documents( $input ) {
        $sanitized = array();
        $allowed_types = array( 'invoice', 'receipt', 'delivery', 'packing', 'credit' );

        if ( is_array( $input ) ) {
            foreach ( $input as $type => $data ) {
                if ( in_array( $type, $allowed_types, true ) ) {
                    $sanitized[ sanitize_key( $type ) ] = array(
                        'active' => ! empty( $data['active'] ) ? 'yes' : 'no',
                        'title'  => sanitize_text_field( $data['title'] ),
                    );
                }
            }
        }

        return $sanitized;
    }

    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__( 'Configure your shop information that appears on documents.', 'woocommerce-print-documents' ) . '</p>';
    }

    /**
     * Documents section callback
     */
    public function documents_section_callback() {
        echo '<p>' . esc_html__( 'Configure document types and invoice numbering.', 'woocommerce-print-documents' ) . '</p>';
    }

    /**
     * Template section callback
     */
    public function template_section_callback() {
        echo '<p>' . esc_html__( 'Configure paper and template settings.', 'woocommerce-print-documents' ) . '</p>';
    }

    /**
     * Shop name field
     */
    public function shop_name_field() {
        $value = get_option( 'wcp_shop_name', get_bloginfo( 'name' ) );
        echo '<input type="text" name="wcp_shop_name" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    /**
     * Shop address field
     */
    public function shop_address_field() {
        $value = get_option( 'wcp_shop_address', '' );
        echo '<textarea name="wcp_shop_address" rows="3" class="regular-text">' . esc_textarea( $value ) . '</textarea>';
    }

    /**
     * Shop phone field
     */
    public function shop_phone_field() {
        $value = get_option( 'wcp_shop_phone', '' );
        echo '<input type="text" name="wcp_shop_phone" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    /**
     * Shop email field
     */
    public function shop_email_field() {
        $value = get_option( 'wcp_shop_email', '' );
        echo '<input type="email" name="wcp_shop_email" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    /**
     * Logo URL field
     */
    public function logo_url_field() {
        $value  = get_option( 'wcp_logo_url', '' );
        $upload_url = admin_url( 'media-upload.php?post_id=0&TB_iframe=true&width=640&height=150' );
        ?>
        <input type="text" name="wcp_logo_url" id="wcp_logo_url" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <button type="button" class="button button-secondary" id="wcp_upload_logo"><?php esc_html_e( 'Upload Logo', 'woocommerce-print-documents' ); ?></button>
        <script>
        jQuery(document).ready(function($) {
            $('#wcp_upload_logo').click(function() {
                var frame = wp.media({
                    title: '<?php esc_html_e( 'Select Logo', 'woocommerce-print-documents' ); ?>',
                    button: { text: '<?php esc_html_e( 'Use as Logo', 'woocommerce-print-documents' ); ?>' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#wcp_logo_url').val(attachment.url);
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Invoice start field
     */
    public function invoice_start_field() {
        $value = get_option( 'wcp_invoice_start', 1 );
        echo '<input type="number" name="wcp_invoice_start" value="' . esc_attr( $value ) . '" min="1" class="small-text" />';
        echo '<p class="description">' . esc_html__( 'The next invoice number will be used as the starting point.', 'woocommerce-print-documents' ) . '</p>';
    }

    /**
     * Invoice reset yearly field
     */
    public function invoice_reset_yearly_field() {
        $value = get_option( 'wcp_invoice_reset_yearly', 'no' );
        echo '<select name="wcp_invoice_reset_yearly">';
        echo '<option value="no" ' . selected( $value, 'no', false ) . '>' . esc_html__( 'No', 'woocommerce-print-documents' ) . '</option>';
        echo '<option value="yes" ' . selected( $value, 'yes', false ) . '>' . esc_html__( 'Yes', 'woocommerce-print-documents' ) . '</option>';
        echo '</select>';
    }

    /**
     * Paper size field
     */
    public function paper_size_field() {
        $value = get_option( 'wcp_paper_size', 'A4' );
        ?>
        <select name="wcp_paper_size">
            <option value="A4" <?php selected( $value, 'A4' ); ?>><?php esc_html_e( 'A4', 'woocommerce-print-documents' ); ?></option>
            <option value="A5" <?php selected( $value, 'A5' ); ?>><?php esc_html_e( 'A5', 'woocommerce-print-documents' ); ?></option>
            <option value="letter" <?php selected( $value, 'letter' ); ?>><?php esc_html_e( 'Letter', 'woocommerce-print-documents' ); ?></option>
            <option value="legal" <?php selected( $value, 'legal' ); ?>><?php esc_html_e( 'Legal', 'woocommerce-print-documents' ); ?></option>
        </select>
        <?php
    }

    /**
     * Paper orientation field
     */
    public function paper_orientation_field() {
        $value = get_option( 'wcp_paper_orientation', 'portrait' );
        ?>
        <select name="wcp_paper_orientation">
            <option value="portrait" <?php selected( $value, 'portrait' ); ?>><?php esc_html_e( 'Portrait', 'woocommerce-print-documents' ); ?></option>
            <option value="landscape" <?php selected( $value, 'landscape' ); ?>><?php esc_html_e( 'Landscape', 'woocommerce-print-documents' ); ?></option>
        </select>
        <?php
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets( $hook ) {
        if ( 'woocommerce_page_wc-print-documents' !== $hook ) {
            return;
        }

        wp_enqueue_media();
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $documents = get_option( 'wcp_documents', array(
            'packing' => array( 'active' => 'yes', 'title' => 'Packing Slip' ),
        ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Print Documents Settings', 'woocommerce-print-documents' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'wcp_settings_group' ); ?>
                <?php do_settings_sections( self::PAGE_SLUG ); ?>

                <h2><?php esc_html_e( 'Document Types', 'woocommerce-print-documents' ); ?></h2>
                <table class="widefat" id="wcp-documents-table">
                    <thead>
                        <tr>
                            <th width="100"><?php esc_html_e( 'Enable', 'woocommerce-print-documents' ); ?></th>
                            <th><?php esc_html_e( 'Document Type', 'woocommerce-print-documents' ); ?></th>
                            <th><?php esc_html_e( 'Title', 'woocommerce-print-documents' ); ?></th>
                            <th width="150"><?php esc_html_e( 'Actions', 'woocommerce-print-documents' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $documents as $type => $data ) : ?>
                        <tr data-type="<?php echo esc_attr( $type ); ?>">
                            <td>
                                <input type="checkbox" name="wcp_documents[<?php echo esc_attr( $type ); ?>][active]" value="1" <?php checked( $data['active'], 'yes' ); ?> />
                            </td>
                            <td><strong><?php echo esc_html( ucfirst( $type ) ); ?></strong></td>
                            <td>
                                <input type="text" name="wcp_documents[<?php echo esc_attr( $type ); ?>][title]" value="<?php echo esc_attr( $data['title'] ); ?>" class="regular-text" />
                            </td>
                            <td>
                                <button type="button" class="button wcp-preview-btn" data-type="<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Preview', 'woocommerce-print-documents' ); ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>

            <!-- Preview Modal -->
            <div id="wcp-preview-modal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <iframe id="wcp-preview-frame" src="" width="100%" height="600"></iframe>
                </div>
            </div>
        </div>

        <style>
        .modal { position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 5% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: 8px; }
        .close { float: right; cursor: pointer; font-size: 28px; }
        #wcp-documents-table { margin-top: 10px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Open preview modal
            $('.wcp-preview-btn').on('click', function() {
                var type = $(this).data('type');
                var url = '<?php echo admin_url( 'admin-ajax.php' ); ?>?action=wcp_preview_document&type=' + type + '&TB_iframe=true&width=800&height=600';
                tb_show('<?php esc_html_e( 'Document Preview', 'woocommerce-print-documents' ); ?>', url);
            });
        });
        </script>
        <?php
    }

    /**
     * Get option helper
     */
    public static function get_option( $key, $default = '' ) {
        return get_option( $key, $default );
    }
}