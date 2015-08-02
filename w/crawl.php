<?php

    error_reporting(E_ALL ^ E_DEPRECATED);
    ini_set('display_errors', 1);
    
    include("../inc/functions.php");
    
    // Mise au format EAN
    $ean = isbn($_GET["isbn"],"EAN");
    $uid = $_GET["uid"];
	
    $ref = null;
    if (isset($_SERVER['HTTP_REFERER'])) {
        $ref = $_SERVER['HTTP_REFERER'];
    }
    
	// Multi domaine noosfere
	if (strstr($ref,'http://www.noosfere.org/')) $ref = str_replace('www.noosfere.org','www.noosfere.com',$ref);
	elseif (strstr($ref,'http://www.noosfere.net/')) $ref = str_replace('www.noosfere.net','www.noosfere.com',$ref);
	elseif (strstr($ref,'http://www.noosfere.fr/')) $ref = str_replace('www.noosfere.fr','www.noosfere.com',$ref);
	
	// Mode debug
    if (strstr($ref,"debug=1") || isset($_GET["debug"])) {
		$_DEBUG = 1;
		$ref = str_replace('&debug=1','',$ref);
		$ref = str_replace('?debug=1','',$ref);
	}
	
	// Encodage utf8 si nécessaire
	$encodings = array('UTF-8', 'ISO-8859-1');
	foreach ($_GET as $key => $val)
	{
		if(mb_detect_encoding($val, array('UTF-8', 'ISO-8859-1')) != "UTF-8")
		{
			$_GET[$key] = utf8_encode($val);
		}
	}
	
    if (!empty($_GET["reviewer"])) $reviewer = trim(strip_tags(urldecode($_GET["reviewer"]))); else $reviewer = NULL;
    if (!empty($_GET["source"])) $source = trim(strip_tags(urldecode($_GET["source"]))); else $source = NULL;
    if (!empty($_GET["excerpt"])) $excerpt = trim(strip_tags(urldecode($_GET["excerpt"]))); else $excerpt = NULL;
    if (!empty($_GET["rating"])) $rating = (int) trim(strip_tags(urldecode($_GET["rating"]))); else $rating = NULL;
	if (!empty($_GET["plugin"])) $plugin = trim(strip_tags($_GET["plugin"])); else $plugin = NULL;
	if (!empty($_GET["date"])) $date = trim(strip_tags($_GET["date"])); else $date = NULL;
	if (isset($_GET["ignore"])) $ignore = (bool) TRUE; else $ignore = (bool) FALSE;
	
    if (empty($_GET["uid"])) debug("CITRIQ : UID manquant.");
    elseif (empty($_GET["isbn"])) debug("CITRIQ : ISBN manquant.");
    elseif (strlen($ean) != 13) debug("CITRIQ : ISBN trop long (".$ean.")");
    elseif (preg_match("/[^0-9]/", $ean)) debug("CITRIQ : ISBN invalide (".$ean."). L'ISBN ne doit contenir que des chiffres.");
    elseif (!isset($ref)) debug("CITRIQ : R&eacute;f&eacute;rent manquant.");
    else {
        preg_match("/^(http:\/\/)?([^\/]+)/i",$ref,$domaine);
        $sites = mysql_query("SELECT `site_id`, `site_premium` FROM `sites` WHERE `site_url` like '%$domaine[1]"."$domaine[2]%' LIMIT 1");   
        if($s = mysql_fetch_array($sites)) {
			
			if ($s["site_id"] != 0)
			{
			
				//if($s["site_premium"]) {
				if (!$ignore) // Si $ignore alors on affiche les critiques pour mais on n'enregistre pas l'url
				{
					
					// Creation du livre en base
					$books = mysql_query("SELECT `book_id` FROM `books` WHERE `book_ean` = '".$ean."' LIMIT 1");
					if(!mysql_fetch_array($books)) {
						debug('CITRIQ : Cr&eacute;ation de la fiche livre en base.<br />');
						mysql_query("INSERT INTO `books`(`book_ean`) VALUES('".$ean."')") or debug("?".mysql_error());
					}
					
					// Ajout/Update à CITRIQ
					$review = mysql_query("SELECT `review_id`, `review_views`, `review_hits`, `book_title`, `review_insert` FROM `reviews` JOIN `books` ON `book_ean` = `review_ean` WHERE `review_uid` = '".$_GET["uid"]."' AND `site_id` = '".$s["site_id"]."' LIMIT 1") or debug("Erreur : ".mysql_error());
					if($rev = mysql_fetch_array($review)) {
						
						// Date de publication de la critique
						if (empty($date)) $date = $rev['review_insert']; // Si elle n'est pas précisée, on utilise la date de première insertion
						elseif ($date > $rev['review_insert']) $date = $rev['review_insert']; // Pour eviter de tricher en utilisant une date plus récente que la première insertion
						
						// Si la critique est déjà en base, on l'update
						mysql_query("UPDATE `reviews` SET `review_ean` = '$ean', `review_url` = '$ref', `review_called` = NOW(), `review_reviewer` = '".$reviewer."', `review_source` = '".$source."', `review_excerpt` = '".$excerpt."', `review_score` = '".$rating."', `review_plugin` = '".$plugin."', `review_pub_date` = '".$date."' WHERE `review_id` = '".$rev["review_id"]."' LIMIT 1") or debug("Erreur : ".mysql_error());
						if(!empty($rev["book_title"])) debug("CITRIQ : Critique en base. Affich&eacute;e ".$rev["review_views"]." fois. Cliqu&eacute;e ".$rev["review_hits"]." fois.<br />id : ".$rev["review_id"]."<br />uid : ".$_GET["uid"]." ");
						else debug("CITRIQ : Critique ajout&eacute;e &agrave; la base, en attente de donn&eacute;es bibliographiques pour ".$ean.".");
					} else {
						// Sinon, on l'ajoute à la base
						if (empty($date)) $date = date('Y-m-d H:i:s'); // Si date non précisée, date courante
						mysql_query("INSERT INTO `reviews`(`review_uid`,`site_id`,`review_ean`,`review_url`,`review_reviewer`,`review_source`,`review_score`,`review_plugin`,`review_pub_date`,`review_insert`,`review_called`) values('".$uid."','".$s["site_id"]."','".$ean."','".$ref."','".$reviewer."','".$source."','".$rating."','".$plugin."','".$date."',NOW(),NOW())") or debug("Erreur : ".mysql_error());
						$short = short_url(mysql_insert_id());
						mysql_query("UPDATE `reviews` SET `review_shorturl` = '$short' WHERE `review_id` = '".mysql_insert_id()."'");
						debug("CITRIQ : Ajout de la critique &#224 la base.<br />");
					}
					
				} else debug('CITRIQ : Page référente ignorée, affichage des critiques pour cet ISBN.<br />');
				
				// Affichage des critiques correspondantes sur les autres sites
				$reviews = mysql_query("SELECT `review_id`, `review_shorturl`, `site_name` FROM `reviews` LEFT JOIN `sites` USING(`site_id`) WHERE `review_ean` = '".$ean."' AND `site_id` != '".$s["site_id"]."' AND `review_called` != '0000-00-00 00:00:00' GROUP BY `site_id`") or die(debug("CITRIQ : ".mysql_error()));
				$num = mysql_num_rows($reviews);
				
				if(!empty($num)) {
					echoj("<div id=\"citriq\">");
					echoj("  <ul>");
				}
				while($r = mysql_fetch_array($reviews)) {
					echoj("    <li>");
					echoj("      <a href=\"http://citriq.net/".$r["review_shorturl"]."\">Voir l'avis de ".htmlentities($r['site_name'])."</a>"); // Ajouté utf8_decode dans html_entities de $r[site_name] le 17/02/2014 pour mesimaginaires.net
					echoj("    </li>");
					mysql_query("UPDATE `reviews` SET `review_views` = `review_views`+1 WHERE `review_id` = '$r[review_id]'");
				}
				if(!empty($num)) {
					echoj("  </ul>");
					echoj("</div>");
				}
					//else debug("CITRIQ : Aucune critique &agrave; afficher pour cet ISBN.");
					
				//} else debug("CITRIQ : R&eacute;f&eacute;rencement automatique d&eacute;sactiv&eacute;.");
			} else debug("CITRIQ : Site r&eacute;f&eacute;rent inconnu. (0)");
        } else debug("CITRIQ : Site r&eacute;f&eacute;rent inconnu. ($domaine[0])");
    }

    mysql_close();
