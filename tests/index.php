<?php
	// If you installed via composer, just use this code to require autoloader on the top of your projects.
	//require_once __DIR__."/../vendor/autoload.php";
	require_once __DIR__."/../LittleYoutube.php";

	use \ScarletsFiction\LittleYoutube;

	$error = '';
	$video = LittleYoutube::video("https://www.youtube.com/watch?v=R1RonAlzvZk");
	$error .= $video->error."\n";
	//$video->getImage();
	print_r($video->data);

	//$channel = LittleYoutube::channel("https://www.youtube.com/user/yifartofmagic/");
	//$error .= $channel->error."\n";
	//print_r($channel->data);

	//$playlist = LittleYoutube::playlist("https://www.youtube.com/watch?v=692TvKPDaEU&list=UUa-iuHGLTxvkOChd4jnybiA");
	//$error .= $playlist->error."\n";
	//print_r($playlist->data);

	//$search = LittleYoutube::search("nice movies");
	//$search->next();
	//$search->previous();
	//$error .= $search->error."\n";
	//print_r($search->data);

	if(str_replace("\n", '', $error)) echo('Error: '.$error);
	else echo("All test script running");