<?php

function wp_days_ago_v3 ($stopUsingAjaxAfter = 0, $showDateAfter = -1, $showDateFormat = null, $showYesterday = true, $context = 1) {
	
	if ($context <= 1 || $context > 3) {
		$id = get_the_ID();
		$the_time = get_post_time("U", true, $id);
		$ajax_wait_time = get_post_time("H:i", false, $id);
	} else if ($context === 2) {
		$id = get_the_ID();
		$the_time = get_post_modified_time("U", true, $id);
		$ajax_wait_time = get_post_modified_time("H:i", false, $id);
	}  else if ($context === 3) {
		$id = get_comment_ID();
		$the_time = get_comment_time("U", true, $id);
		$ajax_wait_time = get_comment_time("H:i", false, $id);
	}
	
	if(time() - $the_time > $stopUsingAjaxAfter) {
		echo wp_days_ago_internal_v3($the_time, $id, $showDateAfter, $showDateFormat, $showYesterday);
	} else {
		echo "<script type=\"text/javascript\"><!--\n";
		echo "jQuery(document).ready(function(){";
		echo "get_wp_days_ago_v3(" . $id . ", '" . $the_time . "', '" . $showDateAfter . "', '" . $showDateFormat . "', '" . $showYesterday . "', '" . $context . "');";
		echo "})\n";
		echo "--></script>\n";
		echo "<span class=\"wp_days_ago\" id=\"wp_days_ago-" . $context . "-" . $id . "\">" . $ajax_wait_time . "</span>";
	}
}

function wp_days_ago_ajax_handler_v3 () {
	$showDateFormat = $_POST["showDateFormat"];
	if($showDateFormat == 'null' || $showDateFormat == '') {
		$showDateFormat = null;
	}
	$showYesterday = $_POST["showYesterday"];
	if($showYesterday == '') {
		$showYesterday = false;
	} else {
		$showYesterday = true;
	}
		
	die(wp_days_ago_internal_v3($_POST["time"], $_POST["id"], $_POST["showDateAfter"], $showDateFormat, $showYesterday));
}

function wp_days_ago_internal_v3 ($the_time, $postId, $showDateAfter = -1, $showDateFormat = null, $showYesterday = true) {
		
	$gmt_offset = get_option("gmt_offset");
	$time = time();
	if($gmt_offset != null && $gmt_offset != "") {
		$the_time = $the_time + (3600 * $gmt_offset);
		$time = $time + (3600 * $gmt_offset); 
	}
			
	$output = "";

	if($showDateAfter > 0 && ($time - $the_time > $showDateAfter)) {
		if($showDateFormat == null) {
			$showDateFormat = get_option('date_format') . " " . get_option('time_format');
		}
		$output .= get_the_time($showDateFormat, $postId);
	} else {
		$output .= timespanToString(calculateTimespan($the_time, $time, $showYesterday), $showYesterday);
	}

	return $output;
}

function timespanToString($t, $showYesterday = true) {

	$foundSomething = $foundYear = $foundMonth = $singular = false;
	$s = "";
	//print_r($t);
	
	// Future
	/*if ($t[0] < 0) {
		$foundSomething = true;
		$singular = true;
		$foundYear = true;
		$foundMonth = true;
		$t[1] = 0;
		$s .= " " . __("Some time in the future", "wp-days-ago"); // FUTURE
	}*/
	
	// Year.
	if($t[0] >= 1) {
		$foundSomething = true;
		$foundYear = true;
		$s .= $t[0];
		if($t[0] == 1) {
			$s .= " " . __("year", "wp-days-ago"); // YEAR
		} else {
			$s .= " " . __("years", "wp-days-ago"); // YEARS
		}
	}
	
	// Month.
	if($t[1] >= 1) {
		$foundSomething = true;
		$foundMonth = true;
		if(strlen($s) > 0) {
			$s .= ", ";
		}
		$s .= $t[1];
		if($t[1] == 1) {
			$s .= " " . __("month", "wp-days-ago"); // MONTH
		} else {
			$s .= " " . __("months", "wp-days-ago"); // MONTHS
		}
	}
	
	// Day.
	if($t[2] >= 1 && (($foundYear && !$foundMonth) || (!$foundYear && $foundMonth) || (!$foundYear && !$foundMonth))) {
		$foundSomething = true;
		if(strlen($s) > 0) {
			$s .= ", ";
		}
		if($t[2] == 1 && $showYesterday) {
			if($foundYear || $foundMonth) {
				$s .= $t[2] . " " . __("day", "wp-days-ago"); // DAY
			} else {
				$s .= " " . __("Yesterday", "wp-days-ago"); // YESTERDAY
				$singular = true;
			}
		} else if($t[2] == 1 && !$showYesterday) {
			$s .= $t[2] . " " . __("day", "wp-days-ago"); // DAY
		} else if($t[2] == 7 && !$foundYear && !$foundMonth) {
			$s .= " " . __("One week", "wp-days-ago"); // ONE WEEK
		} else {
			$s .= $t[2] . " " . __("days", "wp-days-ago"); // DAYS
		}
	}
	
	// Hour.
	if($t[3] >= 1 && !$foundSomething) {
		$foundSomething = true;
		if(strlen($s) > 0) {
			$s .= ", ";
		}
		$s .= $t[3];
		if($t[3] == 1) {
			$s .= " " . __("hour", "wp-days-ago"); // HOUR
		} else {
			$s .= " " . __("hours", "wp-days-ago"); // HOURS
		}
	}
	
	// Minute.
	if($t[4] >= 1 && !$foundSomething) {
		$foundSomething = true;
		if(strlen($s) > 0) {
			$s .= ", ";
		}
		$s .= $t[4];
		if($t[4] == 1) {
			$s .= " " . __("minute", "wp-days-ago"); // MINUTE
		} else {
			$s .= " " . __("minutes", "wp-days-ago"); // MINUTES
		}
	}
	
	//Second.
	if($t[5] >= 1 && !$foundSomething) {
		$foundSomething = true;
		$singular = true;
		$s .= __("Just now", "wp-days-ago"); // JUST NOW
	}
	
	$prepender = __("prepender", "wp-days-ago");
	if($prepender == "prepender" || $prepender == "[none]") {
		$prepender = "";
	}
	
	$appender = __("ago", "wp-days-ago");
	if($appender == "[none]") {
		$appender = "";
	}
	
	return trim(($singular ? "" : " " . $prepender) . " " . $s . ($singular ? "" : " " . $appender)); // AGO
}

function calculateTimespan($older, $newer, $showYesterday = true) { 

	if(!$showYesterday && ($newer - $older < 86400)) {
		/* TODO: Fix this if-else-statement and merge it into one if-statement... */
	} else if(($newer - $older > 86400) || (date("j", $newer) != date("j", $older) && $newer - $older < 86400)) {
		$newer = mktime(0, 0, 0, date("n", $newer), date("j", $newer), date("Y", $newer));
		$older = mktime(0, 0, 0, date("n", $older), date("j", $older), date("Y", $older));
	}

	$Y1 = date('Y', $older); 
	$Y2 = date('Y', $newer); 
	$Y = $Y2 - $Y1; 

	$m1 = date('m', $older); 
	$m2 = date('m', $newer); 
	$m = $m2 - $m1; 

	$d1 = date('d', $older); 
	$d2 = date('d', $newer); 
	$d = $d2 - $d1; 

	$H1 = date('H', $older); 
	$H2 = date('H', $newer); 
	$H = $H2 - $H1; 

	$i1 = date('i', $older); 
	$i2 = date('i', $newer); 
	$i = $i2 - $i1; 

	$s1 = date('s', $older); 
	$s2 = date('s', $newer); 
	$s = $s2 - $s1; 

	if($s < 0) { 
		$i = $i -1; 
		$s = $s + 60; 
	} 
	if($i < 0) { 
		$H = $H - 1; 
		$i = $i + 60; 
	} 
	if($H < 0) { 
		$d = $d - 1; 
		$H = $H + 24; 
	} 
	if($d < 0) { 
		$m = $m - 1; 
		$d = $d + date('t', mktime(0, 0, 0, $m1, 1, $Y1));
	} 
	if($m < 0) { 
		$Y = $Y - 1; 
		$m = $m + 12; 
	} 
  
	return array($Y, $m, $d, $H, $i, $s);
} 
?>