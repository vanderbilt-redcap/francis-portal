<?php
    $sql = "SELECT *
            FROM redcap_validation_types
            WHERE validation_name IS NOT NULL
            AND validation_name != ''
            ORDER BY validation_label";
    //echo "$sql";
    $result = db_query($sql);
    $redcap_valtypes = array();
    while ($row = db_fetch_assoc($result))
    {
        $redcap_valtypes[$row['validation_name']] = array(
            'validation_label'=>$row['validation_label'],
            'regex_js'=>$row['regex_js'],
            'regex_php'=>$row['regex_php'],
            'data_type'=>$row['data_type'],
            'visible'=>$row['visible']
        );
    }
    echo "<div id='valregex_divs' style='display:none;'>";
        foreach ($redcap_valtypes as $valType=>$attr) {
            echo "<div id='valregex-$valType' datatype='".$attr['data_type']."'>".$attr['regex_js']."</div>";
        }
    echo "</div>";
?>

    <script src="js/jquery.min.js"></script>
	<script src="js/jquery.scrollex.min.js"></script>
	<script src="js/jquery.scrolly.min.js"></script>
	<script src="js/skel.min.js"></script>
	<script src="js/util.js"></script>
	<!--[if lte IE 8]><script src="js/ie/respond.min.js"></script><![endif]-->
	<script src="js/main.js"></script>
	<script src="js/jquery-ui.min.js"></script>
	<script type='text/javascript'>
        // i is an abbreviation for "invalid"
        var iZIPCode = "This field must be a 5 or 9 digit U.S. ZIP Code (like 94043). Please re-enter it now.";
        var iUSPhone = "This field must be a 10 digit U.S. phone number. Please use a format such as (999) 999-9999, 999-999-9999, 9999999999, or with an extension such as (999) 999-9999 x9999 or 999-999-9999x999. Please re-enter it now.";
        var iEmail = "This field must be a valid email address (like joe@user.com). Please re-enter it now.";
        var iStateCode = "This field must be a valid two character U.S. state abbreviation (like CA for California). Please re-enter it now.";
        var iWorldPhone = "This field must be a valid international phone number. Please re-enter it now.";
        var iSSN = "This field must be a 9 digit U.S. social security number (like 123 45 6789). Please re-enter it now.";
        var iCreditCardPrefix = "This is not a valid ";
        var iCreditCardSuffix = " credit card number. (Click the link on this form to see a list of sample numbers.) Please re-enter it now.";
        var iDay = "This field must be a day number between 1 and 31.  Please re-enter it now.";
        var iMonth = "This field must be a month number between 1 and 12.  Please re-enter it now.";
        var iYear = "This field must be a 2 or 4 digit year number.  Please re-enter it now.";
        var iDatePrefix = "The Day, Month, and Year for ";
        var iDateSuffix = " do not form a valid date.  Please re-enter them now.";

		$(document).ready(function() {
			generateGaugeCharts();
			setTimeout("hideBanner()", 5000);
            <?php
                /*if ($_POST['consent_check'] == YES) {
                    echo "var consentField = document.getElementById('yes_check');
                        toggleField(consentField,'survey_form_div');";
                }
                elseif ($_POST['consent_check'] == NO) {
                    echo "var consentField = document.getElementById('no_check');
                        toggleField(consentField,'survey_form_div');";
                }*/
                if ($_POST['return_check'] == YES) {
                    echo "var consentField = document.getElementById('yes_return');
                        toggleField('consent_check_div','consent_submit_div');";
                }
                elseif ($_POST['return_check'] === NO) {
                    echo "var consentField = document.getElementById('no_return');
                        toggleField('consent_submit_div','consent_check_div');";
                }
                /*if ($_POST['race'] != "") {
                    echo "var raceField = document.getElementById('race');
                        toggleOtherRace(raceField,'race_other');";
                }*/
            ?>
            var lang_select = document.getElementById("lang_select");
            languageSelect(lang_select.value);
		});

		$(window).resize(function() {
			generateGaugeCharts();
		});

		function generateGaugeCharts() {
			$('.gaugeGraph').each(function() {
				var canvasWidth = $(this).parent().css('width');
				canvasWidth = canvasWidth.substring(0,canvasWidth.length - 2);
				var startX = canvasWidth / 2;
				var startY = 105;
				var innerWidth = Math.min(canvasWidth / 4, 62.5);
				var outerWidth = Math.min(canvasWidth / 2.5, 100);
				var textPadding = innerWidth / 2;
				var ctx = $(this).children('canvas').get(0).getContext("2d");
				$(this).children('canvas').get(0).height = 120;
				$(this).children('canvas').get(0).width = canvasWidth;

				ctx.font="12px Arial";
				ctx.textAlign="center";
				var displayText = $(this).attr('label');
				var displayArray = displayText.split(" ");

				// Fill in name of the graph
				if(canvasWidth > 186) {
					for(var i = 0; i < displayArray.length; i++) {
						ctx.fillText(displayArray[i],canvasWidth / 2, 120-25 + i * 12);
					}
				}
				else {
					ctx.fillText(displayText,canvasWidth / 2, 20);
				}

				// Mark best and worst sides of graph
				ctx.fillText("Worst",startX - outerWidth / 2 - innerWidth / 2,120);
				ctx.fillText("Best",startX + outerWidth / 2 + innerWidth / 2,120);

				if($(this).attr('value') == '') { $(this).attr('value',0); }

				ctx.strokeStyle = "black";
				ctx.fillStyle = 'white';
				ctx.beginPath();
				ctx.arc(startX,startY,outerWidth,Math.PI, 2 * Math.PI, false);
				ctx.arc(startX,startY,innerWidth,0, Math.PI, true);
				ctx.arc(startX,startY,outerWidth,Math.PI, Math.PI, false);
				ctx.fill();
				ctx.stroke();
				if($(this).attr('value') >= 50) {
					ctx.fillStyle = '#55cc55';
				}
				else {
					ctx.fillStyle = '#ffd1dc';
				}
				ctx.beginPath();
				ctx.arc(startX,startY,outerWidth,Math.PI,Math.PI + (Math.PI / 100 * $(this).attr('value')), false);
				ctx.arc(startX,startY,innerWidth,Math.PI + (Math.PI / 100 * $(this).attr('value')), Math.PI, true);
				ctx.fill();
			});
		}

		function hideBanner() {
			$('#banner').hide('slow');
			$("html, body").animate({ scrollTop: 0 }, "slow");
		}

		function displayScores(surveyName, percentile) {
			var dialogBox = $('#scoreDisplay').dialog();
			var percentEnder = "th";
			if((percentile % 10) == 2 && (percentile % 100) != 12) {
				percentEnder = "nd";
			}
			else if((percentile % 10) == 1 && (percentile % 100) != 11) {
				percentEnder = "st";
			}

			dialogBox.html("You scored in the " + percentile + percentEnder + " percentile on the " + surveyName + " survey. " +
					"This means that if 100 people in the United States took the same survey, we would expect about " + percentile +
					" of them to receive a score lower than yours.");
		}

		function launchMoreInfo(infoLink) {
			var dialogBox = $('#scoreDisplay').dialog();

			dialogBox.html("This link will take you to an external site. Click \"Ok\" if you wish to proceed. Please " +
			"remember to return to finish the feedback survey if you haven't already.<br /><br />" +
			"<div style='text-align:center'><button onclick='window.open(\"" + infoLink + "\"); $(\"#scoreDisplay\").dialog(\"close\"); return false;'>Ok</button></div>");
//			if(confirm("This link will take you to an external site. Click \"Ok\" if you wish to proceed. Please " +
//					"remember to return to finish the feedback survey if you haven't already.")) {
//				window.open(infoLink);
//			}
		}

        /*function populateDays(control,dayField,selectedValue="") {
            var month = control.value;
            var i = 1;
            var maxDay = 31;
            if (month == "2") {
                maxDay = 29;
            }
            else if (month == "4" || month == "6" || month == "9" || month == "11") {
                maxDay = 30;
            }
            var dayDropDown = document.getElementById(dayField);
            while (i <= maxDay) {
                var option = document.createElement("option");
                option.text = i;
                option.value = i;
                if (selectedValue == i) {
                    option.selected = true;
                }
                dayDropDown.add(option);
                i++;
            }
        }*/

        function toggleOtherRace(control,target) {
            if (control.value == "99") {
                $("#"+target+"_div").css('display','inline-block');
            }
            else {
                $("#"+target+"_div").css('display','none');
                $("#"+target).val('');
            }
        }

        function toggleField(showTarget,hideTarget) {
            if ($("#"+showTarget).length > 0 && $("#"+hideTarget).length > 0) {
                    $("#" + showTarget).css('display', 'inline-block');
                    $("#" + hideTarget).css('display', 'none');
				/*$("#" + target).css('display', 'inline-block');
				$("#" + hideTarget).css('display', 'none');*/
            }
        }

        function togglePhoneEmail(control) {
            if (control.value == "<?php echo CONTACT_PHONE; ?>") {
                $("#phone_div").css('display','inline-block');
                $("#email_div").css('display','none');
            }
            else if (control.value == "<?php echo CONTACT_EMAIL; ?>") {
                $("#phone_div").css('display','none');
                $("#email_div").css('display','inline-block');
            }
            else if (control.value == "<?php echo CONTACT_EITHER; ?>") {
                $("#phone_div").css('display','inline-block');
                $("#email_div").css('display','inline-block');
            }
            else {
                $("#phone_div").css('display','none');
                $("#email_div").css('display','none');
            }
        }

        function languageSelect(selectElement) {
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

        // REDCap form validation function
        function redcap_validate(ob, min, max, returntype, texttype, regexVal, returnFocus, dateDelimiterReturned)
        {
            var return_value;
            var kickout_message;
            var holder1;
            var holder2;
            var holder3;

            // Reset flag on page
            $('#field_validation_error_state').val('0');

            // If blank, do nothing
            if (ob.value == '') {
                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';
                return true;
            }

            // Get ID of field: If field does not have an id, then given it a random one so later we can reference it directly.
            var obId = $(ob).attr('id');
            if (obId == null) {
                obId = "val-"+Math.floor(Math.random()*10000000000000000);
                $(ob).attr('id', obId);
            }

            // Set the Javascript for returning focus back on element (if specified)
            if (returnFocus == null) returnFocus = 1;
            var returnFocusJS = (returnFocus == 1) ? "$('#"+obId+"').focus();" : "";

            //REGULAR EXPRESSION
            if (regexVal != null)
            {
                // Before evaluating with regex, first do some cleaning
                ob.value = trim(ob.value);

                // Set id for regex validation dialog div
                var regexValPopupId = 'redcapValidationErrorPopup';

                // For date[time][_seconds] fields, replace any periods or slashes with a dash. Add any leading zeros.
                if (texttype=="date_ymd" || texttype=="date_mdy" || texttype=="date_dmy") {
                    ob.value = redcap_clean_date(ob.value,texttype);
                    if (ob.value.split('-').length == 2) {
                        // If somehow contains just one dash, then remove the dash and re-validate it to force reformatting
                        return $(ob).val(ob.value.replace(/-/g,'')).trigger('blur');
                    }
                    var thisdate = ob.value;
                    var thistime = '';
                } else if (texttype=="datetime_ymd" || texttype=="datetime_mdy" || texttype=="datetime_dmy"
                    || texttype=="datetime_seconds_ymd" || texttype=="datetime_seconds_mdy" || texttype=="datetime_seconds_dmy") {
                    var dt_array = ob.value.split(' ');
                    if (dt_array[1] == null) dt_array[1] = '';
                    var thisdate = redcap_clean_date(dt_array[0],texttype);
                    var thistime = redcap_pad_time(dt_array[1]);
                    ob.value = trim(thisdate+' '+thistime);
                    if (ob.value.split('-').length == 2) {
                        // If somehow contains just one dash, then remove the dash and re-validate it to force reformatting
                        return $(ob).val(ob.value.replace(/-/g,'')).trigger('blur');
                    }
                }

                // Obtain regex from hidden divs on page (where they are stored)
                var regexDataType = '';
                regexVal = 1;
                if (regexVal === 1) {
                    regexVal = $('#valregex_divs #valregex-'+texttype).html();
                    regexDataType = $('#valregex_divs #valregex-'+texttype).attr('datatype');
                }

                // Evaluate value with regex
                eval('var regexVal2 = new RegExp('+regexVal+');');

                if (regexVal2.test(ob.value))
                {
                    // Passed the regex test!
                    // Reformat phone format, if needed
                    if (texttype=="phone") {
                        ob.value = ob.value.replace(/-/g,"").replace(/ /g,"").replace(/\(/g,"").replace(/\)/g,"").replace(/\./g,"");
                        if (ob.value.length > 10) {
                            ob.value = trim(reformatUSPhone(ob.value.substr(0,10))+" "+trim(ob.value.substr(10)));
                        } else {
                            ob.value = reformatUSPhone(ob.value);
                        }
                    }
                    // Make sure time has a leading zero if hour is single digit
                    else if (texttype=="time" && ob.value.length == 4) {
                        ob.value = "0"+ob.value;
                    }
                    // If a date[time] field and the returnDelimiter is specified, then do a delimiter replace
                    else if (dateDelimiterReturned != null && dateDelimiterReturned != '-' && (texttype.substring(0,5) == 'date_' || texttype.substring(0,9) == 'datetime_')) {
                        ob.value = ob.value.replace(/-/g, dateDelimiterReturned);
                    }
                    // Now do range check (if needed) for various validation types
                    if (min != '' || max != '')
                    {
                        holder1 = ob.value;
                        holder2 = min;
                        holder3 = max;

                        // Range check - integer/number
                        if (texttype=="integer" || texttype=="number" || regexDataType=="integer" || regexDataType=="number" || regexDataType=="number_comma_decimal")
                        {
                            holder1 = (holder1.replace(',','.'))*1;
                            holder2 = (holder2==='') ? '' : (holder2.replace(',','.'))*1;
                            holder3 = (holder3==='') ? '' : (holder3.replace(',','.'))*1;
                            alert(holder1+", "+holder2+", "+holder3);
                        }
                        // Range check - time
                        else if (texttype=="time")
                        {
                            // Remove all non-numerals so we can compare them numerically
                            holder1 = (holder1.replace(/:/g,""))*1;
                            holder2 = (holder2==='') ? '' : (holder2.replace(/:/g,""))*1;
                            holder3 = (holder3==='') ? '' : (holder3.replace(/:/g,""))*1;
                        }
                        // Range check - date[time][_seconds]
                        else if (texttype=="date_ymd" || texttype=="date_mdy" || texttype=="date_dmy"
                            || texttype=="datetime_ymd" || texttype=="datetime_mdy" || texttype=="datetime_dmy"
                            || texttype=="datetime_seconds_ymd" || texttype=="datetime_seconds_mdy" || texttype=="datetime_seconds_dmy")
                        {
                            // Convert date format of value to YMD to compare with min/max, which are already in YMD format
                            if (/_mdy/.test(texttype)) {
                                holder1 = trim(date_mdy2ymd(thisdate)+' '+thistime);
                                min = date_ymd2mdy(min);
                                max = date_ymd2mdy(max);
                            } else if (/_dmy/.test(texttype)) {
                                holder1 = trim(date_dmy2ymd(thisdate)+' '+thistime);
                                min = date_ymd2dmy(min);
                                max = date_ymd2dmy(max);
                            } else {
                                holder1 = trim(thisdate+' '+thistime);
                            }
                            // Ensure that min/max are in YMD format (legacy values could've been in M/D/Y format)
                            if (texttype.substr(0,5) == "date_") {
                                holder2 = redcap_clean_date(holder2,"date_ymd");
                                holder3 = redcap_clean_date(holder3,"date_ymd");
                            }
                            // Remove all non-numerals so we can compare them numerically
                            holder1 = (holder1.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                            holder2 = (holder2==='') ? '' : (holder2.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                            holder3 = (holder3==='') ? '' : (holder3.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                        }
                        // Check range
                        if ((holder2 !== '' && holder1 < holder2) || (holder3 !== '' && holder1 > holder3)) {
                            var msg1 = ($('#valtext_divs #valtext_rangesoft1').length) ? $('#valtext_divs #valtext_rangesoft1').text() : 'The value you provided is outside the suggested range.';
                            var msg2 = ($('#valtext_divs #valtext_rangesoft2').length) ? $('#valtext_divs #valtext_rangesoft2').text() : 'This value is admissible, but you may wish to verify.';
                            ob.style.backgroundColor='#FFB7BE';
                            var msg = msg1 + ' (' + (min==''?'no limit':min) + ' - ' + (max==''?'no limit':max) +'). ' + msg2;
                            $('#'+regexValPopupId).remove();
                            createDialog(regexValPopupId);
                            $('#'+regexValPopupId).html(msg);
                            setTimeout(function(){
                                francisDialog(msg, null, regexValPopupId);
                            },10);
                            return true;
                        }
                    }
                    // Not out of range, so leave the field as normal
                    ob.style.fontWeight = 'normal';
                    ob.style.backgroundColor='#FFFFFF';
                    return true;
                }
                // Set default generic message for failure
                var msg = ($('#valtext_divs #valtext_regex').length) ? $('#valtext_divs #valtext_regex').text() : 'The value you provided could not be validated because it does not follow the expected format. Please try again.';
                // Custom messages for legacy validation types
                if (texttype=="zipcode") {
                    msg = ($('#valtext_divs #valtext_zipcode').length) ? $('#valtext_divs #valtext_zipcode').text() : iZIPCode;
                } else if (texttype=="email") {
                    msg = ($('#valtext_divs #valtext_email').length) ? $('#valtext_divs #valtext_email').text() : iEmail;
                } else if (texttype=="phone") {
                    msg = ($('#valtext_divs #valtext_phone').length) ? $('#valtext_divs #valtext_phone').text() : iUSPhone;
                } else if (texttype=="integer") {
                    msg = ($('#valtext_divs #valtext_integer').length) ? $('#valtext_divs #valtext_integer').text() : 'This value you provided is not an integer. Please try again.';
                } else if (texttype=="number") {
                    msg = ($('#valtext_divs #valtext_number').length) ? $('#valtext_divs #valtext_number').text() : 'This value you provided is not a number. Please try again.';
                } else if (texttype=="vmrn") {
                    msg = ($('#valtext_divs #valtext_vmrn').length) ? $('#valtext_divs #valtext_vmrn').text() : 'The value entered is not a valid Vanderbilt Medical Record Number (i.e. 4- to 9-digit number, excluding leading zeros). Please try again.';
                } else if (texttype=="time") {
                    msg = ($('#valtext_divs #valtext_time').length) ? $('#valtext_divs #valtext_time').text() : 'The value entered must be a time value in the following format HH:MM within the range 00:00-23:59 (e.g., 04:32 or 23:19).';
                }
                // Because of strange syncronicity issues of back-to-back fields with validation, set pop-up content first here
                $('#'+regexValPopupId).remove();
                createDialog(regexValPopupId);
                $('#'+regexValPopupId).html(msg);
                // Give alert message of failure
                setTimeout(function(){
                    francisDialog(msg, null, regexValPopupId, null, returnFocusJS);
                    $('#'+regexValPopupId).parent().find('button:first').focus();
                },10);
                ob.style.fontWeight = 'bold';
                ob.style.backgroundColor = '#FFB7BE';
                // Set flag on page
                $('#field_validation_error_state').val('1');
                return false;
            }

            //ZIPCODE
            if(texttype=="zipcode")
            {
                if ($('#valtext_divs #valtext_zipcode').length) iZIPCode = $('#valtext_divs #valtext_zipcode').text();
                if (checkZIPCode(ob,true)) {
                    ob.style.fontWeight = 'normal';
                    ob.style.backgroundColor='#FFFFFF';
                    return true;
                }
                return false;
            }

            //EMAIL
            if (texttype=="email")
            {
                if ($('#valtext_divs #valtext_email').length) iEmail = $('#valtext_divs #valtext_email').text();
                if (checkEmail(ob,true)) {
                    ob.style.fontWeight = 'normal';
                    ob.style.backgroundColor='#FFFFFF';
                    return true;
                }
                return false;
            }

            //Phone
            if (texttype=="phone")
            {
                if ($('#valtext_divs #valtext_phone').length) iUSPhone = $('#valtext_divs #valtext_phone').text();
                if (checkUSPhone(ob,true)) {
                    ob.style.fontWeight = 'normal';
                    ob.style.backgroundColor='#FFFFFF';
                    return true;
                }
                return false;
            }

            //Time (HH:MM)
            if (texttype=="time")
            {
                if (ob.value != "") {
                    if (!isTime(ob.value,0)) {
                        var msg = ($('#valtext_divs #valtext_time').length) ? $('#valtext_divs #valtext_time').text() : 'The value entered must be a time value in the following format HH:MM within the range 00:00-23:59 (e.g., 04:32 or 23:19).';
                        francisDialog(msg, null, null, null, returnFocusJS);
                        ob.style.fontWeight = 'bold';
                        ob.style.backgroundColor = '#FFB7BE';
                        return false;
                    }
                    //Now handle limits
                    holder1 = (ob.value.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                    holder2 = (min=='') ? '' : (min.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                    holder3 = (max=='') ? '' : (max.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                    if ((holder2 != '' && holder1 < holder2) || (holder3 != '' && holder1 > holder3)) {
                        if(returntype=="hard") {
                            var msg = ($('#valtext_divs #valtext_rangehard').length) ? $('#valtext_divs #valtext_rangehard').text() : 'The value you provided must be within the suggested range';
                            francisDialog(msg + ' (' + min + ' - ' + max +').', null, null, null, returnFocusJS);
                            ob.style.backgroundColor='#FFB7BE';
                        }
                        else
                        {
                            var msg1 = ($('#valtext_divs #valtext_rangesoft1').length) ? $('#valtext_divs #valtext_rangesoft1').text() : 'The value you provided is outside the suggested range.';
                            var msg2 = ($('#valtext_divs #valtext_rangesoft2').length) ? $('#valtext_divs #valtext_rangesoft2').text() : 'This value is admissible, but you may wish to verify.';
                            francisDialog(msg1 + ' (' + min + ' - ' + max +'). ' + msg2, null, null, null, returnFocusJS);
                            ob.style.backgroundColor='#FFB7BE';
                        }
                    }
                }
                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';
                return true;
            }


            //Datetime (YYYY-MM-DD HH:MM) and Datetime w/ seconds (YYYY-MM-DD HH:MM:SS)
            if (texttype=="datetime" || texttype=="datetime_seconds")
            {
                if (ob.value != "") {
                    var dt_array = ob.value.split(' ');
                    var dt_date = dt_array[0];
                    var dt_time = dt_array[1];
                    var holder1 = parseDate(dt_date);
                    var hasSeconds = (texttype=="datetime_seconds");
                    if (!isTime(dt_time,hasSeconds) || holder1==null) {
                        if (!hasSeconds) {
                            var msg = ($('#valtext_divs #valtext_datetime').length) ? $('#valtext_divs #valtext_datetime').text() : 'The value entered must be a datetime value in the following format YYYY-MM-DD HH:MM with the time in the range 00:00-23:59.';
                        } else {
                            var msg = ($('#valtext_divs #valtext_datetime_seconds').length) ? $('#valtext_divs #valtext_datetime_seconds').text() : 'The value entered must be a datetime value in the following format YYYY-MM-DD HH:MM:SS with the time in the range 00:00:00-23:59:59.';
                        }
                        francisDialog(msg, null, null, null, returnFocusJS);
                        ob.style.fontWeight = 'bold';
                        ob.style.backgroundColor = '#FFB7BE';
                        return false;
                    }
                    ob.value=formatDate(holder1,'y-MM-dd')+' '+dt_time;
                    //Now handle limits
                    holder1 = (ob.value.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                    holder2 = (min=='') ? '' : (min.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                    holder3 = (max=='') ? '' : (max.replace(/:/g,"").replace(/ /g,"").replace(/-/g,""))*1;
                    if ((holder2 != '' && holder1 < holder2) || (holder3 != '' && holder1 > holder3)) {
                        if(returntype=="hard") {
                            var msg = ($('#valtext_divs #valtext_rangehard').length) ? $('#valtext_divs #valtext_rangehard').text() : 'The value you provided must be within the suggested range';
                            francisDialog(msg + ' (' + min + ' - ' + max +').', null, null, null, returnFocusJS);
                            ob.style.backgroundColor='#FFB7BE';
                        }
                        else
                        {
                            var msg1 = ($('#valtext_divs #valtext_rangesoft1').length) ? $('#valtext_divs #valtext_rangesoft1').text() : 'The value you provided is outside the suggested range.';
                            var msg2 = ($('#valtext_divs #valtext_rangesoft2').length) ? $('#valtext_divs #valtext_rangesoft2').text() : 'This value is admissible, but you may wish to verify.';
                            francisDialog(msg1 + ' (' + min + ' - ' + max +'). ' + msg2, null, null, null, returnFocusJS);
                            ob.style.backgroundColor='#FFB7BE';
                        }
                    }
                }
                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';
                return true;
            }


            //Dates
            if(texttype=="date")
            {
                //if empty, let it go
                if(isEmpty(ob.value)){return true;}
                var result;
                var holder1 = parseDate(ob.value);
                if(holder1==null){
                    var msg = ($('#valtext_divs #valtext_date').length) ? $('#valtext_divs #valtext_date').text() : 'The value entered in this field must be a date. You may use one of several formats (ex. YYYY-MM-DD or MM/DD/YYYY), but the final result must constitute a real date. Please try again.';
                    francisDialog(msg, null, null, null, returnFocusJS);
                    ob.style.fontWeight = 'bold';
                    ob.style.backgroundColor='#FFB7BE';
                    return false;
                }
                holder1=formatDate(holder1,'y-MM-dd');
                ob.value=holder1;
                //Reset field style
                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';
                //Now handle limits
                holder2 = (!min=='') ? formatDate(parseDate(min),'y-MM-dd') : formatDate(parseDate(ob.value),'y-MM-dd');
                holder3 = (!max=='') ? formatDate(parseDate(max),'y-MM-dd') : formatDate(parseDate(ob.value),'y-MM-dd');
                if(compareDates(holder2,'y-MM-dd',holder1,'y-MM-dd')==1 || compareDates(holder1,'y-MM-dd',holder3,'y-MM-dd')==1){
                    if(returntype=="hard") {
                        var msg = ($('#valtext_divs #valtext_rangehard').length) ? $('#valtext_divs #valtext_rangehard').text() : 'The value you provided must be within the suggested range';
                        francisDialog(msg + ' (' + holder2 + ' - ' + holder3 +').', null, null, null, returnFocusJS);
                        ob.style.backgroundColor='#FFB7BE';
                    }
                    else
                    {
                        var msg1 = ($('#valtext_divs #valtext_rangesoft1').length) ? $('#valtext_divs #valtext_rangesoft1').text() : 'The value you provided is outside the suggested range.';
                        var msg2 = ($('#valtext_divs #valtext_rangesoft2').length) ? $('#valtext_divs #valtext_rangesoft2').text() : 'This value is admissible, but you may wish to verify.';
                        francisDialog(msg1 + ' (' + holder2 + ' - ' + holder3 +'). ' + msg2, null, null, null, returnFocusJS);
                        ob.style.backgroundColor='#FFB7BE';
                    }
                    return true;
                }
                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';
                return true;
            }

            //Vanderbilt MRN
            if (texttype=="vmrn")
            {
                reformat_vanderbilt_mrn(ob); // Remove all non-numerals
                if (!is_vanderbilt_mrn(ob.value)) {
                    var msg = ($('#valtext_divs #valtext_vmrn').length) ? $('#valtext_divs #valtext_vmrn').text() : 'The value entered is not a valid Vanderbilt Medical Record Number (i.e. 4- to 9-digit number, excluding leading zeros). Please try again.';
                    francisDialog(msg, null, null, null, returnFocusJS);
                    ob.style.fontWeight = 'bold';
                    ob.style.backgroundColor = '#FFB7BE';
                    return false;
                } else {
                    ob.style.fontWeight = 'normal';
                    ob.style.backgroundColor='#FFFFFF';
                    return true;
                }
            }

            //Numbers
            if (texttype=="int" ||texttype=="float")
            {
                //if empty, let it go
                if(isEmpty(ob.value)){return true;}
                var range_text;

                if(!min == '' && !max == ''){
                    range_text = 'Range = ' + min + ' to ' + max;
                } else {
                    if(!min==''){
                        range_text = 'Minimum = ' + min;
                    } else {
                        range_text = max + ' = Maximum';
                    }
                }

                //First, make sure the type is correct
                if(texttype=="int")
                {
                    return_value=isSignedInteger(ob.value,true);
                    if(!return_value)
                    {
                        var msg = ($('#valtext_divs #valtext_integer').length) ? $('#valtext_divs #valtext_integer').text() : 'This value you provided is not an integer. Please try again.';
                        francisDialog(msg, null, null, null, returnFocusJS);
                        ob.style.fontWeight = 'bold';
                        ob.style.backgroundColor='#FFB7BE';
                        return false;
                    }
                } else if(texttype=="float") {
                    return_value=isSignedFloat(ob.value,true);
                    if(!return_value)
                    {
                        var msg = ($('#valtext_divs #valtext_number').length) ? $('#valtext_divs #valtext_number').text() : 'This value you provided is not a number. Please try again.';
                        francisDialog(msg, null, null, null, returnFocusJS);
                        ob.style.fontWeight = 'bold';
                        ob.style.backgroundColor='#FFB7BE';
                        return false;
                    }
                }

                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';

                //Handle case where min AND max not provided.
                if(min=='' && max==''){ return true; }
                //Handle case where min and/or max provided.
                if(!min==''){holder1 = min-0;} else {holder1=ob.value;}
                if(!max==''){holder2 = max-0;} else {holder2=ob.value;}
                if(ob.value > holder2 || ob.value < holder1){
                    ob.style.fontWeight = 'bold';
                    ob.style.backgroundColor='#FFB7BE';
                    if(returntype=="hard") {
                        var msg = ($('#valtext_divs #valtext_rangehard').length) ? $('#valtext_divs #valtext_rangehard').text() : 'The value you provided must be within the suggested range.';
                        francisDialog(msg + ' (' + range_text + ')', null, null, null, returnFocusJS);
                    } else {
                        var msg1 = ($('#valtext_divs #valtext_rangesoft1').length) ? $('#valtext_divs #valtext_rangesoft1').text() : 'The value you provided is outside the suggested range.';
                        var msg2 = ($('#valtext_divs #valtext_rangesoft2').length) ? $('#valtext_divs #valtext_rangesoft2').text() : 'This value is admissible, but you may wish to verify.';
                        francisDialog(msg1 + ' (' + range_text +') ' + msg2, null, null, null, returnFocusJS);
                    }
                    return false;
                }
                ob.style.fontWeight = 'normal';
                ob.style.backgroundColor='#FFFFFF';
                return true;
            }
        }

        function trim(s) {
            // str - any string
            // returns the same string with stripped leading and trailing blanks
            var str = new String(s);
            return (str == '') ? '' : str.replace(/^\s*|\s*$/g,"");
        }

        // Creates hidden div needed for jQuery UI dialog box. If div exists and is a dialog already, removes as existing dialog.
        function createDialog(div_id,inner_html) {
            if ($('#'+div_id).length) {
                if ($('#'+div_id).hasClass('ui-dialog-content')) $('#'+div_id).dialog('destroy');
                $('#'+div_id).addClass('francisDialog');
            } else {
                $('body').append('<div id="'+div_id+'" class="francisDialog"></div>');
            }
            $('#'+div_id).html((inner_html == null ? '' : inner_html));
        }

        // Display jQuery UI dialog with Close button (provide id, title, content, width, onClose JavaScript event as string)
        function francisDialog(content,title,id,width,onCloseJs,closeBtnTxt,okBtnJs,okBtnTxt) {
            // If no id is provided, create invisible div on the fly to use as dialog container
            var idDefined = true;
            if (id == null || trim(id) == '') {
                id = "popup"+Math.floor(Math.random()*10000000000000000);
                idDefined = false;
            }
            // If this DOM element doesn't exist yet, then add it and set title/content
            if ($('#'+id).length < 1) {
                var existInDom = false;
                $('body').append('<div class="francisDialog" id="'+id+'"></div>');
            } else {
                if (title == null || title == '') title = $('#'+id).attr('title');
                var existInDom = true;
                if (!$('#'+id).hasClass('francisDialog')) $('#'+id).addClass('francisDialog');
            }
            // Set content
            if (content != null && content != '') $('#'+id).html(content);
            // default title
            if (title == null) title = 'Alert';
            // Set parameters
            if (!$.isNumeric(width)) width = 500; // default width
            // Set default button text
            if (okBtnTxt == null) {
                // Default "okay" text for secondary button
                okBtnTxt = 'Okay';
                // Default "cancel" text for first button when have 2 buttons
                if (okBtnJs != null && closeBtnTxt == null) closeBtnTxt = 'Cancel';
            }
            if (closeBtnTxt == null) {
                // Default "close" text for single button
                closeBtnTxt = 'Close';
            }
            // Set up button(s)
            if (okBtnJs == null) {
                // Only show a Close button
                var btnClass = '';
                if(onCloseJs === 'delete_iframe'){
                    btnClass = 'hidden';
                }
                var btns =	[{ text: closeBtnTxt, 'class': btnClass, click: function() {
                    // Destroy dialog and remove div from DOM if was created on the fly
                    $(this).dialog('close').dialog('destroy');
                    if (!idDefined) $('#'+id).remove();
                } }];
            } else {
                // Show two buttons
                var btns =	[{ text: closeBtnTxt, click: function() {
                    // Destroy dialog and remove div from DOM if was created on the fly
                    $(this).dialog('close').dialog('destroy');
                    if (!idDefined) $('#'+id).remove();
                }},
                    {text: okBtnTxt, click: function() {
                        // If okBtnJs was provided, then eval it to execute
                        if (okBtnJs != null) {
                            if (typeof(okBtnJs) == 'string') {
                                eval(okBtnJs);
                            } else {
                                var okBtnJsFunc = okBtnJs;
                                eval("okBtnJsFunc()");
                            }
                        }
                        // Destroy dialog and remove div from DOM if was created on the fly
                        $(this).dialog('destroy');
                        if (!idDefined) $('#'+id).remove();
                    }}];
            }
            // Show dialog
            $('#'+id).dialog({ bgiframe: true, modal: true, width: width, title: title, buttons: btns });
            // If Javascript is provided for onClose event, then set it here
            if (onCloseJs != null) {
                if(onCloseJs == 'delete_iframe'){
                    var dialogcloseFunc = "function(){window.location.reload()}";
                }else{
                    var dialogcloseFunc = (typeof(onCloseJs) == 'string') ? "function(){"+onCloseJs+"}" : onCloseJs;
                }
                eval("$('#"+id+"').bind('dialogclose',"+dialogcloseFunc+");");
            }
            // If div already existed in DOM beforehand (i.e. wasn't created here on the fly), then re-add title to div because it gets lost when converted to dialog
            if (existInDom)	$('#'+id).attr('title', title);
        }

        // takes USPhone, a string of 10 digits
        // and reformats as (123) 456-789

        function reformatUSPhone (USPhone) {
            return (reformat (USPhone, "(", 3, ") ", 3, "-", 4))
        }

        function reformat (s) {
        	var arg;
            var sPos = 0;
            var resultString = "";

            for (var i = 1; i < reformat.arguments.length; i++) {
                arg = reformat.arguments[i];
                if (i % 2 == 1) resultString += arg;
                else {
                    resultString += s.substring(sPos, sPos + arg);
                    sPos += arg;
                }
            }
            return resultString;
        }

        function generateSurveyLink(unique_code, event_id, form_name) {
			var lang_select = document.getElementById("lang_select");
			var data = {"uniqueCode" : unique_code,
				"eventID" : event_id,
                "formName" : form_name,
                "lang" : lang_select.value
			};
			$.ajax({
				url: "ajax.php",
				type: "POST",
				data: data
			}).done(function(html) {
				var error = false;
				if(html == "0") {
					$("#ajaxresult").html("The provided unique code could not be mapped to a survey.");
					error = true;
				}
				else if(html.indexOf("Fatal error") !== -1) {
					$("#ajaxresult").html("There was an error handling your request.");
					error = true;
				}
				else {
					$("#consent_frame").attr('src',html);
				}
			});
			return false;
        }

	</script>
</body>
</html>