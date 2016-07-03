<?php
	
	class JSSDK extends WXBase{
		
		public function GetSignPackage() {
			$jsapiTicket = $this -> GetJsApiTicket();
			
			$timestamp = time();
			$nonceStr = $this -> CreateNonceStr();
			
			$url = $this -> GetCurrentUrl();
			
			$signInfo = array(
				"jsapi_ticket"	=> $jsapiTicket,
				"noncestr"		=> $nonceStr,
				"timestamp"		=> $timestamp,
				"url"			=> $url
			);
			
			$signPackage = array(
				"appId"		=> $this -> appId,
				"nonceStr"	=> $nonceStr,
				"timestamp"	=> $timestamp,
				"url"		=> $url,
				"signature"	=> $this -> SignatureArray($signInfo)
			);
			return $signPackage; 
		}
	}
?>