<?php
	//require_once __DIR__."/../vendor/autoload.php";
	require_once __DIR__."/../LittleYoutube.php";
	use \ScarletsFiction\LittleYoutube;
	$error = '';

	if(isset($_REQUEST['video'])){
		$video = LittleYoutube::video($_REQUEST['video']);
		$error .= $video->error."\n";
		print_r(json_encode(["data"=>$video->data, "picture"=>$video->getImage(), "error"=>$error]));
	}

	if(isset($_REQUEST['channel'])){
		$channel = LittleYoutube::channel($_REQUEST['channel']);
		$error .= $channel->error."\n";
		print_r(json_encode(["data"=>$channel->data, "error"=>$error]));
	}

	if(isset($_REQUEST['playlist'])){
		$playlist = LittleYoutube::playlist($_REQUEST['playlist']);
		$error .= $playlist->error."\n";
		print_r(json_encode(["data"=>$playlist->data, "error"=>$error]));
	}

	if(isset($_REQUEST['search'])){
		$search = LittleYoutube::search($_REQUEST['search']);
		$error .= $search->error."\n";
		print_r(json_encode(["data"=>$search->data, "error"=>$error]));
	}