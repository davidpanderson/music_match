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

// Functions for composer/performer profiles
// These are sufficiently similar that we use the same functions
// with a "role" argument.

// show a profile as a list of name/value rows.
// Used in home page and user page.
//
function cp_profile_summary_table($user, $profile, $role) {
    row2('Instruments',
        lists_to_string(
            $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, $profile->inst_custom
        )
    );
    if ($role == COMPOSER) {
        row2('Ensemble types',
            lists_to_string(
                COMPOSE_FOR_LIST,
                $profile->ens_type, $profile->ens_type_custom
            )
        );
    }
    row2('Styles',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    row2('Levels',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    if ($profile->signature_filename) {
        row2('Audio signature',
            sprintf('<a href=%s/%d.mp3>%s</a>',
                role_dir($role), $user->id, $profile->signature_filename
            )
        );
    }

    if ($profile->link) {
        row2('Links',
            links_to_string($profile->link)
        );
    }
    if ($role == COMPOSER) {
        row2(
            'Usually paid', $profile->comp_paid?"Yes":"No"
        );
    } else {
        row2('Performing',
            sprintf('Regularly: %s. Usually paid: %s',
                $profile->perf_reg?"yes":"no",
                $profile->perf_paid?"yes":"no"
            )
        );
    }

    if ($profile->description) {
        row2('Introduction', "<small>$profile->description</small>");
    }
}


// name, with link and audio mouseover
//
function cp_listen_link($user, $profile) {
    $x = '';
    if ($profile->signature_filename) {
        $audio = sprintf(
            ' onclick="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $user->id, $user->id
        );
        $x .= sprintf('&nbsp;<a %s href=#><img height=24px src=note.png></a>', $audio);
    }
    return $x;
}

// show profile as an N-column table row
//
function cp_profile_summary_row($profile, $role) {
    $user = $profile->user;
    if ($profile->signature_filename) {
        echo sprintf('<audio id=a%d><source src="%s/%d.mp3"></source></audio>',
            $user->id,
            role_dir($role),
            $user->id
        );
    }

    $x = array();
    $n = user_name_link($user, $profile);
    if (has_picture($user)) {
        $n .= sprintf('<br><img src=%s width=120>', picture_path($user));
    }
    $x[] = $n;
    $x[] = cp_listen_link($user, $profile);
    $x[] = lists_to_string(
        $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
        $profile->inst, $profile->inst_custom, "<br>"
    );
    if ($role==COMPOSER) {
        $x[] = lists_to_string(
            COMPOSE_FOR_LIST,
            $profile->ens_type, $profile->ens_type_custom, "<br>"
        );
    }
    $x[] = lists_to_string(
        STYLE_LIST, $profile->style, $profile->style_custom, "<br>"
    );
    $x[] = lists_to_string(LEVEL_LIST, $profile->level, null, "<br>");
    $x[] = links_to_string($profile->link, "<br>");
    $x[] = country_distance($user, $profile->dist, "<br>");
    row_array($x);
}

// table header for the above
//
function cp_profile_summary_header($role) {
    enable_audio();
    $x= [
        'Name<br><small>click for details<br>hover for intro</small>',
        music_sample_header(),
        "Instruments"
    ];
    if ($role == COMPOSER) {
        $x[] = "Ensemble types";
    }
    $x[] = "Styles";
    $x[] = "Level";
    $x[] = "Links";
    $x[] = "Country";
    row_heading_array($x);
}

?>
