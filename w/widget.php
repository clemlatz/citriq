<?
    include("../inc/mysql.php");
    include("../inc/functions.php");
	
	// Connect to MySQL
	if(mysql_connect($db["host"],$db["user"],$db["pass"])) {
		mysql_select_db($db["base"]);
		mysql_set_charset('utf8'); // Encodage de la connexion MySQL
	}
	else die("<h1>Maintenance du site en cours...</h1><p>Merci de votre compr&#233;hension !</p>");
    
    if(strstr($_SERVER['HTTP_REFERER'],"debug=1") || $_GET["debug"] == 1) $_DEBUG = 1;
    
    //$_DEBUG = 1;
    if(strstr($_GET["x"],".png")) { $_GET["x"] = str_replace('.png','',$_GET["x"]); $_MODE = 'img'; }
    else $_MODE = 'js';
    
    $img_count = 0;
     
    if(empty($_GET["x"])) debug("CITRIQ : code manquant.");
    elseif(!isset($_SERVER['HTTP_REFERER'])) debug("CITRIQ : R&eacute;f&eacute;rent manquant.");
    else {
        $ref = $_SERVER['HTTP_REFERER'];
        $x = $_GET["x"];
        preg_match("/^(http:\/\/)?([^\/]+)/i",$_SERVER['HTTP_REFERER'],$domaine);
        $sites = mysql_query("SELECT `site_id` FROM `sites` where `site_url` like '%$domaine[1]"."$domaine[2]%'");   
        if($s = mysql_fetch_array($sites)) {
            
            $review = mysql_query("SELECT `review_id`, `review_ean` FROM `reviews` WHERE `review_shorturl` = '".$x."' AND `site_id` = '".$s["site_id"]."' LIMIT 1") or die("Erreur : ".mysql_error());
            if($rev = mysql_fetch_array($review)) {
                // Si la critique est d�eja en base, on l'update
                mysql_query("UPDATE `reviews` SET `review_called` = NOW() WHERE `review_id` = '".$rev["review_id"]."' LIMIT 1") or die(mysql_error());
                
                // Affichage des critiques correspondantes sur les autres sites
                $reviews = mysql_query("SELECT `review_id`, `review_shorturl`, `review_reviewer`, `review_source`, `site_name` FROM `reviews` LEFT JOIN `sites` USING(`site_id`) WHERE `review_ean` = '".$rev["review_ean"]."' AND `site_id` != '".$s["site_id"]."'") or die(debug("CITRIQ : ".mysql_error()));
                $num = mysql_num_rows($reviews);
                if(!empty($num)) {
                    echoj("<div id=\"citriq\">");
                    echoj("  <ul>");
                }
                while($r = mysql_fetch_array($reviews)) {
                    if(!empty($r["review_reviewer"])) $reviewer = ' de '.$r["review_reviewer"]; else $reviewer = NULL;
                    if(!empty($r["review_source"]) and $r["review_source"] != $r["site_name"]) $site = ' dans '.$r["review_source"][$ir]; else $site = ' sur '.$r["site_name"];
                    $r["line"] = '<p><a href="/'.$r["review_shorturl"].'">Lire la critique '.$reviewer.$site.' '.score($r["review_score"]).'</a></p>';
                    echoj("    <li>");
                    echoj('      <a href="http://citriq.net/'.$r["review_shorturl"].'">Lire la critique '.$reviewer.$site.' '.score($r["review_score"]).'</a>');
                    echoj("    </li>");
                    mysql_query("UPDATE `reviews` SET `review_views` = `review_views`+1 WHERE `review_id` = '$r[review_id]'");
                    $img_count++;
                }
                if(!empty($num)) {
                    echoj("  </ul>");
                    echoj("</div>");
                } else debug("<p>CITRIQ : Aucune critique &agrave; afficher pour cet ISBN.</p>");
            }
            else debug("CITRIQ : Cette critique n'est pas r&eacute;f&eacute;renc&eacute;e !");
        } else debug("CITRIQ : Site r&#233;f&#233;rent '.$domaine[1]'.'$domaine[2].' inconnu.");
    }
    
    
    if(!$_DEBUG) {
        if($_MODE == "img") {
            header("Content-type: image/png");
            $im = imagecreate(400,30);
            $back = imagecreatefrompng('../img/widget'.$img_count.'.png');
            imagecopy($im, $back, 0, 0, 0, 0, 400, 30);
            imagepng($im);
        }
    }
    mysql_close();
