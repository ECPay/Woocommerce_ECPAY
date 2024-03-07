<?php

final class Wooecpay_Db_Process
{
    /**
     * 綠界模組資料庫版本，資料庫有異動時請更新
     *
     * @var string
     */
    public static $ecpay_db_version = '1.0';

    /**
     * 資料庫處理程序
     *
     * @return void
     */
    public static function ecpay_db_process() {
        $site_option_ecpay_db_version = get_site_option('ecpay_db_version');

        // 檢查綠界模組資料庫版本，若有差異則更新
        if ($site_option_ecpay_db_version == NULL || $site_option_ecpay_db_version != self::$ecpay_db_version) {
            self::create_db_table_ecpay_orders_payment_status();
        }
    }

    /**
     * 新增 Table - ecpay_orders_payment_status
     *
     * @return void
     */
    protected static function create_db_table_ecpay_orders_payment_status() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ecpay_orders_payment_status';
        $sql = "CREATE TABLE $table_name (
            id                      bigint        NOT NULL AUTO_INCREMENT,
            order_id                bigint        NOT NULL,
            payment_method          varchar(60)   NOT NULL,
            merchant_trade_no       varchar(60)   NOT NULL DEFAULT '',
            payment_status          int(10)       NOT NULL DEFAULT 0,
            is_completed_duplicate  int(1)        NOT NULL DEFAULT 0,
            updated_at              timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at              timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";

        self::modify_db_table($table_name, $sql);
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

        // 更新綠界模組資料庫版本紀錄
        update_option('ecpay_db_version', self::$ecpay_db_version);
    }
}