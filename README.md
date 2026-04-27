# WooCommerce Print Documents

Generate, print and email invoices, receipts, delivery notes, packing slips and credit notes for WooCommerce orders.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/woocommerce-print-documents)](https://wordpress.org/plugins/woocommerce-print-documents/)
[![WordPress Rating](https://img.shields.io/wordpress/plugin/rating/woocommerce-print-documents)](https://wordpress.org/support/plugin/woocommerce-print-documents/reviews/)
[![WordPress Tested Up To](https://img.shields.io/wordpress/plugin/tested/woocommerce-print-documents)](https://wordpress.org/plugins/woocommerce-print-documents/)
[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

## Description

WooCommerce Print Documents allows store owners to generate, customize, and print order documents directly from the WooCommerce admin. Supports five document types with PDF generation and email integration.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later
- WooCommerce 5.0 or later

## Installation

### Automatic Installation
1. Go to `Plugins` > `Add New` in WordPress admin
2. Search for "WooCommerce Print Documents"
3. Click `Install Now` and activate the plugin

### Manual Installation
1. Upload the `woocommerce-print-documents` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to `WooCommerce` > `Print Documents` to configure settings

## Features

### Document Types
- **Invoice** - Professional billing document with sequential numbering
- **Receipt** - Payment confirmation document
- **Delivery Note** - Dispatch document for shipments
- **Packing Slip** - Warehouse-ready picking document
- **Credit Note** - Refund and return document

### Customization
- Company logo with adjustable scale and alignment
- Shop name, address, phone and email
- Custom document titles per type
- Paper size (A4, A5, Letter, Legal) and orientation (Portrait/Landscape)
- Individual document enable/disable

### Invoice Numbering
- Sequential invoice numbers
- Customizable number format with placeholders:
  - `{year}` - Current year
  - `{month}` - Current month
  - `{next_number}` - Next invoice number
  - `{order_number}` - WooCommerce order number
  - `{customer}` - Customer name
- Optional yearly reset
- Manual invoice number override per order

### PDF Generation
- Generate PDFs for all document types
- Supports TCPDF and Dompdf
- Fallback to HTML print view if no PDF library available
- Configurable paper size and orientation

### Email Integration
- Attach PDFs to WooCommerce order emails
- Control which order statuses trigger attachments
- Select specific WooCommerce email types to attach to
- Custom email message support

### Customer Access
- Print buttons on My Account Orders page
- Print buttons on order confirmation (thank you) page
- Print links in order emails
- Guest access via secure tokens (no login required)

### Bulk Operations
- Bulk print from WooCommerce Orders page
- Select multiple orders using checkboxes
- Generate merged PDFs for multiple orders

## Usage

### Admin Settings
1. Navigate to `WooCommerce` > `Print Documents`
2. Configure shop information (name, address, logo)
3. Enable desired document types
4. Set invoice numbering options
5. Configure paper size and orientation

### Printing from Order Edit Page
1. Go to `WooCommerce` > `Orders`
2. Click on an order to edit
3. Find the "Print Documents" meta box in the sidebar
4. Click the document button to print or email

### Bulk Printing
1. Go to `WooCommerce` > `Orders`
2. Select multiple orders using checkboxes
3. Choose "Print Documents" from Bulk Actions dropdown
4. Select document type and action

## Hooks & Filters

### Template Data
```php
add_filter('wcp_template_data', function($data, $order, $document_type) {
    // Modify template data
    return $data;
}, 10, 3);
```

### Invoice Number
```php
add_filter('wcp_invoice_number', function($number, $order) {
    return $number;
}, 10, 2);
```

### Order Items
```php
add_filter('wcp_order_items', function($items, $order, $document_type) {
    return $items;
}, 10, 3);
```

### PDF Settings
```php
add_filter('wcp_paper_size', function($size) {
    return 'A4'; // or 'letter', 'legal', 'A5'
});

add_filter('wcp_paper_orientation', function($orientation) {
    return 'portrait'; // or 'landscape'
});
```

## File Structure

```
woocommerce-print-documents/
├── woocommerce-print-documents.php   # Main plugin file
├── includes/
│   ├── class-wc-print-settings.php   # Settings page
│   ├── class-wc-print-document.php   # Document generation
│   ├── class-wc-print-admin.php       # Admin functionality
│   ├── class-wc-print-frontend.php    # Frontend functionality
│   ├── class-wc-print-emails.php      # Email integration
│   ├── class-wc-print-templates.php   # Template helpers
│   └── class-wc-print-ajax.php        # AJAX handlers
├── templates/
│   └── document-default.php           # Default document template
├── assets/
│   ├── css/
│   │   └── admin.css                  # Admin styles
│   └── js/
│       └── admin.js                   # Admin scripts
└── README.md                          # This file
```

## Theme Overrides

Copy `templates/document-default.php` to your theme folder and customize HTML output without losing changes on plugin update.

```php
// In your theme's functions.php
add_filter('wcp_locate_template', function($template, $type) {
    // Use custom template from theme
    return $template;
}, 10, 2);
```

## Frequently Asked Questions

### How do I enable PDF generation?
Install TCPDF or Dompdf library. Without these, the plugin falls back to HTML print view with automatic window.print().

### Can I customize the document template?
Yes. Copy `templates/document-default.php` to your theme folder as `woocommerce-print-documents/templates/document-{type}.php`.

### How do I change the invoice number format?
Go to `WooCommerce` > `Print Documents` > Document Settings. Use placeholders like `{year}{month}{next_number}`.

### How do I add print button to my theme?
```php
$order = wc_get_order($order_id);
$document = new WC_Print_Document($order, 'invoice');
echo '<a href="' . esc_url($document->get_pdf_url()) . '" target="_blank">Print Invoice</a>';
```

## Changelog

### 1.0.0
- Initial release
- Five document types: Invoice, Receipt, Delivery Note, Packing Slip, Credit Note
- Sequential invoice numbering with customizable format
- PDF generation support (TCPDF/Dompdf compatible)
- Email attachment integration with WooCommerce
- Customer print buttons on My Account page
- Bulk print functionality
- Settings page with live preview

## License

GPLv3 or later

## Support

For issues and feature requests, please use the GitHub issues page.