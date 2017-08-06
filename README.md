<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>
[![Build Status](https://travis-ci.org/StefansArya/LittleYoutube-PHP.svg?branch=master)](https://travis-ci.org/StefansArya/LittleYoutube-PHP)
[![Software License](https://img.shields.io/badge/license-GPL2-brightgreen.svg)](LICENSE)

LittleYoutube
==========

LittleYoutube is a library for retrieving youtube data with PHP script

## Getting Started
  * Clone/download this repo
  * Include `LittleYoutube.php` to your php script

## Requirement

  * PHP 5.4+
  * FFmpeg (Needed for converting media files)

## Sample Usage
```
<?php
    require_once "LittleYoutube.php";

    $Youtube = new ScarletsFiction\LittleYoutube;
    echo($Youtube->getVideoIDFromURL("https://www.youtube.com/watch?v=xQomv1gqmb4"));
```

## Contribution

If you want to help in LittleYoutube library, please make it even better and start a pull request into it.

Keep it simple and keep it clear.

## License

LittleYoutube is under the GPL license.