<?php
/**
 * Created by PhpStorm.
 * User: moorejr5
 * Date: 10/20/2016
 * Time: 2:14 PM
 */

require_once(dirname(dirname(__FILE__))."/base.php");

/** @var $Core \Plugin_Core */
global $Core;
global $table_pk, $participant_id, $return_code, $lang;
$Core->Libraries(array("Project","Record"),false);

$surveyEvents = getEventsAsProjects($project_id);

$surveyProject = $surveyEvents[$event_id]['project'];
$surveyRecord = new \Plugin\Record($surveyEvents[$event_id]['project'], array(array($surveyEvents[$event_id]['project']->getFirstFieldName())),array($surveyEvents[$event_id]['project']->getFirstFieldName() => $record));
$details = $surveyRecord->getDetails();
$fieldList = $surveyEvents[$event_id]['project']->getFieldList($instrument);
$sql = "SELECT question_by_section
        FROM redcap_surveys
        WHERE project_id=".$surveyProject->getProjectId()."
        AND form_name='".$instrument."'";
$question_section = db_result(db_query($sql),0);
list ($pageFields, $totalPages) = surveyPageFields($surveyProject, $fieldList, $instrument, $question_section);

$oneWeekSurveys = new \Plugin\RecordSet($surveyEvents[$surveyEvents['event_list'][1]]['project'],array(\Plugin\RecordSet::getKeyComparatorPair($surveyProject->getFirstFieldName(),"!=") => ""));

if (isset($return_code) && !empty($return_code)) {
    // Query data table for data and retrieve field with highest field order on this form
    // (exclude calc fields because may allow participant to pass up required fields that occur earlier)
    $sql = "select m.field_name from redcap_data d, redcap_metadata m where m.project_id = " . $surveyProject->getProjectId() . "
				and d.record = ". pre_query("select record from redcap_surveys_response where return_code = '" . prep($return_code) . "'
				and participant_id = $participant_id and completion_time is null limit 1") . "
				and m.project_id = d.project_id and m.field_name = d.field_name and d.event_id = {$event_id}
				and m.field_name != '$table_pk' and m.field_name != concat(m.form_name,'_complete') and m.form_name = '{$instrument}'
				and m.element_type != 'calc' and d.value != '' order by m.field_order desc limit 1";
    $lastFieldWithData = db_result(db_query($sql), 0);
    // Now find the page of this field
    $foundField = false;
    foreach ($pageFields as $this_page=>$these_fields) {
        foreach ($these_fields as $this_field) {
            if ($lastFieldWithData == $this_field) {
                // Found the page
                $pageCount = $this_page;
            }
            if ($foundField) break;
        }
        if ($foundField) break;
    }
}
// Reduce page number if clicked previous page button
elseif (isset($_POST['submit-action']) && isset($pageFields[$_POST['__page__']]) && is_numeric($_POST['__page__']))
{
    if (!isset($_GET['__reqmsg'])) {
        // PREV PAGE
        if (isset($_GET['__prevpage'])) {
            // Decrement $_POST['__page__'] value by 1
            $pageCount = $_POST['__page__'] - 1;
        }
        // NEXT PAGE
        else {
            // Increment $_POST['__page__'] value by 1
            $pageCount = $_POST['__page__'] + 1;
        }
    } else {
        // If reloaded page for REQUIRED FIELDS, then set Get page as Post page (i.e. no increment)
        $pageCount = $_POST['__page__'];
    }
}

// Make sure page num is not in error
if (!$pageCount || $pageCount < 1 || !is_numeric($pageCount)) {
    $pageCount = 1;
}
//echo "Page Count: $pageCount<br/>";
$percentComplete = ($pageCount -1) / count($pageFields);
$percentString = number_format($percentComplete * 100,0);

$langPref = $surveyRecord->getDetails(LANG_PREF);

?>

<script type='text/javascript'>
    $(document).ready(function () {
		$("<input value='<?php echo ($_GET['lang'] == "es" ? "es" : "en"); ?>' type='hidden' name='lang_hidden' id='lang_hidden'/>").appendTo('#form');
        $("<td class='col-xs-12' style='padding:5px;' colspan='3'><div class='col-xs-12' style='text-align:center;padding-bottom:10px;'><span style='text-decoration: underline;font-size:18px;'>Current Survey Progress</span></div><div class='progress col-xs-12' style='padding:0px;height:25px;'><div class='progress-bar <?php echo ($percentString == 100 ? "progress-bar-success" : "progress-bar-warning"); ?>' role='progressbar' aria-valuenow='<?php echo $percentComplete; ?>' aria-valuemin='0' aria-valuemax='100' style='width:<?php echo $percentString.'%'; ?>; font-size:15px;font-weight:bold;'><?php echo ($percentString < 30 ? "</div><div style='color:black;font-size:15px;font-weight:bold;height:100%;line-height:20px;display:block;width:50%;text-align:center;'>".$percentString."% Complete" : $percentString."% Complete"); ?> </div></div></td><br/>").appendTo("#questiontable");

        function iframeFunc(selectElement) {
			var languageHidden = document.getElementById("lang_hidden");
			var languageElements = document.getElementsByTagName("span");
			if (selectElement != null) {
				var chosenLanguage = selectElement;
				for (var i = 0, n = languageElements.length; i < n; i++) {
					if (languageElements[i].lang == "") continue;
					if (languageElements[i].lang == chosenLanguage) {
						languageElements[i].style.display = "inline-block";
					}
					else {
						languageElements[i].style.display = "none";
					}
				}
				if (typeof(languageHidden) !== undefined && languageHidden != null) {
					languageHidden.value = chosenLanguage;
				}
			}
			else {
				languageHidden.value = 'en';
			}
        }
        oldSelect = parent.languageSelect;
        parent.languageSelect = function (value) {
        	oldSelect(value);
        	iframeFunc(value);
		};
		var lang_select = document.getElementById("lang_hidden");
		iframeFunc(lang_select.value);
    });
</script>