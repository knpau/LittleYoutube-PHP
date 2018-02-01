<?php
	require_once __DIR__."/../src/LittleYoutube.php";
	$error = '';
	use \ScarletsFiction\LittleYoutube;

	if(isset($_REQUEST['video'])){
		$video = LittleYoutube::video($_REQUEST['video'], ["temporaryDirectory"=>realpath(__DIR__."/temp")]);
		//$error .= $video->error."\n";
		print_r(json_encode(["data"=>$video->data, "picture"=>$video->getImage(), "error"=>$error]));
	}

	if(isset($_REQUEST['channel'])){
		$channel = LittleYoutube::channel($_REQUEST['channel'], ["temporaryDirectory"=>realpath(__DIR__."/temp")]);
		//$error .= $channel->error."\n";
		print_r(json_encode(["data"=>$channel->data, "error"=>$error]));
	}

	if(isset($_REQUEST['playlist'])){
		$playlist = LittleYoutube::playlist($_REQUEST['playlist'], ["temporaryDirectory"=>realpath(__DIR__."/temp")]);
		//$error .= $playlist->error."\n";
		print_r(json_encode(["data"=>$playlist->data, "error"=>$error]));
	}

	if(isset($_REQUEST['search'])){
		if(isset($_REQUEST['page'])){
			$search = LittleYoutube::search(false, ["temporaryDirectory"=>realpath(__DIR__."/temp")]);
			$search->init($_REQUEST['search'], $_REQUEST['page']);
		}
		else 
			$search = LittleYoutube::search($_REQUEST['search'], ["temporaryDirectory"=>realpath(__DIR__."/temp")]);
		//$error .= $search->error."\n";
		print_r(json_encode(["data"=>$search->data, "error"=>$error]));
	}

	if(isset($_REQUEST['lyric'])){
		$lyric = LittleYoutube\video::parseSubtitleURL($_REQUEST['lyric'], 'srt');
		\ScarletsFiction\Stream::variableFile('lyric.srt', $lyric);
	}