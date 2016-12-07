<?php
	// make sure browsers see this page as utf-8 encoded HTML
	header('Content-Type: text/html; charset=utf-8');
	include("SpellCorrector.php");
	include("suggest.php");
	include ("snippet.php");
	ini_set("memory_limit", -1);

	//echo "here<br>";
	function getMap(){
		$file = fopen("map.txt", "r") or exit("Unable to open file!");
		$array = array();
		while(!feof($file)){
		 	$line = fgets($file);
		 	$params = explode(',', $line);
		 	$array[$params[0]] = $params[1]; 
		}
		return $array;
		fclose($file);
	}
	
	$limit = 10;
	$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
	$sort  = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
	$results = false;
	if ($query){
		require_once('Apache/Solr/Service.php');
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr/csci572/');
		if (get_magic_quotes_gpc() == 1){
			$query = stripslashes($query);
		}
		try{
			$results = $solr->search($query, 0, $limit, array('sort'=>$sort));
		}
		catch (Exception $e){
			die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
		}
	}
?>
<html>
<head>
	<title>PHP Solr Client Example</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	
	<script type="text/javascript">
		$(function(){
			$("#q").autocomplete({
				source: function(request, response){
					$.ajax({
						url: "suggest.php",
						dataType: "json",
						data: {term: request.term},
						success: function(data){
							response(data);
						},
						error: function(data){
							console.log("error "+data);
						} 
					});
				},
				select: function(event, ui){
					$("#q").val(ui.item.value);
					$("#search_form").submit();
				},
				maxItems: 5
			});
		});
		
	</script>
</head>
<body>
	<form id="search_form" accept-charset="utf-8" method="get">
		<div id="searchBox">
			<h1 id="heading" for="q">Search</h1>
			<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
			<input type="submit"/>
			<div>
				<?php
					if(strlen($sort) == 0){
				?>
						yes<input type="radio" value="pageRankFile desc" name="sort"/>
						no<input  type="radio" value=""  checked="true"  name="sort"/>
					<?php 
					}
					else{
					?>
						yes<input type="radio" value="pageRankFile desc" checked="true" name="sort"/>
						no<input  type="radio" value=""    name="sort"/>
					<?php }?>
			</div>
		</div>
	</form>

	<?php
	$flag = false;
	if ($results){
		$words = preg_split("/\s+/", $query);
		$correct_words = "";
		foreach ($words as $word) {
			$tmp = SpellCorrector::correct($word);
			//echo "check ".$word.", return ".$tmp;
			if(strcmp($tmp, $word)){
				$flag = true;
				$correct_words .= $tmp.' ';
			}
			else{
				$correct_words .= $word.' ';
			}
		}
	}
	if($flag){
		$t = trim($correct_words);
		echo "Did you mean ";
		echo "<a id=\"correction\" href=\"#\">$t</a>";
		echo "<br>";
	}
	if ($results){
		$total = (int) $results->response->numFound;
		$start = min(1, $total);
		$end = min($limit, $total);
		$map = getMap();
	?>
	<div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
	<ol>
	<?php
	// iterate result documents
	foreach ($results->response->docs as $doc)
	{
	?>
	<li>
		<?php
		$id = "";
		$title = "";
		$url = "";
		$filename = "";
		$snippet = "";
		// iterate document fields / values
		foreach ($doc as $field => $value){
			if(strcmp($field, "title") == 0){
				$title = $value;
			}
			else if(strcmp($field, "id") == 0){
				$id = $value;
				$filename = explode('/', $id)[6];
				$url = $map[(string)$filename];
				$snippet = Snippet::get_snippet($filename, $query);
			}
		}
		?>
		<a href="<?php echo $url; ?>" target="_blank"><?php echo $title; ?></a>
		<p>url: <?php echo $url; ?></p>
		<p>id: <?php echo $filename; ?></p>
		<p>Snippet: ... <?php echo $snippet; ?> ...</p>
	</li>
	<?php
	}
	?>
	</ol>
	<?php
	}
	?>

	<script type="text/javascript">
		$("#correction").click(function(){
			$("#q").val($("#correction").html());
			$("#search_form").submit();
		});
	</script>

</body>
</html>