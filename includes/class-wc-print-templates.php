<?php
/**
 * Templates Class
 *
 * Handles document template rendering
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Print_Templates {

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
        // Nothing to initialize
    }

    /**
     * Format address
     */
    public function format_address( $address, $label = '' ) {
        $parts = array();

        if ( ! empty( $address['company'] ) ) {
            $parts[] = $address['company'];
        }

        $name = '';
        if ( ! empty( $address['first_name'] ) || ! empty( $address['last_name'] ) ) {
            $name = trim( $address['first_name'] . ' ' . $address['last_name'] );
            $parts[] = $name;
        }

        if ( ! empty( $address['address_1'] ) ) {
            $parts[] = $address['address_1'];
        }

        if ( ! empty( $address['address_2'] ) ) {
            $parts[] = $address['address_2'];
        }

        $city_line = '';
        if ( ! empty( $address['city'] ) ) {
            $city_line .= $address['city'];
        }
        if ( ! empty( $address['state'] ) ) {
            $city_line .= ', ' . $address['state'];
        }
        if ( ! empty( $address['postcode'] ) ) {
            $city_line .= ' ' . $address['postcode'];
        }

        if ( ! empty( $city_line ) ) {
            $parts[] = $city_line;
        }

        if ( ! empty( $address['country'] ) ) {
            $parts[] = $address['country'];
        }

        $output = '';

        if ( ! empty( $label ) ) {
            $output .= '<strong>' . esc_html( $label ) . '</strong><br>';
        }

        $output .= implode( '<br>', array_map( 'esc_html', $parts ) );

        return $output;
    }

    /**
     * Format phone
     */
    public function format_phone( $phone ) {
        // Apply filter for custom formatting
        return apply_filters( 'wcp_format_phone_number', $phone );
    }

    /**
     * Format price
     */
    public function format_price( $price, $order ) {
        return wp_strip_all_tags( html_entity_decode( wc_price( $price, array(
            'currency' => $order->get_currency(),
        ) ) ) );
    }

    /**
     * Get product image HTML
     */
    public function get_product_image( $image_id, $size = 'thumbnail' ) {
        if ( ! $image_id ) {
            return '';
        }

        $image = wp_get_attachment_image( $image_id, $size, false, array(
            'style' => 'max-width: 50px; height: auto; margin-right: 10px; vertical-align: middle;',
        ) );

        return $image;
    }

    /**
     * Render template section
     */
    public static function render_section( $title, $content, $visible = true ) {
        if ( ! $visible ) {
            return '';
        }

        return '<div class="wcp-section">
            <h3 class="wcp-section-title">' . esc_html( $title ) . '</h3>
            <div class="wcp-section-content">' . $content . '</div>
        </div>';
    }
}