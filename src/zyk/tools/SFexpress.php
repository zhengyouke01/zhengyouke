<?php
namespace zyk\tools;

class SFexpress {

    private static $_instance = NULL;
    private static $checkword;//密钥
    private static $post_url = 'http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';
    private $business_number;
    private $cache_set = 1; //  是否开启5分钟的查询缓存
    private $expire = 300;


    public static $finish_code = '80';

    private function __construct($options = []) {
        self::$checkword = $options['check_word'] ?? '';
        $this->business_number = $options['business_number'] ?? '';
    }

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 查询请求
     * @param $url
     * @param $post_data
     * @return bool|string
     */
    public function sendPost($url, $post_data) {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded;charset=utf-8',
                'content' => $postdata,
                'timeout' => 10 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * 顺丰路由信息查询
     * @param $sf_no
     * @return bool|mixed
     */
    public function SFRoute($sf_no) {
        return $this->queryPostRoute($sf_no);
    }

    /**
     * 批量查询顺丰路由内容
     * @param $sf_nos
     * @return array|bool
     */
    public function SFRouteBatch($sf_nos) {
        $order_nno_route = [];
        $query_list = [];
        foreach ($sf_nos as $no) {
            $query_list[] = $no;
        }

        // 批量查询单号
        return $this->BatchQueryPostRoute($query_list);
    }

    /**
     * 批量查询快递信息
     * @param $order_nos
     * @return array|bool
     */
    public function BatchQueryPostRoute($order_nos) {
        $order_no_str = implode(',',$order_nos);
        $xmlContent = "<Request service='RouteService' lang='zh-CN'><Head>".$this->business_number."</Head><Body><RouteRequest tracking_type='1' method_type='1' tracking_number='{$order_no_str}'/></Body></Request>";
        $post_data = array(
            'xml' => $xmlContent,
            'verifyCode' => $this->verifyCode($xmlContent)
        );
        $resultCont = $this->sendPost(self::$post_url, $post_data);
        return $this->SFres_batch($resultCont);
    }

    /**
     * 根据快递单号，获取路由记录
     * @param $sf_no
     * @return bool
     *
     */
    public function queryPostRoute($sf_no) {
        $xmlContent = "<Request service='RouteService' lang='zh-CN'><Head>".$this->business_number."</Head><Body><RouteRequest tracking_type='1' method_type='1' tracking_number='{$sf_no}'/></Body></Request>";
        $post_data = array(
            'xml' => $xmlContent,
            'verifyCode' => $this->verifyCode($xmlContent)
        );
        $resultCont = $this->sendPost(self::$post_url, $post_data);
        return $this->SFres($resultCont);
    }

    /**
     * 批量返回结果处理
     * @param $resultCont
     * @return array|bool
     */
    public function SFres_batch($resultCont) {
        $res_arr = $this->processXmlRes($resultCont);
        if ($res_arr && !empty($res_arr['Response']) && !empty($res_arr['Response']['Head'])) {
            if ($res_arr['Response']['Head'] == 'OK') {
                if (!empty($res_arr['Response']['Body']['RouteResponse'])) {
                    $routes = [];
                    if (isset($res_arr['Response']['Body']['RouteResponse']['Route'])) {
                        $routes[$res_arr['Response']['Body']['RouteResponse']['@mailno']] = $res_arr['Response']['Body']['RouteResponse']['Route'];
                        //返回结果集中只有一个结果
                    } else {
                        foreach ($res_arr['Response']['Body']['RouteResponse'] as $route) {
                            if (isset($route['Route'])) {
                                $routes[$route['@mailno']] = $route['Route'];
                            }
                        }
                    }
                    return ['status' => 1, 'routes' => $routes];
                }
            }
            elseif ($res_arr['Response']['Head'] == 'ERR') {
                return false;
            }
        }
        return false;
    }

    /**
     * 组装路由返回结果
     * @param $resultCont
     * @return array|bool
     * Route数组格式
     *  ["@remark"]=>
        string(28) "顺丰速运 已收取快件"
        ["@accept_time"]=>
        string(19) "2018-09-06 18:28:02"
        ["@accept_address"]=>
        string(9) "杭州市"
        ["@opcode"]=> // 此编码产看丰桥官网https://qiao.sf-express.com/pages/developDoc/index.html?level2=296618&level3=627651&level4=949000
        string(2) "54"
     *
     */
    public function SFres($resultCont) {
        $res_arr = $this->processXmlRes($resultCont);
        if ($res_arr && !empty($res_arr['Response']) && !empty($res_arr['Response']['Head'])) {
            //  正常数据返回
            if ($res_arr['Response']['Head'] == 'OK') {
                //  请求结果正确
                if (!empty($res_arr['Response']['Body'])) {
                    (isset($res_arr['Response']['Body']['RouteResponse']['Route']))?$info=$res_arr['Response']['Body']['RouteResponse']['Route']:$info=[];
                    return ['status' => 1, 'route' => $info];
                } else {
                    //暂无物流信息
                    return ['status' => 0 ,'route' => []];
                }
            } elseif ($res_arr['Response']['Head'] == 'ERR') {
                return false;
            }
        }
        return false;
    }

    /**
     * 将xml转化成arr
     * @param $resultCont
     * @param bool $recursive
     * @return array|mixed
     */
    protected function processXmlRes($resultCont, $recursive = false) {
        $doc = new \DOMDocument("1.0", 'utf-8');
        $doc->loadXML ($resultCont);
        $result = $this->domNodeToArray($doc);
        if(isset($result['#document'])){
            $result = $result['#document'];
        }
        return $result;
    }

    /**
     * 循环处理xml
     * @param \DOMNode|null $oDomNode
     * @return array
     */
    function domNodeToArray(\DOMNode $oDomNode = null) {
        // return empty array if dom is blank
        if (! $oDomNode->hasChildNodes ()) {
            $mResult = $oDomNode->nodeValue;
        } else {
            $mResult = array ();
            foreach ( $oDomNode->childNodes as $oChildNode ) {
                // how many of these child nodes do we have?
                // this will give us a clue as to what the result structure should be
                $oChildNodeList = $oDomNode->getElementsByTagName ( $oChildNode->nodeName );
                $iChildCount = 0;
                // there are x number of childs in this node that have the same tag name
                // however, we are only interested in the # of siblings with the same tag name
                foreach ( $oChildNodeList as $oNode ) {
                    if ($oNode->parentNode->isSameNode ( $oChildNode->parentNode )) {
                        $iChildCount ++;
                    }
                }
                $mValue = $this->domNodeToArray ( $oChildNode );
                $sKey = ($oChildNode->nodeName {0} == '#') ? 0 : $oChildNode->nodeName;
                $mValue = is_array ( $mValue ) ? $mValue [$oChildNode->nodeName] : $mValue;
                // how many of thse child nodes do we have?
                if ($iChildCount > 1) { // more than 1 child - make numeric array
                    $mResult [$sKey] [] = $mValue;
                } else {
                    $mResult [$sKey] = $mValue;
                }
            }
            // if the child is <foo>bar</foo>, the result will be array(bar)
            // make the result just 'bar'
            if (count ( $mResult ) == 1 && isset ( $mResult [0] ) && ! is_array ( $mResult [0] )) {
                $mResult = $mResult [0];
            }
        }
        // get our attributes if we have any
        $arAttributes = array ();
        if ($oDomNode->hasAttributes ()) {
            foreach ( $oDomNode->attributes as $sAttrName => $oAttrNode ) {
                // retain namespace prefixes
                $arAttributes ["@{$oAttrNode->nodeName}"] = $oAttrNode->nodeValue;
            }
        }
        // check for namespace attribute - Namespaces will not show up in the attributes list
        if ($oDomNode instanceof \DOMElement && $oDomNode->getAttribute ( 'xmlns' )) {
            $arAttributes ["@xmlns"] = $oDomNode->getAttribute ( 'xmlns' );
        }
        if (count ( $arAttributes )) {
            if (! is_array ( $mResult )) {
                $mResult = (trim ( $mResult )) ? array ($mResult ) : array ();
            }
            $mResult = array_merge ( $mResult, $arAttributes );
        }
        $arResult = array ($oDomNode->nodeName => $mResult );
        return $arResult;
    }
    /**
     * 生产验证码
     * @param $xmlContent
     * @return string
     */
    protected function verifyCode($xmlContent) {
        return  base64_encode(md5(($xmlContent . self::$checkword), TRUE));
    }


}
