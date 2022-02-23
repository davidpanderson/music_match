<?php

// search for technicians

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/tech.inc");

function tech_search_form() {
    page_head("Search for technicians");
    form_start("tech_search.php", "POST");
    form_checkboxes(
       "... with expertise in",
       items_list(TECH_AREA_LIST, array(), "style")
    );
    form_checkboxes(
       "who are familiar with",
       items_list(PROGRAM_LIST, array(), "style")
    );
    form_checkboxes(
        "Who live close to me", array(array('close', '', false))
    );
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

function get_form_args() {
    $x = new StdClass;
    $x->tech_area = parse_list(TECH_AREA_LIST, "tech_area");
    $x->program = parse_list(PROGRAM_LIST, "program");
    return $x;
}

function match_args($profile, $args) {
    $x = new StdClass;
    $x->tech_area = 0;
    $x->program = 0;
    foreach ($profile->tech_area as $i) {
        if (in_array($i, $args->tech_area)) {
            $x->tech_area++;
        }
    }
    foreach ($profile->program as $i) {
        if (in_array($i, $args->program)) {
            $x->program++;
        }
    }
    return $x;
}

function match_value($match) {
    $x = 0;
    if ($match->tech_area) $x += 100 + $match->tech_area;
    if ($match->program) $x += 100 + $match->program;
    return $x;
}

function tech_search_action($user) {
    page_head("Technician search results");
    $form_args = get_form_args();
    $profiles = get_profiles(TECHNICIAN);
    foreach ($profiles as $user_id=>$profile) {
        $profile->match = match_args($profile, $form_args);
        $profile->value = match_value($profile->match);
    }
    uasort($profiles, 'compare_value');
    start_table("table-striped");
    foreach ($profiles as $user_id=>$profile) {
    }
    end_table();
    page_tail();
}

$user = get_logged_in_user();
$action = post_str("submit", true);
if ($action) {
    tech_search_action($user);
} else {
    tech_search_form();
}
?>
