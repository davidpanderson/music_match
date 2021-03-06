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

// search for technicians
//
// If you add any search parameters, add them in search.inc too

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/search.inc");
require_once("../inc/notification.inc");
require_once("../inc/tech.inc");

function tech_search_form() {
    page_head("Search for technicians");
    form_start("tech_search.php", "POST");
    form_checkboxes(
       "with expertise in",
       items_list(TECH_AREA_LIST, array(), "tech_area")
    );
    form_checkboxes(
       "who are familiar with",
       items_list(PROGRAM_LIST, array(), "program")
    );
    form_checkboxes(
        "who live close to me", array(array('close', '', false))
    );
    form_input_text(
        'Introduction includes', 'writing'
    );
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

function get_form_args() {
    $x = new StdClass;
    $x->tech_area = parse_list(TECH_AREA_LIST, "tech_area");
    $x->program = parse_list(PROGRAM_LIST, "program");
    $x->close = post_str('close', true)=='on';
    $x->writing = post_str('writing');
    return $x;
}

function tech_search_action($req_user) {
    page_head("Technician search results");
    $form_args = get_form_args();
    $profiles = tech_search($form_args, $req_user);
    record_search($req_user, TECHNICIAN, $form_args, $profiles);
    if (!$profiles) {
        echo "No results found.  Try changing your criteria.";
        page_tail();
        return;
    }
    notify_search_results($req_user, TECHNICIAN, $profiles);
    start_table("table-striped");
    tech_summary_header();
    foreach ($profiles as $user_id=>$profile) {
        tech_summary_row($profile);
    }
    end_table();
    page_tail();
}

$user = get_logged_in_user();
update_visit_time($user);
$action = post_str("submit", true);
if ($action) {
    tech_search_action($user);
} else {
    tech_search_form();
}
?>
