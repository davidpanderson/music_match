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

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/cp_profile.inc");
require_once("../inc/tech.inc");
require_once("../inc/ensemble.inc");
require_once("../inc/teacher.inc");
require_once("../inc/search.inc");

// $item is either a profile or an ensemble
//
function show_item($item, $role) {
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        cp_profile_summary_row($item, $role);
        break;
    case TECHNICIAN:
        tech_summary_row($item);
        break;
    case ENSEMBLE:
        ens_profile_summary_row($item);
        break;
    case TEACHER:
        teacher_profile_summary_row($item);
        break;
    }
}

function show_search($search, $user) {
    $params = json_decode($search->params);
    $role = $params->role;
    $args = add_missing_args($params->args, $role);
    $view_results = json_decode($search->view_results);
    page_head(sprintf("%s search results", role_name($role)));
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        $results = cp_search($role, $args, $user);
        break;
    case TECHNICIAN:
        $results = tech_search($args, $user);
        break;
    case ENSEMBLE:
        $results = ens_search($args, $user);
        break;
    case TEACHER:
        $results = teacher_search($args, $user);
        break;
    }
    if (!$results) {
        echo "No results found.";
        page_tail();
        return;
    }
    start_table();
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        cp_profile_summary_header($role);
        break;
    case TECHNICIAN:
        tech_summary_header();
        break;
    case ENSEMBLE:
        ens_profile_summary_header();
        break;
    case TEACHER:
        teacher_profile_summary_header();
        break;
    }

    // see if have new results; if so, show them first
    //
    $have_new = false;
    foreach ($results as $id=>$profile) {
        if (!in_array($id, $view_results)) {
            $have_new = true;
            break;
        }
    }

    if ($have_new) {
        row1("New results", 99, 'success');
        foreach ($results as $id=>$profile) {
            if (!in_array($id, $view_results)) {
                show_item($profile, $role);
            }
        }
        row1("Previous results", 99, 'success');
    }
    foreach ($results as $id=>$profile) {
        if (in_array($id, $view_results)) {
            show_item($profile, $role);
        }
    }
    end_table();
    page_tail();

    // update search record
    //
    $cur_results = [];
    foreach ($results as $id=>$profile) {
        $cur_results[] = $id;
    }
    $search->update(sprintf(
        "view_results='%s', view_time=%d, rerun_time=0, rerun_nnew=0",
        json_encode($cur_results), time()
    ));
}

$user = get_logged_in_user();
update_visit_time($user);
$search = Search::lookup_id(get_int('search_id'));
if (!$search) error_page('search not found');
if ($search->user_id != $user->id) error_page('not your search');
show_search($search, $user);

?>
