<?php
// This file is part of Music Match.
// Copyright (C) 2022 David P. Anderson
//
// Music Match is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// Music Match is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with Music Match.  If not, see <http://www.gnu.org/licenses/>.
// --------------------------------------------------------------------

require_once("../inc/util.inc");
require_once("../inc/forum_db.inc");
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
    row2("Private messages",
        sprintf(
            '<a href=%s>Inbox</a><br><small>%d messages, %d unread</small>',
            'pm.php?action=inbox',
            BoincPrivateMessage::count("userid=$user->id"),
            BoincPrivateMessage::count("userid=$user->id AND opened=0")
        )
    );
    $friends = BoincFriend::enum("user_src=$user->id and reciprocated=1");
    $x = [];
    foreach ($friends as $friend) {
        $fuser = BoincUser::lookup_id($friend->user_dest);
        if (!$fuser) continue;
        $x[] = "<a href=user.php?user_id=$fuser->id>$fuser->name</a>";
    }
    if ($x) {
        row2('Friends', implode($x, '<br>'));
    }
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
