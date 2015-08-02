<?php

	define("SITE_PATH", dirname(__DIR__)."/");
	
	error_reporting(E_ALL ^ E_DEPRECATED);
	
	function biblys($x, $m = "book")
	{
		if ($m == "look")
		{
			// API Url
			$url = 'http://api.biblys.fr/v0/articles/search/'.rawurlencode($x);
			
			// API Call
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$return = curl_exec($curl);
			$result = json_decode($return, true);
			
			if (!empty($result['meta']['results']))
			{
				foreach ($result['articles'] as $b)
				{
					$res .= '
						<div id="chosen_'.$b["article_ean"].'" class="invisible">
							<div class="book-cover"><img src="'.$b['article_cover'].'" class="mini" /></div>
							<div class="book-data">
								<p class="book-author">'.$b["article_authors"].'</p>
								<p class="book-title">'.$b["article_title"].'</p>
								<p class="book-infos">'.$b["article_publisher"].' - '.$b["article_ean"].'</p>
							</div>
						</div>
						<img src="'.$b['article_cover'].'" class="clic" height="160" onClick="chooseBook('.$b["article_ean"].');" />
					';
				}
			}
			
			return $res;
		}
		elseif($m == "book")
		{
			// API Url
			$url = 'http://api.biblys.fr/v0/articles/get/'.rawurlencode($x);
			
			// API Call
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$return = curl_exec($curl);
			$result = json_decode($return, true);
			
			$r = array();
			if (!empty($result['meta']['results']))
			{	
				$r["book_title"] = $result['article'][0]['article_title'];
				$r["book_author"] = $result['article'][0]['article_authors'];
				$r["book_publisher"] = $result['publisher'][0]['publisher_name'];
				$r["book_biblys_id"] = $result['article'][0]['article_id'];
				foreach ($result["media"] as $m)
				{
					if($m["type"] == "cover") $r["image"] = $m['url'];
				}
			}
			
			return utf8_array_decode($r);
		}
	}




function utf8_array_decode($input) {
    $return = array();
    foreach ($input as $key => $val) 
    {
      $k = utf8_decode($key);
      $return[$k] = utf8_decode($val);
    }
    return $return;          
}

function amazon($x,$m = "book") {
	global $results;
    $x = utf8_encode($x);
    $public_key = "AKIAJIGPXJ6YGVTJJXGA";
	$private_key = "Av62y5M7mXeY86FI1Yil3CMKQSJd07UVAEbiM9wr";
	$params = array("Operation"=>"ItemSearch","SearchIndex"=>"Books","Keywords"=>$x,"ResponseGroup"=>"Large");
	$params["Service"] = "AWSECommerceService";
	$params["AWSAccessKeyId"] = $public_key;
	$params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
	$params["Version"] = "2009-03-31";
	$params["AssociateTag"] = "librys-20";
	$params["ProductGroup"] = "Book";
	ksort($params);
	$canonicalized_query = array();
	foreach ($params as $param=>$value) {
		$param = str_replace("%7E", "~", rawurlencode($param));
		$value = str_replace("%7E", "~", rawurlencode($value));
		$canonicalized_query[] = $param."=".$value;
	}
	$canonicalized_query = implode("&", $canonicalized_query);
	$string_to_sign = "GET"."\n"."ecs.amazonaws.fr"."\n"."/onca/xml"."\n".$canonicalized_query;
	$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
	$signature = str_replace("%7E", "~", rawurlencode($signature));
	$request = "http://"."ecs.amazonaws.fr"."/onca/xml"."?".$canonicalized_query."&Signature=".$signature;
	while(empty($response)) {
		$response = @file_get_contents($request);
		if(empty($response)) sleep (0.1);
		$try++;
		if($try > 10) break;
	}
    if ($response === False) return False;
    elseif($m == "book") { // Un livre
        $pxml = simplexml_load_string($response);
        if ($pxml === False) return False; // no xml
        else {
            $item = $pxml->Items->Item;
            $l["book_amazon_asin"] = utf8_decode($item->ASIN);
            if(!empty($item->ItemAttributes->Title)) $l["book_title"] = utf8_decode($item->ItemAttributes->Title);
            if(!empty($item->ItemAttributes->Author)) $l["book_author"] = utf8_decode($item->ItemAttributes->Author);
            if(!empty($item->ItemAttributes->Publisher)) $l["book_publisher"] = utf8_decode($item->ItemAttributes->Publisher);
            if(!empty($item->MediumImage->URL)) $l["image"] = $item->MediumImage->URL;
            $l["book_noosfere_id"] = NULL;
            $l["book_item"] = NULL;
            if(!empty($l["book_title"])) return $l;
            else return False;
        }
    } // Recherche
    else {
        $pxml = simplexml_load_string($response);
        if($pxml === False);
        else {
            $res = '';
            foreach($pxml->Items->Item as $item) {
                $l["title"] = utf8_decode($item->ItemAttributes->Title);
                $l["author"] = utf8_decode($item->ItemAttributes->Author);
                $l["publisher"] = utf8_decode($item->ItemAttributes->Publisher);
                $l["ASIN"] = $item->ASIN;
                $l["EAN"] = $item->ItemAttributes->EAN;
                $l["ISBN"] = ean2isbn($l["EAN"]);
                if(!empty($item->MediumImage->URL)) {
					if(!array_search($l["EAN"],$results)) {
						$result = '
							<div id="chosen_'.$l["EAN"].'" class="invisible">
								<div class="book-cover"><img src="'.$item->MediumImage->URL.'" class="mini" /></div>
								<div class="book-data">
									<p class="book-author">'.$l["author"].'</p>
									<p class="book-title">'.$l["title"].'</p>
									<p class="book-infos">'.$l["publisher"].' - '.$l["EAN"].'</p>
								</div>
							</div>
							<img src="'.$item->MediumImage->URL.'" class="clic" onClick="chooseBook('.$l["EAN"].');" />
						';
						if(!empty($l["ISBN"])) $res .= $result;
						array_push($results,$l["EAN"]);
					}
                }
            }
            return $res;
           
        }
        
    }
}

function noosfere($x) {
    $url = "http://www.noosfere.org/biblio/xml_livres.asp?resume=1&isbn=".$x;
	
    $xml = simplexml_load_file($url); //or echo 'Erreur dans le flux noosfere :<br /> <a href="'.$url.'">'.$url.'</a>';
    $num = count($xml->Livre);
  
    $x = NULL;
    foreach($xml->Livre as $n)
    {
        $x["book_title"] = $n->Titre;      
        $x["book_publisher"] = $n->Editeur;
        $x["book_item"] = $n['IdItem'];
        $x["book_noosfere_id"] = $n['IdLivre'];
        $x["image"] = $n->Couverture['LienCouverture'];
      
        $x = utf8_array_decode($x);
      
        $x["book_author"] = NULL;
        // Intervenants doit être après utf8_array_decode !!!
        if(!empty($xml->Livre->Intervenants->Intervenant)) {
            foreach($xml->Livre->Intervenants->Intervenant as $Intervenant) {
                if((string) $Intervenant['TypeIntervention'] == "Auteur") {
					if(isset($x["book_author"])) $x["book_author"] .= ', ';
                    $x["book_author"] .= trim(utf8_decode($Intervenant->Prenom)." ".utf8_decode($Intervenant->Nom));
                }
            }
        }
        
        $x["book_amazon_asin"] = NULL;
        
        return $x;
    }
}

	// Identification
	function auth($type = "user") {
		global $_GET;
		global $_SITE;
		
		// Connexion (retour axys)
		if(isset($_GET["UID"])) {
			setcookie("UID",$_GET["UID"],0,'/');
			//mysql_query("UPDATE `Users` SET `user_key` = '".$_GET["UID"]."', `adresse_ip` = '".$_SERVER["REMOTE_ADDR"]."', `DateConnexion` = '".$todaynow."' WHERE `id` = '".$_LOG[id]."'  ");
			$goto = explode("?UID=","http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
			$goto = explode("&UID=",$goto[0]);
			mysql_close();
			header('Location: '.$goto[0]);
			echo '';
		}
		
		// Identification si cookie UID
		if(!empty($_COOKIE["UID"])) {
			$req = "http://axys.me/call?key=hfatyuTr1K7ur5UX9vBNALduR2se2GF9&uid=".$_COOKIE["UID"];
			if($xml = simplexml_load_file($req)) {
				if($xml->result == "OK") {
					$_LOG = array();
					foreach($xml as $k => $v) {
						$_LOG[$k] = $v;
					}
					if(!empty($_LOG["user_email_key"])) header("Location: http://axys.me/"); // Si l'email n'a pas été vérifié
					if($type == "log") return $_LOG;
				}
			}	
		}
		
		if($type == "user" and !empty($_LOG)) return True;
		return False;
	}

function ean2isbn($x) {
    $x = str_replace(" ","",str_replace("-","",$x));
    if(strlen($x) == 13) {
        $a = substr($x,0,3);
        $b = substr($x,3,1);
        $cd = substr($x,4,8);
        $k1 = substr($x,4,1);
        $k3 = substr($x,4,3);
        $k4 = substr($x,4,4);
        $k5 = substr($x,4,5);
        $k6 = substr($x,4,6);
        $k7 = substr($x,4,7);
        $e = substr($x,12,1);
        if($a == "978" and $b == "2") {
            if($k1 == "0" or $k1 == "1") $l = "2";
            elseif($k3 >= "200" and $k3 <= "349") $l = "3";
            elseif($k5 >= "35000" and $k5 <= "39999") $l = "5";
            elseif($k3 >= "400" and $k3 <= "699") $l = "3";
            elseif($k4 >= "7000" and $k4 <= "8399") $l = "4";
            elseif($k5 >= "84000" and $k5 <= "89999") $l = "5";
            elseif($k6 >= "900000" and $k6 <= "949999") $l = "6";
            elseif($k7 >= "9500000" and $k7 <= "9999999") $l = "7";
            $c = substr($cd,0,$l);
            $d = substr($cd,$l,8-$l);
            return $a."-".$b."-".$c."-".$d."-".$e;
        }
    }
    else return $x;
}

	function log_error($x) {
		global $_GET;
		mysql_query("INSERT INTO `errors`(`error_text`,`error_get`) VALUES('".addslashes($x)."','".json_encode($_GET)."')") or die(mysql_error());
	}

    function debug($x) {
        global $_DEBUG;
		//log_error($x);
        if($_DEBUG) echoj($x);
    }

    function echoj($x) {
        global $_MODE;
		global $_DEBUG;
        $x = addslashes($x);
        if($_MODE != 'img' || $_DEBUG) echo "document.write('".$x."'); \n";
    }
    

function filepath($x, $m = "test", $t = "cover") {
    $filedir = "img/".$t."/".substr($x,-3,3)."/"; // Chemin relatif vers le dossier o doit se trouver le fichier
    $filepath = SITE_PATH.$filedir.$x.".jpg";
    $fileurl = "/".$filedir.$x.".jpg";
	
    if($m == "url") return $fileurl;
    if($m == "test") {        
        if(file_exists($filepath)) return true;
        else return false;
    }
    elseif($m == "path") {
        if(!is_dir(SITE_PATH.$filedir)) mkdir(SITE_PATH.$filedir) or die("erreur : ".SITE_PATH.$filedir); // Si le dossier n'existe pas, on le cre
        return $filepath;
    }
}

	function isbn($x,$m = "check") {
        $original = $x;
        // ISBN Version 1.1 par Clement Bourgoin http://labs.nokto.net/isbn/
        $x = str_replace(" ","",$x); // On retire les eventuels espaces
        $x = str_replace("-","",$x); // On retire les eventuels tirets (dans le cas d'un ISBN)
        if(strlen($x) == 10) $x = substr($x,0,9);
        elseif(strlen($x) == 13) $x = substr($x,0,12); // Si c'est un code a 10 ou 13 chiffres, on retire le dernier (cle de controle)
        if(strlen($x) == 12) { // Si c'est un code a 13 chiffres
                $A = substr($x,0,3); // On extrait le code produit (3 premiers chiffres)
                $x = substr($x,3,13); //  Et on le retire du code
                if ($A != 978 && isset($B) && $B != 979) $result = False;
        }
        if(empty($A)) $A = "978"; // Si le code produit n'est pas precise (ISBN10), on le cree
        if(is_numeric($x) and strlen($x) == 9) { // Si tout va bien, le code fait maintenant 9 chiffres
            if($A == "978") $B_length = 1;
			elseif($A == "979") $B_length = 2;
			$B = substr($x,0,$B_length); // On extrait le code langue
			$CD = substr($x,$B_length); // On extrait le code editeur (C) et le code livre (D)
			//die($CD);
			if($B == 2 or $B == 10) { // Si le code langue est bien egal a 2 (francais)
				//die($CD);
				// Pour distinguer C et D, on recupere des extraits de C
					$CD1 = substr($CD,0,1);
					$CD3 = substr($CD,0,3);
					$CD4 = substr($CD,0,4);
					$CD5 = substr($CD,0,5);
					$CD6 = substr($CD,0,6);
					$CD7 = substr($CD,0,7);
				// Et on compare ces plages de valeurs a ceux indiques dans http://isbn-international.org/agency?rmpdf=1&sort=agency
				// pour connaitre la taille de C
				if($B == 2) {
					if($CD1 == "0" or $CD1 == "1") $l = "2";
					elseif($CD3 >= "200" and $CD3 <= "349") $l = "3";
					elseif($CD5 >= "35000" and $CD5 <= "39999") $l = "5";
					elseif($CD3 >= "400" and $CD3 <= "699") $l = "3";
					elseif($CD4 >= "7000" and $CD4 <= "8399") $l = "4";
					elseif($CD5 >= "84000" and $CD5 <= "89999") $l = "5";
					elseif($CD6 >= "900000" and $CD6 <= "949999") $l = "6";
					elseif($CD7 >= "9500000" and $CD7 <= "9999999") $l = "7";
				} elseif($B = 10) {
					if($CD1 == "0" or $CD1 == "1") $l = "2";
					elseif($CD3 >= "200" and $CD3 <= "699") $l = "3";
					elseif($CD4 >= "7000" and $CD4 <= "8999") $l = "4";
					elseif($CD5 >= "90000" and $CD5 <= "97599") $l = "5";
					elseif($CD6 >= "976000" and $CD5 <= "999999") $l = "5";
				}
				// Enfin, on en deduit C et D
				$C = substr($CD,0,$l);
				$D = substr($CD,$l,8-$l);
		
				// Pour les ISBN-13 et EAN
				if($m == "EAN" or $m == "EAN13" or $m == "ISBN" or $m == "ISBN13") {
						// Calcul de la cle (E) pour un ISBN-13
						$k = $A.$B.$C.$D;
						$k = str_split($k);
						$i = 0; $i2 = 0; $r = 0; 
						while($i2 <= 11)
						{
								if($i2%2 == 0) $p = "1";
								else $p = "3";
								if(!empty($k[$i])) {
										$r += $k[$i] * $p;
										if($k[$i] != "-") $i2++;
								} else $i2++;
								$i++;
						}
						$q = floor($r/10);
						$E = 10 - ($r - $q * 10);
						if($E == "10") $E = "0";
						if($m == "EAN" or $m == "EAN13") $result = $A.$B.$C.$D.$E;
						elseif($m == "ISBN" or $m == "ISBN13") $result = $A."-".$B."-".$C."-".$D."-".$E;
				}
				
				// Pour les ISBN-10
				elseif($m == "ISBN10") {
						$k = $B.$C.$D;
						$k = str_split($k);
						$m = 10; $t = 0;
						foreach($k as $K) {
								$K = $K*$m;
								$t += $K;
								$m--;
						}
						$t = ($t % 11);
						if($t == 0) $E = 0;
						elseif($t == 1) $E = 'X';
						else $E = 11 - $t;
						return $B."-".$C."-".$D."-".$E;
				}
				else $result = True;
			} else $result = False;
        } else $result = False;
		if($result && $m == "check") return True;
		elseif(!$result && $m == "check") return False;
		elseif($result) return $result;
		else return $original;
	}

function isbn2ean($x)
{
  $x = str_replace("-","",$x);
  $x = str_replace(" ","",$x);
  if(strlen($x) == 9) $x = $x."X";
  if(strlen($x) == 10) // ISBN10
  {
    $x = substr($x,0,-1);
    $x = "978".$x;
    $code = $x;
    $x = str_split($x);
    $i = 0;
    while($i2 <= 11)
    {
      if($i2%2 == 0) $p = "1";
      else $p = "3";
      $r += $x[$i] * $p;
      if($x[$i] != "-") $i2++;
      $i++;
    }
    $q = floor($r/10);
    $x = 10 - ($r - $q * 10);
    if($x == "10") $x = "0";
    $x = $code.$x;
  }
  return $x;
}

	function score($x) {
		if($x != '0') return '<span class="score"><span style="width: '.$x.'%"></span></span>';
	}

function short_url($num) {
    $string = NULL;
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($chars);
    while($num >= $len) {
        $mod = bcmod($num,$len);
        $num = bcdiv($num,$len);
        $string = $chars[$mod].$string;
    }
    $string = $chars[$num].$string;        
    return $string;
}

    function _date($x,$m) {
        if($x == "0000-00-00 00:00:00" or empty($x)) return NULL;
        
        $x = explode(" ",$x);
        if(empty($x[1])) $x[1] = "00:00:00";
        
        $d = explode("-",$x[0]);
        $h = explode(":",$x[1]);
        
        $t = mktime($h[0],$h[1],$h[2],$d[1],$d[2],$d[0]);
        $N = date("N",$t);
        
        // Traduction mois
            if($d[1] == "01") $mois = "janvier";
        elseif($d[1] == "02") $mois = "février";
        elseif($d[1] == "03") $mois = "mars";
        elseif($d[1] == "04") $mois = "avril";
        elseif($d[1] == "05") $mois = "mai";
        elseif($d[1] == "06") $mois = "juin";
        elseif($d[1] == "07") $mois = "juillet";
        elseif($d[1] == "08") $mois = "août";
        elseif($d[1] == "09") $mois = "septembre";
        elseif($d[1] == "10") $mois = "octobre";
        elseif($d[1] == "11") $mois = "novembre";
        elseif($d[1] == "12") $mois = "décembre";
        else $mois = "?";
        
        // Traduction jour de la semaine
            if($N == 1) $jour = "lundi";
        elseif($N == 2) $jour = "mardi";
        elseif($N == 3) $jour = "mercredi";
        elseif($N == 4) $jour = "jeudi";
        elseif($N == 5) $jour = "vendredi";
        elseif($N == 6) $jour = "samedi";
        elseif($N == 7) $jour = "dimanche";
        
        $trans = array( // Pour le Samedi 25 septembre 2010 à 16h34
          "d" => $d[2], // 25 (avec zéro)
          "j" => date("j",$t), // 25
          "l" => $jour, // samedi
          "L" => ucwords($jour), // Samedi
          "m" => $d[1], // 09
          "f" => $mois, // septembre
          "F" => ucwords($mois), // Septembre
          "Y" => $d[0], // 2010
          "H" => $h[0], // 16
          "i" => $h[1] // 34
        );
        
        return strtr($m,$trans);
    }
	
	// Get MySQL credentials
    include("inc/mysql.php");
	
	// Connect to MySQL
	if(mysql_connect($db["host"],$db["user"],$db["pass"])) {
		mysql_select_db($db["base"]);
		mysql_set_charset('utf8'); // Encodage de la connexion MySQL
	}
	else die("<h1>Maintenance du site en cours...</h1><p>Merci de votre compr&#233;hension !</p>");
