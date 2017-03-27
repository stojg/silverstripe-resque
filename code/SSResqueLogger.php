<?php

/**
 * SSResqueLogger
 *
 */
class SSResqueLogger extends Resque_Log {

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed   $level    PSR-3 log level constant, or equivalent string
	 * @param string  $message  Message to log, may contain a { placeholder }
	 * @param array   $context  Variables to replace { placeholder }
	 * @return null
	 */
	public function log($level, $message, array $context = array()) {
		// Filter out any job argument called "password".
		if (!empty($context['job'])) {
			if ($context['job'] instanceof Resque_Job) {
				$payload = $context['job']->payload;
				// Resque always stores args inside the 0-th element of the 'args' array.
				if (!empty($payload['args'][0]['password'])) {
					$payload['args'][0]['password'] = '********';
				}
				$context['job'] = new Resque_Job($context['job']->queue, $payload);
			}
		}

		parent::log($level, $message, $context);

		// if we have a stack context which is the Exception that was thrown,
		// send that to SS_Log so writers can use that for reporting the error.
		if (!empty($context['stack'])) {
			SS_Log::log($context['stack'], $this->convertLevel($level));
		}
	}

	/**
	 *
	 * @param string $resqueError
	 */
	protected function convertLevel($resqueError) {
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
