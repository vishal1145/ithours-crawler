<?php

/**
 *
 *
 * @version	1.0
 * @author	Ahmet Şenocak <istemci@gmail.com>
 */
class template {

	/**
	 *
	 *
	 */
	protected $blocks	= array();

	/**
	 *
	 *
	 */
	protected $vars		= array();

	/**
	 *
	 *
	 */
	protected $content	= '';

	/**
	 *
	 *
	 */
	public $supported_server_vars	= array('REQUEST_URI','PHP_SELF','SCRIPT_NAME','REDIRECT_URL','QUERY_STRING','SERVER_ADMIN','SERVER_NAME','SERVER_ADDR','SERVER_PORT','REMOTE_ADDR','REQUEST_TIME','BASE_URL','SELF_URL','BASE_PATH');

	/**
	 *
	 *
	 *
	 *
	 */
	public function __construct($forc=null, $file=true) {
		if ($file)
			$this->open($forc);
		else
			$this->set_content($forc);
	}

	public function dump() {
		print_r($this->vars);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function open($file) {
		if ($file and is_readable($file)) {
			$this->set_content(file_get_contents($file));
		}
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function set_content($str) {
		$this->content	= $str;
		$this->_adjust();
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function block_exists($block) {
		$block	= (substr($block, 0, 5) != 'root.') ? 'root.'.strtolower($block) : strtolower($block);
		return array_key_exists($block, $this->blocks);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function var_exists($var) {
		return array_key_exists($var, $this->vars);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function assign($name, $value, $xml_encode=true) {
		if (!array_key_exists($name, $this->vars))
			return;

		/*
		if ($xml_encode)
			$value	= xml_encode($value);
		*/

		for ($i = 0; $i < sizeof($this->vars[$name]); $i ++) {
			$this->blocks[$this->vars[$name][$i]['block']]['assign'][$this->vars[$name][$i]['order']][$name]	= $value;
		}
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function parse($block) {
		$block	= (substr($block, 0, 5) != 'root.') ? 'root.'.strtolower($block) : strtolower($block);

		if (!array_key_exists($block, $this->blocks))
			return;

		$text	= '';
		for ($i = 0; $i < sizeof($this->blocks[$block]['active']); $i ++) {
			if (array_key_exists('content', $this->blocks[$block]['active'][$i])) {

				// Değişkenleri bul ve setle.
				preg_match_all('/\{(\w+)\}/Us', $this->blocks[$block]['active'][$i]['content'], $vars);
				if (sizeof($vars[1])) {
					for ($k = 0; $k < sizeof($vars[1]); $k ++) {
						// Daha önceden setlenmişse;
						if (isset($this->blocks[$block]['assign'][$i][$vars[1][$k]]))
							$this->blocks[$block]['active'][$i]['content']	= str_replace('{'.$vars[1][$k].'}', $this->blocks[$block]['assign'][$i][$vars[1][$k]], $this->blocks[$block]['active'][$i]['content']);

						// Setlenmemişse boş olarak setle.
						else
							$this->blocks[$block]['active'][$i]['content']	= str_replace('{'.$vars[1][$k].'}', '', $this->blocks[$block]['active'][$i]['content']);
					}
				}
				$text	.= $this->blocks[$block]['active'][$i]['content'];

			} elseif (array_key_exists('block', $this->blocks[$block]['active'][$i])) {
				$text	.= $this->text($this->blocks[$block]['active'][$i]['block']);
			}
		}

		$this->blocks[$block]['parsed'][]	= $text;
		$this->blocks[$block]['active']		= $this->blocks[$block]['native'];
		$this->reset_sub_blocks_of($block);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function reset($block) {
		$block	= (substr($block, 0, 5) != 'root.') ? 'root.'.strtolower($block) : strtolower($block);

		if (!array_key_exists($block, $this->blocks))
			return;

		$this->blocks[$block]['assign']	= array();
		$this->blocks[$block]['parsed']	= array();
		$this->blocks[$block]['active']	= $this->blocks[$block]['native'];

		$this->reset_sub_blocks_of($block);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function reset_sub_blocks_of($block) {
		$block	= (substr($block, 0, 5) != 'root.') ? 'root.'.strtolower($block) : strtolower($block);

		if (!array_key_exists($block, $this->blocks))
			return;

		for ($i = 0; $i < sizeof($this->blocks[$block]['native']); $i ++) {
			if (array_key_exists('block', $this->blocks[$block]['native'][$i])) {
				$this->reset($this->blocks[$block]['native'][$i]['block']);
			}
		}

	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function text($block) {
		$block	= (substr($block, 0, 5) != 'root.') ? 'root.'.strtolower($block) : strtolower($block);

		if (!array_key_exists($block, $this->blocks))
			return;

		$text	= '';
		for ($i = 0; $i < sizeof($this->blocks[$block]['parsed']); $i ++) {
			$text	.= $this->blocks[$block]['parsed'][$i];
		}
		return $text;
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public function out($block) {
		echo $this->text($block);
	}

	/**
	 *
	 *
	 *
	 *
	 */
	protected function _scan_server_vars() {
		preg_match_all('/\{(\w+)\}/Us', $this->content, $vars);

		for ($i = 0; $i < sizeof($vars[1]); $i ++) {
			if (in_array($vars[1][$i], $this->supported_server_vars) and array_key_exists($vars[1][$i], $_SERVER)) {
				$this->content	= str_replace('{'.$vars[1][$i].'}', $_SERVER[$vars[1][$i]], $this->content);
			}
		}
	}

	/**
	 *
	 *
	 *
	 *
	 */
	protected function _adjust() {

		$this->_scan_server_vars();

		preg_match_all('/(.*)<!-- (BEGIN|END): (\S+) -->/Usi', $this->content, $matcheds);
		$parent	= 'root';

		for ($i = 0; $i < sizeof($matcheds[3]); $i ++) {

			$name	= strtolower($matcheds[3][$i]);
			$state	= strtoupper($matcheds[2][$i]);

			if ($state == 'BEGIN') {
				if ($parent) {
					$name	= $parent.'.'.$name;
				}
				$this->blocks[$parent]['native'][]	= array('content'=>$matcheds[1][$i]);

				// İçerikteki değişkenleri bul ve adresle.
				preg_match_all('/\{(\w+)\}/Us', $matcheds[1][$i], $vars);
				if (sizeof($vars[1])) {
					for ($k = 0; $k < sizeof($vars[1]); $k ++) {
						$this->vars[$vars[1][$k]][]	= array('block'=>$parent, 'order'=>sizeof($this->blocks[$parent]['native'])-1);
					}
				}

				$this->blocks[$parent]['native'][]	= array('block'=>$name);
				$this->blocks[$name]	= array('native'=>array());
				$parent	= $name;

			} else {
				$name	= $parent;
				$this->blocks[$name]['native'][]	= array('content'=>trim($matcheds[1][$i]));

				// İçerikteki değişkenleri bul ve adresle.
				preg_match_all('/\{(\w+)\}/Us', $matcheds[1][$i], $vars);
				if (sizeof($vars[1])) {
					for ($k = 0; $k < sizeof($vars[1]); $k ++) {
						$this->vars[$vars[1][$k]][]	= array('block'=>$name, 'order'=>sizeof($this->blocks[$name]['native'])-1);
					}
				}
				$parent	= substr($parent, 0, strrpos($parent, '.'));
			}
		}
		foreach ($this->blocks as $key => $val) {
			$this->blocks[$key]['active']	= $val['native'];
			$this->blocks[$key]['parsed']	= array();
			$this->blocks[$key]['assign']	= array();
		}
	}

}
?>