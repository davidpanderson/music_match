<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// user home page

function home_page($user) {
    page_head("Home page");
    echo "<p>";
    if (profile_exists($user, true)) {
        echo "<a href=comp_profile.php>Edit your composer profile</a>";
    } else {
        echo "<a href=comp_profile.php>Create a composer profile</a>";
    }

    echo "<p>";
    if (profile_exists($user, false)) {
        echo "<a href=perf_profile.php>Edit your performer profile</a>";
    } else {
        echo "<a href=perf_profile.php>Create a performer profile</a>";
    }

    echo "<p><a href=mm_search.php?comp=1>Search for composers</a>";
    echo "<p><a href=mm_search.php>Search for performers</a>";


    // friend/message stuff
    page_tail();
}

$user = get_logged_in_user();

home_page($user);
?>
