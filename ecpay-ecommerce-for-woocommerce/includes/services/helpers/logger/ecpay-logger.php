<?php

namespace Helpers\Logger;

class Wooecpay_Logger
{
    /**
     * 寫 Log
     *
     * @param  string|array $content
     * @param  string       $code      A:Ecpay payment flow/B:Ecpay logistic flow/C:Ecpay invoice flow/D:Cod flow
     * @param  string       $order_id
     * @return void
     */
    public function log($content, $code = '', $order_id = '') {

        try {
            // 啟用後台偵錯功能才能寫 Log
            if ('yes' === get_option('wooecpay_enabled_debug_log', 'no')) {

                // 檢查 Log 目錄是否存在
                if (!is_dir(WOOECPAY_PLUGIN_LOG_DIR)) {
                    wp_mkdir_p(WOOECPAY_PLUGIN_LOG_DIR);
                }

                // 組合 Log 固定開頭
                $header = '[' . date_i18n('Y-m-d H:i:s') . '] [' . $code . '] [' . $order_id . ']: ';

                // 處理 content 參數格式
                if (gettype($content) === 'array') {
                    $content = print_r($content, true);
                }
                $content = $header . $content;

                // 新增 Log
                // 注意：'外掛檔案編輯器'有限制允許編輯的檔案類型
                $debug_log_file_path = WOOECPAY_PLUGIN_LOG_DIR . '/ecpay_debug_' . date_i18n('Ymd') . '.txt';
                file_put_contents($debug_log_file_path, ($content . PHP_EOL), FILE_APPEND);
            }
        } catch (Exception $e){
            error_log(print_r($e->getMessage(), true));
        }
    }

    /**
     * 清理 Log
     *
     * @return bool
     */
    public function clear_log(){
        if (WP_Filesystem()) {
            global $wp_filesystem;

            if ($wp_filesystem->is_dir(WOOECPAY_PLUGIN_LOG_DIR)) {
                // Delete the folder and its contents
                $wp_filesystem->rmdir(WOOECPAY_PLUGIN_LOG_DIR, true);

                return true;
            }

            return false;
        }
    }

    /**
     * Log 內容隱碼處理
     *
     * @param  string       $type
     * @param  string|array $data
     * @return string|array $data
     */
    public function replace_symbol($type, $data) {
        switch ($type) {
            case 'logistic':
                if (isset($data['SenderName'])) $data['SenderName'] = '*';
                if (isset($data['SenderCellPhone'])) $data['SenderCellPhone'] = '*';
                if (isset($data['ReceiverName'])) $data['ReceiverName'] = '*';
                if (isset($data['ReceiverCellPhone'])) $data['ReceiverCellPhone'] = '*';
                break;
            case 'invoice':
                if (isset($data['Data']['CustomerName'])) $data['Data']['CustomerName'] = '*';
                if (isset($data['Data']['CustomerAddr'])) $data['Data']['CustomerAddr'] = '*';
                if (isset($data['Data']['CustomerPhone'])) $data['Data']['CustomerPhone'] = '*';
                if (isset($data['Data']['CustomerEmail'])) $data['Data']['CustomerEmail'] = '*';
                break;
        }

        return $data;
    }
}