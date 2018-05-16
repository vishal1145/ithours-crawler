<?php


/**
 *
 * (C)onvert, (E)scape, (R)emove
 * singlequote ', doublequote ", backslash \	CER
 * ampersand &									CR
 * htmltag <tag>								CR
 * (L)eft, (R)ight, (B)oth
 * trim											LRB
 *
 *
 * @static
 * @package	text
 * @version	1.0
 * @author	Ahmet Þenocak <ahmet@setrim.com>
 */
class escaper {

	/**
	 * 
	 * @param	mixed	Dizi veya string
	 * @param	string	Properties Source Text
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function escape($_, $pst='bs:e;sq:e') {
		$ps		= explode(';', preg_replace('/\s/', '', $pst));
		for ($i = 0; $i < sizeof($ps); $i ++) {
			if (!$ps[$i])	continue;
			$nav	= explode(':', strtolower($ps[$i]));
			$n		= $nav[0];
			$v		= (isset($nav[1])) ? $nav[1]:'';
			switch($n) {
				case 'trim':
					$_	= self::trim($_, $v);
				break;
				case 'sq': case 'singlequotes': case 'singlequote':
					$_	= self::single_quotes($_, $v);
				break;
				case 'dq': case 'doublequotes': case 'doublequote':
					$_	= self::double_quotes($_, $v);
				break;
				case 'bs': case 'backslashs': case 'backslash': case 'backslashes':
					$_	= self::back_slashes($_, $v);
				break;
				case 'ht': case 'htmltags': case 'htmltag':
					$_	= self::html_tags($_, $v);
				break;
				case 'a': case 'ampersands': case 'ampersand':
					$_	= self::ampersands($_, $v);
				break;
				case 'bt': case 'backticks': case 'backtick':
					$_	= self::backticks($_, $v);
				break;
				default:
					throw new exception('self::Escape - Bilinmeyen deðer: '.$n);
				break;
			}
		}
		return $_;
	}

	/**
	 * Boþluklarý atar. Dizi deðiþken gönderilirse
	 * özyinelemeli olarak çalýþýr.
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function trim($_, $p='b') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::trim($v, $p);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'b': case 'both':
					$_	= trim($_);
				break;
				case 'l': case 'left':
					$_	= ltrim($_);
				break;
				case 'r': case 'right':
					$_	= rtrim($_);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function single_quotes($_, $p='e') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::single_quotes($v, $p);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'e': case 'escape':
					$_ = str_replace("'", "\\'", $_);
				break;
				case 'c': case 'convert':
					$_ = str_replace("'", '&apos', $_);
				break;
				case 'r': case 'remove':
					$_ = str_replace("'", '', $_);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function double_quotes($_, $p='e') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::double_quotes($v, $p);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'e': case 'escape':
					$_ = str_replace('"', '\\"', $_);
				break;
				case 'c': case 'convert':
					$_ = str_replace('"', '&quot;', $_);
				break;
				case 'r': case 'remove':
					$_ = str_replace('"', '', $_);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function quotes($_, $p='e') {
		return self::double_quotes(self::single_quotes($_, $p), $p);
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function back_slashes($_, $p='e') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::back_slashes($v, $p);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'e': case 'escape':
					$_ = str_replace('\\', '\\\\', $_);
				break;
				case 'r': case 'remove':
					$_ = str_replace('\\', '', $_);
				break;
				case 's': case 'strip':
					$_ = stripslashes($_);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @param	string	Ýzin verilen HTML imleri. (REMOVE modunda kullanýlýr)
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function html_tags($_, $p='c', $at='') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::html_tags($v, $p, $at);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'c': case 'convert':
					$_	= str_replace('<', '&lt;', $_);
					$_	= str_replace('>', '&gt;', $_);
				break;
				case 'r': case 'remove':
					$_	= strip_tags($_, $at);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @param	string	Ýzin verilen HTML imleri. (REMOVE modunda kullanýlýr)
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function ampersands($_, $p='c') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::ampersands($v, $p);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'c': case 'convert':
					$_	= str_replace('&', '&amp;', $_);
				break;
				case 'r': case 'remove':
					$_	= str_replace('&', '', $_);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 *
	 * @param	mixed	Dizi veya string
	 * @param	string
	 * @param	string	Ýzin verilen HTML imleri. (REMOVE modunda kullanýlýr)
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function backticks($_, $p='r') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::backticks($v, $p);
		} else {
			$p	= strtolower($p);
			switch($p) {
				case 'c': case 'convert':
					$_	= str_replace('`', '&#96;', $_);
				break;
				case 'r': case 'remove':
					$_	= str_replace('`', '', $_);
				break;
			}
		}
		return $_;
	}

	/**
	 *
	 * @param	mixed
	 * @param	string
	 * @return	mixed	Dizi veya string
	 * @access	public
	 * @static
	 */
	public static function white_spaces($_, $p='r') {
		if (is_array($_)) {
			foreach ($_ as $n => $v)
				$_[$n]	= self::white_spaces($v, $p);			
		} else {
			switch($p) {
				case 'r': case 'remove':
					$_	= preg_replace('/\s/', '', $_);
				break;
			}
		}
		return $_;
	}

}
?>
