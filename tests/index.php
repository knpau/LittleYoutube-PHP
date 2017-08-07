<?php
	// If you installed via composer, just use this code to require autoloader on the top of your projects.
	//require_once __DIR__."/../vendor/autoload.php';
	require_once __DIR__."/../LittleYoutube.php";
	
	use ScarletsFiction\LittleYoutube;
	
	$Youtube = new LittleYoutube();
	$Youtube->getVideoIDFromURL("https://www.youtube.com/watch?v=LFRYghhS2I4");
	$Youtube->getVideoLink();