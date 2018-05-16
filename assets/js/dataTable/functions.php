<?php

function get_dbo($index='local') {
	static $dbos;
	if (!isset($dbos))
		$dbos	= array();

	if (array_key_exists($index, $dbos))
		return $dbos[$index];

	if (array_key_exists($index, $GLOBALS['config']['db'])) {
		$dbos[$index]	= dbo::connect($GLOBALS['config']['db'][$index]);
		return $dbos[$index];
	}
}

function get_add_button_html($link) {
	return '<link rel="stylesheet" type="text/css" href="http://isvar.com/but.css" /><div class="cf block"><a class="button button--brand button--apply js-smooth-scrollto buttonFloat" href="http://isvar.com/apply.php?url='.urlencode($link).'&amp;redirect=true"  target="_blank">Bu ilana başvurmak için tıklayınız</a></div>';
}

//-------------------------------------------------------------------------------------------------
/**
 * Gönderilen metindeki aksanlı karakterleri
 * en yakın İngilizce karaktere dönüştürür.
 *
 */
function to_en($str) {
	return str_replace(
		array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή'),
		array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'A', 'a', 'E', 'e', 'O', 'o', 'O', 'w', 'I', 'i', 'i', 'i', 'Y', 'u', 'u', 'u', 'H', 'n'),
	$str);
}
//-------------------------------------------------------------------------------------------------
function str2url($str, $keep_cases=false, $keep_accents=false) {
	if (!$keep_accents) {
		$str	= to_en($str);
	}
	if (!$keep_cases) {
		$str	= strtolower($str);
	}
	$str	= trim($str);
	$return	= '';
	for ($i = 0; $i < mb_strlen($str, 'UTF-8'); $i ++) {
		$char	= mb_substr($str, $i, 1, 'UTF-8');

		switch ($char) {
			case 'a': case 'b': case 'c': case 'd': case 'e': case 'f': case 'g': case 'h': case 'i': case 'j': case 'k': case 'l': case 'm': case 'n': case 'o': case 'p': case 'q': case 'r': case 's': case 't': case 'u': case 'v': case 'w': case 'x': case 'y': case 'z':
			case '0': case '1': case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9':
			case 'A': case 'B': case 'C': case 'D': case 'E': case 'F': case 'G': case 'H': case 'I': case 'J': case 'K': case 'L': case 'M': case 'N': case 'O': case 'P': case 'Q': case 'R': case 'S': case 'T': case 'U': case 'V': case 'W': case 'X': case 'Y': case 'Z':
			case 'À': case 'Á': case 'Â': case 'Ã': case 'Ä': case 'Å': case 'Æ': case 'Ç': case 'È': case 'É': case 'Ê': case 'Ë': case 'Ì': case 'Í': case 'Î': case 'Ï': case 'Ð': case 'Ñ': case 'Ò': case 'Ó': case 'Ô': case 'Õ': case 'Ö': case 'Ø': case 'Ù': case 'Ú': case 'Û': case 'Ü': case 'Ý': case 'ß': case 'à': case 'á': case 'â': case 'ã': case 'ä': case 'å': case 'æ': case 'ç': case 'è': case 'é': case 'ê': case 'ë': case 'ì': case 'í': case 'î': case 'ï': case 'ñ': case 'ò': case 'ó': case 'ô': case 'õ': case 'ö': case 'ø': case 'ù': case 'ú': case 'û': case 'ü': case 'ý': case 'ÿ': case 'Ā': case 'ā': case 'Ă': case 'ă': case 'Ą': case 'ą': case 'Ć': case 'ć': case 'Ĉ': case 'ĉ': case 'Ċ': case 'ċ': case 'Č': case 'č': case 'Ď': case 'ď': case 'Đ': case 'đ': case 'Ē': case 'ē': case 'Ĕ': case 'ĕ': case 'Ė': case 'ė': case 'Ę': case 'ę': case 'Ě': case 'ě': case 'Ĝ': case 'ĝ': case 'Ğ': case 'ğ': case 'Ġ': case 'ġ': case 'Ģ': case 'ģ': case 'Ĥ': case 'ĥ': case 'Ħ': case 'ħ': case 'Ĩ': case 'ĩ': case 'Ī': case 'ī': case 'Ĭ': case 'ĭ': case 'Į': case 'į': case 'İ': case 'ı': case 'Ĳ': case 'ĳ': case 'Ĵ': case 'ĵ': case 'Ķ': case 'ķ': case 'Ĺ': case 'ĺ': case 'Ļ': case 'ļ': case 'Ľ': case 'ľ': case 'Ŀ': case 'ŀ': case 'Ł': case 'ł': case 'Ń': case 'ń': case 'Ņ': case 'ņ': case 'Ň': case 'ň': case 'ŉ': case 'Ō': case 'ō': case 'Ŏ': case 'ŏ': case 'Ő': case 'ő': case 'Œ': case 'œ': case 'Ŕ': case 'ŕ': case 'Ŗ': case 'ŗ': case 'Ř': case 'ř': case 'Ś': case 'ś': case 'Ŝ': case 'ŝ': case 'Ş': case 'ş': case 'Š': case 'š': case 'Ţ': case 'ţ': case 'Ť': case 'ť': case 'Ŧ': case 'ŧ': case 'Ũ': case 'ũ': case 'Ū': case 'ū': case 'Ŭ': case 'ŭ': case 'Ů': case 'ů': case 'Ű': case 'ű': case 'Ų': case 'ų': case 'Ŵ': case 'ŵ': case 'Ŷ': case 'ŷ': case 'Ÿ': case 'Ź': case 'ź': case 'Ż': case 'ż': case 'Ž': case 'ž': case 'ſ': case 'ƒ': case 'Ơ': case 'ơ': case 'Ư': case 'ư': case 'Ǎ': case 'ǎ': case 'Ǐ': case 'ǐ': case 'Ǒ': case 'ǒ': case 'Ǔ': case 'ǔ': case 'Ǖ': case 'ǖ': case 'Ǘ': case 'ǘ': case 'Ǚ': case 'ǚ': case 'Ǜ': case 'ǜ': case 'Ǻ': case 'ǻ': case 'Ǽ': case 'ǽ': case 'Ǿ': case 'ǿ': case 'Ά': case 'ά': case 'Έ': case 'έ': case 'Ό': case 'ό': case 'Ώ': case 'ώ': case 'Ί': case 'ί': case 'ϊ': case 'ΐ': case 'Ύ': case 'ύ': case 'ϋ': case 'ΰ': case 'Ή': case 'ή':
				$return	.= $char;
			break;
			case ' ': case "\t": case "\n": case '.': case ':': case ',': case ';': case '&': case '/': case "\\": case '-': case '_':
				$return	.= '-';
			break;
		}
	}

	$return	= preg_replace('/-+/', '-', $return);
	if (isset($return[0]) and $return[0] == '-')
		$return	= substr($return, 1);
	if (strlen($return) > 0  and $return[strlen($return)-1] == '-')
		$return	= substr($return, 0, -1);

	return $return;
}
//-------------------------------------------------------------------------------------------------
function is_id($value) {
	return is_scalar($value) and preg_match('/(^[1-9]{1}$)|(^[1-9]+[0-9]*$)/', $value);
}
//-------------------------------------------------------------------------------------------------
/**
 * Gönderilen değerin, gerçekten sayısal dizi olup olmadığını sınar.
 * Yani dizinin anahtarlarında yalnızca sayı indeksi bulunmalıdır.
 */
function is_real_array($value) {
	if (!is_array($value))
		return false;
	if (!sizeof($value))
		return true;

	$keys	= array_keys($value);
	foreach ($keys as $key) {
		$key	= strval($key);
		if (!ctype_digit($key) or (strlen($key) > 1 and $key[0] == '0'))
			return false;
	}
	return true;
}

/**
 * Gönderilen değerin, gerçekten ilişkisel dizi olup olmadığını sınar.
 * Yani dizinin anahtarlarında sayı indeksi bulunmamalıdır.
 */
function is_real_hash($value) {
	if (!is_array($value))
		return false;
	if (!sizeof($value))
		return true;

	$keys	= array_keys($value);
	foreach ($keys as $key) {
		$key	= strval($key);
		if (ctype_digit($key))
			return false;
	}
	return true;
}

function generate_key($length=32) {
	$letters	= array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$numbers	= array('0','1','2','3','4','5','6','7','8','9');
	$specials	= array('_','-');
	$motives	= array('10','01','101');

	$motive		= $motives[0];
	for (;;) {
		if (strlen($motive) >= $length)
			break;
		$motive	.= $motives[mt_rand(0, sizeof($motives)-1)];
	}

	$str	= '';
	for ($i = 0; $i < $length; $i ++) {
		switch ($motive[$i]) {
			case '0':
				$str	.= $numbers[mt_rand(0, sizeof($numbers)-1)];
			break;
			case '2':
				$str	.= $specials[mt_rand(0, sizeof($specials)-1)];
			break;
			default:
				$str	.= $letters[mt_rand(0, sizeof($letters)-1)];
			break;
		}
	}
	return $str;
}

function wait_until($condition, $sleep=1, $timeout=120) {
	$started	= time();
	while (true) {
		try {
		$result	= $condition();
		if ($result)
			return $result;
		} catch (exception $e) {}
		echo '...'."\n";

		if ((time() - $started) >= $timeout) {
			throw new exception('TIMEOUT');
		}

		sleep($sleep);
	}
}

function ApiRequestCookie($URL, $REFERER=null, $HEADERS=array(), $getVal=NULL, $proxy=NULL){

	$COOKIEFILE ='_cookies.txt';

	if($getVal){
		$URL .= "?".$getVal;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	if (is_array($proxy) and array_key_exists('domain', $proxy)) {

		curl_setopt($ch, CURLOPT_PROXY, $proxy['domain']);

		if (array_key_exists('username', $proxy) and array_key_exists('password', $proxy)) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'].':'.$proxy['password']);
		}

		if (array_key_exists('port', $proxy)) {
			curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']);
		}

		if (array_key_exists('type', $proxy)) {
			curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy['type']);
		} else {
			curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
		}

	}

    if (sizeof($HEADERS)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $HEADERS);
	}
    if ($REFERER) {
		curl_setopt($ch, CURLOPT_REFERER, $REFERER);
	}
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $COOKIEFILE);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIEFILE);
	curl_setopt($ch, CURLOPT_HEADER, 0);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_URL,   $URL);
	
	$data = curl_exec($ch);

	//echo $data."\n\n\n";

	return $data."END_OF_HTML";
}
?>