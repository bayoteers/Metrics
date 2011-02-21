<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): save_settings.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

session_start();
require_once ("lib/functions.php");

save_param_int("no_of_weeks_to_show");
save_param_bool('default_page_all_products');
save_param_bool('table_week_view');
save_param_bool("table_expanded");
save_param_bool('graph_week_view');
save_param_bool('graph_size_manual');
save_param_int("graph_size_width");
save_param_int("graph_size_height");
save_param_bool('graph_autoresize_all_products_graphs');
save_param_bool('graph_no_bugs_active');
save_param_bool('graph_bugs_verifiable');
save_param_bool('graph_bugs_not_released');
save_param_bool('graph_no_bugs_open');
save_param_bool('graph_bugs_unconfirmed');
save_param_bool('graph_no_bugs_inflow');
save_param_bool('graph_no_bugs_outflow');
save_param_bool('graph_no_bugs_released');
save_param_bool('graph_bugs_closed');

?>
ok