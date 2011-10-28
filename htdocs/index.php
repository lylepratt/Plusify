<?php
/* START INCLUDES */
include('../includes/Mustache.php');
/* END INCLUDES */


class Plusify {

	/* START CONFIGURATION */
	private $api_url = "https://www.googleapis.com/plus/v1";
	private $api_key = "YOUR GOOGLE API KEY";
	private $google_id = "YOUR GOOGLE + ID";
	/* END CONFIGURATION */

	private $SETTINGS = array();

	function __construct() {
   		$this->SETTINGS['google_id'] = $this->google_id;
   		$this->SETTINGS['api_key'] = $this->api_key;
   		$this->SETTINGS['api_url'] = $this->api_url;
   		$this->SETTINGS['user_ip'] = $_SERVER['REMOTE_ADDR'];
   	}

	function doRequest($url) {

		$ch = curl_init( $url . "?key={$this->SETTINGS['api_key']}&userIp={$this->SETTINGS['user_ip']}");

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
		);

		curl_setopt_array( $ch, $options );

		$result = curl_exec($ch); // Getting jSON result string
		$result = json_decode($result, true);

		return $result;
	}

	function getMe() {
		return $this->doRequest("{$this->SETTINGS['api_url']}/people/{$this->SETTINGS['google_id']}");		
	}

	function getPerson($id) {
		return $this->doRequest("{$this->SETTINGS['api_url']}/people/{$id}");
	}

	function getActivityList($person_id) {
		return $this->doRequest("{$this->SETTINGS['api_url']}/people/{$person_id}/activities/public");	
	}

	function getMyActivities() {
		return $this->doRequest("{$this->SETTINGS['api_url']}/people/{$this->SETTINGS['google_id']}/activities/public");		
	}

	function getActivity($id) {
		return $this->doRequest("{$this->SETTINGS['api_url']}/activities/{$id}");	
	}

	function getComments($id) {
		return $this->doRequest("{$this->SETTINGS['api_url']}/activities/{$id}/comments");
	}
}

$plusify = new Plusify;
$mustache = new Mustache;

if(empty($_GET)) {

	ob_start();
	require_once('../theme/home.php');
	$template = ob_get_contents();
	ob_end_clean();

	$activities = $plusify->getMyActivities();
	$object['content'] = $activities;

	echo $mustache->render($template, $object);

}

if(isset($_GET['activity'])) {

	$activity_id = $_GET['activity'];

	ob_start();
	require_once('../theme/activity.php');
	$template = ob_get_contents();
	ob_end_clean();

	$activity = $plusify->getActivity($activity_id);
	$comments = $plusify->getComments($activity_id);

	$object['content'] = $activity;
	$object['comments'] = $comments;

	echo $mustache->render($template, $object);

	//echo "<pre>";
	//echo print_r($activity);
	//echo "</pre>";
}

if(isset($_GET['style'])) {
	echo file_get_contents("../theme/style.css");
}



?>