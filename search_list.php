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

function show_searches($searches, $role) {
    $s2 = [];
    foreach($searches as $s) {
        if ($s->role != $role) continue;
        $s2[] = $s;
    }
    if (!$s2) return;
    echo sprintf("<h2>%s searches</h2>", role_name($role));
    start_table();
    show_search_list_header($role);
    foreach ($s2 as $s) {
        show_search_list_item($s, $role);
    }
    end_table();
}

function main() {
    $user = get_logged_in_user();
    page_head("Searches");
    $searches = Search::enum("user_id = $user->id order by rerun_nnew desc");
    if ($searches) {
        show_searches($searches, COMPOSER);
        show_searches($searches, PERFORMER);
        show_searches($searches, TECHNICIAN);
        show_searches($searches, ENSEMBLE);
    } else {
        echo "No searches so far.";
    }
    page_tail();
}

main();
?>
