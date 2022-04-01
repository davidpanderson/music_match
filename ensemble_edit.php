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

// create/edit ensembles

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");

// $ens is non-null if editing

function ensemble_form($ens, $ens_info) {
    page_head($ens?"Edit ensemble":"Add ensemble");
    form_start("ensemble_edit.php", "POST", 'ENCTYPE="multipart/form-data"');
    if ($ens) {
        form_input_hidden('ens_id', $ens->id);
    }

    // name
    form_input_text('Ensemble name', 'name', $ens?$ens->name:'');

    // ensemble type
    $list = radio_list(ENSEMBLE_TYPE_LIST);
    if (!$ens_info->type || array_key_exists($ens_info->type, ENSEMBLE_TYPE_LIST)) {
        $list[] = array('custom',
            sprintf('<input name=type_custom %s value="%s" size="20">',
                text_input_default(ENSEMBLE_TYPE_ADD),
                ENSEMBLE_TYPE_ADD
            )
        );
        $selected = $ens_info->type;
    } else {
        $list[] = array('custom',
            sprintf('<input name=type_custom value="%s" size="20">',
                $ens_info->type
            )
        );
        $selected = "custom";
    }
    form_radio_buttons(
        "Ensemble type",
        "type",
        $list,
        $selected
    );

    // instruments
    form_checkboxes(
        "Instruments in the ensemble",
        array_merge(
            items_list(INST_LIST_FINE, $ens_info->inst, "inst"),
            items_custom($ens_info->inst_custom, "inst_custom")
        )
    );
    form_input_text('', 'inst_custom_new', INST_ADD, 'text',
        text_input_default(INST_ADD).'class="sm" size="20"'
    );

    // styles
    form_checkboxes(
        "Styles",
        array_merge(
            items_list(STYLE_LIST, $ens_info->style, "style"),
            items_custom($ens_info->style_custom, "style_custom")
        )
    );
    form_input_text(
        '', 'style_custom_new', STYLE_ADD, 'text',
        text_input_default(STYLE_ADD).'class="sm" size="20"'
    );

    // tech level
    form_checkboxes(
        "Technical levels",
        items_list(LEVEL_LIST, $ens_info->level, "level")
    );

    // intro
    form_input_textarea('Description', 'description', $ens_info->description);

    // audio sig
    $sig_title = "Audio signature MP3<br><small>A short, representative example of the ensemble's playing.<br>Max size 128 MB.</small>";

    if ($ens_info->signature_filename) {
        form_checkboxes($sig_title,
            array(array(
                "signature_check",
                sprintf('<a href=%s/%d.mp3>%s</a>',
                    role_dir(ENSEMBLE), $ens->id, $ens_info->signature_filename
                ),
                true
            ))
        );
    } else {
        form_general($sig_title, '<input name=signature_add type=file>');
    }

    // looking for members?
    form_checkboxes('The ensemble is seeking new members',
        array(array('seeking_members', '', $ens_info->seeking_members))
    );


    // perf reg?
    form_checkboxes('The ensemble regularly performs for an audience',
        array(array('perf_reg', '', $ens_info->perf_reg))
    );

    // money?
    form_checkboxes('The ensemble usually gets paid to perform',
        array(array('perf_paid', '', $ens_info->perf_paid))
    );

    if ($ens) {
        form_submit("Update", 'name=submit value=on');
    } else {
        form_submit("Add", 'name=submit value=on');
    }
    form_end();
    if ($ens) {
        echo "<p>
            <a href=ensemble_edit.php?ens_id=$ens->id&action=delete>Delete ensemble</a>
            <p>
        ";
    }
    page_tail();
}

function ensemble_action($profile, $ens_id) {
    $profile2 = new StdClass;

    // If a radio button is checked, that's the type

    $t = post_str('type', true);
    if ($t == 'custom') {
        $t = strip_tags(post_str('type_custom'));
    }

    if (!$t) {
        error_page("You must specify an ensemble type.");
    }
    $profile2->type = $t;
    $profile2->inst = parse_list(INST_LIST_FINE, "inst");
    $profile2->inst_custom = parse_custom(
        $profile->inst_custom, "inst_custom", INST_ADD
    );
    $profile2->style = parse_list(STYLE_LIST, "style");
    $profile2->style_custom = parse_custom(
        $profile->style_custom, "style_custom", STYLE_ADD
    );
    $profile2->level = parse_list(LEVEL_LIST, "level");
    $profile2->description = strip_tags(post_str('description'));
    $profile2->seeking_members = parse_post_bool('seeking_members');
    $profile2->perf_reg = parse_post_bool('perf_reg');
    $profile2->perf_paid = parse_post_bool('perf_paid');
    $profile2 = handle_audio_signature_upload(
        $profile, $profile2, ENSEMBLE, $ens_id
    );
    return $profile2;
}

function confirm_form($ens, $ens_info) {
    page_head('Confirm delete ensemble');
    echo sprintf('<p>Are you sure you want to delete %s?',
        $ens_info->name
    );
    echo "<p>";
    mm_show_button("ensemble_edit.php?action=confirm&ens_id=$ens->id",
        "Delete ensemble",
        BUTTON_DANGER
    );
    page_tail();
}

function do_delete_ensemble($ens, $ens_info) {
    page_head("Ensemble deleted");
    EnsembleMember::delete("ens_id = $ens->id");
    delete_mm_profile($ens->id, ENSEMBLE);
    $ens->delete();
    echo sprintf("The ensemble '%s' has been deleted.", $ens_info->name);
    page_tail();
}

$user = get_logged_in_user();
update_visit_time($user);
if (post_str('submit', true)) {
    $ens_id = post_int('ens_id', true);
    $name = strip_tags(post_str('name'));
    if (!$name) {
        error_page("You must provide an ensemble name");
    }
    if ($ens_id) {
        // update existing
        $ens = Ensemble::lookup_id($ens_id);
        if (!$ens || $ens->user_id != $user->id) {
            error_page("not owner");
        }
        if ($ens->name != $name) {
            if (Ensemble::lookup("name='$name'")) {
                error_page("The name $name is already in use.");
            }
        }
        $ens->update("name='$name'");
    } else {
        // create new
        if (Ensemble::lookup("name='$name'")) {
            error_page("The name $name is already in use.");
        }
        $ens_id = Ensemble::insert(
            sprintf("(create_time, user_id, name) value (%f, %d, '%s')",
                time(), $user->id, $name
            )
        );
        if (!$ens_id) {
            error_page("Couldn't create ensemble");
        }
    }
    $profile = read_profile($ens_id, ENSEMBLE);
    $profile = ensemble_action($profile, $ens_id);
    write_profile($ens_id, $profile, ENSEMBLE);
    Header("Location: ensemble.php?ens_id=$ens_id");
} else {
    $ens_id = get_int('ens_id', true);
    if ($ens_id) {
        // edit existing ensemble
        //
        $ens = Ensemble::lookup_id($ens_id);
        if ($ens->user_id != $user->id) {
            error_page("not owner");
        }
        $ens_info = read_profile($ens_id, ENSEMBLE);
        $action = get_str('action', true);
        if ($action == 'delete') {
            confirm_form($ens, $ens_info);
        } else if ($action == 'confirm') {
            do_delete_ensemble($ens, $ens_info);
        } else {
            ensemble_form($ens, $ens_info, $ens_id, false);
        }
    } else {
        // create new ensemble
        //
        $ens_info = read_profile(0, ENSEMBLE);
        ensemble_form(null, $ens_info, 0, true);
    }
}

?>
