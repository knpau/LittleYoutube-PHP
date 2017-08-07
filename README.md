<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>
[![Build Status](https://travis-ci.org/StefansArya/LittleYoutube-PHP.svg?branch=master)](https://travis-ci.org/StefansArya/LittleYoutube-PHP)
[![Latest Version](https://img.shields.io/badge/build-beta-yellow.svg)](https://packagist.org/packages/scarletsfiction/littleyoutube)
[![Software License](https://img.shields.io/badge/license-GPL2-brightgreen.svg)](LICENSE)

LittleYoutube
==========

LittleYoutube is a library for retrieving youtube data with PHP script

## Table of contents
[TOC]

## Getting Started
>  * Clone/download this repo
>  * Include `LittleYoutube.php` to your php script

### Download via composer

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

$LittleYoutube = new LittleYoutube();
$LittleYoutube->videoID("https://www.youtube.com/watch?v=xQomv1gqmb4");
echo("Video ID:".$LittleYoutube->info['videoID']."\n");
print_r($LittleYoutube->getVideoImages());
```

## Documentation
### Initialize LittleYoutube
> $LittleYoutube = new LittleYoutube(options);

Available options
```
[
    "temporaryDirectory"=>realpath(__DIR__."/temp"),
    "signatureDebug"=>false,
    "loadVideoMetadata"=>false
]
```

### Set youtube video URL
> $LittleYoutube->videoID("videoURLHere");

Return 
```
(string) videoID //LFRYghhS2I4
```

### Retrieve video media links
> $LittleYoutube->getVideoLink();

Return 
```
{
    "encoded"=>[
        [0] => {
            "itag",
            "type"=>[
                [0] => Media    //video
                [1] => Format   //mp4
                [2] => Encoder  //avc1.64001F, mp4a.40.2
            ],
            "expire",  //timestamp
            "quality", //hd720, medium, small
            "url"
        },
        ...
    ],
    "encoded"=>[
        [0] => {
            "itag",
            "type"=>[
                [0] => Media    //video
                [1] => Format   //mp4
                [2] => Encoder  //avc1.4d401f
            ],
            "expire",  //timestamp
            "quality", //1080p, 720p, 192k, 144k
            "url"
        },
        ...
    ]
}
```

### Get video image preview
> $LittleYoutube->getVideoImages();

Return array
```
[
    "HighQualityURL", "MediumQualityURL", "DefaultQualityURL"
]
```

### Get last error message
> $LittleYoutube->error;

Return 
```
(string) errorMsg //Failed to do stuff
```

### Get info
> $LittleYoutube->info;

Return keys
```
{
    "videoID", "playerID", "title", "duration", "viewCount"
}
```

### Change settings dynamically
You can also change the settings after initialize LittleYoutube
> $LittleYoutube->settings[options] = value;

## Contribution

If you want to help in LittleYoutube library, please make it even better and start a pull request into it.

Keep it simple and keep it clear.

## License

LittleYoutube is under the GPL license.