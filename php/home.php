<?php

	if(!empty($_MESSAGE)) echo $_MESSAGE;

	$_REQ = "SELECT `book_title`, `book_author`, `book_publisher`, `book_ean`, COUNT(`review_id`) as `review_num`, DATE_FORMAT(`review_pub_date`, '%Y-%m-%d') AS `review_date`
        FROM `reviews`
		JOIN `books` ON `book_ean` = `review_ean`
        JOIN `sites` USING(`site_id`)
        WHERE `review_called` != '0000-00-00 00:00:00' AND `review_pub_date` <= NOW() AND `book_title` != '' ".$req." 
        GROUP BY `review_ean`
        ORDER BY ".$order."
    LIMIT ".$_GET["p"].",".$pp."";
	
    $books = mysql_query($_REQ) or die(mysql_error());
    
    $cur_date = NULL; $rw = 0;
    while($b = mysql_fetch_array($books)) {
        $b["reviews"] = NULL; $ir = 0;
        $reviews = mysql_query("SELECT `review_shorturl`, `review_reviewer`, `review_score`, `site_name` FROM `reviews` JOIN `sites` USING(`site_id`) WHERE `review_ean` = '".$b["book_ean"]."' AND `review_called` != '0000-00-00 00:00:00' ORDER BY `review_pub_date` DESC");
        while($r = mysql_fetch_array($reviews)) {
            if(!empty($r["review_reviewer"])) $reviewer = ' de '.$r["review_reviewer"]; else $reviewer = NULL;
            if(!empty($r["review_source"]) and $r["review_source"] != $r["site_name"]) $site = ' dans '.$r["review_source"][$ir]; else $site = ' sur '.$r["site_name"];
            $r["line"] = '<p><a href="/'.$r["review_shorturl"].'">Lire la critique '.$reviewer.$site.' '.score($r["review_score"]).'</a></p>';
            if($ir < 2) $b["reviews"] .= $r["line"];
            elseif($ir == 2) $b["last_review"] = $r["line"];
            $ir++;
        }
        if($ir > 2)
		{
            $ir = $ir - 2;
            if($ir == 1) $b["reviews"] .= $r["last_review"];
            else $b["reviews"] .= '<p>&#8594; <a href="/'.$b["book_ean"].'">Voir les '.$ir.' autres critiques</a></p>';
        }
        
        
        if(filepath($b["book_ean"])) $b["image"] = '<img src="'.filepath($b["book_ean"],"url").'" />';
        else $b["image"] = '';
        
        if(!empty($b["book_title"])) {
            
            if($_GET["o"] == "news" and $cur_date != $b["review_date"]) {
                echo '<div class="date">'._date($b["review_date"],"L j f Y").'</div>';
                $cur_date = $b["review_date"];
            }
            
            echo '
                <div class="book" style="clear: both;">
                    <div class="book-cover"><a href="/'.$b["book_ean"].'">'.$b["image"].'</a></div>
                    <div class="book-data">
                        <p class="book-author"><a href="/?q='.$b["book_author"].'">'.$b["book_author"].'</a></p>
                        <p class="book-title"><a href="/'.$b["book_ean"].'">'.$b["book_title"].'</a></p>
                        <p class="book-infos"><a href="/?q='.$b["book_publisher"].'">'.$b["book_publisher"].'</a> - '.isbn($b["book_ean"],"ISBN").'</p>
                        <div class="book-reviews">
                            '.$b["reviews"].'
                        </div>
                        <div class="book-actions">
                            <p id="addBook_'.$b["book_ean"].'" class="add pointer">Ajouter une critique pour ce livre</a></p>
                            <!-- <p><a href="/widget?ean='.$b["book_ean"].'">Afficher ces critiques sur mon site</a></p -->
                        </div>
                    </div>
                    <div id="chosen_'.$b["book_ean"].'" class="invisible">
                        <div class="book-cover"><img src="'.filepath($b["book_ean"],"url").'" class="mini" /></div>
                        <div class="book-data">
                            <p class="book-author">'.$b["book_author"].'</p>
                            <p class="book-title">'.$b["book_title"].'</p>
                            <p class="book-infos">'.$b["book_publisher"].' - '.$b["book_ean"].'</p>
                        </div>
                    </div>
                </div>
            ';
        }
        $rw++;
    }
    if(empty($rw)) echo '<p class="center">Aucun r&eacute;sultat pour cette recherche.<br /> Pourquoi ne pas <span class="add pointer">ajouter une critique</span> ?</p>';
    echo '<p id="copyright">&copy; <a href="http://nokto.net/">nokto.net</a> 2010-'.date('Y').'</p>';

?>