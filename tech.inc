<?php

function tech_profile_summary_table($user, $profile) {
    row2('Areas',
        lists_to_string(
            TECH_AREA_LIST, $profile->tech_area, $profile->tech_area_custom
        )
    );
    row2('Programs',
        lists_to_string(
            PROGRAM_LIST, $profile->program, $profile->program_custom
        )
    );
}

function tech_summary_header() {
    row_heading_array(array("Name", "Areas", "Programs", "Country"));
}

function tech_summary_row($user, $profile) {
    row_array(array(
        "<a href=mm_user.php?user_id=$user->id>$user->name</a>",
        lists_to_string(
            TECH_AREA_LIST, $profile->tech_area, $profile->tech_area_custom, "<br>"
        ),
        lists_to_string(
            PROGRAM_LIST, $profile->program, $profile->program_custom, "<br>"
        ),
        country_distance($user, $profile->dist, '<br>')
    ));
}

?>
