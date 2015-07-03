<?
    include("../inc/mysql.php");
    include("../inc/functions.php");
    
    header('Content-Type: application/javascript');
    
    if(!empty($_GET["isbn"])) {
        if(isset($_SERVER['HTTP_REFERER'])) {        
            preg_match("/^(http:\/\/)?([^\/]+)/i",$_SERVER['HTTP_REFERER'],$domaine);
            $sites = mysql_query("SELECT `site_id` FROM `sites` where `site_url` like '%$domaine[1]"."$domaine[2]%'");    
            //if($s = mysql_fetch_array($sites)) {
                $ean = isbn($_GET["isbn"],"EAN");
                $reviews = mysql_query("SELECT `review_id`, `review_shorturl`, `site_name` FROM `reviews` LEFT JOIN `sites` USING(`site_id`) WHERE `review_ean` = '".$ean."'") or die("Erreur : ".mysql_error());
                $num = mysql_num_rows($reviews);
                if(!empty($num)) {
                    echoj("<div id=\"citriq\">");
                    echoj("  <ul>");
                }
                while($r = mysql_fetch_array($reviews)) {
                    echoj("    <li>");
                    echoj("      <a href=\"http://citriq.net/".$r["review_shorturl"]."\">Lire la critique de ".$r["site_name"]."</a>");
                    echoj("    </li>");
                    mysql_query("UPDATE `reviews` SET `review_views` = `review_views`+1 WHERE `review_id` = '".$r["review_id"]."'");
                }
                if(!empty($num)) {
                    echoj("  </ul>");
                    echoj("</div>");
                }
            //} else echoj("CITRIQ : Site référent inconnu");
        } else echoj("CITRIQ : Pas de site référent");
    }
?>