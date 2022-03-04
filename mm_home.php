<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/cp_profile.inc");
require_once("../inc/tech.inc");
require_once("../inc/ensemble.inc");
require_once("../inc/notification.inc");

// user home page

function left() {
    show_search();
    echo "<hr>";
    show_profiles();
    echo "<hr>";
    show_ens();
}

function show_profiles() {
    global $user;
    echo "<h3>Your profiles</h3>
        <h3>Composer</h3>
    ";
    if (profile_exists($user->id, COMPOSER)) {
        start_table();
        $profile = read_profile($user->id, COMPOSER);
        echo cp_profile_summary_table($user, $profile, COMPOSER);
        row2('',
            button_text(
                sprintf("cp_profile_edit.php?role=%d", COMPOSER),
                "Edit composer profile", null, "btn-primary"
            )
        );
        end_table();
    } else {
        mm_show_button(
            sprintf("cp_profile_edit.php?role=%d", COMPOSER),
            "Create composer profile"
        );
    }

    echo "<h3>Performer</h3>";

    if (profile_exists($user->id, PERFORMER)) {
        $profile = read_profile($user->id, PERFORMER);
        start_table();
        echo cp_profile_summary_table($user, $profile, PERFORMER);
        row2('',
            button_text(
                sprintf("cp_profile_edit.php?role=%d", PERFORMER),
                "Edit performer profile", null, "btn-primary"
            )
        );
        end_table();
    } else {
        mm_show_button(
            sprintf("cp_profile_edit.php?role=%d", PERFORMER),
            "Create performer profile"
        );
    }

    echo "<h3>Technician</h3>";
    if (profile_exists($user->id, TECHNICIAN)) {
        $profile = read_profile($user->id, TECHNICIAN);
        start_table();
        echo tech_profile_summary_table($user, $profile);
        row2('',
            button_text(
                sprintf("tech_profile_edit.php", PERFORMER),
                "Edit technician profile", null, "btn-primary"
            )
        );
        end_table();
    } else {
        mm_show_button(
            "tech_profile_edit.php",
            "Create technician profile"
        );
    }
}

function show_ens() {
    global $user;
    echo "<h3>Your ensembles</h3>";
    start_table();
    show_ensembles($user);
    end_table();
    mm_show_button(
        "ensemble_edit.php", "Add ensemble"
    );
}

function show_search() {
    echo "<h3>Find musicians</h3>";
    show_button(
        sprintf("cp_search.php?role=%d", COMPOSER),
        "Composers", null, "btn-primary"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        sprintf("cp_search.php?role=%d", PERFORMER),
        "Performers", null, "btn-primary"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        "tech_search.php",
        "Technicians", null, "btn-primary"
    );
    echo "&nbsp;&nbsp;";
    show_button(
        "ensemble_search.php",
        "Ensembles", null, "btn-primary"
    );
}

function right() {
    global $user;
    echo "<h3>Community</h3>";
    start_table();
    show_community_private($user);
    end_table();

    echo "<h3>Notifications</h3>";
    start_table();
    show_notifications($user);
    end_table();
}

function top() {
}

function home_page($user) {
    page_head("");

    grid('top', 'left', 'right', 6);


    page_tail();
}

$user = mm_get_logged_in_user();
BoincForumPrefs::lookup($user);

home_page($user);
?>
