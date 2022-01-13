<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// create/edit a composer profile

// ---------------- form ------------------

function composer_form($profile) {
    global $inst_list_comp, $style_list, $level_list;
    page_head("Composer profile");
    form_start("comp_profile.php", "POST", 'ENCTYPE="multipart/form-data"');
    form_checkboxes(
        "<span>Instruments you write for:</span>",
        array_merge(
            items_list($inst_list_comp, $profile->inst, "inst"),
            items_custom($profile->inst_custom, "inst_custom")
        )
    );
    form_input_text('', 'inst_custom_new', 'Other', 'text', 'class="sm" size="20"', '');
    echo "<hr>";

    form_checkboxes(
        "Styles you write in:",
        array_merge(
            items_list($style_list, $profile->style, "style"),
            items_custom($profile->style_custom, "style_custom")
        )
    );
    form_input_text('', 'style_custom_new', 'Other', 'text', 'class="sm" size="20"', '');

    echo "<hr>";

    form_checkboxes(
        "Technical levels you write for:",
        items_list($level_list, $profile->level, "level")
    );
    echo "<hr>";

    $x = "Composers/musicians who influence your work:";
    if ($profile->influence) {
        form_checkboxes(
            $x,
            items_custom($profile->influence, "influence")
        );
        form_input_text('', 'influence_new', 'Add', 'text', 'class="sm" size="20"', '');
    } else {
        form_input_text($x, 'influence_new', 'Add', 'text', 'class="sm" size="20"', '');
    }

    echo "<hr>";
    form_general('Audio signature MP3<br><small>A short, representative example of your composition.<br>Max size 128 MB.</small>',
        '<input name=signature type=file>'
    );
    echo "<hr>";
    form_general('Links<br><small>... to web pages with examples of your work.</small>',
        '<input type=text size=30 value=URL> &nbsp;&nbsp;&nbsp; <input type=text size=40 value=description>'
    );


    form_submit("Update", 'name=submit value=on');
    form_end();

    page_tail();
}

// ------------ handle submitted form ---------------

function composer_action($user_id, $profile) {
    global $inst_list_comp, $style_list, $level_list;

    $profile2 = new StdClass;
    $profile2->inst = parse_list($inst_list_comp, "inst");
    $profile2->inst_custom = parse_custom($profile->inst_custom, "inst_custom", "Other");
    $profile2->style = parse_list($style_list, "style");
    $profile2->style_custom = parse_custom($profile->style_custom, "style_custom", "Other");
    $profile2->level = parse_list($level_list, "level");
    $profile2->influence = parse_custom($profile->influence, "influence", "Add");

    //print_r($_FILES); exit;
    $sig_file = $_FILES['signature'];
    $sig_name = $sig_file['tmp_name'];
    $orig_name = $sig_file['name'];
    if ($orig_name) {
        if (is_uploaded_file($sig_name)) {
            if (!str_ends_with(strtolower($orig_name), ".mp3")) {
                error_page("$orig_name is not an MP3 file.");
            }
            // check if it's actully an MP3 file?
            $new_name = sprintf('composer/%d.mp3', $user_id);
            if (!move_uploaded_file($sig_name, $new_name)) {
                error_page("Couldn't move uploaded file.");
            }
            $profile2->signature_filename = $orig_name;
        } else {
            error_page("Couldn't upload $sig_name; it may be too large.");
        }
    }
    return $profile2;
}

//$user = get_logged_in_user();
$user = BOINCUser::lookup_id(1);

if (post_str('submit', true)) {
    $profile = read_profile($user->id, COMPOSER);
    $profile = composer_action($user->id, $profile);
    write_profile($user->id, $profile, COMPOSER);
    Header("Location: comp_profile.php");
} else {
    $profile = read_profile($user->id, COMPOSER);
    composer_form($profile);
}

?>
