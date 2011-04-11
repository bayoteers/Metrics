<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): functions.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

#############################################################
/**
 *
 * @return folders list or null.
 */
function get_folders_list($data_folder, $exclude_names)
{
    $ret_arr = array ();

    if ($handle = opendir($data_folder))
    {
        while (false !== ($value = readdir($handle)))
        {
            if (is_dir("$data_folder/$value")
            && $value != "."
            && $value != ".."
            && !in_array($value, $exclude_names) )
            {
                $ret_arr[] = $value;
            }
        }
        closedir($handle);
    } else
    {
        print "<br /><span class='span_header span_err'>Cannot open folder '$data_folder'</span><br /><br />";
        return null;
    }

    if (count($ret_arr) > 0)
    {
        sort($ret_arr);
    }

    return $ret_arr;
}

#############################################################

function get_products_components($folder)
{
    require "static_variables.php";
    
    $ret_arr = array ();

    $file = "$folder/$SUBGROUPS_FILE_NAME";
    
    if (file_exists($file)) {
        $tmp = explode(';', file_get_contents($file));
        foreach ($tmp as $t) {
            if ( trim($t) != "") {
                $ret_arr[] = trim($t);
            }
        }
    } else {
	}
    
    if (count($ret_arr) > 0)
    {
        sort($ret_arr);
    }
    return $ret_arr;
}

#############################################################
/**
 * Read statistics for specified $product component.
 * @param object $statistic - selected statistics
 * @param object $product - selected product
 * @param object $component - selected component
 * @param object $no_of_weeks_to_show - statistics from how many weeks should be presented
 * @param object $table_day_view - true: day view, false: week view
 * @return Statistics' array or null in case of error.
 */
function read_statistics($statistic, $product, $component, $no_of_weeks_to_show, $day_view)
{
    require "static_variables.php";

    $stats_file = "$DATA_FOLDER/$statistic/$product/";
    if ($day_view) {
        $stats_file .= $DAILY_STATS_HISTORY_FILE_NAME;
    } else {
        $stats_file .= $WEEKLY_STATS_HISTORY_FILE_NAME;
    }
    if ($component != "") {
        $stats_file .= "_$component";
    }

    if (!is_file($stats_file)) {
        print "<br /><span class='span_header span_err'>Cannot read a file: $stats_file</span><br /><br />";
        return null;
    }

    $stats = null;
    $stats['long_desc_graph'] = false;

    $lines = file($stats_file);
    $lines_count = count($lines);
    $first_row = 0;
    
    if ($day_view) {
        if ($no_of_weeks_to_show * 5 < $lines_count)
        {
            $first_row = $lines_count - ($no_of_weeks_to_show * 5);
            $weeks = explode(',', $lines[$first_row]);
            $week = $weeks[0];
            for ($i=$first_row; $i>=0; $i--) {
                $pos = strpos($lines[$i], $week);
                if ($pos === false) {
                    break;
                } else if ($pos == 0) {
                    $first_row = $i;
                }
            }
        }
    } else {
        if ($no_of_weeks_to_show < $lines_count) {
            $first_row = $lines_count - $no_of_weeks_to_show;
        }
    }

    if ($first_row != 0) {
        $stats['hidden_data'] = true;
    } else {
        $stats['hidden_data'] = false;
    }

    $i = 0;
    for ($line_no = $first_row; $line_no < $lines_count; $line_no++)
    {
        $line = trim($lines[$line_no]);
        if (empty($line)) {
            continue;
        }
        $entry = explode(';;;', $line);
        
		$week = substr(trim($entry[0]), 5, 2);
        $week_day = explode(',', trim($entry[0]));
        
        if ($day_view) {
            if ( $line_no == $lines_count-1 && count($week_day) == 3) {
                // online view: "Week 11, Mon, 10:32"
                $stats['desc_table'][$i] = $entry[0];
                if ($week_day[1] === " Mon") {
                    $stats['desc_graph'][$i] = str_replace("Week ", "W", $week_day[0]) . $week_day[1] . $week_day[2];
                    $stats['long_desc_graph'] = true;
                } else {
                    $stats['desc_graph'][$i] = $week_day[1] . $week_day[2];
                }
            } else {
                // day view: "Week 11, Mon"
                $stats['desc_table'][$i] = $week_day[0] . "," . $week_day[1];
                if ($week_day[1] === " Mon") {
                    $stats['desc_graph'][$i] = str_replace("Week ", "W", $week_day[0]) . $week_day[1];
    				if ($week == 1) {
    					$stats['new_year'][$i] = "Year " . substr($entry[2], 0, 4);
    				}
                } else {
                    $stats['desc_graph'][$i] = $week_day[1];
                }
            }
        } else {
            if ( $line_no == $lines_count-1 && count($week_day) == 3) {
                // online view: "Week 11, Mon, 10:32"
                $stats['desc_table'][$i] = $entry[0];
                $stats['desc_graph'][$i] = str_replace("Week ", "W", $week_day[0]) . $week_day[1] . $week_day[2];
                $stats['long_desc_graph'] = true;
            } else {
            	// week view: "Week 31"
            	$stats['desc_table'][$i] = $week_day[0];
                $stats['desc_graph'][$i] = str_replace("Week ", "W", $week_day[0]);
    			if ($week == 1) {
    				$stats['new_year'][$i] = "Year " . substr($entry[2], 0, 4) . "</br>";
    			}
            }
        }
        // 0              1                         2            3     4    5   6    7     8   9   10  11  12  13
        // Week 36, Mon;;;Tue,  7 Sep 2010, 14:07;;;2010_36_1;;; 109;;;25;;;7;;;84;;;27;;; 1;;;0;;;5;;;0;;;0;;;1
        
        $stats['snapshot_taken_time'][$i] = $entry[1];
        $stats['details_file'][$i] = $entry[2];
        if (count($entry) == 13 ) {
            // for backword compatibility 
            $stats['active'][$i] = $entry[3];
            $stats['fixed'][$i] = $entry[4];
            $stats['verifiable'][$i] = "-";
            $stats['not_released'][$i] = $entry[5];
            $stats['verifiable_graph'][$i] = "-";
            $stats['not_released_graph'][$i] = $entry[5] + $entry[6];
            $stats['open'][$i] = $entry[6];
            $stats['unconfirmed'][$i] = "-";
            $stats['new'][$i] = $entry[7];
            $stats['reopened'][$i] = $entry[8];
            $stats['inflow'][$i] = $entry[7] + $entry[8];
            $stats['resolved'][$i] = $entry[9];
            $stats['moved_out'][$i] = $entry[10];
            $stats['outflow'][$i] = $entry[9] + $entry[10];
            $stats['released'][$i] = $entry[11];
            $stats['closed'][$i] = $entry[12];
        } else if (count($entry) == 14 ) {
            // for backword compatibility
            $stats['active'][$i] = $entry[3];
            $stats['fixed'][$i] = $entry[4];
            $stats['verifiable'][$i] = "-";
            $stats['not_released'][$i] = $entry[5];
            $stats['verifiable_graph'][$i] = "-";
            $stats['not_released_graph'][$i] = $entry[5] + $entry[6];
            $stats['open'][$i] = $entry[6];
            $stats['unconfirmed'][$i] = ($entry[7]==-1?"-":$entry[7]);
            $stats['new'][$i] = $entry[8];
            $stats['reopened'][$i] = $entry[9];
            $stats['inflow'][$i] = $entry[8] + $entry[9];
            $stats['resolved'][$i] = $entry[10];
            $stats['moved_out'][$i] = $entry[11];
            $stats['outflow'][$i] = $entry[10] + $entry[11];
            $stats['released'][$i] = $entry[12];
            $stats['closed'][$i] = $entry[13];
        } else {
            $stats['active'][$i] = $entry[3];
            $stats['fixed'][$i] = $entry[4];
            $stats['verifiable'][$i] = $entry[5];
            $stats['not_released'][$i] = $entry[6];
            $stats['verifiable_graph'][$i] = $entry[3] - $entry[5];
            $stats['not_released_graph'][$i] = $entry[6] + $entry[7];
            $stats['open'][$i] = $entry[7];
            $stats['unconfirmed'][$i] = ($entry[8]==-1?"-":$entry[8]);
            $stats['new'][$i] = $entry[9];
            $stats['reopened'][$i] = $entry[10];
            $stats['inflow'][$i] = $entry[9] + $entry[10];
            $stats['resolved'][$i] = $entry[11];
            $stats['moved_out'][$i] = $entry[12];
            $stats['outflow'][$i] = $entry[11] + $entry[12];
            $stats['released'][$i] = $entry[13];
            $stats['closed'][$i] = $entry[14];
        }
        $i++;
    }

    return $stats;
}

#############################################################
/**
 *
 * @param $file
 * @return unknown_type
 */
function read_changes_details($file)
{
    if (!is_file($file)) {
        return null;
    }

    $changes_details = null;

    $lines = file($file);
    foreach ($lines as $line)
    {
        // 0             1        2       3             4
        // unconfirmed;;;178822;;;major;;;unspecified;;;Ovi account shall be asked in the first phone boot and never again
        // closed;;;179506;;;major;;;high;;;Signond is filling /tmp with traces
        // active;;;155750;;;blocker;;;GUI session is not starting after flashing latest staging image
        $entry = explode(';;;', trim($line));
        if (count($entry) == 5) {
            $changes_details[$entry[0]][$entry[1]]['severity'] = $entry[2];
            $changes_details[$entry[0]][$entry[1]]['priority'] = $entry[3];
            $changes_details[$entry[0]][$entry[1]]['description'] = $entry[4];
        } else if (count($entry) == 4) {
            $changes_details[$entry[0]][$entry[1]]['severity'] = $entry[2];
            $changes_details[$entry[0]][$entry[1]]['priority'] = '';
            $changes_details[$entry[0]][$entry[1]]['description'] = $entry[3];
        } else {
            $changes_details[$entry[0]][$entry[1]]['severity'] = '';
            $changes_details[$entry[0]][$entry[1]]['priority'] = '';
            $changes_details[$entry[0]][$entry[1]]['description'] = $entry[2];
        }
    }

    if ($changes_details != null) {
        if ( array_key_exists('active', $changes_details) ) ksort($changes_details['active']);
        if ( array_key_exists('verifiable', $changes_details) ) ksort($changes_details['verifiable']);
        if ( array_key_exists('not_released', $changes_details) ) ksort($changes_details['not_released']);
        if ( array_key_exists('open', $changes_details) ) ksort($changes_details['open']);
        if ( array_key_exists('unconfirmed', $changes_details) ) ksort($changes_details['unconfirmed']);
        if ( array_key_exists('new', $changes_details) ) ksort($changes_details['new']);
        if ( array_key_exists('reopened', $changes_details) ) ksort($changes_details['reopened']);
        if ( array_key_exists('resolved', $changes_details) ) ksort($changes_details['resolved']);
        if ( array_key_exists('moved_out', $changes_details) ) ksort($changes_details['moved_out']);
        if ( array_key_exists('released', $changes_details) ) ksort($changes_details['released']);
        if ( array_key_exists('closed', $changes_details) ) ksort($changes_details['closed']);
    }

    return $changes_details;
}

#############################################################
#############################################################
function create_reports_links ($x_axis, $y_axis, $z_axis) {
    global $BUGZILLA_URL_BASE, $BUGZILLA_URL_COMMON, $BUGS_ACTIVE, $BUGS_NOT_RELEASED, $BUGS_OPEN, $BUGS_NOT_CONFIRMED, $STR_ACTIVE, $STR_UNRELEASED, $STR_OPEN, $STR_UNCONFIRMED;
    $BUGZILLA_URL_REPORT = "$BUGZILLA_URL_BASE/report.cgi?query_format=report-table&format=table&action=wrap&x_axis_field=$x_axis&y_axis_field=$y_axis&z_axis_field=$z_axis$BUGZILLA_URL_COMMON";
    $ret = "<a target='_blank' href='$BUGZILLA_URL_REPORT$BUGS_ACTIVE'>$STR_ACTIVE</a>&nbsp;&nbsp;&nbsp;";
    $ret .= "<a target='_blank' href='$BUGZILLA_URL_REPORT$BUGS_NOT_RELEASED'>$STR_UNRELEASED</a>&nbsp;&nbsp;&nbsp;";
    $ret .= "<a target='_blank' href='$BUGZILLA_URL_REPORT$BUGS_OPEN'>$STR_OPEN</a>&nbsp;&nbsp;&nbsp;";
    $ret .= "<a target='_blank' href='$BUGZILLA_URL_REPORT$BUGS_NOT_CONFIRMED'>$STR_UNCONFIRMED</a>";
    return $ret;
}

#############################################################

/**
 * read param '$name':
 * - first try to read from $_GET
 * - next from $_COOKIE
 * Expected type of value is "true" or "false"
 * @return boolean true or false
 * @param object $name
 */
function read_param_bool($name)
{
    if ( isset ($_GET[$name]) && $_GET[$name] != "") {
        if ($_GET[$name] == "true") {
            return true;
        }
    }
    else if ( isset ($_COOKIE[$name]) && $_COOKIE[$name] != "") {
        if ($_COOKIE[$name] == "true") {
            return true;
        }
    }
    return false;
}

#############################################################
/**
 * read param '$name':
 * - first try to read from $_GET
 * - next from $_COOKIE
 * Expected type of value is Integer
 * @return Integer type value
 * @param object $name
 */
function read_param_int($name, $default)
{
    if ( isset ($_GET[$name]) && $_GET[$name] != "")
    {
        return $_GET[$name];
    }
    else if ( isset ($_COOKIE[$name]) && $_COOKIE[$name] != "")
    {
        return $_COOKIE[$name];
    }

    return $default;
}

#############################################################
function save_param_bool($name)
{
    if ( isset ($_GET[$name]) && $_GET[$name] != "") {
        if ($_GET[$name] == "true") {
            setcookie($name, "true", time()+60*60*24*300);
            return 1;
        }
    }
    setcookie($name, "false", time()+60*60*24*300);
    return 1;
}

#############################################################
function save_param_int($name)
{
    if ( isset ($_GET[$name]) && $_GET[$name] != "")
    {
        setcookie($name, $_GET[$name], time()+60*60*24*300);
    }
    return 1;
}


?>
