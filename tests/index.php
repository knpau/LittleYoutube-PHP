<?php
// If you installed via composer, just use this code to require autoloader on the top of your projects.
require_once __DIR__."/../vendor/autoload.php";

use \LittleYoutube\LittleYoutube;

$video = LittleYoutube::video("https://www.youtube.com/watch?v=R1RonAlzvZk", ["temporaryDirectory"=>realpath(__DIR__."/../example/temp")]);
$video->getImage();

$channel = LittleYoutube::channel("https://www.youtube.com/user/yifartofmagic/");

$playlist = LittleYoutube::playlist("https://www.youtube.com/watch?v=692TvKPDaEU&list=UUa-iuHGLTxvkOChd4jnybiA");

$search = LittleYoutube::search("nice movies");

echo("All test script running");