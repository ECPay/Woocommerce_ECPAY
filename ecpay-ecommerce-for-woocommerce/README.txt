=== ecpay-ecommerce-for-woocommerce ===
Contributors: ecpaytechsupport
Tags: ecommerce, e-commerce, store, sales, sell, shop, cart, checkout, payment, ecpay
Requires at least: 6.0
Tested up to: 6.0
Requires PHP: 8.2
Stable tag: 1.1.2403150
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Frequently Asked Questions ==
= 系統需求 =

- PHP version 8.1
- MySQL version 5.7 or greater

= 其它問題 =
請來信詢問綠界技術服務信箱: techsupport@ecpay.com.tw

== Changelog ==

v1.1.2403150
修正貨到付款使用永久連結結帳失敗問題
修正虛擬商品結帳失敗問題

v1.1.2312190
支援 HPOS

v1.0.22007080
修正相容性至 WordPress Version 6.0
修正相容性至 WooCommerce Version 6.5.1

v1.0.221123
修正自動物流訂單姓名欄位倒置問題
增加中華郵政物流

v1.0.2304120
綠界服務優化設定

v1.0.2305230
修正前綴詞長度設定過長會造成訂單重覆問題

v1.0.2306280
修正稅金啟用發票開立失敗問題
新增黑貓、7-11離島物流設定

v1.0.2309210
調整建立綠界金流訂單及綠界物流訂單，帶入綠界的時間參數(MerchantTradeDate)，改用 WordPress function date_i18n() 取得時間

v1.0.2309210
調整API URL符合永久連結設定

v1.0.2310050
新增定期定額付款方式

v1.1.2311240
修正相容性至 WordPress Version 6.3.1
修正相容性至 WooCommerce Version 8.1.1
新增歐付寶TWQR行動支付付款方式
新增無卡分期付款方式
綠界服務優化設定

== Upgrade Notice ==

Upgrade Notice 請使用https://github.com/ECPay/Woocommerce_ECPAY

== Screenshots ==



綠界科技 WooCommerce 模組
===============
<p align="center">
    <img alt="Last Release" src="https://img.shields.io/github/release/ECPay/Woocommerce_ECPAY.svg">
</p>

== Description ==

* 綠界科技外掛套件，提供合作特店以及個人賣家使用開放原始碼商店系統時，無須自行處理複雜的檢核，直接透過安裝設定外掛套件，便可快速介接綠界科技系統，進行金流、物流、電子發票操作。


目錄
-----------------
* [支援版本](#支援版本)
* [安裝](#安裝)
* [設定與功能項目](#設定與功能項目)
    1. [參數設定](#參數設定)
    2. [後台訂單](#後台訂單)
* [技術支援](#技術支援)
* [附錄](#附錄)
* [版權宣告](#版權宣告)



支援版本
-----------------
| Wordpress  | WooCommerce | PHP |
| :---------: | :----------: | :----------: |
| 6.0 | 6.5.1 | 7.4 |


安裝
-----------------
#### 解壓縮套件檔
將下載的套件檔解壓縮，解壓縮完成後中會有一份壓縮檔「ecpay-ecommerce-for-woocommerce.zip」，用來上傳的外掛模組。

#### 上傳模組
`購物車後台` -> `外掛(Plugins)` -> `安裝外掛(Add New)` -> `上傳外掛(Upload Plugin)` -> `選擇檔案(選擇壓縮檔「ecpay-ecommerce-for-woocommerce.zip」)`-> `立即安裝(Install Now)`。

#### 啟用模組
安裝完成後，畫面會顯示是否安裝成功，若安裝成功會出現`啟用外掛(Active Plugin)`的按鈕，按下`啟用外掛(Active Plugin)`後即可開始使用綠界模組。

設定與功能項目
-----------------

#### 參數設定
##### 設定路徑
- `購物車後台` -> `WooCommerce` -> `設定(Settings)`，點選綠界科技分頁。

##### 主要設定
- 您可在此勾選需要啟用的綠界服務。
##### 金流設定
- 您可在此設定金流相關參數。
    - 訂單編號前綴
    - 綠界訂單顯示商品名稱
    - 啟用測試模式
    - 商店代號(Merchant ID)
    - 金鑰(Hash Key)
    - 向量(Hash IV)

##### 物流設定
- 您可在此設定物流相關參數。
    - 訂單編號前綴
    - 自動建立物流訂單
    - 寄件人姓名
    - 寄件人電話
    - 寄件人手機
    - 寄件人郵遞區號
    - 寄件人地址
    - 啟用離島物流
    - 啟用測試模式
    - 商店代號(Merchant ID)
    - 金鑰(Hash Key)
    - 向量(Hash IV)

- 您需要至 `運送方式` -> `運送區域`-> `編輯` -> `新增運送方式` ，加入要提供的綠界物流種類，並可進入個別物流種類中編輯運費、免運以及啟用門檻。

##### 電子發票設定
- 您可在此設定電子發票相關參數。
    - 訂單編號前綴
    - 開立發票模式
    - 作廢發票模式
    - 延期開立天數
    - 預設捐贈單位
    - 啟用測試模式
    - 商店代號(Merchant ID)
    - 金鑰(Hash Key)
    - 向量(Hash IV)
##### 注意事項
- 如需超商取貨付款功能，請至 - `購物車後台` -> `WooCommerce` -> `設定(Settings)` -> `付款` -> `貨到付款` -> `啟用運送方式` ，加入超商取貨付款的物流種類。

#### 後台訂單

- 您可在訂單詳細資料中操作相關動作。
    - 物流
        - 變更門市
        - 建立物流訂單(手動模式下)
    - 發票
        - 開立發票(手動模式下)
        - 作廢發票(手動模式下)

技術支援
-----------------
綠界技術服務工程師信箱: techsupport@ecpay.com.tw


附錄
-----------------

#### 測試串接參數

|  | 特店編號(MerchantID) | HashKey | HashIV |
| -------- | -------- | -------- | -------- |
| 金流     | 3002607     | pwFHCqoQZGmho4w6     | EkRm7iFT261dpevs     |
| 物流(B2C)(Home)     | 2000132     | 5294y06JbISpM5x9     | v77hoKGq4kWxNNIS     |
| 物流(C2C)| 2000933     | XBERn1YOvpM9nfZc     | h1ONHk4P4yqbl5LK     |
| 電子發票(B2C)     | 2000132     | ejCk326UnaZWKisg     | q9jcZX8Ib9LM8wYk     |


