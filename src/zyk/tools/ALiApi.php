<?php


namespace zyk\tools;


class ALiApi {

    protected static $aliBankValiUrl = 'https://ccdcapi.alipay.com/validateAndCacheCardInfo.json';

    /**
     * 验证银行卡合法性（BIN码）
     * @author 小贤
     * 2019-08-12
     * @param $cardNo
     * @return bool
     */
    public static function checkCardNo($cardNo) {
        $data = [
            '_input_charset' => 'utf-8',
            'cardNo' => $cardNo,
            'cardBinCheck' => 'true'
        ];
        $res = https_get(self::$aliBankValiUrl , $data);
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
