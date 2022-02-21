<?php

function tech_summary_table($user, $profile) {
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

?>
