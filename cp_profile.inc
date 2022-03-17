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

// Show profile as a list of name: value lines
// Not currently used.
//
function cp_profile_summary($user, $profile, $role) {
    $x = sprintf('Instruments: %s',
        lists_to_string(
            $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, $profile->inst_custom
        )
    );
    $x .= sprintf('<br>Styles: %s',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    $x .= sprintf('<br>Levels: %s',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    if ($role==COMPOSER && $profile->influence) {
        $x .= "<br>Influences: ".implode(", ", $profile->influence);
    }
    if ($profile->link) {
        $x .= "<br>Links: ".links_to_string($profile->link);
    }
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        $x .= sprintf('<br>Country: %s', country_distance($user, $profile->dist));
    }
    return $x;
}

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
                ENSEMBLE_TYPE_LIST,
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
    if ($role==COMPOSER && $profile->influence) {
        row2('Influences',
            implode(", ", $profile->influence)
        );
    }
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
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        row2('Country',
            country_distance($user, user_distance($u, $user), '  ')
        );
    }
}


// name, with link and audio mouseover
//
function cp_name_link($user, $profile) {
    $audio = "";
    if ($profile->signature_filename) {
        $audio = sprintf(' onmouseenter="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $user->id, $user->id
        );
    }
    return sprintf('<a %s href=user.php?user_id=%d>%s%s</a>',
        $audio,
        $user->id, $user->name,
        $profile->signature_filename?"&nbsp;&nbsp;<img height=24px src=note.png>":""
    );
}

// show a 2-column table row summarizing a composer or performer profile
//
function show_profile_2col($user, $profile, $role) {
    $x1 = cp_name_link($user, $profile);
    $x2 = profile_summary($user, $profile, $role);
    if (DEBUG) {
        $x2 .= sprintf('<br>match: %d (%d, %d, %d)',
            $profile->value,
            $profile->match->inst,
            $profile->match->style,
            $profile->match->level
        );
    }
    row2($x1, $x2);
}

// show profile as an N-column table row
//
function cp_profile_summary_row($user, $profile, $role) {
    $x = array();
    $x[] = cp_name_link($user, $profile);
    $x[] = lists_to_string(
        $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
        $profile->inst, $profile->inst_custom, "<br>"
    );
    if ($role==COMPOSER) {
        $x[] = lists_to_string(
            ENSEMBLE_TYPE_LIST,
            $profile->ens_type, $profile->ens_type_custom, "<br>"
        );
    }
    $x[] = lists_to_string(
        STYLE_LIST, $profile->style, $profile->style_custom, "<br>"
    );
    $x[] = lists_to_string(LEVEL_LIST, $profile->level, null, "<br>");
    if ($role==COMPOSER) {
        if ($profile->influence) {
            $x[] = implode("<br>", $profile->influence);
        } else {
            $x[] = '';
        }
    }
    $x[] = links_to_string($profile->link, "<br>");
    $x[] = country_distance($user, $profile->dist, "<br>");
    row_array($x);
}

// table header for the above
//
function cp_profile_summary_header($name_header, $role) {
    $x= [$name_header, "Instruments"];
    if ($role == COMPOSER) {
        $x[] = "Ensemble types";
    }
    $x[] = "Styles";
    $x[] = "Level";
    if ($role==COMPOSER) $x[] = "Influences";
    $x[] = "Links";
    $x[] = "Country";
    row_heading_array($x);
}

?>
