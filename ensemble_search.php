<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/ensemble.inc");

function ens_search_form() {
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
    $x->close = post_str('close', true)=='on';
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

function ens_search_action($req_user) {
    global $audio_head_extra;
    page_head("Ensemble search results", null, false, "", $audio_head_extra);
    $form_args = get_form_args();
    [$close_country, $close_zip] = handle_close($form_args, $req_user);
    $ensembles_in = Ensemble::enum("");
    $ensembles = array();
    foreach ($ensembles_in as $e) {
        $e->profile = read_profile($e->id, ENSEMBLE);
        $e->match = match_args($e->profile, $form_args);
        $e->value = match_value($e->match);
        if ($e->value == 0) continue;
        $user = BoincUser::lookup_id($e->user_id);
        if ($close_country && $close_country != $user->country) {
            continue;
        }
        if ($close_zip) {
            $other_zip = str_to_zip($user->postal_code);
            if (!$other_zip) continue;
            $dist = zip_dist($close_zip, $other_zip);
            if ($dist > 60) continue;
            $e->value -= $dist;
            $e->dist = $dist;
        } else {
            $e->dist = -1;
        }
        $e->user = $user;
        $ensembles[] = $e;
    }
    if (!$ensembles) {
        echo "No results found.  Try expanding your criteria.";
        page_tail();
        return;
    }
    uasort($ensembles, 'compare_value');
    start_table('table-striped');
    $enable_tag = '<br><a id="enable" onclick="remove()" href=#>Enable mouse-over audio</a>';
    $name_header = sprintf(
        'Name<br><small>click for details<br>mouse over to hear audio sample%s</small>',
        $enable_tag
    );

    ens_profile_summary_header($name_header);
    foreach ($ensembles as $e) {
        $profile = $e->profile;
        if ($profile->signature_filename) {
            echo sprintf('<audio id=a%d><source src="%s/%d.mp3"></source></audio>',
                $e->id,
                role_dir(ENSEMBLE),
                $e->id
            );
        }
        ens_profile_summary_row($e);
    }
    end_table();
    page_tail();
}

$action = post_str("submit", true);
$user = get_logged_in_user();
if ($action) {
    ens_search_action($user);
} else {
    ens_search_form();
}

?>
