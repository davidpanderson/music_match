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

require_once("../inc/util.inc");
require_once("../inc/mm_db.inc");

function show_search_list_header() {
    row_heading_array([
        "Search criteria", "Number of matches", "Last viewed"
    ]);
}

function args_to_str($args, $role) {
    $s = '';
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        $s .= "Instruments: ";
        if ($args->inst) {
            $x = [];
            foreach ($args->inst as $i) {
                $x[] = $role==COMPOSER?INST_LIST_COARSE[$i]:INST_LIST_FINE[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        if ($role == COMPOSER) {
            $s .= '<br>Ensemble types: ';
            if ($args->ens_type) {
                $x = [];
                foreach ($args->ens_type as $i) {
                    $x[] = ENSEMBLE_TYPE_LIST[$i];
                }
                $s .= implode(', ', $x);
            } else {
                $s .= 'any';
            }
        }
        $s .= '<br>Style: ';
        if ($args->style) {
            $x = [];
            foreach ($args->style as $i) {
                $x[] = STYLE_LIST[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Level: ';
        if ($args->level) {
            $x = [];
            foreach ($args->level as $i) {
                $x[] = LEVEL_LIST[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Nearby: ';
        if ($args->close) {
            $s .= 'yes';
        } else {
            $s .= 'either';
        }
        break;
    case TECHNICIAN:
        $s = 'Areas: ';
        if ($args->tech_area) {
            $x = [];
            foreach ($args->tech_area as $i) {
                $x[] = TECH_AREA_LIST[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Software: ';
        if ($args->program) {
            $x = [];
            foreach ($args->program as $i) {
                $x[] = PROGRAM_LIST[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Nearby: ';
        if ($args->close) {
            $s .= 'yes';
        } else {
            $s .= 'either';
        }
        break;
    case ENSEMBLE:
        $s = 'Ensemble type: ';
        if ($args->type) {
            $x = [];
            foreach ($args->type as $i) {
                $x[] = ENSEMBLE_TYPE_LIST[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Instruments: ';
        if ($args->inst) {
            $x = [];
            foreach ($args->inst as $i) {
                $x[] = INST_LIST_FINE[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Level: ';
        if ($args->level) {
            $x = [];
            foreach ($args->level as $i) {
                $x[] = LEVEL_LIST[$i];
            }
            $s .= implode(', ', $x);
        } else {
            $s .= 'any';
        }
        $s .= '<br>Seeking members: ';
        $s .= $args->seeking_members?$args->seeking_members:'either';
        $s .= '<br>Perform regularly: ';
        $s .= $args->perf_reg?$args->perf_reg:'either';
        $s .= '<br>Paid to perform: ';
        $s .= $args->perf_paid?$args->perf_paid:'either';
        $s .= '<br>Nearby: ';
        if ($args->close) {
            $s .= 'yes';
        } else {
            $s .= 'either';
        }
        break;
    }
    return $s;

}

function show_search_list_item($s, $role) {
    //print_r($s->params);
    $nresults = count(json_decode($s->view_results));
    $m = "$nresults";
    if ($s->rerun_nnew) {
        $m .= sprintf(
            "<br><font color=orange>This search has %d new matches</font>", $s->rerun_nnew
        );
    }
    $m .= "<br>";
    $m .= mm_button_text("search_show.php?search_id=$s->id", "View matches", BUTTON_SMALL);

    row_array([
        args_to_str($s->params->args, $role),
        $m,
        date_str($s->view_time)
    ]);
}

function show_searches($searches, $role) {
    $s2 = [];
    foreach($searches as $s) {
        if ($s->params->role != $role) continue;
        $s2[] = $s;
    }
    if (!$s2) return;
    echo sprintf("<h2>%s searches</h2>", role_name($role));
    start_table('table-striped');
    show_search_list_header($role);
    foreach ($s2 as $s) {
        show_search_list_item($s, $role);
    }
    end_table();
}

function main() {
    $user = get_logged_in_user();
    page_head("My searches");
    $searches = Search::enum("user_id = $user->id order by rerun_nnew desc");
    if ($searches) {
        foreach($searches as $s) {
            $s->params = json_decode($s->params);
        }
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
