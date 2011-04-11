<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): generate_graph.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

session_start();
require_once ("lib/jpgraph/jpgraph.php");
require_once ("lib/jpgraph/jpgraph_bar.php");
require_once ("lib/jpgraph/jpgraph_line.php");
require_once ("lib/functions.php");
require_once ("lib/static_variables.php");

$selected_statistic = $_GET["s"];
$selected_product = $_GET["p"];
$selected_component = $_GET["c"];
$graph_width = $_GET["w"];
$graph_height = $_GET["h"];
$graph_legend = false;
if ( isset ($_GET["l"]) && $_GET["l"] == "true") {
    $graph_legend = true;
}

$graph_day_view = !read_param_bool('graph_week_view');
$no_of_weeks_to_show = read_param_int("no_of_weeks_to_show", 3);
$graph_bugs_active = ! read_param_bool('graph_no_bugs_active');
$graph_bugs_verifiable = read_param_bool('graph_bugs_verifiable');
$graph_bugs_not_released = read_param_bool('graph_bugs_not_released');
$graph_bugs_open = ! read_param_bool('graph_no_bugs_open');
$graph_bugs_unconfirmed = read_param_bool('graph_bugs_unconfirmed');
$graph_bugs_inflow = ! read_param_bool('graph_no_bugs_inflow');
$graph_bugs_outflow = ! read_param_bool('graph_no_bugs_outflow');
$graph_bugs_released = ! read_param_bool('graph_no_bugs_released');
$graph_bugs_closed = read_param_bool('graph_bugs_closed');

// global variables' file for particular statistic 
if (file_exists("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME");
}
// local variables' file for particular project
if (file_exists("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME");
}

$stats = read_statistics($selected_statistic, $selected_product, $selected_component, $no_of_weeks_to_show, $graph_day_view);

// =======================================================
// Create the graph.
$bgcolor = '#eeeeee';
//$bgcolor = '#ffffff';

$graph = new Graph($graph_width, $graph_height);
$graph->SetScale("textlin");
$graph->SetMarginColor($bgcolor);

// Adjust the margin slightly so that we use the
// entire area (since we don't use a frame)
$graph->SetMargin(40,5,30, ($stats['long_desc_graph']?65:50));

// Box around plotarea
$graph->SetBox();

$graph->SetFrame(true, $bgcolor);
$graph->SetBox(true, '#68b3e3');

// Setup the tab title
$title = "    ";
if ($selected_component != '') {
    $title .= str_replace(array("_and_", "_"), array(" & ", " "), "'$selected_product' / '$selected_component' ");
} else if ($selected_product !== $ALL_PRODUCTS) {
    $title .= str_replace(array("_and_", "_"), array(" & ", " "), "'$selected_product' ");
}
$title .= "'" . str_replace(array("_and_", "_"), array(" & ", " "), $selected_statistic) . "' inflow / outflow graph   ";

$graph->tabtitle->Set($title);
$graph->tabtitle->SetFont($GRAPH_FONT_NAME,FS_BOLD,8);
$graph->tabtitle->SetColor('black',$bgcolor, '#68b3e3');

// Setup the X and Y grid
$graph->ygrid->SetFill(true,'#eeeeee@0.5','#dddddd@0.5');
$graph->ygrid->SetLineStyle('dashed');
$graph->ygrid->SetColor('gray');
$graph->xgrid->Show();
$graph->xgrid->SetLineStyle('dashed');
$graph->xgrid->SetColor('gray');

// Setup labels and titles on the X and Y-axis
$graph->xaxis->SetTickLabels( $stats['desc_graph'] );
$graph->xaxis->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->title->SetFont($GRAPH_FONT_NAME,FS_NORMAL,8);
$graph->xaxis->SetTitlemargin(40);
/*
 if ($graph_day_view) {
 $graph->xaxis->SetTitle("week, day", "middle");
 } else {
 $graph->xaxis->SetTitle("week", "middle");
 }
 */

$graph->yaxis->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
$graph->yaxis->title->SetFont($GRAPH_FONT_NAME,FS_NORMAL,8);
//$graph->yaxis->SetTitle("no. of bugs", "middle");

// legend
if ($graph_legend)
{
    $graph->legend->SetAbsPos(50,40,'left','top');
    $graph->legend->SetColumns(2);
    $graph->legend->SetLayout(LEGEND_VERT);
    //$graph->legend->SetLayout(LEGEND_HOR);
    $graph->legend->SetColor('black','#68b3e3');
    $graph->legend->SetFillColor('#eeeeee@0.75');
    $graph->legend->SetFrameWeight(1);
    $graph->legend->SetFont($GRAPH_FONT_NAME,FS_NORMAL,8);
}

$groupBarPlotArray = array();
//$groupLinePlotArray = array ();

// =======================================================

if ($graph_bugs_inflow) {
    $barPlotInflow = new BarPlot($stats['inflow']);
    $barPlotInflow->SetWidth(0.3);
    $barPlotInflow->SetAlign("left");
    $fcol='#440000@0.25';
    $tcol='#FF9090@0.25';
    $barPlotInflow->SetFillGradient($fcol,$tcol,GRAD_LEFT_REFLECTION);
    $barPlotInflow->SetWeight(0);
    $barPlotInflow->value->Show();
    $barPlotInflow->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$barPlotInflow->value->SetAngle(45);
    $barPlotInflow->value->SetColor('#440000');
    $barPlotInflow->value->SetFormat('%1d');
    $barPlotInflow->value->HideZero(true);
    if ($graph_legend) $barPlotInflow->SetLegend($STR_INFLOW);
    $groupBarPlotArray[] = $barPlotInflow;
}
if ($graph_bugs_outflow) {
    $barPlotOutflow = new BarPlot($stats['outflow']);
    $barPlotOutflow->SetWidth(0.3);
    $barPlotOutflow->SetAlign("center");
    $fcol='#004400@0.25';
    $tcol='#90FF90@0.25';
    $barPlotOutflow->SetFillGradient($fcol,$tcol,GRAD_LEFT_REFLECTION);
    $barPlotOutflow->SetWeight(0);
    $barPlotOutflow->value->Show();
    $barPlotOutflow->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$barPlotInflow->value->SetAngle(45);
    $barPlotOutflow->value->SetColor('#004400');
    $barPlotOutflow->value->SetFormat('%1d');
    $barPlotOutflow->value->HideZero(true);
    if ($graph_legend) $barPlotOutflow->SetLegend($STR_OUTFLOW);
    $groupBarPlotArray[] = $barPlotOutflow;
}
if ($graph_bugs_released) {
    $barPlotReleased = new BarPlot($stats['released']);
    $barPlotReleased->SetWidth(0.3);
    $barPlotReleased->SetAlign("right");
    $fcol='#000044@0.25';
    $tcol='#9090FF@0.25';
    $barPlotReleased->SetFillGradient($fcol,$tcol,GRAD_LEFT_REFLECTION);
    $barPlotReleased->SetWeight(0);
    $barPlotReleased->value->Show();
    $barPlotReleased->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$barPlotReleased->value->SetAngle(45);
    $barPlotReleased->value->SetColor('#000044');
    $barPlotReleased->value->SetFormat('%1d');
    $barPlotReleased->value->HideZero(true);
    if ($graph_legend) $barPlotReleased->SetLegend($STR_RELEASED);
    $groupBarPlotArray[] = $barPlotReleased;
}

if ($graph_bugs_closed) {
    $barPlotClosed = new BarPlot($stats['closed']);
    $barPlotClosed->SetWidth(0.3);
    $barPlotClosed->SetAlign("right");
    $fcol='#d4c800@0.25';
    $tcol='#f3eb66@0.25';
    $barPlotClosed->SetFillGradient($fcol,$tcol,GRAD_LEFT_REFLECTION);
    $barPlotClosed->SetWeight(0);
    $barPlotClosed->value->Show();
    $barPlotClosed->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$barPlotReleased->value->SetAngle(45);
    $barPlotClosed->value->SetColor('#6E6E36');
    $barPlotClosed->value->SetFormat('%1d');
    $barPlotClosed->value->HideZero(true);
    if ($graph_legend) $barPlotClosed->SetLegend($STR_CLOSED);
    $groupBarPlotArray[] = $barPlotClosed;
}

if ( count ($groupBarPlotArray) > 0 ) {
    $groupBarPlot = new GroupBarPlot($groupBarPlotArray);
    $graph->Add($groupBarPlot);
}

if ($graph_bugs_active) {
    $linePlotActive = new LinePlot($stats['active']);
    $linePlotActive->SetColor('blue');
    //$linePlotActive->SetFillColor('blue@0.9');
    $linePlotActive->SetWeight(2);
    $linePlotActive->SetBarCenter();
    $linePlotActive->mark->SetType(MARK_DIAMOND);
    $linePlotActive->mark->SetColor('blue@0.1');
    $linePlotActive->mark->SetFillColor('blue@0.1');
    $linePlotActive->mark->SetSize(8);
    $linePlotActive->value->Show();
    $linePlotActive->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$linePlotActive->value->SetAngle(45);
    $linePlotActive->value->SetColor('blue');
    $linePlotActive->value->SetFormat('%1d');
    $linePlotActive->value->HideZero(true);
    if ($graph_legend) $linePlotActive->SetLegend($STR_ACTIVE);
    $graph->Add($linePlotActive);
    //$groupLinePlotArray[] = $linePlotActive;
}

if ($graph_bugs_verifiable) {
    $linePlotActive = new LinePlot($stats['verifiable_graph']);
    $linePlotActive->SetColor('darkgreen');
    //$linePlotActive->SetFillColor('blue@0.9');
    $linePlotActive->SetWeight(2);
    $linePlotActive->SetBarCenter();
    $linePlotActive->mark->SetType(MARK_DIAMOND);
    $linePlotActive->mark->SetColor('darkgreen@0.1');
    $linePlotActive->mark->SetFillColor('darkgreen@0.1');
    $linePlotActive->mark->SetSize(8);
    $linePlotActive->value->Show(false);
    //$linePlotActive->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    // //$linePlotActive->value->SetAngle(45);
    //$linePlotActive->value->SetColor('darkgreen');
    //$linePlotActive->value->SetFormat('%1d');
    //$linePlotActive->value->HideZero(true);
    if ($graph_legend) $linePlotActive->SetLegend($STR_UNVERIFIED);
    $graph->Add($linePlotActive);
    //$groupLinePlotArray[] = $linePlotActive;
}

if ($graph_bugs_not_released) {
    $linePlotNotReleased = new LinePlot($stats['not_released_graph']);
    $linePlotNotReleased->SetColor('#d200ff');
    //$linePlotNotReleased->SetFillColor('pink@0.9');
    $linePlotNotReleased->SetBarCenter();
    $linePlotNotReleased->SetWeight(2);
    $linePlotNotReleased->mark->SetType(MARK_UTRIANGLE);
    $linePlotNotReleased->mark->SetColor('#d200ff@0.1');
    $linePlotNotReleased->mark->SetFillColor('#d200ff@0.1');
    $linePlotNotReleased->mark->SetSize(6);
    //$linePlotNotReleased->value->Show(false);
    //$linePlotNotReleased->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    // //$linePlotNotReleased->value->SetAngle(45);
    //$linePlotNotReleased->value->SetColor('#d200ff');
    //$linePlotNotReleased->value->SetFormat('%1d');
    //$linePlotNotReleased->value->HideZero(true);
    if ($graph_legend) $linePlotNotReleased->SetLegend($STR_UNRELEASED);
    $graph->Add($linePlotNotReleased);
    //$groupLinePlotArray[] = $linePlotNotReleased;
}

if ($graph_bugs_open) {
    $linePlotOpen = new LinePlot($stats['open']);
    $linePlotOpen->SetColor('red');
    //$linePlotOpen->SetFillColor('red@0.9');
    $linePlotOpen->SetBarCenter();
    $linePlotOpen->SetWeight(2);
    $linePlotOpen->mark->SetType(MARK_UTRIANGLE);
    $linePlotOpen->mark->SetColor('red@0.1');
    $linePlotOpen->mark->SetFillColor('red@0.1');
    $linePlotOpen->mark->SetSize(6);
    $linePlotOpen->value->Show();
    $linePlotOpen->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$linePlotOpen->value->SetAngle(45);
    $linePlotOpen->value->SetColor('red');
    $linePlotOpen->value->SetFormat('%1d');
    $linePlotOpen->value->HideZero(true);
    if ($graph_legend) $linePlotOpen->SetLegend($STR_OPEN);
    $graph->Add($linePlotOpen);
    //$groupLinePlotArray[] = $linePlotOpen;
}

if ($graph_bugs_unconfirmed) {
    $linePlotUnconfirmed = new LinePlot($stats['unconfirmed']);
    $linePlotUnconfirmed->SetColor('black');
    //$linePlotUnconfirmed->SetFillColor('black@0.9');
    $linePlotUnconfirmed->SetWeight(2);
    $linePlotUnconfirmed->SetBarCenter();
    $linePlotUnconfirmed->mark->SetType(MARK_DIAMOND);
    $linePlotUnconfirmed->mark->SetColor('black@0.1');
    $linePlotUnconfirmed->mark->SetFillColor('black@0.1');
    $linePlotUnconfirmed->mark->SetSize(8);
    $linePlotUnconfirmed->value->Show();
    $linePlotUnconfirmed->value->SetFont($GRAPH_FONT_NAME,FS_NORMAL,7);
    //$linePlotUnconfirmed->value->SetAngle(45);
    $linePlotUnconfirmed->value->SetColor('black');
    $linePlotUnconfirmed->value->SetFormat('%1d');
    $linePlotUnconfirmed->value->HideZero(true);
    if ($graph_legend) $linePlotUnconfirmed->SetLegend($STR_UNCONFIRMED);
    $graph->Add($linePlotUnconfirmed);
    //$groupLinePlotArray[] = $linePlotUnconfirmed;
}

//$accLinePlot = new AccLinePlot($groupLinePlotArray);
//$graph->Add($accLinePlot);
//$graph->Add($linePlotClosed);


// .. and finally send it back to the browser
$graph->Stroke();

?>
