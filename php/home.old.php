<?php

        $reviews = mysql_query("SELECT `review_id`,`review_ean`,`review_asin`,`review_insert`,`review_shorturl`,`review_title`,`review_author`,`review_publisher`,`review_score`,`site_name`,COUNT(`review_id`) as `review_num`, DATE_FORMAT(`review_insert`, '%Y-%m-%d') AS `review_date`,
        GROUP_CONCAT(`review_shorturl` ORDER BY `review_insert` DESC SEPARATOR '~') AS `reviews_shorturl`,
        GROUP_CONCAT(`site_name` ORDER BY `review_insert` DESC SEPARATOR '~') AS `reviews_sitename`,
        GROUP_CONCAT(`review_insert` ORDER BY `review_insert` DESC SEPARATOR '~') AS `reviews_insert`,
        GROUP_CONCAT(`review_score` ORDER BY `review_insert` DESC SEPARATOR '~') AS `reviews_score`,
        GROUP_CONCAT(`review_reviewer` ORDER BY `review_insert` DESC SEPARATOR '~') AS `reviews_reviewer`,
        GROUP_CONCAT(`review_source` ORDER BY `review_source` DESC SEPARATOR '~') AS `reviews_source`
        FROM `reviews`
        JOIN `sites` USING(`site_id`)
        WHERE `review_title` != '' ".$req."
        GROUP BY `review_ean`
        ORDER BY ".$order."
        LIMIT ".$_GET["p"].",".$pp."") or die(mysql_error());
    
    $cur_date = NULL; $rw = 0;
    while($r = mysql_fetch_array($reviews)) {
        
        
        
        if(filepath($r["review_ean"])) $r["image"] = '<img src="'.filepath($r["review_ean"],"url").'" />';
        else $r["image"] = '';
        if(!empty($r["review_title"])) {
            $rs["shorturl"] = explode("~",$r["reviews_shorturl"]);
            $rs["sitename"] = explode("~",$r["reviews_sitename"]);
            $rs["insert"] = explode("~",$r["reviews_insert"]);
            $rs["score"] = explode("~",$r["reviews_score"]);
            $rs["reviewer"] = explode("~",$r["reviews_reviewer"]);
            $rs["source"] = explode("~",$r["reviews_source"]);
            $r["reviews"] = ''; $ir = 0;
            foreach($rs["sitename"] as $site) {
                $score_img = ' &nbsp;';
                if(!empty($rs["score"][$ir])) {
                    $score = round($rs["score"][$ir] / 20);
                    for($i = 0; $i < $score; $i++) $score_img .= '<img src="/img/icon_star_1.png" alt="note" />';
                    for($i = 5; $i > $score; $i--) $score_img .= '<img src="/img/icon_star_0.png" alt="note" />';
                }
                if(!empty($rs["reviewer"][$ir])) $reviewer = ' de '.$rs["reviewer"][$ir]; else $reviewer = NULL;
                if(!empty($rs["source"][$ir]) and $rs["source"][$ir] != $site) $site = ' dans '.$rs["source"][$ir]; else $site = ' sur '.$site;
                $r["line"] = '<p><a href="/'.$rs["shorturl"][$ir].'">Lire la critique '.$reviewer.$site.' '.$score_img.'</a></p>';
                if($ir < 2) $r["reviews"] .= $r["line"];
                elseif($ir == 2) $r["last_review"] = $r["line"];
                $ir++;
            }
            if($ir > 2) {
                $ir = $ir - 2;
                if($ir == 1) $r["reviews"] .= $r["last_review"];
                else $r["reviews"] .= '<p>&#8594; <a href="/'.$r["review_ean"].'">Voir les '.$ir.' autres critiques</a></p>';
            }
            
            if($_GET["o"] == "news" and $cur_date != $r["review_date"]) {
                echo '<div class="date">'._date($r["review_date"],"L j f Y").'</div>';
                $cur_date = $r["review_date"];
            }
            
            echo '
                <div class="book" style="clear: both;">
                    <div class="book-cover"><a href="/'.$r["review_ean"].'">'.$r["image"].'</a></div>
                    <div class="book-data">
                        <p class="book-author"><a href="/?q='.$r["review_author"].'">'.$r["review_author"].'</a></p>
                        <p class="book-title"><a href="/'.$r["review_ean"].'">'.$r["review_title"].'</a></p>
                        <p class="book-infos"><a href="/?q='.$r["review_publisher"].'">'.$r["review_publisher"].'</a> - '.isbn($r["review_ean"],"ISBN").'</p>
                        <div class="book-reviews">
                            '.$r["reviews"].'
                        </div>
                        <div class="book-actions">
                            <p id="addBook_'.$r["review_ean"].'" class="add pointer">Ajouter une critique pour ce livre</a></p>
                            <!-- <p><a href="/widget?ean='.$r["review_ean"].'">Afficher ces critiques sur mon site</a></p -->
                        </div>
                    </div>
                    <div id="chosen_'.$r["review_ean"].'" class="invisible">
                        <div class="book-cover"><img src="'.filepath($r["review_ean"],"url").'" class="mini" /></div>
                        <div class="book-data">
                            <p class="book-author">'.$r["review_author"].'</p>
                            <p class="book-title">'.$r["review_title"].'</p>
                            <p class="book-infos">'.$r["review_publisher"].' - '.$r["review_ean"].'</p>
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