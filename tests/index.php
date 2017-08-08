<?php
	// If you installed via composer, just use this code to require autoloader on the top of your projects.
	//require_once __DIR__."/../vendor/autoload.php';
	require_once __DIR__."/../LittleYoutube.php";
	use ScarletsFiction\LittleYoutube;

	$LittleYoutube = new LittleYoutube();
	$LittleYoutube->settings['loadVideoSize'] = true;

	$video = $LittleYoutube->video("https://www.youtube.com/watch?v=R1RonAlzvZk");
	//$video->getImage();
	//print_r($video->parseSubtitle(0, true));

	$channel = $LittleYoutube->channel("https://www.youtube.com/user/yifartofmagic/");
	//print_r($channel->data);

	$playlist = $LittleYoutube->playlist("https://www.youtube.com/watch?v=692TvKPDaEU&list=UUa-iuHGLTxvkOChd4jnybiA");
	//print_r($playlist->data);

	if($LittleYoutube->error) echo('Error: '.$LittleYoutube->error);
	else echo("All test script running");