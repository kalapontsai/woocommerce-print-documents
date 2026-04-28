<?php
/*
Plugin Name: WooCommerce Shipping Slip V3
Description: Simple shipping slip print button for WooCommerce HPOS/new order page.
Version: 3.7
*/
if(!defined('ABSPATH')) exit;

add_action('before_woocommerce_init', function(){
 if(class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')){
   \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
 }
});

// 使用 WooCommerce 原生的 order actions hook
add_action('woocommerce_order_actions_start', function($order_id){
 $nonce = wp_create_nonce('wssv3');
 $url = admin_url('admin-ajax.php?action=wssv3_print&order_id='.$order_id.'&_wpnonce='.$nonce);
 echo '<li>';
 echo '<a href="'.$url.'" class="wssv3-print-btn" target="_blank" style="display:inline-block;padding:6px 12px;background:#f0f0f0;border:1px solid #ccc;border-radius:3px;text-decoration:none;color:#555;font-size:13px;margin-top:5px;">';
 echo '列印出貨單';
 echo '</a>';
 echo '</li>';
});

add_action('wp_ajax_wssv3_print','wssv3_print');
function wssv3_print(){
 if(!current_user_can('manage_woocommerce')) wp_die('no');
 check_admin_referer('wssv3');
 $order=wc_get_order(intval($_GET['order_id']));
 if(!$order) wp_die('order not found');

 // 取得資料
 $order_id = $order->get_id();
 $date_created = $order->get_date_created()->date('Y-m-d');

 // Invoice # = 2026040002 格式：YYYYMMDD + 訂單ID（不足4位補0）
 $invoice_num = date('Ymd', strtotime($date_created)) . str_pad($order_id, 4, '0', STR_PAD_LEFT);

 // 收件人資料
 $first_name = $order->get_shipping_first_name();
 $last_name = $order->get_shipping_last_name();
 $full_name = $first_name . ' ' . $last_name;
 $address = $order->get_formatted_shipping_address();
 $email = $order->get_billing_email();
 $phone = $order->get_billing_phone();

 // 金額計算 - 使用 WooCommerce 的正確方法
 $total_discount = $order->get_discount_total() + $order->get_discount_tax(); // 折扣金額
 $grand_total = $order->get_total(); // 顧客實付
 // 原始小計 = 實付 + 折扣
 $subtotal_excl_discount = $grand_total + $total_discount;

 // 取得商品項目
 $items = $order->get_items();

 echo '<html><head><meta charset="utf-8"><title>出貨單</title>';
 echo '<style>
 body{font-family:sans-serif;padding:20px;font-size:13px;color:#333}
 .header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #333;padding-bottom:15px;margin-bottom:20px}
 .header-left{font-size:24px;font-weight:bold}
 .header-right{text-align:right}
 .header-right h2{margin:0 0 8px 0;font-size:20px}
 .header-right p{margin:3px 0;font-size:12px;color:#555}
 .info-box{border:1px solid #ccc;padding:12px 15px;margin-bottom:20px}
 .info-box h3{margin:0 0 10px 0;font-size:14px;font-weight:bold;color:#333}
 .info-box p{margin:4px 0;font-size:12px}
 table{width:100%;border-collapse:collapse;margin-bottom:20px}
 th{background:#555;color:#fff;padding:8px 6px;text-align:left;font-size:12px;font-weight:bold}
 td{background:#fff;padding:6px;border-bottom:1px solid #eee;vertical-align:middle;font-size:12px}
 tr:nth-child(even) td{background:#f5f5f5}
 .product-name{font-size:12px}
 .sku-cell{color:#888;font-size:11px}
 .qty-cell,.price-cell,.total-cell{text-align:right}
 .price-cell,.total-cell{white-space:nowrap}
 .footer{display:flex;justify-content:flex-end;border-top:2px solid #333;padding-top:15px}
 .total-box{text-align:right}
 .total-box .row{display:flex;justify-content:flex-end;gap:40px;margin-bottom:5px;font-size:13px}
 .total-box .discount-row{color:#c00}
 .total-box .grand-total{font-size:16px;font-weight:bold;margin-top:8px}
 .customer-note{margin-top:20px;font-size:12px;color:#555}
 </style>';
 echo '</head><body>';

 // 雙欄抬頭
 echo '<div class="header">';
 echo '<div class="header-left">艾沙順勢糖球</div>';
 echo '<div class="header-right">';
 echo '<h2>Packing Slip</h2>';
 echo '<p>Invoice #: ' . $invoice_num . '</p>';
 echo '<p>Order #: ' . $order_id . '</p>';
 echo '<p>Date: ' . $date_created . '</p>';
 echo '</div>';
 echo '</div>';

 // 收件人資訊
 echo '<div class="info-box">';
 echo '<h3>BILLING ADDRESS</h3>';
 echo '<p><strong>' . $full_name . '</strong></p>';
 echo '<p>' . $address . '</p>';
 echo '<p>Email: ' . $email . '</p>';
 echo '<p>Tel: ' . $phone . '</p>';
 echo '</div>';

 // 商品列表
 echo '<table>';
 echo '<thead><tr>';
 echo '<th>PRODUCT</th>';
 echo '<th>SKU</th>';
 echo '<th style="text-align:right">QTY</th>';
 echo '<th style="text-align:right">PRICE</th>';
 echo '<th style="text-align:right">TOTAL</th>';
 echo '</tr></thead>';
 echo '<tbody>';

 foreach($items as $item){
   $product = $item->get_product();
   $product_name = $item->get_name();
   $sku = $product ? $product->get_sku() : '';
   $qty = $item->get_quantity();
   // PRICE 和 TOTAL 都顯示折扣後的金額
   $line_total = $item->get_total(); // 折扣後小計
   $unit_price = $qty > 0 ? $line_total / $qty : 0; // 折扣後單價

   echo '<tr>';
   echo '<td><span class="product-name">'.$product_name.'</span></td>';
   echo '<td class="sku-cell">'.$sku.'</td>';
   echo '<td class="qty-cell">'.$qty.'</td>';
   echo '<td class="price-cell">NT$'.$unit_price.'</td>';
   echo '<td class="total-cell">NT$'.$line_total.'</td>';
   echo '</tr>';
 }

 echo '</tbody>';
 echo '</table>';

 // 總計區
 echo '<div class="footer">';
 echo '<div class="total-box">';
 echo '<div class="row"><span>Subtotal:</span><span>NT$'.$subtotal_excl_discount.'</span></div>';
 if($total_discount > 0){
   echo '<div class="row discount-row"><span>Discount:</span><span>-NT$'.$total_discount.'</span></div>';
 }
 echo '<div class="grand-total">Packing Slip Total: NT$'.$grand_total.'</div>';
 echo '</div>';
 echo '</div>';

 // 客戶備註
 $customer_note = $order->get_customer_note();
 if($customer_note){
   echo '<div class="customer-note">';
   echo '<strong>Customer Note:</strong> ' . esc_html($customer_note);
   echo '</div>';
 }

 echo '<script>window.onload=function(){window.print()}</script>';
 echo '</body></html>';
 exit;
}