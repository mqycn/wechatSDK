<?php
	class WXOAuth extends WXBase{
		
		/*
			获取 OAuth 登录地址
		*/
		public function GetLoginUrl( $REDIRECT_URI, $STATE, $SCOPE = 'snsapi_base'){
			return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appId . "&redirect_uri=" . urlencode($REDIRECT_URI) . "&response_type=code&scope=$SCOPE&state=$STATE#wechat_redirect";
		}
		
		/*
			获取用户 AccessToken
			
			{
				"access_token":"ACCESS_TOKEN",
				"expires_in":7200,
				"refresh_token":"REFRESH_TOKEN",
				"openid":"OPENID",
				"scope":"SCOPE"
			} 
		*/
		public function GetUserAccessToken($CODE){
			$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->appId . "&secret=" . $this->appSecret . "&code=" . $CODE . "&grant_type=authorization_code";
			return json_decode($this -> HttpGet($url), true);
		}
		
		/*
			获取用户信息
			
			{
				"openid":" OPENID",
				"nickname": NICKNAME,
				"sex":"1",
				"province":"PROVINCE",
				"city":"CITY",
				"country":"COUNTRY",
				"headimgurl":"image.png",
				"privilege":[ "PRIVILEGE1" "PRIVILEGE2"],
				"unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
			}
		*/
		public function GetUserInfo($ACCESS_TOKEN, $OPENID){
			$url = "https://api.weixin.qq.com/sns/userinfo?access_token=$ACCESS_TOKEN&openid=$OPENID&lang=zh_CN";
			return json_decode($this -> HttpGet($url), true);
		}
	}
?>
