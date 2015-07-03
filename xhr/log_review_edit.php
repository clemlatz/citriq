<?php

    $reviews = mysql_query("SELECT `review_id`, `review_url`, `review_excerpt`, `review_score` FROM `reviews` WHERE `review_id` = '".$_GET["review_id"]."' LIMIT 1");
    if($r = mysql_fetch_array($reviews)) {
        
        
        $result = '
            <form>
                <fieldset>
                
                    <input type="hidden" id="update_review_id" value="'.$r["review_id"].'" />
                
                    <label for="update_review_url">URL de la critique :</label>
                    <input type="text" id="update_review_url" class="textcon" value="'.$r["review_url"].'" />
                    <span id="update_review_urlContext" class="context">Adresse de la page o&ugrave; se trouve votre critique.<br /><em>Obligatoire</em></span>
                    <br />
                    
                    <label for="update_review_excerpt">Extrait :</label>
                    <textarea id="update_review_excerpt" class="textcon">'.$r["review_excerpt"].'</textarea>
                    <span id="update_review_excerptContext" class="context count">Un extrait de votre critique.<br /><span id="update_review_excerptCount">0</span>/<span id="review_excerptMax">512</span> caract&egrave;res<br /><em>Facultatif</em></span>
                    <br />
                    
                    <label for="update_review_score">Note :</label>
                    <input type="text" id="update_review_score" class="short textcon" maxlength="3" value="'.$r["review_score"].'" /> sur 100
                    <span id="update_review_scoreContext" class="context">Attention, votre note doit &ecirc;tre exprim&eacute;e en pourcentage et doit &ecirc;tre un nombre entier.<br />Ex : 3.5/5 = 70, 13/20 = 65.<br /><em>Facultatif</em></span>
                    <br />
                </fieldset>
            </form>
        ';
    }
?>