<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// create/edit a performer profile

// ---------------- form ------------------

function performer_form($profile) {
    global $inst_list_perf, $style_list, $level_list;
    page_head("Performer profile");
    form_start("performer.php");
    form_checkboxes(
        "<span>Instruments you play:</span>",
        array_merge(
            items_list($inst_list_perf, $profile->inst, "inst"),
            items_custom($profile->inst_custom, "inst_custom")
        )
    );
    form_input_text('', 'inst_custom_new', 'Other', 'text', 'class="sm" size="20"', '');
    echo "<hr>";

    form_checkboxes(
        "Styles you play:",
        array_merge(
            items_list($style_list, $profile->style, "style"),
            items_custom($profile->style_custom, "style_custom")
        )
    );
    form_input_text('', 'style_custom_new', 'Other', 'text', 'class="sm" size="20"', '');

    echo "<hr>";

    form_checkboxes(
        "Technical levels you play:",
        items_list($level_list, $profile->level, "level")
    );
    echo "<hr>";

    form_submit("Update", 'name=submit value=on');
    form_end();

    show_button("mm_home.php", "Return to home page");
    page_tail();
}

// ------------ handle submitted form ---------------

function performer_action($profile) {
    global $inst_list_perf, $style_list, $level_list;

    $profile = new StdClass;
    $profile->inst = parse_list($inst_list_perf, "inst");
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
    $profile = read_profile($user->id, PERFORMER);
    $profile = performer_action($profile);
    write_profile($user->id, $profile, PERFORMER);
    Header("Location: performer.php");
} else {
    $profile = read_profile($user->id, PERFORMER);
    performer_form($profile);
}

?>
