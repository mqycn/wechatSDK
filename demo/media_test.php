<?php
	require("common.php");
	$sdk = LoadSDK("WXSDK");
	
	
	//下载媒体
	$sdk -> DownloadMedia($mediaId , '.amr', 'data/audio');
	
	
	//上传媒体
	$upload = $sdk -> UploadMedia("data/audio/test.amr", "voice");
	$mediaId = $upload['media_id'];
?>