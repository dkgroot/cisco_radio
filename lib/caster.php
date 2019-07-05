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

	public function Caster($category) {
		global $TMPDIR;
		$this->channel = $category;
		syslog(LOG_DEBUG, "caster: " . $this->channel ." (re)constructed");
		return $this;
	}
	
	public function setSection($section) {
		if (!array_key_exists('url', $section)) {
			throw new Exception('url not provided');
		}
		$this->name = array_key_exists('name', $section) ? $section['name']: "";
		$this->description = array_key_exists('description', $section) ? $section['description']: "";
		$this->url = $section['url'];
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

	protected function isCasting() {
		if ($this->process)
			return $this->process->status();
		return false;
	}
	
	protected function startCasting() {
		if (!$this->process) {
			$cmd = "/usr/bin/ffmpeg -loglevel 0 -re -i " . $this->getUrl() . " -filter_complex aresample=8000,asetnsamples=n=160 -ab 2300 -acodec pcm_mulaw -ac 1 -vn -f rtp rtp://" . $this->getUri();
			$this->port += 2;
			$this->process = new Process($cmd);
		}
		$this->process->start();
		syslog(LOG_DEBUG, "start casting:" . $this->channel);
	}
	
	protected function stopCasting() {
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

class UniCaster extends Caster {
	public function UniCaster($category) {
		parent::caster($category);
		$this->format = castFormat::UniCast;
		syslog(LOG_DEBUG, "UniCaster: $category");
		return $this;
	}
	
	public function addListener($devicename) {
		if (!$this->isCasting())
			$this->startCasting($devicename);
		return $this->getUri();
	}
	
	public function removeListener($devicename) {
		$this->stopCasting($devicename);
	}
}
class MultiCaster extends Caster {
	private $listeners = Array();

	public function MultiCaster($category) {
		parent::caster($category);
		$this->format = castFormat::MultiCast;
		syslog(LOG_DEBUG, "MultiCaster: $category");
		return $this;
	}

	public function setMulticastAddress($address) {
		$this->address = $address;
		syslog(LOG_DEBUG, "caster: setMulticastAddress({$this->address})");
	}

	public function addListener($devicename) {
		$this->listeners[$devicename] = true;
		if (!$this->isCasting())
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
