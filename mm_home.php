<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/cp_profile.inc");
require_once("../inc/tech.inc");
require_once("../inc/ensemble.inc");

// user home page

function left() {
    global $user;
    echo "<h3>Composer profile:</h3>";
    if (profile_exists($user->id, COMPOSER)) {
        $profile = read_profile($user->id, COMPOSER);
        start_table('table-striped');
        echo cp_profile_summary_table($user, $profile, COMPOSER);
        echo "<tr><td> </td><td>";
        show_button(
            sprintf("cp_profile_edit.php?role=%d", COMPOSER),
            "Edit composer profile", null, "btn-primary"
        );
        echo "</td></tr>";
        end_table();
    } else {
        show_button(
            sprintf("cp_profile_edit.php?role=%d", COMPOSER),
            "Create composer profile", null, "btn-primary"
        );
    }

    echo "<h3>Performer profile:</h3>";

    if (profile_exists($user->id, PERFORMER)) {
        $profile = read_profile($user->id, PERFORMER);
        start_table('table-striped');
        echo cp_profile_summary_table($user, $profile, PERFORMER);
        echo "<tr><td> </td><td>";
        show_button(
            sprintf("cp_profile_edit.php?role=%d", PERFORMER),
            "Edit performer profile", null, "btn-primary"
        );
        echo "</td></tr>";
        end_table();
    } else {
        show_button(
            sprintf("cp_profile_edit.php?role=%d", PERFORMER),
            "Create performer profile", null, "btn-primary"
        );
    }

    echo "<h3>Technician profile:</h3>";
    if (profile_exists($user->id, TECHNICIAN)) {
        $profile = read_profile($user->id, TECHNICIAN);
        start_table('table-striped');
        echo tech_profile_summary_table($user, $profile);
        echo "<tr><td> </td><td>";
        show_button(
            sprintf("tech_profile_edit.php", PERFORMER),
            "Edit technician profile", null, "btn-primary"
        );
        echo "</td></tr>";
        end_table();
    } else {
        show_button(
            "tech_profile_edit.php",
            "Create technician profile", null, "btn-primary"
        );
    }

    echo "<h3>Ensembles</h3>";
    show_ensembles($user);
    show_button("ensemble_edit.php", "Add ensemble", null, "btn-primary");

    echo "<h3>Search</h3>";
    show_button(
        sprintf("cp_search.php?role=%d", COMPOSER),
        "Find composers", null, "btn-primary"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        sprintf("cp_search.php?role=%d", PERFORMER),
        "Find performers", null, "btn-primary"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        "tech_search.php",
        "Find technicians", null, "btn-primary"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        "ensemble_search.php",
        "Find ensembles", null, "btn-primary"
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
