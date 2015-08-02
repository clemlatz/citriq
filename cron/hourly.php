<?php
    include("../inc/functions.php");

	error_reporting(E_ALL ^ E_DEPRECATED);

    $date = date("Y-m-d",strtotime("- 1 month"));
	
	// Mise a jour des infos 25 dernieres critiques ajoutees ou remise a jour des critiques mise a jour il y a plus d'un mois
    $req = "SELECT `book_id`, `book_ean` FROM `books` WHERE `book_import` < '".$date."' ORDER BY `book_import`, `book_insert` DESC";
	$sql = mysql_query($req) or die(mysql_error());
	$num = mysql_num_rows($sql);

	echo '<title>'.$num.'</title><h1>'.$num.' livres &agrave; mettre &agrave; jour</h1>';
	
	if($num) {
		$books = mysql_query($req." LIMIT 10") or die(mysql_error());
		while($b = mysql_fetch_array($books)) {
			
			//die(var_dump($l = biblys($b["book_ean"])));
			
			if($l = biblys($b["book_ean"])) echo 'biblys : ';
			elseif($l = noosfere($b["book_ean"])) echo 'noosfere (ean) : ';
			elseif($l = noosfere(isbn($b["book_ean"],'ISBN10'))) echo 'noosfere (isbn) : ';
			elseif($l = amazon($b["book_ean"])) echo 'amazon : ';
			
			if(is_array($l)) foreach($l as $key => $val) $l[$key] = utf8_encode($val);
			
			if(!isset($l["book_biblys_id"])) $l["book_biblys_id"] = 0;
			if(!isset($l["book_amazon_asin"])) $l["book_amazon_asin"] = 0;
			if(!isset($l["book_noosfere_id"])) $l["book_noosfere_id"] = 0;
			
			if(!empty($l["book_title"])) {
				if(!filepath($b["book_ean"]) and !empty($l["image"])) copy($l["image"],filepath($b["book_ean"],"path")); // Si l'image n'existe pas, on l'importe
				mysql_query("UPDATE `reviews` SET `review_asin` = '".$l["book_amazon_asin"]."', `review_title` = '".addslashes($l["book_title"])."', `review_author` = '".addslashes($l["book_author"])."', `review_publisher` = '".addslashes($l["book_publisher"])."', `review_import` = NOW() WHERE `review_ean` = '".$b["book_ean"]."'") or die(mysql_error());
				$b["reviews"] = mysql_affected_rows();
				mysql_query("UPDATE `books` SET `book_item` = '".$l["book_item"]."', `book_biblys_id` = '".$l["book_biblys_id"]."', `book_amazon_asin` = '".$l["book_amazon_asin"]."', `book_noosfere_id` = '".$l["book_noosfere_id"]."', `book_title` = '".addslashes($l["book_title"])."', `book_author` = '".addslashes($l["book_author"])."', `book_publisher` = '".addslashes($l["book_publisher"])."', `book_import` = NOW(), `book_update` = NOW() WHERE `book_id` = '".$b["book_id"]."'") or die(mysql_error());
			}
			else mysql_query("UPDATE `books` SET `book_import` = NOW() WHERE `book_id` = '".$b["book_id"]."'") or die(mysql_error());
			echo ' [<a href="/'.$b["book_ean"].'">'.$b["book_ean"].'</a>] : '.$l["book_title"].' ('.$b["reviews"].') <br />';
		}
		
		echo '
			<script>
				setTimeout(function() { window.location.reload() }, 1000);
			</script>
		';
	}
	
	mysql_close();
