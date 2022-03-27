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

// search for teachers

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/teacher.inc");
require_once("../inc/search.inc");

function teacher_search_form($profile) {
    page_head("Search for teachers");
    form_start("teacher_search.php", "POST");
    form_checkboxes(
        "Topics",
        items_list(TOPIC_LIST, $profile->topic, "topic")
    );
    form_checkboxes(
       "in styles including",
        items_list(STYLE_LIST, $profile->style, "style")
    );
    form_checkboxes(
        "in difficulty levels including",
        items_list(LEVEL_LIST, $profile->level, "level")
    );
    form_checkboxes(
        "Teaching location",
        items_list(WHERE_LIST, [], "where")
    );
    form_checkboxes(
        "Who live close to me", array(array('close', '', false))
    );
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

// parse form args; return object with arrays of attrs
//
function get_form_args() {
    $x = new StdClass;
    $x->topic = parse_list(TOPIC_LIST, "topic");
    $x->style = parse_list(STYLE_LIST, "style");
    $x->level = parse_list(LEVEL_LIST, "level");
    $x->where = parse_list(WHERE_LIST, "where");
    $x->close = post_str('close', true)=='on';
    return $x;
}

function teacher_search_action($req_user) {

    page_head("Teacher search results");

    $form_args = get_form_args();
    $profiles = teacher_search($form_args, $req_user);
    if (!$profiles) {
        echo "No results found.  Try expanding your criteria.";
        page_tail();
        return;
    }

    start_table("table-striped");
    teacher_profile_summary_header();
    foreach ($profiles as $user_id=>$profile) {
        teacher_profile_summary_row($profile);
    }
    end_table();
    page_tail();

    record_search($req_user, TEACHER, $form_args, $profiles);
}

$user = get_logged_in_user(true);
update_visit_time($user);

$action = post_str("submit", true);
if ($action) {
    teacher_search_action($user);
} else {
    if ($user) {
        $profile = read_profile($user->id, TEACHER);
    } else {
        $profile = read_profile(0, TEACHER);
    }
    teacher_search_form($profile);
}

?>
