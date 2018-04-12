<?php
/**
 * Created by PhpStorm.
 * User: mcguffk
 * Date: 8/25/2015
 * Time: 11:39 AM
 */
define("NOAUTH", true);
include_once("base.php");

require_once("../Core/bootstrap.php");

/** @var $Core \Plugin_Core */
$Core->Libraries(array("ProjectSet","RecordSet","Passthru"),false);
$randomId = isset($_GET[RANDOM_ID_GET]) ? db_real_escape_string($_GET[RANDOM_ID_GET]) : null;
$instrumentName = isset($_GET[INSTRUMENT_NAME_GET]) ? db_real_escape_string($_GET[INSTRUMENT_NAME_GET]) : null;

$dashboardProject = new \Plugin\Project(DASHBOARD_PROJECT);
if($_SESSION['currentRecordId']) {
	$record = \Plugin\Record::createRecordFromId($dashboardProject, $_SESSION['currentRecordId']);

//if($randomId) {
	//$record = new \Plugin\Record($dashboardProject, array(array(RANDOM_ID_FIELD)), array(RANDOM_ID_FIELD => $randomId));

	try {
		$recordId = $record->getId();
	}
	catch(Exception $e) {
		die("<div class='col-xs-10 col-xs-offset-1 header'><h1>You've reached this page in error</h1></div>");
	}

	$surveyLink = \Plugin\Passthru::passthruToSurvey($record, $instrumentName, true);

	preg_match_all("/\\?s\\=([a-zA-z0-9]+)/",$surveyLink, $surveyHashes);

	## Print a self submitting form using the GET method (Passthru would automatically use post, which causes adaptive surveys to not load
	echo "<html><body>
	<form name='form' action='$surveyLink' method='get' enctype='multipart/form-data'>
	<input type='hidden' value='{$surveyHashes[1][0]}' name='s' />
	</form>
	<script type='text/javascript'>
		document.form.submit();
	</script>

	</body>
	</html>";
}
else {
	echo "Your session has expired. Please go back to the dashboard page.";
}