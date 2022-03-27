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

// convert search args to a list of lines
//
function args_to_str($args, $role) {
    $s = '';
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        if ($args->inst) {
            $s .= "Instruments: ";
            $x = [];
            foreach ($args->inst as $i) {
                $x[] = $role==COMPOSER?INST_LIST_COARSE[$i]:INST_LIST_FINE[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($role == COMPOSER) {
            if ($args->ens_type) {
                $s .= '<br>Ensemble types: ';
                $x = [];
                foreach ($args->ens_type as $i) {
                    $x[] = ENSEMBLE_TYPE_LIST[$i];
                }
                $s .= implode(', ', $x);
                $s .= '<br>';
            }
        }
        if ($args->style) {
            $s .= 'Style: ';
            $x = [];
            foreach ($args->style as $i) {
                $x[] = STYLE_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->level) {
            $s .= 'Level: ';
            $x = [];
            foreach ($args->level as $i) {
                $x[] = LEVEL_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->close) {
            $s .= 'Nearby: ';
            $s .= 'yes';
            $s .= '<br>';
        }
        break;
    case TECHNICIAN:
        if ($args->tech_area) {
            $s = 'Areas: ';
            $x = [];
            foreach ($args->tech_area as $i) {
                $x[] = TECH_AREA_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->program) {
            $s .= 'Software: ';
            $x = [];
            foreach ($args->program as $i) {
                $x[] = PROGRAM_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->close) {
            $s .= 'Nearby: ';
            $s .= 'yes';
            $s .= '<br>';
        }
        break;
    case ENSEMBLE:
        if ($args->type) {
            $s = 'Ensemble type: ';
            $x = [];
            foreach ($args->type as $i) {
                $x[] = ENSEMBLE_TYPE_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->inst) {
            $s .= 'Instruments: ';
            $x = [];
            foreach ($args->inst as $i) {
                $x[] = INST_LIST_FINE[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->level) {
            $s .= 'Level: ';
            $x = [];
            foreach ($args->level as $i) {
                $x[] = LEVEL_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->seeking_members != 'either') {
            $s .= 'Seeking members: ';
            $s .= $args->seeking_members;
            $s .= '<br>';
        }
        if ($args->perf_reg != 'either') {
            $s .= 'Perform regularly: ';
            $s .= $args->perf_reg;
            $s .= '<br>';
        }
        if ($args->perf_paid != 'either') {
            $s .= 'Paid to perform: ';
            $s .= $args->perf_paid;
            $s .= '<br>';
        }
        if ($args->close) {
            $s .= 'Nearby: ';
            $s .= 'yes';
            $s .= '<br>';
        }
        break;
    case TEACHER:
        if ($args->topic) {
            $s .= 'Topic: ';
            $x = [];
            foreach ($args->topic as $i) {
                $x[] = TOPIC_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->style) {
            $s .= 'Style: ';
            $x = [];
            foreach ($args->style as $i) {
                $x[] = STYLE_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->level) {
            $s .= 'Level: ';
            $x = [];
            foreach ($args->level as $i) {
                $x[] = LEVEL_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if (isset($args->where)) {
            $s .= 'Where: ';
            $x = [];
            foreach ($args->where as $i) {
                $x[] = WHERE_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
        if ($args->close) {
            $s .= 'Nearby: ';
            $s .= 'yes';
            $s .= '<br>';
        }
        break;
    }
    return $s;

}

function show_search_list_item($s, $role) {
    $nresults = count(json_decode($s->view_results));
    $m = "$nresults";
    if ($s->rerun_nnew) {
        $m .= sprintf(
            "<br><font color=orange>This search has %d new %s</font>",
            $s->rerun_nnew,
            $s->rerun_nnew==1?"match":"matches"
        );
    }
    $m .= "<br>";
    $m .= mm_button_text("search_show.php?search_id=$s->id", "View matches", BUTTON_SMALL);

    row_array([
        args_to_str($s->params->args, $role),
        $m,
        date_str($s->view_time),
        mm_button_text("search_list.php?search_id=$s->id&action=delete",
            "Delete", BUTTON_SMALL
        )
    ]);
    row1('&nbsp;', 99, 'bg-dark');
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
    page_head("Search deleted");
    page_tail();
} else {
    main($user);
}
?>
