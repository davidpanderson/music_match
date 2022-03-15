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

function tech_profile_summary_table($user, $profile) {
    row2('Areas',
        lists_to_string(
            TECH_AREA_LIST, $profile->tech_area, $profile->tech_area_custom
        )
    );
    row2('Programs',
        lists_to_string(
            PROGRAM_LIST, $profile->program, $profile->program_custom
        )
    );
}

function tech_summary_header() {
    row_heading_array(array("Name", "Areas", "Programs", "Country"));
}

function tech_summary_row($user, $profile) {
    row_array(array(
        "<a href=user.php?user_id=$user->id>$user->name</a>",
        lists_to_string(
            TECH_AREA_LIST, $profile->tech_area, $profile->tech_area_custom, "<br>"
        ),
        lists_to_string(
            PROGRAM_LIST, $profile->program, $profile->program_custom, "<br>"
        ),
        country_distance($user, $profile->dist, '<br>')
    ));
}

?>
