<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): generate_list_of_available_statistics.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/
?>

<!-- <hr/>  -->
<span class='span_header'>List of all collected statistics:</span>
<br /><br />

<?php
//$IE = (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')!==FALSE?true:false);
$g_id = 0;

foreach ($statistics_groups as $group_name=>$group)
{
    $g1_count = 0;
    $g1_content = "";
    foreach ($group as $subgroup_name=>$subgroup)
    {
        if ($subgroup_name != '[all]')
        {
            if (count($subgroup) == 1)
            {
                $g1_count++;
                if ($subgroup[0] === '[all]') {
                    $g1_content .= "<td class='td2'><a href='?s=" . $group_name . "_-_" . $subgroup_name . "'>" . str_replace(array("_", "and"), array(" ", "&"), $subgroup_name) . "</a></td><td class='td0'></td></tr><tr>\n";
                } else {
                    $g1_content .= "<td class='td0'><span>" . str_replace(array("_", "and"), array(" ", "&"), $subgroup_name) . "</span></td>\n";
                    $g1_content .= "<td class='td2'><a href='?s=" . $group_name . "_-_" . $subgroup_name . "_-_" . $subgroup[0] . "'>" . str_replace(array("_", "and"), array(" ", "&"), $subgroup[0]) . "</a></td></tr><tr>\n";
                }
            }
            else
            {
                $g2_count = 0;
                $g2_content = "";
                foreach ($subgroup as $subsubgroup_name)
                {
                    if ($subsubgroup_name != '[all]')
                    {
                        $g2_count++;
                        $g2_content .= "<td class='td2'><a href='?s=" . $group_name . "_-_" . $subgroup_name . "_-_" . $subsubgroup_name . "'>" . str_replace(array("_", "and"), array(" ", "&"), "$subsubgroup_name") . "</a></td></tr>\n";
                        if ($g2_count < count($subgroup)) {
                            $g2_content .= "<tr>";
                        }
                    }
                }

                if (in_array('[all]', $subgroup)) {
                    $g1_content .= "<td class='td2' rowspan='$g2_count'><a href='?s=" . $group_name . "_-_" . $subgroup_name . "'>" . str_replace(array("_", "and"), array(" ", "&"), $subgroup_name) . "</a></td>\n";
                } else {
                    $g1_content .= "<td class='td0' rowspan='$g2_count'><span>" . str_replace(array("_", "and"), array(" ", "&"), $subgroup_name) . "</span></td>\n";
                }
                $g1_content .= $g2_content;
                $g1_count += $g2_count;
            }
        }
        
    }
    
    if ($g1_count == 0)
    {
        print "<table class='table_header' id='th_$g_id'><tr>\n";
        print "<td class='td1'><a href='?s=$group_name'>" . str_replace(array("_", "and"), array(" ", "&"), $group_name) . "</a></td><td class='td0'><span>&nbsp;</span></td><td class='td0'><span>&nbsp;</span></td><tr></tr>\n";
        print "</tr></table>";
    }
    else
    {
        print "<div class='table_div_header'><table class='table_header'><tr>\n";
        if (array_key_exists('[all]', $group)) {
            print "<td class='td1' rowspan='$g1_count'><a href='?s=$group_name'>" . str_replace(array("_", "and"), array(" ", "&"), $group_name) . "</a></td>\n";
        } else {
            print "<td class='td0' rowspan='$g1_count'><span>" . str_replace(array("_", "and"), array(" ", "&"), $group_name) . "</span></td>\n";
        }
        print "<td class='td_btn' id='th_$g_id' onmouseover='image_hover(\"plusminus_$g_id\");' onmouseout='image_default(\"plusminus_$g_id\");' title='Show statistics available in this group.' onclick='' >";
        print "<img id='plusminus_$g_id' src='img/plus.gif' class='img_arrow' alt='' />";
        print "</td>";
        print "</tr></table></div>";
        
        print "<div id='tc_$g_id' class='table_div_content'><table class='table_content'><tr>\n";
        print "<td class='td0' rowspan='$g1_count'></td>\n";
        
        $g1_content = trim($g1_content);
        $g1_content = substr($g1_content, 0, -4);
        //strrpos 
        
        
        print "$g1_content";
        /*
        if ($IE) {
            print "<td colspan='3'><hr class='hr_100' /></td></tr><tr>\n";
        }
        */
        print "</table></div>";
    }
    $g_id++;
}
//if ($IE) {
//    print "<td colspan='3'><hr class='hr_100' /></td></tr><tr>";
//}

?>
