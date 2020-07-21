<?php
declare(strict_types = 1);
namespace zyk\func;

/**
 * 使用盐值加密
 * @param $str
 * @param $salt
 * @return string
 */
function zyk_encrypt(string $str, string $salt = ''){
    return md5(md5($salt).$str);
}

/**
 * 创建随机数 小写字母+数字
 * @param $length
 * @param string $char_str
 * @return string
 */
function zyk_create_random(int $length, string $char_str = 'abcdefghijklmnopqrstuvwxyz0123456789') {
    $hash = '';
    $chars = $char_str;
    $max = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $hash .= substr($chars, (rand(0, 1000) % $max), 1);
    }
    return $hash;
}



/**
 * @获取6位验证码
 */
function zyk_rand_code() {
    return rand(100000, 999999);
}

/**
 * 产生随机数
 * @return string|null
 */
function zyk_randomkeys() {
    $pattern='1234567890abcdefghigklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key = null;
    for($i=0; $i< 6; $i++)
    {
        $key .= $pattern{mt_rand(0,35)}; // 生成php随机数
    }
    return $key;
}

/**
 * 生成邀请码
 *
 * @author wxw 2020/1/8
 *
 * @param int $uid 用户id
 *
 * @return string
 */
function zyk_create_invite_code(int $uid) {
    $len = 8; // 34str位下，至少保证6位，不会出现混乱的情况(id超过15亿也会出现混乱，在六位的基础上）
    $str = 'HK1M2N3TGQPR4WCUA5ZJE6BIV7YF8LS9DX';
    $expStr = 'O';
    $code = '';
    $num = $uid;
    while ($num > 0) {
        $mod = $num % 34;
        $num = ($num - $mod) / 34;
        $code = $str[$mod].$code;
    }
    if (strlen($code) < $len) {
        $codeStr = strlen($code);
        $code = $expStr.$code;
        for ($i = 0 ; $i < ($len - $codeStr  - 1); $i++) {
            $code = $str[rand(0, 33)].$code;
        }
    }
    return $code;
}

/**
 * @中文字串截取无乱码
 * @param $string
 * @param $start
 * @param $length
 * @return string
 */
function zyk_get_substr(string $string, int $start, int $length) {
    if (mb_strlen($string,'utf-8') > $length) {
        $str = mb_substr($string, $start, $length,'utf-8');
        return $str.'...';
    } else {
        return $string;
    }
}

/**
 * 字符串截取，支持中文和其他编码
 *
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function zyk_msubstr(string $str, int $start, int $length, string $charset = "utf-8", bool $suffix = true) {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 隐藏手机号码
 * @param $mobile
 * @return mixed
 */
function zyk_mobile_hide(string $mobile) {
    return substr_replace($mobile,'****',3,4);
}

/**
 * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
 * @param string $name 姓名
 * @return string 格式化后的姓名
 */
function zyk_substr_cut(string $name) {
    $strlen = mb_strlen($name, 'utf-8');
    $firstStr = mb_substr($name, 0, 1, 'utf-8');
    $lastStr = mb_substr($name, -1, 1, 'utf-8');
    return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
}

/**
 * 数字转汉字
 * @param $number
 * @return bool|string
 */
function zyk_upper_number(int $number) {
    if (!is_numeric($number)) return false;
    $numfont = array('0' => '零', '1' => '一', '2' => '二', '3' => '三', '4' => '四', '5' => '五', '6' => '六', '7' => '七', '8' => '八', '9' => '九');
    $upper = '';
    for ($i = 1; $i <= strlen($number); $i++) {
        $key = $i - 1;
        $key = mb_substr((string)$number, $key, 1);
        $upper .= $numfont[$key];
    }
    return $upper;
}



