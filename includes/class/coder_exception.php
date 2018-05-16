<?php

/**
 *
 *
 */
class coder_exception extends exception {

	public $reason	= '';
	public $file	= '';
	public $line	= '';

	public function __construct($message='unknown_error', $reason='', $file=null, $line=null) {

		$this->reason	= $reason;

		if ($file)
			$this->file	= $file;

		if ($line)
			$this->line	= $line;

		if (is_integer($message)) {
			switch ($message) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
					$message	= 'fatal_error';
				break;
				case E_WARNING:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
					$message	= 'warning';
				break;
				case E_RECOVERABLE_ERROR:
					$message	= 'catchable_fatal_error';
				break;
				case E_PARSE:
					$message	= 'parse_error';
				break;
				case E_USER_ERROR:
					$message	= 'user_error';
				break;
				case E_USER_WARNING:
					$message	= 'user_warning';
				break;
				case E_NOTICE: case E_USER_NOTICE: case E_STRICT: case E_DEPRECATED: case E_USER_DEPRECATED:
					$message	= 'notice';
				break;
			}
		}
		$this->message	= $message;
		parent::__construct($this->message);

	}
}
?>