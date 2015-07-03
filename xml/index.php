<?php

	$start = (float) array_sum(explode(' ',microtime()));
	
    include("../inc/mysql.php");
    include("../inc/functions.php");
	
		
	function error($e) {
		die('<citriq><error>'.$e.'</error></citriq>');
	}
	
	
	// Recup des variables
	$url = $_SERVER['REQUEST_URI'];
	$url = explode("/",$url);
	
	if(isset($_SERVER["HTTP_REFERER"])) $ref = $_SERVER["HTTP_REFERER"]; else $ref = NULL;
		mysql_query("INSERT INTO  `citriq`.`calls` (
`call_key` ,
`call_ip` ,
`call_referer` ,
`call_user_agent` ,
`call_query`
)
VALUES ('".$url[3]."',  '".$_SERVER["REMOTE_ADDR"]."',  '".$ref."',  '".addslashes($_SERVER["HTTP_USER_AGENT"])."',  '".$url[2]."');") or error(mysql_error());
	$call_id = mysql_insert_id();

	
	
	// Prologue
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	
	// Controle cle secrete
	if(empty($url[2])) error('No secret key');
	elseif($url[2] != 'MK5gOGGCchN5O1tG4CY0mez9NqRFjFhW') error('Invalid secret key : '.$url[2]);
	elseif(strlen($url[3]) < 3) error('Query too short : '.$url[3]);
		
	if(!empty($error)) {
		echo '<citriq><error>'.$error.'</error></citriq>';
	} else {
		
		$reviews = mysql_query("SELECT `book_ean`, `review_id`, `review_shorturl`, `review_excerpt`, `review_score` FROM `books` JOIN `reviews` ON `book_ean` = `review_ean` WHERE `book_ean` = '".$url[3]."' OR `book_title` LIKE '%".urldecode($url[3])."%'") or error(mysql_error());
		$num = mysql_num_rows($reviews);
		
		$response = '
<citriq>
	<call>'.$call_id.'</call>
	<key>'.$url[2].'</key>
	<query>'.urldecode($url[3]).'</query>
	<results>'.$num.'</results>
	<reviews>
		';
		
		while($r = mysql_fetch_array($reviews)) {
			$response .= '
		<id>'.$r["review_id"].'</id>
		<ean>'.$r["book_ean"].'</ean>
		<url>http://citriq.net/'.$r["review_shorturl"].'</url>
		<excerpt>'.utf8_encode($r["review_excerpt"]).'</excerpt>
		<note>'.$r["review_score"].'</note>
			';
		}
		
		$response .= '
	</reviews>
</citriq>
		';
		echo $response;
	}
	
	$end = (float) array_sum(explode(' ',microtime()));
	$time = sprintf("%.4f", ($end-$start));
 
	mysql_query("UPDATE `calls` SET `call_execution_time` = '".$time."', `call_results` = '".$num."', `call_response` = '".addslashes($response)."' WHERE `call_id` = '".$call_id."'") or error(mysql_error());
 
?>