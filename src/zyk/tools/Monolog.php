<?php
namespace zyk\tools;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Monolog implements BaseInterface {

    private static $logPath = '../log/';
    private static $instance = [];

    public function __construct($option = []) {
        self::$logPath = $option['monolog_path'] ?? self::$logPath;
    }

    /**
     * @param array $option
     */
    public static function getInstance($option = []) {
        self::$logPath = $option['monolog_path'] ?? self::$logPath;
        $k = md5(self::$logPath);
        if( !isset(static::$instance[$k]) || !(static::$instance[$k] instanceof self)) {
            static::$instance[$k] = new self($option);
            static::$instance[$k]->k = $k;
        }
        return static::$instance[$k];
    }

    public function serviceInfo() {
        return ['service_name' => 'Mongolog记录类', 'service_class' => 'Monolog', 'service_describe' => 'Mongolog记录类', 'author' => 'wxw', 'version' => '1.0'];
    }

    /**
     * info级别的日志
     * @author 小贤
     * 2019-08-27
     * @param $name
     * @param $msg
     * @param $ip
     * @param string $params
     * @param string $module
     */
    public function info($name, $msg, $ip, $params = [], $module = '') {
        $this->setLog($name)->info(self::processMsg($msg, $ip, $params, $module));
    }

    /**
     * warning级别的日志
     * @author 小贤
     * 2019-08-28
     * @param $name
     * @param $msg
     * @param $ip
     * @param string $params
     * @param string $module
     */
    public function warning($name, $msg, $ip, $params = [], $module = '') {
        $this->setLog($name)->warning(self::processMsg($msg, $ip, $params, $module));
    }

    /**
     * error级别的日志
     * @author 小贤
     * 2019-08-28
     * @param $name
     * @param $msg
     * @param $ip
     * @param string $params
     * @param string $module
     */
    public function error($name, $msg, $ip, $params = [], $module = '') {
        $this->setLog($name)->error(self::processMsg($msg, $ip, $params, $module));
    }


    /**
     * 处理格式
     * @author 小贤
     * 2019-08-28
     * @param $msg
     * @param $ip
     * @param string $params
     * @param string $module
     * @return string
     */
    static public function processMsg($msg, $ip, $params = [], $module = '') {
        $module = empty($module)?"[]":"[$module]";
        $ip = empty($ip)?"[]":$ip;
        $paramStr = '';
        if (!empty($params)) {
            // 目前只拿取第一个参数
            foreach ($params as $key => $param) {
                $paramStr .= $key.':'.$param;
                break;
            }
        } else {
            $paramStr = '[]';
        }
        return "$ip"."\t".$module."\t".$paramStr."\t".$msg;
    }

    /**
     * 获取日志计入的方式
     * @author 小贤
     * 2019-08-20
     * @param $name
     * @return bool|Logger
     */
    public function setLog($name, $module = '') {
        $logger = new Logger($name);
        if (!is_dir(static::$logPath)) {
            if (mkdir(static::$logPath, 0777, true) === false) {
                return false;
            }
        }
        // 根据模块区分目录的
        if (empty($module)) {
            if (defined('BIND_MODULE')) {
                $module = BIND_MODULE;
            } else {
                // 默认使用default目录
                $module = 'default';
            }
        }
        $logFileName = static::$logPath.$module.DIRECTORY_SEPARATOR.$name.'.log';
        $handler = new StreamHandler($logFileName, Logger::INFO);
        // 自定义格式
        $output = "[%datetime%]\t%level_name%\t%message%\t%context%\t%extra%\n";
        $formatter = new LineFormatter($output, 'Y-m-d H:i:s', false, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        return $logger;
    }
}
