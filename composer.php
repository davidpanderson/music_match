<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// create/edit a composer profile

// ---------------- form ------------------

function composer_form($profile) {
    global $inst_list_comp, $style_list, $level_list;
    page_head("Composer profile");
    form_start("composer.php");
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

    form_checkboxes(
        "Composers/musicians who influence your work:",
        items_custom($profile->influence, "influence")
    );
    form_input_text('', 'influence_new', 'Add', 'text', 'class="sm" size="20"', '');

    echo "<hr>";

    form_submit("Update", 'name=submit value=on');
    form_end();

    page_tail();
}

// ------------ handle submitted form ---------------

function composer_action($profile) {
    global $inst_list_comp, $style_list, $level_list;

    $profile = new StdClass;
    $profile->inst = parse_list($inst_list_comp, "inst");
    $profile->inst_custom = parse_custom($profile->inst_custom, "inst_custom", "Other");
    $profile->style = parse_list($style_list, "style");
    $profile->style_custom = parse_custom($profile->style_custom, "style_custom", "Other");
    $profile->level = parse_list($level_list, "level");
    $profile->influence = parse_custom($profile->influence, "influence", "Add");
    return $profile;
}

//$user = get_logged_in_user();
$user = BOINCUser::lookup_id(1);

if (get_str('submit', true)) {
    $profile = read_profile($user->id, COMPOSER);
    $profile = composer_action($profile);
    write_profile($user->id, $profile, COMPOSER);
    Header("Location: composer.php");
} else {
    $profile = read_profile($user->id, COMPOSER);
    composer_form($profile);
}

?>
