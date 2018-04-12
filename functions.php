<?php
function getPassthruUrl($projectId, $participantId) {
	$iv = base64_decode('nPPXunwAj9E*'); /* our initialization vector */
	$key = md5('ClickityClackity');      /* our lovely hard-wired key */
	$input = $projectId."|".$participantId;
	$code = urlencode(base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $input, MCRYPT_MODE_CBC, $iv)));
	$url = "passthru.php?code=$code";

	return $url;
}

// Make sure project id and record exist and are numeric. If not, give error with link back to prev page.
function surveyResponseExists($project_id, $record) {
	//if (!is_numeric($project_id) || !is_numeric($record)) return false;
	$sql = "select 1 from redcap_data where project_id = $project_id and record = '$record' limit 1";
	$q = db_query($sql);
	return (db_num_rows($q) > 0);
}

function getProjectAndEvent($projectShortCode) {
	$sql = "SELECT p.project_id, e.event_id
			FROM redcap_projects p, redcap_events_metadata e, redcap_events_arms a
			WHERE p.app_title LIKE '%($projectShortCode)'
				AND p.project_id = a.project_id
				AND e.arm_id = a.arm_id
			ORDER BY p.project_id, e.event_id";

	if($row = db_fetch_assoc(db_query($sql))) {
		return array($row["project_id"], $row["event_id"]);
	}
	else {
		return array(NULL, NULL);
	}
}


function renderEnumData($data, $enum, $rawOrLabel = "label") {
	// make sure that the \n's are also treated as line breaks
	if (strpos($enum, "\\n")) {
		$enum = str_replace("\\n", "\n", $enum);
	}

	$select_array = explode("\n", $enum);
	$newValue = "";

	foreach ($select_array as $key => $value) {
		if (strpos($value, ",")) {
			$pos = strpos($value, ",");
			$this_value = trim(substr($value, 0, $pos));
			$this_text = trim(substr($value, $pos+1));

			if ($data == $this_value) {
				if ($rawOrLabel == 'raw')
					$newValue = $this_value;
				else if ($rawOrLabel == 'label')
					$newValue = $this_text;
				else
					$newValue = "$this_value,$this_text";
				break;
			}
		}
		else {
			$value = trim($value);

			if ($data == $value) {
				$newValue = $value;
				break;
			}
		}
	}

	return $newValue;
}

function debug($output, $exit = false) {
	if (ENVIRONMENT == "DEV") {
		if (is_array($output) || is_object($output))
			echo "<pre>".print_r($output, true)."</pre><br/>";
		else if (is_bool($output))
			echo ($output) ? "true<br/>" : "false<br/>";
		else
			echo "<pre>$output</pre>";

		if ($exit) exit;
	}
}

function getLockStatus($projectId, $patientRecord, $eventId = "") {
	//global $log;

	//$log->logInfo("Starting Lock ".RANDOM_SESSION);

	while(true) {
		@db_query("COMMIT");

		$currentTimestamp = date("YmdHis");
		$sql = "DELETE FROM redcap_data
				WHERE project_id = $projectId
				AND record = '$patientRecord'
				AND field_name = '__lock_record__'
				AND ($currentTimestamp - value > 60)";
		db_query($sql);

		$sql = "SELECT d.field_name
			FROM redcap_data d
			WHERE d.project_id = {$projectId}
				AND d.record = '$patientRecord'
				AND d.field_name = '__lock_record__'";

		$result = db_query($sql);
		//$log->logInfo("Count ".db_num_rows($result)." ".RANDOM_SESSION);
		if (db_num_rows($result) > 0) {
			$randomSleepTime = rand(5000,25000);
			usleep($randomSleepTime);
			continue;
		}

		$sql = "INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES
			({$projectId},{$eventId},'$patientRecord','__lock_record__','$currentTimestamp')";

		db_query($sql);
		//@db_query("COMMIT");
		//$logSql = $sql;

		# Make sure there is not more than 1 lock record before continuing.
		$sql = "SELECT d.field_name
			FROM redcap_data d
			WHERE d.project_id = {$projectId}
				AND d.record = '$patientRecord'
				AND d.field_name = '__lock_record__'";

		$result = db_query($sql);

		//$log->logInfo("Count ".db_num_rows($result)." ".RANDOM_SESSION);
		if (db_num_rows($result) > 1) {
			# Delete, wait a random amount of time before trying again
			$sql = "DELETE FROM redcap_data
				WHERE project_id = $projectId
					AND record = '$patientRecord'
					AND field_name = '__lock_record__'
				LIMIT 1";
			db_query($sql);

			$randomSleepTime = rand(5000,25000);
			usleep($randomSleepTime);
		}
		else {
			break;
		}
	}
	try {
		@db_query("BEGIN");
	}
	catch (Exception $e) {
		$inTransaction = true;
	}
}

function getRandomAlphaNum($length = 6) {
	$output = "";
	$startNum = pow(32,5) + 1;
	$endNum = pow(32,6);
	while($length > 0) {

		# Generate a number between 32^5 and 32^6, then convert to a 6 digit string
		$randNum = mt_rand($startNum,$endNum);
		$randAlphaNum = numberToBase($randNum,32);

		if($length >= 6) {
			$output .= $randAlphaNum;
		}
		else {
			$output .= substr($randAlphaNum,0,$length);
		}
		$length -= 6;
	}

	return $output;
}

function numberToBase($number, $base) {
	$newString = "";
	while($number > 0) {
		$lastDigit = $number % $base;
		$newString = convertDigit($lastDigit, $base).$newString;
		$number -= $lastDigit;
		$number /= $base;
	}

	return $newString;
}

function convertDigit($number, $base) {
	if($base > 192) {
		chr($number);
	}
	else if($base == 32) {
		$stringArray = "ABCDEFGHJLKMNPQRSTUVWXYZ23456789";

		return substr($stringArray,$number,1);
	}
	else {
		if($number < 192) {
			return chr($number + 32);
		}
		else {
			return "";
		}
	}
}

function checkUserName() {
	return in_array(USERID, ["mcguffk","site_admin"]);
}

############################################
### Log sql update
############################################
function logUpdate($sql, $project_id, $event, $table, $record, $dataValues, $description) {
	// Log the event in the redcap_log_event table
	$ts 	 	= str_replace(array("-",":"," "), array("","",""), NOW);
	$page 	 	= (defined("PAGE") ? PAGE : (defined("PLUGIN") ? "PLUGIN" : ""));
	$userid		= defined("USERID") ? USERID : "[survey respondent]";
	$ip 	 	= (isset($userid) && $userid == "[survey respondent]") ? "" : getIpAddress(); // Don't log IP for survey respondents
	$event	 	= strtoupper($event);
	$event_id	= (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) ? $_GET['event_id'] : "NULL";

	// Query
	$sql = "INSERT INTO redcap_log_event
			(project_id, ts, user, ip, page, event, object_type, sql_log, pk, event_id, data_values, description, change_reason)
			VALUES ($project_id, $ts, '".prep($userid)."', ".checkNull($ip).", '$page', '$event', '$table', ".checkNull($sql).",
			".checkNull($record).", $event_id, ".checkNull($dataValues).", ".checkNull($description).", NULL)";
	//echo "$sql<br/>";
	db_query($sql);
}

# Get the project name for the survey project based on field in master patient list
function surveyCodeToName($surveyCode) {
	switch($surveyCode) {
		case HW_PROJECT_VALUE:
			return HW_PROJECT_NAME;
		case CHD_PROJECT_VALUE:
			return CHD_PROJECT_NAME;
		case COMB_PROJECT_VALUE:
			return COMB_PROJECT_NAME;
	}
}

function csv_to_array($filename='', $delimiter=',', $enclosure='"')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 65535, $delimiter, $enclosure)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function emailContacts($type,$recordURL,$emailArray,$langPref,\Plugin\Project $project) {
	echo "Trying to generate an email<br/>";
    //TODO Create emailing code based on provided language
    $mail = new PHPMailer;
	//echo "Made the mailer<br/>";
    $returnString = "";
    $adminName = $project->getMetadata(EMAIL_SENDER)->getElementLabel();
    $adminEmail = $project->getMetadata(EMAIL_SENDER)->getElementLabel();
	//echo "Made the admin info<br/>";
    foreach ($emailArray as $email) {
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) === false) {
        	//echo "Checking the type for $email<br/>";
            switch ($type) {
                case EMAIL_PERS_INIT:
                	$mailBody = str_replace("<participant_record_link>",$recordURL,$project->getMetadata(RT_PAPER_EMAIL)->getElementLabel());
                    $mail->Subject = $project->getMetadata(RT_PAPER_SUBJECT)->getElementLabel();
                    $mail->msgHTML($mailBody);
                    break;
                case EMAIL_RT_WEEKLY:

                    break;
                case EMAIL_PART_INIT:
                    break;
                default:
                    continue;
            }
            echo "Generating an email<br/>";
            $mail->addReplyTo($adminName,$adminEmail);
            $mail->setFrom($adminName,$adminEmail);
            $mail->addAddress($email,$email);
            echo "Sending an email<br/>";
            if (!$mail->send()) {
                $returnString .= "Mailer Error: ".$mail->ErrorInfo."<br/>";
                //echo $returnString;
            }
            $mail->clearAddresses();
            usleep(10);
        }
    }
    return $returnString;
}

function getEventsAsProjects($projectID) {
    $projectArray = array();

    $sql = "SELECT d.project_id,d2.event_id,d3.form_name,d2.descrip
            FROM redcap_events_arms d
            JOIN redcap_events_metadata d2 ON d.arm_id=d2.arm_id
            JOIN redcap_events_forms d3 ON d2.event_id=d3.event_id
            WHERE d.project_id=".$projectID."
            ORDER BY d2.event_id ASC";
    //echo "$sql<br/>";
    $result = db_query($sql);
    while ($row = db_fetch_assoc($result)) {
        if (!isset($projectArray[$row['event_id']])) {
            $projectArray[$row['event_id']]['project'] = new \Plugin\Project($projectID,$row['event_id']);
            $projectArray[$row['event_id']]['event_name'] = $row['descrip'];
            $projectArray['event_list'][] = $row['event_id'];
        }
        $projectArray[$row['event_id']]['forms'][$row['form_name']] = $row['form_name'];
    }
    if (count($projectArray) === 0) {
        return false;
    }
    return $projectArray;
}

function getCurrentEvent($eventArray,$specialCode) {
    $currentEvent = "";
    try {
        $eventList = $eventArray['event_list'];
        $baseRecord = new \Plugin\Record($eventArray[$eventList[0]]['project'], array(array(UNIQUE_CODE)), array(UNIQUE_CODE => $specialCode));
        //$consentDate = DateTime::createFromFormat('Y-m-d H:i',$baseRecord->getDetails(SURVEY_START_DATE));
        $currentDate = date('Y-m-d H:i');
        //$interval = $consentDate->diff($currentDate);
        //$days = $interval->days;
        //TODO Perform the check here to see if the survey is older than 12 hours and needs to be reset?
        $hours = getDateDifference($baseRecord->getDetails(CONSENT_DATE), $currentDate, "h");
        $days = getDateDifference($baseRecord->getDetails(CONSENT_DATE), $currentDate, "d");

        if (intval($days) >= 180 && intval($days) <= 187) {
            $currentEvent = $eventList[4];
        }
        elseif (intval($days) >= 120 && intval($days) <= 127) {
            $currentEvent = $eventList[3];
        }
        elseif (intval($days) >= 60 && intval($days) <= 67) {
            $currentEvent = $eventList[2];
        }
        elseif (intval($days) >= 7 && intval($days) <= 10) {
            $currentEvent = $eventList[1];
        }
        elseif (intval($days) < 7) {
            $currentEvent = $eventList[0];
        }
    }
    catch (Exception $e) {
        return "error";
    }
    return array($eventList[0],$currentEvent);
}

function surveyPageFields(\Plugin\Project $project, $fieldList, $form_name, $question_section)
{
    // Set page counter at 1
    $page = 1;
    // Field counter
    $i = 1;
    // Create empty array
    $pageFields = array();
    $firstField = $project->getFirstFieldName();
    $metaData = $project->getMetadata();

    // Loop through all form fields and designate fields to page based on location of section headers
    foreach ($fieldList as $field_name)
    {
        $fieldMeta = $metaData->getField($field_name);
        // Do not include record identifier field nor form status field (since they are not shown on survey)
        if ($field_name == $firstField || $field_name == $form_name."_complete") continue;
        // If field has a section header, then increment the page number (ONLY for surveys that have paging enabled)
        if ($question_section && $fieldMeta->getElementPrecedingHeader() != "" && $i != 1) $page++;
        // Add field to array
        $pageFields[$page][$i] = $field_name;
        // Increment field count
        $i++;
    }
    // Return array
    return array($pageFields, count($pageFields));
}

function getDateDifference($base, $compare,$format) {
    $difference = "";
    if ($base == "" || !validateDate($base,"Y-m-d H:i")) {
        $base = date('Y-m-d H:i');
    }
    if ($compare == "" || !validateDate($compare,"Y-m-d H:i")) {
        $compare = date('Y-m-d H:i');
    }

    $baseDate = DateTime::createFromFormat('Y-m-d H:i',$base);
    $compareDate = DateTime::createFromFormat('Y-m-d H:i', $compare);

    $interval = $baseDate->diff($compareDate);

    if ($format == "d") {
        $difference = $interval->y * 365.25 + $interval->m * 30 + $interval->d + ($interval->h + ($interval->i / 60))/24;
    }
    else {
        $difference = ($interval->y * 365.25 + $interval->m * 30 + $interval->d) * 24 + $interval->h + $interval->i / 60;
    }
    return $difference;
}

function resetSurveyData(\Plugin\Project $eventProject, \Plugin\Record $surveyRecord, $eventID, $deleteFields) {
    /*echo "<pre>";
    print_r($deleteFields);
    echo "</pre>";*/
    $surveyRecord->updateDetails($deleteFields);
    $surveyIDs = array();
    $sql = "SELECT survey_id
            FROM redcap_surveys
            WHERE project_id=".$eventProject->getProjectId();
    $result = db_query($sql);
    while ($row = db_fetch_assoc($result)) {
        $surveyIDs[] = $row['survey_id'];
    }

    $participantIDs = array();
    $sql = "SELECT d.participant_id
            FROM redcap_surveys_participants d
            JOIN redcap_surveys_response d2
              ON d.participant_id = d2.participant_id AND d2.record=".$surveyRecord->getId()."
            WHERE d.event_id=".$eventID."
            AND d.survey_id IN (".implode(",",$surveyIDs).")";
    $result = db_query($sql);
    while ($row = db_fetch_assoc($result)) {
        $participantIDs[] = $row['participant_id'];
    }

    foreach ($participantIDs as $participantID) {
        $sql = "UPDATE redcap_surveys_response
              SET start_time=NULL, first_submit_time=NULL, completion_time=NULL, return_code=NULL, results_code=NULL
              WHERE record=".$surveyRecord->getId()." AND participant_id=".$participantID;
        db_query($sql);
    }
}

/*function redirectParticipant($surveyMethod, $currentEvent, $eventList) {
    $returnString = "";
    if ((($surveyMethod == PREFER_PAPER || $surveyMethod == PAPER) && $currentEvent != $eventList[1]) || (($surveyMethod == PREFER_ELECTRONIC || $surveyMethod == ELECTRONIC) && $currentEvent == $eventList[1])) {
        $returnString = "Your records show that you chose to take your surveys through mailed in paper documents. If you believe this is in error please contact: <br/>";
    }
    else {
        $returnString = "<script type='text/javascript'>
                window.location = '" . APP_PATH_WEBROOT_FULL . "plugins/francis_portal/dashboard.php';
            </script>";
    }
    return $returnString;
}*/
function redirectParticipant() {
	$returnString = "<script type='text/javascript'>
		window.location = '" . APP_PATH_WEBROOT_FULL . "plugins/francis_portal/dashboard.php';
    </script>";
	return $returnString;
}

function getResearchTeam($projectID) {
	$returnArray = array();
	$sql = "SELECT d.username, d2.user_email
			FROM redcap_user_rights d
			JOIN redcap_user_information d2
				ON d.username=d2.username
			JOIN redcap_user_roles d3
				ON d.project_id=d3.project_id AND d.role_id=d3.role_id
			WHERE d.project_id=$projectID
            AND d3.role_name='Research Team'";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)) {
		$returnArray[$row['username']] = $row['user_email'];
	}
	return $returnArray;
}