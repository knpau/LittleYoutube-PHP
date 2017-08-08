<?php
namespace ScarletsFiction{
	class LittleYoutube
	{
		public $version = "0.6.1";
		public $error = false;
		public $settings;

		public function __construct($options = null)
		{
			$this->settings = [
				"temporaryDirectory"=>realpath(__DIR__."/temp"),
				"signatureDebug"=>false,
				"loadVideoSize"=>false
			];
			$this->info = [];
			$this->data = [];
			if($options){
				$this->settings = array_replace($this->settings,$options);
			}
		}

		public function video($url, $processDetail=true)
		{
			$id = $url;
			if(strpos($id, '/watch?v=')!==false){
				$id = explode('/watch?v=', $id)[1];
				if(strpos($id, '#')!==false){
					$id = explode('#', $id)[0];
				}
				if(strpos($id, '&')!==false){
					$id = explode('&', $id)[0];
				}
			}
			else if(strpos($id, 'youtu.be/')!==false){
				$id = explode('youtu.be/', $id)[1];
				$id = explode('?', $id)[0];
			}
			$this->info['videoID'] = $id;
			return new \ScarletsFiction\LittleYoutube\Video($this->settings, $this->error, $id, $processDetail);
		}

		public function channel($url)
		{
			$id = $url;
			if(strpos($id, '/user/')!==false){
				$id = explode('/user/', $id)[1];
				$id = explode('/', $id)[0];
				$id = explode('?', $id)[0];
			}
			else if(strpos($id, 'youtu.be/')!==false){
				$id = explode('youtu.be/', $id)[1];
				$id = explode('?', $id)[0];
			}
			$this->info['channelID'] = $id;
			return new \ScarletsFiction\LittleYoutube\Channel($this->settings, $this->error, $id);
		}
	}
}

namespace ScarletsFiction\LittleYoutube{
	class Video
	{
		private $error;
		public $info;
		private $data;
		private $settings;

		public function __construct(&$settings, &$error, $id, $processDetail)
		{
			$this->info = ["videoID"=>$id];
			$this->data = [];
			$this->settings = $settings;
			$this->error = &$error;
			if($processDetail) $this->processDetails();
		}

		public function processDetails()
		{
			if(isset($this->info['videoID'])) $id = $this->info['videoID'];
			else{
				$this->error = "No videoID";
				return false;
			}

			$data = \ScarletsFiction\WebApi::loadURL('https://www.youtube.com/watch?spf=navigate&v='.$id)['content'];
			$this->parseVideoDetail($data);
			$data = explode('"swfcfg":', $data)[1];

			//Sometime error here, this is to avoid parse the whole json 
			$data = explode('}}},', $data);
			if(count($data)==2) $data = $data[0].'}';
			else $data = explode('}},"', $data[0])[0].'}}';

			$data_ = json_decode($data, true);
			if(json_last_error() !== JSON_ERROR_NONE){
				file_put_contents($this->settings['temporaryDirectory']."/error.json", $data);
				$this->error = 'JSON: '.json_last_error_msg();
				return false;
			}
			$data = $data_;
			unset($data_); unset($data['args']['fflags']);

			$this->getPlayerScript($data['assets']['js']);

			if(!isset($data['args']['title'])){
				$this->error = "Video not exist";
				return false;
			}
			$this->info['title'] = $data['args']['title'];
			$this->info['duration'] = $data['args']['length_seconds'];
			$this->info['viewCount'] = $data['args']['view_count'];
			$this->info['author'] = $data['args']['author'];
			$this->info['channelID'] = $data['args']['ucid'];

			$subtitle = json_decode($data['args']['player_response'], true);
			if(isset($subtitle['captions'])){
				$this->info['subtitle'] = $subtitle['captions']['playerCaptionsTracklistRenderer']['captionTracks'];
				foreach ($this->info['subtitle'] as &$value) {
					$value = ['url'=>$value['baseUrl'], 'lang'=>$value['languageCode']];
				}
			} else $this->info['subtitle'] = false;
			
			$streamMap = [[],[]];
			if(isset($data['args']['url_encoded_fmt_stream_map'])){
				$streamMap[0] = explode(',', $data['args']['url_encoded_fmt_stream_map']);
				if(count($streamMap[0])) $streamMap[0] =  $this->streamMapToArray($streamMap[0]);
			}
			if(isset($data['args']['adaptive_fmts'])){
				$streamMap[1] = explode(',', $data['args']['adaptive_fmts']);
				if(count($streamMap[1])) $streamMap[1] =  $this->streamMapToArray($streamMap[1]);
			}

			$this->info['video'] = ["encoded"=>$streamMap[0], "adaptive"=>$streamMap[1]];
		}

		private function parseVideoDetail($data){
			$panelDetails = explode('action-panel-details', $data)[1];
			$panelDetails = explode('"button', $panelDetails)[0];

			$uploaded = explode('\u003c\/strong', $panelDetails)[0];
			$uploaded = explode('Published on ', $uploaded)[1];
			$this->info['uploaded'] = $uploaded;

			$description = explode('\u003c\/p', $panelDetails)[0].'\u003c\/p\u003e';
			$description = '["\u003cp'.explode('\u003cp', $description)[1].'\\\u003e"]';
			$description = json_decode($description, true)[0];
			$description = str_replace(['<br />', '<br/>', '<br>'], "\n", $description);
			$description = strip_tags($description);
			$this->info['description'] = str_replace('\u003e', '', $description);
			
			$metatag = '["\u003c \"'.explode('watch-extras-section', $panelDetails)[1];
			$metatag = explode('yt-uix-expander-head', $metatag)[0].'\u003e"]';
			$metatag = json_decode(str_replace('  ', '', $metatag), true)[0];
			$metatag = explode("<h4 class=\"title\">\n", $metatag);
			$metatag = array_values($metatag);
			unset($metatag[0]);
			foreach ($metatag as &$value) {
				$value = str_replace("\n\n\n", ': ', trim(strip_tags($value)));
			}
			$this->info['metatag'] = $metatag;
			
			$likeDetails = explode('"like-button-renderer', $data)[1];
			$likeDetails = explode('dislike-button-clicked yt-uix-button-toggled', $likeDetails)[0];
			$likeDetails = explode('like-button-unclicked', $likeDetails);
			unset($likeDetails[0]);
			foreach ($likeDetails as &$value) {
				$value = explode('button-content\"\u003e', $value)[1];
				$value = explode('\u003c\/span', $value)[0];
			}
			
			$this->info['like'] = str_replace('.', '', $likeDetails[1]);
			$this->info['dislike'] = str_replace('.', '', $likeDetails[2]);
		}

		private function streamMapToArray($streamMap)
		{
			foreach($streamMap as &$map)
			{
				parse_str($map, $map_info);
				parse_str(urldecode($map_info['url']), $url_info);

				$map = [];
				$map['itag'] = $map_info['itag'];
				$map['type'] = explode(';', $map_info['type']);
				$format = explode('/', $map['type'][0]);
				$encoder = explode('"', $map['type'][1])[1];
				$map['type'] = array_merge($format, [$encoder]);
				$map['expire'] = isset($url_info['expire'])?$url_info['expire']:0;

				if(isset($map_info['bitrate']))
					$map['quality'] = isset($map_info['quality_label'])?$map_info['quality_label']:round($map_info['bitrate']/1000).'k';
				else
					$map['quality'] = isset($map_info['quality'])?$map_info['quality']:'';
		
				$signature = '';

				// The video signature need to be deciphered
				if(isset($map_info['s']))
				{
					if(!isset($this->info['playerID']))
						$this->info['playerID'] = $this->getPlayerScript($data['videoID']);
					if(strpos($map_info['url'], 'ratebypass=')===false)
						$map_info['url'] .= '&ratebypass=yes';
	  				$signature = '&signature='.$this->decipherSignature($map_info['s']);
				}
		
				$map['url'] = $map_info['url'].$signature.'&title='.urlencode($this->info['title']);
			}
			return $streamMap;
		}

		public function getEmbedLink(){
			if(!isset($this->info['videoID'])){
				$this->error = "videoID was not found";
				return false;
			}
			return "//www.youtube.com/embed/".$this->info['videoID']."?rel=0";
		}

		public function parseSubtitle($idOrXML = false){
			if(is_string($idOrXML)){
				$data = $idOrXML;
			} else{
				if(!isset($this->info['subtitle'][$idOrXML])){
					$this->error = "No subtitle found";
					return false;
				}
				$data = \ScarletsFiction\WebApi::loadURL($this->info['subtitle'][$idOrXML]['url']);
			}
			if(!$data) return false;

			$data = str_replace(['</transcript>', '</text>'], '', $data);
			$data = explode('<text ', $data);
			unset($data[0]); $data = array_values($data);

			foreach ($data as &$value) {
				$value = explode('>', $value);
				$value = ["when"=>$value[0], "text"=>strip_tags(html_entity_decode($value[1]))];
				$value['time'] = explode('"', explode('start="', $value['when'])[1])[0];
				$value['duration'] = explode('"', explode('dur="', $value['when'])[1])[0];
				unset($value['when']);
			}
			return $data;
		}
		
		public function getImage()
		{
			$id = $this->info['videoID'];
			return [
			//High Quality Thumbnail (480x360px)
				"http://i1.ytimg.com/vi/$id/hqdefault.jpg",
			//Medium Quality Thumbnail (320x180px)
				"http://i1.ytimg.com/vi/$id/mqdefault.jpg",
			//Normal Quality Thumbnail (120x90px)
				"http://i1.ytimg.com/vi/$id/default.jpg"
			];
		}

		private function getPlayerScript($playerURL){
			try{
				$playerID = explode("/yts/jsbin/player", $playerURL)[1];
				$playerID = explode("-", explode("/", $playerID)[0]);
				$playerID = $playerID[count($playerID)-1];
			} catch(\Exception $e){
				$this->error = "Failed to parse playerID from player url: ".$playerURL;
				return false;
			}
			$playerURL = str_replace('\/', '/', explode('"', $playerID)[0]);
			$playerID = explode('/', $playerURL)[0];
		
			if(!file_exists($this->settings['temporaryDirectory']."/$playerID")) {
				$decipherScript = \ScarletsFiction\WebApi::loadURL("https://youtube.com/yts/jsbin/player-$playerURL");
				file_put_contents($this->settings['temporaryDirectory']."/$playerID", $decipherScript);
			}

			$this->info['playerID'] = $playerID;
			return $playerID;
		}

		private function getSignatureParser(){
			$this->info['signature'] = ['playerID'=>$this->info['playerID']];
			if($this->settings['signatureDebug']){
				$this->info['signature']['log'] = "==== Load player script and execute patterns ====\n\n";
				$this->info['signature']['log'] .= "Loading player ID = ".$this->info['playerID']."\n";
			}
			
			if(!$this->info['playerID']) return false;

			if(file_exists($this->settings['temporaryDirectory'].'/'.$this->info['playerID'])) {
				$decipherScript = file_get_contents($this->settings['temporaryDirectory'].'/'.$this->info['playerID']);
			} else{
				$this->error = "Player script was not found for id: ".$this->info['playerID'];
				if($this->settings['signatureDebug'])
					
				return false;
			}
		
			// Some preparation
			$signatureCall = explode('("signature",', $decipherScript);
			$callCount = count($signatureCall);
		
			// Search for function call for example: e.set("signature",PE(f.s));
			// We need to get "PE"
			$signatureFunction = "";
			for ($i=$callCount-1; $i > 0; $i--){
				$signatureCall[$i] = explode(');', $signatureCall[$i])[0];
				if(strpos($signatureCall[$i], '(')){
					$signatureFunction = explode('(', $signatureCall[$i])[0];
					break;
				}
				else if($i==0){
					$this->error = "Failed to get signature function";
					return false;
				}
			}
			
			if($this->settings['signatureDebug'])
				$this->info['signature']['log'] .= 'signatureFunction = '.$signatureFunction."\n";

			$decipherPatterns = explode($signatureFunction."=function(", $decipherScript)[1];
			$decipherPatterns = explode('};', $decipherPatterns)[0];
			
			if($this->settings['signatureDebug'])
				$this->info['signature']['log'] .= 'decipherPatterns = '.$decipherPatterns."\n";
		
			$deciphers = explode("(a", $decipherPatterns);
			for ($i=0; $i < count($deciphers); $i++) { 
				$deciphers[$i] = explode('.', explode(';', $deciphers[$i])[1])[0];
				if(count(explode($deciphers[$i], $decipherPatterns))>=2){
					// This object was most called, that's mean this is the deciphers
					$deciphers = $deciphers[$i];
					break;
				}
				else if($i==count($deciphers)-1){
					$this->error = "Failed to get deciphers function";
					return false;
				}
			}
		
			$deciphersObjectVar = $deciphers;
			$decipher = explode($deciphers.'={', $decipherScript)[1];
			$decipher = str_replace(["\n", "\r"], "", $decipher);
			$decipher = explode('}};', $decipher)[0];
			$decipher = explode("},", $decipher);
			if($this->settings['signatureDebug'])
				$this->info['signature']['log'] .= print_r($decipher, true);
		
			// Convert pattern to array
			$decipherPatterns = str_replace($deciphersObjectVar.'.', '', $decipherPatterns);
			$decipherPatterns = str_replace('(a,', '->(', $decipherPatterns);
			$decipherPatterns = explode(';', explode('){', $decipherPatterns)[1]);
			$this->data['signature']['patterns'] = $decipherPatterns;
		
			// Convert deciphers to object
			$deciphers = [];
			foreach ($decipher as &$function) {
				$deciphers[explode(':function', $function)[0]] = explode('){', $function)[1];
			}
			$this->data['signature']['deciphers'] = $deciphers;

			return true;
		}
		
		private function decipherSignature($signature){
			if(isset($this->info['signature']['playerID'])&&$this->info['signature']['playerID']==$this->info['playerID']){
				if($this->settings['signatureDebug'])
					$this->info['signature']['log'] = "==== Deciphers loaded ====\n";
			}
			else $this->getSignatureParser();

			if(!isset($this->data['signature']['patterns'])){
				$this->error = "Signature patterns not found";
				return false;
			}
			$patterns = $this->data['signature']['patterns'];
			$deciphers = $this->data['signature']['deciphers'];

			if($this->settings['signatureDebug']){
				$this->info['signature']['log'] = "==== Retrieved deciphers ====\n\n";
				$this->info['signature']['log'] .= print_r($patterns, true);
				$this->info['signature']['log'] .= print_r($deciphers, true);
			}
		
			if($this->settings['signatureDebug'])
				$this->info['signature']['log'] .= "\n\n\n==== Processing ====\n\n";
		
			// Execute every $patterns with $deciphers dictionary
			$processSignature = $signature;
			for ($i=0; $i < count($patterns); $i++) {
				// This is the deciphers dictionary, and should be updated if there are different pattern
				// as PHP can't execute javascript
		
				//Handle non deciphers pattern
				if(strpos($patterns[$i], '->')===false){
					if(strpos($patterns[$i], '.split("")')!==false)
					{
						$processSignature = str_split($processSignature);
						if($this->settings['signatureDebug'])
							$this->info['signature']['log'] .= "String splitted\n";
					}
					else if(strpos($patterns[$i], '.join("")')!==false)
					{
						$processSignature = implode('', $processSignature);
						if($this->settings['signatureDebug'])
							$this->info['signature']['log'] .= "String combined\n";
					}
					else{
						$this->error = "Decipher dictionary was not found #1";
						return false;
					}
				} 
				else
				{
					//Separate commands
					$executes = explode('->', $patterns[$i]);
		
					// This is parameter b value for 'function(a,b){}'
					$number = intval(str_replace(['(', ')'], '', $executes[1]));
					// Parameter a = $processSignature
		
					$execute = $deciphers[$executes[0]];
		
					//Find matched command dictionary
					if($this->settings['signatureDebug'])
						$this->info['signature']['log'] .= "Executing $executes[0] -> $number";
					switch($execute){
						case "a.reverse()":
							$processSignature = array_reverse($processSignature);
							if($this->settings['signatureDebug'])
								$this->info['signature']['log'] .= " (Reversing array)\n";
						break;
						case "var c=a[0];a[0]=a[b%a.length];a[b]=c":
							$c = $processSignature[0];
							$processSignature[0] = $processSignature[$number%count($processSignature)];
							$processSignature[$number] = $c;
							if($this->settings['signatureDebug'])
								$this->info['signature']['log'] .= " (Swapping array)\n";
						break;
						case "a.splice(0,b)":
							$processSignature = array_slice($processSignature, $number);
							if($this->settings['signatureDebug'])
								$this->info['signature']['log'] .= " (Removing array)\n";
						break;
						default:
							$this->error = "Decipher dictionary was not found #2";
							return false;
						break;
					}
				}
			}
		
			if($this->settings['signatureDebug']){
				$this->info['signature']['log'] .= "\n\n\n==== Result ====\n";
				$this->info['signature']['log'] .= "Signature  : ".$signature."\n";
				$this->info['signature']['log'] .= "Deciphered : ".$processSignature;
			}

			return $processSignature;
		}
	}

	class Channel
	{
		private $error;
		public $info;
		private $data;
		private $settings;

		public function __construct(&$settings, &$error, $id)
		{
			$this->info = ["channelID"=>$id];
			$this->data = [];
			$this->settings = $settings;
			$this->error = &$error;
			if($this->settings['autoProcessVideoDetails'])
				$this->getVideoLink();
		}

		public function getChannelRSS($load=false, $parse=false){
			$link = "https://www.youtube.com/feeds/videos.xml?channel_id=".$this->info['channelID'];
			if(!$load) return $link;
			else{
				$data = \ScarletsFiction\WebApi::loadURL($link);
				if($parse){
					//ToDo
				} else return $data;
			}
		}
	}
	class Playlist
	{
		
	}
}

namespace ScarletsFiction{
	class WebApi
	{
		public static function loadURL($url, $options=false){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36');
		
			$headers = [];
			$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/apng,*/*;q=0.8';
			$headers[] = 'Accept-Language: en-US,en;q=0.5';
			$headers[] = 'Connection: keep-alive';

			if($options){
				if(isset($options['headerOnly'])){
					curl_setopt($ch, CURLOPT_NOBODY, true);
				}
				if(isset($options['headers'])){
					$headers = $options['headers'];
				}
				if(isset($options['cookies'])){
					curl_setopt($ch, CURLOPT_COOKIE, $options['cookies']);
				}
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
			$data = curl_exec($ch);
			
			$header  = curl_getinfo($ch);
			$myHeader = $header['request_header'];
			curl_close( $ch );
		
			$header_content = substr($data, 0, $header['header_size']);
			$body_content = trim(str_replace($header_content, '', $data));
			$pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m"; 
			preg_match_all($pattern, $header_content, $matches); 
			$cookies = implode("; ", $matches['cookie']);
			
			$data = ['headers'=>$header_content, 'content'=>$body_content, 'cookies'=>$cookies];
			return $data;
		}
		public static function urlContentSize($url){
			$dat = loadURL($url, ['headerOnly'=>true]);
			if($dat['headers']) {
				$size = 0;
				if(preg_match("/Content-Length: (\d+)/", $data, $matches)){
		    	  $size = (int)$matches[1];
		    	}
				return $size;
		    }
		}
	}

	class FileApi{
		public static function fileSize($bytes, $decimals=2) {
		  	$sz = 'BKMGTP';
		  	$factor = floor((strlen($bytes) - 1) / 3);
		  	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
		}
	}
}