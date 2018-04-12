<?php

define("NAME_COLUMN", 0);
define("TSCORE_COLUMN", 4);
define("TOTAL_COLUMN", 6);
define("FEMALE_COLUMN", 7);
define("MALE_COLUMN", 8);
define("UNDER35_COLUMN", 9);
define("AGE3545_COLUMN", 10);
define("AGE4555_COLUMN", 11);
define("AGE5565_COLUMN", 12);
define("AGE6575_COLUMN", 13);
define("OVER75_COLUMN", 14);
define("PERCENTILE_FILE_NAME", "tscore_percentile.csv");

/**
 * @param $tscore int
 * @param $instrumentName string
 *
 * @return array|null
 */
function lookupTscore($tscore, $instrumentName, $invert = false) {
	$fileHandle = fopen(PERCENTILE_FILE_NAME, "r");

	if($tscore == "") {
		return NULL;
	}

	## Round tscore to nearest integer
	$tscore = round($tscore);

	while($row = fgetcsv($fileHandle)) {
		$convertedName = strtolower(preg_replace("[\\.]","",preg_replace("/[ \\-\\_]+/","_",$row[NAME_COLUMN])));

		if($convertedName == $instrumentName) {

			if($tscore == $row[TSCORE_COLUMN]) {
				return array(
					"total" => ($invert ? 100 - $row[TOTAL_COLUMN] : $row[TOTAL_COLUMN]),
					"male" => ($invert ? 100 - $row[MALE_COLUMN] : $row[MALE_COLUMN]),
					"female" => ($invert ? 100 - $row[FEMALE_COLUMN] : $row[FEMALE_COLUMN]),
					"under35" => ($invert ? 100 - $row[FEMALE_COLUMN] : $row[FEMALE_COLUMN]),
					"3545" => ($invert ? 100 - $row[AGE3545_COLUMN] : $row[AGE3545_COLUMN]),
					"4555" => ($invert ? 100 - $row[AGE4555_COLUMN] : $row[AGE4555_COLUMN]),
					"5565" => ($invert ? 100 - $row[AGE5565_COLUMN] : $row[AGE5565_COLUMN]),
					"6575" => ($invert ? 100 - $row[AGE6575_COLUMN] : $row[AGE6575_COLUMN]),
					"over75" => ($invert ? 100 - $row[OVER75_COLUMN] : $row[OVER75_COLUMN])
				);
			}
		}
	}

	return NULL;
}