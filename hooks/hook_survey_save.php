<?php

require_once(dirname(dirname(__FILE__))."/base.php");

/** @var $Core \Plugin_Core */
global $Core;
global $table_pk, $participant_id, $return_code, $lang;

/*echo "<pre>";
print_r($_GET);
echo "</pre>";
exit;*/
//TODO Add in updating the Surveys_complete field to mark the date if ALL surveys for the given event have been completed
//TODO Middle radio button question needs to be marked "Yes" as well?
$Core->Libraries(array("Project","Record"),false);
//echo "Got here<br/>";
if ($survey_hash != "" && $instrument == CONSENT_FORM && empty($_GET['__reqmsg']) && empty($_GET['__reqmsgpre'])) {
	$HtmlPage = new HtmlPage();
	$HtmlPage->PrintHeaderExt();
	echo "</div></div></div>";
	//echo "Above project<br/>";
	$surveyProject = new \Plugin\Project($project_id,$event_id);
	$surveyRecord = new \Plugin\Record($surveyProject,array(array($surveyProject->getFirstFieldName())),array($surveyProject->getFirstFieldName() => $record));
	if ($_POST[CONFIDENT_Q] != "" && $_POST[CONFIDENT_Q] == "0") {
		$surveyRecord->updateDetails(array(CONSENTED => NO));
		$surveyData = $surveyRecord->getDetails();
    }
    else {
		$surveyRecord->updateDetails(array(CONSENTED => YES, CONSENT_DATE => date("Y-m-d")));
		$surveyData = $surveyRecord->getDetails();
	}
	//echo "After consent check<br/>";
    if ($surveyData[CONSENTED] !== NO) {
		$randomized = false;
		if ($_POST[PAPER_SURVEY] != "" && $_POST[PAPER_SURVEY] == "1") {
			$random = rand(1, 10);
			if ($random >= 5) {
				$randomized = "paper";
			} else {
				$random = rand(1, 10);
				if ($random > 7) {
					$randomized = "electronic";
				}
			}
		} else {
			$random = rand(1, 10);
			if ($random >= 5) {
				$randomized = "electronic";
			}
		}
		$randomized = "paper";
		//echo "After randomization<br/>";
		if ($randomized !== false) {
			//$oneWeekRecord = new \Plugin\Record($surveyEvents[$surveyEvents['event_list'][1]]['project'],array(array($surveyProject->getFirstFieldName())),array($surveyProject->getFirstFieldName()=>$record));
            $oneWeekRecord = \Plugin\Record::createRecordFromId($surveyEvents[$surveyEvents['event_list'][1]]['project'],$record);
			$oneWeekRecord->updateDetails(array($surveyProject->getFirstFieldName()=>$record,ONEWEEK_RANDOMIZED=>$randomized,UNIQUE_CODE=>$surveyData[UNIQUE_CODE],CONSENTED=>$surveyData[CONSENTED]));

			if ($randomized == "paper") {
				$researchTeam = getResearchTeam($project_id);
				$recordURL = APP_PATH_WEBROOT_FULL . "/redcap_v{$redcap_version}/DataEntry/grid.php?pid=" . $surveyProject->getProjectId() . "&arm=1&id=" . $record;
				$errorString = emailContacts(EMAIL_PERS_INIT, $recordURL, $researchTeam,$surveyData[LANG_PREF],$surveyProject);
			}
		}
	}
    //echo "After the randomized check<br/>";
	?>
	<script type='text/javascript'>
		$(document).ready(function() {
			var formString = "<form method='POST' action='<?php echo APP_PATH_WEBROOT_FULL; ?>plugins/francis_portal/consent.php' target='_parent'><input type='hidden' name='recordID' value='<?php echo $record; ?>' /><input type='hidden' name='special_code' value='<?php echo $surveyData[UNIQUE_CODE]; ?>' /><input type='hidden' name='return_check' value='1' /></form>";
			//alert(formString);
			$(formString).appendTo('body').submit();
		});
	</script>
	<?php
}