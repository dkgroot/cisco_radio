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
		exec($command, $res);
		$this->pid = (int)$res[0];
	}

	public function setPid($pid){
		$this->pid = $pid;
	}

	public function getPid(){
		return $this->pid;
	}

	public function status(){
		$command = 'ps -p ' . $this->pid;
		exec($command, $res);
		if (!isset($res[1]))
			return false;
		return true;
	}

	public function start(){
		syslog(LOG_DEBUG, "starting process: {$this->command}\n");
		if ($this->command != '')
			$this->runCom();
		syslog(LOG_DEBUG, "running using: {$this->pid}\n");
		return true;
	}

	public function stop(){
		syslog(LOG_DEBUG, "killing process: {$this->pid}\n");
		$command = 'kill -15 '.$this->pid;
		exec($command);
		return !$this->status();
	}
}
?>
