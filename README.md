# WooCommerce Shipping Slip V3

WooCommerce 訂單出貨單列印插件，在訂單頁面提供便捷的出貨單列印功能。

## 功能

- 在 WooCommerce 訂單編輯頁面顯示「列印出貨單」按鈕
- 產生格式專業的 Packing Slip，包含：
  - 雙欄頁眉（商店名稱 + 文件資訊）
  - 收件人資訊區塊
  - 商品列表（含商品縮圖、SKU、數量、單價、小計）
  - 金額總計
  - 客戶備註
- 自動列印（開啟時觸發 window.print()）

## 安裝

1. 下載 plugin 資料夾
2. 上傳至 WordPress 的 `/wp-content/plugins/woocommerce-shipping-slip-v3/`
3. 在 WordPress 後台啟用插件
4. 前往 WooCommerce > 訂單，點擊任一訂單即可看到「列印出貨單」按鈕

## 螢幕截圖

出貨單輸出格式包含：
- 左側：品牌名稱「艾沙順勢糖球」
- 右側：Invoice #、Order #、Date
- 收件人資訊方框
- 表格顯示商品（含縮圖）
- 數量、單價、小計（右對齊）
- 間行換色（Zebra striping）

## 版本歷史

### 3.4
- 修正按鈕位置相容性
- 移至 `woocommerce_order_actions_start` hook

### 3.3
- 優化 admin_print_scripts 載入時機
- 加入延遲載入確保 DOM 就緒

### 3.2
- 將按鈕移至頁面頂部標題區
- 移除訂單詳細資料區塊的按鈕

### 3.1
- 新增多項格式優化：
  - 雙欄頁眉佈局
  - 收件人資訊框
  - 表格間行換色
  - 商品縮圖顯示
  - 數字右對齊
  - 金額總計區
  - Customer Note 顯示

### 3.0
- 初始版本（支援 HPOS）

## 需求

- WordPress 6.0+
- PHP 7.4+
- WooCommerce 5.0+（建議使用 HPOS）

## 授權

GPLv3 or later