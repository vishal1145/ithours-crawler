<?php

class validator {

	/**
	 *
	 */
	private static $last_reason			= '';


	/**
	 *
	 *
	 */
	public static function check(&$value, $type='string', $settings=array(), $default=null) {

		self::$last_reason	= '';

		// Dizi kontrolleri.
		if (get_hash_value($settings, 'array', 'bool', false)) {


		// Tekil kontrolleri.
		} else {

			if (method_exists('validator', 'check_'.$type)) {
				return eval('return self::check_'. $type . '($value, $settings);');
			} else {
				return self::check_string($value, $settings);
			}

		}

	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_string(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		return true;
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_md5(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		if (preg_match('/^[a-fA-F0-9]{32}$/', $value)) {
			$value	= strtolower($value);
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_sha1(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		if (preg_match('/^[a-fA-F0-9]{40}$/', $value)) {
			$value	= strtolower($value);
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_id(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		return preg_match('/(^[1-9]{1}$)|(^[1-9]+[0-9]*$)/', $value);
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_username(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		if (!preg_match('/^[a-z\-\_]+[a-z0-9\-\_]*$/', $value))
			return false;

		if (strlen($value) < 3 or strlen($value) > 32)
			return false;

		return true;
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_password(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		if (!preg_match('/^\S+$/', $value))
			return false;

		if (strlen($value) < 8 or strlen($value) > 32)
			return false;

		return true;
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_email(&$value, $settings) {
		$value	= trim($value);
		if (!strlen($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);
		}

		if (false === ($atpos = strrpos($value, '@')))
			return false;

		$login	= substr($value, 0, $atpos);
		$host	= substr($value, $atpos+1);

		if (!preg_match('/^[a-zA-Z0-9+_.-]{1,64}$/', $login) or $login[0]=='.' or $login[strlen($login)-1]=='.' or strstr($login, '..'))
			return false;

		$items	= explode('.', $host);

		if (sizeof($items) < 2)
			return false;

		for ($i = 0; $i < sizeof($items); $i ++) {
			if (!preg_match('/^[a-zA-Z0-9-]{1,63}$/', $items[$i]) or $items[$i][0]=='-' or $items[$i][strlen($items[$i])-1]=='-')
				return false;
		}
		return true;
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_number(&$value, $settings) {
		if (is_null($value)) {
			return get_hash_value($settings, 'empty', 'bool', false);

		} elseif (is_bool($value)) {
			// false ise null kabul et.
			if (!$value) {
				return get_hash_value($settings, 'empty', 'bool', false);
			}
			// true ise 1 kabul et.
			$value	= 1;

		} elseif (!is_int($value) and !is_float($value)) {
			$value	= str2float(strval($value));

			if ($value === false)
				return false;

			if (is_null($value)) {
				return get_hash_value($settings, 'empty', 'bool', false);
			}
		}

		if (strstr($value, '.')) {
			$decimal_length	= get_hash_value($settings, 'decimal_length', 'int', 0);
			list($whole, $decimal)	= explode('.', $value);

			if (strlen($decimal) > $decimal_length) {
				if (get_hash_value($settings, 'decimal_truncate') == 'ceil') {
					$value	= to_ceil($value, $decimal_length);
				} elseif (get_hash_value($settings, 'decimal_truncate') == 'floor') {
					$value	= to_floor($value, $decimal_length);
				} elseif (get_hash_value($settings, 'decimal_truncate') == 'round' or get_hash_value($settings, 'decimal_truncate', 'bool', true)) {
					$value	= round($value, $decimal_length);
				} else {
					return false;
				}
			}

		}

		// Sıfıra izin veriliyor mu? (varsayılan: verilmez)
		if ($value == 0 and !get_hash_value($settings, 'zero', 'bool', false))
			return false;

		// Negatif sayılara izin veriliyor mu? (varsayılan: verilmez)
		if ($value < 0 and !get_hash_value($settings, 'negative', 'bool', false))
			return false;

		if (strlen(get_hash_value($settings, 'min')) and $value < floatval(get_hash_value($settings, 'min'))) {
			if (get_hash_value($settings, 'truncate', 'bool', false))
				$value = floatval(get_hash_value($settings, 'min'));
			else
				return false;
		}

		if (strlen(get_hash_value($settings, 'max')) and $value > floatval(get_hash_value($settings, 'max'))) {
			if (get_hash_value($settings, 'truncate', 'bool', false))
				$value = floatval(get_hash_value($settings, 'max'));
			else
				return false;
		}

		return true;
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_decimal(&$value, $settings) {
		return self::internal($value, 'number', array('decimal_length'=>10), $settings);
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_money(&$value, $settings) {
		return self::internal($value, 'number', array('decimal_length'=>2), $settings);
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_year(&$value, $settings) {
		return self::internal($value, 'number', array('min'=>1000, 'max'=>9999), $settings);
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_month(&$value, $settings) {
		return self::internal($value, 'number', array('min'=>1, 'max'=>12), $settings);
	}

	/**
	 *
	 *
	 *
	 */
	protected static function check_timestamp(&$value, $settings) {
		return self::internal($value, 'number', array('min'=>0), $settings);
	}

	/**
	 *
	 *
	 */
	protected static function internal(&$value, $type, $config=array(), $custom=array()) {

		$settings	= array_merge($config, $custom);
		unset($config, $custom);

		if (method_exists('validator', 'check_'.$type)) {
			return eval('return self::check_'. $type . '($value, $settings);');
		} else {
			return self::check_string($value, $settings);
		}

	}
}
?>