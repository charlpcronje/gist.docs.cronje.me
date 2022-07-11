<?php
/**
 * Simple Benchmarker for PHP While Loops
 *
 * USES pcntl_fork() to split to different processes,
 *   so we assure RAM utilization doesn't "bleed"
 *
 * Usage:

 * We are using php files for the large test, just to get a bunch of "random"
 * content --- feel free to change how you generate your large lists
 *
 * cd <path>
 * curl '<URL-to-this-script>' > benchmark-loops.php
 * php -f benchmark-loops.php
 *
 *
 *
 *
 * ========================
 * == Benchmarking Loops
 * ========================
 * on PHP: 5.5.22
 * on: Darwin AlanMacBook.local 14.1.0 Darwin Kernel Version 14.1.0: Thu Feb 26 19:26:47 PST 2015; root:xnu-2782.10.73~1/RELEASE_X86_64 x86_64
 *
 * ------------------------
 * -- array: small
 * -- test array: 5000 nodes, 67789 approx size
 * ------------------------
 * for
 *   took 0.003 sec
 *   increased memory by 312 bytes (to 1048552)
 * foreach
 *   took 0.002 sec
 *   increased memory by 96 bytes (to 1048648)
 * foreach-with-keys
 *   took 0.003 sec
 *   increased memory by 144 bytes (to 1048672)
 * foreach-array-keys
 *   took 0.002 sec
 *   increased memory by 96 bytes (to 1048672)
 * while-iterator
 *   took 0.002 sec
 *   increased memory by 96 bytes (to 1048672)
 * while-shifter
 *   took 0.172 sec
 *   increased memory by 65840 bytes (to 1114416)
 *
 * ------------------------
 * -- array: mid
 * -- test array: 50000 nodes, 2388900 approx size
 * ------------------------
 * for
 *   took 0.025 sec
 *   increased memory by 144 bytes (to 10427320)
 * foreach
 *   took 0.029 sec
 *   increased memory by 96 bytes (to 10427320)
 * foreach-with-keys
 *   took 0.031 sec
 *   increased memory by 144 bytes (to 10427368)
 * foreach-array-keys
 *   took 0.035 sec
 *   increased memory by 96 bytes (to 10427368)
 * while-iterator
 *   took 0.023 sec
 *   increased memory by 96 bytes (to 10427368)
 * while-shifter
 *   took 20.764 sec
 *   increased memory by 524656 bytes (to 10951928)
 *
 * ------------------------
 * -- array: large
 * -- test array: 7748 nodes, 56266678 approx size
 * ------------------------
 * for
 *   took 0.005 sec
 *   increased memory by 144 bytes (to 57722224)
 * foreach
 *   took 0.006 sec
 *   increased memory by 96 bytes (to 57722224)
 * foreach-with-keys
 *   took 0.006 sec
 *   increased memory by 144 bytes (to 57722272)
 * foreach-array-keys
 *   took 0.008 sec
 *   increased memory by 96 bytes (to 57722272)
 * while-iterator
 *   took 0.006 sec
 *   increased memory by 96 bytes (to 57722272)
 * while-shifter
 *   took 0.507 sec
 *   increased memory by 67536 bytes (to 57789712)
 *
 * ------------------------
 * -- array: huge
 * -- test array: 2399 nodes, 251826261 approx size
 * ------------------------
 * for
 *   took 0.004 sec
 *   increased memory by 144 bytes (to 252498512)
 * foreach
 *   took 0.005 sec
 *   increased memory by 96 bytes (to 252498512)
 * foreach-with-keys
 *   took 0.009 sec
 *   increased memory by 144 bytes (to 252498560)
 * foreach-array-keys
 *   took 0.010 sec
 *   increased memory by 96 bytes (to 252498560)
 * while-iterator
 *   took 0.007 sec
 *   increased memory by 96 bytes (to 252498560)
 * while-shifter
 *   took 0.195 sec
 *   increased memory by 50760 bytes (to 252549224)
 *
 *
 *
 */

class Benchmarker {
	public $pid = null;
	public $startTime = null;
	public $startMemory = null;
	public $stopTime = null;
	public $stopMemory = null;

	public function init() {
		echo "\n";
		echo "\n========================";
		echo "\n== Benchmarking Loops";
		echo "\n========================";
		echo "\non PHP: " . phpversion();
		echo "\non: " . php_uname('a');
	}

	public function runTestsOnArray($label, $array) {
		echo "\n";
		echo "\n------------------------";
		echo "\n-- {$label}";
		echo sprintf(
			"\n-- test array: %s nodes, %s approx size",
			count($array),
			strlen(serialize($array))
		);
		echo "\n------------------------";

		// initialize a "ghost" variable to do nothing...
		$devnull = null;

		// For
		$this->start();
		$length = count($array);
		for($i=0;$i<$length;++$i) {
			$devnull = $array[$i];
		}
		$this->stop();
		$this->output('for');

		// Foreach keyless
		$this->start();
		foreach($array as $a) {
			$devnull = $a;
		}
		$this->stop();
		$this->output('foreach');

		// Foreach with keys
		$this->start();
		foreach($array as $k => $a) {
			$devnull = $a;
		}
		$this->stop();
		$this->output('foreach-with-keys');

		// Foreach array-keys
		$this->start();
		foreach(array_keys($array) as $k) {
			$devnull = $array[$k];
		}
		$this->stop();
		$this->output('foreach-array-keys');

		// While iterator
		$this->start();
		$i = 0;
		$length = count($array);
		while($i<$length) {
			$devnull = $array[$i++];
		}
		$this->stop();
		$this->output('while-iterator');

		/* -- commented out because it was so ridiculously slow and bad w/ RAM
		/* -- * /
		// While shifter (destructive)
		$this->start();
		while(!empty($array)) {
			$devnull = array_shift($array);
		}
		$this->stop();
		$this->output('while-shifter');
		/* -- */
	}

	public function start() {
		$this->pid = pcntl_fork();
		if ($this->pid == -1) {
			die('could not fork');
		} else if ($this->pid) {
			// we are the parent
			//   no start/stop data/testing on parent, test management
			pcntl_wait($status); //Protect against Zombie children
			return;
		}
		// we are the child
		$this->startTime = microtime(true);
		$this->startMemory = memory_get_usage();
	}

	public function stop() {
		if ($this->pid == -1) {
			die('could not fork');
		} else if ($this->pid) {
			// we are the parent
			//   no start/stop data/testing on parent, test management
			return;
		}
		// we are the child
		$this->stopTime = microtime(true);
		$this->stopMemory = memory_get_usage();
	}

	public function output($label) {
		if ($this->pid == -1) {
			die('could not fork');
		} else if ($this->pid) {
			// we are the parent
			//   no start/stop data/testing on parent, test management
			return;
		}

		// we are the child
		$seconds = $this->stopTime - $this->startTime;
		$memoryDelta = $this->stopMemory - $this->startMemory;
		echo sprintf(
			"\n%s\n  took %s sec \n  increased memory by %s bytes (to %s)",
			$label,
			number_format($seconds, 3, '.', ''),
			$memoryDelta,
			$this->stopMemory
		);

		// we are the child
		//   exit after every output
		exit;
	}


	/**
	 * Convenience function to recursivly find files in a path
	 *
	 * @param string $path
	 * @param string $find glob pattern eg: *.php
	 * @return array $filenames (with paths, starting at $path)
	 */
	public function globRecursive($path, $find) {
		$output = [];
		$dh = opendir($path);
		while (($file = readdir($dh)) !== false) {
			if (substr($file, 0, 1) == '.') {
				continue;
			}
			$rfile = "{$path}/{$file}";
			if (is_dir($rfile)) {
				$output = array_merge(
					$output,
					$this->globRecursive($rfile, $find)
				);
			} else {
				if (fnmatch($find, $file)) {
					$output[] = $rfile;
				}
			}
		}
		closedir($dh);
		return $output;
	}
}

$Bench = new Benchmarker();

$Bench->init();

// initialize our "test array"


// Generating test array: small
$n = 5000;
$array = [];
$i = 0;
while($i < $n) {
	$array[] = $i++;
}
$Bench->runTestsOnArray('array: small', $array);

// Generating test array: mid
$n = 50000;
$array = [];
$i = 0;
while($i < $n) {
	$array[] = md5(uniqid());
	$i++;
}
$Bench->runTestsOnArray('array: mid', $array);


// Generating test array: large
$array = [];
foreach ($Bench->globRecursive('.', "*.ctp") as $filename) {
	//echo "\n - $filename";
	$array[] = file_get_contents($filename);
}
foreach ($Bench->globRecursive('.', "*.php") as $filename) {
	//echo "\n - $filename";
	$array[] = file_get_contents($filename);
}

$Bench->runTestsOnArray('array: large', $array);

// Generating test array: huge
$array = [];
foreach ($Bench->globRecursive('.', "*.ctp") as $filename) {
	$data = file_get_contents($filename);
	// lots of manipulations of the source data trying to get "randomish" text
	$array[] = base64_encode($data) .
		serialize(str_split($data, 5)) .
		serialize(array_flip(str_split($data, rand(5, 50)))) .
		serialize(array_unique(str_split($data, rand(5, 50)))) .
		serialize(array_reverse(str_split($data, rand(5, 50)))) .
		json_encode(str_split($data, 5)) .
		json_encode(array_flip(str_split($data, rand(5, 50)))) .
		json_encode(array_unique(str_split($data, rand(5, 50)))) .
		json_encode(array_reverse(str_split($data, rand(5, 50)))) .
		serialize(str_split(base64_encode($data), 5)) .
		serialize(array_flip(str_split(base64_encode($data), rand(5, 50)))) .
		serialize(array_unique(str_split(base64_encode($data), rand(5, 50)))) .
		serialize(array_reverse(str_split(base64_encode($data), rand(5, 50)))) .
		json_encode(str_split(base64_encode($data), 5)) .
		json_encode(array_flip(str_split(base64_encode($data), rand(5, 50)))) .
		json_encode(array_unique(str_split(base64_encode($data), rand(5, 50)))) .
		json_encode(array_reverse(str_split(base64_encode($data), rand(5, 50))));
}

$Bench->runTestsOnArray('array: huge', $array);




