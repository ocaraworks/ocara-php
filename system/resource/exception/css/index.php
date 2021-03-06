<?php
/**
 * 异常显示主页CSS样式
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
?>
<style type="text/css">
    body {
        margin: 0;
        padding: 0;
        font-family: Verdana;
        height: 100%;
        width: 100%;
        overflow: hidden;
    }

    div {
        margin: 0;
    }

    #header {
        background-color: #DEECF7;
        height: 65px;
    }

    #main {
        height: 100%;
        position: absolute;
        width: 100%;
        overflow: hidden;
        _position: relative;
    }

    #left-nav {
        float: left;
        background-color: #e5f1ff;
        height: 100%;
        width: 200px;
        border-right: 1px solid #CBDAEA;
        overflow: hidden;
    }

    #right {
        float: left;
        background-color: white;
        height: 510px;
        width: 870px;
    }

    #sep {
        background-color: #13539c;
        height: 5px;
        overflow: hidden;
    }

    #iframe-main {
        width: 855px;
        height: 500px;
    }

    #logo {
        color: #1261f2;
        font-size: 22px;
        top: 15px;
        left: 20px;
        position: relative;
        font-weight: bold;
    }

    ul.left-menu {
        list-style: none;
        margin: 0;
        padding: 10px;
    }

    ul.left-menu li {
        float: left;
        padding: 8px 2px;
        text-align: left;
        height: 20px;
        margin: 0;
        overflow: hidden;
    }

    ul.left-menu li a:hover {
        background-color: #97c8fd;
        color: #FFF;
    }

    a:link, a:visited {
        color: #333333;
        text-decoration: none;
        padding: 5px;
        display: block;
    }

    ul.left-menu li a {
        width: 150px;
    }
</style>
