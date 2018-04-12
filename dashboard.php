<?php
/**
 * Created by PhpStorm.
 * User: mcguffk
 * Date: 8/25/2015
 * Time: 10:23 AM
 */

define("NOAUTH", true);

include_once("base.php");
require_once("../Core/bootstrap.php");
/** @var $Core \Plugin_Core */
global $Core;
//$Core->Libraries(array("Passthru","Project","Record","ProjectSet","RecordSet"),false);
//$Core->Helpers(array("getRandomIdentifier","lookupTscore"));

if (isset($_POST['reset_session'])) {
    session_destroy();
    echo "<script type='text/javascript'>
		window.location = '" . APP_PATH_WEBROOT_FULL . "plugins/francis_portal/consent.php';
    </script>";
}
if(ENVIRONMENT == "DEV" || ENVIRONMENT == "TEST") {
    error_reporting(1);
    ini_set('display_errors', true);
}
?>

    <script type="text/javascript">
        function navigateSurvey(surveyLink,returnCode) {
            var navForm = $('<form/>')
                .attr({
                    'action':surveyLink,
                    'method': 'post',
                    'target': '_blank',
                    'id':'selfNavigate'
                });
            if (returnCode != "") {
                navForm.append($('<input/>')
                        .attr({
                            'type': 'hidden',
                            'name': '__code',
                            'value': returnCode
                        })
                )
            }
            $('body').append(navForm).find('#selfNavigate').submit();
        }

        var time = new Date().getTime();
        $(document.body).bind("mousemove keypress", function(e) {
            if(new Date().getTime() - time >= 7500)
                window.location.reload(true);

            time = new Date().getTime();
        });
    </script>

<?php
$invalidSession = false;
//$surveyProject = new \Plugin\Project(SURVEY_PROJECT);
$surveyEvents = getEventsAsProjects($surveyProject->getProjectId());

$specialCode = $_SESSION['special_code'];
$surveyStartDate = $_SESSION['survey_start'];
$currentDate = date('Y-m-d H:i');
$surveyList = array();
if ($_SESSION['current_date'] != "") {
	$currentDate = $_SESSION['current_date'];
}
/*echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "Special Code: ".$specialCode."<br/>";
exit;*/
//echo "Special Code: ".$specialCode."<br/>";

if($specialCode != "") {
    list($baseEvent,$currentEvent) = getCurrentEvent($surveyEvents,$specialCode);
    //$currentEvent = 50;
    //echo "Current Event: ".$currentEvent."<br/>";
    $participantRecord = new \Plugin\Record($surveyEvents[$currentEvent]['project'],array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
    //$record = \Plugin\Record::createRecordFromId($dashboardProject, $currentRecordId);
    try {
        $participantRecord->getId();
    }
    catch (Exception $e) {
        echo "Error!<br/>";
    }
    try {
        $participantData = $participantRecord->getDetails();
        /*echo "<pre>";
        print_r($participantData);
        echo "</pre>";*/
        $currentRecordId = $participantData[$surveyProject->getFirstFieldName()];

        if($participantData[UNIQUE_CODE] != $specialCode || $participantData[CONSENTED] != YES) {
            unset($participantRecord);
            session_destroy();
            $invalidSession = true;
        }
        else {
            if(isset($surveyStartDate) && $surveyStartDate != "") {
                //TODO If timestamp is past good time, need to invalidate everything and lock participant out, delete their surveys
                //TODO Doesn't even have the possibility of coming in here, I believe
                //$_SESSION['consent_time'] = "2016-11-10 12:21";

                $hours = getDateDifference($surveyStartDate,$currentDate,"h");

                if (intval($hours) >= 12) {
                    $invalidSession = true;
                    $_SESSION = array();
                    resetSurveyData($surveyEvents[$currentEvent]['project'], $participantRecord, $currentEvent, $deleteFields);
                    session_unset();
                    session_destroy();
                    session_start();
                }
            }
        }
    }
    catch(Exception $e) {
        session_destroy();
        $invalidSession = true;
    }
    ## If session is valid and record already exists, need to do this check here
    if(!$invalidSession) {
        $sql = "SELECT d.*,d2.form_menu_description
		        FROM redcap_surveys d
		        JOIN redcap_metadata d2
		          ON d.project_id=d2.project_id AND d.form_name=d2.form_name AND d2.form_menu_description IS NOT NULL
                JOIN redcap_events_forms d3
                  ON d2.form_name = d3.form_name AND d3.event_id='$currentEvent'
		        WHERE d.project_id=".$surveyProject->getProjectId()."
		            ORDER BY d2.field_order ASC";
        //echo "$sql<br/>";
        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            $surveyList[$row['survey_id']] = $row;
        }
    }
}

include_once("includes/header.php");

echo "<div class='header'>
        <span lang='en'><h2>".$surveyProject->getMetadata(DASHBOARD_PORTAL_HEADER."_en")->getElementLabel()."</h2></span>
        <span lang='es'><h2>".$surveyProject->getMetadata(DASHBOARD_PORTAL_HEADER."_es")->getElementLabel()."</h2></span>
    </div>";
echo "<div class='col-md-offset-3 col-md-6 text_description'>";
echo "<span lang='en'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_BODY."_en")->getElementLabel()."</span>
            <span lang='es'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_BODY."_es")->getElementLabel()."</span>
    </div>";
echo "<form name='reset_form' action='dashboard.php' method='post'><div class='col-md-offset-5 col-md-4'><input type='submit' value='Reset Session and Return to Consent Page' name='reset_session'/></div></form>";
?>

<?php
//echo "Current Record ID: ".$currentRecordId.", Invalid Session? ".($invalidSession ? "True" : "False")."<br/>";
if($currentRecordId != "" && !$invalidSession) {
    echo "<form name='dashboard_form' action='dashboard.php' method='post'>
        <div class='col-md-offset-3 col-md-6 consent_input jumbotron' style='padding:25px;'>
        <table class='table table-bordered' style='text-align:center;'>
            <thead><tr><th style='text-align:center;'><span lang='en'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_NHEAD."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_NHEAD."_es")->getElementLabel()."</span></th><th style='text-align:center;'><span lang='en'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_AHEAD."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_AHEAD."_es")->getElementLabel()."</span></th><th style='text-align:center;'><span lang='en'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_THEAD."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_THEAD."_es")->getElementLabel()."</span></th></tr></thead>";
        foreach ($surveyList as $surveyID => $surveyData) {
            $surveyLink = \Plugin\Passthru::passthruToSurvey($participantRecord, $surveyData['form_name'], true);
            $sql = "SELECT r.return_code
				FROM redcap_surveys_participants p, redcap_surveys_response r
				WHERE p.survey_id = '$surveyID'
					AND p.participant_id = r.participant_id
					AND r.record = '".$currentRecordId."'
					AND p.event_id = '".$currentEvent."'";
            //echo "$sql<br/>";
            $queryResults = db_fetch_assoc(db_query($sql));
            $returnCode = $queryResults['return_code'];
            /*echo "<pre>";
            print_r($participantData);
            echo "</pre>";*/
            echo "<tr".($participantData[$surveyData['form_name']."_complete"] == "2" ? " style='background-color:lightgreen;'" : "").">";
            echo "<td>".$surveyData['form_menu_description']."</td>";
            echo "<td>".($participantData[$surveyData['form_name']."_complete"] == "2" ? "<span lang='en'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_ACOMP."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_ACOMP."_es")->getElementLabel()."</span>" : "<a href='#' onclick='navigateSurvey(\"".$surveyLink."\",\"".$returnCode."\");'><span lang='en'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_ASURV."_en")->getElementLabel()."</span><span lang='es'>".$surveyProject->getMetadata(DASHBOARD_PORTAL_ASURV."_es")->getElementLabel()."</span></a>")."</td>";
            echo "<td>20-25 minutes</td>";
            echo "</tr>";
        }
        echo "</table>
        <input type='hidden' id='lang_hidden' name='lang_hidden'/>
        </div>
    </form>";
}
else {
    echo "<script type='text/javascript'>
                window.location = '" . APP_PATH_WEBROOT_FULL . "plugins/francis_portal/consent.php';
            </script>";
}

include_once("includes/footer.php");
