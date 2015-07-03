<?php

	//print_r($_GET);

    include("inc/mysql.php");
    include("inc/functions.php");
    
	// Connect to MySQL
	if(mysql_connect($db["host"],$db["user"],$db["pass"])) {
		mysql_select_db($db["base"]);
		mysql_set_charset('utf8'); // Encodage de la connexion MySQL
	}
	else die("<h1>Maintenance du site en cours...</h1><p>Merci de votre compr&#233;hension !</p>");
    
    $redirect = NULL;
    $reviews = mysql_query("SELECT `review_id`, `review_url`, `site_id`, `review_uid` FROM `reviews` WHERE `review_shorturl` = '".$_GET["url"]."' LIMIT 1") or die("Erreur : ".mysql_error());
    if($r = mysql_fetch_array($reviews)) {
		if(@fopen($r["review_url"], "r")) { // Si le lien est valide
			mysql_query("UPDATE `reviews` SET `review_hits` = `review_hits` + 1 WHERE `review_id` = '".$r["review_id"]."'") or die("Erreur : ".mysql_error());
			if($r["site_id"] == '4') $r["review_url"] .= '#Crit_'.$r["review_uid"];
			$redirect = '<meta http-equiv="Refresh" content="0;URL='.$r["review_url"].'">';
		}
		else { // Si le lien n'est pas valide
			die('Erreur : Le <a href="'.$r["review_url"].'">lien</a> ne semble plus valide.');
		}
    }

  
    // Stats �change
    if(!empty($_SERVER['HTTP_REFERER'])) {
	preg_match("/^(http:\/\/)?([^\/]+)/i",$_SERVER['HTTP_REFERER'],$domaine);
	$sites = mysql_query("SELECT `site_id` FROM `sites` WHERE `site_url` like '%$domaine[1]"."$domaine[2]%'") or die("Erreur : ".mysql_error());
	if($s = mysql_fetch_array($sites)) {
	    $from = $s["site_id"];
	} else $s["site_id"] = 0;
	mysql_query("INSERT INTO `clics`(`review_id`,`clic_from`,`clic_to`,`clic_referer`) VALUES('".$r["review_id"]."','".$s["site_id"]."','".$r["site_id"]."','".addslashes($_SERVER["HTTP_REFERER"])."')") or die("Erreur : ".mysql_error());
    }
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "DTD/xhtml1-strict.dtd">



<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<title>CITRIQ | Redirection...</title>		
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Language" content="fr" />
		<?php echo $redirect; ?>
		
		<link href="/design/styles.css" type="text/css" rel="stylesheet" />
		
	</head>
  
	<body>
  	<div id="goto">
		<p>Redirection vers <a href="<?php echo $r["review_url"]; ?>"><?php echo $r["review_url"]; ?></a></p>
		<!--<p class="center"><a href="<?php echo $r["review_url"]; ?>"><img src="/design/location.png"></a></p>
		<p class="center"><img src="/design/location.gif"></p>-->
    </div>
  </body>
  
</html>