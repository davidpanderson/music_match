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
    if ($profile->description) {
        row2('Description', "<small>$profile->description</small>");
    }
    if ($profile->link) {
        row2('Links',
            links_to_string($profile->link)
        );
    }
}

// show profile as an N-column table row
//
function teacher_profile_summary_row($profile) {
    $user = $profile->user;

    row_array([
        user_name_link($user, $profile),
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
