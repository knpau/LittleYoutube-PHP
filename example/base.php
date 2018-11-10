<?php
require_once __DIR__."/../src/LittleYoutube.php";
use \LittleYoutube\LittleYoutube;

# Declare some variable
$error = '';
$haveError = "Please check error on the /example/error.log";
$options = [
	'temporaryDirectory'=>realpath(__DIR__."/temp"),
	'signatureDebug'=>true,
	'processVideoFrom'=>'VideoPage',
	'onError'=>'throw'
];
$handler = null;

# Catch any error
try{

	# Get video data
	if(isset($_REQUEST['video'])){
		$handler = LittleYoutube::video($_REQUEST['video'], $options);

		die(json_encode(["data"=>$handler->data, "picture"=>$handler->getImage()]));
	}

	# Get channel data
	if(isset($_REQUEST['channel'])){
		$handler = LittleYoutube::channel($_REQUEST['channel'], $options);

		die(json_encode(["data"=>$handler->data]));
	}

	# Get playlist data
	if(isset($_REQUEST['playlist'])){
		$handler = LittleYoutube::playlist($_REQUEST['playlist'], $options);

		die(json_encode(["data"=>$handler->data]));
	}

	# Search video
	if(isset($_REQUEST['search'])){
		if(isset($_REQUEST['page'])){
			$handler = LittleYoutube::search(false, $options);
			$handler->init($_REQUEST['search'], $_REQUEST['page']);
		}
		else 
			$handler = LittleYoutube::search($_REQUEST['search'], $options);

		die(json_encode(["data"=>$handler->data]));
	}

	# Get lyrics only
	if(isset($_REQUEST['lyric'])){
		$lyric = LittleYoutube::video()->parseSubtitleURL($_REQUEST['lyric'], 'srt');
		\ScarletsFiction\Stream::variableFile('lyric.srt', $lyric);
	}

} catch(\Exception $e) {
	file_put_contents('error.log', $e->getMessage()."\n");
	file_put_contents('error.log', str_replace(realpath("./.."), '', $e->getTraceAsString())."\n", FILE_APPEND);
	echo("Please check /example/error.log");
}