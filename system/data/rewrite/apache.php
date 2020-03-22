<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 Apache默认配置 《请勿修改》
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden!');

/*
 * htaccess文件默认内容
 */
$htaccess = "<IfModule mod_rewrite.c>\r\nRewriteEngine On\r\n%s";
$htaccess .= "RewriteBase /\r\n";
$htaccess .= "RewriteRule ^(src/?.*)|(pass/.+)|(favicon.ico)|(robots\\.txt)|(sitemap\\d*\.[[:alpha:]]{3})$ - [NE,NC,L]\r\n";
$htaccess .= "RewriteRule ^.+\.html?(\?.*)?$ static/$0 [NE,NC]\r\n";
$htaccess .= "RewriteCond %%{REQUEST_FILENAME} !-f\r\n";
$htaccess .= "RewriteRule ^.*$ " . OC_INDEX_FILE . " [NE,NC,L]\r\n</IfModule>\r\n";

return $htaccess;