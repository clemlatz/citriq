    <?php 
        if(filepath($b["book_ean"])) $b["image"] = '<img src="'.filepath($b["book_ean"],"url").'" alt="'.$b["book_title"].'" />';
        else $b["image"] = '';
        
        echo '
            <div class="book-cover">
                '.$b["image"].'
            </div>
            <div id="book-data">
                <h2>'.$b["book_title"].'</h2>
                <p class="book-author">Auteur : <a href="/?q='.$b["book_author"].'">'.$b["book_author"].'</a></p>
                <p class="book-publisher">Editeur : <a href="/?q='.$b["book_publisher"].'">'.$b["book_publisher"].'</a></p>
                <p class="book-isbn">ISBN : '.isbn($b["book_ean"],"ISBN").'</p>
                <br />
                <div id="book-buzz">
                    <a href="http://twitter.com/share" data-text="Toutes les critiques de '.$b["book_title"].' sur CITRIQ" class="twitter-share-button" data-count="horizontal" data-lang="fr">Tweeter</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
                    <g:plusone size="medium"></g:plusone>
                    <!-- a id="fb_share" name="fb_share" type="button_count" href="http://www.facebook.com/sharer.php">Partager</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script -->
                    <iframe src="http://www.facebook.com/plugins/like.php?app_id=133975366679339&amp;href=http%3A%2F%2Fcitriq.net%2F'.$b["book_ean"].'&amp;send=false&amp;layout=button_count&amp;width=200&amp;show_faces=false&amp;action=recommend&amp;colorscheme=light&amp;font=lucida+grande&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:200px; height:21px;" allowTransparency="true"></iframe>
                </div>
            </div><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>
			<div id="chosen_'.$b["book_ean"].'" class="invisible">
				<div class="book-cover"><img src="'.filepath($b["book_ean"],"url").'" class="mini" /></div>
				<div class="book-data">
					<p class="book-author">'.$b["book_author"].'</p>
					<p class="book-title">'.$b["book_title"].'</p>
					<p class="book-infos">'.$b["book_publisher"].' - '.$b["book_ean"].'</p>
				</div>
			</div>
        ';
    ?>
    <h3>Toutes les critiques</h3>
    <div id="reviews">
    <?php
		
		echo '<p id="addBook_'.$b["book_ean"].'" class="add pointer">Ajouter une critique pour ce livre</a></p>';
		
		$reviews = mysql_query("
            SELECT `review_title`, `review_author`, `review_ean`, `review_publisher`, `review_shorturl`, `review_excerpt`, `review_reviewer`, `review_source`, `review_score`, `site_name`
            FROM `reviews`
            JOIN `sites` USING(`site_id`)
            WHERE `review_ean` = '".$_GET["ean"]."' AND `review_called` != '0000-00-00 00:00:00' ORDER BY `review_insert` DESC") or die(mysql_error());
        if($r = mysql_fetch_array($reviews)) { 
        
            while($rx = mysql_fetch_array($reviews)) {
                if(!empty($rx["review_excerpt"])) $desc = addslashes($rx["review_excerpt"]);
            }
			
            mysql_data_seek($reviews,0);
            $other_reviews = '<br />';
            while($r = mysql_fetch_array($reviews)) {
                $score_img = ' &nbsp;';
                if(!empty($r["review_score"])) {
                    $score = round($r["review_score"] / 20);
                    for($i = 0; $i < $score; $i++) $score_img .= '<img src="/img/icon_star_1.png" alt="note" />';
                    for($i = 5; $i > $score; $i--) $score_img .= '<img src="/img/icon_star_0.png" alt="note" />';
					$score = '';
				}
                if(!empty($r["review_excerpt"])) {
                    if(!empty($r["review_source"]) and $r["review_source"] != $r["site_name"]) $r["site_name"] = '<a href="/'.$r["review_shorturl"].'">'.$r["review_source"].'</a>'; else $r["site_name"] = '<a href="/'.$r["review_shorturl"].'">'.$r["site_name"].'</a>';
                    if(!empty($r["review_reviewer"])) $reviewer = $r["review_reviewer"].' ('.$r["site_name"].')'; else $reviewer = $r["site_name"];
                    echo '<br /><p><span class="review_excerpt">&laquo;&nbsp;'.stripslashes($r["review_excerpt"]).'&nbsp;&raquo;</span><br />&#8594; '.$reviewer.' '.score($r["review_score"]).'</a></p>';
                }
                else {
                    if(!empty($r["review_reviewer"])) $reviewer = ' de '.$r["review_reviewer"]; else $reviewer = NULL;
                    if(!empty($r["review_source"]) and $r["review_source"] != $r["site_name"]) $r["site_name"] = ' dans '.$r["review_source"]; else $r["site_name"] = ' sur '.$r["site_name"];
                    $other_reviews .= '<p><a href="/'.$r["review_shorturl"].'">Lire la critique '.$reviewer.$r["site_name"].' '.score($r["review_score"]).'</a></p>';
                    //$other_reviews .= '<p><a href="/'.$r["review_shorturl"].'">Lire la critique de '.$r["site_name"].'</a></p>';
                }
            }
            echo $other_reviews;
        ?>
    </div>

<?php

    }
    //else header("Location: /?q=".$_GET["ean"]);

?>