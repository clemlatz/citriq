<?php

	if(!empty($_GET["q"])) {
		//$_GET["q"] = utf8_encode($_GET["q"]);
		//if(isbn($_GET["q"])) $_GET["q"] = isbn($_GET["q"],"EAN");
        $qex = explode(" ",$_GET["q"]);
        $req = "AND "; $i = 0;
        foreach($qex as $qexa) {
            if($i != 0) $req .= " AND ";
            $qexa = addslashes($qexa);
            $req .= "(`book_title` LIKE '%".$qexa."%' OR `book_author` LIKE '%".$qexa."%' OR `book_publisher` LIKE '%".$qexa."%' OR `book_ean` like '%".$qexa."%')";
            $i++;
        }
    } else $req = NULL;
	
	$books = mysql_query("SELECT `book_ean`, `book_title`, `book_author`, `book_publisher` FROM `books` WHERE `book_title` != '' ".$req) or die('?'.mysql_error());
	$res = ''; $r = 0; $results[0] = 0;
	global $results;
	while($b = mysql_fetch_array($books)) {
		if(filepath($b["book_ean"])) {	
			$res .= '
				<div id="chosen_'.$b["book_ean"].'" class="hidden">
					<div class="book-cover"><img src="'.filepath($b["book_ean"],"url").'" class="mini" /></div>
					<div class="book-data">
					    <p class="book-author">'.$b["book_author"].'</p>
					    <p class="book-title">'.$b["book_title"].'</p>
					    <p class="book-infos">'.$b["book_publisher"].' - '.$b["book_ean"].'</p>
					</div>
				</div>
				<img src="'.filepath($b["book_ean"],"url").'" class="clic" style="max-height: 160px;" onClick="chooseBook('.$b["book_ean"].');" />
			';
			array_push($results,$b["book_ean"]);
			$r++;
		}
	}
	
	if($r < 10) $res .= biblys($_GET["q"],"look");
	if($r < 10) $res .= amazon(utf8_decode($_GET["q"]),"look");
	
	if(empty($res)) $res = '<em>Aucun r&#233;sultat pour &#171; '.utf8_decode($_GET["q"]).' &#187;</em>';
	
	$result = '<br />'.$res;
	
	//print_r($results);
	
?>