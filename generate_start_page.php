<?php
/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): generate_start_page.php
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
<div id='stats_graphs'>
    <div id='stats_graph_div_0' class='stats_graph3'>
        <a href='<?php print "?s=$selected_statistic&p=$ALL_PRODUCTS"; ?>'>
            <img id='stats_graph_img_0' src='img/loading.gif' alt='' title='Click on the graph to see details'/>
        </a>
        <input id='stats_graph_0' type='hidden' value='<?php print $ALL_PRODUCTS; ?>' alt='' />
    </div>
</div>
