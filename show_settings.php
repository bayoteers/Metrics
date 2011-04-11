<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): show_settings.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

$no_of_weeks_to_show = read_param_int('no_of_weeks_to_show', 3);
$default_page_start_page = read_param_bool("default_page_all_products")?"":" checked='checked'";
$default_page_all_products = read_param_bool("default_page_all_products")?" checked='checked'":"";
$table_week_view = read_param_bool('table_week_view')?" checked='checked'":"";
$table_day_view = read_param_bool('table_week_view')?"":" checked='checked'";
$table_expanded = read_param_bool('table_expanded')?" checked='checked'":"";
$graph_week_view = read_param_bool('graph_week_view')?" checked='checked'":"";
$graph_day_view = read_param_bool('graph_week_view')?"":" checked='checked'";
$graph_bugs_active = read_param_bool('graph_no_bugs_active')?"":" checked='checked'";
$graph_bugs_verifiable = read_param_bool('graph_bugs_verifiable')?" checked='checked'":"";
$graph_bugs_not_released = read_param_bool('graph_bugs_not_released')?" checked='checked'":"";
$graph_bugs_open = read_param_bool('graph_no_bugs_open')?"":" checked='checked'";
$graph_bugs_unconfirmed = read_param_bool('graph_bugs_unconfirmed')?" checked='checked'":"";
$graph_bugs_inflow = read_param_bool('graph_no_bugs_inflow')?"":" checked='checked'";
$graph_bugs_outflow = read_param_bool('graph_no_bugs_outflow')?"":" checked='checked'";
$graph_bugs_released = read_param_bool('graph_no_bugs_released')?"":" checked='checked'";
$graph_bugs_closed = read_param_bool('graph_bugs_closed')?" checked='checked'":"";
$graph_size_manual = read_param_bool('graph_size_manual')?" checked='checked'":"";
$graph_size_auto = read_param_bool('graph_size_manual')?"":" checked='checked'";
$graph_size_width = read_param_int('graph_size_width', 800);
$graph_size_height = read_param_int('graph_size_height', 500);
$graph_autoresize_all_products_graphs = read_param_bool('graph_autoresize_all_products_graphs')?" checked='checked'":"";

?>
Show statistics from last <input id='no_of_weeks_to_show' type='text' size='4' value='<?php print $no_of_weeks_to_show; ?>'> weeks<br />
<input id='no_of_weeks_to_show_prev' type='hidden' value='<?php print $no_of_weeks_to_show; ?>'>
<br />

By default show:<br />
<?php
print "&nbsp;&nbsp;<input id='default_page_start_page' name='default_page' type='radio' $default_page_start_page>$STR_START_PAGE";
print "&nbsp;&nbsp;<input id='default_page_all_products' name='default_page' type='radio' $default_page_all_products>$STR_ALL_PRODUCTS";
?>
<br />
<br />
Table:<br />
&nbsp;&nbsp;Group in: <input id='table_day_view' name='table_view_type' type='radio' <?php print $table_day_view; ?>>days&nbsp;&nbsp;
<input id='table_week_view' name='table_view_type' type='radio' <?php print $table_week_view; ?>>weeks<br />
&nbsp;&nbsp;<input id='table_expanded' type='checkbox' <?php print $table_expanded; ?>>Expand inflow and outflow colums<br />
<br />

Graph:<br />
&nbsp;&nbsp;Group in: <input id='graph_day_view' name='graph_view_type' type='radio' <?php print $graph_day_view; ?>>days&nbsp;&nbsp;
<input id='graph_week_view' name='graph_view_type' type='radio' <?php print $graph_week_view; ?>>weeks<br /><br />

&nbsp;<input id='graph_size_a' name='graph_size_type' type='radio' <?php print $graph_size_auto; ?>>Auto resize graphs to available space<br />
&nbsp;<input id='graph_size_m' name='graph_size_type' type='radio' <?php print $graph_size_manual; ?>>Fixed size of graphs:
&nbsp;&nbsp;<input id='graph_size_w' type='text' size='4' value='<?php print $graph_size_width; ?>'>
&nbsp;x&nbsp;<input id='graph_size_h' type='text' size='4' value='<?php print $graph_size_height; ?>'><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_size_all_a' type='checkbox' <?php print $graph_autoresize_all_products_graphs; ?>>Also on <?php print $STR_GRAPHS_FOR_ALL_PRODUCTS; ?> page<br /><br />

&nbsp;&nbsp;Show following data on the graph: <br />
<?php
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_active' type='checkbox' $graph_bugs_active>&nbsp;$STR_ACTIVE<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_verifiable' type='checkbox' $graph_bugs_verifiable>&nbsp;$STR_UNVERIFIED<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_not_released' type='checkbox' $graph_bugs_not_released>&nbsp;$STR_UNRELEASED<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_open' type='checkbox' $graph_bugs_open>&nbsp;$STR_OPEN<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_unconfirmed' type='checkbox' $graph_bugs_unconfirmed>&nbsp;$STR_UNCONFIRMED<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_inflow' type='checkbox' $graph_bugs_inflow>&nbsp;$STR_INFLOW<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_outflow' type='checkbox' $graph_bugs_outflow>&nbsp;$STR_OUTFLOW<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_released' type='checkbox' $graph_bugs_released>&nbsp;$STR_RELEASED<br />";
print "&nbsp;&nbsp;&nbsp;&nbsp;<input id='graph_bugs_closed' type='checkbox' $graph_bugs_closed>&nbsp;$STR_CLOSED<br />";
?>

<br/>
&nbsp;&nbsp;
<button onclick='save_settings();'>Save settings</button>
&nbsp;&nbsp;&nbsp;&nbsp;<button onclick='show_hide_settings();'>Cancel</button>
&nbsp;&nbsp;<img id='loading_banner_s' src='img/loading.gif' alt='loading...' />
