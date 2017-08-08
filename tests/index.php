<?php
	// If you installed via composer, just use this code to require autoloader on the top of your projects.
	//require_once __DIR__."/../vendor/autoload.php';
	require_once __DIR__."/../LittleYoutube.php";
	use ScarletsFiction\LittleYoutube;

	$LittleYoutube = new LittleYoutube();
	$LittleYoutube->settings['autoProcessVideoDetails'] = true;

	$video = $LittleYoutube->video("https://www.youtube.com/watch?v=R1RonAlzvZk");
	//$video->getImage();
	//print_r($video->data);

	$channel = $LittleYoutube->channel("https://www.youtube.com/user/yifartofmagic/");
	//print_r($channel->data);

	//$playlise = $LittleYoutube->playlist("https://www.youtube.com/user/yifartofmagic/");
	//print_r($channel->data);

	if($LittleYoutube->error) echo('Error: '.$LittleYoutube->error);
	else echo("All test script running");