<?php
/**
 * Created by PhpStorm.
 * User: moorejr5
 * Date: 10/29/14
 * Time: 1:12 PM
 */
require_once("base.php");

echo "<script type='text/javascript'>
	//Call the file input's click function when the placeholder button is clicked
	function getFile(){
        document.getElementById('uploadedfile').click();
    }
	//When a file is chosen for upload, this updates the site to display the chosen file's name.
	function getStats(fName){
		shortName = fName.replace(/^.*[\\\/]/, '');
		document.getElementById('chosenfile').innerHTML = shortName
	}
</script>";
$redCAPUrl = "";
if (ENVIRONMENT == "PROD") {
	$redCAPUrl = "redcap.vanderbilt.edu";
}
else {
	$redCAPUrl = "redcaptest.vanderbilt.edu";
}

# Check to make sure that a file was uploaded.
if (isset($_FILES['uploadedfile'])) {
	list($patientProjectId) = [];

	# Create the header line for the report that we will be generating listing names and Q'd surveys
	$fileName = date("YmdHis")."_pcori_report.csv";
	$filePath = dirname(APP_PATH_DOCROOT)."/temp/".$fileName;
	file_put_contents($filePath,"Name,Survey Queued For\r\n");

	$exclusionRecords = array(); # Serves as a list of records that are marked as declined or already consented
	$doNotSendRecords = array(); # Serves as a list of records that should not receive further emails
	$duplicates = array(); # Keeps a list of all duplicate records: Either within the import file itself or within the database
	$eligibleEmails = array(); # List of eligible patients
	$ineligibleEmails = array(); # List of ineligible patients
	$notstoredEmails = array(); # List of patients that are in the import file but NOT in the database
	$queueEnum = ''; # Keep the field enum values for the survey_bucket field

	$uploadedFileLocation = $_FILES['uploadedfile']["tmp_name"];
	if($uploadedFileLocation == "") {
		echo "<h4>File to upload was not provided.</h4>";
	}

	list($exclusionRecords,$doNotSendRecords) = getExclusionRecords($patientProjectId);

	# Get the field enum for the data field
	$sql = "SELECT element_enum
			FROM redcap_metadata
			WHERE project_id=$patientProjectId
			AND field_name='survey_bucket'";
	$queueEnum = db_result(db_query($sql),0);

	$importDOBs = array(); # List of all the date of births in the import document
	$importFirstNames = array(); # List of all the first names in the import document
	$importLastNames = array(); # List of all the last names in the import document
	$emailList = array(); # Master list of emails that come from the import document
	$needHeader = true; # Flag to indicate whether the import file header has already been passed
	$startFile = date("Y-m-d H:i:s"); # Track time that the process started
	$count = 0;
	$handle = fopen(str_replace(" ", "\x20", $uploadedFileLocation), "rb");

	if ($handle) {
		while (($line = fgetcsv($handle)) !== false) {
			# Import file has 3 lines of header data
			if ($count < 3 && $needHeader) {
				$count++;
				continue;
			}
			$needHeader = false;
			# If the Date of Birth is blank for some reason, skip it
			if ($line[3] == "")
				continue;
			$importDOBs[] = date("Y-m-d",strtotime($line[3]));
			$importFirstNames[] = db_real_escape_string(strtoupper($line[2]));
			$importLastNames[] = db_real_escape_string(strtoupper($line[1]));
			# Make sure that the import document does not have any duplicate records in it. Keep track if it does to report it
			if ($emailList[date("Y-m-d",strtotime($line[3]))."::".db_real_escape_string(strtoupper($line[2]))."::".db_real_escape_string(strtoupper($line[1]))] != '')
				$duplicates[] = "Import File Had Duplicates for DOB: ".date("Y-m-d",strtotime($line[3])).", First Name: ".$line[2].", Last Name: ".$line[1]."<br/>";
			else
				$emailList[date("Y-m-d",strtotime($line[3]))."::".db_real_escape_string(strtoupper($line[2]))."::".db_real_escape_string(strtoupper($line[1]))] = $line[9];
			$count++;

			# Limit the number of people who will be examined at the same time. Reset everything this happens.
			if ($count >= 250) {
				findPatientList($patientProjectId,$exclusionRecords,$doNotSendRecords,$importDOBs,$importFirstNames,$importLastNames,$emailList);
				foreach ($emailList as $key => $email) {
					//echo "Outside Ineligible Email: ".$email."<br/>";
					$data = explode("::",$key);
					$notstoredEmails[$key]['email'] = $email;
					$notstoredEmails[$key]['name'] = $data[1]." ".$data[2];
					$notstoredEmails[$key]['survey_bucket'] = "Not eligible.";
				}
				$count = 0;
				$importDOBs = array();
				$importFirstNames = array();
				$importLastNames = array();
				$emailList = array();
				continue;
			}
		}
		findPatientList($patientProjectId,$exclusionRecords,$doNotSendRecords,$importDOBs,$importFirstNames,$importLastNames,$emailList);

		foreach ($emailList as $key => $email) {
			//echo "Outside Ineligible Email: ".$email."<br/>";
			$data = explode("::",$key);
			$notstoredEmails[$key]['email'] = $email;
			$notstoredEmails[$key]['name'] = $data[1]." ".$data[2];
			$notstoredEmails[$key]['survey_bucket'] = "Not eligible.";
		}
	}
	else {
		echo "There was a problem opening the file.";
		exit;
	}
	emailParticipants($eligibleEmails,"eligible",$filePath,$redCAPUrl);
	emailParticipants($ineligibleEmails,"ineligible",$filePath,$redCAPUrl);
	emailParticipants($notstoredEmails,"not_stored",$filePath,$redCAPUrl);
}

# Get the list of people to email based on records in the database
function findPatientList($patientProjectId,$exclusionRecords,$doNotSendRecords,$importDOBs,$importFirstNames,$importLastNames,&$emailList) {
	global $eligibleEmails,$ineligibleEmails,$duplicates,$queueEnum;
	$startSql = date("Y-m-d H:i:s");
	$includedRecords = array();

	# Due to the incredible amount of data stored in the project, it is faster to pull each field separately rather
	# than to perform joins on the data.
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='dob'
			AND value IN ('".implode("','",$importDOBs)."')
			AND record != ''";
	//echo "$sql<br/>";
	$result = db_query($sql);
	while ($row=db_fetch_assoc($result)) {
		$includedRecords[$row['record']]['dob'] = $row['value'];
	}
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='name_first'
			AND value IN ('".implode("','",$importFirstNames)."')
			AND record IN ('".implode("','",array_keys($includedRecords))."')";
	//echo "$sql<br/>";
	$result = db_query($sql);
	while ($row=db_fetch_assoc($result)) {
		$includedRecords[$row['record']]['name_first'] = strtoupper($row['value']);
	}
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='name_last'
			AND value IN ('".implode("','",$importLastNames)."')
			AND record IN ('".implode("','",array_keys($includedRecords))."')";
	$result = db_query($sql);
	while ($row=db_fetch_assoc($result)) {
		$includedRecords[$row['record']]['name_last'] = strtoupper($row['value']);
	}
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='survey_bucket'
			AND record IN ('".implode("','",array_keys($includedRecords))."')";
	$result = db_query($sql);
	while ($row=db_fetch_assoc($result)) {
		$includedRecords[$row['record']]['survey_bucket'] = $row['value'];
	}
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='unique_id'
			AND record IN ('".implode("','",array_keys($includedRecords))."')";
	$result = db_query($sql);
	while ($row=db_fetch_assoc($result)) {
		$includedRecords[$row['record']]['unique_id'] = $row['value'];
	}

	# Keep a list of duplicates from the database records, remove any duplicate or empty data records from the array
	$checkDuplicates = array();
	foreach ($includedRecords as $record => $data) {
		# Remove any records that have missing data fields
		if ($data['dob'] == '' || $data['name_first'] == '' || $data['name_last'] == '') {
			unset($includedRecords[$record]);
			continue;
		}
		# If a record already exists in the duplicates array, mark the duplicate
		if ($checkDuplicates[$data['dob'].$data['name_first'].$data['name_last']] != '') {
			$duplicates[] = "Database Contained Duplicates for DOB: ".$data['dob'].", First Name: ".$data['name_first'].", Last Name: ".$data['name_last']."<br/>";
			unset($includedRecords[$record]);
			continue;
		}
		$checkDuplicates[$data['dob'].$data['name_first'].$data['name_last']] = $record;
	}
	foreach ($includedRecords as $record => $data) {
		# If person was marked as being declined or consented already, put them in the ineligible list, otherwise put them in the eligible list
		# Remove found people from the total list so we know what people are not found in the database (whoever remains in the email list at the end)
		if ($exclusionRecords[$record] == $record){
			# If person is in the doNotSendRecords list, don't do anything except unset their emailList record.
			if($doNotSendRecords[$record] == $record) {
				unset($emailList[$data['dob']."::".$data['name_first']."::".$data['name_last']]);
				echo "Not sending emails to ".($data['dob']."::".$data['name_first']."::".$data['name_last'])." <br />";
				continue;
			}

			//echo "Ineligible Email: ".$emailList[$data['dob'].$data['name_first'].$data['name_last']]."<br/>";
			$ineligibleEmails[$record]['email'] = $emailList[$data['dob']."::".$data['name_first']."::".$data['name_last']];
			$ineligibleEmails[$record]['name'] = $data['name_first']." ".$data['name_last'];
			$ineligibleEmails[$record]['survey_bucket'] = "Not eligible";
			unset($emailList[$data['dob']."::".$data['name_first']."::".$data['name_last']]);
		}
		elseif ($emailList[$data['dob']."::".$data['name_first']."::".$data['name_last']] != '') {
			//echo "Eligible Email: ".$emailList[$data['dob'].$data['name_first'].$data['name_last']]."<br/>";
			$eligibleEmails[$record]['email'] = $emailList[$data['dob']."::".$data['name_first']."::".$data['name_last']];
			$eligibleEmails[$record]['name'] = $data['name_first']." ".$data['name_last'];
			$eligibleEmails[$record]['survey_bucket'] = renderEnumData($data['survey_bucket'],$queueEnum,"label");
			$eligibleEmails[$record]['unique_id'] = $data['unique_id'];
			unset($emailList[$data['dob']."::".$data['name_first']."::".$data['name_last']]);
		}
	}
	$endSql = date("Y-m-d H:i:s");
	/*echo "Started SQL: ".$startSql."<br/>";
	echo "Ended SQL: ".$endSql."<br/>";*/
}

# Function to actually perform the emails.
function emailParticipants($emailList,$emailType,$filepath,$redCAPUrl) {
	global $patientProjectId;

	@db_query("BEGIN");
	$statements = array();
	$dataValues = array();
	$message = "";
	$subject = "PCORI Survey Eligibility";
	//$headers = "From: noreply@vanderbilt.edu\r\n";
    global $from_email;
    if($from_email != '') {
        $headers = "From: ".$from_email."\r\n";
    }
    else {
        $headers = "From: noreply@vanderbilt.edu\r\n";
    }
	$headers .= 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	# Each survey requires its own email body message. A link to the person's consent page goes between the 1 & 2 type messages.
	if ($emailType == 'eligible') {
		$messageA1 = "<html><body><img src='https://redcap.vanderbilt.edu/plugins/pcori/images/logo.jpg' width='390' height='128'/><br/><br/>
		Thank you so much for your interest in participating in the Healthy Weight Study.  Based on our records, you are eligible to participate!<br/><br/>
		The Vanderbilt Institute for Medicine and Public Health is partnering with the Vanderbilt Internal Medicine Clinic to conduct a research study that asks about your interest in participating in future medical research, along with some questions about your health habits and lifestyle.
		We are looking for adults 18 and older who have previously received care at the Vanderbilt Internal Medicine Clinic to fill out a short survey. The survey takes approximately 15 minutes to complete.
		Participation in this study is totally voluntary and you do not have to participate in this study if you do not want to.<br/><br/>
		The study is designed to ensure the security and confidentiality of the information that you provide.  Upon completion of the survey you will receive a $10 gift card.
		If you are interested in participating, please click on the secure link below.<br/>";
		$messageA2 = "Thank you for your participation,<br/>
		If you have any questions or comments regarding the study, feel free to contact us:<br/>
		Bill Heerman MD MPH, Principal Investigator<br/>
		David Crenshaw LMSW, Research Coordinator<br/>
		HealthyWeightStudy@Vanderbilt.edu<br/>
		(615) 343-1765<br/></body></html>";

		$messageB1 = "<html><body><img src='https://redcap.vanderbilt.edu/plugins/pcori/images/logo.jpg' width='390' height='128'/><br/><br/>
		Thank you so much for your interest in participating in the Healthy Weight Study.  Based on our records, you are eligible to participate in two surveys; the Healthy Weight Survey and the Heart Health Survey!<br/><br/>

		The first part of the survey will take about 10-15 minutes and you will receive $10 for your time and participation. There is also an optional second section which will take an extra 5-10 minutes, and you will receive an additional $10 if you choose to complete it.
		This survey includes questions about:<br/>
		<ul>
		<li>Your background</li>
		<li>Your health and habits</li>
		<li>Your willingness to participate in certain types of research studies in the future</li>
		</ul>
		Your participation in this survey is totally voluntary.  If you choose not to participate, it will not affect your health care or opportunity to participate in future research.
		If you participate, we would like to collect some information from your medical chart, such as your height, weight, blood pressure, lab test results, and other health information now and in the future.<br/><br/>

		There is very little risk involved in this survey.  The main risk is that some questions may make you feel uncomfortable.   You may choose not to answer any of the questions.  Your responses will be kept private.
		If you are interested in participating, please click on the secure link below.<br/>";
		$messageB2 = "Thank you for your participation,<br/>
		If you have any questions or comments regarding the study, feel free to contact us:<br/><br/>

		Cardella Leak, Study Coordinator<br/>
		HeartHealthSurvey@Vanderbilt.edu<br/>
		(615) 936-0997<br/><br/>

		David Crenshaw, Study Coordinator<br/>
		HealthyWeightSurvey@vanderbilt.edu<br/>
		(615) 343-1765<br/><br/>

		Thank you!<br/></body></html>";
	}
	# If they are ineligible or just not in the system at all, they need the ineligible email sent.
	elseif ($emailType == 'ineligible' || $emailType == 'not_stored') {
		$message = "<html><body>Thank you so much for your interest in participation in the Healthy Weight Study survey.<br/><br/>
		Our records indicate that you are not eligible to take the survey at this time, but we wish to thank you for taking the time to contact us.<br/><br/>
		If you have any questions please contact:<br/>
		David Crenshaw<br/>
		healthyweightstudy@vanderbilt.edu<br/>
		(615) 343-1765<br/></body></html>";
	}

	try {
		$handle = fopen($filepath,"a");
		$baseSql = "INSERT INTO redcap_data (project_id, event_id, record, field_name, value) VALUES ";
		if ($emailType != "not_stored") {
			$sql = "SELECT d.record, d.event_id, COALESCE(d2.value,'NULL') as email_sent, COALESCE(d3.value,'NULL') as email_sent_date
				FROM redcap_data d
				LEFT JOIN redcap_data d2
					ON d.project_id=d2.project_id AND d.record=d2.record AND d2.field_name='email_sent'
				LEFT JOIN redcap_data d3
					ON d.project_id=d3.project_id AND d.record=d3.record AND d3.field_name='email_sent_date'
				WHERE d.project_id=$patientProjectId
				AND d.field_name='mrn'
				AND d.record IN ('".implode("','",array_keys($emailList))."')";
			//echo "$sql<br/>";
			$result = db_query($sql);

			while ($row = db_fetch_assoc($result)) {
				if ($emailList[$row['record']]['email'] != '') {
					if ($emailList[$row['record']]['survey_bucket'] == "A: Weight Only") {
						$message = $messageA1."<a href='https://".$redCAPUrl."/plugins/pcori/index.php?code=".$emailList[$row['record']]['unique_id']."'>https://".$redCAPUrl."/plugins/pcori/index.php?code=".$emailList[$row['record']]['unique_id']."</a><br/><br/>".$messageA2;
					}
					elseif ($emailList[$row['record']]['survey_bucket'] == "B: Combined") {
						$message = $messageB1."<a href='https://".$redCAPUrl."/plugins/pcori/index.php?code=".$emailList[$row['record']]['unique_id']."'>https://".$redCAPUrl."/plugins/pcori/index.php?code=".$emailList[$row['record']]['unique_id']."</a><br/><br/>".$messageB2;
					}
					elseif ($emailList[$row['record']]['survey_bucket'] == "C: CHD Only") {
						$message = "";
					}
					# Perform the inserts on the email fields to indicate the person has been sent the email (only if it wasn't done already).
					if ($row['email_sent'] == 'NULL') {
						$statements[] = "($patientProjectId, ".$row['event_id'].", '".$row['record']."', 'email_sent', '1')";
						logUpdate($baseSql."($patientProjectId, ".$row['event_id'].", '".$row['record']."', 'email_sent', '1')",$patientProjectId,"UPDATE","redcap_data",$row['record'],"email_sent = 1","Update record");
					}
					if ($row['email_sent_date'] == 'NULL') {
						$statements[] = "($patientProjectId, ".$row['event_id'].", '".$row['record']."', 'email_sent_date', '".date('Y-m-d')."')";
						logUpdate($baseSql."($patientProjectId, ".$row['event_id'].", '".$row['record']."', 'email_sent_date', '".date('Y-m-d')."')",$patientProjectId,"UPDATE","redcap_data",$row['record'],"email_sent = 1","Update record");
					}
					//TODO Implement the real mail command! Also move the insert/update and fwrite into the mail succeeding. Make sure the correct message for the survey_bucket is being used
					if ($message != "") {
						$to = $emailList[$row['record']]['email'];
						//$successful = true;
						$successful = mail($to,$subject,$message."<br/>".$row['record'],$headers);
						if (!$successful) {
							echo "<p style='color:red;'>ERROR: Email was unable to be sent to email: ".$to.".</p>";
						}
					}
					else {
						echo "No email sent for ".$emailList[$row['record']]['email']."!";
					}
					fwrite($handle,$emailList[$row['record']]['name'].",".$emailList[$row['record']]['survey_bucket']."\r\n");
				}
			}
			if (count($statements) > 0) {
				$sql = $baseSql.implode(",",$statements);
				if (!db_query($sql)) throw new Exception("DATABASE_ERROR");
			}
			//TODO Make sure to have it start committing the inserts for real testing. Make sure the REDCap project is on production.
			@db_query("COMMIT");
			//@db_query("ROLLBACK");
		}
		else {
			foreach ($emailList as $email) {
				//TODO Implement the real mail command! Move the fwrite into the mail succeeding
				$to = $email['email'];
				//$successful = true;
				$successful = mail($to,$subject,$message."<br/>".$emailList[$email],$headers);
				if (!$successful) {
					echo "<p style='color:red;'>ERROR: Email was unable to be sent to email: ".$to.".</p>";
				}
				fwrite($handle,$email['name'].",".$email['survey_bucket']."\r\n");
			}
		}
		fclose($handle);
	}
	catch (Exception $e) {
		@db_query("ROLLBACK");

		if ($e->getMessage() == "DATABASE_ERROR") {
			echo "<p style='color:red;'>ERROR: There was an issue performing database operations.</p>";
		}
		else {
			echo "<p style='color:red;'>ERROR: ".$e->getMessage()."</p>";
		}
	}
}

# Obtain the list of people who are classified as ineligible based on their patient record
function getExclusionRecords($patientProjectId) {
	$exclusionRecords = array();
	$doNotSendRecords = array();
	$tempExclusions = array();
	# Find all records with a consent_timestamp
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='consent_timestamp'
			AND value!=''";
	$result = db_query($sql);
	while($row = db_fetch_assoc($result)) {
		if ($row['record'] != '')
			$tempExclusions[$row['record']]['consent_timestamp'] = $row['value'];
	}

	# Find all records that have been sent an email
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id = $patientProjectId
			AND field_name='email_sent_date'
			AND value !=''";
	$result = db_query($sql);
	while($row = db_fetch_assoc($result)) {
		if ($row['record'] != '')
			$tempExclusions[$row['record']]['email_sent_date'] = $row['value'];
	}

	# Find all records where the survey is complete
	$sql = "SELECT record,value
			FROM redcap_data
			WHERE project_id=$patientProjectId
			AND field_name='survey_complete_timestamp'
			AND value != ''";
	$result = db_query($sql);
	while($row = db_fetch_assoc($result)) {
		if ($row['record'] != '')
			$tempExclusions[$row['record']]['survey_complete_timestamp'] = $row['value'];
	}

	# Anyone who has completed the survey is ineligible
	# Anyone who has consented before being sent an email is ineligible
	# Anyone who is ineligible and has been sent an email before is doubley ineligible and shouldn't be sent any emails at all
	foreach ($tempExclusions as $record => $data) {
		if (isset($data['survey_complete_timestamp'])
				|| ($data['consent_timestamp'] != "" && (date("Y-m-d",strtotime($data['consent_timestamp'])) < date("Y-m-d",strtotime($data['email_sent_date']))))
				|| (isset($data['consent_timestamp']) && !isset($data['email_sent_date']))) {
			$exclusionRecords[$record] = $record;
			if(isset($data['email_sent_date'])) {
				$doNotSendRecords[$record] = $record;
			}
		}
	}
	return array($exclusionRecords,$doNotSendRecords);
}

include_once("includes/header.php");

?>

<div class="header">
	<h2>PCORI Import Email List</h2>
</div>

<div style="text-align: center;">
	<div id="error" class='errorText'></div>
	<img src='<?php echo APP_PATH_IMAGES."progress_circle.gif"; ?>' id='loading_gif' style='display:none' />
</div>

<div class="row">
	<div class="jumbotron">
		<form class="form-horizontal" id='emailForm' action="email_patients.php" method='POST' enctype="multipart/form-data">

			<div class="form-group">
				<div class="col-md-offset-1 col-md-10">
					<div class="checkbox">
						<!--The file input type does not take kindly to being formatted. Create a placeholder to be shown for it, hide the real file upload button-->
						<input type='button' class='btn btn-primary btn-default' value='Choose a File' onclick='getFile();'/><div style='margin-left:5px;display:inline-block;' id='chosenfile'>No file selected</div><br/>
						<input type='file' id='uploadedfile' name='uploadedfile' size='50' style='display:none;' onChange='getStats(this.value);'/><br />
						<input type='submit' class='btn btn-primary btn-default' value='Upload Value' />
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

	<div class="row">
		<div class="jumbotron" style="background-color:white;">
				<div class="form-group">
					<div class="col-md-offset-1 col-md-10">
						<?php
						if ($uploadedFileLocation != "") {
							echo "Download the report <a href='file_download.php?file=".$fileName."' style='font-weight:bold;'>here.</a><br/><br/>";
							/*echo "Started File: ".$startFile."<br/>";
							$endFile = date("Y-m-d H:i:s");
							echo "Ended File: ".$endFile."<br/>";*/
							echo "The following ".count($duplicates)." duplicates were found: <br/>";
							foreach ($duplicates as $duplicate) {
								echo $duplicate;
							}
							echo "<br/>";
							echo "Total Eligibles Found: ".count($eligibleEmails)."<br/>";
							echo "Total Ineligibles Found: ".(count($ineligibleEmails) + count($notstoredEmails))."<br/>";
						}
						?>
					</div>
				</div>
		</div>
	</div>

<?php

include_once("includes/footer.php");