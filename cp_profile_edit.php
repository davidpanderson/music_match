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
require_once("../inc/notification.inc");

// ---------------- form ------------------

function cp_form($user, $profile, $role) {
    page_head($role==COMPOSER?"Composer profile":"Performer profile");
    form_start(
        "cp_profile_edit.php",
        "POST",
        'ENCTYPE="multipart/form-data" name="fname" onsubmit="return validate_link()"'
    );
    form_input_hidden("role", $role);
    if ($role == COMPOSER) {
        form_general("<font size=-1>All fields are optional</font>", '');
        $x = "
            Instruments I write for";
    } else {
        $x = "Instruments I play
            <br><font size=-1>Required; all other fields are optional</font>";
    }
    form_general(
        $x,
        checkbox_table([
            items_list(
                $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
                $profile->inst, "inst"
            ),
            $role==COMPOSER?checkbox_all_none(INST_LIST_COARSE, "inst"):[],
            items_custom($profile->inst_custom, "inst_custom"),
            form_input_text_field('inst_custom_new', INST_ADD, 'text',
                text_input_default(INST_ADD).'class="sm" size="20"'
            )
            ], 3
        )
    );

    if ($role==COMPOSER) {
        form_general(
            'Groups I write for',
            checkbox_table([
                items_list(
                    COMPOSE_FOR_LIST,
                    $profile->ens_type, "ens_type"
                ),
                checkbox_all_none(COMPOSE_FOR_LIST, 'ens_type'),
                items_custom($profile->ens_type_custom, "ens_type_custom"),
                form_input_text_field(
                    'ens_type_custom_new', ENSEMBLE_TYPE_ADD, 'text',
                    text_input_default(ENSEMBLE_TYPE_ADD).'class="sm" size="20"'
                )
                ], 3
            )
        );
    }

    form_general(
        $role==COMPOSER?"Styles I write in":"Styles I play",
        checkbox_table([
            items_list(STYLE_LIST, $profile->style, "style"),
            checkbox_all_none(STYLE_LIST, 'style'),
            items_custom($profile->style_custom, "style_custom"),
            form_input_text_field(
                'style_custom_new', STYLE_ADD, 'text',
                text_input_default(STYLE_ADD).'class="sm" size="20"'
            )
            ], 3
        )
    );

    form_general(
        $role==COMPOSER?"Technical levels I write for":"Technical levels I play",
        checkbox_table([
            items_list(LEVEL_LIST, $profile->level, "level")
            //checkbox_all_none(LEVEL_LIST, 'level'),
            ], 3
        )
    );

    form_input_textarea(
        $role==COMPOSER?
            "Introduction<br>
            <small>Me as a composer: background, influences, etc.</small>"
        :
            "Introduction<br><small>
            Me as a performer: background, favorite composers, etc. </small>"
        ,
        'description',
        $profile->description,
        3
    );

    $sig_title = sprintf('Audio signature MP3<br><small>A representative example of my %s.<br>Max size 128 MB.</small>',
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
    $title = 'Web links<br><font size=-1>... to examples of my work.
        Add as many as you want, one at a time.</font>';
    validate_link_script('fname', 'link_url', 'link_desc');

    if ($profile->link) {
        form_checkboxes($title, items_link($profile->link, "link"));
        form_general('', "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    } else {
        form_general($title, "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    }

    if ($role == COMPOSER) {
        form_checkboxes('I usually get paid to compose',
            array(array('comp_paid', '', $profile->comp_paid))
        );
    }
    if ($role == PERFORMER) {
        form_checkboxes('I regularly perform for audiences',
            array(array('perf_reg', '', $profile->perf_reg))
        );
        form_checkboxes('I usually get paid to perform',
            array(array('perf_paid', '', $profile->perf_paid))
        );
    }

    $have_profile = profile_exists($user->id, $role);
    form_submit($have_profile?"Update profile":"Create profile", 'name=submit value=on');
    form_end();

    if ($have_profile) {
        echo "<p>";
        mm_show_button(
            "cp_profile_edit.php?role=$role&action=delete",
            "Delete profile",
            BUTTON_SMALL
        );
    }
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

    if ($role==PERFORMER) {
        if (!count($profile2->inst) && !count($profile2->inst_custom)) {
            error_page("You must specify at least one instrument.");
        }
    }
    if ($role==COMPOSER) {
        $profile2->ens_type = parse_list(
            COMPOSE_FOR_LIST, "ens_type"
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

    $profile2->description = strip_tags(post_str('description'));

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

    if ($role == COMPOSER) {
        $profile2->comp_paid = parse_post_bool('comp_paid');
    }
    if ($role == PERFORMER) {
        $profile2->perf_reg = parse_post_bool('perf_reg');
        $profile2->perf_paid = parse_post_bool('perf_paid');
    }


    return $profile2;
}

function delete_confirm_form($role) {
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

if (post_str('submit', true)) {
    $role = post_int('role');
    $profile = read_profile($user->id, $role);
    $profile = action($user->id, $profile, $role);
    write_profile($user->id, $profile, $role);
    notify_profile_change($user, $role);
    Header("Location: home.php");
} else {
    $role = get_int('role');
    $action = get_str('action', true);
    if ($action == 'delete') {
        delete_confirm_form($role);
    } else if ($action == 'confirm') {
        do_delete_profile($user, $role);
    } else {
        $profile = read_profile($user->id, $role);
        cp_form($user, $profile, $role);
    }
}

?>
