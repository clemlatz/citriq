<?php

	$reviews = mysql_query("SELECT COUNT(  `book_id` ) AS  `num` ,  `book_title`, `book_ean`, `book_id`
		FROM `books`
		GROUP BY  `book_ean`
		HAVING `num` > 1
		ORDER BY  `num` DESC ") or die(mysql_error());
	while($r = mysql_fetch_array($reviews)) {
		echo '<p>'.$r["book_id"].' | '.$r["num"].' | '.$r["book_title"].'</p>';
		//mysql_query("DELETE FROM `books` WHERE `book_id` = '".$r["book_id"]."'");
	}

?>