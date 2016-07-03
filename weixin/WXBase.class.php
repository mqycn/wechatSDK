<?php
	
	class WXBase {
		protected $appId;
		protected $appSecret;
		protected $varPath;	//缓存保存路径

		public function __construct($appId, $appSecret, $varPath = '') {
			if( $varPath != ''){
				$varPath .= "/";
			}
			$this -> appId = $appId;
			$this -> appSecret = $appSecret;
			$this -> varPath = $varPath;
		}
		
		/*
			调用微信 API 接口
		*/
		public function Api($ACTION = 'getcallbackip', $POST = '', $queryAddon = '') {
			$res = $this -> HttpGet( $this -> ApiUrl($ACTION, $queryAddon), $POST);
			return json_decode($res, true);
		}
		
		/*
			API调用示例：发送模板消息
		*/
		public function TemplateSend($templateId, $openid, $dataInfo, $url = ""){
			if( $url == "" ){
				$url = $this -> GetCurrentUrl();
			}
			$data = array();
			foreach( $dataInfo as $key => $val){
				$data[$key] = array(
					"value" => $val,
					"color" => "#173177"
				);
			}
			$post = array(
				"touser" => $openid,
				"template_id" => $templateId,
				"url" => $url,
				"data" => $data
			);
			$arr = $this -> Api("message/template/send", json_encode($post));
			$debug = array(
				"action" => $post,
				"result" => $arr
			);
			//记录 模板消息日志
			file_put_contents($this -> varPath . "../msg/" . time(), json_encode($debug));
			return $arr;
		}

		/*
			获取微信 API 接口 的URL
		*/
		protected function ApiUrl($ACTION, $queryAddon = '') {
			if( $queryAddon != '' ){
				if( is_array($queryAddon) ){
					$queryAddon = $this -> BuilderQuery($queryAddon);
				}
				$queryAddon = "&" . $queryAddon;
			}
			return "https://api.weixin.qq.com/cgi-bin/" . $ACTION . "?access_token=" . $this -> GetAccessToken() . $queryAddon;
		}
		
		/*
			返回当前网页地址
		*/
		public function GetCurrentUrl(){
			return $this -> GetSiteUrl() . $_SERVER["REQUEST_URI"];
		}
		
		/*
			返回回调地址
		*/
		public function GetCallbackUrl($ACTION = 'checkLogin', $path = '/wechat/'){
			return $this -> GetSiteUrl() . $path . $ACTION;
		}
		
		/*
			返回HTTP请求方式
		*/
		public function GetProtocol(){
			return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		}
		
		/*
			返回 站点 域名
		*/
		public function GetSiteUrl(){
			return $this -> GetProtocol() . $_SERVER['SERVER_NAME'];
		}
		
		/*
			生成随机字符串
		*/
		protected function CreateNonceStr($length = 16) {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$str = "";
			for ($i = 0; $i < $length; $i++) {
				$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			}
			return $str;
		}
		
		/*
			获取AccessToken
		*/
		protected function GetAccessToken() {
			$data = $this -> GetCacheItem("access_token");
			if($data == ""){
				$url =  "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this -> appId . "&secret=" . $this -> appSecret;
				$res = json_decode($this -> HttpGet( $url ), true);
				$data = $this -> SetCacheItem("access_token", $res);
			}
			return $data;
		}
		
		/*
			获取JsApiTicket
		*/
		protected function GetJsApiTicket() {
			$data = $this -> GetCacheItem("ticket");
			if($data == ""){
				$data = $this -> SetCacheItem("ticket", $this -> Api("ticket/getticket", "", "type=jsapi") );
			}
			return $data;
		}
		
		/*
			返回 HTTP请求结果
			$post不为空是，使用POST方式提交
		*/
		protected function HttpGet($url, $post = '', $sslcert = '', $sslkey = '') {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 500);
			// 如果出错请参考：http://www.miaoqiyuan.cn/p/curl-cacert
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curl, CURLOPT_URL, $url);
			if( $sslcert != "" && $sslkey != ""){
				curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
				curl_setopt($curl, CURLOPT_SSLCERT, $sslcert);
				curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
				curl_setopt($curl, CURLOPT_SSLKEY, $sslkey);
			}
			if( $post != "" ){
				curl_setopt($curl, CURLOPT_POST, 1); //设置为POST方式
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post); //POST数据
			}
			$res = curl_exec($curl) or die(curl_error($curl));
			curl_close($curl);
			return $res;
		}
		
		/*
			数组生成摘要
		*/
		protected function SignatureArray($arr, $signType = "sha1", $joinArr = ''){
			$arr = $this -> ArraySort($arr);
			if( is_array($joinArr) ){
				foreach( $joinArr as $key => $val ){
					if( is_string($val) && trim($val) != "" ){
						$arr[$key] = $val;
					}
				}
			}
			return $signType($this -> BuilderQuery($arr, false));
		}
		
		/*
			数组排序
		*/
		protected function ArraySort($arr, $sortType = SORT_ASC){
			$sortArr = array();
			foreach($arr as $key => $val){
				$sortArr[] = $key;
			}
			array_multisort($sortArr, $sortType, $arr);
			unset($sortType);
			return $arr;
		}
		
		/*
			数组生成URL请求字符串
		*/
		protected function BuilderQuery($arr, $encode = true){
			$str = "";
			foreach($arr as $key => $val){
				$str .= "&" . $key . "=" . ($encode ? urlencode($val) : $val);
			}
			if( $str != ""){
				$str = substr($str, 1);
			}
			return $str;
		}
		
		/*
			获取缓存的项目
		*/
		private function GetCacheItem( $cacheName ){
			$data = json_decode($this -> GetCache( $cacheName ), true);
			if( !isset($data[$cacheName]) || !isset($data['expire_time']) || $data['expire_time'] < time() ){
				return "";
			}else{
				return $data[$cacheName];
			}
		}
		
		/*
			设置缓存的项目
		*/
		private function SetCacheItem( $cacheName , $data ){
			if( isset($data[$cacheName]) ){
				$arr = array(
					"expire_time"	=> time() + 2000,
					$cacheName		=> $data[$cacheName]
				);
				$this -> SetCache($cacheName, json_encode($arr) );
				return $arr[$cacheName];
			}else{
				return "";
			}
		}
		
		/*
			缓存文件存储路径
		*/
		protected function GetCacheFile($cacheName) {
			return $this -> varPath . '_' . $cacheName . ".php";
		}
		
		/*
			读取缓存文件
		*/
		protected function GetCache($cacheName) {
			$fileName = $this -> GetCacheFile($cacheName);
			if( is_file( $fileName ) ){
				return trim(substr(file_get_contents( $fileName ), 15));
			}else{
				return "{}";
			}
		}
		
		/*
			写入缓存文件
		*/
		protected function SetCache($cacheName, $content) {
			$fp = fopen($this -> GetCacheFile($cacheName) , "w");
			fwrite($fp, "<?php exit();?>" . $content);
			fclose($fp);
		}
	}
?>