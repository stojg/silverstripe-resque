<?php

/**
 * SSResqueLogger
 *
 */
class SSResqueLogger extends Psr\Log\AbstractLogger {

	/**
	 *
	 * @var boolean
	 */
	public $verbose;
	
	/**
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * 
	 * @param true $verbose
	 */
	public function __construct($verbose = false) {
		$this->verbose = $verbose;
		$this->path = DEPLOYNAUT_LOG_PATH.'/resque-log.log';
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed   $level    PSR-3 log level constant, or equivalent string
	 * @param string  $message  Message to log, may contain a { placeholder }
	 * @param array   $context  Variables to replace { placeholder }
	 * @return null
	 */
	public function log($level, $message, array $context = array()) {
		$fh = fopen($this->path, 'a');
		if($this->verbose) {
			fwrite($fh, '[' . $level . '] [' . strftime('%T %Y-%m-%d') . '] ' . $this->interpolate($message, $context) . PHP_EOL);
			return;
		}

		if(!($level === Psr\Log\LogLevel::INFO || $level === Psr\Log\LogLevel::DEBUG)) {
			fwrite($fh, '[' . $level . '] ' . $this->interpolate($message, $context) . PHP_EOL);
		}
		fclose($fh);
	}

	/**
	 * Fill placeholders with the provided context
	 * @author Jordi Boggiano j.boggiano@seld.be
	 * 
	 * @param  string  $message  Message to be logged
	 * @param  array   $context  Array of variables to use in message
	 * @return string
	 */
	public function interpolate($message, array $context = array()) {
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}
	
	/**
	 * 
	 * @param string $resqueError
	 */
	protected function convertErrorLevel($resqueError) {
		switch($resqueError) {
			case 'emergency':
			case 'alert':
			case 'critical':
			case 'error':
				return SS_Log::ERR;
				break;
			case 'warning':
				return SS_Log::WARN;
				break;
			case 'notice':
			case 'info':
			case 'debug':
				return SS_Log::NOTICE;
				break;
			default:
				return SS_Log::NOTICE;
				break;
		}
	}
}
