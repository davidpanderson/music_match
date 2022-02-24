<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/ensemble.inc");

function search_form() {
    page_head("Ensemble search");
    form_start("ensemble_search.php", "POST");
    form_checkboxes("Ensemble type",
        items_list(ENSEMBLE_TYPE_LIST, array(), "type")
    );
    form_checkboxes("Instruments",
        items_list(INST_LIST_FINE, array(), "inst")
    );
    form_checkboxes("Styles",
        items_list(STYLE_LIST, array(), "style")
    );
    form_checkboxes("Level",
        items_list(LEVEL_LIST, array(), "level")
    );
    radio_bool("Seeking new members", 'seeking_members');
    radio_bool("Perform regularly", 'perf_reg');
    radio_bool("Paid to perform", 'perf_paid');
    form_checkboxes(
        "Close to me", array(array('close', '', false))
    );
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

function get_form_args() {
    $x = new StdClass;
    $x->type = parse_list(ENSEMBLE_TYPE_LIST, "type");
    $x->inst = parse_list(INST_LIST_FINE, "inst");
    $x->style = parse_list(STYLE_LIST, "style");
    $x->level = parse_list(LEVEL_LIST, "level");
    return $x;
}

function match_args($profile, $args) {
    $x = new StdClass;
    $x->type = 0;
    $x->inst = 0;
    $x->style = 0;
    $x->level = 0;
    if (in_array($profile->type, $args->type)) {
        $x->type++;
    }
    foreach ($profile->inst as $i) {
        if (in_array($i, $args->inst)) {
            $x->inst++;
        }
    }
    foreach ($profile->style as $i) {
        if (in_array($i, $args->style)) {
            $x->style++;
        }
    }
    foreach ($profile->level as $i) {
        if (in_array($i, $args->level)) {
            $x->level++;
        }
    }
    return $x;
}

function match_value($match) {
    $x = 0;
    if ($match->type) $x += 100 + $match->type;
    if ($match->inst) $x += 100 + $match->inst;
    if ($match->style) $x += 100 + $match->style;
    if ($match->level) $x += 100 + $match->level;
    return $x;
}

function search_action() {
    page_head("Search results");
    $form_args = get_form_args();
    $ensembles = Ensemble::enum("");
    foreach ($ensembles as $e) {
        $e->profile = read_profile($e->id, ENSEMBLE);
        $e->match = match_args($e->profile, $form_args);
        $e->value = match_value($e->match);
    }
    uasort($ensembles, 'compare_value');
    start_table('table-striped');
    ens_profile_summary_header();
    $found = false;
    foreach ($ensembles as $e) {
        if ($e->value == 0) continue;
        $found = true;
        ens_profile_summary_row($e);
    }
    end_table();
    if (!$found) {
        echo "No ensembles found.  Try expanding your search.";
    }
    page_tail();
}

$action = post_str("submit", true);
if ($action) {
    search_action();
} else {
    search_form();
}

?>
