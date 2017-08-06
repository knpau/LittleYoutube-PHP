<?php
namespace ScarletsFiction;

class LittleYoutube
{
	public $settings;
	public function __construct($options = null)
	{
		if($options){
			$settings = (object) array_merge((array) $settings, (array) $options);
		}
	}
	public function getVideoIDFromURL($url)
	{
		$id = "";
		if(strpos($url, '/watch?v=')!==false){
			$id = explode('/watch?v=', $url)[1];
			if(strpos($id, '#')!==false){
				$id = explode('#', $id)[0];
			}
			if(strpos($id, '&')!==false){
				$id = explode('&', $id)[0];
			}
		}
		return $id;
	}

	public function getVideoLink($id)
	{
		global $loadURLOptions;
		$raw = loadURL('http://www.youtube.com/get_video_info?&video_id='.$id.'	&asv=3&hl=en_US&el=embedded&ps=default&eurl=&gl=US&', isset($loadURLOptions)?$loadURLOptions:['ssl'=>0]);
		parse_str($raw, $data);print_r($loadURLOptions);
		if(isset($data['reason'])&&$data['reason']!='') die($data['reason']);
		$streamMap = [[],[]];
		if(isset($data['url_encoded_fmt_stream_map']))
			$streamMap[0] = explode(',', $data['url_encoded_fmt_stream_map']);
		if(isset($data['adaptive_fmts']))
			$streamMap[1] = explode(',', $data['adaptive_fmts']);
		return [parseStreamMapToFormats($streamMap[0]), parseStreamMapToFormats($streamMap[1])];
	}

	private function loadURL($url)
	{
		$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$dat = curl_exec($ch);
		curl_close($ch);
		return $dat;
	}
	
	public function getVideoImages($id, $quality=1)
	{
		if($quality==1){//High Quality Thumbnail (480x360px)
			return "http://i1.ytimg.com/vi/$id/hqdefault.jpg";
		}
		if($quality==2){//Medium Quality Thumbnail (320x180px)
			return "http://i1.ytimg.com/vi/$id/mqdefault.jpg";
		}
		if($quality==3){//Normal Quality Thumbnail (120x90px)
			return "http://i1.ytimg.com/vi/$id/default.jpg";
		}
	}
}