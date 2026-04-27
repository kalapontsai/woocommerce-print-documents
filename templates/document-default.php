<?php
/**
 * Default Document Template
 *
 * @package WooCommerce_Print_Documents
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$paper_size      = get_option( 'wcp_paper_size', 'A4' );
$paper_orientation = get_option( 'wcp_paper_orientation', 'portrait' );

// Get paper dimensions in mm
$paper_sizes = array(
    'A4'     => array( 'width' => 210, 'height' => 297 ),
    'A5'     => array( 'width' => 148, 'height' => 210 ),
    'letter' => array( 'width' => 216, 'height' => 279 ),
    'legal'  => array( 'width' => 216, 'height' => 356 ),
);

// Determine if landscape
$is_landscape = 'landscape' === $paper_orientation;
$pw = $is_landscape ? $paper_sizes[ $paper_size ]['height'] : $paper_sizes[ $paper_size ]['width'];
$ph = $is_landscape ? $paper_sizes[ $paper_size ]['width'] : $paper_sizes[ $paper_size ]['height'];

// Format dates
$invoice_date_formatted = date_i18n( get_option( 'date_format' ), strtotime( $data['invoice_date'] ) );
$order_date_formatted   = date_i18n( get_option( 'date_format' ), strtotime( $data['order_date'] ) );

// Get currency symbol
$currency_symbol = isset( $data['totals']['currency_symbol'] ) ? $data['totals']['currency_symbol'] : get_woocommerce_currency_symbol();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html( $data['title'] ); ?> - #<?php echo esc_html( $data['order_number'] ); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            size: <?php echo esc_html( $paper_size ); ?> <?php echo esc_html( $paper_orientation ); ?>;
            margin: 0;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background: #fff;
        }
        
        .document-page {
            width: <?php echo esc_html( $pw ); ?>mm;
            min-height: <?php echo esc_html( $ph ); ?>mm;
            padding: 15mm 20mm;
            margin: 0 auto;
            background: #fff;
        }
        
        /* Header */
        .document-header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        
        .shop-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .shop-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .shop-info {
            font-size: 12px;
            color: #666;
            line-height: 1.8;
        }
        
        .document-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .document-meta {
            font-size: 12px;
            color: #666;
        }
        
        /* Addresses */
        .addresses-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .billing-address,
        .shipping-address {
            display: table-cell;
            width: 48%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .shipping-address {
            margin-left: 4%;
        }
        
        .address-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .address-content {
            font-size: 13px;
            line-height: 1.6;
        }
        
        /* Order details */
        .order-details {
            margin-bottom: 20px;
        }
        
        .order-number {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .order-date {
            font-size: 12px;
            color: #666;
        }
        
        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #333;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .items-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .items-table .product-name {
            font-weight: 500;
        }
        
        .items-table .product-sku {
            font-size: 11px;
            color: #999;
        }
        
        .items-table .product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .items-table td:first-child {
            display: flex;
            align-items: center;
        }
        
        /* Totals */
        .totals-section {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .totals-table tr td {
            padding: 8px 0;
            font-size: 13px;
        }
        
        .totals-table tr td:first-child {
            text-align: left;
            color: #666;
        }
        
        .totals-table tr td:last-child {
            text-align: right;
            width: 120px;
        }
        
        .totals-table tr.total-row td {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 12px;
        }
        
        /* Footer */
        .document-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .footer-notes {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .footer-text {
            font-size: 11px;
            color: #999;
            text-align: center;
        }
        
        /* Payment info */
        .payment-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        
        .payment-info-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.05);
            pointer-events: none;
            z-index: 1000;
            white-space: nowrap;
        }
        
        /* Print styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .document-page {
                width: 100%;
                padding: 10mm;
            }
            
            .items-table th {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="document-page">
        <!-- Header -->
        <div class="document-header">
            <div class="header-left">
                <?php if ( ! empty( $data['shop']['logo'] ) ) : ?>
                    <img src="<?php echo esc_url( $data['shop']['logo'] ); ?>" class="shop-logo" alt="<?php echo esc_attr( $data['shop']['name'] ); ?>">
                <?php endif; ?>
                <div class="shop-name"><?php echo esc_html( $data['shop']['name'] ); ?></div>
                <?php if ( ! empty( $data['shop']['address'] ) ) : ?>
                    <div class="shop-info"><?php echo nl2br( esc_html( $data['shop']['address'] ) ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $data['shop']['phone'] ) ) : ?>
                    <div class="shop-info"><?php esc_html_e( 'Tel:', 'woocommerce-print-documents' ); ?> <?php echo esc_html( $data['shop']['phone'] ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $data['shop']['email'] ) ) : ?>
                    <div class="shop-info"><?php echo esc_html( $data['shop']['email'] ); ?></div>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <div class="document-title"><?php echo esc_html( $data['title'] ); ?></div>
                <div class="document-meta">
                    <strong><?php esc_html_e( 'Invoice #:', 'woocommerce-print-documents' ); ?></strong> <?php echo esc_html( $data['invoice_number'] ); ?><br>
                    <strong><?php esc_html_e( 'Date:', 'woocommerce-print-documents' ); ?></strong> <?php echo esc_html( $invoice_date_formatted ); ?><br>
                    <strong><?php esc_html_e( 'Order #:', 'woocommerce-print-documents' ); ?></strong> <?php echo esc_html( $data['order_number'] ); ?><br>
                    <strong><?php esc_html_e( 'Order Date:', 'woocommerce-print-documents' ); ?></strong> <?php echo esc_html( $order_date_formatted ); ?>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses-section">
            <div class="billing-address">
                <div class="address-label"><?php esc_html_e( 'Billing Address', 'woocommerce-print-documents' ); ?></div>
                <div class="address-content">
                    <?php if ( ! empty( $data['billing']['company'] ) ) : ?>
                        <strong><?php echo esc_html( $data['billing']['company'] ); ?></strong><br>
                    <?php endif; ?>
                    <?php if ( ! empty( $data['billing']['first_name'] ) || ! empty( $data['billing']['last_name'] ) ) : ?>
                        <?php echo esc_html( $data['billing']['first_name'] . ' ' . $data['billing']['last_name'] ); ?><br>
                    <?php endif; ?>
                    <?php echo esc_html( $data['billing']['address_1'] ); ?><br>
                    <?php if ( ! empty( $data['billing']['address_2'] ) ) : ?>
                        <?php echo esc_html( $data['billing']['address_2'] ); ?><br>
                    <?php endif; ?>
                    <?php echo esc_html( $data['billing']['city'] ); ?>, <?php echo esc_html( $data['billing']['state'] ); ?> <?php echo esc_html( $data['billing']['postcode'] ); ?><br>
                    <?php echo esc_html( $data['billing']['country'] ); ?><br>
                    <?php if ( ! empty( $data['billing']['email'] ) ) : ?>
                        <br><?php echo esc_html( $data['billing']['email'] ); ?>
                    <?php endif; ?>
                    <?php if ( ! empty( $data['billing']['phone'] ) ) : ?>
                        <br><?php esc_html_e( 'Phone:', 'woocommerce-print-documents' ); ?> <?php echo esc_html( $data['billing']['phone'] ); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php
            // Only show shipping address if it's different from billing
            $show_shipping = false;
            if ( ! empty( $data['shipping']['address_1'] ) && $data['shipping']['address_1'] !== $data['billing']['address_1'] ) {
                $show_shipping = true;
            }
            ?>
            
            <?php if ( $show_shipping ) : ?>
                <div class="shipping-address">
                    <div class="address-label"><?php esc_html_e( 'Shipping Address', 'woocommerce-print-documents' ); ?></div>
                    <div class="address-content">
                        <?php if ( ! empty( $data['shipping']['company'] ) ) : ?>
                            <strong><?php echo esc_html( $data['shipping']['company'] ); ?></strong><br>
                        <?php endif; ?>
                        <?php if ( ! empty( $data['shipping']['first_name'] ) || ! empty( $data['shipping']['last_name'] ) ) : ?>
                            <?php echo esc_html( $data['shipping']['first_name'] . ' ' . $data['shipping']['last_name'] ); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html( $data['shipping']['address_1'] ); ?><br>
                        <?php if ( ! empty( $data['shipping']['address_2'] ) ) : ?>
                            <?php echo esc_html( $data['shipping']['address_2'] ); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html( $data['shipping']['city'] ); ?>, <?php echo esc_html( $data['shipping']['state'] ); ?> <?php echo esc_html( $data['shipping']['postcode'] ); ?><br>
                        <?php echo esc_html( $data['shipping']['country'] ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment info (for receipts) -->
        <?php if ( 'receipt' === $data['document_type'] && ! empty( $data['payment_method'] ) ) : ?>
            <div class="payment-info">
                <div class="payment-info-title"><?php esc_html_e( 'Payment Method', 'woocommerce-print-documents' ); ?></div>
                <?php echo esc_html( $data['payment_method'] ); ?>
                <?php if ( ! empty( $data['shipping_method'] ) ) : ?>
                    <br><strong><?php esc_html_e( 'Shipping Method:', 'woocommerce-print-documents' ); ?></strong> <?php echo esc_html( $data['shipping_method'] ); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Items table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Product', 'woocommerce-print-documents' ); ?></th>
                    <th><?php esc_html_e( 'SKU', 'woocommerce-print-documents' ); ?></th>
                    <th style="text-align: center;"><?php esc_html_e( 'Qty', 'woocommerce-print-documents' ); ?></th>
                    <th><?php esc_html_e( 'Price', 'woocommerce-print-documents' ); ?></th>
                    <th><?php esc_html_e( 'Total', 'woocommerce-print-documents' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $data['items'] as $item ) : ?>
                    <tr>
                        <td>
                            <?php if ( ! empty( $item['image_id'] ) ) : ?>
                                <?php
                                $image_url = wp_get_attachment_image_url( $item['image_id'], 'thumbnail' );
                                if ( $image_url ) :
                                    ?>
                                    <img src="<?php echo esc_url( $image_url ); ?>" class="product-image" alt="">
                                <?php endif; ?>
                            <?php endif; ?>
                            <span class="product-name"><?php echo esc_html( $item['name'] ); ?></span>
                        </td>
                        <td class="product-sku"><?php echo esc_html( $item['sku'] ); ?></td>
                        <td style="text-align: center;"><?php echo esc_html( $item['quantity'] ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $item['price'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $item['total'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td><?php esc_html_e( 'Subtotal', 'woocommerce-print-documents' ); ?></td>
                    <td><?php echo wp_kses_post( wc_price( $data['totals']['subtotal'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                </tr>
                <?php if ( ! empty( $data['totals']['discount'] ) && $data['totals']['discount'] > 0 ) : ?>
                    <tr>
                        <td><?php esc_html_e( 'Discount', 'woocommerce-print-documents' ); ?></td>
                        <td>-<?php echo wp_kses_post( wc_price( $data['totals']['discount'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $data['totals']['shipping'] ) && $data['totals']['shipping'] > 0 ) : ?>
                    <tr>
                        <td><?php esc_html_e( 'Shipping', 'woocommerce-print-documents' ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $data['totals']['shipping'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $data['totals']['tax'] ) && $data['totals']['tax'] > 0 ) : ?>
                    <tr>
                        <td><?php esc_html_e( 'Tax', 'woocommerce-print-documents' ); ?></td>
                        <td><?php echo wp_kses_post( wc_price( $data['totals']['tax'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td><?php echo esc_html( $data['title'] ); ?> <?php esc_html_e( 'Total', 'woocommerce-print-documents' ); ?></td>
                    <td><?php echo wp_kses_post( wc_price( $data['totals']['total'], array( 'currency' => $data['totals']['currency'] ) ) ); ?></td>
                </tr>
            </table>
        </div>

        <!-- Customer note -->
        <?php if ( ! empty( $data['customer_note'] ) ) : ?>
            <div class="footer-notes">
                <strong><?php esc_html_e( 'Customer Note:', 'woocommerce-print-documents' ); ?></strong><br>
                <?php echo esc_html( $data['customer_note'] ); ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="document-footer">
            <div class="footer-text">
                <?php
                $footer_text = get_option( 'wcp_footer_text', '' );
                if ( ! empty( $footer_text ) ) {
                    echo nl2br( esc_html( $footer_text ) );
                } else {
                    echo esc_html( $data['shop']['name'] ); ?> - <?php echo esc_html( home_url() );
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>