<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): generate_statistics_csv.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

// ===============================================================
// variables
$current_time = new DateTime();
require_once "lib/functions.php";
require_once "lib/static_variables.php";

$selected_statistic = $_GET["s"];
$selected_product = $ALL_PRODUCTS;
$selected_component = "";

if (isset($_GET['p']) && $_GET['p'] != '') {
    $selected_product = $_GET["p"];
}
if (isset($_GET['c']) && $_GET['c'] != '') {
    $selected_component = $_GET["c"];
}

// global variables' file for particular statistic 
if (file_exists("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME");
}
// local variables' file for particular project
if (file_exists("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME");
}


$table_day_view = !read_param_bool('table_week_view');
$table_expanded = read_param_bool("table_expanded");

$filename = $selected_statistic . '_-_' . $selected_product;
if ($selected_component != "") {
    $filename .= '_-_' . $selected_component;
}
$filename .= '_-_' . $current_time->format('Y-m-d_G-i');
if ($table_day_view) {
    $filename .= "_-_day_view.csv";
} else {
    $filename .= "_-_week_view.csv";
}

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

if ($selected_statistic != "")
{
    // print statistics from all collected days for a selected product
    $stats = read_statistics($selected_statistic, $selected_product, $selected_component, 99, $table_day_view);
    
    if ( count($stats['desc_table']) == 0 ) {
        print "No snapshots for $STR_PRODUCT '".$selected_product."'<br />";
    }
    else
    {
        print "statistics:;'$selected_statistic'\r\n";
        print "$STR_PRODUCT:;'$selected_product'\r\n";
        if ($selected_component != "") {
            print "$STR_COMPONENT:;'$selected_component'\r\n";
        }
        if ($table_day_view) {
            print "type:;day view\r\n";
        } else {
            print "type:;week view\r\n";
        }
        print ";\r\n";
        if ($table_day_view) {
            print "\"Week, day(s)\";";
        } else {
            print "\"Week\";";
        }
        if ($table_expanded) {
            print "Snapshot taken at;Current status;;;;;Changes in bug statuses;;;;;\r\n";
            print ";;$STR_ACTIVE;$STR_UNVERIFIED;$STR_UNRELEASED;$STR_OPEN;$STR_UNCONFIRMED;$STR_NEW;$STR_REOPENED;$STR_RESOLVED;$STR_MOVED_OUT;$STR_RELEASED;$STR_CLOSED\r\n";
        } else {
            print "Snapshot taken at;Current status;;;;;Changes in bug statuses;;;\r\n";
            print ";;$STR_ACTIVE;$STR_UNVERIFIED;$STR_UNRELEASED;$STR_OPEN;$STR_UNCONFIRMED;$STR_INFLOW;$STR_OUTFLOW;$STR_RELEASED;$STR_CLOSED\r\n";
        }
        
        $prev_week = null;

        for ($i = 0; $i < count($stats['desc_table']); $i++ )
        {
            // before new week put empty line
            if ($table_day_view) {
                $weeks = explode(',', $stats['desc_table'][$i]);
                $week = $weeks[0];
                if ($prev_week == null || $week != $prev_week) {
                    if ($table_expanded) {
                        print ";;;;;;;;;;;;\r\n";
                    } else {
                        print ";;;;;;;;;;\r\n";
                    }
                }
                $prev_week = $week;
            }

            // Snapshot time
            print "\"" . $stats['desc_table'][$i] . "\";";
            print "\"" . $stats['snapshot_taken_time'][$i] . "\";";
            print $stats['active'][$i] . ";";
            print $stats['verifiable'][$i] . ";";
            print $stats['not_released'][$i] . ";";
            print $stats['open'][$i] . ";";
            print $stats['unconfirmed'][$i] . ";";

            if ($table_expanded) {
                print $stats['new'][$i] . ";";
                print $stats['reopened'][$i] . ";";
                print $stats['resolved'][$i] . ";";
                print $stats['moved_out'][$i] . ";";
            } else {
                print $stats['inflow'][$i] . ";";
                print $stats['outflow'][$i] . ";";
            }
            print $stats['released'][$i] . ";";
            print $stats['closed'][$i] . "\r\n";
        }
    }
} else {
    print "missing parameter: 'stats' ! ";
}
?>
