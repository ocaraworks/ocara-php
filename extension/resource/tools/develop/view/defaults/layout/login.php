<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Ocara框架开发者中心</title>
    <?php ocImport($this->getViewPath('css/index.php')); ?>
    <style type="text/css">
        #main {
            width: 100%;
            font-size: 15px;
        }

        .submit {
            width: 390px;
            text-align: center;
        }

        .global {
            width: 500px;
            margin: 0 auto;
            border: 1px solid #ADC4DF;
            background: #DEECF7;
            margin-top: 50px;
            padding: 10px;
        }

        .global div {
            margin: 0 auto;
            padding: 15px;
        }

        .left-span {
            width: 180px;
            display: inline-block;
        }

        .global .content {
            height: 150px;
            margin: 10px auto;
            color: #F61E2F;
            font-size: 15px;
            text-align: center;
        }

        .global .content form {
            color: initial;
            text-align: left;
        }

        .content a {
            display: inline;
        }
    </style>
</head>
<body>
<div>
    <div id="header">
        <div id="logo">Ocara框架开发者中心</div>
        <div id="member">
            <?php if ($isLogin) { ?>
                <a href="<?php echo ocUrl(array(OC_MODULE_NAME, 'generate', 'logout')); ?>">登出</a>
                <span>您好 <font><?php echo $_SESSION['OC_DEV_USERNAME']; ?></font>，欢迎使用本系统！ </span>
            <?php } ?>
        </div>
    </div>
    <div id="sep"></div>
    <div id="main">
        <div class="global">
            <?php $this->showTpl(); ?>
        </div>
    </div>
    <div id="footer"></div>
</div>
</body>
</html>
