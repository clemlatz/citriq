<?php

	define("VERSION", "1.1.3");

    include("inc/functions.php");

    // Variables GET
    if(isset($_GET["q"])) $q = $_GET["q"]; else $q = NULL;

    // Lancement de la session
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '1');
    ini_set('url_rewriter.tags', '');
    session_start();

	// Identification
	if(auth()) {
		$_LOG = auth('log');
		$user_site = mysql_query("SELECT `site_id`, `site_url` FROM `sites` WHERE `user_id` = '".$_LOG["user_id"]."' LIMIT 1") or die("Erreur : ".mysql_error());
        if($_SITE = mysql_fetch_array($user_site)) $ok = 1;
    }

    // Inscription
    if($_POST) {
        $content = NULL; $headers = NULL;
        $headers .= "From: ".$_POST["user_email"]." <".$_POST["user_email"].">\n";
        $headers .= "Content-Type: text/html; charset=iso-8859-1\n";
        foreach($_POST as $k => $v) {
            $content .= '<p>'.$k.' : '.$v.'</p>';
        }
        mail("cb@nokto.net","CITRIQ | Nouveau site",$content,$headers) or die('erreur');
        $_MESSAGE = '<p class="success">Votre demande a bien été enregistrée, elle sera traitée sous 48h.</p>';
    }

	$_TITLE = 'CITRIQ - Toutes les critiques littéraires';
	$_OPENGRAPH = NULL;

    // Livres
	if(!empty($_GET["page"])) {
		if($_GET["page"] == 'book' and $_GET["ean"]) {
			$books = mysql_query("SELECT `book_title`, `book_author`, `book_publisher`, `book_ean` FROM `books` WHERE `book_ean` = '".$_GET["ean"]."' LIMIT 1");
			if($b = mysql_fetch_array($books)) {
				$desc = 'Toutes les critiques pour '.$b["book_title"].' de '.$b["book_author"].' ('.$b["book_publisher"].') sur CITRIQ';
				$_TITLE = $b["book_title"].' de '.$b["book_author"].' sur CITRIQ';
				$_OPENGRAPH = '
					<meta property="og:title" content="'.$b["book_title"].' de '.$b["book_author"].'" />
					<meta property="og:type" content="book" />
					<meta property="og:url" content="http://citriq.net/'.$b["book_ean"].'" />
					<meta property="og:description" content="'.$desc.'" />
					<meta property="og:site_name" content="CITRIQ" />
					<meta name="description" content="'.$desc.'" />
				';
				if(filepath($b["book_ean"])) $_OPENGRAPH .= '<meta property="og:image" content="http://citriq.net/'.filepath($b["book_ean"],"url").'" />';
			}
		}
	}


?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Content-Style-Type" content="text/css" />
        <meta http-equiv="Content-Language" content="fr" />

        <meta name="google-site-verification" content="RCun84e5ljf2SbmRVDa3xQVBvLQ1HJMENfuXg672-pg" />

		<meta name="viewport" content="width=device-width">

        <link type="text/css" media="screen" rel="stylesheet" href="/css/ui-lightness/jquery-ui-1.8.6.custom.css" />
        <link type="text/css" media="screen" rel="stylesheet" href="/css/styles.css" />

        <link href='https://fonts.googleapis.com/css?family=PT+Sans' rel='stylesheet' type='text/css'>

        <link rel="alternate" type="application/rss+xml" title="Derni&egrave;res critiques" href="http://feeds.feedburner.com/citriq" />

        <title><?php echo $_TITLE; ?></title>

		<?php echo $_OPENGRAPH; ?>

        <script type="text/javascript" src="/js/jquery-1.5.2.min.js"></script>
        <script type="text/javascript" src="/js/jqueryui-1.8.6.js"></script>
        <script type="text/javascript" src="/js/jquery.tablesorter.min.js"></script>
        <script type="text/javascript" src="/js/jquery.tablesorter.pager.js"></script>
        <script type="text/javascript" src="/js/index.js?<?php echo VERSION; ?>"></script>

        <script type="text/javascript">
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', 'UA-1439349-21']);
            _gaq.push(['_trackPageview']);
            (function() {
              var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
              ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
              var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
    </head>
    <body>

        <input type="hidden" id="user_uid" value="<?php echo (isset($_SESSION["user_key"]) ? $_SESSION["user_key"] : null) ?>" />

    <?php

        if(auth() and $_SITE) {

            // Ajouter une critique
            echo '
                <div id="add" class="dialog">
                    <h3>Etape 1 : Rechercher le livre critiqu&#233;</h3>
                    <div id="add_step1" class="step">
                        <form id="addQueryForm">
                            <fieldset>
                                <label for="addQuery">Livre critiqu&#233; :</label> <input type="text" class="search" id="addQuery" />
                                <input type="image" src="/img/search.png" />
                                <div id="addResults"><br /><em>Entrez un titre de livre, un auteur, un &#233;diteur ou un ISBN.</em></div>
                            </fieldset>
                        </form>
                    </div>
                    <h3>Etape 2 : Ajouter votre critique &#224; CITRIQ</h3>
                    <div id="add_step2" class="step">
                        <div id="choosenBook">
                            <p class="add clic">Choisir un autre livre</p>
                        </div>
                        <form id="reviewAddForm" style="clear: right;">
                            <fieldset>
                                <input type="hidden" id="review_ean" />

                                <label for="review_url">URL de la critique :</label>
                                <input type="text" id="review_url" class="textcon" value="'.$_SITE["site_url"].'" />
                                <span id="review_urlContext" class="context">Adresse de la page o&ugrave; se trouve votre critique.<br /><em>Obligatoire</em></span>
                                <br />

                                <label for="review_excerpt">Extrait :</label>
                                <textarea id="review_excerpt" class="textcon"></textarea>
                                <span id="review_excerptContext" class="context count">Un extrait de votre critique.<br /><span id="review_excerptCount">0</span>/<span id="review_excerptMax">512</span> caract&egrave;res<br /><em>Facultatif</em></span>
                                <br />

                                <label for="review_score">Note :</label>
                                <input type="text" id="review_score" class="short textcon" maxlength="3" /> / 100
                                <span id="review_scoreContext" class="context">Attention, votre note doit &ecirc;tre exprim&eacute;e en pourcentage et doit &ecirc;tre un nombre entier.<br />Ex : 3.5/5 = 70, 13/20 = 65.<br /><em>Facultatif</em></span>
                                <br />

                                <input type="submit" id="submitReviewAddForm" value="Ajouter la critique &#224; CITRIQ" />
                            </fieldset>
                        </form>
                    </div>
                    <h3 style="clear: both;">Etape 3 : Ajouter CITRIQ &#224; votre site</h3>
                    <div id="add_step3" class="step">
                        <p>Votre critique a &eacute;t&eacute; ajout&eacute;e &agrave; CITRIQ.</p>
                        <p>Elle sera valid&eacute;e automatiquement d\'ici 24h au plus tard. Pour permettre cette validation, &agrave; votre tour maintenant d\'ajouter le widget CITRIQ &agrave; votre critique en y ins&eacute;rant le code javascript ci-dessous :</p>
                        <textarea id="citriq_widget"></textarea>
                        <p>Ce widget affichera automatiquement les liens vers les autres critiques du m&ecirc;me livre enregistr&eacute;es dans CITRIQ.</p>
                        <p>Si votre site/blog ne supporte pas le javascript, vous pouvez utiliser le code suivant :</p>
                        <textarea id="citriq_widget_nojs"></textarea>
                        <p class="add clic">Ajouter une autre critique</p>
                    </div>
					<p>Vous avez un blog sous Wordpress ?<br />Il existe <a href="http://nokto.net/citriq-wordpress-plugin/">une extension</a> pour automatiser le référencement de vos critiques.</p>
					<p>Découvrez également <a href="http://nokto.net/citriq-automatiser-le-referencement-de-vos-critiques-litteraires-avec-php/">des fonctions PHP et Python</a>.</p>
				</div>
            ';
            // Mes critiques
            echo '
                <div id="reviewEdit" class="dialog"></div>
                <div id="myReviews" class="dialog">
                    <div id="pager"></div>
                    <table>
                        <thead class="pointer">
                            <tr>
                                <th></th>
                                <th class="left pointer">Livre</th>
                                <th class="left pointer">Auteur</th>
                                <th class="left pointer">Editeur</th>
                                <th>Vues</th>
                                <th>Clics</th>
                            </tr>
                        </thead>
                        <tbody>
            ';
            //$_SITE["site_id"] = 26;
            $myReviews = mysql_query("SELECT `review_id`, `review_title`, `review_author`, `review_ean`, `review_publisher`, `review_views`, `review_hits`, `review_shorturl`, `review_called` FROM `reviews` WHERE `site_id` = '".$_SITE["site_id"]."' ORDER BY `review_pub_date` DESC");
            while($mR = mysql_fetch_array($myReviews)) {
                echo '
                            <tr id="review_'.$mR["review_id"].'">';
                if($mR["review_called"] == '0000-00-00 00:00:00') echo '<td><img src="/img/icon_error_32x32.png" title="Erreur Widget !" /></td>';
                else echo '<td></td>';
                echo '
                                <td><a href="/'.$mR["review_ean"].'">'.$mR["review_title"].'</a></td>
                                <td><a href="/?q='.$mR["review_author"].'">'.$mR["review_author"].'</a></td>
                                <td><a href="/?q='.$mR["review_publisher"].'">'.$mR["review_publisher"].'</a></td>
                                <td class="center">'.$mR["review_views"].'</td>
                                <td class="center">'.$mR["review_hits"].'</td>
                                <td class="center"><a href="/'.$mR["review_shorturl"].'">voir</a></td>
                                <td class="center pointer updateReview">modifier</td>
                                <td class="center pointer deleteReview">supprimer</td>
                            </tr>
                ';
            }

            echo '
                        </tbody>
                    </table>
                    <br />
                </div>
            ';// Mes visites
            echo '
                <div id="myVisits" class="dialog">
                    <table>
                        <thead class="pointer">
                            <tr>
                                <th class="left pointer">Critique</th>
                                <th class="left pointer" style="width: 300px;">Source</th>
                                <th class="left pointer">Date</th>
                            </tr>
                        </thead>
                        <tbody>
            ';
            //$_SITE["site_id"] = 26;
            $myVisits = mysql_query("SELECT `review_title`, `review_url`, `clic_referer`, `clic_date` FROM `clics` JOIN `reviews` USING(`review_id`) JOIN `sites` USING(`site_id`) WHERE `site_id` = '".$_SITE["site_id"]."' ORDER BY `clic_date` DESC");
            while($mV = mysql_fetch_array($myVisits)) {
                echo '
                            <tr>
                                <td><a href="'.$mV["review_url"].'">'.$mV["review_title"].'</a></td>
                                <td><div style="width: 400px; overflow: hidden;"><a href="'.$mV["clic_referer"].'">'.$mV["clic_referer"].'</a></div></td>
                                <td>'._date($mV["clic_date"],'j/m/Y&nbsp;H:m').'</td>
                            </tr>
                ';
            }

            echo '
                        </tbody>
                    </table>
                    <br />
                </div>
            ';
        } elseif(auth()) {
            echo '
                <div id="add" class="dialog">
                    <p class="error">L\'ajout de critiques est pour le moment réservé aux beta-testeurs.</p>
					<h3>Inscription de votre site au beta-test</h3>
                    <p>Pour ajouter une critique, il faut en premier lieu inscrire votre site au beta-test. Les inscriptions étant validées manuellement, vous recevrez une réponse sous 48h et pourrez commencer à enregistrer des critiques. Dans ce but, merci de renseigner le formulaire ci-dessous.</p>
                    <form action="/" method="post">
                        <fieldset>

                            <input type="hidden" name="user_id" value="'.$_LOG["user_id"].'" />
                            <input type="hidden" name="user_email" value="'.$_LOG["user_email"].'" />

                            <label for="site_name">Nom du site/blog :</label>
                            <input type="text" name="site_name" for="site_name" />
                            <br />

                            <label for="site_url">Adresse du site/blog :</label>
                            <input type="text" name="site_url" for="site_url" value="http://" />
                            <br />

                            <input type="submit" value="Envoyer" />

                        </fieldset>
                    </form>
                </div>
            ';

        } else {
            echo '
                <div id="add" class="dialog">
                    <h3><a href="https://axys.me/login/">Connectez-vous</a> ou <a href="https://axys.me/#Inscription">inscrivez-vous</a> pour participer à Citriq.</h3>
                </div>
            ';
        }


    ?>
        </div>
		<div id="mobile-header">
			<img src="img/citriq.png" class="logo" alt="Citriq">
		</div>
        <div id="page">
            <div id="header">
                <h1>CITRIQ</h1>
                <h2>Toutes les critiques litt&eacute;raires</h2>
            </div>

<?php

    if(!empty($_GET["q"])) {
        $qex = explode(" ",$_GET["q"]);
        $req = "AND "; $i = 0;
        foreach($qex as $qexa) {
            if($i != 0) $req .= " AND ";
            $qexa = addslashes($qexa);
            $req .= "(`review_title` LIKE '%".$qexa."%' OR `review_author` LIKE '%".$qexa."%' OR `review_publisher` LIKE '%".$qexa."%' OR `site_name` LIKE '%".$qexa."%'  OR `review_reviewer` LIKE '%".$qexa."%'  OR `review_source` LIKE '%".$qexa."%' OR `review_ean` LIKE '%".isbn($qexa,"EAN")."%')";
            $i++;
        }
        $_GET["o"] = "news";
    } else $req = NULL;

    $activeheadline = NULL; $activenews = NULL; $activetop = NULL; $activesites = NULL; $o = NULL;
    if(empty($_GET["page"])) {
        if(empty($_GET["o"])) $_GET["o"] = "headline";
        if($_GET["o"] == "top") { $order = "`review_num` DESC, `review_pub_date` DESC"; $activetop = ' class="active" '; } else $activetop = NULL; // Les livres les plus critiques
        if($_GET["o"] == "news") { $order = "`review_pub_date` DESC"; $activenews = ' class="active" '; } else $activenews = NULL; // Les critiques les plus recentes
        if($_GET["o"] == "headline") { // Les livres les plus critiques cette semaine
            $sem = date("Y-m-d",strtotime("- 7 days"));
            $req = " AND `review_pub_date` > '$sem'";
            $order = "`review_num` DESC, `review_pub_date` DESC";
            $activeheadline = ' class="active" ';
        } else $activeheadline = NULL;
    } else {
        if($_GET["page"] == "sites") $activesites = ' class="active"';
    }

    $num = mysql_num_rows(mysql_query("SELECT `review_id` FROM `reviews` JOIN `sites` USING(`site_id`)  WHERE `review_title` != '0'".$req." GROUP BY `review_ean`"));

    // Pages
    if(empty($_GET["p"])) $_GET["p"] = 0; // page actuelle
    if(empty($pp)) $pp = 10; // nombre par page
    $np = $_GET["p"] + $pp; // page suivante
    $pr = $_GET["p"] - $pp; // page prcdente
    if(!empty($_GET["q"])) $query = '&q='.$_GET["q"]; else $query = "";
    if(!empty($_GET["o"])) $o = '&o='.$_GET["o"]; else $order = "";
    if($num < $pp) $np = $num;
    if($pr >= 0) $previous = '<a href="/?p='.$pr.$query.$o.'">&#171; Pr&#233;c.</a> '; else $previous = NULL;
    if($np < $num) $next = ' <a href="/?p='.$np.$query.$o.'">Suiv. &#187;</a>'; else $next = NULL;
    $p = $_GET["p"]+1;
    $pages = '<p id="pages">'.$previous.$p.'-'.$np.' sur '.$num.$next;

    // Order by
    if(!isset($_GET["o"])) {
        if(isset($_GET["q"])) $_GET["o"] = "news";
        else $_GET["o"] = "headline";
    }

    if(isset($_GET["q"])) $custom_rss = '<a href="http://citriq.net/rss?q='.$_GET["q"].'"><img src="/img/icon_feed_16x16.png" alt="feed" /></a>';
    else $custom_rss = NULL;

    echo '
                <div id="nav" class="right">
                <a href="/"><img src="/img/citriq.png" class="logo" /></a>
                <p'.$activeheadline.'><a href="/?o=headline">&Agrave; la une</a></p>
                <p'.$activenews.'><a href="/?o=news">Derni&#232;res critiques</a></p>
                <p'.$activetop.'><a href="/?o=top">Livres les plus critiqu&#233;s</a></p>
                <br />
                <p class="clic add">Ajouter une critique</p>
	';
	if(auth() and $_SITE) {
		echo '
			<p class="pointer showMyReviews">Mes critiques</p>
			<p class="pointer showMyVisits">Mes visites</p>
		';
	}
	echo '
                <br />
                <p'.$activesites.'><a href="/pages/network">R&eacute;seau Citriq</a></p>
                <br />
                <p><a href="http://nokto.net/tag/citriq">Blog</a></p>
                <p><a href="http://nokto.net/contact">Contact</a></p>
                <br />
                <p>
                    <a href="http://feeds.feedburner.com/citriq"><img src="/img/icon_feed_16x16.png" alt="feed" /></a>
                    <a href="http://www.facebook.com/pages/citriqnet/111785432222874"><img src="/img/icon_facebook_16x16.png" alt="facebook" /></a>
                    <a href="http://twitter.com/_citriq"><img src="/img/icon_twitter_16x16.png" alt="twitter" /></a>
                </p>
            </div>
            <div id="search">
                '.$pages.'
                <form action="/">
                    <fieldset>
                        <input type="text" name="q" class="search" value="'.$q.'" />
                        <input type="image" src="/img/search.png" />
                '.$custom_rss.'
                    </fieldset>
                </form>
            </div>
    ';

    if(empty($_GET["page"])) $_GET["page"] = "home";

    echo '<div id="content" class="page_'.$_GET["page"].'">';
    include("php/".$_GET["page"].".php");
    echo '</div>';
?>

        </div>

		<ul id="addToAxysMenu" class="hidden">
		</ul>

		<?php
		if(auth()) echo '<script type="text/javascript" src="https://axys.me/widget.php?UID='.$_COOKIE["UID"].'"></script>';
					else echo '<script type="text/javascript" src="https://axys.me/widget.php"></script>';
		?>

    </body>
</html>

<?php mysql_close(); ?>
