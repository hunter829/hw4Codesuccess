<?php 
	class SuggestWord{
		private static $DICT = array();
		private static $flag = false;
		public static function build_prefix($words){
			$tmp = "";
			if(count($words) == 0){
				return "";
			}
			foreach ($words as $word) {
				$tmp_array = self::suggest($word);
				if(count($tmp_array) == 0 || in_array($word, $tmp_array)){
					$tmp .= $word.' ';
				}
				else{
					$tmp .= $tmp_array[0].' ';
				}
			}
			return $tmp;
		}

		private static function init_dict(){
			$indexes = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
			foreach ($indexes as $file) {
				$path = "/Users/hongruzh/Sites/solr-php-client-master/txtFolder/files/file".$file.".txt";
				//echo $path;
				self::$DICT[strtolower($file)] = self::getMap($path);
				// self::$DICT[$file] = file_get_contents($path);
			}
			$flag = true;
		}
		
		public static function getMap($file){
		$file = fopen($file, "r") or exit("Unable to open file!");
		$array = array();
		while(!feof($file)){
		 	$line = fgets($file);
		 	if(strcmp($line, "")==0) break;
		 	$params = explode(" ", $line);
		 	$array[$params[0]] = $params[1]; 
		}
		return $array;
		fclose($file);
	   }

		private static function add_extra_word($res, $dictionary, $prefix){
			$extra_list = array();
			foreach ($dictionary as $w => $c) {
				if(strlen($prefix) < strlen($w) && substr($w, 0, strlen($prefix)) == $prefix){
					$extra_list[$w] = $c;
				}
			}
			arsort($extra_list);
			foreach ($extra_list as $key => $value) {
				if(count($res) < 10){
					array_push($res, $key);

				}
			}
			return $res;
		}
		public static function suggest($word){
			$url = "http://localhost:8983/solr/csci572/suggest?q=".$word."&wt=json&indent=true";
			$result = file_get_contents($url);
			$tmp = array();
			if($result){
				$array = json_decode($result, true);
				$suggestions = $array["suggest"]["suggest"][$word]["suggestions"];
				foreach ($suggestions as $item) {
					$tmp[$item["term"]] = $item["weight"];
				}
				arsort($tmp);
			}

			$keys = array_keys($tmp);
			//print_r($keys);

			/*
			* use an external dictionary to improve the suggestions
			*/
			$index = substr($word, 0, 1);

			$flag = false;
			if($flag == false) {
				self::init_dict();
			}
			$dictionary = self::$DICT[$index];
			$res = array();
			foreach ($keys as $key) {
				if(array_key_exists($key, $dictionary) || preg_match("/^[a-zA-Z0-9]+'{0,1}[a-zA-Z]*$/", $key)){
					array_push($res, $key);
				}
			}
			if(count($res) < 10){
				$res = self::add_extra_word($res, $dictionary, $word);
			}
			$input_length = strlen($word);
			if($input_length == 1){
				return $res;
			}
			elseif ($input_length == 2) {
				return array_slice($res, 0, 7);
			}
			else{
				return array_slice($res, 0, 5);
			}
		}
	}

	$query = isset($_GET['term']) ? $_GET['term'] : false;
	$result = false;
	if($query){
		$words = preg_split("/\s+/", $query);
		$last = array_pop($words);
		$prefix = SuggestWord::build_prefix($words);
		$last_suggest = SuggestWord::suggest($last);
		$result = array();
		foreach ($last_suggest as $s) {
			array_push($result, $prefix.$s);
		}
		echo json_encode($result);
	}
	
?>