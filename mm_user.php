<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/cp_profile.inc");
require_once("../inc/tech.inc");

// show info on another user

function left() {
    global $user;

    if (profile_exists($user->id, COMPOSER)) {
        echo "<h3>Composer profile</h3>";
        $profile = read_profile($user->id, COMPOSER);
        start_table();
        echo cp_profile_summary_table($user, $profile, COMPOSER);
        end_table();
    }

    if (profile_exists($user->id, PERFORMER)) {
        echo "<h3>Performer profile</h3>";
        $profile = read_profile($user->id, PERFORMER);
        start_table();
        echo cp_profile_summary_table($user, $profile, PERFORMER);
        end_table();
    }

    if (profile_exists($user->id, TECHNICIAN)) {
        echo "<h3>Technician profile</h3>";
        $profile = read_profile($user->id, TECHNICIAN);
        start_table();
        echo tech_profile_summary_table($user, $profile, PERFORMER);
        end_table();
    }
}

function right() {
    global $user;
    $clo = get_community_links_object($user);
    start_table();
    community_links($clo, get_logged_in_user(true));
    end_table();
}

function show_user($user) {
    page_head($user->name);
    grid(null, 'left', 'right', 6);
    page_tail();
}

$user_id = get_int("user_id");
$user = BoincUser::lookup_id($user_id);
if (!$user) error_page("No such user");

show_user($user);

?>
