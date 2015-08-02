<h2>Sitemaps</h2>
<?php
    include("../inc/functions.php");
    
    $map = NULL; $u = 0;

    $books = mysql_query("SELECT `review_ean`, `review_insert` FROM `reviews` WHERE `review_title` != '' GROUP BY `review_ean` ORDER BY `review_insert` DESC") or die(mysql_error());
    while($b = mysql_fetch_array($books)) {
        $map .= '<url>'."\n";
        $map .= '  <loc>http://citriq.net/'.$b["review_ean"].'</loc>'."\n";
        $map .= '  <lastmod>'._date($b["review_insert"],"Y-m-d").'</lastmod>'."\n";
        $map .= '  <changefreq>weekly</changefreq>'."\n";
        $map .= '</url>'."\n";
        $u++;
    }


// GENERATION DES SITEMAPS

    $x = NULL;
    $x .= '<'.'?'.'xml version="1.0" encoding="UTF-8"'.'?'.'> '."\n";
    $x .= '<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'."\n";
    $x .= $map;
    $x .= '</urlset>'."\n";
    
    // Ecriture du fichier
    $sitemap = '../sitemap.xml';
    $sitemap = fopen($sitemap,"w");
    fputs($sitemap,$x);
    fclose($sitemap);
    
    // Compression du fichier
    $gzx = gzencode($x, 9);
    $gzsitemap = '../sitemap.xml.gz';
    $gzsitemap = fopen($gzsitemap,"w");
    fputs($gzsitemap,$gzx);
    fclose($gzsitemap);
    
    echo '<p class="success"><a href="/sitemap.xml">/sitemap.xml</a> : '.$u.' urls trait�es (<a href="http://www.validome.org/google/validate?url=http://www.librys.fr/sitemap_'.$s["id"].'.xml&lang=en&googleTyp=SITEMAP">validation</a>)</p>';

    mysql_close();
