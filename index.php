<?php
/**
 * Created by PhpStorm.
 * User: moorejr5
 * Date: 8/25/2015
 * Time: 10:23 AM
 */
define("NOAUTH", true);
include_once("base.php");

require_once("../Core/bootstrap.php");
if(ENVIRONMENT == "DEV") {
    error_reporting(1);
    ini_set('display_errors', true);
}

/** @var $Core \Plugin_Core */
$Core->Libraries(array("ProjectSet","RecordSet"),false);
//$Core->Helpers(array("getRandomIdentifier","lookupTscore"));

include_once("includes/header.php");

    echo "<div class='header'>
		<span lang='en'><h2>".$surveyProject->getMetadata(PORTAL_WELCOME_HEADER."_en")->getElementLabel()."</h2></span>
        <span lang='es'><h2>".$surveyProject->getMetadata(PORTAL_WELCOME_HEADER."_es")->getElementLabel()."</h2></span>
    </div>";
    echo "<div class='col-md-offset-3 col-md-6 text_description'>";
    echo "<span lang='en'>".$surveyProject->getMetadata(PORTAL_WELCOME_BODY."_en")->getElementLabel()."</span>
        <span lang='es'>".$surveyProject->getMetadata(PORTAL_WELCOME_BODY."_es")->getElementLabel()."</span>";
    echo "</div>";

    echo "<form name='index_form' action='consent.php' method='post'>
        <div class='submit_div col-md-12'>";
            echo "<div class='col-xs-offset-5 col-xs-2'>";
                echo "<span lang='en'><input type='submit' class='btn btn-primary' value='".$surveyProject->getMetadata(PORTAL_WELCOME_SUBMIT."_en")->getElementLabel()."'></button></span>";
				echo "<span lang='es'><input type='submit' class='btn btn-primary' value='".$surveyProject->getMetadata(PORTAL_WELCOME_SUBMIT."_es")->getElementLabel()."'></button></span>";
            echo "</div>";
        echo "</div>
        <input type='hidden' id='lang_hidden' name='lang_hidden'/>
    </form>";

//echo "</div>";
/*echo "</body>
</html>";*/

include_once("includes/footer.php");
