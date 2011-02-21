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
?>

<!-- <hr/>  -->
<input id='graph_size_auto' type='hidden' value='<?php print $graph_size_auto; ?>'>
<input id='graph_size_width' type='hidden' value='<?php print $graph_size_width; ?>'>
<input id='graph_size_height' type='hidden' value='<?php print $graph_size_height; ?>'>
<input id='graph_autoresize_all_products_graphs' type='hidden' value='<?php print $graph_autoresize_all_products_graphs; ?>'>

<div id='stats_graphs'>
<?php
print "<div id='stats_graph_div_0' class='stats_graph2'>";
print "<a href='?s=$selected_statistic&p=$ALL_PRODUCTS'><img id='stats_graph_img_0' src='img/loading.gif' alt='' title=\"Click on the graph to see details of $STR_ALL_PRODUCTS\"/></a>";
print "<input id='stats_graph_0' type='hidden' value='$ALL_PRODUCTS' alt='' />";
print "</div>";

$i = 1;
foreach ($products_list as $product)
{
    if ($product != $ALL_PRODUCTS) {
        print "<div id='stats_graph_div_$i' class='stats_graph2'>";
        print "<a href='?s=$selected_statistic&p=$product'><img id='stats_graph_img_$i' src='img/loading.gif' alt='' title=\"Click on the graph to see details of '$product'\"/></a>";
        print "<input id='stats_graph_$i' type='hidden' value='$product' alt='' />";
        print "</div>";
    }
    $i++;
}
?>

</div>
