<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/27
 * Time: 15:31
 */

/**
 * @param $data    要解密的字符串
 * @param $key     密钥
 * @return string
 */
if (!function_exists('decrypt')) {
    function decrypt($data, $key = 'encrypt')
    {
        $key = md5($key);
        $x = 0;
        $l = strlen($key);
        $data = substr(base64_decode($data), $l);
        $len = strlen($data);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));}
        }
        return $str;
    }
}
