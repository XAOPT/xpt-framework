<?php
/**
 * upload.php
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

require_once("../../../../include/inc_config.php");
require_once(ROOT_PATH."/include/inc_libs.php");

// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Settings

@set_time_limit(5 * 60);

// Get parameters
$chunk    = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
$chunks   = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

// Clean the fileName for security reasons
$img_name_arr = explode(".", $fileName);
$img_type     = end($img_name_arr);

$fileName = TIME.rand(100, 999).'.'.$img_type;

// Look for the content type header
if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
	$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

if (isset($_SERVER["CONTENT_TYPE"]))
	$contentType = $_SERVER["CONTENT_TYPE"];

// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
if (strpos($contentType, "multipart") !== false) {

	$id = zReq::getVar( 'id', 'INT');

	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']))
	{
		if(!is_dir(ROOT_PATH."/uploads/news/thumb/".$id))
			mkdir(ROOT_PATH."/uploads/news/thumb/".$id, 0700);
		if(!is_dir(ROOT_PATH."/uploads/news/full/".$id))
			mkdir(ROOT_PATH."/uploads/news/full/".$id, 0700);

		$image = ClassUpload::Image($_FILES['file'], 225, 225, 'news/thumb/'.$id, 'strict', $fileName);
		ClassUpload::Image($_FILES['file'], 800, 600, 'news/full/'.$id, 'anyone_lower', $image);

		global $sql;

		$sql->SetQuery("INSERT INTO `news_images` (id, image) VALUES ('{$id}','{$image}')");

		@unlink($_FILES['file']['tmp_name']);

		die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
	}
}

?>