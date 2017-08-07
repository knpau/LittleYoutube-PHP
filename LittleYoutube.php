<?php
namespace ScarletsFiction;

class LittleYoutube
{
	public $version = "0.6.1";
	public $error = false;
	public $settings;
	public $info;
	private $data;

	public function __construct($options = null)
	{
		$this->settings = [
			"temporaryDirectory"=>realpath(__DIR__."/temp"),
			"signatureDebug"=>false,
			"loadVideoMetadata"=>false
		];
		$this->info = [];
		$this->data = [];
		if($options){
			$this->settings = array_replace($this->settings,$options);
		}
	}

	public function videoID($url)
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
		return $id;
	}

	public function getVideoLink()
	{
		if(isset($this->info['videoID'])) $id = $this->info['videoID'];
		else{
			$this->error = "No videoID";
			return false;
		}

		$data = $this->loadURL('https://www.youtube.com/watch?v='.$id);
		$data = explode(';ytplayer.load', $data)[0];
		$data = explode('ytplayer.config = ', $data)[1];
		$data = json_decode($data, true);
		unset($data['args']['fflags']);

		$this->getPlayerScript($data['assets']['js']);
		$this->info['title'] = $data['args']['title'];
		$this->info['duration'] = $data['args']['length_seconds'];
		$this->info['viewCount'] = $data['args']['view_count'];

		if(isset($data['reason'])&&$data['reason']!=''){
			$this->error = $data['reason'];
			return false;
		}
		
		$streamMap = [[],[]];
		if(isset($data['args']['url_encoded_fmt_stream_map']))
			$streamMap[0] = explode(',', $data['args']['url_encoded_fmt_stream_map']);
		if(isset($data['args']['adaptive_fmts']))
			$streamMap[1] = explode(',', $data['args']['adaptive_fmts']);

		return [
			"encoded"=>$this->streamMapToArray($streamMap[0]),
			"adaptive"=>$this->streamMapToArray($streamMap[1])
		];
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
	
			$map['url'] = $map_info['url'].$signature.'&title='.$this->info['title'];
		}
		return $streamMap;
	}

	private function loadURL($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	public function getVideoImages()
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
		$playerID = explode("/yts/jsbin/player-", $playerURL);
		if(count($playerID)<=1){
			$this->error = "Failed to parse playerID from player url: ".$playerURL;
			return false;
		}
		$playerID = $playerID[1];
		$playerURL = str_replace('\/', '/', explode('"', $playerID)[0]);
		$playerID = explode('/', $playerURL)[0];
	
		if(!file_exists($this->settings['temporaryDirectory']."/$playerID")) {
			$decipherScript = $this->loadURL("https://youtube.com/yts/jsbin/player-$playerURL");
			file_put_contents($this->settings['temporaryDirectory']."/$playerID", $decipherScript);
		}

		$this->info['playerID'] = $playerID;
		return $playerID;
	}

	private function getSignatureParser(){
		$this->data['signature'] = ['playerID'=>$this->info['playerID']];
		if($this->settings['signatureDebug']){
			$this->data['signature']['log'] = "==== Load player script and execute patterns ====\n\n";
			$this->data['signature']['log'] .= "Loading player ID = ".$this->info['playerID']."\n";
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
		$this->data['signature']['patterns'] = $decipherPatterns;
	
		// Convert deciphers to object
		$deciphers = [];
		foreach ($decipher as &$function) {
			$deciphers[explode(':function', $function)[0]] = explode('){', $function)[1];
		}
		$this->data['signature']['deciphers'] = $deciphers;

		return true;
	}
	
	public function decipherSignature($signature){
		if(isset($this->data['signature']['playerID'])&&$this->data['signature']['playerID']==$this->info['playerID']){
			if($this->settings['signatureDebug'])
				$this->data['signature']['log'] = "==== Deciphers loaded ====\n";
		}
		else $this->getSignatureParser();

		if(!isset($this->data['signature']['patterns'])){
			$this->error = "Signature patterns not found";
			return false;
		}
		$patterns = $this->data['signature']['patterns'];
		$deciphers = $this->data['signature']['deciphers'];

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
}