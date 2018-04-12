<?php

include_once("base.php");

/** @var $Core \Plugin_Core */
global $Core;
//$Core->Libraries(array("ProjectSet","RecordSet","Record"),false);
//$Core->Helpers(array("getRandomIdentifier","lookupTscore"));
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

//$surveyProject = new \Plugin\Project(SURVEY_PROJECT);
$surveyEvents = getEventsAsProjects($surveyProject->getProjectId());
$participantData = array();
/*echo "<pre>";
print_r($surveyEvents);
echo "</pre>";*/

foreach ($surveyEvents as $eventID => $eventData) {
	if (!key_exists('project',$eventData)) continue;
	$currentProject = $eventData['project'];
	$participantRecordSet = new \Plugin\RecordSet($currentProject,array(\Plugin\RecordSet::getKeyComparatorPair($currentProject->getFirstFieldName(),"!=") => ""));
	$participantRecords = $participantRecordSet->sortRecords($currentProject->getFirstFieldName());
	foreach($participantRecords as $participantRecord) {
		$eventData = $participantRecord->getDetails();
		$participantData[$eventData[$currentProject->getFirstFieldName()]][$eventID] = $eventData;
	}
}

$tableHTML = "";

$tableHTML .= "<table class='dataTable cell-border'>
	<thead>
		<tr>
			<th>Participant Record</th><th>Consent Date</th><th>Time 0 Status</th><th>Time 0 Reimbursement</th><th>1 Week Status</th><th>1 Week Reimbursement</th><th>2 Month Status</th><th>2 Month Reimbursement</th><th>4 Month Status</th><th>4 Month Reimbursement</th><th>6 Month Status</th><th>6 Month Reimbursement</th>
		</tr>
	</thead>";
foreach ($participantData as $recordID => $recordData) {
	$tableHTML .= "<tr><td>$recordID</td>";
	foreach ($surveyEvents as $eventID => $eventData) {
		$completeCount = 0;
		$surveyList = $eventData['project']->getSurveyList();
		$participantEvent = $recordData[$eventID];
		if ($participantEvent[CONSENT_DATE] != "") {
			$tableHTML .= "<td>".$participantEvent[CONSENT_DATE]."</td>";
		}
		foreach ($surveyList as $surveyForm) {
			if ($participantEvent[$surveyForm."_complete"] == "2") {
				$completeCount++;
			}
		}

		if ($completeCount == count($surveyList)) {
			$tableHTML .= "<td>Completed<br/>".date("Y-m-d",strtotime($eventData[SURVEYS_COMPLETE]))."</td>";
		}
		elseif($completeCount == 0) {
			$tableHTML .= "<td>Not Started</td>";
		}
		else {
			$percent = $completeCount / count($surveyList);
			$tableHTML .= "<td>In Progress (".number_format($percent * 100, 0)."%)</td>";
		}
	}
	$tableHTML .= "</tr>";
}

$tableHTML .= "</table>";

echo $tableHTML;