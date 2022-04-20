#! /usr/bin/env php
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

// ops script to rerun searches, record the results, and notify if new results

require_once("../inc/search.inc");
require_once("../inc/boinc_db.inc");
require_once("../inc/forum_db.inc");

function get_nnew($new, $old) {
    $n = 0;
    foreach ($new as $i) {
        if (!in_array($i, $old)) {
            $n++;
        }
    }
    return $n;
}

function main() {
    $t = time() - 7*86400;
    $searches = Search::enum("rerun_time < $t");
    foreach ($searches as $search) {
        $user = BoincUser::lookup_id($search->user_id);
        $params = json_decode($search->params);
        $role = $params->role;
        $args = add_missing_args($params->args, $role);
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
        default:
            echo("bad role: $role");
            continue;
        }
        $old_results = json_decode($search->view_results);
        $ids = [];
        foreach ($results as $id=>$x) {
            $ids[] = $id;
        }
        $nnew = get_nnew($ids, $old_results);
        $search->update(
            sprintf("rerun_time=%d, rerun_nnew=%d", time(), $nnew)
        );
        if ($nnew) {
            BoincNotify::replace(
                sprintf("userid=%d, create_time=%d, type=%d, opaque=0, id2=0, sent_by_email=0",
                    $user->id, time(), NOTIFY_SEARCH
                )
            );
        }
        echo "re-ran search $search->id; got $nnew new results\n";
    }
}


echo date(DATE_RFC822), ": Starting\n";
main();
echo date(DATE_RFC822), ": Finished\n";
?>
