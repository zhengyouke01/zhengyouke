<?php
declare(strict_types = 1);

namespace zyk\tools;


class ALiApi implements BaseInterface {

    protected static $aliBankValiUrl = 'https://ccdcapi.alipay.com/validateAndCacheCardInfo.json';


    public function serviceInfo() {
        return ['service_name' => '验证银行卡号', 'service_class' => 'ALiApi', 'service_describe' => '验证银行卡号', 'author' => 'zyk', 'version' => '1.0'];
    }

    /**
     * 验证银行卡合法性（BIN码）
     * @author 小贤
     * 2019-08-12
     * @param $cardNo
     * @return bool
     */
    public static function checkCardNo(string $cardNo) {
        $data = [
            '_input_charset' => 'utf-8',
            'cardNo' => $cardNo,
            'cardBinCheck' => 'true'
        ];
        $res = zyk_https_get(self::$aliBankValiUrl , $data);
        $res = json_decode($res, true);
        if (empty($res)) {
            return false;
        }
        if (empty($res['validated']) || !$res['validated']) {
            return false;
        }
        return true;
    }
}
