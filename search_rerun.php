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
    $now = time();
    $t = $now - 7*86400;
    $searches = Search::enum("rerun_time < $t");
    foreach ($searches as $search) {
        $params = json_decode($search->params);
        switch ($params->role) {
        case COMPOSER:
        case PERFORMER:
            $results = cp_rerun($params);
            break;
        case TECHNICIAN:
            $results = tech_rerun($params);
            break;
        case ENSEMBLE:
            $results = ens_rerun($params);
            break;
        default:
            die("bad role");
        }
        $old_results = json_decode($search->view_results);
        $nnew = get_nnew($results, $old_results);
        if ($nnew) {
            $search->update(
                sprintf("rerun_time=%d, rerun_nnew=%d", $now, $nnew)
            );
            BoincNotify::replace(
                sprintf(
        }
    }
}

main();
?>
