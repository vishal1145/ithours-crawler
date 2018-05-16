<?php

/**
 *
 *
 */
class page {

	private $vars		= array();

	public	$invalids	= array();
	public	$out		= array();

	/**
	 *
	 *
	 */
	public function __construct($directory, $path) {
		$this->vars['directory']			= $directory;
		$this->vars['path']					= $path;
		$this->vars['directory_name']		= ($directory != $_SERVER['PROJECT_DIRECTORY'].'pages/') ? substr($directory, strrpos($directory, '/', -2)+1, -1) : '';
		$this->vars['path_name']			= ($path != '/') ? substr($path, strrpos($path, '/', -2)+1, -1) : '';
		$this->vars['status']				= ($_SERVER['REQUEST_METHOD'] == 'POST') ? 'sent' : 'normal';
		$this->vars['content']				= '';
		$this->vars['error']				= '';
		$this->vars['outputs']				= array();
		$this->vars['config_file_loaded']	= false;
		$this->vars['title']				= '';
		$this->vars['meta']					= array();
		$this->vars['links']				= array();
		$this->vars['scripts']				= array();
	}


	/**
	 *
	 *
	 */
	public function __get($name) {
		$name	= strtolower($name);
		if (isset($this->vars[$name]))
			return $this->vars[$name];

		switch ($name) {
			case 'url':
				$this->vars[$name]	= $_SERVER['BASE_PATH'].$this->vars['path'];
			break;
			case 'href':
				$this->vars[$name]	= $_SERVER['BASE_URL'].substr($this->vars['path'], 1);
			break;
			case 'directory':
				$this->vars[$name]	= $_SERVER['PROJECT_DIRECTORY'].'pages'.$this->vars['directory_path'];
			break;
			case 'parent_path':
				$this->vars[$name]	= substr($this->vars['path'], 0, strrpos(substr($this->vars['path'], 0, -1), '/')+1);
			break;
			case 'parent_directory':
				$this->vars[$name]	= substr($this->directory, 0, strrpos(substr($this->directory, 0, -1), '/')+1);
			break;
			case 'parent_directory_path':
				$this->vars[$name]	= substr($this->vars['directory_path'], 0, strrpos(substr($this->vars['directory_path'], 0, -1), '/')+1);
			break;
			case 'config_file':
				$this->vars[$name]	= $this->directory.'page.json';
			break;
			case 'config_file_exists':
				$this->vars[$name]	= $this->config_file_exists();
			break;
			case 'check_file':
				$this->vars[$name]	= $this->directory.'check.php';
			break;
			case 'check_file_exists':
				$this->vars[$name]	= $this->check_file_exists();
			break;
			case 'run_file':
				$this->vars[$name]	= $this->directory.'page.php';
			break;
			case 'run_file_exists':
				$this->vars[$name]	= $this->run_file_exists();
			break;
			case 'template_file':
				$this->vars[$name]	= $this->directory.'page.html';
			break;
			case 'template_file_exists':
				$this->vars[$name]	= $this->template_file_exists();
			break;
			case 'template':
				$this->vars[$name]	= $this->template();
			break;
			case 'template_object':
				$this->vars['template_object']	= $this->template_object();
			break;
			case 'any_error':
				return (sizeof($this->invalids) or strlen($this->vars['error']));
			break;
			case 'any_invalids':
				return sizeof($this->invalids);
			break;
			case 'no_error':
				return (!sizeof($this->invalids) and !strlen($this->vars['error']));
			break;
			case 'no_invalid': case 'no_invalids':
				return !sizeof($this->invalids);
			break;
			default:
				return null;
			break;
		}
		return $this->vars[$name];
	}

	/**
	 *
	 *
	 */
	public function __set($name, $value) {
		$name	= strtolower($name);

		switch ($name) {
			default:
				$this->vars[$name]	= $value;
			break;
		}
	}

	/**
	 *
	 *
	 */
	public function is_status($status) {
		$status	= strtolower($status);

		if ($_SERVER['REQUEST_METHOD'] == 'POST' and $status == 'sent')
			return true;

		if ($_SERVER['REQUEST_METHOD'] == 'GET' and $status == 'normal')
			return true;

		return false;
	}

	/**
	 *
	 *
	 */
	public function check() {
		if (!$this->check_file_exists)
			return true;

		$code	= include($this->check_file);
		if (!is_callable($code))
			throw new coder_exception('CHECK_FILE_MUST_RETURN_CALLABLE');

		$reflector	= new ReflectionFunction($code); 
		$parameters	= $reflector->getParameters();
		$args		= array();

		for ($i = 0; $i < $sizeof=sizeof($parameters); $i ++) {
			$name	= strtolower($parameters[$i]->name);

			switch ($name) {
				case 'page': case 'me':
					$args[]	= $this;
				break;
				case 'db': case 'dbo':
					$args[]	= get_dbo();
				break;
				case 'locals': case 'l': case '_':
					$args[]	=& $this->vars['locals'];
				break;
				case 'template':
					$args[]	= $this->template;
				break;
				case 'tpl': case 'tplo':
					$args[]	= $this->template_object;
				break;
				case 'headers':
					$args[]	= &$this->vars['headers'];
				break;
				case 'out': case 'output': case 'outputs':
					$args[]	= &$this->vars['outputs'];
				break;
				case 'member':
					$args[]	= get_current_member();
				break;
				case 'admin':
					$args[]	= get_current_admin();
				break;
				case 'current':
					$args[]	=& $GLOBALS['current'];
				break;
				case 'entry':
					if ($parameters[$i]->isDefaultValueAvailable()) {
						$subject	= $parameters[$i]->getDefaultValue();
						if (array_key_exists($subject, $GLOBALS['current'])) {
							$args[]	=& $GLOBALS['current'][$subject];
						} else {
							$args[]	= array();
						}
					} else {
						if ($subject = array_last_key($GLOBALS['current'])) {
							$args[]	=& $GLOBALS['current'][$subject];
						} else {
							$args[]	= array();
						}
					}
					unset($subject);
				break;
				case 'remember': case 'rem':
					$subject	= '';
					if ($parameters[$i]->isDefaultValueAvailable() and is_string($parameters[$i]->getDefaultValue()))
						$subject	= $parameters[$i]->getDefaultValue();

					if (!strlen($subject)) {
						$subject	= 'page_'.md5($this->directory_path);
					}

					if (!isset($_SESSION['remember'][$subject])) {
						$_SESSION['remember'][$subject]	= array();
					}
					$args[]	=& $_SESSION['remember'][$subject];

					unset($subject);
				break;
				default:
					if (substr($name, -6) == '_entry' && array_key_exists(substr($name, 0, -6), $GLOBALS['current'])) {
						$args[]	=& $GLOBALS['current'][substr($name, 0, -6)];
					} elseif (substr($name, -3) == '_id' && array_key_exists(substr($name, 0, -3), $GLOBALS['current']) and array_key_exists('id', $GLOBALS['current'][substr($name, 0, -3)])) {
						$args[]	= $GLOBALS['current'][substr($name, 0, -3)]['id'];
					} elseif (substr($name, -4) == '_rem') {

						$subject	= '';
						if ($parameters[$i]->isDefaultValueAvailable() and is_string($parameters[$i]->getDefaultValue()))
							$subject	= $parameters[$i]->getDefaultValue();

						if (!strlen($subject)) {
							$subject	= substr($name, 0, -4);
						}

						if (!isset($_SESSION['remember'][$subject])) {
							$_SESSION['remember'][$subject]	= array();
						}
						$args[]	=& $_SESSION['remember'][$subject];

						unset($subject);

					} else {
						$args[]	= $parameters[$i]->isDefaultValueAvailable() ? $parameters[$i]->getDefaultValue() : null;
					}
				break;
			}

		}
		unset($reflector, $parameters);

		$return	= call_user_func_array($code, $args);
	}

	/**
	 *
	 *
	 */
	public function run($arguments=array()) {

		$this->vars['headers']		= array('Content-type:text/html; charset=UTF-8');

		if ($this->config_file_exists and !$this->config_file_loaded) {
			$this->load_config_file();
		}

		if (!$this->run_file_exists) {
			if ($this->template) {
				$tpl	= $this->template_object();
				$tpl->parse('main');
				$this->vars['content']	= $tpl->text('main');

			}
			return $this->vars['content'];
		}

		$code	= include($this->run_file);
		if (!is_callable($code))
			throw new coder_exception('RUN_FILE_MUST_RETURN_CALLABLE');

		$reflector	= new ReflectionFunction($code); 
		$parameters	= $reflector->getParameters();
		$args		= array();

		for ($i = 0; $i < $sizeof=sizeof($parameters); $i ++) {
			$name	= strtolower($parameters[$i]->name);

			switch ($name) {
				case 'page': case 'me':
					$args[]	= $this;
				break;
				case 'db': case 'dbo':
					$args[]	= get_dbo();
				break;
				case 'locals': case 'l': case '_':
					$args[]	=& $this->vars['locals'];
				break;
				case 'template':
					$args[]	= $this->template;
				break;
				case 'tpl': case 'tplo':
					$args[]	= $this->template_object;
				break;
				case 'headers':
					$args[]	= &$this->vars['headers'];
				break;
				case 'out': case 'output': case 'outputs':
					$args[]	= &$this->vars['outputs'];
				break;
				case 'member':
					$args[]	= get_current_member();
				break;
				case 'admin':
					$args[]	= get_current_admin();
				break;
				case 'current':
					$args[]	=& $GLOBALS['current'];
				break;
				case 'entry':
					if ($parameters[$i]->isDefaultValueAvailable()) {
						$subject	= $parameters[$i]->getDefaultValue();
						if (array_key_exists($subject, $GLOBALS['current'])) {
							$args[]	=& $GLOBALS['current'][$subject];
						} else {
							$args[]	= array();
						}
					} else {
						if ($subject = array_last_key($GLOBALS['current'])) {
							$args[]	=& $GLOBALS['current'][$subject];
						} else {
							$args[]	= array();
						}
					}
					unset($subject);
				break;
				case 'remember': case 'rem':
					$subject	= '';
					if ($parameters[$i]->isDefaultValueAvailable() and is_string($parameters[$i]->getDefaultValue()))
						$subject	= $parameters[$i]->getDefaultValue();

					if (!strlen($subject)) {
						$subject	= 'page_'.md5($this->directory_path);
					}

					if (!isset($_SESSION['remember'][$subject])) {
						$_SESSION['remember'][$subject]	= array();
					}
					$args[]	=& $_SESSION['remember'][$subject];

					unset($subject);
				break;
				default:
					if (substr($name, -6) == '_entry' && array_key_exists(substr($name, 0, -6), $GLOBALS['current'])) {
						$args[]	=& $GLOBALS['current'][substr($name, 0, -6)];
					} elseif (substr($name, -3) == '_id' && array_key_exists(substr($name, 0, -3), $GLOBALS['current']) and array_key_exists('id', $GLOBALS['current'][substr($name, 0, -3)])) {
						$args[]	= $GLOBALS['current'][substr($name, 0, -3)]['id'];
					} elseif (substr($name, -4) == '_rem') {

						$subject	= '';
						if ($parameters[$i]->isDefaultValueAvailable() and is_string($parameters[$i]->getDefaultValue()))
							$subject	= $parameters[$i]->getDefaultValue();

						if (!strlen($subject)) {
							$subject	= substr($name, 0, -4);
						}

						if (!isset($_SESSION['remember'][$subject])) {
							$_SESSION['remember'][$subject]	= array();
						}
						$args[]	=& $_SESSION['remember'][$subject];

						unset($subject);

					} else {
						$args[]	= $parameters[$i]->isDefaultValueAvailable() ? $parameters[$i]->getDefaultValue() : null;
					}
				break;
			}
		}
		unset($reflector, $parameters);

		try {
			$return	= call_user_func_array($code, $args);
		} catch (exception $e) {
			throw $e;
		}

		if (array_key_exists('__viewtype', $_REQUEST) and $_REQUEST['__viewtype'] == 'json') {
			header('Content-Type: application/json; charset=utf-8', true, 200);
			echo json_encode($this->vars['outputs']);
			exit;
		}


		if (is_string($return) and strlen($return)) {
			$this->vars['content']	= $return;

		} elseif (strlen($this->template)) {

			if (sizeof($this->invalids)) {
				foreach ($this->invalids as $invalid_name => $invalid_data) {
					if ($this->template_object->block_exists('main.invalid_'.$invalid_name.'__'.$invalid_data['reason'])) {
						$this->template_object->parse('main.invalid_'.$invalid_name.'__'.$invalid_data['reason']);
					} elseif ($this->template_object->block_exists('main.invalid_'.$invalid_name)) {
						$this->template_object->parse('main.invalid_'.$invalid_name);
					}
				}

				$this->template_object->parse('main.error.validation_error');
				$this->template_object->parse('main.error');

				unset($invalid_name, $invalid_data);
			}

			if (strlen($this->vars['error'])) {
				if ($this->template_object->block_exists('main.error.'.$this->vars['error'])) {
					$this->template_object->parse('main.error.'.$this->vars['error']);
					$this->template_object->parse('main.error');
				} elseif ($this->template_object->block_exists('main.error_'.$this->vars['error'])) {
					$this->template_object->parse('main.error_'.$this->vars['error']);
				}
			}


			if (sizeof($this->vars['outputs'])) {
				self::out_template($this->template_object, $this->vars['outputs']);
			}

			$this->template_object->parse('main');
			$this->vars['content']	= $this->template_object->text('main');

			if (stristr($this->vars['content'], '<form')) {
				$formik		= new formik();
				$formik->set_content($this->vars['content']);

				$this->vars['content']		= $formik->text(array_merge($_GET, $_POST), array_keys($this->invalids));

			}

		}

		if (array_key_exists('__viewtype', $_REQUEST) and $_REQUEST['__viewtype'] == 'ajax') {
			header('Content-Type: text/html; charset=utf-8', true, 200);
			echo $this->vars['content'];
			exit;

		}

		return $this->vars['content'];
	}

	/**
	 *
	 *
	 */
	public function &get_argument($name) {
		$ref		= null;
		$names		= explode('/', $name);
		$length		= sizeof($names);
		$first_part	= true;

		for ($i = 0; $i < $length; $i ++) {
			$name	= trim($names[$i]);
			if (!strlen($name))
				continue;

			if ($first_part) {
				if (array_key_exists($name, $_POST)) {
					$ref	=& $_POST[$name];
				} elseif (array_key_exists($name, $_GET)) {
					$ref	=& $_GET[$name];
				} else {
					$_POST[$name]	= null;
					$ref	=& $_POST[$name];
				}
				$first_part	= false;
			} else {
				if (is_array($ref)) {
					if (!array_key_exists($name, $ref)) {
						$ref[$name]	= null;
					}
					$ref	=& $ref[$name];
				} else {
					$ref	= array();
					$ref[$name]	= null;
					$ref	=& $ref[$name];
				}
			}
		}

		return $ref;
	}

	/**
	 *
	 *
	 */
	public function has_argument($name) {
		$ref		= null;
		$names		= explode('/', $name);
		$length		= sizeof($names);
		$first_part	= true;

		for ($i = 0; $i < $length; $i ++) {
			$name	= trim($names[$i]);
			if (!strlen($name))
				continue;

			if ($first_part) {
				if (array_key_exists($name, $_POST)) {
					$ref	=& $_POST[$name];
				} elseif (array_key_exists($name, $_GET)) {
					$ref	=& $_GET[$name];
				} else {
					return false;
				}
				$first_part	= false;
			} else {
				if (is_array($ref)) {
					if (!array_key_exists($name, $ref)) {
						return false;
					}
					$ref	=& $ref[$name];
				}
			}
		}

		if ($first_part)
			return false;

		return true;
	}

	/**
	 *
	 *
	 */
	public function set_argument($name, $value) {

		$ref	=& $this->get_argument($name);
		$ref	= $value;
	}

	/**
	 *
	 *
	 */
	public function check_argument($name, $type, $settings=array()) {

		$ref	=& $this->get_argument($name);

		if (validator::check($ref, $type, $settings)) {
			return true;
		}

		$this->set_invalid($name);
		return false;
	}

	/**
	 *
	 *
	 */
	public function set_invalid($name, $reason=null, $data=array()) {
		$this->invalids[$name]	= array('reason'=>$reason, 'data'=>$data);
	}

	/**
	 *
	 *
	 */
	public function unset_invalid($name) {
		unset($this->invalids[$name]);
	}

	/**
	 *
	 *
	 */
	public function empty_invalids() {
		$this->invalids	= array();
	}

	/**
	 *
	 *
	 */
	public function config_file_exists() {
		return is_readable($this->config_file);
	}

	/**
	 *
	 *
	 */
	public function check_file_exists() {
		return is_readable($this->check_file);
	}

	/**
	 *
	 *
	 */
	public function run_file_exists() {
		return is_readable($this->run_file);
	}

	/**
	 *
	 *
	 */
	public function template_file_exists() {
		return is_readable($this->template_file);
	}

	/**
	 *
	 *
	 */
	public function template_object() {
		$tpl	= new template();
		$tpl->set_content($this->template);
		unset($this->vars['template']);
		return $tpl;
	}

	/**
	 *
	 *
	 */
	public function template() {
		if (!$this->template_file_exists)
			return null;

		$content	= file_get_contents($this->template_file);
		return $content;
	}

	/**
	 *
	 *
	 */
	public function load_config_file() {
		if (!$this->config_file_exists)
			return false;

		$config	= json_decode(file_get_contents($this->config_file), true);

		$this->vars['title']	= strval($config['title']);
		$this->vars['meta']		= $config['meta'];
		$this->vars['links']	= $config['links'];
		$this->vars['scripts']	= $config['scripts'];

		$this->vars['config_file_loaded']	= true;
		return true;
	}

	/**
	 *
	 *
	 */
	public function __toString() {
		return $this->content;
	}

	/**
	 *
	 *
	 */
	public function __invoke($arguments=null) {
		return $this->run($arguments);
	}

	/**
	 *
	 *
	 */
	public function redirect($url) {
		header('Location: '.$url);
		exit;
	}

	/**
	 *
	 *
	 */
	public static function out_template($tpl, $out, $path='main.') {
		if (is_array($out)) {
			foreach ($out as $n => $v) {
				if ($tpl->block_exists($path.$n)) {
					if (is_real_array($v)) {
						foreach ($v as $key => $val) {
							if (is_real_hash($val)) {
								self::out_template($tpl, $val, $path.$n.'.');
							}
							$tpl->parse($path.$n);
						}
					} elseif (is_real_hash($v)) {
						self::out_template($tpl, $v, $path.$n.'.');
						$tpl->parse($path.$n);
					} else {
						if ($tpl->var_exists($n)) {
							$tpl->assign($n, $v);
						}
						if ($tpl->block_exists($path.$n.'.'.$v)) {
							$tpl->parse($path.$n.'.'.$v);
						}
						$tpl->parse($path.$n);

					}
				} elseif ($tpl->var_exists($n)) {
					$tpl->assign($n, $v);
				}
			}
		}
	}


}
?>