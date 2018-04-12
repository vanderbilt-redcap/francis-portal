<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?=APP_TITLE?></title>
<!---->
<!--	<script type="text/javascript" src="--><?php //echo APP_PATH_JS ?><!--base.js"></script>-->
<!--	<script type='text/javascript'>-->
<!--		var app_path_webroot = '--><?//=APP_PATH_WEBROOT?><!--//';-->
<!--		var app_path_webroot_full = '--><?//=APP_PATH_WEBROOT?><!--//';-->
<!--		var app_path_images = '--><?//=APP_PATH_IMAGES?><!--//';-->
<!--	</script>-->
    <?php
        $HtmlPage = new HtmlPage();
        $HtmlPage->PrintHeaderExt();
        echo "</div></div></div>";
    ?>
    <script>
        $('#pagecontainer').css('display','none');
    </script>
	<link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="col-md-offset-10 col-md-2">
        <label for="lang_select">Language:</label>
        <select id="lang_select" name="lang_select" onchange="languageSelect(this.value);">
            <option value="en" <?php echo ($_POST['lang_hidden'] == "en" || $_POST['lang_hidden'] == "" || ($_SESSION['lang_pref'] == "en" && $_POST['lang_hidden'] == "") ? "selected" : ""); ?>>English</option>
            <option value="es" <?php echo ($_POST['lang_hidden'] == "es" || ($_SESSION['lang_pref'] == "es" && $_POST['lang_hidden'] == "") ? "selected" : ""); ?>>Español</option>
        </select>
    </div>
    <br/>
	<div id="page-wrapper" style="height:100%;">