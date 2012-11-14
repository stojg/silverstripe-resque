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

# Usage

Start the redis server:

	$ redis-server
	
Start the one worker for all queues in another terminal window

	$ ./framework/sake dev/resque/run queue=*

In another terminal window, try creating a ping job

	$ ./framework/sake dev/resque/ping

In the worker terminal you should now see something similar to

	Ping: 2012-11-15 10:51:29 from hostname