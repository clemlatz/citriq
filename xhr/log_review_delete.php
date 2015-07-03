<?php
    if(!empty($_POST)) {
        if(mysql_query("DELETE FROM `reviews` WHERE `review_id` = '".$_POST["review_id"]."' AND `user_id` = '".$_LOG["id"]."' LIMIT 1")) $result = "OK";
        else $result = "Impossible de supprimer cette critique !";
    }
?>