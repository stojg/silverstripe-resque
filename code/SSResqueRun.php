<?php
/**
 * This controller starts a long lived process that will execute resque jobs
 * 
 * Typically you will start it by running this in a cli environment
 * 
 *     ./framework/sake dev/resque/run verbose=1 queue=* flush=1
 * 
 * list of GET params:
 *  
 *  verbose: 1 | 0 -  Should we log all messages to the log
 *  queue: "queuename" - A comma separated list of queues to work on
 *  backend: "localhost:6379" - the address and port number of the redis server
 *  count: int - the number of child workers to spin up
 *  log: bool - use the silverstripe logger instead of STDOUT
 * 
 */
class SSResqueRun extends Controller {

	/**
	 *
	 * @var array
	 */
	public static $allowed_actions = array(
		'index',
	);

	/**
	 * Comma separated string with the queues that this runner will work on
	 *
	 * @var string 
	 */
	protected $queue = null;

	/**
	 *
	 * @var mixed $backend Host/port combination separated by a colon, or
	 *                     a nested array of servers with host/port pairs 
	 */
	protected $backend = null;

	/**
	 * How many child processes should be started
	 *
	 * @var int
	 */
	protected $numWorkers = 1;

	/**
	 *
	 * @var Psr\Log\AbstractLogger 
	 */
	protected $logger = null;

	/**
	 * How often to check for new jobs on the queue in seconds
	 *
	 * @var int
	 */
	protected $interval = 5;
	
	
	/**
	 * Check that all needed and option params have been set
	 *
	 *
	 */
	public function init() {
		// Ensure the composer autoloader is loaded so dependencies are loaded correctly
		require_once BASE_PATH.'/vendor/autoload.php';
		
		parent::init();
		
		$numWorkers = $this->request->getVar('count');
		if($numWorkers > 1 && !function_exists('pcntl_fork')) {
			throw new Exception('This module need the pcntl PHP module');
		} else if($numWorkers) {
			$this->numWorkers = $numWorkers;
		}

		if(php_sapi_name() !== 'cli') {
			echo 'The resque runner must be started in a CLI environment.';
			exit(1);
		}
		
		if(!$this->request->getVar('queue')) {
			echo("Set 'queue' parameter to containing the list of queues to work on.\n");
			exit(1);
		}
		$this->queue = $this->request->getVar('queue');

		if($this->request->getVar('backend')) {
			Resque::setBackend($this->request->getVar('backend'));
		}
		
		if($this->request->getVar('log')) {
			if($this->request->getVar('verbose')) {
				$this->logger = new SSResqueLogger(true);
			} else {
				$this->logger = new SSResqueLogger(false);
			}
		} else if($this->request->getVar('verbose')) {
			$this->logger = new Resque_Log(true);
		} else {
			$this->logger = new Resque_Log(false);
		}
	}

	/**
	 * This is where the action starts
	 *
	 * @param SS_HTTPRequest $request
	 */
	public function index(SS_HTTPRequest $request) {
		
		if($this->numWorkers > 1) {
			$this->fork($this->numWorkers);
		} else {
			$this->startWorker();	
		}
	}

	/**
	 * Start up multiple workers
	 *
	 * @param int $workers - how many workers should be started
	 */
	protected function fork($workers=1) {
		for($i = 0; $i < $workers; ++$i) {
			$pid = pcntl_fork();
			if($pid == -1) {
				echo "Could not fork worker ".$i.PHP_EOL;
				exit(1);
			// When $pid is 0 we are in the childs process
			} else if(!$pid) {
				$this->startWorker(true);
				break;
			}
		}
	}

	/**
	 * Start a single worker
	 *
	 * @param bool $isForked - is this worker forked
	 */
	protected function startWorker($isForked=false) {
		$queues = explode(',', $this->queue);
		$worker = new Resque_Worker($queues);
		$worker->setLogger($this->logger);
		
		if(!$isForked) {
			$PIDFILE = getenv('PIDFILE');
			if($PIDFILE) {
				file_put_contents($PIDFILE, getmypid()) or die('Could not write PID information to ' . $PIDFILE);
			}
		}

		fwrite(STDOUT, '[+] Starting worker '.$worker." ".$this->interval.PHP_EOL);
		$worker->work($this->interval);
	}
}