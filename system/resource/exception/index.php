<?php
/**
 * 异常显示主页
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Ocara框架提示</title>
    <?php ocImport(OC_SYS . 'resource/exception/css/index.php'); ?>
    <style type="text/css">
        #main {
            width: 100%;
        }

        .content {
            height: 100%;
            padding: 15px 25px;
            background: #F7FBFC;
            width: 100%;
            margin: 0;
        }

        .title {
            font-weight: bold;
            height: 35px;
            color: #225985;
        }

        .oc-error {
            font-weight: bold;
            color: #f33;
        }

        .oc-exception {
            font-weight: bold;
            color: #f63;
        }

        .oc-message {
            font-weight: normal;
            font-size: 15px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
<div id="header">
    <div id="logo">Ocara框架提示</div>
</div>
<div id="sep"></div>
<div id="main">
    <div class="content">
        <div class="<?= $error['class'] ?>"><?php echo $error['code'], $error['desc'], $error['message']; ?></div>
        <?php if (ocContainer()->config->get('SYSTEM_RUN_MODE') == 'develop') { ?>
            <div class="oc-message">
                <div class="location">In file <strong><?= $error['file']; ?></strong> at line <?= $error['line'] ?>
                </div>
                <?= $error['trace'] ?>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>