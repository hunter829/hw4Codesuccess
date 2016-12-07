<?php 
include("Html2Text-1.php");
class Snippet{
	// static $chars = "/[a-zA-Z0-9, ';\"-’—]/";
	static $chars = "/\.|!|\[|\]/";
	public static function get_snippet($filename, $query){
		//$path = "/home/jiaqigu0607/Desktop/572_HW3/solr-6.2.1/crawl_data/".$filename;

		$path = "/Users/hongruzh/solr-6.2.1/data/crawldata/".$filename;

		$raw = file_get_contents($path);
		//$raw = file_get_contents("1");
		$html = new Html2Text($raw);
		$content = $html->getText();
		//echo $content."<br>";
		$Snippet_text = self::extract_snipet($content, $query); 
		return $Snippet_text;
	}

	private static function extract_snipet($content, $query){
		$keys = preg_split("/\s+/", $query);
		$candidate = array();
		foreach ($keys as $key) {
			$pos =  stripos($content, $key);
			while(!is_bool($pos)){
				//echo "position is ".$pos."<br>";

				$start = self::findBound($content, $pos, 1);
				$end   = self::findBound($content, $pos, 2);
				//echo "start: ".$start.", end: ".$end."<br>";

				$text = trim(substr($content, $start, $end - $start + 1));
				//echo $text."<br>";

				$content = substr($content, 0, $start).substr($content, $end+1, strlen($content) - $end - 1);
				//echo "new str is: ".$content."<br>";
				if(is_bool(strpos($text, "/"))){
					array_push($candidate, $text);
				}
				$pos =  stripos($content, $key);
			}
		}
		// foreach ($candidate as $s) {
		// 	echo $s."<br>";
		// }
		usort($candidate, "self::cmp_function");
		return count($candidate)==0 ? "nothing" : $candidate[0];
	}

	private static function findBound($str, $pos, $type){
		while($pos >= 0 && $pos < strlen($str) && !preg_match(self::$chars, substr($str, $pos, 1))){
			if($type == 1){
				$pos -= 1;
			}
			else{
				$pos += 1;
			}
		}
		if($type == 1){
			return $pos+1;
		}
		else{
			if($pos < strlen($str) && in_array(substr($str, $pos, 1), array(".","!"))){
				return $pos;
			}
			else{
				return $pos - 1;
			}
		}
	}

	private static function cmp_function($s1, $s2){
		return strlen($s2) - strlen($s1);
	}
}

// $t = Snippet::get_snippet("1", "california");	
// echo "<br>the best snippet is : ... ".$t."...<br>";

?>