<?php
$PIDDIR="/tmp/";
include_once(dirname(__FILE__) . "/process.php");

class Caster {
	private $channel = null;
	private $name = null;
	private $url = null;
	private $port = null;
	private $pidfile = null;
	private $process = null;
	private $address = null;
	private $multicast_address = false;
	private $listeners = Array();

	public function Caster($category, $section, $multicast_address = false) {
		global $PIDDIR;
		$this->channel = $category;
		$this->name = $section['name'];
		$this->url = $section['url'];
		$this->port = $section['port'];
		$this->pidfile = $PIDDIR . "/cast_" . $this->channel . ".pid";
		$this->process = $this->restorePidFile();
		$this->address = $multicast_address ? $multicast_address : "234.3.2.1";
		syslog(LOG_DEBUG, "caster: " . $this->channel ." (re)constructed");
		return $this;
	}
	
	public function __destruct() {
		syslog(LOG_DEBUG, "caster: destructed HIER");
	}

	public function getChannel() {
		return $this->channel;
	}
	
	public function getName() {
		return $this->name;
	}

	public function getUrl() {
		return $this->url;
	}
	
	public function getUri() {
		return $this->address . ":" . $this->port;
	}

	private function restorePidFile() {
		if (file_exists($this->pidfile)) {
			$process = new Process();
			if ($process->setPidFile($this->pidfile)) {
				return $process;
			}
		}
		return null;
	}

	private function isCasting() {
		if ($this->process)
			return $this->process->status();
		return false;
	}
	
	public function startCasting() {
		if (!$this->isCasting()) {
			if (file_exists($this->pidfile)) unlink($this->pidfile);
			$cmd = "/usr/bin/ffmpeg -loglevel 0 -re -i " . $this->getUrl() . " -filter_complex aresample=8000,asetnsamples=n=160 -ab 2300 -acodec pcm_mulaw -ac 1 -vn -f rtp rtp://" . $this->getUri();
			$this->process = new Process($cmd);
			$this->process->writePidFile($this->pidfile);
		}
		$this->process->start();
		syslog(LOG_DEBUG, "start casting:" . $this->channel);
	}
	
	public function stopCasting() {
		if ($this->process && $this->process->status()) {
			$this->process->stop();
		}
		syslog(LOG_DEBUG, "stop casting:" . $this->channel);
	}
	
	public function addListener($devicename) {
		$this->listeners[$devicename] = true;
		if (!$this->isCasting()) {
			$this->startCasting();
		}
		return $this->getUri();
	}
	
	public function removeListener($devicename) {
		foreach($this->listeners as $listener) {
			if ($listener == $devicename) {
				unset($listener);
			}
		}
		if (array_count_values($this->listeners) == 0 && $this->isCasting()) {
			$this->stopCasting();
		}
	}
}
?>
