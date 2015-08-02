<?php

	function check_url($url)
	{
		$url = $_POST["review_url"];
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
		$response = curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		return $httpCode;
	}
	
    if(!empty($_POST)) {
		$_POST["review_excerpt"] = addslashes(str_replace("?","'",strip_tags($_POST["review_excerpt"])));
		
		if ($_SITE['site_id'] == 29 || $_SITE['site_id'] == 119) $check_url = 200;
		else $check_url = check_url($_POST['review_url']);
		
		if(empty($_POST["review_url"]) or $_POST["review_url"] == "http://") $result = "Le champ URL ne peut pas être vide !";
		if(strlen($_POST["review_excerpt"]) > 512) $result = "L'extrait proposé ne doit pas dépasser 512 caractères !";
		elseif(!strstr($_POST["review_url"],$_SITE["site_url"])) $result = "L'adresse de la critique doit commencer par ".$_SITE["site_url"];
		else {
			if ($check_url == 200)
			{
				if(!empty($_POST["review_id"])) { // Mise à jour
					$reviews = mysql_query("SELECT `review_id` FROM `reviews` WHERE `review_id` = '".$_POST["review_id"]."' LIMIT 1");
					if($r = mysql_fetch_array($reviews)) {
					mysql_query("UPDATE `reviews` SET `review_url` = '".$_POST["review_url"]."', `review_excerpt` = '".$_POST["review_excerpt"]."', `review_score` = '".$_POST["review_score"]."' WHERE `review_id` = '".$_POST["review_id"]."' LIMIT 1") or die(mysql_error());
					$result = 'OK';
					} else $result = "Erreur : cette critique n'existe plus !";
				} else {
					$urls = mysql_query("SELECT `review_id`, `review_shorturl` FROM `reviews` WHERE `review_url` = '".$_POST["review_url"]."' LIMIT 1");
					if($u = mysql_fetch_array($urls)) {
						mysql_query("UPDATE `reviews` SET `review_excerpt` = '".$_POST["review_excerpt"]."' WHERE `review_id` = '".$u["review_id"]."'") or die(mysql_error());
						$result = 'OK::<script type="text/javascript" src="http://citriq.net/widget/'.$u["review_shorturl"].'"></script>::<a href="http://citriq.net/'.$_POST["review_ean"].'"><img src="http://citriq.net/widget/'.$u["review_shorturl"].'.png" alt="CITRIQ" /></a>';
					} else {
						
						// Adding review
						mysql_query("INSERT INTO `reviews`(`site_id`,`user_id`,`review_ean`,`review_url`,`review_excerpt`,`review_score`,`review_insert`,`review_pub_date`) values('".$_SITE["site_id"]."','".$_LOG["user_id"]."','".$_POST["review_ean"]."','".$_POST["review_url"]."','".$_POST["review_excerpt"]."','".$_POST["review_score"]."',NOW(),NOW())") or die(mysql_error());
						
						// Creating and saving short url
						$review_id = mysql_insert_id();
						$short_url = short_url($review_id);
						mysql_query("UPDATE `reviews` SET `review_shorturl` = '$short_url' WHERE `review_id` = $review_id") or die(mysql_error());
						
						// Adding or getting related book
						$books = mysql_query("SELECT `book_id` FROM `books` WHERE `book_ean` = '".$_POST["review_ean"]."'") or die(mysql_error());
						if ($b = mysql_fetch_array($books)) { 
							$book_id = $b["book_id"]; 
						} else {
							mysql_query("INSERT INTO `books`(`book_ean`) VALUES('".$_POST["review_ean"]."')") or die(mysql_error());
						}
						
						// Return code
						$result = 'OK::<script type="text/javascript" src="http://citriq.net/widget/'.$short_url.'"></script>::<a href="http://citriq.net/'.$_POST["review_ean"].'"><img src="http://citriq.net/widget/'.$short_url.'.png" alt="CITRIQ" /></a>';
					}
				}
			}
			else $result = "Erreur : l'adresse fournie a renvoyé une erreur ".$check_url." !";
		}
    }
