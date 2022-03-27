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

// Functions for teacher profiles

// Show profile as a list of name: value lines
// Not currently used.
//
function teacher_profile_summary($user, $profile) {
    $x = sprintf('Topics: %s',
        lists_to_string(
            TOPIC_LIST,
            $profile->topic, $profile->topic_custom
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
    if ($profile->link) {
        $x .= "<br>Links: ".links_to_string($profile->link);
    }
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        $x .= sprintf('<br>Country: %s', country_distance($user, $profile->dist));
    }
    return $x;
}

// show a profile as a list of name/value rows
//
function teacher_profile_summary_table($user, $profile) {
    row2('Topics',
        lists_to_string(
            TOPIC_LIST,
            $profile->topic, $profile->topic_custom
        )
    );
    row2('Styles',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    row2('Levels',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    row2('Where',
        lists_to_string(WHERE_LIST, $profile->where)
    );
    if ($profile->link) {
        row2('Links',
            links_to_string($profile->link)
        );
    }
}


// name, with link
//
function teacher_name_link($user, $profile) {
    return sprintf('<a href=user.php?user_id=%d>%s</a>',
        $user->id, $user->name
    );
}

// show profile as an N-column table row
//
function teacher_profile_summary_row($profile) {
    $user = $profile->user;

    row_array([
        teacher_name_link($user, $profile),
        lists_to_string(
            TOPIC_LIST,
            $profile->topic, $profile->topic_custom, "<br>"
        ),
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom, "<br>"
        ),
        lists_to_string(LEVEL_LIST, $profile->level, null, "<br>"),
        lists_to_string(WHERE_LIST, $profile->where, null, "<br>"),
        links_to_string($profile->link, "<br>"),
        country_distance($user, $profile->dist, "<br>")
    ]);
}

// table header for the above
//
function teacher_profile_summary_header() {
    row_heading_array(
        ["Name", "Instruments", "Styles", "Level", "Location", "Links", "Where"]
    );
}

?>
