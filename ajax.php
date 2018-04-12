<?php
/**
 * Created by PhpStorm.
 * User: moorejr5
 * Date: 11/21/2016
 * Time: 11:23 AM
 */
define("NOAUTH", true);

include_once("base.php");
require_once("../Core/bootstrap.php");
/** @var $Core \Plugin_Core */

//$Core->Helpers(array("getRandomIdentifier","lookupTscore"));

if(ENVIRONMENT == "DEV" || ENVIRONMENT == "TEST") {
    error_reporting(1);
    ini_set('display_errors', true);
}

$invalidSession = false;
//$surveyProject = new \Plugin\Project(SURVEY_PROJECT);
$surveyEvents = getEventsAsProjects($surveyProject->getProjectId());

$surveyRecord = new \Plugin\Record($surveyEvents[$_POST['eventID']]['project'],array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $_POST['uniqueCode']));
$surveyData = $surveyRecord->getDetails();

$surveyLink = \Plugin\Passthru::passthruToSurvey($surveyRecord,$_POST['formName'],true);
/*$sql = "SELECT d.survey_id
		FROM redcap_surveys d
		WHERE d.project_id=".$surveyProject->getProjectId()."
			AND form_name='".CONSENT_FORM."'";
$surveyID = db_result(db_query($sql),0);;
$hash = Survey::getSurveyHash($surveyID, $surveyEvents['event_list'][0]);*/

if ($surveyLink != "") {
    //header('HTTP/1.1 200 Unauthorized', true, 200);
    echo $surveyLink."&lang=".$_POST['lang'];
}
else {
    //header('HTTP/1.1 401 Unauthorized', true, 401);
	echo "0";
}