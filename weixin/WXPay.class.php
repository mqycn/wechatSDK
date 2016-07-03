<?php
	
	/*
		微信支付，JSAPI方式
	*/
	class WXPay extends WXBase{
		
		private $mchId;
		private $mchKey;
		private $mchCert;
		private $payUrl = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		private $refundUrl = "https://api.mch.weixin.qq.com/secapi/pay/refund";
		private $transfersUrl = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";

		
		public function __construct($appId, $appSecret, $varPath = '', $mchId = '', $mchKey = '', $mchCert = 'apiclient_cert.pem') {
			parent::__construct($appId, $appSecret, $varPath);
			$this -> mchId = $mchId;
			$this -> mchKey = $mchKey;
			$this -> mchCert = $mchCert;
		}
		
		/*
			创建订单 方式1（通过商户账号和KEY）
		*/
		public function Create($openid, $body, $total_fee, $trade_no, $notify_url = 'notify'){
			$orderInfo = $this -> CreateOrder($openid, $body, $total_fee, $trade_no, $notify_url);
			
			$orderInfo["openid"]		= $openid;
			$orderInfo["trade_type"]	= "JSAPI";
			$orderInfo["appid"]			= $this -> appId;
			$orderInfo["mch_id"]		= $this -> mchId;
			$orderInfo["nonce_str"]		= $this -> createNoncestr();
			$orderInfo["sign"]			= strtoupper($this -> SignatureArray($orderInfo, "md5", array("key" => $this -> mchKey)));
			
			$arr = $this -> ApiCallback($this -> payUrl, $orderInfo);
			if( is_array($arr) ){
				if( isset($arr['prepay_id']) ){
					return $this -> JSSDKOrderInfo(array("prepay_id" => $arr['prepay_id']));
				}else{
					return "可能订单号重复，请刷新页面后重试";
				}
			}else{
				return $arr;
			}
		}
		
		/*
			创建订单 方式2(通过 partner)
		*/
		public function PartnerCreate($openid, $body, $total_fee, $trade_no, $notify_url = 'notify'){
			$packageInfo = $this -> CreateOrder($openid, $body, $total_fee, $trade_no, $notify_url);
			$packageInfo['partner'] = $this -> mchId; //暂时无法获取：partner
			$packageInfo["sign"] = strtoupper($this -> SignatureArray($packageInfo, "md5", array("key" => $this -> mchKey)));
			return $this -> JSSDKOrderInfo($packageInfo);
		}
		
		/*
			检查支付结果
		*/
		public function PayResult(){
			$res = file_get_contents("php://input");

			$arr = $this -> ApiCallback(array("input" => "PayNotify"), $res);
			if( is_array($arr) ){
				return array(
					"trade_no"			=> $arr["out_trade_no"],
					"time"				=> $arr["time_end"],
					"total_fee"			=> $arr["total_fee"],
					"transaction_id"	=> $arr["transaction_id"],
					"openid"			=> $arr["openid"]
				);
			}else{
				return $arr;
			}
		}

		/*
			返回支付处理结果
		*/
		public function PayReturn($status, $errInfo){
			$arr = array(
				"return_code"	=> $status,
				"return_msg"	=> $errInfo
			);
			return $this -> ArrayToXML($arr);
		}

		/*
			给用户退款
		*/
		public function RefundOrder($trade_no, $refund_no, $total_fee, $refund_fee){
			$refund = array(
				"appid" => $this -> appId,
				"mch_id" => $this -> mchId,
				"nonce_str" => $this -> createNoncestr(),
				"out_trade_no" => $trade_no,
				"out_refund_no" => $refund_no,
				"total_fee" => $total_fee,
				"refund_fee" => $refund_fee,
				"op_user_id" => $this -> mchId
			);
			$refund["sign"] = strtoupper($this -> SignatureArray($refund, "md5", array("key" => $this -> mchKey)));
			$arr = $this -> ApiCallback($this -> refundUrl, $refund, true);
			if( is_array($arr) ){
				return array(
					'refund_fee' => $arr['refund_fee'],
					'refund_no' => $arr['out_refund_no'],
					'trade_no' => $arr['out_trade_no']
				);
			}else{
				return $arr;
			}
		}

		/*
			给用户付款
		*/
		public function TransferAccount($openid, $trans_no, $amount, $body = "提现"){
			$trans = array(
				"mch_appid" => $this -> appId,
				"mchid" => $this -> mchId,
				"nonce_str" => $this -> createNoncestr(),
				"partner_trade_no" => $trans_no,
				"openid" => $openid,
				"check_name" => "NO_CHECK",
				"amount" => $amount,
				"desc" => $body,
				"spbill_create_ip" => $_SERVER["SERVER_ADDR"],
			);
			$trans["sign"] = strtoupper($this -> SignatureArray($trans, "md5", array("key" => $this -> mchKey)));
			$arr = $this -> ApiCallback($this -> transfersUrl, $trans, true, "mchid");
			if( is_array($arr) ){
				return array(
					'payment_time' => $arr['payment_time'],
					'partner_trade_no' => $arr['partner_trade_no'],
					'payment_no' => $arr['payment_no']
				);
			}else{
				return $arr;
			}
		}

		/*
			输出可以供 JSSDK 使用的 数组
		*/
		private function JSSDKOrderInfo($packageInfo){
			$jsInfo = array(
				"appId"		=> $this -> appId,
				"timeStamp"	=> time(),
				"nonceStr"	=> $this -> CreateNonceStr(),
				"package"	=> $this -> BuilderQuery($packageInfo),
				"signType"	=> 'MD5'
			);
			$jsInfo["paySign"] = $this -> SignatureArray($jsInfo, 'md5', array("key" => $this -> mchKey));
			$jsInfo["timestamp"] = $jsInfo["timeStamp"];
			unset($jsInfo['appId']);
			return $jsInfo;
		}

		/*
			返回 证书路径
		*/
		private function GetCertPath($type = "cert", $file = "pem"){
			return $this -> varPath . "_" . $type . "_" . $this -> mchCert . "." . $file;
		}

		/*
			生成订单信息
		*/
		private function CreateOrder($openid, $body, $total_fee, $trade_no, $notify_url){
			return array(
				"body"				=> $body,
				"total_fee"			=> $total_fee * 100,
				"out_trade_no"		=> $trade_no,
				"notify_url"		=> $this -> GetCallbackUrl($notify_url, "/"),
				"spbill_create_ip"	=> $_SERVER['REMOTE_ADDR']
			);
		}
		
		/*
			接口返回数据，同意处理
		*/
		private function ApiCallback($url, $param, $cert = false, $mchIdName = 'mch_id'){
			$checkSign = false;
			if( is_array($url) ){
				$res = $param;
				$checkSign = true;
			}else{
				$res = $this -> HttpGet($url, $this -> ArrayToXML($param), $cert ? $this -> GetCertPath("cert") : '', $cert ? $this -> GetCertPath("key") : '');
			}
			if($res == ""){
				return "请检查证书是否安装正确";
			}
			$arr = $this -> XMLToArray($res);
			if( !isset($arr["return_code"]) || !isset($arr["result_code"]) ){
				return "系统异常，可能微信支付接口改版";
			}elseif ($arr["return_code"] == "FAIL"){
				return "系统出错(" . (isset($arr['return_msg'])? $arr['return_msg'] : '系统没有返回出错信息') . ")";
			}elseif ($arr["result_code"] == "FAIL"){
				return "系统出错(" . (isset($arr['return_msg']) ? $arr['return_msg'] : '系统没有返回出错信息') . (isset($arr['err_code_des'])? '->' . $arr['err_code_des'] : '') . ")";
			}elseif( !isset($arr[$mchIdName]) || $arr[$mchIdName] != $this -> mchId ){
				return "商户ID不匹配";
			}else{
				if( $checkSign ){
					$arr_sign = isset($arr['sign']) ? $arr['sign'] : '';
					unset($arr['sign']);
					$sign = strtoupper($this -> SignatureArray($arr, 'md5', array("key" => $this -> mchKey)));
					if( $arr_sign != $sign ){
						return "签名信息不匹配";
					}
				}
				return $arr;
			}
		}

		private function ArrayToXML($arr){
			$xml = "<xml>";
			foreach ($arr as $key => $val){
				$xml .= "<" . $key . ">";
				if( is_numeric($val) ){
					$xml .= $val;
				}else{
					$xml .= "<![CDATA[" . $val . "]]>";  
				}
				$xml .= "</" . $key . ">";
			}
			$xml .= "</xml>";
			return $xml; 
	    }
	    
	    private function XMLToArray($xml){
	        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
	    }
	}
?>
