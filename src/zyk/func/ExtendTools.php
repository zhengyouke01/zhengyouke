<?php
/**
 * 第三方扩展需要集成的方法
 */

/**
 * CURL POST 方式
 * @param $url
 * @param $data
 * @return bool|string
 */
function zyk_https_post($url , $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $tmpInfo = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Errno：' .curl_errno($curl). curl_error($curl);
    }
    curl_close($curl);
    return $tmpInfo;
}


/**
 * CURL GET 方式
 * @param $url
 * @param array $data
 * @return bool|string
 */
function zyk_https_get($url, $data = []) {
    if (!empty($data)) {
        $url = $url . '?' . http_build_query($data);
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']:'' );
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_HTTPGET, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $tmpInfo = curl_exec($curl);
    if (curl_errno($curl)) {
        // 错误输出修改，防止在api接口的时候输出内容
        return false;
    }
    curl_close($curl);
    return $tmpInfo;
}

/**
 * 验证银行卡卡号格式
 *
 * @author wxw 2020/1/9
 *
 * @param $cardNo
 *
 * @return bool|string
 */
function zyk_check_card_no($cardNo) {
    $n = 0;
    $ns = strrev($cardNo); // 倒序
    for ($i=0; $i <strlen($cardNo) ; $i++) {
        if(!is_numeric($ns[$i])){
            return false;
        }
        if ($i % 2 ==0) {
            $n += $ns[$i]; // 偶数位，包含校验码
        }else{
            $t = $ns[$i] * 2;
            if ($t >=10) {
                $t = $t - 9;
            }
            $n += $t;
        }
    }
    if(( $n % 10 ) == 0){
        return true;
    }else{
        // 第二种验证算法（针对17年发布的iso规则改变）
        $n = 0;
        for ($i=0; $i <strlen($cardNo) ; $i++) {
            if(!is_numeric($ns[$i])){
                return false;
            }
            if ($i % 2 ==0) {
                $n += $ns[$i];
            }else{
                $t = $ns[$i] * 2;
                $n += $t;
            }
        }

        if(( $n % 10 ) == 0) {
            return true;
        } else {
            // 如果还是验证失败，通过第三方进行查询银行卡的正确性，保证正确卡号不遗漏和功能的完整性
            $res = \zyk\tools\ALiApi::checkCardNo($cardNo);
            if ($res) {
                return true;
            }
            return false;
        }
    }
}

/**
 * 获得用户的真实IP地址
 *
 * @access  public
 * @return  string
 */
function real_ip()
{
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}

/**
 * redis扩展
 * @param $config 配置文件扩展
 */
function zyk_redis($config = array()) {
    $config = array_merge($config, config('redis'));
    $db_id = 0;
    if (!empty($config['db_id'])) {
        $db_id = $config['db_id'];
    }
    try {
        return \zyk\tools\Redis::getInstance($config, $db_id);
    } catch (\ErrorException $e) {
        throw new \ErrorException('redis连接异常');
    }
}
