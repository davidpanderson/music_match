<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");

// create/edit ensembles

function ensemble_form($ens, $ens_id, $create) {
    page_head($create?"Add ensemble":"Edit ensemble");
    form_start("ensemble_edit.php", "POST", 'ENCTYPE="multipart/form-data"');
    if ($ens_id) {
        form_input_hidden('ens_id', $ens_id);
    }

    // name
    form_input_text('Ensemble name', 'name', $ens->name);

    // ensemble type
    $list = radio_list(ENSEMBLE_TYPE_LIST);
    if (!$ens->type || array_key_exists($ens->type, ENSEMBLE_TYPE_LIST)) {
        $list[] = array('custom',
            sprintf('<input name=type_custom %s value="%s" size="20">',
                text_input_default(ENSEMBLE_TYPE_ADD),
                ENSEMBLE_TYPE_ADD
            )
        );
        $selected = $ens->type;
    } else {
        $list[] = array('custom',
            sprintf('<input name=type_custom value="%s" size="20">',
                $ens->type
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
            items_list(INST_LIST_FINE, $ens->inst, "inst"),
            items_custom($ens->inst_custom, "inst_custom")
        )
    );
    form_input_text('', 'inst_custom_new', INST_ADD, 'text',
        text_input_default(INST_ADD).'class="sm" size="20"'
    );

    // styles
    form_checkboxes(
        "Styles",
        array_merge(
            items_list(STYLE_LIST, $ens->style, "style"),
            items_custom($ens->style_custom, "style_custom")
        )
    );
    form_input_text(
        '', 'style_custom_new', STYLE_ADD, 'text',
        text_input_default(STYLE_ADD).'class="sm" size="20"'
    );

    // tech level
    form_checkboxes(
        "Technical levels",
        items_list(LEVEL_LIST, $ens->level, "level")
    );

    // intro
    form_input_textarea('Description', 'description', $ens->description);

    // audio sig
    $sig_title = "Audio signature MP3<br><small>A short, representative example of the ensemble's playing.<br>Max size 128 MB.</small>";

    if ($ens->signature_filename) {
        form_checkboxes($sig_title,
            array(array(
                "signature_check",
                sprintf('<a href=%s/%d.mp3>%s</a>',
                    role_dir(ENSEMBLE), $ens_id, $ens->signature_filename
                ),
                true
            ))
        );
    } else {
        form_general($sig_title, '<input name=signature_add type=file>');
    }

    // looking for members?
    form_checkboxes('Is the ensemble seeking new members?',
        array(array('seeking_members', '', $ens->seeking_members))
    );


    // perf reg?
    form_checkboxes('Does the ensemble perform regularly?',
        array(array('perf_reg', '', $ens->perf_reg))
    );

    // money?
    form_checkboxes('Does the ensemble typically get paid to perform?',
        array(array('perf_paid', '', $ens->perf_paid))
    );

    if ($create) {
        form_submit("Add", 'name=submit value=on');
    } else {
        form_submit("Update", 'name=submit value=on');
    }
    form_end();
    echo "<p>
        <a href=ensemble_edit.php?ens_id=$ens_id&action=delete>Delete ensemble</a>
        <p>
    ";
    show_button('mm_home.php', 'Return to home page', null, 'btn-primary');
    page_tail();
}

function ensemble_action($profile, $ens_id) {
    $profile2 = new StdClass;

    // If a radio button is checked, that's the type

    $t = post_str('type', true);
    $tc = strip_tags(post_str('type_custom'));
    if (!$t) {
        if ($tc) {
            $t = $tc;
        } else {
            error_page("You must specify an ensemble type.");
        }
    }
    $profile2->type = $t;
    $profile2->name = strip_tags(post_str('name'));
    if (!$profile2->name) {
        error_page("You must provide an ensemble name");
    }
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
    echo "<p>";
    mm_show_button("mm_home.php", "Return to home page", BUTTON_NORMAL);
    page_tail();
}

function do_delete_ensemble($ens, $ens_info) {
    page_head("Ensemble deleted");
    EnsembleMember::delete("ens_id = $ens->id");
    delete_mm_profile($ens->id, ENSEMBLE);
    $ens->delete();
    echo sprintf("The ensemble '%s' has been deleted.", $ens_info->name);
    mm_show_button("mm_home.php", "Return to home page");
    page_tail();
}

$user = get_logged_in_user();
if (post_str('submit', true)) {
    $ens_id = post_int('ens_id', true);
    if ($ens_id) {
        // update existing
        $ens = Ensemble::lookup_id($ens_id);
        if (!$ens || $ens->user_id != $user->id) {
            error_page("not owner");
        }
    } else {
        // create new
        $ens_id = Ensemble::insert(
            sprintf("(create_time, user_id) value (%f, %d)",
                time(), $user->id
            )
        );
        if (!$ens_id) {
            error_page("Couldn't create ensemble");
        }
    }
    $profile = read_profile($ens_id, ENSEMBLE);
    $profile = ensemble_action($profile, $ens_id);
    write_profile($ens_id, $profile, ENSEMBLE);
    Header("Location: ensemble_edit.php?ens_id=$ens_id");
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
            ensemble_form($ens_info, $ens_id, false);
        }
    } else {
        // create new ensemble
        //
        $ens_info = read_profile(0, ENSEMBLE);
        ensemble_form($ens_info, 0, true);
    }
}

?>
