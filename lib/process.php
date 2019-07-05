<?php
class Process{
	private $pid;
	private $command;

	public function __construct($commandline = false){
		if ($commandline != false){
			$this->command = $commandline;
		}
	}
	private function runCom(){
		$command = 'nohup '.$this->command.' > /dev/null 2>&1 & echo $!';
		exec($command ,$op);
		$this->pid = (int)$op[0];
	}

	public function setPid($pid){
		$this->pid = $pid;
	}

	public function setPidFile($pidfile){
		if (file_exists($pidfile)) {
			$pid = file_get_contents($pidfile);
			$this->setPid($pid);
			return $this->status();
		}
		return false;
	}

	public function writePidFile($pidfile){
		if (file_exists($pidfile))
			unlink($pidfile);
		file_put_contents($pidfile, $this->getPid());
	}

	public function getPid(){
		return $this->pid;
	}

	public function status(){
		$command = 'ps -p '.$this->pid;
		exec($command,$op);
		if (!isset($op[1]))return false;
		else return true;
	}

	public function start(){
		syslog(LOG_DEBUG, "starting process: {$this->command}\n");
		if ($this->command != '')$this->runCom();
		else return true;
	}

	public function stop(){
		syslog(LOG_DEBUG, "killing process: {$this->pid}\n");
		$command = 'kill '.$this->pid;
		exec($command);
		if ($this->status() == false)return true;
		else return false;
	}
}
?>
