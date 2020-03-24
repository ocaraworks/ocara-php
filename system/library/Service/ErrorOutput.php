<?php
/**
 * 错误输出插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class ErrorOutput extends ServiceBase
{
    /**
     * 打印错误
     * @param $error
     * @return mixed
     */
    public function display($error)
    {
        if ($error['type'] == 'program_error') {
            $displayError = @ini_get('display_errors');
            if (empty($displayError)) {
                return ocService()->response->send();
            }
        }

        if (function_exists('ocLang')) {
            $error['desc'] = ocLang($error['type']);
            $error['message'] = ocLang($error['message']);
        } else {
            $error['desc'] = ucfirst($error['type']) . ': ';
        }

        $error['code'] = $error['code'] ? "[{$error['code']}]" : null;
        $error['class'] = $error['type'] == 'program_error' ? 'oc-error' : 'oc-exception';
        $error['file'] = trim(ocCommPath(self::stripRootPath($error['file'])), OC_DIR_SEP);
        $error['trace'] = nl2br(ocCommPath($error['trace']));

        if (PHP_SAPI == 'cli') {
            list ($trace, $traceInfo) = ocDel($error, 'trace', 'traceInfo');
            $error = array_merge(array('time' => date('Y-m-d H:i:s')), $error);
            $content = ocBr2nl(ocJsonEncode($error) . PHP_EOL . $trace);
        } else {
            $filePath = OC_SYS . 'resource/exception/index.php';
            if (ocFileExists($filePath)) {
                ob_start();
                include($filePath);
                $content = ob_get_contents();
                ob_end_clean();
            } else {
                $content = self::getSimpleTrace($error);
            }
        }

        $response = ocService('response', true);
        $response->setBody($content, true);
    }

    /**
     * 获取简洁的Trace内容
     * @param $error
     * @return string
     */
    public static function getSimpleTrace($error)
    {
        return 'Lost exception template file.';
    }

    /**
     * 去除当前出错文件路径的根目录
     * @param $errorFile
     * @return mixed
     */
    protected static function stripRootPath($errorFile)
    {
        $filePath = ocCommPath(realpath($errorFile));
        $rootPath = ocCommPath(realpath(OC_APP_ROOT));
        $ocPath = ocCommPath(realpath(OC_PATH)) . OC_DIR_SEP;

        if (strpos($filePath, $ocPath) === 0) {
            $filePath = str_ireplace($ocPath, OC_EMPTY, $filePath);
        } elseif (strpos($filePath, $rootPath) === 0) {
            $filePath = str_ireplace(OC_APP_ROOT, OC_EMPTY, $filePath);
        }

        return $filePath;
    }
}
