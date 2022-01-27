<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// user home page

function left() {
    global $user;
    echo "<h3>Your composer profile:</h3>";
    if (profile_exists($user->id, COMPOSER)) {
        $profile = read_profile($user->id, COMPOSER);
        start_table();
        echo profile_summary_table($user, $profile, COMPOSER);
        echo "<tr><td> </td><td>";
        show_button_small("profile.php?comp=1", "Edit composer profile");
        echo "</td></tr>";
        end_table();
    } else {
        show_button_small("profile.php?comp=1", "Create composer profile");
    }

    echo "<h3>Your performer profile:</h3>";

    if (profile_exists($user->id, PERFORMER)) {
        $profile = read_profile($user->id, PERFORMER);
        start_table();
        echo profile_summary_table($user, $profile, PERFORMER);
        echo "<tr><td> </td><td>";
        show_button_small("profile.php?comp=0", "Edit performer profile");
        echo "</td></tr>";
        end_table();
    } else {
        show_button_small("profile.php?comp=0", "Create performer profile");
    }
}

function right() {
    global $user;
    echo "<h3>Community</h3>";
    start_table();
    show_community_private($user);
    end_table();
}

function top() {
    global $user;
    show_button("mm_search.php?comp=1", "Find composers", null, "btn-success btn-lg");
    echo "&nbsp;&nbsp;";
    show_button("mm_search.php?comp=0", "Find performers", null, "btn-success btn-lg");
}

function home_page($user) {
    page_head("");

    grid('top', 'left', 'right', 6);


    page_tail();
}

$user = get_logged_in_user();
BoincForumPrefs::lookup($user);


home_page($user);
?>
