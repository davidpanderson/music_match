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

// show a list of searches, with links to view or delete
// No details (i.e. search params) should be here

require_once("../inc/util.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/search.inc");

function show_search_list_item($s, $role) {
    $nresults = count(json_decode($s->view_results));
    $m = "$nresults";
    $m .= " &nbsp; ";
    $m .= mm_button_text("search_show.php?search_id=$s->id", "View", BUTTON_SMALL);
    if ($s->rerun_nnew) {
        $m .= sprintf(
            "<br><font color=orange>This search has %d new %s</font>",
            $s->rerun_nnew,
            $s->rerun_nnew==1?"match":"matches"
        );
    }

    $a = args_to_str(add_missing_args($s->params->args, $role), $role);
    if (!$a) return;
    row_array([
        $a,
        $m,
        date_str($s->view_time),
        mm_button_text("search_list.php?search_id=$s->id&action=delete",
            "Delete", BUTTON_SMALL
        )
    ]);
    echo '<tr><td colspan=99><hr style="height:2px;border-width:0;background-color:#444444"></td></tr>';
}

function show_searches($searches, $role) {
    $s2 = [];
    foreach($searches as $s) {
        if ($s->params->role != $role) continue;
        $s2[] = $s;
    }
    if (!$s2) return;
    row1(
        sprintf("%s searches", role_name($role)),
        99, "bg-primary"
    );
    foreach ($s2 as $s) {
        show_search_list_item($s, $role);
    }
}

function show_search_list_header() {
    row_heading_array([
        "Search criteria", "Number of matches", "Last viewed", 'Delete search'
    ],
    null,
    "bg-primary"
    );
}

function main($user) {
    page_head("My searches");
    $searches = Search::enum("user_id = $user->id order by rerun_nnew desc");
    if ($searches) {
        text_start();
        echo "
            <p>
            Music Match records your searches,
            and we'll notify you if there are new results
            since the last time you looked.
            You can delete searches you're no longer interested in.
            <p>
        ";
        text_end();
        foreach($searches as $s) {
            $s->params = json_decode($s->params);
        }
        start_table('table');
        show_search_list_header();
        row1("", 99, "bg-secondary");
        show_searches($searches, COMPOSER);
        show_searches($searches, PERFORMER);
        show_searches($searches, TECHNICIAN);
        show_searches($searches, ENSEMBLE);
        show_searches($searches, TEACHER);
        end_table();
    } else {
        echo "No searches so far.";
    }
    page_tail();
}

$user = get_logged_in_user();
if (get_str('action', true) == 'delete') {
    $search_id = get_int('search_id');
    $search = Search::lookup_id($search_id);
    if (!$search || $search->user_id != $user->id) {
        error_page("No such search");
    }
    $search->delete();
    Header("Location: search_list.php");
} else {
    main($user);
}
?>
