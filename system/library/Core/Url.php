<?php
/**
 * URL处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class Url extends Base
{
    const ROUTE_TYPE_DEFAULT = 1; //默认类型
    const ROUTE_TYPE_DIR = 2; //伪目录类型
    const ROUTE_TYPE_PATH = 3; //伪路径类型
    const ROUTE_TYPE_STATIC = 4; //伪静态类型

    const EVENT_PARSE_URL_PARAMS = 'parseUrlParams';
    const EVENT_FORMAT_URL_PARAMS = 'formatUrlParams';

    /**
     * 是否虚拟URL地址
     * @param string $urlType
     * @return bool
     */
    public function isVirtualUrl($urlType)
    {
        $urlTypes = array(
            self::ROUTE_TYPE_DIR,
            self::ROUTE_TYPE_PATH,
            self::ROUTE_TYPE_STATIC
        );

        $urlTypes = array_merge($urlTypes, ocConfig('EXTEND_VIRTUAL_ROOT_TYPE', array()));
        return in_array($urlType, $urlTypes);
    }

    /**
     * URL请求参数解析
     * @param string $url
     * @return array|string
     * @throws Exception
     */
    public function parseGet($url = null)
    {
        if (empty($url)) {
            if (PHP_SAPI == 'cli') {
                $url = trim(isset($_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : OC_EMPTY, OC_DIR_SEP);
            } else {
                $webRoot = ocCommPath(OC_WEB_ROOT);
                $documentRoot = ocCommPath($_SERVER['DOCUMENT_ROOT']);
                $localUrl = $documentRoot . OC_REQ_URI;

                if ($localUrl == ocCommPath($_SERVER['SCRIPT_FILENAME'])) {
                    return array();
                }

                $urlDir = ocCommPath(str_ireplace($documentRoot, '', $webRoot));
                $urlDir = $urlDir == '/' ? OC_EMPTY : $urlDir;
                $requestUri = str_ireplace($urlDir, '', OC_REQ_URI);
                $url = trim($requestUri, OC_DIR_SEP);
            }
        }

        if (empty($url)) return array();

        $result = $this->parseUrlParams($url, OC_URL_ROUTE_TYPE);

        if ($result['is_valid'] !== true) {
            ocService()->error->show('fault_url');
        }

        $get = $result['params'];

        if (PHP_SAPI == 'cli') {
            $route = explode(OC_DIR_SEP, trim($url, OC_DIR_SEP));
            $get = array_merge($route, $get);
        } elseif ($this->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            if (!$get) {
                $get[0] = null;
            }
            if ($result['extends']) {
                $get[] = $result['extends'];
            }
        } else {
            $routeParamName = ocConfig('ROUTE_PARAM_NAME', '_route');
            if (isset($get[$routeParamName])) {
                $route = explode(OC_DIR_SEP, ocDel($get, $routeParamName));
                $route[1] = isset($route[1]) ? $route[1] : null;
                $route[2] = isset($route[2]) ? $route[2] : null;
                $get = array_merge($route, $get);
            }
        }

        return $get;
    }

    /**
     * 检测URL
     * @param string $url
     * @param string $urlType
     * @return null
     */
    public function parseUrlParams($url, $urlType)
    {
        $result = array(
            'is_valid' => true,
            'params' => array(),
            'extends' => array(),
        );

        if (PHP_SAPI == 'cli') {
            $paramsString = isset($_SERVER['argv']['2']) ? $_SERVER['argv']['2'] : OC_EMPTY;
            if ($paramsString) {
                $result['params'] = explode(OC_DIR_SEP, trim($paramsString, OC_DIR_SEP));
            }
            return $result;
        }

        $paramsString = str_replace(OC_NS_SEP, OC_DIR_SEP, $url);

        if (ocService()->resources->contain('url.parse_query_params')) {
            $customResult = ocService()
                ->resources
                ->get('url.parse_query_params')
                ->handler($urlType, $paramsString);
            if (!empty($customResult[$urlType])) {
                return $customResult[$urlType];
            }
        }

        if ($this->isVirtualUrl($urlType)) {
            $str = $urlType == self::ROUTE_TYPE_PATH ? 'index\.php[\/]?' : false;
            $el = '[^\/\&\?]';
            $mvc = '\w*';
            $mvcs = $mvc . '\/';

            if ($urlType == self::ROUTE_TYPE_STATIC && $paramsString != OC_DIR_SEP) {
                $file = "\.html?";
            } else {
                $file = OC_EMPTY;
            }

            $tail = "(\/\w+\.\w+)?";
            $tail = $file . "({$tail}\?(\w+={$el}*(&\w+={$el}*)*)?(#.*)?)?";
            $exp = "/^(\w+:\/\/\w+(\.\w)*)?{$str}(({$mvc})|({$mvcs}{$mvc})|({$mvcs}{$mvcs}{$mvc}(\/({$el}*\/?)+)*))?{$tail}$/i";
            if (preg_match($exp, $paramsString, $matches)) {
                if ($matches[3]) {
                    $result['params'] = explode(OC_DIR_SEP, trim($matches[3], OC_DIR_SEP));
                }
                if (isset($matches[11])) {
                    parse_str($matches[11], $result['extends']);
                }
            } else {
                $result['is_valid'] = false;
            }
        } else {
            $get = parse_url($paramsString, PHP_URL_QUERY);
            parse_str($get, $result['params']);
        }

        return $result;
    }

    /**
     * 新建URL
     * @param $route
     * @param array $params
     * @param bool $relative
     * @param null $urlType
     * @param bool $static
     * @return bool|string
     * @throws Exception
     */
    public function create($route, $params = array(), $relative = false, $urlType = null, $static = true)
    {
        $route = ocService()->app->formatRoute($route);
        if (empty($route)) return false;

        extract($route);
        $urlType = $urlType ?: OC_URL_ROUTE_TYPE;

        if (is_numeric($params) || is_string($params)) {
            $array = array_chunk(explode(OC_DIR_SEP, $params), 2);
            $params = array();
            foreach ($array as $value) {
                $params[reset($value)] = isset($value[1]) ? $value[1] : null;
            }
        } elseif (!is_array($params)) {
            $params = array();
        }

        if ($static && ocService()->staticPath->isOpen()) {
            list($file, $args) = ocService()->staticPath->getStaticFile($module, $controller, $action, $params);
            if ($file && is_file(ocPath('static', $file))) {
                return $relative ? OC_DIR_SEP . $file : OC_ROOT_URL . $file;
            }
        }

        if (ocService()->resources->contain('url.create_url')) {
            $customResult = ocService()
                ->resources
                ->get('url.create_url')
                ->handler($urlType, $route, $params);
            if ($customResult) {
                return $customResult;
            }
        }

        if ($this->isVirtualUrl($urlType)) {
            if ($module) {
                $query = array($module, $controller, $action);
            } else {
                $query = array($controller, $action);
            }

            $route = implode(OC_DIR_SEP, $query);
            $query = $params ? OC_DIR_SEP . implode(OC_DIR_SEP, $this->divideQuery($params)) : false;
            $paramPath = $urlType == self::ROUTE_TYPE_PATH ? OC_INDEX_FILE . OC_DIR_SEP : false;
            $paramPath = $paramPath . $route . $query;
            $paramPath = $urlType == self::ROUTE_TYPE_STATIC ? $paramPath . '.html' : $paramPath;
        } else {
            $route = array();
            if ($module) {
                $route[] = $module;
            }

            $route[] = $controller;
            $route[] = $action;

            $route = implode(OC_DIR_SEP, $route);
            $routeParam = ocConfig('ROUTE_PARAM_NAME');

            $queryString = $this->buildQuery(array_merge(array($routeParam => $route), $params));
            $paramPath = OC_INDEX_FILE . '?' . $queryString;
        }

        return $relative ? OC_DIR_SEP . $paramPath : OC_ROOT_URL . $paramPath;
    }

    /**
     * 格式化参数数组
     * @param array $params
     * @return array
     */
    public function divideQuery(array $params)
    {
        $result = array();

        if ($params) {
            if (0) return array_values($params);
            foreach ($params as $key => $value) {
                $result[] = $key;
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 添加查询字符串参数
     * @param array $params
     * @param string $url
     * @param string $urlType
     * @return string
     * @throws Exception
     */
    public function appendQuery(array $params, $url = null, $urlType = null)
    {
        $urlType = $urlType ?: OC_URL_ROUTE_TYPE;
        $urlInfo = $this->parseUrlInfo($url);

        if ($url) {
            $uri = $urlInfo['path'] . ($urlInfo['query'] ? '?' . $urlInfo['query'] : false);
        } else {
            $uri = OC_REQ_URI;
        }

        $result = $this->parseUrlParams($uri, $urlType);
        if ($result === null) {
            ocService()->error->show('fault_url');
        }

        if (ocService()->resources->contain('url.append_query_params')) {
            $customResult = ocService()
                ->resources
                ->get('url.append_query_params')
                ->handler($urlType, $result, $urlInfo, $params);
            if ($customResult) {
                return $customResult;
            }
        }

        if ($this->isVirtualUrl($urlType)) {
            $params = array_merge($result['params'], $this->divideQuery($params));
            $urlInfo['path'] = implode(OC_DIR_SEP, $params);
        } else {
            parse_str($urlInfo['query'], $query);
            $urlInfo['query'] = $this->buildQuery(array_merge($query, $params));
        }

        return $this->buildUrl($urlInfo);
    }

    /**
     * 获取URL详情
     * @param string $url
     * @return array
     */
    public function parseUrlInfo($url = null)
    {
        $fields = array(
            'scheme', 'host', 'port',
            'username', 'password',
            'path', 'query',
        );

        if ($url) {
            $data = array_merge(array_fill_keys($fields, null), parse_url($url));
        } else {
            $request = ocService()->request;
            $values = array(
                OC_PROTOCOL,
                $request->getServer('HTTP_HOST'),
                $request->getServer('SERVER_PORT'),
                $request->getServer('PHP_AUTH_USER'),
                $request->getServer('PHP_AUTH_PW'),
                $request->getServer('REDIRECT_URL'),
                $request->getServer('QUERY_STRING'),
            );
            $data = array_combine($fields, $values);
        }

        return $data;
    }

    /**
     * 生成查询字符串
     * @param array $params
     * @param string $numeric_prefix
     * @param string $arg_separator
     * @param int $enc_type
     * @return string
     */
    public function buildQuery(array $params, $numericPrefix = null, $argSeparator = null)
    {
        $argSeparator = $argSeparator ?: '&';
        return urldecode(http_build_query($params, $numericPrefix, $argSeparator));
    }

    /**
     * 生成URL
     * @param array $data
     * @return string
     */
    public function buildUrl(array $data)
    {
        $url = $data['scheme'] . '://';
        if ($data['username']) {
            $url = $url . "{$data['username']}:{$data['password']}@";
        }

        $url = $url . $data['host'];
        if ($data['port']) {
            $url = $url . ($data['port'] == '80' ? false : ':' . $data['port']);
        }

        $url = ocDir($url) . $data['path'];
        if ($data['query']) {
            $url = $url . '?' . $data['query'];
        }

        return $url;
    }
}