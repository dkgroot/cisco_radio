<?php
include_once(dirname(__FILE__) . "/caster.php");

class Casters {
	private $casters = Array();
	private $format = false;
	private $multicast_address = null;
	
	public function __construct() {
		syslog(LOG_DEBUG, "casters contructing");
		$ini = parse_ini_file(dirname(__FILE__) . "/../channels.ini", true);
		foreach($ini as $category => $section) {
			if ($category == "general") {
				$format = isset($section['format']) ? $section['format'] : "unicast";
				$multicast_address = isset($section['multicast_address']) ? $section['multicast_address'] : false;
			} else {
				$this->casters[$category] = new Caster($category, $section, $this->multicast_address);
			}
		}
		syslog(LOG_DEBUG, "casters contructed");
		return $this;
	}
	
	function __destruct() {
		syslog(LOG_DEBUG, "casters destructed");
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
