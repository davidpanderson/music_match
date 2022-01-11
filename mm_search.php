<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

function comp_search_form($profile) {
    global $inst_list_comp, $style_list, $level_list;

    page_head("Search for composers");
    form_start("mm_search.php");
    form_checkboxes(
        "Who write for at least one of:",
        items_list($inst_list_comp, $profile->inst, "inst")
    );
    echo "<hr>";
    form_checkboxes(
       "in styles including at least one of:",
        items_list($style_list, $profile->style, "style")
    );
    echo "<hr>";
    form_checkboxes(
        "in difficulty levels including at least one of:",
        items_list($level_list, $profile->level, "level")
    );
    echo "<hr>";
    form_checkboxes(
        "Who live close to me", array(array('close', '', false))
    );
    echo "<hr>";
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

// parse form args; return object with arrays of attrs
//
function get_comp_attrs() {
    global $inst_list_comp, $style_list, $level_list;
    $x = new StdClass;
    $x->inst = parse_list($inst_list_comp, "inst");
    $x->style = parse_list($style_list, "style");
    $x->level = parse_list($level_list, "level");
    return $x;
}

// return true if profile matches attrs
//
function comp_match($comp, $attrs) {
    $found = false;
    foreach ($comp->instr as $i) {
        if (in_array($i, $attrs->instr)) {
            $found = true;
        }
    }
    if (!$found) return false;

    $found = false;
    foreach ($comp->style as $i) {
        if (in_array($i, $attrs->style)) {
            $found = true;
        }
    }
    if (!$found) return false;

    $found = false;
    foreach ($comp->level as $i) {
        if (in_array($i, $attrs->level)) {
            $found = true;
        }
    }
    if (!$found) return false;

    return true;
}

function comp_search_action() {
    page_head("Composer search results");
    $comp_attrs = get_comp_attrs();
    $comps = get_profiles(true);
    foreach ($comps as $comp) {
        if (comp_match($comp, $comp_attrs)) {
            show_comp_short($comp);
        }
    }
    page_tail();
}

$user = get_logged_in_user(true);

if ($user) {
    $profile = read_profile($user->id, false);
} else {
    $profile = read_profile(0, false);
}

$action = get_str("submit", true);
if ($action == 'submit') {
    comp_search_action();
} else {
    comp_search_form($profile);
}

?>
