# WooCommerce Print Documents

Generate, print and email invoices, receipts, delivery notes, packing slips and credit notes for WooCommerce orders.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later
- WooCommerce 5.0 or later

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via WordPress Plugins page
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Print Documents to configure settings

## Features

### Document Types
- **Invoice** - Professional billing document with sequential numbering
- **Receipt** - Payment confirmation document  
- **Delivery Note** - Dispatch document for shipments
- **Packing Slip** - Warehouse-ready picking document
- **Credit Note** - Refund and return document

### Customization
- Company logo with adjustable settings
- Shop name, address, phone and email
- Custom document titles per type
- Paper size (A4, A5, Letter, Legal) and orientation (Portrait/Landscape)
- Individual document enable/disable

### Invoice Numbering
- Sequential invoice numbers
- Customizable number format with placeholders
- Optional yearly reset
- Manual invoice number override per order

### PDF Generation
- Generate PDFs for all document types
- Email PDF attachments to customers
- Bulk print from WooCommerce Orders page

### Customer Access
- Print buttons on My Account Orders page
- Print buttons on order confirmation page
- Print links in order emails
- Guest access via secure tokens

### Integration
- Attach PDFs to WooCommerce order emails
- Custom email recipient support
- WordPress REST API hooks for developers

## Usage

### Admin Settings
1. Navigate to WooCommerce > Print Documents
2. Configure shop information (name, address, logo)
3. Enable desired document types
4. Set invoice numbering options
5. Configure paper size and orientation

### Printing from Order Edit Page
1. Go to WooCommerce > Orders
2. Click on an order to edit
3. Find the "Print Documents" meta box in the sidebar
4. Click the document button to print or email

### Bulk Printing
1. Go to WooCommerce > Orders
2. Select multiple orders using checkboxes
3. Choose "Print Documents" from Bulk Actions dropdown
4. Select document type and action

### Customer Print Button
Customers can print documents from:
- My Account > Orders page
- Order confirmation (thank you) page
- Link in order emails

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
│   ├── class-wc-print-admin.php      # Admin functionality
│   ├── class-wc-print-frontend.php   # Frontend functionality
│   ├── class-wc-print-emails.php     # Email integration
│   ├── class-wc-print-templates.php  # Template helpers
│   └── class-wc-print-ajax.php       # AJAX handlers
├── templates/
│   └── document-default.php          # Default template
├── assets/
│   ├── css/
│   │   └── admin.css                 # Admin styles
│   └── js/
│       └── admin.js                  # Admin scripts
└── languages/                        # Translation files
```

## Changelog

### 1.0.0
- Initial release
- Five document types: Invoice, Receipt, Delivery Note, Packing Slip, Credit Note
- Sequential invoice numbering with customizable format
- PDF generation with paper size and orientation options
- Email attachment support
- Customer print buttons on My Account page
- Bulk print functionality
- WooCommerce order email integration

## License

GPLv3 or later

## Support

For issues and feature requests, please use the WordPress support forums or submit a pull request on GitHub.