<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>
[![Build Status](https://travis-ci.org/StefansArya/LittleYoutube-PHP.svg?branch=master)](https://travis-ci.org/StefansArya/LittleYoutube-PHP)
[![Latest Version](https://img.shields.io/badge/build-beta-yellow.svg)](https://packagist.org/packages/scarletsfiction/littleyoutube)
[![Software License](https://img.shields.io/badge/license-GPL2-brightgreen.svg)](LICENSE)

LittleYoutube
==========

LittleYoutube is a library for retrieving youtube data with PHP script

## Getting Started
  * Clone/download this repo
  * Include `LittleYoutube.php` to your php script

### Or download via composer

Add LittleYoutube to composer.json configuration file.
```
$ composer require scarletsfiction/littleyoutube
```

And update the composer
```
$ composer update
```

## Sample Usage
```php
<?php
// If you installed via composer, just use this code to require autoloader on the top of your projects.
//require 'vendor/autoload.php';
require_once "LittleYoutube.php";

use ScarletsFiction\LittleYoutube;

$Youtube = new LittleYoutube();
echo($Youtube->getVideoIDFromURL("https://www.youtube.com/watch?v=xQomv1gqmb4"));
```

## Contribution

If you want to help in LittleYoutube library, please make it even better and start a pull request into it.

Keep it simple and keep it clear.

## License

LittleYoutube is under the GPL license.