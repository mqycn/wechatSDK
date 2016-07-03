<?php
	return array(
		'AppID'				=> 'AppID',
		'AppSecret'			=> 'AppSecret',
		'Token'				=> 'Token',
		'EncodingAESKey'	=> 'EncodingAESKey',
		'VarPath'			=> 'data/weixin',	//调用的文件目录
		'MchID'				=> '111111',	//商户号(如果不使用微信支付，不需要)
		'MchKey'			=> '2222222',	//商户支付秘钥KEY(如果不使用微信支付，不需要)
		'MchCert'			=> 'test'	//证书文件名(如果不使用微信支付，不需要)需要放到 VarPath 目录下，最终文件路径为 {VarPath}_(cert|key)_{MchCert}.pem
	);
?>