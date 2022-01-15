<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// show info on another user

function show_user($user_id) {
    $user = BoincUser::lookup_id($user_id);
    if (!$user) error_page("No such user");

    page_head($user->name);

    if (profile_exists($user_id, COMPOSER)) {
        echo "<h3>Composer profile</h3>";
        $profile = read_profile($user_id, COMPOSER);
        echo profile_summary($user, $profile, COMPOSER);
    }

    if (profile_exists($user_id, PERFORMER)) {
        echo "<h3>Performer profile</h3>";
        $profile = read_profile($user_id, PERFORMER);
        echo profile_summary($user, $profile, PERFORMER);
    }

    // links to message, friend

    page_tail();
}

$user_id = get_int("user_id");
show_user($user_id);

?>
