<?php
 
function aws_signed_request($params)
{
    
    $public_key = "AKIAIGVQJKSHYVNIUZXA";
    $private_key = "iyHJim571et2F3G0ViVGQn7/LkkhHQEs9sd/mTw1";
    $region = "fr";
 
    // some paramters
    $method = "GET";
    $host = "ecs.amazonaws.".$region;
    $uri = "/onca/xml";
 
    // additional parameters
    $params["Service"] = "AWSECommerceService";
    $params["AWSAccessKeyId"] = $public_key;
    // GMT timestamp
    $params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
    // API version
    $params["Version"] = "2009-03-31";
 
    // sort the parameters
    ksort($params);
 
    // create the canonicalized query
    $canonicalized_query = array();
    foreach ($params as $param=>$value)
    {
        $param = str_replace("%7E", "~", rawurlencode($param));
        $value = str_replace("%7E", "~", rawurlencode($value));
        $canonicalized_query[] = $param."=".$value;
    }
    $canonicalized_query = implode("&", $canonicalized_query);
 
    // create the string to sign
    $string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
 
    // calculate HMAC with SHA256 and base64-encoding
    $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
 
    // encode the signature for the request
    $signature = str_replace("%7E", "~", rawurlencode($signature));
 
    // create request
    $request = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;
 
    // do request
    $response = @file_get_contents($request);
 
    if ($response === False)
    {
        return False;
    }
    else
    {
        // parse XML
        $pxml = simplexml_load_string($response);
        if ($pxml === False)
        {
            return False; // no xml
        }
        else
        {
            return $pxml;
        }
    }
}

$pxml = aws_signed_request(array("Operation"=>"ItemLookup","ItemId"=>"B001FY2N14","ResponseGroup"=>"Small"));
if ($pxml === False)
{
    echo "Did not work.\n";
}
else
{
    if (isset($pxml->Items->Item->ItemAttributes->Title))
    {
        echo $pxml->Items->Item->ItemAttributes->Title, "\n";
    }
    else
    {
        echo "Could not find item.\n";
    }
}


?>