<?php
	
	class WXSDK extends WXBase{
		
		/*
			下载媒体文件
		*/
		public function DownloadMedia($mediaId, $mediaType, $savePath = '/data') {
			$fileName = $savePath . '/' . md5($mediaId) . $mediaType;
			file_put_contents($fileName, $this -> HttpGet( $this -> ApiUrl("media/get", array("media_id" => $mediaId)) ) );
			return array("file" => $fileName, "size" => filesize($fileName), "mediaId" => $mediaId, "mediaHash" => md5($mediaId));
		}
		
		/*
			上传媒体文件
		*/
		public function UploadMedia($fileName, $MediaType) {
			return $this -> Api("media/upload", array("media" => "@" . $fileName), array("type" => $MediaType ));
		}

	}
?>