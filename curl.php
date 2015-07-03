<?php

	$url = 'http://philemont.over-blog.net/';

	$curl = curl_init($url);
	
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
	$return = curl_exec($curl);
	
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	
	curl_close($curl);
	
	echo 'Got response '.$httpCode.' for '.$url;