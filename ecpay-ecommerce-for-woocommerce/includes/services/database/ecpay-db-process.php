<?php

final class Wooecpay_Db_Process
{
    /**
     * 綠界模組資料庫版本，資料庫有異動時請更新
     *
     * @var string
     */
    public static $ecpay_db_version = '1.1';

    /**
     * 資料庫處理程序
     *
     * @return void
     */
    public static function ecpay_db_process() {
        $site_option_ecpay_db_version = get_site_option('ecpay_db_version');

        // 檢查綠界模組資料庫版本，若有差異則更新
        if ($site_option_ecpay_db_version == NULL || $site_option_ecpay_db_version != self::$ecpay_db_version) {
            self::create_or_update_db_table_ecpay_orders_payment_status();
        }
    }

    /**
     * 新增 Table - ecpay_orders_payment_status
     *
     * @return void
     */
    protected static function create_or_update_db_table_ecpay_orders_payment_status() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';
        $sql = "CREATE TABLE $table_name (
            id                      bigint        NOT NULL AUTO_INCREMENT,
            order_id                bigint        NOT NULL,
            payment_method          varchar(60)   NOT NULL,
            merchant_trade_no       varchar(60)   NOT NULL DEFAULT '',
            payment_status          int(10)       NOT NULL DEFAULT 0,
            is_completed_duplicate  int(1)        NOT NULL DEFAULT 0,
            MerchantID              varchar(10)   NULL,
            MerchantTradeNo         varchar(20)   NULL,
            StoreID                 varchar(20)   NULL,
            RtnCode                 int(10)       NULL,
            RtnMsg                  varchar(200)  NULL,
            TradeNo                 varchar(20)   NULL,
            TradeAmt                int(10)       NULL,
            PaymentDate             varchar(20)   NULL,
            PaymentType             varchar(20)   NULL,
            PaymentTypeChargeFee    int(10)       NULL,
            PlatformID              varchar(20)   NULL,
            TradeDate               varchar(20)   NULL,
            SimulatePaid            int(1)        NULL,
            CustomField1            varchar(50)   NULL,
            CustomField2            varchar(50)   NULL,
            CustomField3            varchar(50)   NULL,
            CustomField4            varchar(50)   NULL,
            CheckMacValue           varchar(200)  NULL,
            eci                     int(10)       NULL,
            card4no                 varchar(4)    NULL,
            card6no                 varchar(6)    NULL,
            process_date            varchar(20)   NULL,
            auth_code               varchar(6)    NULL,
            stage                   int(10)       NULL,
            stast                   int(10)       NULL,
            red_dan                 int(10)       NULL,
            red_de_amt              int(10)       NULL,
            red_ok_amt              int(10)       NULL,
            red_yet                 int(10)       NULL,
            gwsr                    int(10)       NULL,
            PeriodType              varchar(1)    NULL,
            Frequency               int(10)       NULL,
            ExecTimes               int(10)       NULL,
            amount                  int(10)       NULL,
            ProcessDate             varchar(20)   NULL,
            AuthCode                varchar(6)    NULL,
            FirstAuthAmount         int(10)       NULL,
            TotalSuccessTimes       int(10)       NULL,
            BankCode                varchar(3)    NULL,
            vAccount                varchar(16)   NULL,
            ATMAccNo                varchar(5)    NULL,
            ATMAccBank              varchar(3)    NULL,
            WebATMBankName          varchar(10)   NULL,
            WebATMAccNo             varchar(5)    NULL,
            WebATMAccBank           varchar(3)    NULL,
            PaymentNo               varchar(14)   NULL,
            ExpireDate              varchar(20)   NULL,
            Barcode1                varchar(20)   NULL,
            Barcode2                varchar(20)   NULL,
            Barcode3                varchar(20)   NULL,
            BNPLTradeNo             varchar(64)   NULL,
            BNPLInstallment         varchar(2)    NULL,
            TWQRTradeNo             varchar(64)   NULL,
            updated_at              timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at              timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";

        self::modify_db_table($table_name, $sql);
    }

    /**
     * 驗證資料表及欄位是否成功建立
     */
    protected static function verify_db_create_or_update($table_name) {
        global $wpdb;

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        // 檢查資料表是否存在
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if ($table_exists) {
            $column_names = ['id', 'order_id', 'payment_method', 'merchant_trade_no', 'payment_status', 'is_completed_duplicate', 'MerchantID', 'MerchantTradeNo', 'StoreID', 'RtnCode', 'RtnMsg', 'TradeNo', 'TradeAmt', 'PaymentDate', 'PaymentType', 'PaymentTypeChargeFee', 'PlatformID', 'TradeDate', 'SimulatePaid', 'CustomField1', 'CustomField2', 'CustomField3', 'CustomField4', 'CheckMacValue', 'eci', 'card4no', 'card6no', 'process_date', 'auth_code', 'stage', 'stast', 'red_dan', 'red_de_amt', 'red_ok_amt', 'red_yet', 'gwsr', 'PeriodType', 'Frequency', 'ExecTimes', 'amount', 'ProcessDate', 'AuthCode', 'FirstAuthAmount', 'TotalSuccessTimes', 'BankCode', 'vAccount', 'ATMAccNo', 'ATMAccBank', 'WebATMBankName', 'WebATMAccNo', 'WebATMAccBank', 'PaymentNo', 'ExpireDate', 'Barcode1', 'Barcode2', 'Barcode3', 'BNPLTradeNo', 'BNPLInstallment', 'TWQRTradeNo', 'updated_at', 'created_at'
            ];

            foreach ($column_names as $column_name) {
                // 資料表存在，檢查欄位是否存在
                $column_exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", $column_name));
                if (!$column_exists) return false;
            }
            
        } else return false;

        return true;
    }

    /**
     * 根據指定 SQL 異動資料庫
     *
     * @param  string $table_name
     * @param  string $sql
     * @return void
     */
    protected static function modify_db_table($table_name, $sql) {
        global $wpdb;

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        $isTableExists   = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $charset_collate = $isTableExists ? '' : $wpdb->get_charset_collate();

        // 異動資料庫
        $dbDelta_result = dbDelta($sql . $charset_collate . ";");

        // 驗證資料庫新增、更新狀態
        $db_create_or_update_result = self::verify_db_create_or_update($table_name);
        
        // 更新綠界模組資料庫版本紀錄
        if ($db_create_or_update_result) {
            update_option('ecpay_db_version', self::$ecpay_db_version);
        }
    }
}