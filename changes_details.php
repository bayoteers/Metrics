<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): changes_details.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

session_start();
require_once "lib/functions.php";
require_once "lib/static_variables.php";


$selected_statistic = $_GET["s"];
$selected_product = $_GET["p"];
$file = $_GET["f"];
$week_day = $_GET["d"];
$snapshot_taken_time = $_GET["t"];

if ( isset ($_GET['sid']) && $_GET['sid'] != "")
save_param_bool('sort_by_bug_id');
$sort_by_bug_id = read_param_bool('sort_by_bug_id');

// global variables' file for particular statistic 
if (file_exists("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME");
}
// local variables' file for particular project
if (file_exists("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME");
}

$changes_details = read_changes_details("$DATA_FOLDER/$selected_statistic/$selected_product/$file");

?>

<ul class="details_menu">
    <?php
    print "<li><a href='#active'>$STR_ACTIVE</a></li>";
    print "<li><a href='#unverified'>$STR_UNVERIFIED</a></li>";
    print "<li><a href='#unreleased'>$STR_UNRELEASED</a></li>";
    print "<li><a href='#open'>$STR_OPEN</a></li>";
    print "<li><a href='#unconfirmed'>$STR_UNCONFIRMED</a></li>";
    print "<li><a href='#inflow'>$STR_INFLOW</a></li>";
    print "<li><a href='#outflow'>$STR_OUTFLOW</a></li>";
    print "<li><a href='#released'>$STR_RELEASED</a></li>";
    print "<li><a href='#closed'>$STR_CLOSED</a></li>";
    ?>
</ul>
<br />


<div class="details_container">
    <div id="active" class='details_tab'>
        <?php print_changes_details($changes_details, 'active', $STR_ACTIVE_DESC, "Status at $snapshot_taken_time"); ?></p>
    </div>
    <div id="unverified" class='details_tab'>
        <?php print_changes_details($changes_details, 'verifiable', $STR_UNVERIFIED_DESC, "Status at $snapshot_taken_time"); ?></p>
    </div>
    <div id="unreleased" class='details_tab'>
        <?php print_changes_details($changes_details, 'not_released', $STR_UNRELEASED_DESC, "Status at $snapshot_taken_time"); ?></p>
    </div>
    <div id="open" class='details_tab'>
        <?php print_changes_details($changes_details, 'open', $STR_OPEN_DESC, "Status at $snapshot_taken_time"); ?></p>
    </div>
    <div id="unconfirmed" class='details_tab'>
        <?php print_changes_details($changes_details, 'unconfirmed', $STR_UNCONFIRMED_DESC, "Status at $snapshot_taken_time"); ?></p>
    </div>

    <div id="inflow" class='details_tab'>
        <?php print_changes_details($changes_details, 'new', $STR_NEW_DESC, "Changes done during $week_day"); ?></p>
        <hr class='hr_short' />
        <?php print_changes_details($changes_details, 'reopened', $STR_REOPENED_DESC, "Changes done during $week_day"); ?></p>
    </div>
    <div id="outflow" class='details_tab'>
        <?php print_changes_details($changes_details, 'resolved', $STR_RESOLVED_DESC, "Changes done during $week_day"); ?></p>
        <hr class='hr_short' />
        <?php print_changes_details($changes_details, 'moved_out', $STR_MOVED_OUT_DESC, "Changes done during $week_day"); ?></p>
    </div>
    <div id="released" class='details_tab'>
        <?php print_changes_details($changes_details, 'released', $STR_RELEASED_DESC, "Changes done during $week_day"); ?></p>
    </div>
    <div id="closed" class='details_tab'>
        <?php print_changes_details($changes_details, 'closed', $STR_CLOSED_DESC, "Changes done during $week_day"); ?></p>
    </div>
</div>
<?php

$sorting = "<div id='sorting'>sort by:"
    . "<input id='sort_by_bug_id' type='radio'" . ($sort_by_bug_id ? " checked='checked'" : "")
    . "onclick='show_changes_details(prev_row_id, \"$file\", \"$week_day\", \"$snapshot_taken_time\", 1);'>bug id</input>&nbsp;&nbsp;"
    . "<input id='sort_by_severity' type='radio'" . ($sort_by_bug_id ? "" : "checked='checked'")
    . "onclick='show_changes_details(prev_row_id, \"$file\", \"$week_day\", \"$snapshot_taken_time\", 0);'>severity</input></div>";

print $sorting;
// TODO - place it correctly


function print_changes_details($changes_details, $type, $header, $add_info)
{
    global $BUGZILLA_URL_BASE, $sort_by_bug_id;
    $BUGZILLA_URL_BUG = $BUGZILLA_URL_BASE . "/show_bug.cgi?id=";
    $BUGZILLA_URL_BUGS_LIST = $BUGZILLA_URL_BASE . "/buglist.cgi?query_format=advanced&bug_id=";
    
    print "<p><span class='span_bold'>$header</span>";
    if ($add_info != "") {
        print " <br />$add_info";
    }
    print " :<br /><br />";
    
    if ( $changes_details == null)
    {
        print "Details are not available<br />";
    }
    else if (! array_key_exists($type, $changes_details) || count($changes_details[$type]) == 0)
    {
        print "none<br />";
    }
    else
    {
        
        $list = "<span class='span_bold'>See it in Bugzilla</span>:";
        $list .= "&nbsp;&nbsp;&nbsp;<a target='_blank' href='$BUGZILLA_URL_BUGS_LIST";
        $i1 = 1;
        $i2 = 0;
        foreach ($changes_details[$type] as $bug_id=>$dd) {
            $list .= "$bug_id,";
            $i2++;
            if ($i2%500 == 0 && $i2 != count($changes_details[$type])) {
                $list .= "'>$i1-$i2</a>&nbsp;&nbsp;&nbsp;<a target='_blank' href='$BUGZILLA_URL_BUGS_LIST";
                $i1 = $i2+1;
            }
        }
        $list .= "'>$i1-$i2</a><br />";
        print $list;
        
        $bc_only = ( count($changes_details[$type]) > 500 ? true : false);
        if ( $bc_only ) {
            print "There are more that 500 bugs on the list so only blockers and critical are presented here.<br />";
        }
        
        if ($sort_by_bug_id) {
            // sort by bug ID
            foreach ($changes_details[$type] as $bug_id=>$dd) {
                $severity = $dd['severity'];
                $priority = ($dd['priority']!=""?" / ".$dd['priority']:"");
                $description = $dd['description'];
                $description = str_replace  ( "<", "&gt;", $description);
                $description = str_replace  ( "<", "&gt;", $description);
                print "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (";
                if ($severity == 'blocker') {
                    print "<span class='blocker'>blocker";
                } else if ($severity == 'critical') {
                    print "<span class='critical'>critical";
                } else if (! $bc_only) {
                    if ($severity == 'major') {
                        print "<span class='major'>major";
                    } else if ($severity == 'normal') {
                        print "<span class='normal'>normal";
                    } else if ($severity == 'minor') {
                        print "<span class='minor'>minor";
                    } else {
                        print "<span class='enhancement'>$severity";
                    }
                }
                if (! $bc_only) {
                    print "$priority</span>) ".$description."<br />";
                }
            }
        } else {
            // sort by bug severity
            $bugs_blocker = '';
            $bugs_critical = '';
            $bugs_major = '';
            $bugs_normal = '';
            $bugs_minor = '';
            $bugs_enhancement = '';
            foreach ($changes_details[$type] as $bug_id=>$dd) {
                $severity = $dd['severity'];
                $priority = ($dd['priority']!=""?" / ".$dd['priority']:"");
                $description = $dd['description'];
                $description = str_replace  ( "<", "&gt;", $description);
                $description = str_replace  ( "<", "&gt;", $description);
                if ($severity == 'blocker') {
                    $bugs_blocker .= "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (<span class='blocker'>blocker$priority</span>) ".$description."<br />";
                } else if ($severity == 'critical') {
                    $bugs_critical .= "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (<span class='critical'>critical$priority</span>) ".$description."<br />";
                } else if (! $bc_only) {
                    if ($severity == 'major') {
                        $bugs_major .= "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (<span class='major'>major$priority</span>) ".$description."<br />";
                    } else if ($severity == 'normal') {
                        $bugs_normal .= "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (<span class='normal'>normal$priority</span>) ".$description."<br />";
                    } else if ($severity == 'minor') {
                        $bugs_minor .= "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (<span class='minor'>minor$priority</span>) ".$description."<br />";
                    } else {
                        $bugs_enhancement .= "<a href='".$BUGZILLA_URL_BUG.$bug_id."'>$bug_id</a> (<span class='enhancement'>$severity$priority</span>) ".$description."<br />";
                    }
                }
            }
            print $bugs_blocker;
            print $bugs_critical;
            if (! $bc_only) {
                print $bugs_major;
                print $bugs_normal;
                print $bugs_minor;
                print $bugs_enhancement;
            }
        }
    }
}

?>

<br />
