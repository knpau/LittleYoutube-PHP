<?php
namespace ScarletsFiction;

class LittleYoutube
{
	public $version = "0.6.1";
	public $settings;
	private $data;

	public function __construct($options = null)
	{
		$settings = [
			"temporaryDirectory"=>"./temp",
			"5"=>false,
		];
		$data = [];
		if($options){
			$settings = (object) array_merge((array) $settings, (array) $options);
		}
	}

	public function getVideoIDFromURL($url)
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
		$data['videoID'] = $id;
		return $id;
	}

	public function getVideoLink($id=false)
	{
		if(!$id) 
			if(isset($data['videoID'])) $id = $data['videoID'];
			else return "No videoID";
		$id = self::getVideoIDFromURL($id);
		$raw = self::loadURL('https://www.youtube.com/get_video_info?video_id='.$id.'&asv=3&hl=en_US&el=embedded&ps=default&eurl=&gl=US');
		parse_str($raw, $data);
		if(isset($data['reason'])&&$data['reason']!='')
			return($data['reason']);
		
		$streamMap = [[],[]];
		if(isset($data['url_encoded_fmt_stream_map']))
			$streamMap[0] = explode(',', $data['url_encoded_fmt_stream_map']);
		if(isset($data['adaptive_fmts']))
			$streamMap[1] = explode(',', $data['adaptive_fmts']);

		return [
			"encoded"=>self::streamMapToArray($streamMap[0]),
			"adaptive"=>self::streamMapToArray($streamMap[1])
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
			$map['type'][1] = explode('"', $map['type'][1])[1];
			$map['expire'] = isset($url_info['expire'])?$url_info['expire']:0;

			if(isset($map_info['bitrate']))
				$map['quality'] = round($map_info['bitrate']/1000).'k';
			else
				$map['quality'] = isset($map_info['quality'])?$map_info['quality']:'';
	
			$signature = '';

			// The video signature need to be deciphered
			if(isset($map_info['s'])&&false) //On progress
			{
				$this->$data['playerID'] = self::downloadPlayerScript($data['videoID']);
				if(strpos($map_info['url'], 'ratebypass=')===false)
					$map_info['url'] .= '&ratebypass=yes';
  				$signature = '&signature='.self::decipherSignature(self::$data['playerID'], $map_info['s']);
			}
	
			$map['url'] = $map_info['url'].$signature;
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

	public function downloadPlayerScript($videoID){
		$playerID = self::loadURL("https://www.youtube.com/watch?v=$videoID");
		$playerID = explode("\/yts\/jsbin\/player-", $playerID);
		if(count($playerID)<=1){
			echo("Failed to retrieve player script for video id: $videoID");
			return false;
		}
		$playerID = $playerID[1];
		$playerURL = str_replace('\/', '/', explode('"', $playerID)[0]);
		$playerID = explode('/', $playerURL)[0];
	
		if(!file_exists($this->$settings['temporaryDirectory']."$playerID")) {
			$decipherScript = self::loadURL("https://youtube.com/yts/jsbin/player-$playerURL");
			file_put_contents($settings['temporaryDirectory']."$playerID", $decipherScript);
		}

		self::$data['playerID'] = $playerID;
	}

	public function decipherSignature($signature){
		ob_start(); //For debugging
		echo("==== Load player script and execute patterns ====\n\n");
		echo("Loading player ID = ".self::$data['playerID']."\n");
		
		if(!self::$data['playerID']) return;

		if(file_exists($settings['temporaryDirectory'].self::$data['playerID'])) {
			$decipherScript = file_get_contents($settings['temporaryDirectory'].self::$data['playerID']);
		} else die("\n==== Player script was not found for id: ".self::$data['playerID']." ====");
	
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
			else if($i==0) die("\n==== Failed to get signature function ====");
		}
		echo('signatureFunction = '.$signatureFunction."\n");
	
		$decipherPatterns = explode($signatureFunction."=function(", $decipherScript)[1];
		$decipherPatterns = explode('};', $decipherPatterns)[0];
		echo('decipherPatterns = '.$decipherPatterns."\n");
	
		$deciphers = explode("(a", $decipherPatterns);
		for ($i=0; $i < count($deciphers); $i++) { 
			$deciphers[$i] = explode('.', explode(';', $deciphers[$i])[1])[0];
			if(count(explode($deciphers[$i], $decipherPatterns))>=2){
				// This object was most called, that's mean this is the deciphers
				$deciphers = $deciphers[$i];
				break;
			}
			else if($i==count($deciphers)-1) die("\n==== Failed to get deciphers function ====");
		}
	
		$deciphersObjectVar = $deciphers;
		$decipher = explode($deciphers.'={', $decipherScript)[1];
		$decipher = str_replace(["\n", "\r"], "", $decipher);
		$decipher = explode('}};', $decipher)[0];
		$decipher = explode("},", $decipher);
		print_r($decipher);
	
		// Convert deciphers to object
		$deciphers = [];
		foreach ($decipher as &$function) {
			$deciphers[explode(':function', $function)[0]] = explode('){', $function)[1];
		}
	
		// Convert pattern to array
		$decipherPatterns = str_replace($deciphersObjectVar.'.', '', $decipherPatterns);
		$decipherPatterns = str_replace('(a,', '->(', $decipherPatterns);
		$decipherPatterns = explode(';', explode('){', $decipherPatterns)[1]);
	
		$decipheredSignature = self::executeSignaturePattern($decipherPatterns, $deciphers, $signature);
	
		// For debugging
		echo("\n\n\n==== Result ====\n");
		echo("Signature  : ".$signature."\n");
		echo("Deciphered : ".$decipheredSignature);

		if($settings['signatureDebug'])
			file_put_contents($settings['temporaryDirectory']."Deciphers.log", ob_get_contents());
			//file_put_contents("Deciphers".rand(1, 100000).".log", ob_get_contents()); 
				// ^If you need to debug all video
		ob_end_clean();
	
		//Return signature
		return $decipheredSignature;
	}
	
	private function executeSignaturePattern($patterns, $deciphers, $signature){
		echo("\n\n\n==== Retrieved deciphers ====\n\n");
		print_r($patterns);
		print_r($deciphers);
	
		echo("\n\n\n==== Processing ====\n\n");
	
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
					echo("String splitted\n");
				}
				else if(strpos($patterns[$i], '.join("")')!==false)
				{
					$processSignature = implode('', $processSignature);
					echo("String combined\n");
				}
				else die("\n==== Decipher dictionary was not found ====");
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
				echo("Executing $executes[0] -> $number");
				switch($execute){
					case "a.reverse()":
						$processSignature = array_reverse($processSignature);
						echo(" (Reversing array)\n");
					break;
					case "var c=a[0];a[0]=a[b%a.length];a[b]=c":
						$c = $processSignature[0];
						$processSignature[0] = $processSignature[$number%count($processSignature)];
						$processSignature[$number] = $c;
						echo(" (Swapping array)\n");
					break;
					case "a.splice(0,b)":
						$processSignature = array_slice($processSignature, $number);
						echo(" (Removing array)\n");
					break;
					default:
						die("\n==== Decipher dictionary was not found ====");
					break;
				}
			}
		}
		return $processSignature;
	}
}