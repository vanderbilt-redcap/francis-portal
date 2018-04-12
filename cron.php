<?php
/**
 * Created by PhpStorm.
 * User: moorejr5
 * Date: 11/29/2016
 * Time: 4:10 PM
 */
define("NOAUTH", true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once("base.php");
?>
<?php

require_once("../Core/bootstrap.php");

/** @var $Core \Plugin_Core */
global $Core;
//$Core->Libraries(array("ProjectSet","RecordSet"),false);
//$Core->Helpers(array("getRandomIdentifier","lookupTscore"));

//$surveyProject = new \Plugin\Project(SURVEY_PROJECT);
$surveyEvents = getEventsAsProjects($surveyProject->getProjectId());

$participantRecordSet = new \Plugin\RecordSet($surveyProject,array(\Plugin\RecordSet::getKeyComparatorPair(UNIQUE_CODE,"!=") => ""));
$participantRecords = $participantRecordSet->sortRecords($surveyProject->getFirstFieldName(),true);

$firstField = $surveyProject->getFirstFieldName();
$eventList = $surveyEvents['event_list'];
$currentDate = date('Y-m-d H:i');
$researchTeamArray = getResearchTeam($surveyProject->getProjectId());

if (date("w",strtotime($currentDate)) == 5) {
	$errorString = emailContacts(EMAIL_RT_WEEKLY,APP_PATH_WEBROOT_FULL."plugins/francis_portal/research_dashboard.php",$researchTeamArray,"en",$surveyProject);
}

foreach ($participantRecords as $participantRecord) {
    $participantData = $participantRecord->getDetails();

    $emailArray = array();
    //TODO Perform the check here to see if the survey is older than 12 hours and needs to be reset?
    //TODO Depends on whether we need to erase half-finished surveys for data reporting purposes
    $hours = getDateDifference($participantData[CONSENT_DATE], $currentDate, "h");
    $days = getDateDifference($participantData[CONSENT_DATE], $currentDate, "d");
    $createRecord = false;
    $sendReminder = false;
    if (intval($days) >= 180 && intval($days) <= 187) {
        $currentEvent = $eventList[4];
    }
    elseif (intval($days) >= 166 && intval($days) < 180) {
        $currentEvent = $eventList[4];
        $createRecord = true;
    }
    elseif (intval($days) >= 120 && intval($days) <= 127) {
        $currentEvent = $eventList[3];
    }
    elseif (intval($days) >= 106 && intval($days) < 120) {
        $currentEvent = $eventList[3];
        $createRecord = true;
    }
    elseif (intval($days) >= 60 && intval($days) <= 67) {
        $currentEvent = $eventList[2];
    }
    elseif (intval($days) >= 46 && intval($days) < 60) {
        $currentEvent = $eventList[2];
        $createRecord = true;
    }
    elseif (intval($days) >= 7 && intval($days) <= 10) {
        $currentEvent = $eventList[1];
    }
    elseif (intval($days) < 7) {
        $currentEvent = $eventList[0];
    }
    if ($currentEvent == "error" || $currentEvent == "" || $currentEvent == $eventList[1]) {
        continue;
    }
    $currentProject = $surveyEvents[$currentEvent]['project'];
    $sql = "SELECT value
            FROM redcap_data
            WHERE project_id=".$surveyProject->getProjectId()."
            AND event_id=".$currentEvent."
            AND field_name='".$firstField."'
            AND value='".$participantData[$firstField]."'";
    $recordID = db_result(db_query($sql),0);

    $copyFields = array();
    //$formCompleteCount = 0;
    foreach ($surveyEvents[$currentEvent]['forms'] as $form) {
        /*if ($participantData[$form . "_complete"] == "2") {
            $formCompleteCount++;
        }*/
        if ($form != PREFILL_FORM) continue;
        $fieldList = $surveyEvents[$currentEvent]['project']->getFieldList($form);

        foreach ($fieldList as $field) {
            $copyFields[$field] = $participantData[$field];
        }
        $copyFields[PREFILL_FORM."_complete"] = "2";
    }

    if ($createRecord) {
		if ($recordID != "") continue;

		$newRecord = \Plugin\Record::createRecordFromId($currentProject, $copyFields[$firstField]);
		$newRecord->updateDetails($copyFields);
		$recordURL = APP_PATH_WEBROOT_FULL . "/redcap_v{$redcap_version}/DataEntry/index.php?pid=" . $currentProject->getProjectId() . "&page=" . PREFILL_FORM . "&id=" . $latestParticipantData[$currentProject->getFirstFieldName()] . "&event_id=" . $currentEvent;

		$errorString = emailContacts(EMAIL_PERS_INIT, $recordURL, $researchTeam,$surveyData[LANG_PREF],$surveyProject);
		$errorString = emailContacts(EMAIL_PERS_INIT, $recordURL, $emailArray);
		if ($errorString != "") {
			echo $errorString . "<br/>";
		}
	}
    else {
        if ($currentEvent < $eventList[1]) continue;
        $latestParticipantRecord = new \Plugin\Record($currentProject,array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $participantData[UNIQUE_CODE]));
        try {
            $latestParticipantData = $latestParticipantRecord->getDetails();
            if (($latestParticipantData[SURVEY_METHOD] == PREFER_ELECTRONIC || $latestParticipantData[SURVEY_METHOD] == ELECTRONIC) && $latestParticipantData[PARTICIPANT_CONTACTED] != "") {
                $latestParticipantRecord->updateDetails(array(PARTICIPANT_CONTACTED => YES));
                $recordURL = APP_PATH_WEBROOT_FULL."/plugins/francis_portal/consent.php";
                if ($latestParticipantData[CONTACT_METHOD] == CONTACT_PHONE) {
                    //TODO Contact participant through the phone number provided?
                }
                else {
                    //TODO Need to have a field to store a participant's name?
                    $emailArray['event_name'] = $surveyEvents[$currentEvent]['event_name'];
                    $emailArray['participant_id'] = $copyFields[UNIQUE_CODE];
                    $emailArray['emails'] = array($copyFields[EMAIL]=>array("to_name"=>$copyFields[UNIQUE_CODE],"to"=>$copyFields[EMAIL]));
                    $errorString = emailContacts(EMAIL_PERS_INIT,$recordURL,$emailArray);
                    if ($errorString != "") {
                        echo $errorString."<br/>";
                    }
                }
            }
        }
        catch (Exception $e) {
            echo $e->getMessage()."<br/>";
        }
    }
}