<?php
include_once(dirname(__FILE__) . "/process.php");

abstract class castFormat {
	const UniCast = 0;
	const MultiCast = 1;
	
	function fromString($str) {
		switch($str) {
			case "multicast":
				return castFormat::MultiCast;
				break;
			case "unicast":
				return castFormat::UniCast;
				break;
		}
	}
}

class Caster {
	private $channel = null;
	private $name = "";
	private $description = "";
	private $url = null;
	private $port = null;
	private $process = null;
	private $address = null;
	private $format = castFormat::UniCast;
	private $listeners = Array();

	public function Caster($category) {
		global $TMPDIR;
		$this->channel = $category;
		syslog(LOG_DEBUG, "caster: " . $this->channel ." (re)constructed");
		return $this;
	}
	
	/*
	public function __destruct() {
		syslog(LOG_DEBUG, "caster: destructed");
	}
	*/
	
	public function setSection($section) {
		if (!array_key_exists('url', $section)) {
			throw new Exception('url not provided');
		}
		$this->name = array_key_exists('name', $section) ? $section['name']: "";
		$this->description = array_key_exists('description', $section) ? $section['description']: "";
		$this->url = $section['url'];
	}
	
	public function setMulticastAddress($address) {
		if ($address) {
			$this->format = castFormat::MultiCast;
			$this->address = $address;
		} else {
			$this->format = castFormat::UniCast;
			$this->address = null;
		}
		syslog(LOG_DEBUG, "caster: setMulticastAddress({$this->address})");
	}

	public function setStartingPort($port) {
		$this->port = $port;
		syslog(LOG_DEBUG, "caster: setStartingPort({$this->address})");
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

	private function isCasting() {
		if ($this->process)
			return $this->process->status();
		return false;
	}
	
	public function startCasting() {
		if (!$this->process) {
			$cmd = "/usr/bin/ffmpeg -loglevel 0 -re -i " . $this->getUrl() . " -filter_complex aresample=8000,asetnsamples=n=160 -ab 2300 -acodec pcm_mulaw -ac 1 -vn -f rtp rtp://" . $this->getUri();
			$this->port += 2;
			$this->process = new Process($cmd);
		}
		$this->process->start();
		syslog(LOG_DEBUG, "start casting:" . $this->channel);
	}
	
	public function stopCasting() {
		if ($this->process) {
			$this->process->stop();
		}
		syslog(LOG_DEBUG, "stop casting:" . $this->channel);
	}
	
	public function addListener($devicename) {
		$this->listeners[$devicename] = true;
		if (!$this->isCasting() || $this->format == castFormat::UniCast)
			$this->startCasting();
		return $this->getUri();
	}
	
	public function removeListener($devicename) {
		foreach($this->listeners as $listener) {
			if ($listener == $devicename) {
				unset($listener);
			}
		}
		if (array_count_values($this->listeners) == 0) {
			$this->stopCasting();
		}
	}
}
?>
