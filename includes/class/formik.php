<?php

/**
 *
 *
 *
 *
 * @version	1.0
 * @author	Ahmet Şenocak <istemci@gmail.com>
 */
class formik {

	/**
	 * Otomatik mod desteği.
	 * @var		bool
	 */
	public $auto_set	= array('GET', 'POST');

	/**
	 * Şifre alanlarında setleme.
	 *
	 */
	public $set_password_fields	= true;

	/**
	 * Geçersiz alanlar listesi.
	 *
	 */
	public $invalids			= array();

	/**
	 * Geçerli alanlar için CSS sınıfı.
	 *
	 */
	public $valid_class		= 'md-valid';

	/**
	 * Geçersiz alanlar için CSS sınıfı.
	 *
	 */
	public $invalid_class	= 'md-valid';

	/**
	 * İşlem yapılan içerik.
	 * @var		string
	 */
	protected $_content;

	/**
	 * İçerikte bulunan alanlar.
	 * @var		array
	 */
	protected $_fields	= array();

	/**
	 * Alanların gerçek hâlleri.
	 * @var		array
	 */
	protected $_natives;

	/**
	 * Sınıf kurucusu.
	 * @param	string	Dosya adı ya da içerik metni.
	 *					Gönderilen veri bir dosya ismiyse ve bu dosya
	 *					ulaşılabiliyorsa, dosya içeriği yüklenir.
	 *					Aksi hâlde gönderilen değer içerik olarak kullanılır.
	 * @return	bool
	 */
	public function __construct($forc=null) {
		if (!is_null($forc)) {
			$forc	= trim($forc);
			if (substr($forc, 0, 1) == '<') {
				$this->set_content($forc);
			} elseif (is_file($forc)) {
				return $this->open($forc);
			}
		}
		return true;
	}

	/**
	 * Gönderilen dosyanın içeriğini işlem yapmak üzere nesneye yükler.
	 * @param	string	Dosya yolu.
	 * @return	bool
	 */
	public function open($file) {
		$mname	= 'open';
		if (!isset($file) or !is_readable($file))
			return $this->_warning($mname, 'FILE_NOT_FOUND', $file);

		$this->set_content(file_get_contents($file));
		return true;
	}

	/**
	 * Gönderilen içeriği işlem yapmak üzere nesneye yükler.
	 *
	 * @param	string	Şablon içeriği.
	 */
	public function set_content($content) {
		$mname	= 'set_content';
		if (!isset($content) or !$content)
			return $this->_warning($mname, 'MISSING_ARGUMENT', 'content');

		$this->_content	= $content;
		$this->_adjust();
	}

	/**
	 * Alana gönderilen değeri setler.
	 * @param	string	Alan ismi.
	 * @param	mixed	Değer.
	 * @return	void
	 */
	public function set($name, $value, $which=-1) {

		$mname	= 'set';

		if (!$this->exists($name)) {

			// Dizi ismi ile kontrol et.
			if ($this->exists($name.'[]')) {
				$name	.= '[]';

			// Yuvalı dizi ise;
			} elseif (is_array($value)) {
				foreach ($value as $k => $v) {
					$this->set($name.'['.$k.']', $v);
				}
				return;
			} else {
				return $this->_notice($mname, 'NO_FIELD', $name);
			}
		}

		foreach ($this->_fields[$name] as $key => $atts) {
			if ($which > -1) {
				if ($key != $which)
					continue;
			}
			switch ($this->_fields[$name][$key]['type']) {
				case 'textarea':
					$this->_fields[$name][$key]['value']	= is_array($value) ? implode("\n",$this->escape($value)):$this->escape($value);
				break;
				case 'password':
					if ($this->set_password_fields) {
						$this->_fields[$name][$key]['value']	= $this->escape($value);
					}
				break;
				case 'checkbox':
					if ((is_array($value) and in_array($this->_fields[$name][$key]['value'], $value)) or (!is_array($value) and $this->_fields[$name][$key]['value']==$value)) {
						$this->_fields[$name][$key]['checked'] = 'checked';
					} else {
						unset($this->_fields[$name][$key]['checked']);
					}
				break;
				case 'radio':
					if ((is_array($value) and in_array($this->_fields[$name][$key]['value'], $value)) or (!is_array($value) and $this->_fields[$name][$key]['value']==$value)) {
						$this->_fields[$name][$key]['checked'] = 'checked';
					} else {
						unset($this->_fields[$name][$key]['checked']);
					}
				break;
				case 'select':
					$value	= (array) $value;
					for ($i = 0; $i < sizeof($this->_fields[$name][$key]['options']); $i ++) {
						if (in_array($this->_fields[$name][$key]['options'][$i]['value'], $value))
							$this->_fields[$name][$key]['options'][$i]['selected']	= 'selected';
						else
							unset($this->_fields[$name][$key]['options'][$i]['selected']);
					}
				break;
				case 'submit':


				break;
				case 'text': case 'hidden': default:
					if (is_array($value)) {
						if (array_key_exists($key, $value)) {
							$temp	= $this->escape($value);
							$this->_fields[$name][$key]['value']	= $temp[$key];
							unset($temp);
						}
					} else {
						$this->_fields[$name][$key]['value']	= $this->escape($value);
					}
				break;
			}
		}
		$this->_change($name);
	}

	/**
	 * Bir alanın herhangi bir özniteliğini döndürür.
	 * @param	string	Alan ismi.
	 * @param	string	Öznitelik adı.
	 * @param	int		Birden fazla olan alanlarda, hangisinin istendiği.
	 * @return	string
	 */
	public function get_attribute($name, $attribute, $which=0) {
		$mname	= 'get_attribute';
		if (!$this->exists($name))
			return $this->_notice($mname, 'NO_FIELD', $name);

		if (!isset($this->_fields[$name][$which][$attribute]))
			return $this->_notice($mname, 'NO_ATTRIBUTE', $attribute);

		return $this->_fields[$name][$which][$attribute];
	}

	/**
	 * Bir alanın istenilen bir özniteliğini değiştirir.
	 * DİKKAT! Bu metodun yalnızca deneyimli programcılar tarafından
	 * kullanılması gerekir. Metod yanlış kullanılırsa, nesne kararsız
	 * hâle gelebilir ve beklenmeyen hatalarla karşılaşılabilir.
	 * @param	string	Alan ismi.
	 * @param	string	Öznitelik adı. ("name" ve "type" özniteliklerine erişilemez.)
	 * @param	string	Özniteliğin yeni değeri.
	 * @param	int		Birden fazla olan alanlarda, hangisinin istendiği.
	 * @return	string
	 */
	public function set_attribute($name, $attribute, $value=null, $which=0) {
		$mname	= 'set_attribute';
		if (!$this->exists($name))
			return $this->_notice($mname, 'NO_FIELD', $name);

		if ($attribute == 'name' or $attribute == 'type')
			return $this->_warning($mname, 'PERMISSION_DENIED', $attribute);

		if (isset($value))
			$this->_fields[$name][$which][$attribute] = $this->escape($value);
		else
			unset($this->_fields[$name][$which][$attribute]);

		$this->_change($name);
	}

	/**
	 * Hazırlanan içeriği döndürür.
	 * @return	string
	 */
	public function text($values=array(), $invalids=array()) {
		if (sizeof($values) or sizeof($invalids)) {

			foreach ($this->_fields as $fn => $fv) {
				/*
				$dot_name	= str_replace('][', '.', $fn);
				$dot_name	= str_replace('[', '.', $dot_name);
				$dot_name	= str_replace(']', '', $dot_name);
				echo $fn.' dot name: '.$dot_name."\n\n";
				*/

				$assoc	= '';
				if (preg_match('/\[\]$/', $fn)) {
					$name	= substr($fn, 0, -2);
				} elseif (preg_match('/\[.*\]/', $fn)) {
					$name	= substr($fn, 0, strpos($fn, '['));
					$assoc	= substr($fn, strpos($fn, '[')+1, -1);
				} else {
					$name	= $fn;
				}
				$name	= str_replace('.','_',$name);
				for ($i = 0; $i < sizeof($this->_fields[$fn]); $i ++) {
					if (in_array($name, $invalids) or in_array($name.'['.$assoc.']', $invalids)) {
						$this->_fields[$fn][$i]['class']	= array_key_exists('class', $this->_fields[$fn][$i]) ? $this->_fields[$fn][$i]['class'].' '.$this->invalid_class:$this->invalid_class;
					} elseif (array_key_exists($name, $values) and $values[$name]) {
						$this->_fields[$fn][$i]['class']	= array_key_exists('class', $this->_fields[$fn][$i]) ? $this->_fields[$fn][$i]['class'].' '.$this->valid_class:$this->valid_class;
					}
				}
				if (array_key_exists($name, $values)) {
					if (strlen($assoc)) {
						if (is_array($values[$name]) and array_key_exists($assoc, $values[$name])) {
							$this->set($fn, $values[$name][$assoc]);
						} else {
							$this->set($fn, null);
						}
					} else {
						$this->set($fn, $values[$name]);
					}
				}
			}
		}
		return $this->_content;
	}

	/**
	 * Hazırlanan içeriği çıktılar.
	 *
	 * @return	void
	 */
	public function out() {
		echo $this->text();
	}

	/**
	 * Bulunan tüm alanların isimlerini döndürür.
	 * @return	array
	 */
	public function get_fields() {
		return array_keys($this->_fields);
	}

	/**
	 * Gönderilen alan isminin içerikte olup olmadığını döndürür.
	 * @param	string
	 * @return	bool
	 */
	public function exists($name) {
		return isset($this->_fields[$name]);
	}

	/**
	 * Güvenlik gereği, HTML sayfasında doğru görüntülenmek
	 * üzere, gönderilen değerde değişiklikler yapar.
	 * @param	string
	 * @return	string
	 */
	public function escape($value) {
		return escaper::escape($value, 'a:c;dq:c;ht:c');
	}

	/**
	 * Setlenen şablon içeriğine göre sınıfı hazırlar.
	 * @return	void
	 */
	protected function _adjust() {
		$mname	= '_adjust';
		if (!$this->_content)
			$this->_warning($mname, 'NO_CONTENT');

		//- Inputs --------------------------------------------------------------------------------
		if (preg_match_all('/<input.*\/>/Usi', $this->_content, $inputs)) {
			for ($i = 0; $i < sizeof($inputs[0]); $i ++) {
				preg_match_all('/\s(([^\s"]+)="([^"]*)")/i', $inputs[0][$i], $nav);

				$navs	= array();
				for ($k = 0; $k < sizeof($nav[2]); $k ++)
					$navs[strtolower($nav[2][$k])]		= $nav[3][$k];

				if (isset($navs['name'])) {
					$this->_fields[$navs['name']][]		= $navs;
					$this->_natives[$navs['name']][]	= $inputs[0][$i];
				}
			}
		}
		//- TextAreas -----------------------------------------------------------------------------
		if (preg_match_all('/<textarea(.*)>(.*)<\/textarea>/Usi', $this->_content, $textareas)) {
			for ($i = 0; $i < sizeof($textareas[0]); $i ++) {
				preg_match_all('/\s(([^\s"]+)="([^"]*)")/i', $textareas[1][$i], $nav);
				$navs	= array();

				for ($k = 0; $k < sizeof($nav[2]); $k ++)
					$navs[$nav[2][$k]]	= $nav[3][$k];

				if (array_key_exists('name', $navs) and $navs['name']) {
					$navs['value']		= $textareas[2][$i];
					$navs['type']		= 'textarea';
					$this->_fields[$navs['name']][]		= $navs;
					$this->_natives[$navs['name']][]	= $textareas[0][$i];
				}
			}
		}
		//- Selects -------------------------------------------------------------------------------
		if (preg_match_all('/<select(.*)>(.*)<\/select>/Usi', $this->_content, $selects)) {
			for ($i = 0; $i < sizeof($selects[0]); $i ++) {
				preg_match_all('/\s(([^\s"]+)="([^"]*)")/i', $selects[1][$i], $nav);
				$navs	= array();
				for ($k = 0; $k < sizeof($nav[2]); $k ++)
					$navs[strtolower($nav[2][$k])]	= $nav[3][$k];

				if ($navs['name']) {
					$navs['type']		= 'select';
					$navs['optgroups']	= array();
					$navs['options']	= array();

					preg_match_all('/<optgroup(.*)>(.*)<\/optgroup>/Usi', $selects[2][$i], $optgroups);

					// optgroup bulunduysa;
					if (sizeof($optgroups[0])) {

						// optgroup'ları işle
						for ($l = 0; $l < sizeof($optgroups[0]); $l ++) {
							preg_match_all('/\s(([^\s"]+)="([^"]*)")/i', $optgroups[1][$l], $ognav);

							// optgroup attributes
							for ($k = 0; $k < sizeof($ognav[2]); $k ++)
								$ognavs[strtolower($ognav[2][$k])]	= $ognav[3][$k];

							$ognavs['options']		= array();
							$navs['optgroups'][]	= $ognavs;

							// optgroup içindeki option taglarını bul.
							preg_match_all('/<option(.*)>(.*)<\/option>/Usi', $optgroups[2][$l], $options);

							$onavs				= array();
							for ($k = 0; $k < sizeof($options[0]); $k ++) {
								preg_match_all('/\s(([^\s"]+)="([^"]*)")/i', $options[1][$k], $onav);
								for ($j = 0; $j < sizeof($onav[2]); $j ++)
									$onavs[strtolower($onav[2][$j])] = $onav[3][$j];

								$onavs['text']		= $options[2][$k];
								$navs['options'][]	= $onavs;
								$navs['optgroups'][sizeof($navs['optgroups'])-1]['options'][]	=& $navs['options'][sizeof($navs['options'])-1];
							}
						}
						$this->_fields[$navs['name']][]		= $navs;
						$this->_natives[$navs['name']][]	= $selects[0][$i];

					} else {
						preg_match_all('/<option(.*)>(.*)<\/option>/Usi', $selects[2][$i], $options);

						$onavs				= array();
						for ($k = 0; $k < sizeof($options[0]); $k ++) {
							preg_match_all('/\s(([^\s"]+)="([^"]*)")/i', $options[1][$k], $onav);
							for ($j = 0; $j < sizeof($onav[2]); $j ++)
								$onavs[strtolower($onav[2][$j])] = $onav[3][$j];

							$onavs['text']		= $options[2][$k];
							$navs['options'][]	= $onavs;
						}
						$this->_fields[$navs['name']][]		= $navs;
						$this->_natives[$navs['name']][]	= $selects[0][$i];
					}

				}
			}
		}
		//-----------------------------------------------------------------------------------------
	}

	/**
	 * Alanlarda yapılan değişiklikleri içeriğe uygular.
	 * @param	string		Alan ismi.
	 * @return	void
	 */
	protected function _change($name) {

		$fields	= $this->_fields[$name];
		for ($c = 0; $c < sizeof($fields); $c ++) {
			$field	= $fields[$c];

			switch($field['type']) {
				case 'textarea':
					$str	 = '<textarea ';
					foreach($field as $a => $v) {
						if ($a != 'value' and $a != 'type')
							$str.= $a.'="'.$v.'" ';
					}
					$str	.= '>'.$field['value'].'</textarea>';
				break;
				case 'select':
					$str	= '<select ';
					foreach ($field as $a => $v) {
						if ($a != 'options' and $a != 'optgroups' and $a != 'type')
							$str.= $a.'="'.$v.'" ';
					}
					$str	.= '>';

					if (sizeof($field['optgroups'])) {
						for ($i  = 0; $i < sizeof($field['optgroups']); $i ++) {
							$str.= '<optgroup';
							foreach ($field['optgroups'][$i] as $a => $v) {
								if ($a != 'options')
									$str .= ' '.$a.'="'.$v.'"';
							}
							$str.= '>';

							for ($k = 0; $k < sizeof($field['optgroups'][$i]['options']); $k ++) {
								$str.= '<option';
								foreach ($field['optgroups'][$i]['options'][$k] as $a => $v) {
									if ($a != 'text')
										$str .= ' '.$a.'="'.$v.'"';
								}
								$str.= '>'.$field['optgroups'][$i]['options'][$k]['text'].'</option>';
							}
							$str.= '</optgroup>';
						}
					} else {
						for ($i  = 0; $i < sizeof($field['options']); $i ++) {
							$str.= '<option';
							foreach ($field['options'][$i] as $a => $v) {
								if ($a != 'text')
									$str .= ' '.$a.'="'.$v.'"';
							}
							$str.= '>'.$field['options'][$i]['text'].'</option>';
						}
					}
					$str	.= '</select>';
				break;
				case 'text': case 'password': case 'hidden': case 'radio': case 'checkbox': case 'file': case 'button': case 'image': case 'reset': case 'submit': default:
					$str	 = '<input ';
					foreach ($field as $a => $v) {
						$str.= $a.'="'.$v.'" ';
					}
					$str	.= '/>';
				break;
			}
			$this->_content	 = str_replace($this->_natives[$name][$c], $str, $this->_content);
			$this->_natives[$name][$c] = $str;
		}
	}

	protected function _warning() {}
	protected function _notice($method, $message, $extra) {}

}
?>
