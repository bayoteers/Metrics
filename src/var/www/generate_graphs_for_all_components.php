<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): generate_graphs_for_all_products.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

$graph_size_auto = !read_param_bool('graph_size_manual');
$graph_size_width = read_param_int("graph_size_width", 800);
$graph_size_height = read_param_int("graph_size_height", 500);
$graph_autoresize_all_products_graphs = read_param_bool('graph_autoresize_all_products_graphs');

print "<div id='stats_table'><p id='header_links'>";
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
    print "<a href='$link'>" . str_replace(array("_", "and"), array(" ", "&"), $selected_product) . "</a>";
}
if ($selected_component != "") {
    $link .= "&c=$selected_component";
    print " / ";
    print "<a href='$link'>$STR_GRAPHS_FOR_ALL_COMPONENTS</a>";
}
print "</p></div>";

?>

<!-- <hr/>  -->
<input id='graph_size_auto' type='hidden' value='<?php print $graph_size_auto; ?>'>
<input id='graph_size_width' type='hidden' value='<?php print $graph_size_width; ?>'>
<input id='graph_size_height' type='hidden' value='<?php print $graph_size_height; ?>'>
<input id='graph_autoresize_all_products_graphs' type='hidden' value='<?php print $graph_autoresize_all_products_graphs; ?>'>

<div id='stats_graphs'>
<?php
print "<div id='stats_graph_div_0' class='stats_graph2'>";
print "<a href='?s=$selected_statistic&p=$selected_product'><img id='stats_graph_img_0' src='img/loading.gif' alt='' title=\"Click on the graph to see details of '$selected_product'\"/></a>";
print "<input id='stats_graph_0' type='hidden' value='' alt='' />";
print "</div>";

$i = 1;
$products_components = get_products_components("$DATA_FOLDER/$selected_statistic/$selected_product");
foreach ($products_components as $component) {
    print "<div id='stats_graph_div_$i' class='stats_graph2'>";
    print "<a href='?s=$selected_statistic&p=$selected_product&c=$component'><img id='stats_graph_img_$i' src='img/loading.gif' alt='' title=\"Click on the graph to see details of '$selected_product / $component'\"/></a>";
    print "<input id='stats_graph_$i' type='hidden' value='$component' alt='' />";
    print "</div>";
    $i++;
}
?>

</div>
