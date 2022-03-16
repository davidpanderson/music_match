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

// create/edit a composer or performer profile

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// ---------------- form ------------------

function cp_form($user, $profile, $role) {
    page_head($role==COMPOSER?"Composer profile":"Performer profile");
    form_start("cp_profile_edit.php", "POST", 'ENCTYPE="multipart/form-data"');
    form_input_hidden("role", $role);
    form_checkboxes(
        $role==COMPOSER?"Instruments you write for":"Instruments you play",
        array_merge(
            items_list(
                $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
                $profile->inst, "inst"
            ),
            items_custom($profile->inst_custom, "inst_custom")
        )
    );
    form_input_text('', 'inst_custom_new', INST_ADD, 'text',
        text_input_default(INST_ADD).'class="sm" size="20"'
    );

    if ($role==COMPOSER) {
        form_checkboxes(
            "Ensemble types you write for",
            array_merge(
                items_list(
                    ENSEMBLE_TYPE_LIST,
                    $profile->ens_type, "ens_type"
                ),
                items_custom($profile->ens_type_custom, "ens_type_custom")
            )
        );
        form_input_text('', 'ens_type_custom_new', ENSEMBLE_TYPE_ADD, 'text',
            text_input_default(ENSEMBLE_TYPE_ADD).'class="sm" size="20"'
        );
    }

    form_checkboxes(
        $role==COMPOSER?"Styles you write in":"Styles you play",
        array_merge(
            items_list(STYLE_LIST, $profile->style, "style"),
            items_custom($profile->style_custom, "style_custom")
        )
    );
    form_input_text(
        '', 'style_custom_new', STYLE_ADD, 'text',
        text_input_default(STYLE_ADD).'class="sm" size="20"'
    );

    form_checkboxes(
        $role==COMPOSER?"Technical levels you write for":"Technical levels you play",
        items_list(LEVEL_LIST, $profile->level, "level")
    );

    if ($role==COMPOSER) {
        $x = "Composers/musicians who influence your work";
        if ($profile->influence) {
            form_checkboxes(
                $x,
                items_custom($profile->influence, "influence")
            );
            form_input_text(
                '', 'influence_new', INFLUENCE_ADD, 'text',
                text_input_default(INFLUENCE_ADD).'class="sm" size="20"'
            );
        } else {
            form_input_text(
                $x, 'influence_new', INFLUENCE_ADD, 'text',
                text_input_default(INFLUENCE_ADD).'class="sm" size="20"'
            );
        }
    }

    $sig_title = sprintf('Audio signature MP3<br><small>A short, representative example of your %s.<br>Max size 128 MB.</small>',
        $role==COMPOSER?"composition":"playing"
    );
    if ($profile->signature_filename) {
        form_checkboxes($sig_title,
            array(array(
                "signature_check",
                sprintf("<a href=%s/%d.mp3>%s</a>",
                    role_dir($role),
                    $user->id,
                    $profile->signature_filename
                ),
                true
            ))
        );
    } else {
        form_general($sig_title, '<input name=signature_add type=file>');
    }

    // links

    $in_url = sprintf(
        '<input name=link_url size=40 %s value="%s">',
        text_input_default(LINK_ADD_URL), LINK_ADD_URL
    );
    $in_desc = sprintf(
        '<input name=link_desc size=40 %s value="%s">',
        text_input_default(LINK_ADD_DESC), LINK_ADD_DESC
    );
    $title = 'Links<br><small>... to web pages with examples of your work.</small>';
    if ($profile->link) {
        form_checkboxes($title, items_link($profile->link, "link"));
        form_general('', "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    } else {
        form_general($title, "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    }

    form_submit("Update", 'name=submit value=on');
    form_end();

    echo "<p>
        <a href=cp_profile_edit.php?role=$role&action=delete>Delete profile</a>
    ";
    page_tail();
}

// ------------ handle submitted form ---------------

function action($user_id, $profile, $role) {
    $profile2 = new StdClass;
    $profile2->inst = parse_list(
        $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE, "inst"
    );
    $profile2->inst_custom = parse_custom(
        $profile->inst_custom, "inst_custom", INST_ADD
    );

    if ($role==COMPOSER) {
        $profile2->ens_type = parse_list(
            ENSEMBLE_TYPE_LIST, "ens_type"
        );
        $profile2->ens_type_custom = parse_custom(
            $profile->ens_type_custom, "ens_type_custom", ENSEMBLE_TYPE_ADD
        );
    }

    $profile2->style = parse_list(STYLE_LIST, "style");
    $profile2->style_custom = parse_custom(
        $profile->style_custom, "style_custom", STYLE_ADD
    );
    $profile2->level = parse_list(LEVEL_LIST, "level");

    if ($role==COMPOSER) {
        $profile2->influence = parse_custom(
            $profile->influence, "influence", INFLUENCE_ADD
        );
    }

    $profile2 = handle_audio_signature_upload(
        $profile, $profile2, $role, $user_id
    );

    foreach ($profile->link as $i=>$link) {
        if (post_str(sprintf('link_%d', $i), true)) {
            $profile2->link[] = $link;
        }
    }
    $link_url = post_str('link_url');
    if ($link_url != LINK_ADD_URL) {
        if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
            error_page("$link_url is not a valid URL");
        }
        $link_desc = strip_tags(post_str('link_desc'));
        if (!$link_desc || $link_desc == LINK_ADD_DESC) {
            error_page("You must supply a link description");
        }
        $x = new StdClass;
        $x->url = $link_url;
        $x->desc = $link_desc;
        $profile2->link[] = $x;
    }

    return $profile2;
}

function confirm_form($role) {
    page_head('Confirm delete profile');
    echo sprintf('<p>Are you sure you want to delete your %s profile?',
        role_name($role)
    );
    echo "<p>";
    mm_show_button("cp_profile_edit.php?action=confirm&role=$role",
        "Delete profile",
        BUTTON_DANGER
    );
    page_tail();
}

function do_delete_profile($user, $role) {
    page_head("Profile deleted");
    delete_mm_profile($user->id, $role);
    echo sprintf('Your %s profile has been deleted.', role_name($role));
    page_tail();
}

$user = get_logged_in_user();
//$user = BOINCUser::lookup_id(1);

if (post_str('submit', true)) {
    $role = post_int('role');
    $profile = read_profile($user->id, $role);
    $profile = action($user->id, $profile, $role);
    write_profile($user->id, $profile, $role);
    //Header("Location: cp_profile_edit.php?role=$role");
    Header("Location: home.php");
} else {
    $role = get_int('role');
    $action = get_str('action', true);
    if ($action == 'delete') {
        confirm_form($role);
    } else if ($action == 'confirm') {
        do_delete_profile($user, $role);
    } else {
        $profile = read_profile($user->id, $role);
        cp_form($user, $profile, $role);
    }
}

?>
