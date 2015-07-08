<?php

    include("inc/mysql.php");
    include("inc/functions.php");
    
    if(auth()) $_LOG = auth('log');
    
    if(!empty($_GET["url"]) and file_exists("xhr/".$_GET["url"].".php")) {
        $rank = substr($_GET["url"],0,4);
        if($rank == "log_" and auth()) {
            $site = mysql_query("SELECT `site_id`, `site_url` FROM `sites` WHERE `user_id` = '".$_LOG["user_id"]."' LIMIT 1");
            if($_SITE = mysql_fetch_array($site)) include("xhr/".$_GET["url"].".php");
            else $result = "Erreur site";
        }
        elseif($rank == "log_" and !auth()) $result = "Erreur identification";
        else include("xhr/".$_GET["url"].".php");
        
        echo stripslashes($result);
    }
    
    mysql_close();
