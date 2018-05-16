<?php

function ApiRequest($URL, $getVal=NULL, $proxy=NULL)
{
	if($getVal){
		$URL .= "?".$getVal;
	}
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36');
	curl_setopt($ch, CURLOPT_REFERER, $URL);
		if($proxy) {
			// ip:port
			
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    $response = curl_exec($ch);
    curl_close($ch);
	
    return $response;
 
}

header('Content-Type: application/json; charset=utf-8', true,200);
//$_SERVER["QUERY_STRING"]
$str = ApiRequest('https://ls.betradar.com/ls/feeds/?/betradar/tr/Europe:Istanbul/gismo/event_fullfeed', NULL , NULL);
//$str = ApiRequest("http://localhost/www/scoremobile/answer.json", NULL , NULL);

//echo $str;

$jsonData = json_decode($str);

echo $jsonData->doc[0]->data[0]->realcategories[0]->name;