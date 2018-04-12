<?php
/**
 * Created by PhpStorm.
 * User: mcguffk
 * Date: 1/14/2016
 * Time: 3:34 PM
 */

define("NOAUTH", true);
include_once("base.php");

require_once("../Core/bootstrap.php");
if(ENVIRONMENT == "DEV") {
	error_reporting(1);
	ini_set('display_errors', true);
}

/** @var $Core \Plugin_Core */
$Core->Libraries(array("ProjectSet","RecordSet","Passthru"),false);

$Core->getJs();

$randomId = isset($_GET[RANDOM_ID_GET]) ? db_real_escape_string($_GET[RANDOM_ID_GET]) : null;

include_once("includes/header.php");

## If the session contains a non-existant recordId, then fail, otherwise, pass them into a
## new record or the existing record depending on if the session ID is set and whether it matches
## the current randomId
$dashboardProject = new \Plugin\Project(DASHBOARD_PROJECT);
$paymentProject = new \Plugin\Project(PAYMENT_PROJECT);
$invalidSession = false;

if($_SESSION['currentRecordId']) {
	$record = \Plugin\Record::createRecordFromId($dashboardProject, $_SESSION['currentRecordId']);

	try {
		if($record->getDetails(RANDOM_ID_FIELD) != $randomId) {
			unset($record);
		}
	}
	catch(Exception $e) {
		unset($_SESSION['currentRecordId']);
		$invalidSession = true;
	}
}

if(!isset($record)) {
	header("Location:dashboard.php?".RANDOM_ID_GET."=$randomId");
	die();
}

# If record meets requirements for payment
if($record->getDetails(FEEDBACK_INSTRUMENT."_complete") == 2 && $record->getDetails(PAYMENT_COMPLETE_FIELD) != 1 && $record->getDetails(READY_FOR_PAYMENT_FIELD) == 1) {
	# Check how many others have started completed feedback
	$completeList = new \Plugin\RecordSet($dashboardProject,array(FEEDBACK_INSTRUMENT."_complete" => 2, RANDOM_ID_FIELD => $randomId));

	$alreadyPaidList = $completeList->filterRecords([PAYMENT_COMPLETE_FIELD => 1]);

	# If no other records meet requirement for payment
	if(count($completeList->getRecords()) <= 1 && count($alreadyPaidList->getRecords()) == 0) {
		$paymentRecord = $paymentProject->createNewAutoIdRecord();

		$record->updateDetails([PAYMENT_COMPLETE_FIELD => 1]);

		$surveyLink = \Plugin\Passthru::passthruToSurvey($paymentRecord, "", true);
		echo "<script type='text/javascript'>";

		echo "
		\$(document).ready(function() {
		//	\$('#dialogBox).dialog();
		});

		function openPaymentSeparately() {
			window.location = 'dashboard.php?".RANDOM_ID_GET."=$randomId';
			window.open('$surveyLink');
		}

		function openPaymentHere() {
			window.location = '$surveyLink';
		}";

		echo "</script>";

		echo "<style type='text/css'>";

		echo "div.buttonStyle button {
			background-color: #aaaaaa;
			letter-spacing: normal;
			padding: 0 10px;
		}";

		echo "</style>";

		echo "<section id='three' style='height:100%' class='wrapper style3 special'>
		<div class='inner'>
		<div id='dialogBox' class='buttonStyle'>
		You're about to be directed to the payment information survey. <br /><br />
		If you'd like to return to the dashboard afterwards, click the button on the left and complete the survey in the new tab/window it opens.<br /><br />
		Otherwise, click the button on the right to be taken directly the payment survey.<br /><br />

		<button onclick='openPaymentSeparately(); return false;'>Open new window</button>
		<button onclick='openPaymentHere(); return false;'>Open in this window</button>
		</div></div></section>";

		die();
	}
}
header("Location:dashboard.php?".RANDOM_ID_GET."=$randomId");
die();