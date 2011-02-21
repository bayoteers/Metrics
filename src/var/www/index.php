<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): index.php
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

session_start();
require "lib/functions.php";
require "lib/static_variables.php";

// read components list

// selected statistics - full name
$selected_statistic = '';
// selected statistics - group
$ss_g = '';
// selected statistics - sub-group
$ss_sg = '[all]';
// selected statistics - sub-sub-group
$ss_ssg = '[all]';

$default_page_all_products = read_param_bool("default_page_all_products");

$selected_product = $START_PAGE;
if ($default_page_all_products) {
    $selected_product = $ALL_PRODUCTS;
}

$selected_component = '';

if (isset($_GET['s']) && $_GET['s'] != '') {
    $selected_statistic = $_GET['s'];
}
// TEMPORARY
else if (isset($_GET['stats']) && $_GET['stats'] != '') {
    $selected_statistic = $_GET['stats'];
}

if (isset($_GET['p']) && $_GET['p'] != '') {
    $selected_product = $_GET['p'];
}
// TEMPORARY
else if (isset($_GET['component']) && $_GET['component'] != '') {
    $selected_product = $_GET['component'];
}

if (isset($_GET['c']) && $_GET['c'] != '') {
    $selected_component = $_GET['c'];
}
// TEMPORARY
else if (isset($_GET['subgroup']) && $_GET['subgroup'] != '') {
    $selected_component = $_GET['subgroup'];
} 

// $statistics_list: list of sub-foldes in 'data' folder
$statistics_list = get_folders_list($DATA_FOLDER, array());
// $statistics_groups: statistics divided to groups, sub-groups and sub-sub-groups
$statistics_groups = array ();
// list of products belonging to choosen statistic
$products_list = array ();
$products_components = array ();

// divide $statistics_list to $statistics_groups
foreach ($statistics_list as $stat) {
    $g = $stat;
    $sg = "[all]";
    $ssg = "[all]";
    if (strpos($stat, "_-_") !== false) {
        $e = explode("_-_", $stat);
        $g = $e[0];
        $sg = $e[1];
        if (array_key_exists(2, $e)) {
            $ssg = $e[2];
        }
    }
    $statistics_groups[$g][$sg][] = $ssg;
}

// divide $selected_statistic to group and sub-groups
if ($selected_statistic == '' || $selected_statistic == '-') {
    $selected_statistic = '';
} else {


    $e = explode("_-_", $selected_statistic);
    $ss_g = $e[0];
    if (array_key_exists(1, $e)) {
        $ss_sg = $e[1];
        if (array_key_exists(2, $e)) {
            $ss_ssg = $e[2];
        }
    }

    // TODO
    if ( ! array_key_exists($ss_g, $statistics_groups) )
    {
        // GROUP does not exist -> reload the page to show the list of available statistics
        
        header("Location: ?s=");
        exit("Error: user requested not existing statistics - reload the page to show the list of available statistics"); 
    }

    // GROUP exists -> check sub-group
    $selected_statistic = $ss_g;
    
    if ( ! array_key_exists($ss_sg, $statistics_groups[$ss_g]) )
    {
        // SUB-GROUP does not exist -> take the fist sub-group and sub-sub-group from the list
        $w = array_keys($statistics_groups[$ss_g]);
        $ss_sg = $w[0];
        $ss_ssg = $statistics_groups[$ss_g][$ss_sg][0];
        if ($ss_sg != "[all]") {
            $selected_statistic .= "_-_" . $ss_sg;
            if ($ss_ssg != "[all]") {
                $selected_statistic .= "_-_" . $ss_ssg;
            }
        }
    }
    else
    {
        // SUB-GROUP exists -> check sub-sub-group
        if ($ss_sg != "[all]") {
            $selected_statistic .= "_-_" . $ss_sg;
        }

        if ( ! in_array ($ss_ssg, $statistics_groups[$ss_g][$ss_sg]) )
        {
            // SUB-SUB-GROUP does not exist -> take the fist sub-sub-group from the list
            $ss_ssg = $statistics_groups[$ss_g][$ss_sg][0];
            if ($ss_ssg != "[all]") {
                $selected_statistic .= "_-_" . $ss_ssg;
            }
        }
        else
        {
            // SUB-SUB-GROUP exists
            if ($ss_ssg != "[all]") {
                $selected_statistic .= "_-_" . $ss_ssg;
            }
        }
    }
    
    // read products list
    $products_list = get_folders_list("$DATA_FOLDER/$selected_statistic", array($ALL_PRODUCTS));
    
    if ($selected_product != '' && $selected_product != $START_PAGE && $selected_product != $GRAPHS_FOR_ALL_PRODUCTS && $selected_product != $ALL_PRODUCTS ) {
        if ( ! in_array($selected_product, $products_list) )
        {
            // selected product does not exist -> reload the page to show the 'all products' of selected statistics
            header("Location: ?s=" . $selected_statistic);
            exit("Error: user requested not existing product - reload the page to show the 'all products' of selected statistics"); 
        }
        
        $products_components = get_products_components("$DATA_FOLDER/$selected_statistic/$selected_product");
        if ( $selected_component != "") {
            if ( ! in_array($selected_component, $products_components) )
            {
                // selected component does not exist -> reload the page to show the product stats of selected statistics
                header("Location: ?s=" . $selected_statistic . "&p=" . $selected_product);
                exit("Error: user requested not existing component - reload the page to show the product stats of selected statistics"); 
            }
        }
    }
}

$title = "BAM";
if ($selected_statistic != '' && $selected_statistic != '-') {
	$title .= " (" . str_replace(array("_", "and"), array(" ", "&"), $selected_statistic);
	if ($selected_product != "") {
		$title .= " / " . ($selected_product===$ALL_PRODUCTS ? $STR_ALL_PRODUCTS : str_replace(array("_", "and"), array(" ", "&"), $selected_product));
		if ($selected_component != "") {
			$title .= " / " . str_replace(array("_", "and"), array(" ", "&"), $selected_component);
		}
	}
	$title .= ")";
}

// global variables' file for particular statistic 
if (file_exists("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$VARIABLES_FILE_NAME");
}
// local variables' file for particular project
if (file_exists("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME") ) {
    include_once("$DATA_FOLDER/$selected_statistic/$selected_product/$VARIABLES_FILE_NAME");
}

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='pl' lang='pl'>
    <head>
        <title><?php print $title; ?></title>
        <meta http-equiv='Content-Type' content='application/xhtml+xml; charset=utf-8' />
        <link rel='stylesheet' type='text/css' href='lib/styles.css' />
        <script type='text/javascript' src='lib/jquery/jquery.js'></script>

        <link rel='stylesheet' type='text/css' href='lib/tipsy/tipsy.css' />
        <script type='text/javascript' src='lib/tipsy/jquery.tipsy.js'></script>

        <link rel='stylesheet' type='text/css' href='lib/superfish/superfish.css' />
        <script type='text/javascript' src='lib/superfish/superfish.js'></script>
        
        <script type='text/javascript' src='lib/scripts.js'></script>
        
        <script type="text/javascript">
            window.onresize=resize_elements;
        </script>
        
        <meta name='author' content='grzegorz szura' />
        <meta name='copyright' content='(c)2009 nokia' />
    </head>

    <body>
        <div id='content'>
            
            <div id='header'>
                <div class='header_element'>
                <span class='header_span'>Statistic:</span>
                </div>
                <div class='header_element'>
                
                    <ul class="sf-menu"> <!-- menu -->
                        <li>
                            
                            <a href="#" class='main'><?php print str_replace(array("_-_", "_", "and"), array(" / ", " ", "&"), ($selected_statistic!="" ? $selected_statistic : "-- select statistics --") ); ?></a>
                            <ul> <!-- menu list -->
                                <li><a href='?s='>-- list of collected statistics --</a></li>
                                <?php
                                foreach ($statistics_groups as $group_name=>$group_content) {
                                    print "<li><a href='?s=$group_name&p=$selected_product&c=$selected_component'>" . str_replace(array("_", "and"), array(" ", "&"), $group_name) . "</a>\n";
                                    
                                        if (count($group_content) > 1 || !array_key_exists("[all]", $group_content) ) {
                                            print "<ul>";
                                            foreach ($group_content as $subgroup_name=>$subgroup_content) {
                                                if ($subgroup_name != "[all]") {
                                                    print "<li><a href='?s=$group_name"."_-_$subgroup_name&p=$selected_product&c=$selected_component'>" . str_replace(array("_", "and"), array(" ", "&"), $subgroup_name) . "</a>\n";
                                                    
                                                    if (count($subgroup_content) > 1 || !in_array("[all]", $subgroup_content) ) {
                                                        print "<ul>\n";
                                                            foreach ($subgroup_content as $subsubgroup_name) {
                                                                if ($subsubgroup_name != "[all]") {
                                                                    print "<li><a href='?s=$group_name"."_-_$subgroup_name"."_-_$subsubgroup_name&p=$selected_product&c=$selected_component'>" . str_replace(array("_", "and"), array(" ", "&"), $subsubgroup_name) . "</a></li>\n";
                                                                }
                                                            }
                                                        print "</ul>\n";
                                                    }
                                                    print "</li>\n";
                                                }
                                                
                                            }
                                            print "</ul>\n";
                                        }
                
                                    print "</li>\n";
                                }
                                ?>
                            </ul> <!-- menu list -->
                            
                        </li>
                    </ul> <!-- menu -->
                </div>
                <input type='hidden' id='selected_statistic' value='<?php print $selected_statistic; ?>' />
                
                
                <?php if ($selected_statistic!="") { ?> 
                <div class='header_space'>&nbsp;</div>
                <div class='header_element'>
                <span class='header_span'><?php print $STR_PRODUCT; ?>:</span>
                </div>
                <div class='header_element'>
                
                    <ul class="sf-menu"> <!-- menu -->
                        <li>

                            <?php
                            $create_component_menu = false;
                            $selected_product_name = "----";
                            if ($selected_product == $START_PAGE) {
                                $selected_product_name = $STR_START_PAGE;
                            } else if ($selected_product == $GRAPHS_FOR_ALL_PRODUCTS) {
                                $selected_product_name = $STR_GRAPHS_FOR_ALL_PRODUCTS;
                            } else if ($selected_product == $ALL_PRODUCTS) {
                                $selected_product_name = $STR_ALL_PRODUCTS;
                            } else if ($selected_component != '') {
                                $selected_product_name = str_replace(array("_", "and"), array(" ", "&"), "$selected_product / $selected_component");
                                if (count($products_list) > 15) {
                                    $create_component_menu = true;
                                }
                            } else {
                                $selected_product_name = str_replace(array("_", "and"), array(" ", "&"), $selected_product);
                                if (count($products_list) > 15) {
                                    $create_component_menu = true;
                                }
                            }
                            print "<a href='#' class='main'>$selected_product_name</a>\n";
                            
                            print "<ul>\n";
                                if ($create_component_menu) {
                                    if ( count($products_components) > 0 ) {
                                        print "<li><a href='?s=$selected_statistic&p=$selected_product'>&nbsp;&nbsp;&nbsp;" . str_replace(array("_", "and"), array(" ", "&"), $selected_product) . "</a>\n";
                                        print "<ul>\n";
                                        foreach ($products_components as $component) {
                                            print "<li><a href='?s=$selected_statistic&p=$selected_product&c=$component'>&nbsp;&nbsp;&nbsp;" . str_replace(array("_", "and"), array(" ", "&"), $component) . "</a></li>\n";
                                        }
                                        print "</ul>\n";
                                    }
                                    print "</li>\n";
                                    print "<li><a href='#'>--</a></li>\n";
                                }
                                print "<li><a href='?s=$selected_statistic&p=$START_PAGE'>$STR_START_PAGE</a></li>\n";
                                print "<li><a href='?s=$selected_statistic&p=$GRAPHS_FOR_ALL_PRODUCTS'>$STR_GRAPHS_FOR_ALL_PRODUCTS</a></li>\n";
                                print "<li><a href='?s=$selected_statistic&p=$ALL_PRODUCTS'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$STR_ALL_PRODUCTS</a></li>\n";
                                
                                if (count($products_list) < 16) {
                                    foreach ($products_list as $product) {
                                        print "<li><a href='?s=$selected_statistic&p=$product'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . str_replace(array("_", "and"), array(" ", "&"), $product) . "</a>\n";
                                        $products_components = get_products_components("$DATA_FOLDER/$selected_statistic/$product");
                                        if ( count($products_components) > 0 ) {
                                            print "<ul>\n";
                                            foreach ($products_components as $component) {
                                                print "<li><a href='?s=$selected_statistic&p=$product&c=$component'>&nbsp;&nbsp;&nbsp;" . str_replace(array("_", "and"), array(" ", "&"), $component) . "</a></li>\n";
                                            }
                                            print "</ul>\n";
                                        }
                                        print "</li>\n";
                                    }
                                } else {
                                    for ($c=0; $c<count($products_list); $c+=15) {
                                        $last = ($c+15 > count($products_list) ? count($products_list)-$c : 15);
                                        print "<li><a href='#'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . substr(str_replace(array("_", "and"), array(" ", "&"), $products_list[$c]),0,6) . "..&nbsp;&nbsp;&nbsp;-&gt;&nbsp;&nbsp;&nbsp;" . substr(str_replace(array("_", "and"), array(" ", "&"), $products_list[$c+$last-1]),0,6) . "..</a>\n";

                                        print "<ul>\n";
                                        for ($c1=0; $c1<$last; $c1++) {
                                            $product = $products_list[$c+$c1];
                                            print "<li><a href='?s=$selected_statistic&p=$product'>&nbsp;&nbsp;&nbsp;" . str_replace(array("_", "and"), array(" ", "&"), $product) . "</a>\n";
                                            $products_components = get_products_components("$DATA_FOLDER/$selected_statistic/$product");
                                            if ( count($products_components) > 0 ) {
                                                print "<ul>\n";
                                                foreach ($products_components as $component) {
                                                    print "<li><a href='?s=$selected_statistic&p=$product&c=$component'>&nbsp;&nbsp;&nbsp;" . str_replace(array("_", "and"), array(" ", "&"), $component) . "</a></li>\n";
                                                }
                                                print "</ul>\n";
                                            }
                                            print "</li>\n";
                                        }
                                        print "</ul>\n";
                                        print "</li>\n";
                                        
                                    }
                                }
                                ?>
                            </ul> <!-- menu list -->
                            
                        </li>
                    </ul> <!-- menu -->
                    <input type='hidden' id='selected_product' value='<?php print $selected_product; ?>' />
                    <input type='hidden' id='selected_component' value='<?php print $selected_component; ?>' />

                    <input type='hidden' id='name_start_page' value='<?php print $START_PAGE; ?>' />
                    <input type='hidden' id='name_all_products' value='<?php print $ALL_PRODUCTS; ?>' />
                    <input type='hidden' id='name_graphs_all_products' value='<?php print $GRAPHS_FOR_ALL_PRODUCTS; ?>' />
                
                </div>
                <?php } ?>
                <div class='header_space'>&nbsp;</div>
                <div class='header_element'>
					<!-- <div class='header_element'><span class='header_warning'>Note: because of projects.maemo.org migration data are not up-to-date</span></div> -->
                    <img id='loading_banner' src='img/loading.gif' alt='loading...' />
                </div>
            </div>
            
            <div id='stats'>
                <?php
                if ($selected_statistic == '') {
                    require "generate_list_of_available_statistics.php";
                } else if ($selected_product == $START_PAGE) {
                    require "generate_start_page.php";
                } else if ($selected_product == $GRAPHS_FOR_ALL_PRODUCTS) {
                    require "generate_graphs_for_all_products.php";
                } else {
                    require "generate_statistics.php";
                }
                ?>
            </div>
            
            <div id='details'></div>

        </div>
        <div id='settings_header' title='Change the way, how the data are presented on the page'>Settings</div>
        <div id='settings'>
            <?php require "show_settings.php"; ?>
        </div>
        <br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
        <br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />


    </body>
</html>

