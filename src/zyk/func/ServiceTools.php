<?php
declare(strict_types = 1);
namespace zyk\func;

/**
 * 校验字符表情
 * @author LYJ 2020.06.16
 * @param string $str
 * @return bool
 */
function zyk_check_emoji(string $str) {
    $mat = [];
    preg_match_all('/./u', $str,$mat);
    foreach ($mat[0] as $v){
        if(strlen($v) > 3){
            return false;
        }
    }
    return true;
}

/**
 * 验证存数字
 * @param $mobile
 * @return bool
 */
function zyk_check_number(string $mobile) {
    if(preg_match('/^[0-9]*$/',$mobile))
        return true;
    return false;
}

/**
 * 检查固定电话
 * @param $mobile
 * @return bool
 */
function zyk_check_telephone(string $mobile) {
    if(preg_match('/^([0-9]{3,4}-)?[0-9]{7,8}$/',$mobile))
        return true;
    return false;
}

/**
 * 检查邮箱地址格式
 * @param $email
 * @return bool
 */
function zyk_check_email(string $email) {
    if(filter_var($email,FILTER_VALIDATE_EMAIL))
        return true;
    return false;
}

/**
 * 验证是否是手机号
 * @author 小贤 2019/12/12
 * @param $mobile 需要判断的手机号
 * @return bool
 */
function zyk_check_mobile(string $mobile) {
    if(preg_match('/1[23456789]\d{9}$/',$mobile))
        return true;
    return false;
}


