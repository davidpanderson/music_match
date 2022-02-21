<?php

// create/edit a technician profile

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

define('TECH_AREA_ADD', 'Add area');
define('PROGRAM_ADD', 'Add program');

function tech_form($profile) {
    page_head("Technician profile");
    form_start("tech.php", "POST");
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
    form_submit("Update", 'name=submit value=on');
    form_end();
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

$user = get_logged_in_user();
if (post_str('submit', true)) {
    $profile = read_profile($user->id, TECHNICIAN);
    $profile = tech_action($user->id, $profile);
    write_profile($user->id, $profile, TECHNICIAN);
    Header("Location: tech.php");
} else {
    $profile = read_profile($user->id, TECHNICIAN);
    tech_form($profile);
}
?>
