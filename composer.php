<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// create/edit a composer profile

function inst_comp_items($inst_comp, $inst_comp_custom) {
    global $inst_list_comp;

    $x = array();
    foreach ($inst_list_comp as $tag => $name) {
        $x[] = array("inst_$tag", $name, in_array($tag, $inst_comp));
    }
    foreach ($inst_comp_custom as $name) {
        $x[] = array("inst_custom_$name", $name, true);
    }
    return $x;
}

function style_items($styles, $styles_custom) {
    global $style_list;
    $x = array();
    foreach ($style_list as $tag => $name) {
        $x[] = array("style_$tag", $name, in_array($tag, $styles));
    }
    foreach ($styles_custom as $name) {
        $x[] = array("style_custom_$name", $name, true);
    }
    return $x;
}

function level_items($levels) {
    global $level_list;
    $x = array();
    foreach ($level_list as $tag => $name) {
        $x[] = array("level_$tag", $name, in_array($tag, $levels));
    }
    return $x;
}

function influence_items($influences) {
    $x = array();
    foreach ($influences as $name) {
        $x[] = array("inf_$name", $name, true);
    }
    return $x;
}

function comp_form($profile) {
    page_head("Composer profile");
    form_start("composer.php");
    form_checkboxes(
        "<span>Instruments you write for:</span>",
        inst_comp_items(array('keyboard'), array('didgeridu'))
    );
    form_input_text('', 'inst_custom', 'Other', 'text', 'class="sm" size="20"', '');
    echo "<hr>";

    form_checkboxes(
        "Styles you write in:",
        style_items(array('classical'), array('neopunk'))
    );
    form_input_text('', 'style_custom', 'Other', 'text', 'class="sm" size="20"', '');

    echo "<hr>";
    form_checkboxes(
        "Technical levels you write for?",
        level_items(array())
    );
    echo "<hr>";

    form_checkboxes(
        "Composers/musicians who influence your work:",
        influence_items(array())
    );
    form_input_text('', 'influence_add', 'Add', 'text', 'class="sm" size="20"', '');


    echo "<hr>";

    form_submit("OK", 'name=submit value=on');
    form_end();

    page_tail();
}

function parse_inst() {
    global $inst_list_comp;
    $x = array();
    foreach ($inst_list_comp as $tag=>$name) {
        if (get_str("inst_$tag", true)) {
            $x[] = $tag;
        }
    }
    return $x;
}

function parse_styles() {
    return Array();
}
function parse_levels() {
    return Array();
}
function parse_influences() {
    return Array();
}

function comp_action() {
    $profile = new StdClass;
    $profile->inst = parse_inst();
    $profile->styles = parse_styles();
    $profile->levels = parse_levels();
    $profile->influences = parse_influences();
    echo "<pre>";
    echo json_encode($profile, JSON_PRETTY_PRINT);
}

//$user = get_logged_in_user();

if (get_str('submit', true)) {
    comp_action();
} else {
    comp_form(0);
}

?>
