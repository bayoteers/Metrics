<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): static_variables.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

require_once ("lib/jpgraph/jpgraph_ttf.inc.php");

// ======================
// Folder when data file are collected - it must be in sync with configuration of fetching data script. 
$DATA_FOLDER = "data";

// ======================
// Font used on graphs
// Names of available fonts can be found in file lib/jpgraph/jpgraph_ttf.inc.php
//$GRAPH_FONT_NAME = FF_DV_SANSSERIF;
$GRAPH_FONT_NAME = FF_DV_SANSSERIFCOND;
//$GRAPH_FONT_NAME = FF_VERA;

// ======================
// 'Static' variables
// DO NOT change this data unless you know what you're doing - they must be in sync with hardcoded variables in fetching data script.
$DAILY_STATS_HISTORY_FILE_NAME = "daily_stats";
$WEEKLY_STATS_HISTORY_FILE_NAME = "weekly_stats";
$SUBGROUPS_FILE_NAME = "subgroups";
// there are two variables files - global one for all projects in statistics (in root forder for statistic), and local for partucular project (in project file)
// Both have the same name but different location.
// Script first read data from this file, 'static_variables.php', next from global variables file, and next from local variables file.
$VARIABLES_FILE_NAME = "variables.php";
$START_PAGE = "start_page";
$ALL_PRODUCTS = "all";
$GRAPHS_FOR_ALL_PRODUCTS = "graphs";


// ======================
// STRINGS VISIBLE ON THE PAGE - THEY MUST BE UPDATED TO DEFAULT ONES!!
// Each string however can be overriden in '$VARIABLES_FILE_NAME' (through the config file).

$STR_START_PAGE = "!! [start page]";
$STR_ALL_PRODUCTS = "!! [all products - summary]";
$STR_GRAPHS_FOR_ALL_PRODUCTS = "!! [graphs for all products]";

$STR_PRODUCT = "!! product";
$STR_COMPONENT = "!! component";

// Field names of the x, y, and z axis in Bugzilla report.
// Change these parameters only when you generate different statistic than typical: products in classification.
// 
// For example you can create statistic for partucular product, then your products will be in real components in the product - in such case you have to define:
// $STR_ALL_PRODUCTS = "[all components - summary]";
// $STR_GRAPHS_FOR_ALL_PRODUCTS = "[graphs for all components]";
// $STR_PRODUCT "components";
// $STR_COMPONENT = "component";
// $STR_CLASSIFICATION_COLUMN_NAME = "product";
// $STR_PRODUCT_COLUMN_NAME = "component";
// $STR_COMPONENT_COLUMN_NAME = "component";
// 
// 
$STR_CLASSIFICATION_COLUMN_NAME = "!! classification";
$STR_PRODUCT_COLUMN_NAME = "!! product";
$STR_COMPONENT_COLUMN_NAME = "!! component";

$STR_ACTIVE = "!! active";
$STR_UNVERIFIED = "!! unverified";
$STR_UNRELEASED = "!! unreleased";
$STR_OPEN = "!! open";
$STR_UNCONFIRMED = "!! unconfirmed";
$STR_INFLOW = "!! inflow";
$STR_NEW = "!! new";
$STR_REOPENED = "!! reopened";
$STR_OUTFLOW = "!! outflow";
$STR_RESOLVED = "!! resolved";
$STR_MOVED_OUT = "!! moved out";
$STR_RELEASED = "!! released";
$STR_CLOSED = "!! closed";

$STR_ACTIVE_DESC = "!! Active bugs (not closed)";
$STR_UNVERIFIED_DESC = "!! Bugs which can be verified immediately";
$STR_UNRELEASED_DESC = "!! Bugs fixed but not released yet";
$STR_OPEN_DESC = "!! Bugs not resolved yet";
$STR_UNCONFIRMED_DESC = "!! Bugs not confirmed yet as a valid bugs";
$STR_NEW_DESC = "!! New bugs (newly reported or others matching the search rule, e.g. moved from another project or with specific keyword added, severity changed, etc.)";
$STR_REOPENED_DESC = "!! Bugs reopened";
$STR_RESOLVED_DESC = "!! Bugs resolved";
$STR_MOVED_OUT_DESC = "!! Bugs moved out (which don't match the search rule anymore, e.g. moved to another project or with specific keyword removed, severity changed, etc.)";
$STR_RELEASED_DESC = "!! Bugs released";
$STR_CLOSED_DESC = "!! Bugs closed";




?>
