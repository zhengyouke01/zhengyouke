<?php
declare(strict_types = 1);
namespace zyk\tools;

use mysql_xdevapi\XSession;

class Easemob  implements BaseInterface {

	private $client_id;
	private $client_secret;
	private $org_name;
	private $app_name;
	private $url;
    protected static $instance;
    //------------------------------------------------------用户体系
	/**
	 * 初始化参数
	 *
	 * @param array $options
	 * @param $options['client_id']
	 * @param $options['client_secret']
	 * @param $options['org_name']
	 * @param $options['app_name']
	 */
	public function __construct(array $options = []) {
		$this->client_id = $options ['client_id'] ?? '';
		$this->client_secret = $options ['client_secret'] ?? '';
		$this->org_name = $options ['org_name'] ?? '';
		$this->app_name = $options ['app_name'] ?? '';
		if (!empty ( $this->org_name ) && !empty ( $this->app_name )) {
			$this->url = 'https://a1.easemob.com/' . $this->org_name . '/' . $this->app_name . '/';
		}
	}

    public function serviceInfo() {
        return ['service_name' => '环信操作类', 'service_class' => 'Easemob', 'service_describe' => '环信操作', 'author' => 'LYJ', 'version' => '1.0'];
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return \think\Request
     */
    public static function instance(array $options = []) {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }
	/**
	*获取token
	*/
	public function getToken() {
		$options=array(
		"grant_type"=>"client_credentials",
		"client_id"=>$this->client_id,
		"client_secret"=>$this->client_secret
		);
		//json_encode()函数，可将PHP数组或对象转成json字符串，使用json_decode()函数，可以将json字符串转换为PHP数组或对象
		$body=json_encode($options);
		//使用 $GLOBALS 替代 global
		$url=$this->url.'token';
		//$url=$base_url.'token';
		$tokenResult = $this->postCurl($url,$body,$header=array());
		//var_dump($tokenResult['expires_in']);
		//return $tokenResult;
		return "Authorization:Bearer ".$tokenResult['access_token'];

	}
    /**
	 * 授权注册
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $nickname 用户昵称
     * @return mixed
     */
    public function createUser(string $username, string $password, string $nickname=''){
		$url = $this->url.'users';
		$options = array(
			"username"=>$username,
			"password"=>$password
		);
		if($nickname) {
            $options['nickname'] = $nickname;
		}
		$body = json_encode($options);
		$header = array($this->getToken());
		$result = $this->postCurl($url,$body,$header);
		return $result;
	}
	/*
		批量注册用户
	*/
    public function createUsers(string $options){
		$url=$this->url.'users';

		$body=json_encode($options);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/*
	 * 重置用户密码
	*/
    public function resetPassword(string $username, string $newpassword){
		$url=$this->url.'users/'.$username.'/password';
		$options=array(
			"newpassword"=>$newpassword
		);
		$body=json_encode($options);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header,"PUT");
		return $result;
	}

	/*
	 *获取单个用户
	*/
    public function getUser(string $username){
		$url=$this->url.'users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/*
		获取批量用户----不分页
	*/
    public function getUsers(int $limit=0){
		if(!empty($limit)){
			$url=$this->url.'users?limit='.$limit;
		}else{
			$url=$this->url.'users';
		}
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/*
		获取批量用户---分页
	*/
    public function getUsersForPage(int $limit=0, string $cursor=''){
		$url=$this->url.'users?limit='.$limit.'&cursor='.$cursor;

		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		if(!empty($result["cursor"])){
			$cursor=$result["cursor"];
			$this->writeCursor("userfile.txt",$cursor);
		}
		//var_dump($GLOBALS['cursor'].'00000000000000');
		return $result;
	}

	//创建文件夹
    public function mkdirs($dir, $mode = 0777)
	 {
		 if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
		 if (!mkdirs(dirname($dir), $mode)) return FALSE;
		 return @mkdir($dir, $mode);
	 }
	 //写入cursor
    public function writeCursor(string $filename, string $content){
		//判断文件夹是否存在，不存在的话创建
		if(!file_exists("resource/txtfile")){
			mkdirs("resource/txtfile");
		}
		$myfile=@fopen("resource/txtfile/".$filename,"w+") or die("Unable to open file!");
		@fwrite($myfile,$content);
		fclose($myfile);
	}
	 //读取cursor
    public function readCursor(string $filename){
		//判断文件夹是否存在，不存在的话创建
		if(!file_exists("resource/txtfile")){
			mkdirs("resource/txtfile");
		}
		$file="resource/txtfile/".$filename;
		$fp=fopen($file,"a+");//这里这设置成a+
		if($fp){
			while(!feof($fp)){
				//第二个参数为读取的长度
				$data=fread($fp,1000);
			}
			fclose($fp);
		}
		return $data;
	}
	/*
		删除单个用户
	*/
    public function deleteUser(string $username){
		$url=$this->url.'users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		删除批量用户
		limit:建议在100-500之间，、
		注：具体删除哪些并没有指定, 可以在返回值中查看。
	*/
    public function deleteUsers(int $limit){
		$url=$this->url.'users?limit='.$limit;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;

	}
	/*
	*修改用户昵称
	*/
    public function editNickname(string $username, string $nickname){
		$url=$this->url.'users/'.$username;
		$options=array(
			"nickname"=>$nickname
		);
		$body=json_encode($options);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header,'PUT');
		return $result;
	}
	/*
		添加好友-
	*/
    public function addFriend(string $username, string $friend_name){
		$url=$this->url.'users/'.$username.'/contacts/users/'.$friend_name;
		$header=array($this->getToken(),'Content-Type:application/json');
		$result=$this->postCurl($url,'',$header,'POST');
		return $result;


	}


	/*
		删除好友
	*/
    public function deleteFriend(string $username, string $friend_name){
		$url=$this->url.'users/'.$username.'/contacts/users/'.$friend_name;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;

	}
	/*
		查看好友
	*/
    public function showFriends(string $username){
		$url=$this->url.'users/'.$username.'/contacts/users';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;

	}
	/*
		查看用户黑名单
	*/
    public function getBlacklist(string $username){
		$url=$this->url.'users/'.$username.'/blocks/users';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;

	}
	/*
		往黑名单中加人
	*/
    public function addUserForBlacklist(string $username, string $usernames){
		$url=$this->url.'users/'.$username.'/blocks/users';
		$body=json_encode($usernames);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header,'POST');
		return $result;

	}
	/*
		从黑名单中减人
	*/
    public function deleteUserFromBlacklist(string $username, string $blocked_name){
		$url=$this->url.'users/'.$username.'/blocks/users/'.$blocked_name;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;

	}
	 /*
		查看用户是否在线
	 */
    public function isOnline(string $username){
		$url=$this->url.'users/'.$username.'/status';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;

	}
	/*
		查看用户离线消息数
	*/
    public function getOfflineMessages(string $username){
		$url=$this->url.'users/'.$username.'/offline_msg_count';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;

	}
	/*
		查看某条消息的离线状态
		----deliverd 表示此用户的该条离线消息已经收到
	*/
    public function getOfflineMessageStatus(string $username, int $msg_id){
		$url=$this->url.'users/'.$username.'/offline_msg_status/'.$msg_id;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;

	}
	/*
		禁用用户账号
	*/
    public function deactiveUser(string $username){
		$url=$this->url.'users/'.$username.'/deactivate';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header);
		return $result;
	}
	/*
		解禁用户账号
	*/
    public function activeUser(string $username){
		$url=$this->url.'users/'.$username.'/activate';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header);
		return $result;
	}

	/*
		强制用户下线
	*/
    public function disconnectUser(string $username){
		$url=$this->url.'users/'.$username.'/disconnect';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	//--------------------------------------------------------上传下载
	/*
		上传图片或文件
	*/
    public function uploadFile(string $filePath){
		$url=$this->url.'chatfiles';
		$file=file_get_contents($filePath);
		$body['file']=$file;
		$header=array('Content-type: multipart/form-data',$this->getToken(),"restrict-access:true");
		$result=$this->postCurl($url,$body,$header,'XXX');
		return $result;

	}
	/*
		下载文件或图片
	*/
    public function downloadFile(string $uuid, string $shareSecret, array $ext)
	{
		$url = $this->url . 'chatfiles/' . $uuid;
		$header = array("share-secret:" . $shareSecret, "Accept:application/octet-stream", $this->getToken(),);

		if ($ext=="png") {
			$result=$this->postCurl($url,'',$header,'GET');
		}else {
			$result = $this->getFile($url);
		}
		$filename = md5(time().mt_rand(10, 99)).".".$ext; //新图片名称
		if(!file_exists("resource/down")){
			mkdir("resource/down/");
		}

		$file = @fopen("resource/down/".$filename,"w+");//打开文件准备写入
		@fwrite($file,$result);//写入
		fclose($file);//关闭
		return $filename;

	}

    public function getFile(string $url){
		set_time_limit(0); // unlimited max execution time

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 600); //max 10 minutes
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	/*
		下载图片缩略图
	*/
    public function downloadThumbnail(string $uuid, string $shareSecret){
		$url=$this->url.'chatfiles/'.$uuid;
		$header = array("share-secret:".$shareSecret,"Accept:application/octet-stream",$this->getToken(),"thumbnail:true");
		$result=$this->postCurl($url,'',$header,'GET');
		$filename = md5(time().mt_rand(10, 99))."th.png"; //新图片名称
		if(!file_exists("resource/down")){
			//mkdir("../image/down");
			mkdirs("resource/down/");
		}

		$file = @fopen("resource/down/".$filename,"w+");//打开文件准备写入
		@fwrite($file,$result);//写入
		fclose($file);//关闭
		return $filename;
	}



	//--------------------------------------------------------发送消息
	/*
		发送文本消息
	*/
    public function sendText(string $from="admin", string $target_type, string $target, string $content, array $ext){
		$url=$this->url.'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="txt";
		$options['msg']=$content;
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$b,$header);
		return $result;
	}
	/*
		发送透传消息
	*/
    public function sendCmd($from="admin",$target_type,$target,$action,$ext){
		$url=$this->url.'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="cmd";
		$options['action']=$action;
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$b,$header);
		return $result;
	}
	/*
		发图片消息
	*/
    public function sendImage($filePath,$from="admin",$target_type,$target,$filename,$ext){
		$result=$this->uploadFile($filePath);
		$uri=$result['uri'];
		$uuid=$result['entities'][0]['uuid'];
		$shareSecret=$result['entities'][0]['share-secret'];
		$url=$this->url.'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="img";
		$options['url']=$uri.'/'.$uuid;
		$options['filename']=$filename;
		$options['secret']=$shareSecret;
		$options['size']=array(
			"width"=>480,
			"height"=>720
		);
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array($this->getToken());
		//$b=json_encode($body,true);
		$result=$this->postCurl($url,$b,$header);
		return $result;
	}
	/*
		发语音消息
	*/
    public function sendAudio($filePath,$from="admin",$target_type,$target,$filename,$length,$ext){
		$result=$this->uploadFile($filePath);
		$uri=$result['uri'];
		$uuid=$result['entities'][0]['uuid'];
		$shareSecret=$result['entities'][0]['share-secret'];
		$url=$this->url.'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="audio";
		$options['url']=$uri.'/'.$uuid;
		$options['filename']=$filename;
		$options['length']=$length;
		$options['secret']=$shareSecret;
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array($this->getToken());
		//$b=json_encode($body,true);
		$result=$this->postCurl($url,$b,$header);
		return $result;}
	/*
		发视频消息
	*/
    public function sendVedio($filePath,$from="admin",$target_type,$target,$filename,$length,$thumb,$thumb_secret,$ext){
	$result=$this->uploadFile($filePath);
		$uri=$result['uri'];
		$uuid=$result['entities'][0]['uuid'];
		$shareSecret=$result['entities'][0]['share-secret'];
		$url=$this->url.'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="video";
		$options['url']=$uri.'/'.$uuid;
		$options['filename']=$filename;
		$options['thumb']=$thumb;
		$options['length']=$length;
		$options['secret']=$shareSecret;
		$options['thumb_secret']=$thumb_secret;
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array($this->getToken());
		//$b=json_encode($body,true);
		$result=$this->postCurl($url,$b,$header);
		return $result;
	}
	/*
	发文件消息
	*/
    public function sendFile($filePath,$from="admin",$target_type,$target,$filename,$length,$ext){
		$result=$this->uploadFile($filePath);
		$uri=$result['uri'];
		$uuid=$result['entities'][0]['uuid'];
		$shareSecret=$result['entities'][0]['share-secret'];
		$url=$GLOBALS['base_url'].'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="file";
		$options['url']=$uri.'/'.$uuid;
		$options['filename']=$filename;
		$options['length']=$length;
		$options['secret']=$shareSecret;
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array(getToken());
		//$b=json_encode($body,true);
		$result=postCurl($url,$b,$header);
		return $result;
	}
	//-------------------------------------------------------------群组操作

	/*
		获取app中的所有群组----不分页
	*/
    public function getGroups($limit=0){
		if(!empty($limit)){
			$url=$this->url.'chatgroups?limit='.$limit;
		}else{
			$url=$this->url.'chatgroups';
		}
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/*
		获取app中的所有群组---分页
	*/
    public function getGroupsForPage($limit=0,$cursor=''){
		$url=$this->url.'chatgroups?limit='.$limit.'&cursor='.$cursor;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");

		if(!empty($result["cursor"])){
			$cursor=$result["cursor"];
			$this->writeCursor("groupfile.txt",$cursor);
		}
		return $result;
	}
	/*
		获取一个或多个群组的详情
	*/
    public function getGroupDetail($group_ids){
		$g_ids=implode(',',$group_ids);
		$url=$this->url.'chatgroups/'.$g_ids;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	/*
		创建一个群组
	*/
    public function createGroup($options){
		$url=$this->url.'chatgroups';
		$header=array($this->getToken());
		$body=json_encode($options);
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/*
		修改群组信息
	*/
    public function modifyGroupInfo($group_id,$options){
		$url=$this->url.'chatgroups/'.$group_id;
		$body=json_encode($options);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header,'PUT');
		return $result;
	}
	/*
		删除群组
	*/
    public function deleteGroup($group_id){
		$url=$this->url.'chatgroups/'.$group_id;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		获取群组中的成员
	*/
    public function getGroupUsers($group_id){
		$url=$this->url.'chatgroups/'.$group_id.'/users';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	/*
		群组单个加人
	*/
    public function addGroupMember($group_id,$username){
		$url=$this->url.'chatgroups/'.$group_id.'/users/'.$username;
		$header=array($this->getToken(),'Content-Type:application/json');
		$result=$this->postCurl($url,'',$header);
		return $result;
	}
	/*
		群组批量加人
	*/
    public function addGroupMembers($group_id,$usernames){
		$url=$this->url.'chatgroups/'.$group_id.'/users';
		$body=json_encode($usernames);
		$header=array($this->getToken(),'Content-Type:application/json');
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/*
		群组单个减人
	*/
    public function deleteGroupMember($group_id,$username){
		$url=$this->url.'chatgroups/'.$group_id.'/users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		群组批量减人
	*/
    public function deleteGroupMembers($group_id,$usernames){
		$url=$this->url.'chatgroups/'.$group_id.'/users/'.$usernames;
		//$body=json_encode($usernames);
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		获取一个用户参与的所有群组
	*/
    public function getGroupsForUser($username){
		$url=$this->url.'users/'.$username.'/joined_chatgroups';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	/*
		群组转让
	*/
    public function changeGroupOwner($group_id,$options){
		$url=$this->url.'chatgroups/'.$group_id;
		$body=json_encode($options);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header,'PUT');
		return $result;
	}
	/*
		查询一个群组黑名单用户名列表
	*/
    public function getGroupBlackList($group_id){
		$url=$this->url.'chatgroups/'.$group_id.'/blocks/users';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	/*
		群组黑名单单个加人
	*/
	public function addGroupBlackMember($group_id,$username){
		$url=$this->url.'chatgroups/'.$group_id.'/blocks/users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header);
		return $result;
	}
	/*
		群组黑名单批量加人
	*/
	public function addGroupBlackMembers($group_id,$usernames){
		$url=$this->url.'chatgroups/'.$group_id.'/blocks/users';
		$body=json_encode($usernames);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/*
		群组黑名单单个减人
	*/
	public function deleteGroupBlackMember($group_id,$username){
		$url=$this->url.'chatgroups/'.$group_id.'/blocks/users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		群组黑名单批量减人
	*/
	public function deleteGroupBlackMembers($group_id,$usernames){
		$url=$this->url.'chatgroups/'.$group_id.'/blocks/users';
		$body=json_encode($usernames);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header,'DELETE');
		return $result;
	}
	//-------------------------------------------------------------聊天室操作
	/*
		创建聊天室
	*/
	public function createChatRoom($options){
		$url=$this->url.'chatrooms';
		$header=array($this->getToken());
		$body=json_encode($options);
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/*
		修改聊天室信息
	*/
	public function modifyChatRoom($chatroom_id,$options){
		$url=$this->url.'chatrooms/'.$chatroom_id;
		$body=json_encode($options);
        $header = array($this->getToken());
		$result=$this->postCurl($url,$body,$header,'PUT');
		return $result;
	}
	/*
		删除聊天室
	*/
	public function deleteChatRoom($chatroom_id){
		$url=$this->url.'chatrooms/'.$chatroom_id;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		获取app中所有的聊天室
	*/
	public function getChatRooms(){
		$url=$this->url.'chatrooms';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}

	/*
		获取一个聊天室的详情
	*/
	public function getChatRoomDetail($chatroom_id){
		$url=$this->url.'chatrooms/'.$chatroom_id;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	/*
		获取一个用户加入的所有聊天室
	*/
	public function getChatRoomJoined($username){
		$url=$this->url.'users/'.$username.'/joined_chatrooms';
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'GET');
		return $result;
	}
	/*
		聊天室单个成员添加
	*/
	public function addChatRoomMember($chatroom_id,$username){
		$url=$this->url.'chatrooms/'.$chatroom_id.'/users/'.$username;
		//$header=array($this->getToken());
		$header=array($this->getToken(),'Content-Type:application/json');
		$result=$this->postCurl($url,'',$header);
		return $result;
	}
	/*
		聊天室批量成员添加
	*/
	public function addChatRoomMembers($chatroom_id,$usernames){
		$url=$this->url.'chatrooms/'.$chatroom_id.'/users';
		$body=json_encode($usernames);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$body,$header);
		return $result;
	}
	/*
		聊天室单个成员删除
	*/
	public function deleteChatRoomMember($chatroom_id,$username){
		$url=$this->url.'chatrooms/'.$chatroom_id.'/users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	/*
		聊天室批量成员删除
	*/
	public function deleteChatRoomMembers($chatroom_id,$usernames){
		$url=$this->url.'chatrooms/'.$chatroom_id.'/users/'.$usernames;
		//$body=json_encode($usernames);
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,'DELETE');
		return $result;
	}
	//-------------------------------------------------------------聊天记录

	/*
		导出聊天记录----不分页
	*/
	public function getChatRecord($ql){
		if(!empty($ql)){
			$url=$this->url.'chatmessages?ql='.$ql;
		}else{
			$url=$this->url.'chatmessages';
		}
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		return $result;
	}
	/*
		导出聊天记录---分页
	*/
	public function getChatRecordForPage($ql,$limit=0,$cursor){
		if(!empty($ql)){
			$url=$this->url.'chatmessages?ql='.$ql.'&limit='.$limit.'&cursor='.$cursor;
		}
		$header=array($this->getToken());
		$result=$this->postCurl($url,'',$header,"GET");
		$cursor=isset ( $result["cursor"] ) ? $result["cursor"] : '-1';
		$this->writeCursor("chatfile.txt",$cursor);
		//var_dump($GLOBALS['cursor'].'00000000000000');
		return $result;
	}

	/**
	 *$this->postCurl方法
	 */
	public function postCurl($url,$body,$header,$type="POST"){
		//1.创建一个curl资源
		$ch = curl_init();
		//2.设置URL和相应的选项
		curl_setopt($ch,CURLOPT_URL,$url);//设置url
		//1)设置请求头
		//array_push($header, 'Accept:application/json');
		//array_push($header,'Content-Type:application/json');
		//array_push($header, 'http:multipart/form-data');
		//设置为false,只会获得响应的正文(true的话会连响应头一并获取到)
		curl_setopt($ch,CURLOPT_HEADER,0);
//		curl_setopt ( $ch, CURLOPT_TIMEOUT,5); // 设置超时限制防止死循环
		//设置发起连接前的等待时间，如果设置为0，则无限等待。
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		//将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//2)设备请求体
		if ($body) {
			//$b=json_encode($body,true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);//全部数据使用HTTP协议中的"POST"操作来发送。
		}
		//设置请求头
		if(count($header)>0){
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		}
		//上传文件相关设置
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算

		//3)设置提交方式
		switch($type){
			case "GET":
				curl_setopt($ch,CURLOPT_HTTPGET,true);
				break;
			case "POST":
				curl_setopt($ch,CURLOPT_POST,true);
				break;
			case "PUT"://使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。这对于执行"DELETE" 或者其他更隐蔽的HTT
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
				break;
			case "DELETE":
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"DELETE");
				break;
		}


		//4)在HTTP请求中包含一个"User-Agent: "头的字符串。-----必设

//		curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
//		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
		//5)

		//3.抓取URL并把它传递给浏览器
		$res=curl_exec($ch);

		$result=json_decode($res,true);
		//4.关闭curl资源，并且释放系统资源
		curl_close($ch);
		if(empty($result))
			return $res;
		else
			return $result;
	}
}
