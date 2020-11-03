<?php


if (!function_exists('unauthenticated')) {
    /**
     * 为通过验证统一返回
     * @param string $message
     * @return array
     */
    function unauthenticated($message = 'Unauthorized')
    {
        return [
            'code' => 401,
            'message' => $message,
            'data' => []
        ];
    }
}

if (!function_exists('unused')) {
    /**
     * 秘钥失效统一返回
     * @return array
     */
    function unused()
    {
        return [
            'code' => 402,
            'message' => '秘钥失效',
            'data' => []
        ];
    }
}

if (!function_exists('unsigned')) {
    /**
     * 验签失败统一返回
     * @return array
     */
    function unsigned()
    {
        return [
            'code' => 403,
            'message' => '验签失败',
            'data' => []
        ];
    }
}

if (!function_exists('overtime')) {
    /**
     * 签名超时统一返回
     * @return array
     */
    function overtime()
    {
        return [
            'code' => 405,
            'message' => '签名超时',
            'data' => []
        ];
    }
}

if (!function_exists('validation_failure')) {
    /**
     * 数据验证失败统一返回
     * @param string $message
     * @return array
     */
    function validation_failure($message = 'validation failure')
    {
        return [
            'code' => 301,
            'message' => $message,
            'data' => []
        ];
    }
}

if (!function_exists('no_manager')) {
    /**
     * 管理员ID有问题时统一返回
     * @param string $message
     * @return array
     */
    function no_manager($message = '没有管理员ID')
    {
        return [
            'code' => 406,
            'message' => $message,
            'data' => []
        ];
    }
}
