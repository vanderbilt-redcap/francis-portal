<?php
/*** Created Kyle McGuffin
 Expanded upon for David Francis Portal by Ryan Moore*/
session_name("francis_portal");
session_start();

require_once("functions.php");

# Define the environment: options include "DEV", "TEST" or "PROD"
if (is_file('/app001/victrcore/lib/Victr/Env.php'))
	include_once('/app001/victrcore/lib/Victr/Env.php');

if(class_exists("Victr_Env")) {
	$envConf = Victr_Env::getEnvConf();

	if ($envConf[Victr_Env::ENV_CURRENT] === Victr_Env::ENV_PROD) {
		define("ENVIRONMENT", "PROD");
	}
	elseif ($envConf[Victr_Env::ENV_CURRENT] === Victr_Env::ENV_DEV) {
		define("ENVIRONMENT", "TEST");
	}
}
else {
	define("ENVIRONMENT", "DEV");
}

# Define REDCap path
if (ENVIRONMENT == "DEV") {
	define("CONNECT_FILE_PATH", dirname(dirname(dirname(__FILE__))));
}
else {
	define("CONNECT_FILE_PATH", dirname(dirname(dirname(__FILE__))));
}

if(ENVIRONMENT == "DEV" || ENVIRONMENT == "TEST") {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

define("CONSENT_FORM","consent_form");

## Definitions for field names for dashboard project
define("AGE","dob");
define("GENDER","gender");
define("RACE","race");
define("OTHER_RACE","other_race");
define("EDUCATION","education");
//define("PHONE","participant_phone");
define("EMAIL","participant_email");
define("UNIQUE_CODE","unique_code");
define("CONSENT_DATE","date_started");
define("SURVEY_START_DATE","survey_start");
define("SURVEYS_COMPLETE","surveys_complete");
//define("SURVEY_TYPE","survey_type");
//define("SURVEY_METHOD","survey_method");
//define("CONTACT_METHOD","contact_method");
define("CONSENTED","consented");
define("LANG_PREF","lang_pref");
define("PARTICIPANT_CONTACTED","participant_contacted");
define("CONFIDENT_Q","confidentiality_question");
define("PAPER_SURVEY","paper_survey");
define("ONEWEEK_RANDOMIZED","oneweek_randomized");

define("PREFILL_FORM","portal_prefill_fields");

## Methods for participants to receive surveys
/*define("PAPER",2);
define("ELECTRONIC",1);
define("PREFER_ELECTRONIC",3);
define("PREFER_PAPER",4);

define("CONTACT_PHONE",1);
define("CONTACT_EMAIL",2);
define("CONTACT_EITHER",3);*/

define("EMAIL_PERS_INIT","email_pers_init");
define("EMAIL_RT_WEEKLY","email_rt_weekly");
define("EMAIL_RT_PAPER","email_rt_paper");

define("YES",1);
define("NO",0);

define("COHORT_PAYMENT_LIMIT", 1000);
define("PORTAL_WELCOME_HEADER","portal_welcome_header");
define("PORTAL_WELCOME_BODY","portal_welcome_body");
define("PORTAL_WELCOME_SUBMIT","portal_welcome_submit");
define("CONSENT_PORTAL_UNIQUE_CODE","consent_portal_uc");
define("CONSENT_PORTAL_NEW","consent_portal_new");
define("CONSENT_PORTAL_SUBMIT","consent_portal_submit");
define("DASHBOARD_PORTAL_HEADER","dashboard_portal_header");
define("DASHBOARD_PORTAL_BODY","dashboard_portal_body");
define("DASHBOARD_PORTAL_NHEAD","dashboard_portal_nhead");
define("DASHBOARD_PORTAL_AHEAD","dashboard_portal_ahead");
define("DASHBOARD_PORTAL_THEAD","dashboard_portal_thead");
define("DASHBOARD_PORTAL_ACOMP","dashboard_portal_acomp");
define("DASHBOARD_PORTAL_ASURV","dashboard_portal_asurv");

define("EMAIL_SENDER","email_sender");
define("PARTICIPANT_ONLINE_SUBJECT","participant_online_subject");
define("PARTICIPANT_ONLINE_EMAIL","participant_online_email");
define("RT_WEEKLY_SUBJECT","rt_weekly_subject");
define("RT_WEEKLY_EMAIL","rt_weekly_email");
define("RT_PAPER_SUBJECT","rt_paper_subject");
define("RT_PAPER_EMAIL","rt_paper_email");

define("APP_TITLE", "David Francis Test Portal");

require_once(CONNECT_FILE_PATH."/redcap_connect.php");
require_once(CONNECT_FILE_PATH."/plugins/Core/bootstrap.php");
if (!function_exists('PHPMailerAutoload')) {
	require 'phpmailer/PHPMailerAutoload.php';
}

global $Core;
$Core->Libraries(array("Project","Record","ProjectSet","RecordSet","Passthru", "Metadata"));
$monthArray = array("1"=>"Jan",
                    "2"=>"Feb",
                    "3"=>"Mar",
                    "4"=>"Apr",
                    "5"=>"May",
                    "6"=>"Jun",
                    "7"=>"Jul",
                    "8"=>"Aug",
                    "9"=>"Sep",
                    "10"=>"Oct",
                    "11"=>"Nov",
                    "12"=>"Dec");
$yearArray = array("start"=>"1900","end"=>"2016");

//TODO Provide list of all people who need emails about participants consenting
$personnelEmails = array("james.r.moore@vanderbilt.edu"=>array("to_name"=>"Ryan Moore","to"=>"james.r.moore@vanderbilt.edu"));

$surveyProject = new \Plugin\Project("francis_portal");
$surveyEvents = getEventsAsProjects($surveyProject->getProjectId());

?>