<?php
	// If you installed via composer, just use this code to require autoloader on the top of your projects.
	//require_once 'vendor/autoload.php';
	require_once "LittleYoutube.php";
	
	use ScarletsFiction\LittleYoutube;
	
	$Youtube = new LittleYoutube();
	print_r($Youtube->getVideoLink("https://www.youtube.com/watch?v=LFRYghhS2I4"));