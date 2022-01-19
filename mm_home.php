<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// user home page

function home_page($user) {
    page_head("Home");
    echo "<h3>Composer profile:</h3>";
    if (profile_exists($user->id, COMPOSER)) {
        $profile = read_profile($user->id, COMPOSER);
        echo profile_summary($user, $profile, COMPOSER);
        echo "<p><p>";
        show_button_small("profile.php?comp=1", "Edit composer profile");
    } else {
        show_button_small("profile.php?comp=1", "Create composer profile");
    }

    echo "<h3>Performer profile:</h3>";

    if (profile_exists($user->id, PERFORMER)) {
        $profile = read_profile($user->id, PERFORMER);
        echo profile_summary($user, $profile, PERFORMER);
        echo "<p><p>";
        show_button_small("profile.php?comp=0", "Edit performer profile");
    } else {
        show_button_small("profile.php?comp=0", "Create performer profile");
    }

    echo "<hr>";
    echo "<h2>Search</h2>";

    show_button_small("mm_search.php?comp=1", "Find composers");
    echo "&nbsp;&nbsp;";
    show_button_small("mm_search.php?comp=0", "Find performers");


    // friend/message stuff
    page_tail();
}

$user = get_logged_in_user();

home_page($user);
?>
