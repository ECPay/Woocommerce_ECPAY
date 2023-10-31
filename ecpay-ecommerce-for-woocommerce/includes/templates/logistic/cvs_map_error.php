<!DOCTYPE html>
<html itemscope itemtype="http://schema.org/WebPage" lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Language" content="zh-TW">
    <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="format-detection" content="telephone=no">
    <meta name="renderer" content="webkit|ie-comp|ie-stand">

    <!-- SEO 設定 -->
    <link rel="shortcut icon" href="<?php echo WOOECPAY_PLUGIN_URL . 'public/images/cvs_map_error/favicon.ico' ?>">
    <title>交易失敗，請重新購買</title>
    <!-- SEO 設定 END-->

    <!-- 字體 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@100;300;400;500;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="<?php echo WOOECPAY_PLUGIN_URL . 'public/css/cvs_map_error/cvs_map_error.css' ?>" rel="stylesheet">

</head>

<body>
    <!-- header start -->
    <div class="site-header sh-2">
        <div class="sh-container">
            <div class="sh-logo-box">
                <a href="https://www.ecpay.com.tw" class="slb-logo" title="綠界科技">
                    <img src="<?php echo WOOECPAY_PLUGIN_URL . 'public/images/cvs_map_error/ecpay_logo.svg' ?>" alt="綠界科技" />
                </a>
            </div>
        </div>
    </div>
    <!-- header end -->

    <!-- site-body start -->
    <div class="site-body">
        <div class="site-content-wrapper scw-status">

            <div class="site-content">

                <div class="ctp-status-wrap">
                    <div class="csw-box">
                        <div class="csb-ic cai-fail">
                            <div id="svgContainer" class="cic-ani"></div>
                        </div>
                        <div class="csb-title">
                            交易取消 !！
                        </div>
                        <div class="csb-info-box">
                            <ul class="cib-list">
                                <li>錯誤描述：error</li>
                            </ul>
                            <div class="cib-txt">
                                選擇物流運費與超商門市地點不符，<br>
                                因此取消訂單，請回到商店後重新購買。
                            </div>
                        </div>
                        <div class="csb-btn-box">
                            <a href="<?php echo $back_url ?>" class="btn btn-main">重新購買</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- site-body 內容  end -->



    <!-- JS -->
    <script src='<?php echo WOOECPAY_PLUGIN_URL . "public/js/cvs_map_error/libs/jquery.min.js" ?>'></script>
    <script src='<?php echo WOOECPAY_PLUGIN_URL . "public/js/cvs_map_error/app/actions.js" ?>'></script>

    <!--lottie 動態圖檔-->
    <script src='<?php echo WOOECPAY_PLUGIN_URL . "public/js/cvs_map_error/libs/lottie.min.js" ?>'></script>
    <script>
        var svgContainer = document.getElementById('svgContainer');
        var animItem = bodymovin.loadAnimation({
            wrapper: svgContainer,
            animType: 'svg',
            loop: false,
            path: '<?php echo WOOECPAY_PLUGIN_URL . 'public/images/cvs_map_error/pay_fail.json' ?>'
        });
    </script>
    <!---->

    <!-- JS end -->

</body>

</html>