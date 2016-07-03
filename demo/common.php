<?php
	function loadSDK($sdkName){
		$wx = require('config.php');
		require_once('../weixin/WXBase.class.php');
		require_once("../weixin/$sdkName.class.php");
		if( $sdkName == "WXPay" ){	//微信支付需要传入 商户号
			return new $sdkName($wx['AppID'], $wx['AppSecret'], $wx['VarPath'], $wx['MchID'], $wx['MchKey'], $wx['MchCert']);
		}else{
			return new $sdkName($wx['AppID'], $wx['AppSecret'], $wx['VarPath']);
		}
	}
?>