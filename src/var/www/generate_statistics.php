<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): generate_statistics.php
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

$last_row = 0;
$last_day = "";
$last_week = "";

// ===============================================================
// read params
$no_of_weeks_to_show = read_param_int("no_of_weeks_to_show", 3);
$table_day_view = !read_param_bool('table_week_view');
$table_expanded = read_param_bool("table_expanded");
$graph_size_auto = !read_param_bool('graph_size_manual');
$graph_size_width = read_param_int("graph_size_width", 800);
$graph_size_height = read_param_int("graph_size_height", 500);


// global variables' file for particular statistic 
if (file_exists("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME");
}
// local variables' file for particular project
if (file_exists("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME");
}

// ===============================================================
// PRINT COLLECTED DATA
$stats = read_statistics($selected_statistic, $selected_product, $selected_component, $no_of_weeks_to_show, $table_day_view);
?>

<!-- <hr/>  -->
<input type='hidden' id='graph_size_auto' value='<?php print $graph_size_auto; ?>'>
<input type='hidden' id='graph_size_width' value='<?php print $graph_size_width; ?>'>
<input type='hidden' id='graph_size_height' value='<?php print $graph_size_height; ?>'>


<?php
if ($stats == null) {
    print "<span class='span_header'>No data available to present</span>";
}
else
{
?>

<div id='stats_table'>
    <?php
    print "<p id='header_links'>";
    $name = "";
    $link = "?s=";
    $s = explode("_-_", $selected_statistic);
    foreach ($s as $name) {
        if ($link == "?s=") {
            $link .= $name;
        } else {
            $link .= "_-_" . $name;
            print " / ";
        }
        print "<a href='$link'>" . str_replace(array("_", "and"), array(" ", "&"), $name) . "</a>";
    }
    if ($selected_product != "") {
        $link .= "&p=$selected_product";
        print "&nbsp;&nbsp;-&rarr;&nbsp;&nbsp;";
        print "<a href='$link'>" . ($selected_product===$ALL_PRODUCTS ? $STR_ALL_PRODUCTS : str_replace(array("_", "and"), array(" ", "&"), $selected_product)) . "</a>";
    }
    if ($selected_component != "") {
        $link .= "&c=$selected_component";
        print " / ";
        print "<a href='$link'>" . str_replace(array("_", "and"), array(" ", "&"), $selected_component) . "</a>";
    }
    print "</p>";
    ?>
    <!--
    <span class='span_header'><?php print $header; ?></span>:
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <?php
        print "<a href='generate_statistics_csv.php?s=$selected_statistic&p=$selected_product&c=$selected_component'" 
            . "title='Download statistics in CSV file (including hidden (older) ones).'>CSV</a>";

    ?>
    <br/>
    <br/>
    -->
    <table>
        <?php
        if ($table_expanded)
        {
        ?>
            <tr>
                <th colspan='2' rowspan='3'><?php print ($table_day_view ? "Week, day" : "Week"); ?></th>
                <th></th>
                <th colspan='5'>Status at the end of the <?php print ($table_day_view ? "day" : "week"); ?></th>
                <th></th>
                <th colspan='6'>Changes</th>
            </tr>
            <tr>
                <?php
                print "<th rowspan='2' class='td_space'></th>";
                print "<th rowspan='2' title='$STR_ACTIVE_DESC'>$STR_ACTIVE</th>";
                print "<th rowspan='2' title='$STR_UNVERIFIED_DESC'>$STR_UNVERIFIED</th>";
                print "<th rowspan='2' title='$STR_UNRELEASED_DESC'>$STR_UNRELEASED</th>";
                print "<th rowspan='2' title='$STR_OPEN_DESC'>$STR_OPEN</th>";
                print "<th rowspan='2' title='$STR_UNCONFIRMED_DESC'>$STR_UNCONFIRMED</th>";
                print "<th rowspan='2' class='td_space'></th>";
                print "<th>$STR_NEW</th>";
                print "<th>$STR_REOPENED</th>";
                print "<th>$STR_RESOLVED</th>";
                print "<th>$STR_MOVED_OUT</th>";
                print "<th rowspan='2'>$STR_RELEASED</th>";
                print "<th rowspan='2'>$STR_CLOSED</th>";
                ?>
            </tr>
            <tr>
                <?php
                print "<th colspan='2'>($STR_INFLOW)</th>";
                print "<th colspan='2'>($STR_OUTFLOW)</th>";
                ?>
            </tr>
        <?php
        }
        else
        {
        ?>
            <tr>
                <th colspan='2' rowspan='2'><?php print ($table_day_view ? "Week, day" : "Week"); ?></th>
                <th></th>
                <th colspan='5'>Status at the end of the <?php print ($table_day_view ? "day" : "week"); ?></th>
                <th></th>
                <th colspan='4'>Changes</th>
            </tr>
            <tr>
                <?php
                print "<th class='td_space'></th>";
                print "<th title='$STR_ACTIVE_DESC'>$STR_ACTIVE</th>";
                print "<th title='$STR_UNVERIFIED_DESC'>$STR_UNVERIFIED</th>";
                print "<th title='$STR_UNRELEASED_DESC'>$STR_UNRELEASED</th>";
                print "<th title='$STR_OPEN_DESC'>$STR_OPEN</th>";
                print "<th title='$STR_UNCONFIRMED_DESC'>$STR_UNCONFIRMED</th>";
                print "<th class='td_space'></th>";
                print "<th>$STR_INFLOW</th>";
                print "<th>$STR_OUTFLOW</th>";
                print "<th>$STR_RELEASED</th>";
                print "<th>$STR_CLOSED</th>";
                ?>
            </tr>
        <?php
        }

        // =================================================
        // DISPLAY
        if ( $stats['hidden_data'] )
        {
            print "<tr class='tr1' title='Statistics older than $no_of_weeks_to_show weeks are hidden. Change settings to see them.'><td";
            if ($table_expanded) {
                print " colspan='15'";
            } else {
                print " colspan='13'";
            }
            print " class='td_align_to_left' onclick='show_hide_settings();'>&lt;&lt;&lt;</td></tr>";
        }
            
        // =================================================
        // print statistics

        $prev_week = null;
        $command = null;

        for ($i = 0; $i < count($stats['desc_table']); $i++ )
        {
            // before new week put empty line
            if ($table_day_view) {
                $weeks = explode(',', $stats['desc_table'][$i]);
                $week = $weeks[0];
            	if ($prev_week == null || $week != $prev_week) {
	                if ($table_expanded) {
	                    print "<tr><td colspan='15'></td></tr>";
	                } else {
	                    print "<tr><td colspan='13'></td></tr>";
	                }
            	}
            	$prev_week = $week;
            }
        	
			if (array_key_exists('new_year',$stats)&&array_key_exists($i,$stats['new_year']) ) {
	                if ($table_expanded) {
	                    print "<tr><td class='td_align_to_left' colspan='15'>" . $stats['new_year'][$i] . "</tr>";
	                } else {
	                    print "<tr><td class='td_align_to_left' colspan='13'>" . $stats['new_year'][$i] . "</tr>";
	                }
			}
            
            $command = "show_changes_details(\"$i\", \"" . $stats['details_file'][$i] . "\", \"" . $stats['desc_table'][$i] . "\", \"" . $stats['snapshot_taken_time'][$i] . "\", -1);";
            if ($table_day_view) {
                print "<tr id='tr_$i' class='tr1' onclick='$command' title='Click on the row to see details of changes during this day'>";
            } else {
                print "<tr id='tr_$i' class='tr1' onclick='$command' title='Click on the row to see details of changes during this week'>";
            }
			
            print "<td id='td_$i'></td>";
            print "<td class='td_align_to_left'>" . $stats['desc_table'][$i] . "</td>";
            print "<td class='td_space'></td>";
            print "<td>" . $stats['active'][$i] . "</td>";
            print "<td>" . $stats['verifiable'][$i] . "</td>";
            print "<td>" . $stats['not_released'][$i] . "</td>";
            print "<td>" . $stats['open'][$i] . "</td>";
            print "<td>" . $stats['unconfirmed'][$i] . "</td>";
            print "<td class='td_space'></td>";

            if ($table_expanded) {
                print "<td>" . $stats['new'][$i] . "</td>";
                print "<td>" . $stats['reopened'][$i] . "</td>";
                print "<td>" . $stats['resolved'][$i] . "</td>";
                print "<td>" . $stats['moved_out'][$i] . "</td>";
            } else {
                print "<td>" . $stats['inflow'][$i] . "</td>";
                print "<td>" . $stats['outflow'][$i] . "</td>";
            }
            print "<td>" . $stats['released'][$i] . "</td>";
            print "<td>" . $stats['closed'][$i] . "</td>";
            print "</tr>";
            
            $last_row = $i;
            //if ($table_day_view) $last_day = $entry['day'];
            //$last_week = $entry['week'];
        }
        ?>
    </table>
    <br />
    <input type='hidden' id='last_row' value='<?php print $last_row; ?>' />
    <input type='hidden' id='last_details_file' value='<?php print $stats['details_file'][$last_row]; ?>' />
    <input type='hidden' id='last_desc_table' value='<?php print $stats['desc_table'][$last_row]; ?>' />
    <input type='hidden' id='last_snapshot_taken_time' value='<?php print $stats['snapshot_taken_time'][$last_row]; ?>' />
    
    <?php
    print "<a href='generate_statistics_csv.php?s=$selected_statistic&p=$selected_product&c=$selected_component'" 
        . "title='Download statistics in CSV file including hidden (older) ones.'>Save result in CSV file</a><br /><br />";

    
    if (file_exists("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME") ) {
        // TODO
                 
        print "<span class='span_bold'>Reports presenting current situation in " . ($selected_product===$ALL_PRODUCTS ? $STR_ALL_PRODUCTS : "'".str_replace(array("_", "and"), array(" ", "&"), $selected_product)."'") . " directly in Bugzilla</span>:";
        print "<table class='tab_links'>";
        if ($selected_product == $ALL_PRODUCTS) {
            print "<tr><td>-<span class='span_bold'>[ $STR_PRODUCT / status ]</span>:</td><td>" . create_reports_links("bug_status", $STR_PRODUCT_COLUMN_NAME, $STR_CLASSIFICATION_COLUMN_NAME) . "</td></tr>";
            print "<tr><td>-<span class='span_bold'>[ $STR_PRODUCT / severity ]</span>:</td><td>" . create_reports_links("bug_severity", $STR_PRODUCT_COLUMN_NAME, $STR_CLASSIFICATION_COLUMN_NAME) . "</td></tr>";
            print "<tr><td>-<span class='span_bold'>[ status / resolution ]</span>:</td><td>" . create_reports_links("resolution", "bug_status", $STR_CLASSIFICATION_COLUMN_NAME) . "</td></tr>";
            print "<tr><td>-<span class='span_bold'>[ status / severity ]</span>:</td><td>" . create_reports_links("bug_severity", "bug_status", $STR_CLASSIFICATION_COLUMN_NAME) . "</td></tr>";
        } else {
            print "<tr><td>-<span class='span_bold'>[ $STR_COMPONENT / status ]</span>:</td><td>" . create_reports_links("bug_status", $STR_COMPONENT_COLUMN_NAME, $STR_PRODUCT_COLUMN_NAME) . "</td></tr>";
            print "<tr><td>-<span class='span_bold'>[ $STR_COMPONENT / severity ]</span>:</td><td>" . create_reports_links("bug_severity", $STR_COMPONENT_COLUMN_NAME, $STR_PRODUCT_COLUMN_NAME) . "</td></tr>";
            print "<tr><td>-<span class='span_bold'>[ status / resolution ]</span>:</td><td>" . create_reports_links("resolution", "bug_status", $STR_PRODUCT_COLUMN_NAME) . "</td></tr>";
            print "<tr><td>-<span class='span_bold'>[ status / severity ]</span>:</td><td>" . create_reports_links("bug_severity", "bug_status", $STR_PRODUCT_COLUMN_NAME) . "</td></tr>";
        }
        print "</table>";
        
        
        
        

        if (preg_match('/msie/', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            print "<span class='span_err'>NOTE! These links can work incorrectly in Internet Explorer.<br />(IE browser does not accept URL containing > 2048 characters)<br />Use e.g. Firefox insted.</span>";
        }
    }
    ?>
    <br /><br />
    

</div>
<div id='stats_space'>
    &nbsp;
</div>
<div id='stats_graph'>
    
    <!-- <span class='span_bold'>Inflow / Outflow graph</span>:
    <br/>
    <br/> -->
    <img id='stats_graph_img' src='img/loading.gif' alt='' title='Graph details can be configured in settings'/>
</div>

<?php
}
?>
