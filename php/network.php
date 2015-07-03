<?php

    echo '
        <h2>Sites participants</h2>
        <ul>
    ';

    $sites = mysql_query("SELECT `site_id`, `site_name`, `site_url`, COUNT(`review_id`) AS `site_num` FROM `sites` JOIN `reviews` USING(`site_id`) WHERE `site_id` != '0' GROUP BY `site_id` ORDER BY `site_num` DESC") or die(mysql_error());
    while($s = mysql_fetch_array($sites)) {
        echo '<li><a href="'.$s["site_url"].'">'.$s["site_name"].'</a> (<a href="/?q='.$s["site_name"].'">'.$s["site_num"].'</a>)</li>';
    }

    echo '
        </ul>
        <h2>Chroniqueurs</h2>
        <ol>
    ';

    $reviewers = mysql_query("SELECT `review_reviewer`, COUNT(`review_id`) AS `reviewer_num` FROM `reviews` WHERE `review_reviewer` != '' GROUP BY `review_reviewer` ORDER BY `reviewer_num` DESC") or die(mysql_error());
    while($r = mysql_fetch_array($reviewers)) {
        echo '<li>'.$r["review_reviewer"].'</a> (<a href="/?q='.$r["review_reviewer"].'">'.$r["reviewer_num"].'</a>)</li>';
    }
    
    echo '</ol>';
    
    


    $users = mysql_query("SELECT `Email` FROM `citriq`.`sites` JOIN `tys`.`Users` ON `user_id` = `id`") or die(mysql_error());
    while($u = mysql_fetch_array($users)) {
        //echo $u["Email"].', ';
    }

?>