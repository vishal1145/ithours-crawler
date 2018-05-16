<?php

$__DIR__						= str_replace('\\', '/', __DIR__);
$_SERVER['SCRIPT_FILENAME']		= str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$_SERVER['SCRIPT_DIRECTORY']	= substr($__DIR__, 0, strrpos($__DIR__, '/')+1);
$_SERVER['PROJECT_DIRECTORY']	= $_SERVER['SCRIPT_DIRECTORY'];
$_SERVER['BASE_PATH']			= substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));



if (!is_background()) {
	if (!array_key_exists('REDIRECT_URL', $_SERVER))
		$_SERVER['REDIRECT_URL']	= $_SERVER['REQUEST_URI'];

	if (strpos($_SERVER['REDIRECT_URL'], '?')) {
		$_SERVER['REDIRECT_URL']	= substr($_SERVER['REDIRECT_URL'], 0, strpos($_SERVER['REDIRECT_URL'], '?'));
	}

	$_SERVER['BASE_URL']	= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['BASE_PATH'].'/';
	$_SERVER['SELF_URL']	= array_key_exists('REDIRECT_URL', $_SERVER) ? $_SERVER['REDIRECT_URL'] : $_SERVER['PHP_SELF'];
}

$GLOBALS['current']	= array();

function __autoLoad($name) {
	$file_name	= $_SERVER['PROJECT_DIRECTORY'].'includes/class/'.strtolower($name).'.php';
	if (is_readable($file_name)) {
		require_once($file_name);
	}
}

function get_dbo() {
	if (!isset($dbo))
		static $dbo;
	if ($dbo)
		return $dbo;

	$dbo	= dbo::connect($GLOBALS['config']['db']);
	return $dbo;
}

function add_event($message, $type='info') {
	if (!array_key_exists('events', $_SESSION)) {
		$_SESSION['events']	= array();
	}
	$_SESSION['events'][]	= array('message'=>$message, 'type'=>$type);
}

function get_events($expunge=true) {
	if (!array_key_exists('events', $_SESSION)) {
		$_SESSION['events']	= array();
	}

	$events	= $_SESSION['events'];

	if ($expunge) {
		$_SESSION['events']	= array();
	}
	return $events;
}

function get_current_member() {
	static $member;

	if (isset($member)) {
		return $member;
	}

	if ($_SESSION['member']['id']) {
		$dbo	= get_dbo();
		return $member = $dbo->seek('members', $_SESSION['member']['id'], array('id','username','email','status'));
	} else {
		return array('id'=>null, 'username'=>'', 'email'=>'', 'status'=>'');
	}
}

function get_current_admin() {
	static $admin;

	if (isset($admin)) {
		return $admin;
	}

	if ($_SESSION['admin']['id']) {
		$dbo	= get_dbo();
		return $admin = $dbo->seek('admins', $_SESSION['admin']['id'], array('id','username','email','status'));
	} else {
		return array('id'=>null, 'username'=>'', 'email'=>'', 'status'=>'');
	}
}

function check_authentication($identity) {

	switch ($identity) {
		case 'member':
			if ($_SESSION['member']['id'])
				return;

			$_SESSION['member']['go_after_login']	= $_SERVER['REQUEST_URI'];

			header('Location: '.$_SERVER['BASE_PATH'].'/uye-girisi/');
			exit;

		break;
		case 'admin':
			if ($_SESSION['admin']['id'])
				return;

			$_SESSION['admin']['go_after_login']	= $_SERVER['REQUEST_URI'];

			header('Location: '.$_SERVER['BASE_PATH'].'/yonetim/kullanici-girisi/');
			exit;

		break;
	}

}

function get_mailer() {
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->SMTPAuth		= true;
	$mail->Charset		= 'UTF-8';
	$mail->Host			= $GLOBALS['config']['smtp']['server'];
	$mail->Username		= $GLOBALS['config']['smtp']['username'];
	$mail->Password		= $GLOBALS['config']['smtp']['password'];
	$mail->Port			= $GLOBALS['config']['smtp']['port'];
	$mail->setFrom($GLOBALS['config']['smtp']['username'], 'Mailer');
	$mail->isHTML(true);
	return $mail;
}

function get_settings() {
	static $settings;
	if (!isset($settings))
		$settings	= array();

	$dbo	= get_dbo();
	$rows	= $dbo->select('settings');

	foreach ($rows as $row) {
		switch ($row['type']) {
			case 'integer':
				$settings[$row['name']]	= intval($row['value']);
			break;
			case 'float':
				$settings[$row['name']]	= floatval($row['value']);
			break;
			case 'boolean':
				$settings[$row['name']]	= intval($row['value']) ? true : false;
			break;
			default:
				$settings[$row['name']]	= $row['value'];
			break;
		}
	}
	return $settings;
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

function generate_username($length=null) {
	$letters1	= array('b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z');
	$letters2	= array('a','e','i','o','u');
	$motives	= array('10','01','101');

	if (!$length) {
		$length	= rand(2, 6);
	}

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
				$str	.= $letters2[mt_rand(0, sizeof($letters2)-1)];
			break;
			case '1':
				$str	.= $letters1[mt_rand(0, sizeof($letters1)-1)];
			break;
		}
	}
	return $str;
}


function is_id($value) {
	return is_scalar($value) and preg_match('/(^[1-9]{1}$)|(^[1-9]+[0-9]*$)/', $value);
}

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

/**
 * Gönderilen dizi değişkenin ilk eleman indeksini döndürür.
 */
function array_first_key($array) {
	if (is_array($array))
		return key(array_slice($array, 0, 1, true));
}

/**
 * Gönderilen dizi değişkenin son eleman indeksini döndürür.
 */
function array_last_key($array) {
	if (is_array($array))
		return key(array_slice($array, -1, 1, true));
}
//-------------------------------------------------------------------------------------------------
function str2float($str) {
	if (is_int($str) or is_float($str) or is_null($str))
		return $str;

	$s	= preg_replace('/\s/', '', $str);
	if (strlen($s) and !preg_match('/^[\+\-]?[0-9.,]+$/', $s))
		return false;

	$s	= preg_replace('/([^0-9.,\-\+]+)/', '', $str);

	if (!strlen($s))
		return null;

	// Hem nokta hem virgül varsa;
	if (strstr($s, ',') and strstr($s, '.')) {
		// En son virgül geliyorsa;
		if (strrpos($s, '.') < strrpos($s, ',')) {
			// Noktayı yoket, virgülü nokta yap, dönüştürmeyi tamamla.
			$s		= str_replace('.', '', $s);
			$s		= str_replace(',', '.', $s);
			return floatval($s);
		// En son nokta geliyorsa;
		} else {
			// Virgülü yoket, dönüştürmeyi tamamla.
			$s		= str_replace(',', '', $s);
			return floatval($s);
		}

	// Yalnızca virgül varsa;
	} elseif (strstr($s, ',')) {
		// Virgül birden fazlaysa, binlik ayırıcı
		if (preg_match_all('/,/', $s, $found) > 1) {
			$s		= str_replace(',', '', $s);
			return floatval($s);
		} else {
			switch (strlen($s)-(strrpos($s, ',')+1)) {
				// Binlik ayırıcı
				case 3:
					$s		= str_replace(',', '', $s);
					return floatval($s);
				break;
				// Ondalık ayırıcı
				default:
					$s		= str_replace(',', '.', $s);
					return floatval($s);
				break;
			}
		}

	// Yalnızca nokta varsa;
	} elseif (strstr($s, '.')) {
		// Nokta birden fazlaysa, binlik ayırıcı
		if (preg_match_all('/\./', $s, $found) > 1) {
			$s		= str_replace('.', '', $s);
			return floatval($s);
		} else {
			switch (strlen($s)-(strrpos($s, '.')+1)) {
				// Binlik ayırıcı
				case 3:
					$s		= str_replace('.', '', $s);
					return floatval($s);
				break;
				// Ondalık ayırıcı
				default:
					return floatval($s);
				break;
			}
		}
	} else {
		return floatval($s);
	}
}
//-------------------------------------------------------------------------------------------------
/**
 * Şu anda çalışılan platformun Windows olup olmadığını tespit eder.
 */
function is_windows() {
	return PHP_OS == 'WINNT';
}
//-------------------------------------------------------------------------------------------------
/**
 * Şu anda arkaplânda çalışılıp çalışılmadığını tespit eder.
 */
function is_background() {
	return php_sapi_name() == 'cli';
}
//-------------------------------------------------------------------------------------------------
function xml_encode($xml_string) {
	return str_replace(array('&','<','>','"'), array('&amp;','&lt;','&gt;','&quot;'), $xml_string);
}
//-------------------------------------------------------------------------------------------------
function xml_decode($xml_string) {
	return str_replace(array('&amp;','&lt;','&gt;','&quot;'), array('&','<','>','"'), $xml_string);
}
//-------------------------------------------------------------------------------------------------
function get_hash_value($hash, $name, $type='', $default=null) {
	if (!is_real_hash($hash) or !array_key_exists($name, $hash))
		return $default;

	return get_value($hash[$name], $type, $default);
}
//-------------------------------------------------------------------------------------------------
function escape($value) {
	return escaper::escape($value, 'bs:e;sq:e');
}
//-------------------------------------------------------------------------------------------------
function get_value($value, $type='', $default=null) {

	switch($type) {
		case 'bool': case 'boolean':
			if (is_bool($value)) {
				return $value;
			} elseif (is_string($value)) {
				return in_array(trim(strtolower($value)), array('','false','0','f','not','no','n','off','disable','disabled','passive','inactive','deny','disallow','incorrect','invalid','none','null','nil','nothing')) ? false : true;
			} elseif (is_int($value) or is_float($value)) {
				return $value == 0 ? false : true;
			} elseif (is_null($value)) {
				return $default;
			} else {
				return $value ? true : false;
			}
		break;
		case 'int': case 'integer':
			if (is_null($value)) {
				return $default;
			} elseif (is_string($value)) {
				if (!strlen($value))
					return $default;
			}
			return intval($value);
		break;
		default:
			if (is_null($value))
				return $default;
			return $value;
		break;
	}

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
function format_value($value, $type, $settings=array()) {
	switch ($type) {
		case 'date': case 'time': case 'datetime': case 'long_datetime': case 'long_date': case 'long_time': case 'year': case 'month': case 'day':
			if (!strlen($value))
				return false;

			if (!array_key_exists('format', $settings) or !strlen($format=$settings['format'])) {
				if ($type == 'date') {
					$format	= $GLOBALS['config']['date_format'];
				} elseif ($type == 'time') {
					$format	= $GLOBALS['config']['time_format'];
				} elseif ($type == 'datetime') {
					$format	= $GLOBALS['config']['datetime_format'];
				} elseif ($type == 'long_date') {
					$format	= $GLOBALS['config']['long_date_format'];
				} elseif ($type == 'long_time') {
					$format	= $GLOBALS['config']['long_time_format'];
				} elseif ($type == 'long_datetime') {
					$format	= $GLOBALS['config']['long_datetime_format'];
				} elseif ($type == 'year') {
					$format	= 'Y';
				} elseif ($type == 'month') {
					$format	= 'm';
				} elseif ($type == 'day') {
					$format	= 'd';
				}
			}

			$datetime	= new DateTime('@'.$value);

			if (array_key_exists('timezone', $settings) and strlen($settings['timezone'])) {
				$datetime->setTimezone(new DateTimeZone($settings['timezone']));
			} elseif (isset($GLOBALS['current_timezone'])) {
				$datetime->setTimezone(new DateTimeZone($GLOBALS['current_timezone']));
			} else {
				$datetime->setTimezone(new DateTimeZone($GLOBALS['config']['default_timezone']));
			}

			$escape	= false;
			$value	= '';

			foreach (str_split($format) as $char) {
				if ($escape) {
					$value	.= $char;
					$escape	 = false;
					continue;
				}

				switch ($char) {
					case '\\':
						$escape	= true;
					break;
					case 'a': case 'A':
						$val	= $datetime->format('a');
						$value	.= $val;
					break;
					case 'd':
						$val	= $datetime->format('d');
						$value	.= $val;
					break;
					case 'D':
						$val	= $GLOBALS['config']['day_names_short'][intval($datetime->format('w')+1)];
						$value	.= $val;
					break;
					case 'F':
						$val	= $GLOBALS['config']['month_names'][intval($datetime->format('n'))];
						$value	.= $val;
					break;
					case 'g':
						$val	= $datetime->format('g');
						$value	.= $val;
					break;
					case 'G':
						$val	= $datetime->format('G');
						$value	.= $val;
					break;
					case 'h':
						$val	= $datetime->format('h');
						$value	.= $val;
					break;
					case 'H':
						$val	= $datetime->format('H');
						$value	.= $val;
					break;
					case 'i':
						$val	= $datetime->format('i');
						$value	.= $val;
					break;
					case 'j':
						$val	= $datetime->format('j');
						$value	.= $val;
					break;
					case 'l':
						$val	= $GLOBALS['config']['day_names'][intval($datetime->format('w')+1)];
						$value	.= $val;
					break;
					case 'm':
						$val	= $datetime->format('m');
						$value	.= $val;
					break;
					case 'M':
						$val	= $GLOBALS['config']['month_names_short'][intval($datetime->format('n'))];
						$value	.= $val;
					break;
					case 'n':
						$val	= $datetime->format('n');
						$value	.= $val;
					break;
					case 's':
						$val	= $datetime->format('s');
						$value	.= $val;
					break;
					case 't':
						$val	= $datetime->format('t');
						$value	.= $val;
					break;
					case 'y':
						$val	= $datetime->format('y');
						$value	.= $val;
					break;
					case 'Y':
						$val	= $datetime->format('Y');
						$value	.= $val;
					break;
					case 'w':
						$val	= $datetime->format('w');
						$value	.= $val;
					break;
					case 'z':
						$val	= $datetime->format('z');
						$value	.= $val;
					break;
					default:
						$value	.= $char;
					break;
				}
			}
		break;
	}
	return $value;
}

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

	return $data;
}

/**
 * Id numarası gönderilen yazar için, takip etme ve takip bırakma görevlerini
 * yeniden oluşturur. Önce varolan tüm "follow_author" ve "unfollow_author"
 * görevleri silinir, daha sonra yeniden oluşturulur.
 * Yönetici yeni bir yazar eklediğinde veya yazarın takipçi ayarlarını değiştirdiğinde
 * çağırılmalıdır.
 */
function refresh_author_tasks($author_id) {
	$dbo	= get_dbo();

	// Yazarı bul.
	if (!($author = $dbo->seek('authors', $author_id)))
		return;

	// Görevler arasında bulunan "follow_author" ve "unfollow_author" görevlerinin tamamını sil.
	$dbo->execute('DELETE FROM tasks WHERE type IN(\'follow_author\',\'unfollow_author\') AND author_id='.$author['id']);

	// Hedef takipçi sayısından, güncel takipçi sayısını çıkar.
	$target_count	= $author['target_follows'] - $author['current_follows'];

	$threshold_min	= $author['threshold_percent_min'];
	$threshold_max	= $author['threshold_percent_max'];

	// Rakam sıfırdan büyükse, takipçi ekleme görevi söz konusu.
	if ($target_count > 0) {

		$day = 0;
		$remain_count	= $target_count;

		while ($remain_count > 0) {
			$percent	= mt_rand($threshold_min * 1000, $threshold_max * 1000);
			$add_count	= ceil($target_count / 100 * ($percent / 1000));

			if ($add_count > $remain_count)
				$add_count = $remain_count;

			for ($i = 0; $i < $add_count; $i ++) {
				$dbo->insert('tasks', array('type'=>'follow_author', 'author_id'=>$author['id'], 'due'=>time() + mt_rand(86400*$day, 86400*($day+1))));
			}

			$remain_count -= $add_count;
			$day ++;
		}


	// Rakam sıfırdan küçükse, takip bırakma görevleri söz konusu.
	} elseif ($target_count < 0) {
		$target_count	= abs($target_count);
		$remain_count	= $target_count;
		$day = 0;

		while ($remain_count > 0) {
			$percent	= mt_rand($threshold_min * 1000, $threshold_max * 1000);
			$add_count	= ceil($target_count / 100 * ($percent / 1000));

			if ($add_count > $remain_count)
				$add_count = $remain_count;

			for ($i = 0; $i < $add_count; $i ++) {
				$dbo->insert('tasks', array('type'=>'unfollow_author', 'author_id'=>$author['id'], 'due'=>time() + mt_rand(86400*$day, 86400*($day+1))));
			}

			$remain_count -= $add_count;
			$day ++;
		}

	}
}



?>