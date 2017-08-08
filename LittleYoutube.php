<?php
namespace ScarletsFiction{
	class LittleYoutube
	{
		public $error = false;
		public $settings;

		public function __construct($options = null)
		{
			$this->settings = [
				"temporaryDirectory"=>realpath(__DIR__."/temp"),
				"signatureDebug"=>false,
				"loadVideoSize"=>false
			];
			if($options){
				$this->settings = array_replace($this->settings, $options);
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
			return new \ScarletsFiction\LittleYoutube\Video($this->settings, $this->error, $id, $processDetail);
		}

		public function channel($url, $processDetail=true)
		{
			$id = $url;
			if(strpos($id, '/user/')!==false){
				$id = explode('/user/', $id)[1];
				$id = explode('/', $id)[0];
				$id = explode('?', $id)[0];
				$id = ['userID', explode('?', $id)[0]];
			}
			else if(strpos($id, 'channel_id=')!==false){
				$id = explode('channel_id=', $id)[1];
				$id = ['channelID', explode('&', $id)[0]];
			}
			else $id = ['channelID', $id];
			return new \ScarletsFiction\LittleYoutube\Channel($this->settings, $this->error, $id, $processDetail);
		}

		public function playlist($url, $processDetail=true)
		{
			$id = $url;
			if(strpos($id, 'list=')!==false){
				$id = explode('list=', $id)[1];
				$id = ['userID', explode('&', $id)[0]];
			}
			else $id = ['playlistID', $id];
			return new \ScarletsFiction\LittleYoutube\Playlist($this->settings, $this->error, $id, $processDetail);
		}
	}
}

namespace ScarletsFiction\LittleYoutube{
	class LittleYoutubeInfo{
		private $error;
		public $data;
		private $privateData;
		protected $settings;

		public function __construct(&$settings, &$error, $id, $processDetail=true)
		{
			$this->setID($id);
			$this->privateData = [];
			$this->settings = &$settings;
			$this->error = &$error;
			if($processDetail)
				$this->processDetails();
		}
	}

	class Video extends LittleYoutubeInfo
	{
		public function processDetails()
		{
			if(isset($this->data['videoID'])) $id = $this->data['videoID'];
			else{
				$this->error = "No videoID";
				return false;
			}

			$data = \ScarletsFiction\WebApi::loadURL('https://www.youtube.com/watch?v='.$id)['content'];
			$data = explode('ytplayer.config = ', $data)[1];
			$data = explode(';ytplayer.load', $data);
			$this->parseVideoDetail($data[1]);
			$data = $data[0];
			$data = json_decode($data, true);
			unset($data['args']['fflags']);

			$this->getPlayerScript($data['assets']['js']);
			if(!isset($data['args']['title'])){
				$this->error = "Video not exist";
				return false;
			}
			$this->data['title'] = $data['args']['title'];
			$this->data['duration'] = $data['args']['length_seconds'];
			$this->data['viewCount'] = $data['args']['view_count'];
			$this->data['channelID'] = $data['args']['ucid'];

			$subtitle = json_decode($data['args']['player_response'], true);
			if(isset($subtitle['captions'])){
				$this->data['subtitle'] = $subtitle['captions']['playerCaptionsTracklistRenderer']['captionTracks'];
				foreach ($this->data['subtitle'] as &$value) {
					$value = ['url'=>$value['baseUrl'], 'lang'=>$value['languageCode']];
				}
			} else $this->data['subtitle'] = false;
			if(isset($data['args']['livestream'])&&$data['args']['livestream']){
				$this->data['video'] = ["stream"=>$data['args']['hlsvp']];
				$this->data['uploaded'] = "Live Now!";
			}
			else{
				$streamMap = [[],[]];
				if(isset($data['args']['url_encoded_fmt_stream_map'])){
					$streamMap[0] = explode(',', $data['args']['url_encoded_fmt_stream_map']);
					if(count($streamMap[0])) $streamMap[0] =  $this->streamMapToArray($streamMap[0]);
				}
				if(isset($data['args']['adaptive_fmts'])){
					$streamMap[1] = explode(',', $data['args']['adaptive_fmts']);
					if(count($streamMap[1])) $streamMap[1] =  $this->streamMapToArray($streamMap[1]);
				}
				$this->data['video'] = ["encoded"=>$streamMap[0], "adaptive"=>$streamMap[1]];
			}
		}

		private function parseVideoDetail($data){
			$panelDetails = explode('"action-panel-details"', $data);
			if(count($panelDetails)==1){
				$this->error = "Failed to parse video details";
				file_put_contents($this->settings['temporaryDirectory'].'/error.log', $data);
				return false;
			}

			$userData = explode('photo.jpg', $panelDetails[0]);
			$userData[0] = explode('"', $userData[0]);
			$userData[0] = $userData[0][count($userData[0])-1].'photo.jpg';
			$userData[1] = explode('alt="', $panelDetails[0])[1];
			$userData[1] = explode('"', $userData[1])[0];
			$this->data['userData'] = [
				"name"=>$userData[1],
				"image"=>$userData[0]
			];
			$this->data['userID'] = explode('/', explode('?', explode('"', explode('/user/', $panelDetails[0])[1])[0])[0])[0];

			$panelDetails = $panelDetails[1];
			$panelDetails = explode("<button", $panelDetails)[0];

			$uploaded = explode('"watch-time-text">', $panelDetails);
			if(count($uploaded)!=1){
				$uploaded = explode('</strong>', $uploaded[1])[0];
				$this->data['uploaded'] = $uploaded;
			}

			$description = explode('"eow-description"', $panelDetails)[1];
			$description = str_replace(['<br />', '<br/>', '<br>'], "\n", $description);
			$description = strip_tags('<p'.explode('</p>', $description)[0]);
			$this->data['description'] = $description;
			
			$metatag = trim('<'.explode('"watch-extras-section"', $panelDetails)[1]);
			$metatag = str_replace('  ', '', $metatag);
			$metatag = explode("<h4 class=\"title\">\n", $metatag);
			unset($metatag[0]);
			$metatag = array_values($metatag);
			foreach ($metatag as &$value) {
				$value = str_replace("\n\n\n", ': ', trim(strip_tags($value)));
			}
			$this->data['metatag'] = $metatag;
			
			$likeDetails = explode('"like-button-renderer', $data)[1];
			$likeDetails = explode('dislike-button-clicked yt-uix-button-toggled', $likeDetails)[0];
			$likeDetails = explode('like-button-unclicked', $likeDetails);
			unset($likeDetails[0]);
			foreach ($likeDetails as &$value) {
				$value = explode('button-content">', $value)[1];
				$value = explode('</span>', $value)[0];
			}
			$this->data['like'] = str_replace('.', '', $likeDetails[1]);
			$this->data['dislike'] = str_replace('.', '', $likeDetails[2]);
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
					if(!isset($this->data['playerID']))
						$this->data['playerID'] = $this->getPlayerScript($data['videoID']);
					if(strpos($map_info['url'], 'ratebypass=')===false)
						$map_info['url'] .= '&ratebypass=yes';
	  				$signature = '&signature='.$this->decipherSignature($map_info['s']);
				}
		
				$map['url'] = $map_info['url'].$signature.'&title='.urlencode($this->data['title']);
				if($this->settings['loadVideoSize']){
					$size = \ScarletsFiction\WebApi::urlContentSize($map['url']);
					$map['size'] = \ScarletsFiction\FileApi::fileSize($size);
				}
			}
			return $streamMap;
		}

		public function getEmbedLink(){
			if(!isset($this->data['videoID'])){
				$this->error = "videoID was not found";
				return false;
			}
			return "//www.youtube.com/embed/".$this->data['videoID']."?rel=0";
		}

		public function parseSubtitle($idOrXML=false, $asSRT=false){
			if(is_string($idOrXML)){
				$data = $idOrXML;
			}else{
				if(!isset($this->data['subtitle'][$idOrXML])){
					$this->error = "No subtitle found";
					return false;
				}
				$data = \ScarletsFiction\WebApi::loadURL($this->data['subtitle'][$idOrXML]['url'])['content'];
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
			if($asSRT){
				$srt = '';
				for($i=0; $i<count($data); $i++){
					$srt .= "\n".($i+1)."\n";
					$srt .= gmdate("H:i:s,", $data[$i]['time']).floor(fmod($data[$i]['time'], 1)*1000);
					$sum = $data[$i]['time']+$data[$i]['duration'];
					$srt .= ' --> '.gmdate("H:i:s,", $sum).floor(fmod($sum, 1)*1000);
					$srt .= "\n".$data[$i]['text'];
					if($data[$i+1]['time']<$sum){
						$srt .= ' '.$data[$i+1]['text'];
						$i++;
					}
					$srt .= "\n";
				}
				return $srt;
			}
			return $data;
		}
		
		public function getImage()
		{
			$id = $this->data['videoID'];
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

			$this->data['playerID'] = $playerID;
			return $playerID;
		}

		private function getSignatureParser(){
			$this->data['signature'] = ['playerID'=>$this->data['playerID']];
			if($this->settings['signatureDebug']){
				$this->data['signature']['log'] = "==== Load player script and execute patterns ====\n\n";
				$this->data['signature']['log'] .= "Loading player ID = ".$this->data['playerID']."\n";
			}
			
			if(!$this->data['playerID']) return false;

			if(file_exists($this->settings['temporaryDirectory'].'/'.$this->data['playerID'])) {
				$decipherScript = file_get_contents($this->settings['temporaryDirectory'].'/'.$this->data['playerID']);
			} else{
				$this->error = "Player script was not found for id: ".$this->data['playerID'];
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
				$this->data['signature']['log'] .= 'signatureFunction = '.$signatureFunction."\n";

			$decipherPatterns = explode($signatureFunction."=function(", $decipherScript)[1];
			$decipherPatterns = explode('};', $decipherPatterns)[0];
			
			if($this->settings['signatureDebug'])
				$this->data['signature']['log'] .= 'decipherPatterns = '.$decipherPatterns."\n";
		
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
				$this->data['signature']['log'] .= print_r($decipher, true);
		
			// Convert pattern to array
			$decipherPatterns = str_replace($deciphersObjectVar.'.', '', $decipherPatterns);
			$decipherPatterns = str_replace('(a,', '->(', $decipherPatterns);
			$decipherPatterns = explode(';', explode('){', $decipherPatterns)[1]);
			$this->privateData['signature']['patterns'] = $decipherPatterns;
		
			// Convert deciphers to object
			$deciphers = [];
			foreach ($decipher as &$function) {
				$deciphers[explode(':function', $function)[0]] = explode('){', $function)[1];
			}
			$this->privateData['signature']['deciphers'] = $deciphers;

			return true;
		}
		
		private function decipherSignature($signature){
			if(isset($this->data['signature']['playerID'])&&$this->data['signature']['playerID']==$this->data['playerID']){
				if($this->settings['signatureDebug'])
					$this->data['signature']['log'] = "==== Deciphers loaded ====\n";
			}
			else $this->getSignatureParser();

			if(!isset($this->privateData['signature']['patterns'])){
				$this->error = "Signature patterns not found";
				return false;
			}
			$patterns = $this->privateData['signature']['patterns'];
			$deciphers = $this->privateData['signature']['deciphers'];

			if($this->settings['signatureDebug']){
				$this->data['signature']['log'] = "==== Retrieved deciphers ====\n\n";
				$this->data['signature']['log'] .= print_r($patterns, true);
				$this->data['signature']['log'] .= print_r($deciphers, true);
			}
		
			if($this->settings['signatureDebug'])
				$this->data['signature']['log'] .= "\n\n\n==== Processing ====\n\n";
		
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
							$this->data['signature']['log'] .= "String splitted\n";
					}
					else if(strpos($patterns[$i], '.join("")')!==false)
					{
						$processSignature = implode('', $processSignature);
						if($this->settings['signatureDebug'])
							$this->data['signature']['log'] .= "String combined\n";
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
						$this->data['signature']['log'] .= "Executing $executes[0] -> $number";
					switch($execute){
						case "a.reverse()":
							$processSignature = array_reverse($processSignature);
							if($this->settings['signatureDebug'])
								$this->data['signature']['log'] .= " (Reversing array)\n";
						break;
						case "var c=a[0];a[0]=a[b%a.length];a[b]=c":
							$c = $processSignature[0];
							$processSignature[0] = $processSignature[$number%count($processSignature)];
							$processSignature[$number] = $c;
							if($this->settings['signatureDebug'])
								$this->data['signature']['log'] .= " (Swapping array)\n";
						break;
						case "a.splice(0,b)":
							$processSignature = array_slice($processSignature, $number);
							if($this->settings['signatureDebug'])
								$this->data['signature']['log'] .= " (Removing array)\n";
						break;
						default:
							$this->error = "Decipher dictionary was not found #2";
							return false;
						break;
					}
				}
			}
		
			if($this->settings['signatureDebug']){
				$this->data['signature']['log'] .= "\n\n\n==== Result ====\n";
				$this->data['signature']['log'] .= "Signature  : ".$signature."\n";
				$this->data['signature']['log'] .= "Deciphered : ".$processSignature;
			}

			return $processSignature;
		}

		protected function setID($set){
			$this->data = ["videoID"=>$set];
		}
	}

	class Channel extends LittleYoutubeInfo
	{
		public function processDetails(){
			$data = [];
			if(isset($this->data['channelID'])){
				$data[0] = "https://www.youtube.com/channel/".$this->data['channelID']."/playlists";
				$data[1] = "https://www.youtube.com/channel/".$this->data['channelID']."/videos?sort=dd&view=0&shelf_id=2";
			} else if(isset($this->data['userID'])){
				$data[0] = "https://www.youtube.com/user/".$this->data['userID']."/playlists";
				$data[1] = "https://www.youtube.com/user/".$this->data['userID']."/videos?sort=dd&view=0&shelf_id=2";
			} else {
				$this->error = "No Channel ID found";
				return false;
			}

			// Playlists
			$value = \ScarletsFiction\WebApi::loadURL($data[0])['content'];
			$value = explode('/playlist?list=', $value);

			$this->data['channelID'] = explode('/', explode('?', explode('"', explode('/channel/', $value[0])[1])[0])[0])[0];
			$this->data['userID'] = explode('/', explode('?', explode('"', explode('/user/', $value[0])[1])[0])[0])[0];//src="
			$userData = explode('>', explode('appbar-nav-avatar', $value[0])[1])[0];
			$this->data['userData'] = [
				"name"=>explode('"', explode('title="', $userData)[1])[0],
				"image"=>explode('"', explode('src="', $userData)[1])[0],
			];

			unset($value[0]); $value = array_values($value);
			foreach ($value as &$value_) {
				$value_ = explode('</a>', $value_)[0];
				$value_ = explode('>', $value_);
				$value_ = ["title"=>$value_[1], "playlistID"=>explode('"', $value_[0])[0]];
			}
			$this->data['playlists'] = $value;

			// Videos
			$value = \ScarletsFiction\WebApi::loadURL($data[1])['content'];
			$value = explode('yt-lockup-title', $value);
			unset($value[0]); $value = array_values($value);

			foreach ($value as &$value_) {
				$value_ = explode('</span>', $value_)[0];
				$value_ = explode('/watch?v=', $value_)[1];
				$value_ = explode('>', $value_);
				$value_ = [
					"title"=>explode('<', $value_[1])[0],
					"duration"=>explode('Duration: ', $value_[count($value_)-1])[1],
					"videoID"=>explode('"', $value_[0])[0]];
			}
			$this->data['videos'] = $value;
		}

		public function getChannelRSS($load=false, $parse=false){
			$link = "https://www.youtube.com/feeds/videos.xml?channel_id=".$this->data['channelID'];
			if(!$load) return $link;
			else{
				$data = \ScarletsFiction\WebApi::loadURL($link);
				if($parse){
					//ToDo: build youtube rss parser
				} else return $data;
			}
		}

		protected function setID($set){
			$this->data[$set[0]] = $set[1];
		}
	}

	class Playlist extends LittleYoutubeInfo
	{
		public function processDetails(){
			$data = \ScarletsFiction\WebApi::loadURL('https://www.youtube.com/playlist?list='.$this->data['playlistID'])['content'];
			$data = explode('data-title="', $data);

			$this->data['channelID'] = explode('/', explode('?', explode('"', explode('/channel/', $data[0])[1])[0])[0])[0];
			$this->data['userID'] = explode('/', explode('?', explode('"', explode('/user/', $data[0])[1])[0])[0])[0];//src="
			$userData = explode('>', explode('appbar-nav-avatar', $data[0])[1])[0];
			$this->data['userData'] = [
				"name"=>explode('"', explode('title="', $userData)[1])[0],
				"image"=>explode('"', explode('src="', $userData)[1])[0],
			];

			unset($data[0]); $data = array_values($data);
			foreach ($data as &$value){
				$title = explode('" ', $value)[0];
				$playlistID = explode('watch?v=', $value);
				$playlistID = explode('&amp;', $playlistID[1])[0];
				$value = ["title"=>$title, "videoID"=>$playlistID];
			}
			$this->data['videos'] = $data;
		}

		protected function setID($set){
			$this->data = ["playlistID"=>$set[1]];
		}
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
			if($header['http_code']!=200){
				curl_close($ch);
				echo("Connection error: ".$header['http_code']);
				return ['headers'=>'', 'content'=>'', 'cookies'=>''];
			}
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
			$data = self::loadURL($url, ['headerOnly'=>true]);
			$size = 0;
			if($data['headers']) {
				if(preg_match("/Content-Length: (\d+)/", $data['headers'], $matches)){
		    	  $size = (int)$matches[1];
		    	}
		    }
			return $size;
		}
	}

	class FileApi{
		public static function fileSize($bytes, $decimals=2) {
		  	$sz = 'BKMGTP';
		  	$factor = floor((strlen($bytes) - 1) / 3);
		  	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).' '.@$sz[$factor].($factor!=0?'B':'ytes');
		}
	}
}