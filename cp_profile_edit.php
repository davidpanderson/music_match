<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// create/edit a composer or performer profile

// ---------------- form ------------------

function form($profile, $role) {
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
            array(array("signature_check", $profile->signature_filename, true))
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

    show_button('mm_home.php', 'Return to home page', null, 'btn-primary');
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

    if ($profile->signature_filename) {
        if (post_str(sprintf('signature_check'), true)) {
            $profile2->signature_filename = $profile->signature_filename;
        } else {
            $profile2->signature_filename = '';
            // remove MP3 file?
        }
    } else {
        $sig_file = $_FILES['signature_add'];
        $sig_name = $sig_file['tmp_name'];
        $orig_name = $sig_file['name'];
        if ($orig_name) {
            if (is_uploaded_file($sig_name)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if (finfo_file($finfo, $sig_name) != 'audio/mpeg') {
                    error_page("$orig_name is not an MP3 file.");
                }
                $new_name = sprintf('%s/%d.mp3',
                    $role==COMPOSER?"composer":"performer", $user_id
                );
                if (!move_uploaded_file($sig_name, $new_name)) {
                    error_page("Couldn't move uploaded file.");
                }
                $profile2->signature_filename = $orig_name;
            } else {
                error_page("Couldn't upload $orig_name; it may be too large.");
            }
        }
    }

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
        $link_desc = post_str('link_desc');
        if ($link_desc == LINK_ADD_DESC) {
            error_page("You must supply a link description");
        }
        $x = new StdClass;
        $x->url = $link_url;
        $x->desc = $link_desc;
        $profile2->link[] = $x;
    }

    return $profile2;
}

$user = get_logged_in_user();
//$user = BOINCUser::lookup_id(1);

if (post_str('submit', true)) {
    $role = post_int('role');
    $profile = read_profile($user->id, $role);
    $profile = action($user->id, $profile, $role);
    write_profile($user->id, $profile, $role);
    Header("Location: cp_profile_edit.php?role=$role");
} else {
    $role = get_int('role');
    $profile = read_profile($user->id, $role);
    form($profile, $role);
}

?>
