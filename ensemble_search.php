<?php
// This file is part of Music Match.
// Copyright (C) 2022 David P. Anderson
//
// Music Match is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// Music Match is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with Music Match.  If not, see <http://www.gnu.org/licenses/>.
// --------------------------------------------------------------------

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/ensemble.inc");
require_once("../inc/search.inc");

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
    $x->seeking_members = post_str('seeking_members');
    $x->perf_reg = post_str('perf_reg');
    $x->perf_paid = post_str('perf_paid');
    $x->close = post_str('close', true)=='on';
    return $x;
}

function ens_search_action($req_user) {
    page_head("Ensemble search results");
    $form_args = get_form_args();
    $ensembles = ens_search($form_args, $req_user);
    if (!$ensembles) {
        echo "No results found.  Try expanding your criteria.";
        page_tail();
        return;
    }
    start_table('table-striped');
    ens_profile_summary_header();
    foreach ($ensembles as $e) {
        ens_profile_summary_row($e);
    }
    end_table();
    page_tail();
    record_search($req_user, ENSEMBLE, $form_args, $ensembles);
}

$action = post_str("submit", true);
$user = get_logged_in_user();
update_visit_time($user);
if ($action) {
    ens_search_action($user);
} else {
    ens_search_form();
}

?>
