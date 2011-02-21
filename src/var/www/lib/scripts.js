/* ==========================================================================
 * BAM (Bugzilla Automated Metrics): scripts.js
 *
 * Copyright 2011, Nokia Oy
 * Maintainer: Grzegorz Szura <ext-grzegorz.szura@nokia.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Feb  3 14:21:00 EET 2011
 * ==========================================================================
*/

/*************** VARIABLES ***************/

var IE = (document.all),
    prev_row_id = null;

plus = new Image(9, 9);
plus.src = "img/plus.gif";
plus_hover = new Image(9, 9);
plus_hover.src = "img/plus_hover.gif";

minus = new Image(9, 9);
minus.src = "img/minus.gif";
minus_hover = new Image(9, 9);
minus_hover.src = "img/minus_hover.gif";

function parse_img_name(id) {
    img_name = document.getElementById(id).src;
    img_name = img_name.slice(img_name.lastIndexOf("/")+1);
    img_name = img_name.slice(0, img_name.indexOf("."));
    if (img_name.indexOf("_") > -1) {
        img_name = img_name.slice(0, img_name.indexOf("_"));
    }
    return img_name;
}

function image_default(id) {
    image = eval(parse_img_name(id) + ".src");
    document.getElementById(id).src = image;
}

function image_hover(id) {
    image = eval(parse_img_name(id) + "_hover.src");
    document.getElementById(id).src = image;
}

function image_change(id, img_name){
    image = eval(img_name + ".src");
    document.getElementById(id).src = image;
}

/*************** JQUERY FUNCTIONS ***************/

//$(document).ready(function(){
//});

$(function() {
    $('tr[title], td[title], a[title]').tipsy({gravity: 'w', fade: true, html: true});
    $('select[title]').tipsy({gravity: 'n', fade: true, html: true});
    $('img[title], th[title]').tipsy({gravity: 's', fade: true, html: true});
    $('#settings_header').tipsy({gravity: 'e', fade: true, html: true});

    $("#settings_header").click(function(){
        show_hide_settings();
    });

    $('#no_of_weeks_to_show, #graph_size_w, #graph_size_h').keypress(function(e){
        if (e.which == 13) {
            save_settings();
        }
    });

    $('ul.sf-menu').superfish({ 
        animation  : {opacity:'show', height: 'show'},
        hoverClass : 'sfHover',
        delay      : 500,
        pathLevels : 0
    }).find('ul');
    

    $('td.td_btn').click(function(){
        var t = this.id.replace('th_', '#tc_'),
            i = this.id.replace('th_', 'plusminus_');
        
        if ( $(t).css('display') == 'none') {
            if (IE) {
                $("div.table_div_content:visible").hide();
                $("div.table_div_content:visible").each(function(){
                    var i = this.id.replace('tc_', 'plusminus_');
                    image_change(i, 'plus');
                });
            } else {
                $("div.table_div_content:visible").animate({
                    height: 'hide'
                }, 'slow', function(){
                    var i = this.id.replace('tc_', 'plusminus_');
                    image_change(i, 'plus');
                });
            }
            image_change(i, 'minus');
            if (IE) {
                $(t).show();
            }
            else {
                $(t).animate({
                    height: 'show'
                }, 'slow');
            }
        } else {
            image_change(i, 'plus');
            if (IE) {
                $(t).hide();
            }
            else {
                $(t).animate({
                    height: 'hide'
                }, 'slow');
            }
        }
    });

});


/*************** x ***************/
function gEl(id){
    return document.getElementById(id);
}

function trim(string){
    return string.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}

function showEl(requestor){
    //gEl(requestor).style.display = 'inline';
    gEl(requestor).style.visibility = 'visible';
}

function hideEl(requestor){
    //gEl(requestor).style.display = 'none';
    gEl(requestor).style.visibility = 'hidden';
}

function clearDiv(requestor){
    gEl(requestor).innerHTML = "";
}


/*************** AJAX INIT ***************/

var request;
request = getXMLHttpRequest();

function getXMLHttpRequest(){
    var request = false;
    
    try {
        // Firefox 2, Opera 9, IE 7
        request = new XMLHttpRequest();
    } 
    catch (err1) {
        try {
            // IE 6
            request = new ActiveXObject('Msxml2.XMLHTTP');
        } 
        catch (err2) {
            try {
                // IE 5
                request = new ActiveXObject('Microsoft.XMLHTTP');
            } 
            catch (err3) {
                request = null;
            }
        }
    }
    return request;
}

/*****************************/
$(document).ready(function(){
    resize_elements();
    
    if (gEl('selected_product') == null || $('#selected_product').val() == '' || $('#selected_product').val() == '-') {
        hideEl('loading_banner');
    }
    else 
        if ($('#selected_product').val() === $('#name_start_page').val()) {
            // start page - generate summary graph for all products only 
            generate_start_page_graph();
        }
        else 
            if ($('#selected_product').val() === $('#name_graphs_all_products').val()) {
                // generate separate graphs for each product - Note: it's heavy 
                generate_graphs_for_all_products_response();
            }
            else {
                prev_row_id = null;
                generate_statistics_graph();
                show_changes_details(gEl('last_row').value, gEl('last_details_file').value, gEl('last_desc_table').value, gEl('last_snapshot_taken_time').value);
            }
});

function resize_elements() {
    // place the settings DIVs in the top-right corner of the screen 
    var windowWidth = (IE) ? document.body.clientWidth : window.innerWidth;
    $("#settings_header").css({'top': 10, 'left': (windowWidth - 120)});
    $("#settings").css({'top': 24 + $("#settings_header").height(), 'left': (windowWidth - (IE?320:300))});
    $("#settings").width( (IE?280:260) );
    //$("#settings").height( (IE?520:490) );
}

function change_product() {
    var new_statistics = $('#selected_statistic').val();
    var new_product = $('#selected_product').val();
    var new_component = $('#selected_component').val();
    if (new_statistics == '') {
                new_product = '';
                hideEl('loading_banner');
        }
    location.href = "?s=" + new_statistics + "&p=" + new_product + "&c=" + new_component;
    
}

function show_hide_settings(){
    //$("#settings").show();
    if ($('#settings').css('display') == 'none' ) {
        $("#settings").animate({height: 'show', opacity:'show'}, 'slow');
        $("#loading_banner_s, div.tipsy").hide();
        $('#no_of_weeks_to_show').focus();
    } else {
        $("#settings").animate({height: 'hide', opacity:'hide'}, 'slow');
    }
}

function save_settings() {
    var params = "";
    $("#loading_banner_s").show();
    
    params = params + "?no_of_weeks_to_show=" + gEl("no_of_weeks_to_show").value;
    params = params + "&default_page_all_products=" + (gEl("default_page_all_products").checked ? "true" : "false");
    params = params + "&table_week_view=" + (gEl("table_week_view").checked ? "true" : "false");
    params = params + "&table_expanded=" + (gEl("table_expanded").checked ? "true" : "false");
    params = params + "&graph_week_view=" + (gEl("graph_week_view").checked ? "true" : "false");
    params = params + "&graph_size_manual=" + (gEl("graph_size_m").checked ? "true" : "false");
    params = params + "&graph_size_width=" + gEl("graph_size_w").value;
    params = params + "&graph_size_height=" + gEl("graph_size_h").value;
    params = params + "&graph_autoresize_all_products_graphs=" + (gEl("graph_size_all_a").checked ? "true" : "false");
    params = params + "&graph_no_bugs_active=" + (gEl("graph_bugs_active").checked ? "false" : "true" );
    params = params + "&graph_bugs_verifiable=" + (gEl("graph_bugs_verifiable").checked ? "true" : "false" );
    params = params + "&graph_bugs_not_released=" + (gEl("graph_bugs_not_released").checked ? "true" : "false" );
    params = params + "&graph_no_bugs_open=" + (gEl("graph_bugs_open").checked ? "false" : "true" );
    params = params + "&graph_bugs_unconfirmed=" + (gEl("graph_bugs_unconfirmed").checked ? "true" : "false" );
    params = params + "&graph_no_bugs_inflow=" + (gEl("graph_bugs_inflow").checked ? "false" : "true" );
    params = params + "&graph_no_bugs_outflow=" + (gEl("graph_bugs_outflow").checked ? "false" : "true" );
    params = params + "&graph_no_bugs_released=" + (gEl("graph_bugs_released").checked ? "false" : "true");
    params = params + "&graph_bugs_closed=" + (gEl("graph_bugs_closed").checked ? "true" : "false");

    var reloadData = false;
    if (gEl("no_of_weeks_to_show").value != gEl("no_of_weeks_to_show_prev").value) {
        reloadData = true;
    }
    
    //$("#settings").hide();
    request.open("GET", 'save_settings.php' + params, true);
    request.onreadystatechange = function(){
        $("#loading_banner_s").hide();
        save_settings_response(reloadData);
    };
    request.send(null);
}

function save_settings_response(reloadData){
    try {
        if (request.readyState == 4) {
            change_product();
        }
    } 
    catch (e) {
        gEl('details').innerHTML = 'Exception catched: ' + e;
        throw e;
    }
}

/*****************************/
function generate_start_page_graph()
{
        var width = 800;
        var height = 400;
		
        var window_width = (IE) ? document.body.clientWidth : window.innerWidth;
        var window_height = (IE) ? document.body.clientHeight : window.innerHeight;

		if (window_width > 900) {
			width = window_width - 100;
		}
		if (window_height > 500) {
			height = window_height - 100;
		} else {
			height =  width / 2;
		}

		var div_width = width + 10;
		var div_height = height + 10;

        gEl('stats_graph_div_0').style.width  = div_width + "px";
        gEl('stats_graph_div_0').style.height = div_height + "px";

        generate_graph('stats_graph_img_0', gEl('stats_graph_0').value, "", width, height, true);
}

/*****************************/
function generate_graphs_for_all_products_response()
{
    var width = 800;
    var height = 500;
    var div_width = 800;
    var div_height = 500;

    if ( gEl("graph_size_auto").value !== "1" && gEl("graph_autoresize_all_products_graphs").value === "1" )
    {
        width = new Number( gEl("graph_size_width").value );
        height = new Number( gEl("graph_size_height").value );
        div_width = width + 10;
        div_height = height + 10;
    }
    else
    {
        var window_width = (IE) ? document.body.clientWidth : window.innerWidth;
        width = window_width - 100;
        width = (width / 3) - (width % 3);
        height = width / 5 * 3;
        div_width = width + 10;
        div_height = height + 10;
    }

    var i = 0;
    var iel = gEl('stats_graph_div_' + i);
    while (iel != null)
    {
        iel.style.width  = div_width + "px";
        iel.style.height = div_height + "px";
        i += 1;
        iel = gEl('stats_graph_div_' + i);
    }

    i = 0;
    iel = gEl('stats_graph_img_' + i);
    while (iel != null)
    {
        generate_graph('stats_graph_img_' + i, gEl('stats_graph_' + i).value, "", width, height, false);
        i += 1;
        iel = gEl('stats_graph_img_' + i);
    }
}

/*****************************/
function generate_statistics_graph(){
    var width = 800;
    var height = 500;

    if ( gEl("graph_size_auto").value === "1" )
    {
        var window_width = (IE) ? document.body.clientWidth : window.innerWidth;
        var window_height = (IE) ? (screen.height-150) : window.innerHeight;
        width = window_width - gEl('stats_table').offsetWidth - gEl('stats_space').offsetWidth - 50;
        if (width < 600) width = 600;
        height = width / 5 * 3;
    }
    else
    {
        width = gEl("graph_size_width").value;
        height = gEl("graph_size_height").value;
    }

    generate_graph('stats_graph_img', $('#selected_product').val(), $('#selected_component').val(), width, height, true);
}



/*****************************/
function generate_graph(originator, product, component, width, height, do_show_legend) {
    var randomnumber = Math.floor(Math.random()*1000);
    var graph_image = new Image();
    var url = 'generate_graph.php?' + randomnumber + '&s=' + $('#selected_statistic').val() + '&p=' + product + '&c=' + component + '&w=' + width + '&h=' + height;
    if (do_show_legend) {
        url = url + '&l=true';
    }
    graph_image.src = url;
    wait_for_graph(graph_image, originator);
}

function wait_for_graph(graph_image, originator){
    if (graph_image.complete) {
        if (gEl(originator) != null) {
            gEl(originator).src = graph_image.src;
        }
        return;
    }
    timerID = setTimeout( function() { wait_for_graph(graph_image, originator); }, 50 );
}

/*****************************/

function show_changes_details(row_id, details_file, desc_table, snapshot_taken_time, sort_by){
    clearDiv('details');
    showEl('loading_banner');
    gEl('details').innerHTML = "<br/><hr/><img id='loading_banner_2' src='img/loading.gif' alt='loading...' />";
    if (prev_row_id != null) {
        gEl('tr_' + prev_row_id).className = 'tr1';
        gEl('td_' + prev_row_id).innerHTML = "";
    }
    gEl('tr_' + row_id).className = 'tr2';
    gEl('td_' + row_id).innerHTML = "&gt";
    prev_row_id = row_id;
	params = '?s=' + $('#selected_statistic').val()
	   + '&p=' + $('#selected_product').val()
	   + '&f=' + details_file
       + '&d=' + desc_table
       + '&t=' + snapshot_taken_time;
    if (sort_by == 1)
        params += '&sid=true';
    else if (sort_by == 0)
        params += '&sid=false';
        
	   
    request.open("GET", 'changes_details.php' + params, true);
    request.onreadystatechange = function(){
        show_changes_details_response();
    };
    request.send(null);
}

function show_changes_details_response(){
    var window_width = (IE) ? document.body.clientWidth : window.innerWidth;
    
    try {
        if (request.readyState == 4) {
            hideEl('loading_banner');
            gEl('details').innerHTML = request.responseText;
            
            $(".details_tab").hide();
            $(".details_menu li:first").addClass("active").show();
            $(".details_tab:first").show();

            $("#sorting").css({'top': $("#details_container").css('top') - 20, 'left': (window_width - $("#sorting").width() - 40) });
        
            $(".details_menu li").click(function()
            {
                $(".details_menu li").removeClass("active");
                $(this).addClass("active").show();
                $(".details_tab").hide();
        
                var activeTab = $(this).find("a").attr("href");
                $(activeTab).fadeIn();
                return false;
            });

        }
    } 
    catch (e) {
        //hideLoadingBanner(requestor);
        gEl('details').innerHTML = 'Exception catched: ' + e;
        throw e;
    }
}

function showList(type) {
    if ( gEl('list_' + type).style.display == 'inline')
        gEl('list_' + type).style.display = 'none';
    else
        gEl('list_' + type).style.display = 'inline';
}

/*****************************/

