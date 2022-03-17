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

// rerun a search and display results, highlighting new results

function show_search($search) {
    $params = json_decode($search->params);
    $view_results = json_decode($search->view_results);
    $results = do_search($params);
    page_head("Search results");
    start_table();
    search_result_header($params->role);
    row1("New results");
    foreach ($results as $id=>$profile) {
        if (!in_array($id, $view_results)) {
            show_row($profile);
        }
    }
    row1("Previous results");
    foreach ($results as $id=>$profile) {
        if (in_array($id, $view_results)) {
            show_row($profile);
        }
    }
    end_table();
    page_tail();
}

$user = get_logged_in_user();
$search = Search::lookup_id(get_int('search_id'));
if (!$search) error_page('search not found');
if ($search->user_id != $user->id) error_page('not your search');

show_search($search);

?>
