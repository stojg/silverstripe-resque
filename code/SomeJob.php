<?php 

class SomeJob {

	  public function setUp() {
    	Resque_Event::listen("onFailure", function($ex, $job) {
			// Check to see if we have an attempts variable in
			// the $args array. Attempts is used to track the
			// amount of times we tried (and failed) at this job.
			if (! isset($job->payload['args']['attempts'])) {
				$job->payload['args']['attempts'] = 0;
			}

			// Increase the number of attempts
			$job->payload['args']['attempts']++;

			// If we haven't hit MAX_ATTEMPTS yet, recreate the job to be
			// run again by another worker.
			if (self::MAX_ATTEMPTS >= $job->payload['args']['attempts']) {
				$job->recreate();
			}
    	});
  }

	public function perform() {
		$page = new Page();
		$page->Title = date('Y-m-d H:i:s');
		$page->write();
		$page->doPublish();

		echo 'page '.$page->Title.' created'.PHP_EOL;
	}

}