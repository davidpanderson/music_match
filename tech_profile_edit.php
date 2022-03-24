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

// create/edit a technician profile

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

function tech_form($user) {
    $profile = read_profile($user->id, TECHNICIAN);
    page_head("Technician profile");
    form_start("tech_profile_edit.php", "POST");
    form_checkboxes(
        "Areas of expertise",
        array_merge(
            items_list(TECH_AREA_LIST, $profile->tech_area, "tech_area"),
            items_custom($profile->tech_area_custom, "tech_area_custom")
        )
    );
    form_input_text(
        '', 'tech_area_custom_new', TECH_AREA_ADD, 'text',
        text_input_default(TECH_AREA_ADD).'class="sm" size="20"'
    );
    form_checkboxes(
        "Software you're familiar with",
        array_merge(
            items_list(PROGRAM_LIST, $profile->program, "program"),
            items_custom($profile->program_custom, "program_custom")
        )
    );
    form_input_text(
        '', 'program_custom_new', PROGRAM_ADD, 'text',
        text_input_default(PROGRAM_ADD).'class="sm" size="20"'
    );
    $have_profile = profile_exists($user->id, TECHNICIAN);
    form_submit($have_profile?"Update profile":"Create profile", 'name=submit value=on');
    form_end();
    if ($have_profile) {
        echo "<p>";
        mm_show_button(
            "tech_profile_edit.php?&action=delete",
            "Delete profile",
            BUTTON_SMALL
        );
    }
    page_tail();
}

function tech_action($user_id, $profile) {
    $profile2 = new StdClass;
    $profile2->tech_area = parse_list(TECH_AREA_LIST, 'tech_area');
    $profile2->tech_area_custom = parse_custom(
        $profile->tech_area_custom, "tech_area_custom", TECH_AREA_ADD
    );
    $profile2->program = parse_list(PROGRAM_LIST, "program");
    $profile2->program_custom = parse_custom(
        $profile->program_custom, "program_custom", PROGRAM_ADD
    );
    return $profile2;
}

function confirm_form() {
    page_head('Confirm delete profile');
    echo '<p>Are you sure you want to delete your Technician profile?';
    echo "<p>";
    mm_show_button("tech_profile_edit.php?action=confirm",
        "Delete profile",
        BUTTON_DANGER
    );
    echo "<p>";
    page_tail();
}

function do_delete_profile($user) {
    page_head("Profile deleted");
    delete_mm_profile($user->id, TECHNICIAN);
    echo 'Your Technician profile has been deleted.';
    page_tail();
}
$user = get_logged_in_user();
update_visit_time($user);
if (post_str('submit', true)) {
    $profile = read_profile($user->id, TECHNICIAN);
    $profile = tech_action($user->id, $profile);
    write_profile($user->id, $profile, TECHNICIAN);
    profile_change_notify($user, TECHNICIAN);
    Header("Location: home.php");
} else {
    $action = get_str('action', true);
    if ($action == 'delete') {
        confirm_form();
    } else if ($action == 'confirm') {
        do_delete_profile($user, TECHNICIAN);
    } else {
        tech_form($user);
    }
}
?>
