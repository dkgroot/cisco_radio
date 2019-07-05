<?php

openlog("radio", 0, LOG_LOCAL0);
$TMPDIR="/tmp/";

include_once(dirname(__FILE__) . "/lib/lib.php");
include_once(dirname(__FILE__) . "/lib/casters.php");
try {
	$devicename = isset($_GET['devicename']) ? $_GET['devicename'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'SEPBADBAD0101');
	$action = isset($_GET['action']) ? $_GET['action'] : 'list';
	$station = isset($_GET['station']) ? $_GET['station'] : false;
	
	$appId = "ChanSCCP/Radio";
	$baseUrl = get_baseURL(); 
	$appUrl = get_appURL();
	$casters = new Casters();
	$appTitle = "Radio XYZ";

	//$response = "";
	if(isset($_SERVER['HTTP_HOST'])) {
		header("Content-Type: text/xml; charset=UTF-8");		// ISO-8859-1
		header("Expires: -1");
	} else {
		$TMPDIR="./";
	}
	$response = "<?xml version='1.0' encoding='utf-8'?>\n";		// iso-8859-1
	$response .= "<?xml-stylesheet version='1.0' href='lib/CiscoIPPhone.xslt' type='text/xsl'?>\n";
	switch($action) {
		case 'list':
			$response .= "<CiscoIPPhoneMenu appId='{$appId}'\n";
			$response .= "  onAppFocusLost='{$baseUrl}?devicename={$devicename}&amp;action=lost'\n";
			//$response .= "  onAppFocusGained='{$baseUrl}?devicename={$devicename}&amp;action=gained'\n";
			$response .= "  onAppClosed='{$baseUrl}?devicename={$devicename}&amp;action=exit'\n";
			$response .= ">\n";
			$response .= "<Title>{$appTitle}</Title>\n";
			$response .= "<Prompt>Please select a channel</Prompt>\n";

			foreach ($casters->getCasters() as $channel) {
				$caster = $casters->getCaster($channel);
				$response .= "<MenuItem>\n";
				$response .= "  <Name>" . $caster->getName() . "</Name>\n";
				$response .= "  <URL>{$baseUrl}?devicename={$devicename}&amp;action=play&amp;station=" . $caster->getChannel() . "</URL>\n";
				$response .= "</MenuItem>\n";
			}

			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>Select</Name>\n";
			$response .= "  <URL>SoftKey:Select</URL>\n";
			$response .= "	<Position>1</Position>\n";
			$response .= "</SoftKeyItem>\n";

			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>MUTE</Name>\n";
			$response .= "	<URL>RTPMRx:Stop</URL>\n";
			$response .= "	<Position>2</Position>\n";
			$response .= "</SoftKeyItem>\n";
			
			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>Exit</Name>\n";
			$response .= "	<URL>{$baseUrl}?devicename={$devicename}&amp;action=exit</URL>\n";
			$response .= "	<Position>4</Position>\n";
			$response .= "</SoftKeyItem>\n";

			$response .= "</CiscoIPPhoneMenu>";
                	break;
		case 'play':
			if (!$station || !($caster = $casters->getCaster($station))) return false;
			$uri = $caster->addListener($devicename);
			syslog(LOG_DEBUG, "Playing from {$uri}\n");
			
			$response .= "<CiscoIPPhoneText appId='{$appId}'\n";
			$response .= "  onAppFocusLost='RTPRx:Stop'\n";
			$response .= "  onAppFocusGained='RTPRx:Stop;RTPRx:{$uri}'\n";
			$response .= "  onAppClosed='{$baseUrl}?devicename={$devicename}&amp;station={$station}&amp;action=exit'\n";
			$response .= ">\n";
			$response .= "<Title>You are listening to {$station}</Title>\n";
			$response .= "<Text>xxx</Text>\n";

			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>Back</Name>\n";
			$response .= "  <URL>{$baseUrl}?devicename={$devicename}&amp;station={$station}&amp;action=list</URL>\n";
			$response .= "	<Position>1</Position>\n";
			$response .= "</SoftKeyItem>\n";

			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>next</Name>\n";
			$response .= "  <URL>{$baseUrl}?devicename={$devicename}&amp;station={$station}&amp;action=next</URL>\n";
			$response .= "	<Position>2</Position>\n";
			$response .= "</SoftKeyItem>\n";

			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>prev</Name>\n";
			$response .= "  <URL>{$baseUrl}?devicename={$devicename}&amp;station={$station}&amp;action=prev</URL>\n";
			$response .= "	<Position>3</Position>\n";
			$response .= "</SoftKeyItem>\n";

			$response .= "<SoftKeyItem>\n";
			$response .= "	<Name>Exit</Name>\n";
			$response .= "	<URL>{$baseUrl}?devicename={$devicename}&amp;station={$station}&amp;action=exit</URL>\n";
			//$response .= "  <URL>Init:Services</URL>\n";
			$response .= "	<Position>4</Position>\n";
			$response .= "</SoftKeyItem>\n";

			$response .= "</CiscoIPPhoneText>\n";
		case 'gained':
			/*if (!$station || !($caster = $casters->getCaster($station))) return false;
			$uri = $caster->addListener($devicename);
			$response .= "<CiscoIPPhoneExecute>\n";
			$response .= "	<ExecuteItem Priority='0' URL='RTPMRx:Stop'/>\n";
			if (!empty($uri)) {
				$response .= "	<ExecuteItem Priority='0' URL='RTPRx:{$uri}'/>\n";
			}
			$response .= "</CiscoIPPhoneExecute>\n";
			*/
			break;
		case 'lost':
			if (!$station || !($caster = $casters->getCaster($station))) return false;
			$caster->removeListener($devicename);
			/*
			$response .= "<CiscoIPPhoneExecute>\n";
			$response .= "	<ExecuteItem Priority='0' URL='RTPMRx:Stop'/>\n";
			$response .= "</CiscoIPPhoneExecute>\n";
			*/
			break;
		case 'exit':
			if (!$station || !($caster = $casters->getCaster($station))) return false;
			$caster->removeListener($devicename);
			$response .= "<CiscoIPPhoneExecute>\n";
			$response .= "	<ExecuteItem Priority='0' URL='RTPMRx:Stop'/>\n";
			$response .= "	<ExecuteItem Priority='1' URL='Init:Services'/>\n";
			$response .= "</CiscoIPPhoneExecute>\n";
			break;
		default:
			break;
        }
	echo $response;
} catch(Exception $e) {
	syslog(LOG_DEBUG, "Something went wrong". $e . "\n");
}
closelog();
?>
