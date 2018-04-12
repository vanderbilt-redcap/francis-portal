<?php
/**
 * Created by PhpStorm.
 * User: moorejr5
 * Date: 10/5/2016
 * Time: 10:23 AM
*/
define("NOAUTH", true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once("base.php");

/** @var $Core \Plugin_Core */
global $Core;
//$Core->Libraries(array("ProjectSet","RecordSet"),false);
//$Core->Helpers(array("getRandomIdentifier","lookupTscore"));

include_once("includes/header.php");

//$surveyProject = new \Plugin\Project(SURVEY_PROJECT);
$surveyEvents = getEventsAsProjects($surveyProject->getProjectId());
/*echo "<pre>";
print_r($surveyEvents);
echo "</pre>";*/
/*$methodMetaData = $surveyProject->getMetadata(SURVEY_METHOD);
$methodEnum = $methodMetaData->getElementEnumAsArray();
$contactMetaData = $surveyProject->getMetadata(CONTACT_METHOD);
$contactEnum = $contactMetaData->getElementEnumAsArray();
$genderMetaData = $surveyProject->getMetadata(GENDER);
$genderEnum = $genderMetaData->getElementEnumAsArray();
$raceMetaData = $surveyProject->getMetadata(RACE);
$raceEnum = $raceMetaData->getElementEnumAsArray();
$educationMetaData = $surveyProject->getMetadata(EDUCATION);
$educationEnum = $educationMetaData->getElementEnumAsArray();*/
$currentDate = date('Y-m-d H:i');
$postTime = date('H:i');
$postDate = date("Y-m-d");
if ($_POST['current_time'] != "") {
	$postTime = $_POST['current_time'];
}
if ($_POST['current_date'] != "") {
	$postDate = $_POST['current_date'];
}
$currentDate = date("Y-m-d H:i",strtotime($postDate." ".$postTime));
//echo "Current Date: ".$currentDate."<br/>";
$_SESSION['current_date'] = $currentDate;
$errorMessage = "";
//session_destroy();
/*echo "<pre>";
print_r($_POST);
echo "</pre>";*/

//TODO Put a 12 hour limit on lifetime of a session
if (session_status() == PHP_SESSION_ACTIVE && $_SESSION['survey_start'] != "" && $_SESSION['special_code'] != "") {
    /*echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";*/
    $hours = getDateDifference($_SESSION['survey_start'],$currentDate,"h");

    //$days = $interval->days;
    //$hours = $interval->h + ($interval->i / 60);
    if (intval($hours) >= 12) {
        $errorMessage = "Your previous session has timed out. You will have start your surveys over again.<br/>";
        session_destroy();
    }
    else {
        $specialCode = $_SESSION['special_code'];
        list($baseEvent,$currentEvent) = getCurrentEvent($surveyEvents, $specialCode);
        $participantRecord = new \Plugin\Record($surveyEvents[$currentEvent]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
        try {
            $participantData = $participantRecord->getDetails();
        }
        catch (Exception $e) {
            $baseRecord = new \Plugin\Record($surveyEvents[$baseEvent]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
            $baseData = $baseRecord->getDetails();
            //$participantRecord = \Plugin\Record::createRecordFromId($surveyEvents[$currentEvent]['project'],$baseData[$surveyEvents[$baseEvent]['project']->getFirstFieldName()]);
            //$participantRecord = new \Plugin\Record($surveyEvents[$currentEvent]['project'], array(array($surveyEvents[$currentEvent]['project']->getFirstFieldName())), array($surveyEvents[$baseEvent]['project']->getFirstFieldName() => $baseData[$surveyEvents[$baseEvent]['project']->getFirstFieldName()]));
            $participantData = $baseData;
        }
        /*$participantRecord = new \Plugin\Record($surveyProject,array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $_SESSION['special_code']));
        $participantData = $participantRecord->getDetails();*/
        $errorMessage = redirectParticipant();
    }
}
elseif (isset($_POST) && isset($_POST['return_check']) && $_POST['special_code'] != "") {
	$newParticipant = $_POST['return_check'];
	$surveyRecord = new \Plugin\Record($surveyProject, array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $_POST['special_code']));
	$surveyData = $surveyRecord->getDetails();
	if ($newParticipant === YES) {
		/*$age = $_POST['age_text'];
		$gender = $_POST['gender'];
		$race = $_POST['race'];
		$raceOther = $_POST['race_other'];
		$education = $_POST['education'];
		$specialCode = db_real_escape_string($_POST['special_code']);
		$phone = $_POST['phone'];
		$email = $_POST['email'];
		$consent = $_POST['consent_check'];
		$surveyMethod = $_POST['survey_method'];
		$contactMethod = $_POST['contact_method'];
		$langPref = (($_POST['lang_hidden'] == "en" || $_POST['lang_hidden'] == "es") ? $_POST['lang_hidden'] : "en");*/
		$specialCode = $surveyData[UNIQUE_CODE];
		$email = $surveyData[EMAIL];
		$langPref = $surveyData[LANG_PREF];

		list($baseEvent, $currentEvent) = getCurrentEvent($surveyEvents, $specialCode);
		if ($currentEvent == "error") {
			$errorMessage = "The provided data could not be matched to a valid participant. Please try again.<br/>";
		} elseif ($currentEvent == "") {
			$errorMessage = "You are not currently scheduled to participate in any surveys. You will receive communication when the next list of surveys is ready.<br/>";
		} else {
			/*$baseRecord = new \Plugin\Record($surveyEvents[$baseEvent]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
			$baseData = $baseRecord->getDetails();*/
			$_SESSION['special_code'] = $specialCode;
			$_SESSION['survey_start'] = date("Y-m-d H:i");
			$_SESSION['lang_pref'] = $langPref;
			//$surveyType = $baseData[SURVEY_TYPE];
			$surveyRecord->updateDetails(array(SURVEY_START_DATE => date("Y-m-d H:i")));
			$errorMessage = redirectParticipant();
		}

	} else {
		/*$specialCode = db_real_escape_string($_POST['special_code']);
		$langPref = (($_POST['lang_hidden'] == "en" || $_POST['lang_hidden'] == "es") ? $_POST['lang_hidden'] : "en");*/

		$specialCode = $surveyData[UNIQUE_CODE];
		$langPref = $surveyData[LANG_PREF];
		list($baseEvent, $currentEvent) = getCurrentEvent($surveyEvents, $specialCode);
		if ($currentEvent == "error") {
			$errorMessage = "The provided data could not be matched to a valid participant. Please try again.<br/>";
		} elseif ($currentEvent == "") {
			$errorMessage = "You are not currently scheduled to participate in any surveys. You will receive communication when the next list of surveys is ready.<br/>";
		} else {
			$participantRecord = new \Plugin\Record($surveyEvents[$currentEvent]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
			try {
				$participantData = $participantRecord->getDetails();
				$surveyMethod = $participantData[SURVEY_METHOD];
				$_SESSION['special_code'] = $specialCode;
				$_SESSION['survey_start'] = date("Y-m-d H:i");
				$_SESSION['lang_pref'] = $langPref;
				$errorMessage = redirectParticipant();
			} catch (Exception $e) {
				$errorMessage = "Your data record was not properly set up. Please contact, inform them of this error.<br/>";
			}
		}
	}

	list($baseEvent, $currentEvent) = getCurrentEvent($surveyEvents, $specialCode);
	//$currentEvent = 50;
	if ($currentEvent == "error") {
		$errorMessage = "The provided data could not be matched to a valid participant. Please try again.<br/>";
	} elseif ($currentEvent == "") {
		$errorMessage = "You are not currently scheduled to participate in any surveys. You will receive communication when the next list of surveys is ready.<br/>";
	} else {
		$baseRecord = new \Plugin\Record($surveyEvents[$baseEvent]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
		$baseData = $baseRecord->getDetails();
		$participantRecord = new \Plugin\Record($surveyEvents[$currentEvent]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
		try {
			$participantData = $participantRecord->getDetails();
		} catch (Exception $e) {
			$participantRecord = \Plugin\Record::createRecordFromId($surveyEvents[$currentEvent]['project'], $baseData[$surveyEvents[$baseEvent]['project']->getFirstFieldName()]);
			//$participantRecord = new \Plugin\Record($surveyEvents[$currentEvent]['project'], array(array($surveyEvents[$currentEvent]['project']->getFirstFieldName())), array($surveyEvents[$baseEvent]['project']->getFirstFieldName() => $baseData[$surveyEvents[$baseEvent]['project']->getFirstFieldName()]));
			$participantData = $participantRecord->getDetails();
		}
		# If the person taking the surveys speaks Spanish, they will not have the option of receiving paper surveys
		/*if ($langPref == "es") {
			$surveyMethod = ELECTRONIC;
		}*/

		$deleteFields = array();
		$formCompleteCount = 0;
		foreach ($surveyEvents[$currentEvent]['forms'] as $form) {
			if ($form == PREFILL_FORM) continue;
			$fieldList = $surveyEvents[$currentEvent]['project']->getFieldList($form);
			if ($participantData[$form . "_complete"] == "2") {
				$formCompleteCount++;
			}
			foreach ($fieldList as $field) {
				$deleteFields[$field] = "";
			}
		}
		//$participantData = $participantRecord->getDetails();
		$hours = 0;
		//$days = 0;
		/*echo "<pre>";
		print_r($participantData);
		echo "</pre>";
		exit;*/

		if ($participantData[CONSENTED] === NO) {
			$errorMessage = "This code has already refused consent. This code is now invalid.<br/>";
		} else {
			if ($participantData[SURVEY_START_DATE] != "" && validateDate($participantData[SURVEY_START_DATE], "Y-m-d H:i")) {
				/*$a = DateTime::createFromFormat('Y-m-d H:i',$participantData[SURVEY_START_DATE]);
				$b = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d H:i'));
				$interval = $a->diff($b);
				//$days = $interval->days;
				$hours = $interval->h + ($interval->i / 60);*/
				$hours = getDateDifference($participantData[SURVEY_START_DATE], $currentDate, "h");
			}

			if ($formCompleteCount == count($surveyEvents[$currentEvent]['forms']) - 1) {
				$errorMessage = "You have completed all necessary surveys at this time. You will be contacted the next time you have surveys available.<br/>";
			} else {

				if ($participantData[SURVEY_START_DATE] != "") {
					if (intval($hours) >= 12) {
						session_destroy();
						resetSurveyData($surveyEvents[$currentEvent]['project'], $participantRecord, $currentEvent, $deleteFields);
						session_start();
					}
					$_SESSION['special_code'] = $specialCode;
					$_SESSION['survey_start'] = date("Y-m-d H:i");
					$_SESSION['lang_pref'] = $langPref;
					$participantRecord->updateDetails(array(SURVEY_START_DATE => date("Y-m-d H:i")));

					echo "<script type='text/javascript'>
						window.location = '" . APP_PATH_WEBROOT_FULL . "plugins/francis_portal/dashboard.php';
					</script>";
				} else {
					$_SESSION['special_code'] = $specialCode;
					$_SESSION['survey_start'] = date("Y-m-d H:i");
					$_SESSION['lang_pref'] = $langPref;
					//$surveyType = $baseData[SURVEY_TYPE];
					/*if ($currentEvent != $baseEvent) {
						$surveyMethod = $baseData[SURVEY_METHOD];
						$race = $baseData[RACE];
						$raceOther = $baseData[OTHER_RACE];
						$age = $baseData[AGE];
						$gender = $baseData[GENDER];
						$langPref = $baseData[LANG_PREF];
						$education = $baseData[EDUCATION];
						$contactMethod = $baseData[CONTACT_METHOD];
						$phone = $baseData[PHONE];
						$email = $baseData[EMAIL];
						//$surveyType = $baseData[SURVEY_TYPE];
					}*/

					$participantRecord->updateDetails(array(SURVEY_START_DATE => date("Y-m-d H:i")));

					echo "<script type='text/javascript'>
						window.location = '" . APP_PATH_WEBROOT_FULL . "plugins/francis_portal/dashboard.php';
					</script>";
				}
			}
		}
	}
}

    echo "<div class='header'>
        <h2>Consent to Participation</h2>
    </div>";
    echo "<div id='ajaxresult' class='col-md-offset-3 col-md-6 text_description' style='color:red;margin-bottom:15px;'>$errorMessage</div>
    <div class='col-md-offset-3 col-md-6 text_description'>";
        echo "<span lang='en'>
            This is some language for consenting.<br/>
            <br/>
            It should explain how to consent to things and what the requirements of taking the surveys will be.<br/>
            <br/>
        </span>
        <span lang='es'>
            This is the spanish version of the language for consenting.<br/>
            <br/>
            It would have different language, because it's a different language. Obviously.<br/>
            <br/>
        </span>
    </div>";

    echo "<form name='consent_form' action='consent.php' method='post'>
		<div class='col-md-offset-1 col-md-10 jumbotron' style='padding-top:5px; align-items:center; display:inline-block; font-size:16px;'><div class='col-md-10 col-md-offset-2 consent_input'>
			<span class='col-xs-3'><span lang='en'>".$surveyProject->getMetadata(CONSENT_PORTAL_UNIQUE_CODE."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(CONSENT_PORTAL_UNIQUE_CODE."_es")->getElementLabel()."</span></span>
			<div class='col-xs-9' id='code_div' style='margin-bottom:15px;'>
				<input type='text' name='special_code' id='special_code' ".($_POST['special_code'] != "" ? "value='".$_POST['special_code']."'" : "")." onchange='generateSurveyLink(this.value, \"".$surveyEvents['event_list'][0]."\",\"".CONSENT_FORM."\")'/>
			</div>
			<div class='col-md-12' style='padding-bottom:15px;'>Leave Date and Time FIelds Empty to Use the Real Current Date</div>
			<div class='col-md-12' style='padding:15px;'>
			<span class='col-xs-3'>
				Select Time You Would Like Code to Believe is Current Time
			</span>
			<div class='col-xs-9'>
				<input type='time' name='current_time'/>
			</div>
			</div>
			<div class='col-md-12' style='padding:15px;'>
			<span class='col-xs-3'>
				Select Date You Would Like Code to Believe is Current Date
			</span>
			<div class='col-xs-9'>
				<input type='date' name='current_date'>
			</div>
			</div>
            <span class='col-xs-3'><span lang='en'>".$surveyProject->getMetadata(CONSENT_PORTAL_NEW."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(CONSENT_PORTAL_NEW."_es")->getElementLabel()."</span></span>
            <div class='col-xs-9' id='return_checks'>
                <label for='yes_return'><span lang='en'>Yes</span><span lang='es'>Sí</span></label>
                <input type='radio' onclick='toggleField(\"consent_check_div\",\"consent_submit_div\");' name='return_check' id='yes_return' value='".YES."' ".($_POST['return_check'] == YES ? "checked" : "")."/>
                <br/>
                <label for='no_return'>No</label>
                <input type='radio' onclick='toggleField(\"consent_submit_div\",\"consent_check_div\");' name='return_check' id='no_return' value='".NO."' ".($_POST['return_check'] === NO ? "checked" : "")."/>
            </div>
        </div>";
        echo "<div style='height:500px;display:none;' class='col-md-12 consent_input' id='consent_check_div'>
			<iframe id='consent_frame' width='100%' height='500px' target='_parent'></iframe>
		</div>
		<div style='display:none;' class='col-md-offset-5 col-md-4 consent_input' id='consent_submit_div'>
			<span lang='en'><input type='submit' class='btn btn-primary' value='".$surveyProject->getMetadata(CONSENT_PORTAL_SUBMIT."_en")->getElementLabel()."'></button></span>
			<span lang='es'><input type='submit' class='btn btn-primary' value='".$surveyProject->getMetadata(CONSENT_PORTAL_SUBMIT."_es")->getElementLabel()."'></button></span>
		</div>";
		echo "<input type='hidden' id='lang_hidden' name='lang_hidden'/>
    </form>
</div>";
//echo "</div>";
    /*echo "</body>
    </html>";*/
/*echo "<pre>";
print_r($_SESSION);
echo "</pre>";*/
include_once("includes/footer.php");