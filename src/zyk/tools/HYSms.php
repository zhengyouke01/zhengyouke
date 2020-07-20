<?php
namespace zyk\tools;
use zyk\tools\BaseInterface;

class HYSms implements BaseInterface {
    protected $account;         //账号
    protected $password;        //密码
    protected $target;        //提交地址
    protected static $instance;


    private function __construct($option) {
        $this->account = $option['account'] ?? '';       //用户账号
        $this->password = $option['password'] ?? '';     //密码
        $this->target = $option['target'] ?? '';       //提交地址
    }

    //私有化克隆
    private function __clone(){}

    /**
     * 初始化
     * @param array $options
     * @return static
     */
    public static function instance($options = []) {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 服务基本信息
     */
    public function serviceInfo(){
        return ['service_name' => '互忆短信服务', 'service_class' => 'HYSms', 'service_describe' => '系统短信服务', 'author' => 'wxw', 'version' => '1.0'];
    }

    /**
     * @发送短信
     * @param string $mobile 手机号码
     * @param string $content 短信内容
     * @return mixed
     */
    public function sendSMS($mobile, $content){
        $target = $this->target;
        $msg = $content;
        try {
            $content = rawurlencode($content);
            $post_data = 'account='.$this->account.'&password='.$this->password.'&mobile='.$mobile.'&content='.$content;
            //密码可以使用明文密码或使用32位MD5加密
            $gets =  $this->xml_to_array($this->Post($post_data, $target));
            if($gets['SubmitResult']['code']==2){
                /*发送成功，记录短信内容*/
                $this->log_sms($mobile,$msg);
                $res['success'] = true;
                $res['msg'] = "发送成功";
                return $res;
            }else{
                $res['success'] = false;
                $res['msg'] = $gets['SubmitResult']['msg'];
                return $res;
            }
        } catch (\ErrorException $e) {
            $res['success'] = false;
            $res['msg'] = $e->getMessage();
            return $res;
        }

    }

    public function Post($curlPost, $url) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
            $return_str = curl_exec($curl);
            curl_close($curl);
            return $return_str;
    }

    public function xml_to_array($xml) {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
            $subxml= $matches[2][$i];
            $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = $this->xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    /*获取随机数函数*/
    public function mc_random($length, $char_str = 'abcdefghijklmnopqrstuvwxyz0123456789'){
        $hash = '';
        $chars = $char_str;
        $max = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $hash .= substr($chars, (rand(0, 1000) % $max), 1);
        }
        return $hash;
    }

    public function rand_code(){
        $chars = '0123456789';
        $randCode='';
        for ( $i = 0; $i < 5; $i++ )
            $randCode .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        return $randCode;
    }

    /*记录短信内容*/
    public function log_sms($mobile,$content,$type = 0){
        $getip  =  real_ip();
        $data = array(
                'mobile'                  => $mobile,
                'getip'                   => $getip,
                'msg'                     => $content,
                'add_time'                => time(),
                'type'                    => $type
            );
        #trace(json_encode($data),'info');
        $logPath = '../logs/sms_'.date('Ymi').'.log';
        file_put_contents($logPath, json_encode($data));
    }
}


?>
