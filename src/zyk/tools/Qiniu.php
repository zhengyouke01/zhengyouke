<?php
declare(strict_types = 1);
/**
 * @author lwh 2019-12-03
 * @七牛云第三方类库公共类
 */
namespace zyk\tools;

use Qiniu\Auth;
use Qiniu\Http\Client;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Qiniu implements BaseInterface {



    private $auth = null;
    private $accessKey;//key
    private $secretKey;//密钥
    private $file_upload_domain;//访问域名
    private $bucket;//空间
    private $upload_url = 'http://up-z0.qiniu.com';//上传地址
    private $callbackHost;//回调地址
    private $ObjHost = 'http://rs.qiniu.com/';//
    private $type = 2;//1-公用空间 2-私有空间
    private $exit_file_key;
    private $download_path;


    public function __construct(array $option = []) {
        $this->accessKey = $option['accessKey'] ?? '';
        $this->secretKey = $option['secretKey'] ?? '';
        $this->file_upload_domain = $option['file_upload_domain'] ?? '';
        $this->bucket = $option['bucket'] ?? '';
        $this->upload_url = $option['upload_url'] ?? '';
        $this->callbackHost = $option['callbackHost'] ?? '';
        $this->ObjHost = $option['ObjHost'] ?? $this->ObjHost;
        $this->type = $option['type'] ?? $this->type;
        $this->exit_file_key = $option['exit_file_key'] ?? '';
        $this->download_path = $option['exit_file_key'] ?? '';
        $this->auth = new Auth($this->accessKey, $this->secretKey);
    }

    /**
     * 服务基本信息
     * @author lwh 2019-12-03
     * @return array
     */
    public function serviceInfo() {
        return ['service_name' => '七牛云图片处理', 'service_class' => 'Qiniu', 'service_describe' => '系统图片处理', 'author' => 'LiWenHui', 'version' => '1.0'];
    }

    /**
     * 上传凭证-获取上传token
     * @auhtor lwh 2019-12-19
     * @param $policy 参数1
     * @param $type 类型,默认0,0-表示公有, 1-表示私有
     * @return string
     */
    public function uploadToken(array $policy) {
        if($this->type == 2) {
            $policy['callbackUrl'] = $this->callbackHost.rtrim($policy['callbackUrl']);
            return $this->auth->uploadToken($this->bucket, null, 108000, $policy);
        }else {

            $policy['callbackUrl'] = $this->callbackHost.rtrim($policy['callbackUrl']);
            return $this->auth->uploadToken($this->bucket, null, 108000, $policy);
        }


    }

    /**
     * 获取七牛上传token
     */
    public function qiniuToken(array $policy) {
        return $this->auth->uploadToken($this->bucket, null, 108000, $policy);
    }

    /**
     * 获取验证类
     * @author lwh 2019-12-03
     * @return Auth
     */
    protected function downloadAuth() {
        return $this->auth;
    }

    /**
     * 文件下载
     * @author lwh 2019-12-03
     * @param $type 类型,默认0,0-表示公有, 1-表示私有
     * @param $fkey 图片文件key
     * @param string $ext 加后缀，一般为下载连接
     * @return string 返回
     */
    public function downloadUrl(int $type, string $fkey, string $ext = '') {
        if($type == 1) {
            $url = $this->file_upload_domain.$fkey;
            if(!empty($ext)) {
                $url .= "?attname=" .$ext;
            }
            return $this->auth->privateDownloadUrl($url);
        }else {
            $url = $this->file_upload_domain.$fkey;
            if(!empty($ext)) {
                $url .= "?attname=" . $ext;
            }
            return $url;
        }
    }

    /**
     * 预览图片获取缩率图(公有)
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $type 类型
     * @param int $width 宽度
     * @param int $height 高度
     * @return string 返回
     */
    public function pubThumbUrl(string $fkey, int $type, int $width = 0, int $height = 0) {
        return $this->file_upload_domain . $fkey . '?imageView2/' . $type . '/w/' . $width . '/h/' . $height;
    }

    /**
     * 预览图片获取缩率图(私有)
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $type 类型
     * @param int $width 宽度
     * @param int $height 高度
     * @return string 返回
     */
    public function thumbUrl(string $fkey, int $type, int $width = 0, int $height = 0) {
        $url = $this->file_upload_domain . $fkey . '?imageView2/' . $type . '/w/' . $width . '/h/' . $height;
        return $this->auth->privateDownloadUrl($url);
    }

    /**
     * 获取元数据
     * @author lwh 2019-12-03
     * @param $type 类型,默认0,0-表示公有, 1-表示私有
     * @param $fkey 图片文件key
     * @return mixed
     */
    public function getFileMine(int $type, string $fkey) {
        if($type == 1) {
            $url  = '/stat/'.\Qiniu\base64_urlSafeEncode($this->bucket.':'.$fkey);
            $auth = $this->auth->authorization($url);
            $headers['Authorization'] = $auth['Authorization'];
            $res  = Client::get($this->ObjHost.$url, $headers);
            return $res->json();
        }else {
            $url  = '/stat/'.\Qiniu\base64_urlSafeEncode($this->bucket.':'.$fkey);
            $auth = $this->auth->authorization($url);
            $headers['Authorization'] = $auth['Authorization'];
            $res  = Client::get($this->ObjHost.$url, $headers);
            return $res->json();
        }
    }

    /**
     * 下载文件(公有)
     * @author lwh 2019-12-03
     * @param $type 类型
     * @param $file_key 图片文件key
     * @param bool $need_mine
     * @return array|bool
     */
    public function pubDownloadFile(int $type, string $file_key, bool $need_mine = false) {
        $ext = '';
        if ($need_mine) {
            // 需要获取元数据信息，主要为了补充后缀
            $mine = $this->getFileMine(0,$file_key);
            if ($mine['mimeType'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $ext = '.xlsx';
            }
        }

        $save_path = $this->download_path.'file_temp/'.$type.'/';
        $file_name = md5($type.$file_key).$ext;

        $file_download_path = $this->pubDownloadUrl($file_key, $file_name);
        if (!is_dir($save_path)) {
            mkdir($save_path,0777,true);
        }

        try {
            $res = file_put_contents($save_path.$file_name, file_get_contents($file_download_path));
        } catch (\Exception $e) {
            return false;
        }
        if ($res) {
            return ['file_key' => $file_key, 'file_temp_path' => $save_path.$file_name];
        }
        return false;
    }
    /**
     * 下载文件(私有)
     * @author lwh 2019-12-03
     * @param $type 类型
     * @param $file_key 图片文件key
     * @param bool $need_mine
     * @return array|bool
     */
    public function downloadFile(int $type, string $file_key, bool $need_mine = false) {
        $ext = '';
        if ($need_mine) {
            // 需要获取元数据信息，主要为了补充后缀
            $mine = $this->getFileMine(1,$file_key);
            if ($mine['mimeType'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $ext = '.xlsx';
            }
        }

        $save_path = $this->download_path.'file_temp/'.$type.'/';
        $file_name = md5($type.$file_key).$ext;

        $file_download_path = $this->downloadUrl(1,$file_key, $file_name);
        if (!is_dir($save_path)) {
            mkdir($save_path,0777,true);
        }

        try {
            $res = file_put_contents($save_path.$file_name, file_get_contents($file_download_path));
        } catch (\Exception $e) {
            return false;
        }
        if ($res) {
            return ['file_key' => $file_key, 'file_temp_path' => $save_path.$file_name];
        }
        return false;
    }

    /**
     * 删除资源
     * @author lwh 2019-12-03
     * @param $key 图片文件key
     * @return mixed 返回
     */
    public function delFile(string $key) {
        $bucketManager = new BucketManager($this->downloadAuth());
        return $bucketManager->delete($this->bucket, $key);
    }

    /**
     * 上传网络资源图片（小图）
     * @author lwh 2019-12-03
     * @param $pic_url 地址
     * @return bool 返回
     */
    public function uploadPicUrl(string $pic_url) {
        $upload = new UploadManager();
        $file_name = md5(md5($pic_url).time().rand(1000, 9999));
        $upToken = $this->auth->uploadToken($this->bucket, null, 3600);
        $data = file_get_contents($pic_url);
        if (!$data) {
            return false;
        }
        $res = $upload->put($upToken, $file_name, $data);
        if ($res[0]) {
            return $res[0]['key'];
        }
        return false;
    }

    /**
     * 上传七牛
     * @author lwh 2019-12-03
     * @param $image_txt 内容
     * @return bool 返回
     */
    public function uploadPicBase64(string $image_txt) {
        $image = trim($image_txt);
        if ($image) {
            $upToken = $this->auth->uploadToken($this->bucket, null, 3600);
            $upUrl = $this->upload_url . '/putb64/-1';
            $qiniu = $this->phpCurlImg($upUrl, $image, $upToken);
            $qiniuArr = json_decode($qiniu,true);
            if(!empty($qiniuArr['key'])) {
                return $qiniuArr['key'];
            } else {
                return false;
            }
        }
        return false;
    }
    /**
     * 七牛base64上传方法
     * @author lwh 2019-12-03
     * @param $remote_server 地址
     * @param $post_string 参数
     * @param $upToken token
     * @return bool|string 返回
     */
    public function phpCurlImg(string $remote_server, string $post_string, string $upToken) {
        $headers = array();
        $headers[] = 'Content-Type:application/octet-stream';
        $headers[] = 'Authorization:UpToken '.$upToken;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$remote_server);
        curl_setopt($ch, CURLOPT_HTTPHEADER ,$headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 公库图片裁剪(公有)
     * @author lwh 2019-12-03
     * @param $key 图片文件key
     * @param string $gravity 位置
     * @param int $witch 宽度
     * @param int $height 高度
     * @return bool|string 返回
     */
    public function cropPubImg(string $key, string $gravity = 'NorthWest',  int $witch = 0, int $height = 0) {
        $url = $this->pubDownloadUrl($key);
        $url = $url."?imageMogr2/gravity/{$gravity}/crop/";
        if (!empty($witch) && !empty($height)) {
            return $url."{$witch}x{$height}";
        } elseif (empty($witch) && !empty($height)) {
            return $url."x{$height}";
        } elseif (!empty($witch) && empty($height)) {
            return $url."{$witch}x";
        } else {
            return false;
        }
    }
    /**
     * 私库图片裁剪(私有)
     * @author lwh 2019-12-03
     * @param $key 图片文件key
     * @param string $gravity 位置
     * @param int $witch 宽度
     * @param int $height 高度
     * @return bool|string 返回
     */
    public function cropPrivImg(string $key, string $gravity = 'NorthWest',  int $witch = 0, int $height = 0) {
        $url = $this->downloadUrl(1,$key);
        $url = $url."?imageMogr2/gravity/{$gravity}/crop/";
        if (!empty($witch) && !empty($height)) {
            return $url."{$witch}x{$height}";
        } elseif (empty($witch) && !empty($height)) {
            return $url."x{$height}";
        } elseif (!empty($witch) && empty($height)) {
            return $url."{$witch}x";
        } else {
            return false;
        }
    }

    /**
     * 获取带文字=水印的缩略图链接
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $watermark 文字水印内容
     * @param $gravity 水印位置
     * @param $type 缩略图类型
     * @param int $width 缩略图款
     * @param int $height 缩略图高
     * @param $imgwidth 图片文件宽
     * @return string 展示链接
     */
    public function getWatermarkTTUrl(string $fkey, string $watermark, string $gravity, int $type, int $width = 0, int $height = 0, int $imgwidth) {
        $font_width = mb_strlen($watermark, 'UTF-8');
        $size       = intval($imgwidth * 10 / $font_width);
        $water      = 'watermark/2/text/' . $this->base64UrlSafeEncode($watermark) . '/gravity/' . $gravity . '/dissolve/100/font/' . $this->base64_urlSafeEncode('微软雅黑') . '/fontsize/' . $size . '/fill/' . $this->base64_urlSafeEncode('#e7e7e7');
        $image      = 'imageView2/' . $type . '/w/' . $width . '/h/' . $height;
        $url        = $this->file_upload_domain . $fkey . '?' . $water . '|' . $image;
        return $this->getAuthorization($url);
    }

    /**
     * 获取带图片水印的缩略图的链接
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $watermark 水印图片key
     * @param $gravity 指定水印位置
     * @param $type 缩略图类型
     * @param int $width 缩略图宽
     * @param int $height 缩略图高
     * @param $imgwidth 图片文件的宽
     * @param $imgheight 图片文件的高
     * @return string 展示链接
     */
    public function getWatermarkImageThumbUrl(string $fkey, string $watermark, string $gravity, int $type, int $width = 0, int $height = 0, int $imgwidth, int $imgheight) {

        $ws    = $imgwidth > $imgheight ? 0.7 : 0.5;
        $image = $this->downloadUrl(1, $watermark);
        $water = 'watermark/1/image/' . $this->base64UrlSafeEncode($image) . '/dissolve/100/gravity/' . $gravity . '/ws/' . $ws."/wst/1";
        $image = 'imageView2/' . $type . '/w/' . $width . '/h/' . $height;
        $url   = $this->file_upload_domain . $fkey . '?' . $water . '|' . $image;
        return $this->getAuthorization($url);
    }

    /**
     * 生成带文字水印的图片私有下载地址
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param string $alias 名称
     * @param $watermark 水印
     * @param $gravity 位置
     * @param $width 宽度
     * @return string 返回
     */
    public function getTextWatermarkPrivateDownloadUrl(string $fkey, string $alias = '', string $watermark, string $gravity, int $width) {
        $font_width = mb_strlen($watermark, 'UTF-8');
        $size       = intval($width * 10 / $font_width);
        $water      = 'watermark/2/text/' . $this->base64UrlSafeEncode($watermark) . '/gravity/' . $gravity . '/dissolve/100/font/' . $this->base64_urlSafeEncode('微软雅黑') . '/fontsize/' . $size . '/fill/' . $this->base64_urlSafeEncode('#e7e7e7');
        $url        = $this->file_upload_domain . $fkey . '?' . $water;
        if (!empty($alias)) {
            $alias = urlencode($alias);
            $url   .= "&attname=$alias";
        }
        return $this->getAuthorization($url);
    }

    /**
     * 生成带图片水印的图片私有下载地址
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param string $alias 名称
     * @param $watermark 水印
     * @param $gravity 位置
     * @param $width 宽度
     * @param $height 高度
     * @return string 返回
     */
    public function getImageWatermarkPrivateDownloadUrl(string $fkey, string $alias = '', string $watermark, string $gravity, int $width, int $height) {
        $ws    = $width > $height ? 0.7 : 0.5;
        $image = $this->downloadUrl(1,$watermark);
        $water = 'watermark/1/image/' . $this->base64UrlSafeEncode($image) . '/dissolve/100/gravity/' . $gravity . '/ws/' . $ws."/wst/1";
        $url   = $this->file_upload_domain . $fkey . '?' . $water;
        if (!empty($alias)) {
            $alias = urlencode($alias);
            $url   .= "&attname=$alias";
        }
        return $this->getAuthorization($url);
    }

    /**
     * 上传单个文件（支持分片上传）
     * @author lwh 2019-12-03
     * @modify LYJ 2020.06.01 增加参数key 保持外部可以自定义文件key值
     * @param $file_path 地址
     * @param $auth_token token
     * @param array $params 参数
     * @param string $fileKey 七牛云key值
     * @return array 参数
     * @throws \Exception
     */
    public function uploadSingleFile(string $file_path, string $auth_token, array $params = [], string $fileKey = '') {
        $uploader = new UploadManager();
        if (!$fileKey) {
            $fileKey = pathinfo($file_path, PATHINFO_EXTENSION);
        }
        return $uploader->putFile($auth_token, $fileKey, $file_path, $params);
    }

    /**
     * 多文件压缩，单文件路径处理
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $fname 文件名
     * @param $alias string 文件别名
     * @return string 返回
     */
    public function watermarkZipPackSingleFileUrl(string $fkey, string $fname, string $alias = '', string $watermark, string $gravity, int $width) {
        $file_public_url = $this->getTextWatermarkPrivateDownloadUrl($fkey, $fname, $watermark, $gravity, $width);
        $url             = '/url/' . $this->base64UrlSafeEncode($file_public_url);
        if (!empty($alias)) {
            $url .= '/alias/' . $this->base64UrlSafeEncode($alias);
        }
        return $url;
    }

    public function getAuthorization(string $url) {
        return $this->auth->privateDownloadUrl($url);
    }

    /**
     * 文件压缩认证
     * @author lwh 2019-12-03
     * @param $url 地址
     * @param $body body
     * @return array 返回
     */
    public function getZipAuthorization(string $url, string $body) {
        $authorization = $this->auth->authorization($url, $body, 'application/x-www-form-urlencoded');
        if ($authorization['Authorization']) {
            return $authorization['Authorization'];
        }
        return false;
    }

    /**
     * 多文件压缩，单文件路径处理
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $fname 文件名
     * @param $alias string 文件别名
     * @return string 返回
     */
    public function zipPackSingleFileUrl(string $fkey, string $fname, string $alias = '') {
        $file_public_url = $this->downloadUrl(1,$fkey, $fname);
        $url = '/url/' . $this->base64UrlSafeEncode($file_public_url);
        if (!empty($alias)) {
            $url .= '/alias/' . $this->base64UrlSafeEncode($alias);
        }
        return $url;
    }

    /**
     * 获取压缩图片
     * @author lwh 2019-12-03
     * @param $fkey 图片文件key
     * @param $size 大小
     * @return string 返回
     */
    public function imageMogrFileSizeLimitUrl(string $fkey, int $size) {
        $url = $this->file_upload_domain . $fkey . '?imageMogr2/size-limit/' . $size;
        return $this->getAuthorization($url);
    }

    /**
     * 组装多文件压缩内容
     * @author lwh 2019-12-03
     * @param $fops 参数
     * @return string 返回
     */
    public function pfopZipFileParams(string $fops) {
        $encoding      = 'utf-8';
        //处理操作系统编码，如果是win则修改成gbk
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/win/i', $agent)) {
            $encoding = 'gbk';
        }
        $fops = 'mkzip/2' . '/encoding/' . $this->base64UrlSafeEncode($encoding) . $fops;
        return "bucket=$this->bucket&key=$this->exit_file_key&fops=$fops";
    }

    /**
     * URL安全的Base64编码。
     * @author lwh 2019-12-03
     * @param $data 参数
     * @return mixed 返回
     */
    public function base64UrlSafeEncode(string $data) {
        $find    = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }

    /**
     * 获取七牛文件下载地址
     * @author GJQ 2020-03-16
     * @param $fkey
     * @param string $ext
     * @param string $attrNmae
     * @return string
     */
    public function downloadFileUrl(string $fkey, string $ext = '', string $attrNmae = '') {
        $url = $this->file_upload_domain . $fkey;
        if(!empty($ext)) {
            $url .= empty($attrNmae) ? "?attname=" . md5($fkey). $ext : "?attname=" . $attrNmae . $ext;
        }
        return $url;
    }

    /**
     * 获取图片信息
     * @author LYJ 2020.05.22
     * @param string $fkey 七牛key
     */
    public function getImageInfo(string $fkey) {
        $url = $this->file_upload_domain.$this->file_upload_domain . $fkey.'?imageInfo';
        $client = Client::get($url);
        return $client->json();
    }

    /**
     * 查询文件信息
     * @author LYJ 2020.05.29
     * @param string $fkey key值
     */
    public function getFileInfo(string $fkey) {
        $entry = $this->bucket.':'.$fkey;
        $encodedEntryURI = $this->base64UrlSafeEncode($entry);
        $url = 'https://rs.qbox.me/stat/'.$encodedEntryURI;
        $headers = $this->auth->authorization($url);
        $client = Client::get($url, $headers);
        return $client->json();
    }

}
