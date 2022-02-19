<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// user home page

function left() {
    global $user;
    echo "<h3>Composer profile:</h3>";
    if (profile_exists($user->id, COMPOSER)) {
        $profile = read_profile($user->id, COMPOSER);
        start_table('table-striped');
        echo profile_summary_table($user, $profile, COMPOSER);
        echo "<tr><td> </td><td>";
        show_button("profile.php?comp=1", "Edit composer profile", null, "btn-success");
        echo "</td></tr>";
        end_table();
    } else {
        show_button("profile.php?comp=1", "Create composer profile", null, "btn-success");
    }

    echo "<h3>Performer profile:</h3>";

    if (profile_exists($user->id, PERFORMER)) {
        $profile = read_profile($user->id, PERFORMER);
        start_table('table-striped');
        echo profile_summary_table($user, $profile, PERFORMER);
        echo "<tr><td> </td><td>";
        show_button("profile.php?comp=0", "Edit performer profile", null, "btn-success");
        echo "</td></tr>";
        end_table();
    } else {
        show_button("profile.php?comp=0", "Create performer profile", null, "btn-success");
    }

    echo "<h3>Technician profile:</h3>";
    if (profile_exists($user->id, TECHNICIAN)) {
    } else {
        show_button("profile.php?comp=0", "Create technician profile", null, "btn-success");
    }

    echo "<h3>Ensembles</h3>";
    show_button("ensemble.php", "Add ensemble");

    echo "<h3>Search</h3>";
    show_button(
        sprintf("mm_search.php?role=%d", COMPOSER),
        "Find composers", null, "btn-success"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        sprintf("mm_search.php?role=%d", PERFORMER),
        "Find performers", null, "btn-success"
    );
}

function right() {
    global $user;
    echo "<h3>Community</h3>";
    start_table();
    show_community_private($user);
    end_table();
}

function top() {
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
