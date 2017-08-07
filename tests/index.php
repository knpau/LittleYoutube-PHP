<?php
	// If you installed via composer, just use this code to require autoloader on the top of your projects.
	//require_once __DIR__."/../vendor/autoload.php';
	require_once __DIR__."/../LittleYoutube.php";
	
	use ScarletsFiction\LittleYoutube;
	
	$Youtube = new LittleYoutube();
	$Youtube->videoID("https://www.youtube.com/watch?v=LFRYghhS2I4");
	$Youtube->getVideoLink();
	$Youtube->getVideoImages();
	//print_r($Youtube->info);

	if($Youtube->error) echo('Error: '.$Youtube->error);
	else echo("All script running");