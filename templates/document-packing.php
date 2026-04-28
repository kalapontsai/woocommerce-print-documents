<?php
/**
 * Packing Slip Document Template
 *
 * @package WooCommerce_Print_Documents
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Simple packing slip format (matching slim branch design)
$shop_name    = get_option( 'wcp_shop_name', get_bloginfo( 'name' ) );
$shop_address = get_option( 'wcp_shop_address', '' );
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php esc_html_e( '出貨單', 'woocommerce-print-documents' ); ?> - #<?php echo esc_html( $data['order_number'] ); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body{font-family:sans-serif;padding:20px;font-size:14px}
        h2{text-align:center;margin-bottom:20px}
        .info-grid{display:flex;gap:40px;margin-bottom:20px}
        .info-box{flex:1}
        .info-box p{margin:4px 0}
        table{width:100%;border-collapse:collapse;margin-top:10px}
        td,th{border:1px solid #000;padding:6px}
        th{background:#f0f0f0}
        .footer{margin-top:30px;text-align:center;font-size:12px;color:#666}
        .order-meta{margin-bottom:16px;font-size:13px}
        @media print{body{padding:0}}
    </style>
</head>
<body onload="window.print()">
    <h2><?php esc_html_e( '出貨單', 'woocommerce-print-documents' ); ?> #<?php echo esc_html( $data['order_number'] ); ?></h2>

    <div class="info-grid">
        <div class="info-box">
            <strong><?php esc_html_e( '商店資訊', 'woocommerce-print-documents' ); ?></strong>
            <p><?php echo esc_html( $shop_name ); ?></p>
            <?php if ( $shop_address ) : ?>
                <p><?php echo nl2br( esc_html( $shop_address ) ); ?></p>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <strong><?php esc_html_e( '收件人資訊', 'woocommerce-print-documents' ); ?></strong>
            <p><?php echo esc_html( $data['shipping']['first_name'] . ' ' . $data['shipping']['last_name'] ); ?></p>
            <?php if ( ! empty( $data['shipping']['company'] ) ) : ?>
                <p><?php echo esc_html( $data['shipping']['company'] ); ?></p>
            <?php endif; ?>
            <p><?php echo nl2br( esc_html( $data['shipping']['address_1'] . "\n" . $data['shipping']['city'] . ', ' . $data['shipping']['state'] . ' ' . $data['shipping']['postcode'] . "\n" . $data['shipping']['country'] ) ); ?></p>
            <?php if ( ! empty( $data['shipping']['phone'] ) ) : ?>
                <p><?php esc_html_e( 'Phone:', 'woocommerce-print-documents' ); ?> <?php echo esc_html( $data['shipping']['phone'] ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="order-meta">
        <?php esc_html_e( '訂單編號', 'woocommerce-print-documents' ); ?>: <?php echo esc_html( $data['order_number'] ); ?> |
        <?php esc_html_e( '日期', 'woocommerce-print-documents' ); ?>: <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $data['order_date'] ) ) ); ?>
        <?php if ( ! empty( $data['shipping_method'] ) ) : ?>
            | <?php esc_html_e( '配送方式', 'woocommerce-print-documents' ); ?>: <?php echo esc_html( $data['shipping_method'] ); ?>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th><?php esc_html_e( '商品名稱', 'woocommerce-print-documents' ); ?></th>
                <th style="text-align:center;width:60px;"><?php esc_html_e( '數量', 'woocommerce-print-documents' ); ?></th>
                <th style="text-align:center;width:80px;"><?php esc_html_e( 'SKU', 'woocommerce-print-documents' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $data['items'] as $item ) : ?>
                <tr>
                    <td><?php echo esc_html( $item['name'] ); ?></td>
                    <td style="text-align:center;"><?php echo esc_html( $item['quantity'] ); ?></td>
                    <td style="text-align:center;"><?php echo esc_html( $item['sku'] ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ( ! empty( $data['customer_note'] ) ) : ?>
        <p style="margin-top:16px;"><strong><?php esc_html_e( '客戶備註', 'woocommerce-print-documents' ); ?>:</strong> <?php echo esc_html( $data['customer_note'] ); ?></p>
    <?php endif; ?>

    <div class="footer"><?php esc_html_e( '由 WooCommerce Print Documents 產生', 'woocommerce-print-documents' ); ?></div>
</body>
</html>