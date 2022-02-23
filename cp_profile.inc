<?php

// display composer/performer profiles
// These are sufficiently similar that we use the same functions
// with a "role" argument.

// show profile as a list of name: value lines
//
function cp_profile_summary($user, $profile, $role) {
    $x = sprintf('Instruments: %s',
        lists_to_string(
            $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, $profile->inst_custom
        )
    );
    $x .= sprintf('<br>Styles: %s',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    $x .= sprintf('<br>Levels: %s',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    if ($role==COMPOSER && $profile->influence) {
        $x .= "<br>Influences: ".implode(", ", $profile->influence);
    }
    if ($profile->link) {
        $x .= "<br>Links: ".links_to_string($profile);
    }
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        $x .= sprintf('<br>Country: %s', country_distance($user));
    }
    return $x;
}

function cp_profile_summary_table($user, $profile, $role) {
    row2('Instruments',
        lists_to_string(
            $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, $profile->inst_custom
        )
    );
    row2('Styles',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    row2('Levels',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    if ($role==COMPOSER && $profile->influence) {
        row2('Influences',
            implode(", ", $profile->influence)
        );
    }
    if ($profile->link) {
        row2('Links',
            links_to_string($profile)
        );
    }
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        row2('Country',
            country_distance($user)
        );
    }
}


// name, with link and audio mouseover
//
function name_link($user, $profile) {
    $audio = "";
    if ($profile->signature_filename) {
        $audio = sprintf(' onmouseenter="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $user->id, $user->id
        );
    }
    return sprintf('<a %s href=mm_user.php?user_id=%d>%s</a>',
        $audio,
        $user->id, $user->name
    );
}

// show a 2-column table row summarizing a composer or performer profile
//
function show_profile_2col($user, $profile, $role) {
    $x1 = name_link($user, $profile);
    $x2 = profile_summary($user, $profile, $role);
    if (DEBUG) {
        $x2 .= sprintf('<br>match: %d (%d, %d, %d)',
            $profile->value,
            $profile->match->inst,
            $profile->match->style,
            $profile->match->level
        );
    }
    row2($x1, $x2);
}

// show profile as an N-column table row
//
function cp_profile_summary_row($user, $profile, $role) {
    $x = array();
    $x[] = name_link($user, $profile);
    $x[] = lists_to_string(
        $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
        $profile->inst, $profile->inst_custom, "<br>"
    );
    $x[] = lists_to_string(
        STYLE_LIST, $profile->style, $profile->style_custom, "<br>"
    );
    $x[] = lists_to_string(LEVEL_LIST, $profile->level, null, "<br>");
    if ($role==COMPOSER) {
        if ($profile->influence) {
            $x[] = implode("<br>", $profile->influence);
        } else {
            $x[] = '';
        }
    }
    $x[] = links_to_string($profile, "<br>");
    $x[] = country_distance($user, "<br>");
    row_array($x);
}

// table header for the above
//
function cp_profile_summary_header($name_header, $role) {
    $x= array($name_header, "Instruments", "Styles", "Level");
    if ($role==COMPOSER) $x[] = "Influences";
    $x[] = "Links";
    $x[] = "Country";
    row_heading_array($x);
}

?>
