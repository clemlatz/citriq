<?php
    include("inc/functions.php");
    include("inc/mysql.php");
    
    header("Content-Type: application/xml; charset=ISO-8859-1");
    
    // Flux personnalise
    if(!empty($_GET["q"])) {
        $qex = explode(" ",$_GET["q"]);
        $req = "AND "; $i = 0;
        foreach($qex as $qexa) {
            if($i != 0) $req .= " AND ";
            $qexa = addslashes($qexa);
            $req .= "(`review_title` LIKE '%".$qexa."%' OR `review_author` LIKE '%".$qexa."%' OR `review_publisher` LIKE '%".$qexa."%' OR `site_name` LIKE '%".$qexa."%' OR `review_ean` LIKE '%".isbn($qexa,"EAN")."%')";
            $i++;
        }
        $_GET["o"] = "news";
    } else $req = NULL;
    
    echo "<".'?xml version="1.0" encoding="ISO-8859-1"?'.">";
    $xml = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
    
    $xml .= '<channel>
    '; 
    $xml .= '<title>CITRIQ</title>
    ';
    $xml .= '<link>http://citriq.net/</link>
    ';
    $xml .= '<description>Les derni&#232;res critiques</description>
    ';
    $xml .= '<language>fr</language>
    ';
    $xml .= '<atom:link href="http://citriq.net/rss" rel="self" type="application/rss+xml" />
    ';
    
    $sql = mysql_query("SELECT `review_title`,`review_author`,`review_publisher`,`review_shorturl`,`review_insert`,`site_name`, `review_ean`
        FROM `reviews`
        LEFT JOIN `sites` USING(`site_id`)
        WHERE `review_title` != '' ".$req." ORDER BY `review_insert` DESC LIMIT 100") or die("Erreur : ".mysql_error());
    
    while($r = mysql_fetch_array($sql)) {
        $daterss = date("D, d M Y H:i:s +0100", strtotime($r["review_insert"]));
        
        if(!empty($r["review_publisher"])) $r["publisher"] = ' ('.$r["review_publisher"].')'; else $r["publisher"] = '';
        if(!empty($r["site_name"])) $r["site"] = ' critiqu&#233; par '.$r["site_name"]; else $r["site"] = '';
        $r["text"] = $r["review_title"].', de '.$r["review_author"].$r["publisher"];
        if(strlen($r["text"].$r["site"]) < 118) $r["text"] .= $r["site"];
        elseif(strlen($r["text"]) > 115) $r["text"] = substr($r["text"],0,115)."...";
		$r["text"] = htmlspecialchars($r["text"]);
        $xml .= '<item>
        ';
        $xml .= '<title>'.trim($r["text"]).' http://citriq.net/'.$r["review_shorturl"].'</title>
        ';
        $xml .= '<link>http://citriq.net/'.$r["review_ean"].'</link>
        ';
        $xml .= '<guid>http://citriq.net/'.$r["review_ean"].'</guid>
        ';
        $xml .= '<pubDate>'.$daterss.'</pubDate>
        '; 
        $xml .= '</item>
        ';
    }
    
    $xml .= '</channel>';
    $xml .= '</rss>';
           
    echo $xml;
  
?>
