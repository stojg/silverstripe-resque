# SilverStripe Resque

This modules wraps php-resque to be able to make scheduled background jobs in sweet harmony with redis.

# Requirements

- SilverStripe with requirements
- Redis server
- (optional) a worker monitor, eg god or monit, to start and monitor the worker(s).
- This module require that the PHP pcntl module is installed

_Still in development_

The principle is that a 'front-end' adds a job via Resque class, this is a very fast operation.


	$args = array(
		'message' => 'from '.$_SERVER['HTTP_HOST'],
		'time' => date('Y-m-d H:i:s')
	);
	$token = Resque::enqueue("dev:ping", "SSResquePingJob", $args);

This will be sent over tcp to a redis server that will hold this information until a worker fetches it.

By starting one or many worker via the cli, they will pull jobs from a 'queue' on redis, ie: `dev:ping`. 
Then it will find a PHP class to run the job, `SSResquePingJob` populate it with the `$args` and then 
run `SSResquePingJob->perform()`.

	public function perform() {
		echo 'Ping: '.$this->args['time'].' '.$this->args['message'].PHP_EOL;
	}

Any exception or errors will mark the job as failed, and it's possible to requeue it.

# Installation

## Install redis

Via homebrew:

	$ brew install redis
	
Or by compiling it, see [Redis Quick Start](http://redis.io/topics/quickstart) for more information.

	$ sudo mkdir -p /usr/local/src
	$ cd /usr/local/src
	$ sudo wget http://download.redis.io/redis-stable.tar.gz
	$ sudo tar xvzf redis-stable.tar.gz
	$ cd redis-stable
	$ sudo make
	$ sudo make install
	
There are most likely a suitable packages in your prefered linux distribution (untested).

[Search google](https://www.google.co.nz/search?q=install+redis+apt+yum)

## Silverstripe Resque

	$ git clone git@github.com:stojg/silverstripe-resque.git resque
	$ cd resque
	$ composer update

# Usage

Start the redis server:

	$ redis-server
	
Start the one worker for all queues in another terminal window

	$ ./framework/sake dev/resque/run queue=*

In another terminal window, try creating a ping job

	$ ./framework/sake dev/resque/ping

In the worker terminal you should now see something similar to

	Ping: 2012-11-15 10:51:29 from hostname

# Web interface

	sudo gem install resque --no-ri -no-rdoc

# Process monitor

How to install god on ubuntu / debian: [How to monitor Resque with God and do it all with Capistrano on Ubuntu](https://gist.github.com/1275333)

Example configuration:

  num_workers = 2

  num_workers.times do |num|
    God.watch do |w|
      w.dir      = "/var/www/site"
      w.name     = "site-worker-#{num}"
      w.group    = 'site-worker'
      w.interval = 30.seconds
      w.uid = 'www-data'
      w.gid = 'www-data'
      w.start = "/usr/bin/php ./framework/cli-script.php dev/resque/run queue=ping"

      # restart if memory gets too high
      w.transition(:up, :restart) do |on|
        on.condition(:memory_usage) do |c|
          c.above = 64.megabytes
          c.times = 2
        end
      end

      # determine the state on startup
      w.transition(:init, { true => :up, false => :start }) do |on|
        on.condition(:process_running) do |c|
          c.running = true
        end
      end

      # determine when process has finished starting
      w.transition([:start, :restart], :up) do |on|
        on.condition(:process_running) do |c|
          c.running = true
          c.interval = 5.seconds
        end

        # failsafe
        on.condition(:tries) do |c|
          c.times = 5
          c.transition = :start
          c.interval = 5.seconds
        end
      end

      # start if process is not running
      w.transition(:up, :start) do |on|
        on.condition(:process_running) do |c|
          c.running = false
        end
      end
    end
  end

# Credits

Here are the giants which shoulders I'm standing standing on:

- [Chris Boulton](https://github.com/chrisboulton/php-resque) for PHP resque
- [defunkt](https://github.com/defunkt/resque/) the original resque for ruby
- [redis](http://redis.io/) for providing an awesome, stable and fast key value store
- [SilverStripe](http://www.silverstripe.org/) The place where I get paid to do stuff like this.