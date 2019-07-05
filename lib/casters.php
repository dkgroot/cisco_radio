<?php
include_once(dirname(__FILE__) . "/caster.php");

class Casters {
	private $casters = Array();
	private $format = castFormat::UniCast;
	private $multicast_address = null;
	private $startingport = 20480;
	
	public function __construct() {
		syslog(LOG_DEBUG, "casters contructing");
		$ini = parse_ini_file(dirname(__FILE__) . "/../channels.ini", true);
		$this->restoreCasters();
		foreach($ini as $category => $section) {
			if ($category == "general") {
				$this->format = !empty($section['format']) ? castFormat::fromString($section['format']) : castFormat::UniCast;
				$this->multicast_address = !empty($section['multicast_address']) ? $section['multicast_address'] : null;
				$this->starting_port = !empty($section['starting_port']) ? (int)$section['starting_port'] : 20480;
			} else {
				if (array_key_exists($category, $this->casters)) {
					$caster = $this->casters[$category];
				} else {
					if ($format == castFormat::UniCast) {
						$caster = new UniCaster($category);
					} else {
						$caster = new MultiCaster($category);
						$caster->setMulticastAddress($this->multicast_address);
					}
					$this->casters[$category] = $caster;
				}
				$caster->setSection($section);
				$caster->setStartingPort($this->startingport);
			}
		}
		syslog(LOG_DEBUG, "casters constructed");
		return $this;
	}
	
	function __destruct() {
		$this->saveCasters();
		syslog(LOG_DEBUG, "casters destructed");
	}
	
	private function saveCasters() {
		global $TMPDIR;
		$serfile = $TMPDIR . "casters.ser";
		file_put_contents($serfile, serialize($this->casters));
		syslog(LOG_DEBUG, "casters saved");
	}
	
	private function restoreCasters() {
		global $TMPDIR;
		$serfile = $TMPDIR . "casters.ser";
		if (file_exists($serfile)) {
			$this->casters = unserialize(file_get_contents($serfile));
		}
		syslog(LOG_DEBUG, "casters restored");
	}
	
	public function getCasters() {
		return array_keys($this->casters);
	}

	public function getCaster($channel) {
		if (array_key_exists($channel, $this->casters)) {
			return $this->casters[$channel];
		}
		return false;
	}
}
?>
