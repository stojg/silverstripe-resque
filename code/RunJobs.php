<?php

require_once BASE_PATH.'/resque/vendor/autoload.php';

class RunJobs extends Controller {

	protected $queue = null;

	protected $backend = null;

	protected $count = 1;

	protected $logLevel = null;

	protected $interval = 10;

	public function init() {
		parent::init();

		$REDIS_BACKEND = getenv('REDIS_BACKEND');
		if(!empty($REDIS_BACKEND)) {
			Resque::setBackend($REDIS_BACKEND);
		}


		$this->queue = getenv('QUEUE');

		if(!$this->queue) {
			die("Set QUEUE env var containing the list of queues to work.\n");
		}

		$this->count = getenv('COUNT');

		$logLevel = 0;
		$LOGGING = getenv('LOGGING');
		$VERBOSE = getenv('VERBOSE');
		$VVERBOSE = getenv('VVERBOSE');
		if(!empty($LOGGING) || !empty($VERBOSE)) {
			$this->logLevel = Resque_Worker::LOG_NORMAL;
		}
		else if(!empty($VVERBOSE)) {
			$this->logLevel = Resque_Worker::LOG_VERBOSE;
		}

		$interval = getenv('INTERVAL');
		if($interval) {
			$this->interval = $interval;
		}
		
	}

	public function index() {
		if($this->count > 1) {
			$this->fork($this->count);
		}
		// Start a single worker
		else {
			$this->startWorker();	
		}
	}

	protected function fork($workers=1) {
		for($i = 0; $i < $workers; ++$i) {
			$pid = pcntl_fork();
			if($pid == -1) {
				die("Could not fork worker ".$i."\n");
			}
			// Child, start the worker
			else if(!$pid) {
				$this->startWorker(true);
				break;
			}
		}
	}

	protected function startWorker($forked=false) {
		$queues = explode(',', $this->queue);
		$worker = new Resque_Worker($queues);
		$worker->logLevel = $this->logLevel;
		
		if(!$forked) {
			$PIDFILE = getenv('PIDFILE');
			if ($PIDFILE) {
				file_put_contents($PIDFILE, getmypid()) or
				die('Could not write PID information to ' . $PIDFILE);
			}
		}

		fwrite(STDOUT, '*** Starting worker '.$worker." ".$this->interval.PHP_EOL);
		$worker->work($this->interval);
	}

}