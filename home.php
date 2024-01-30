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
require_once("../inc/teacher.inc");
require_once("../inc/tech.inc");
require_once("../inc/ensemble.inc");
require_once("../inc/notification.inc");

$show_home_link = false;

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
    echo "<font size=+3>My profiles</font>
        <h3>Composer</h3>
    ";
    start_table();
    if (profile_exists($user->id, COMPOSER)) {
        $profile = read_profile($user->id, COMPOSER);
        echo cp_profile_summary_table($user, $profile, COMPOSER);
        row2('',
            mm_button_text(
                sprintf("cp_profile_edit.php?role=%d", COMPOSER),
                "Edit composer profile", BUTTON_SMALL
            )
        );
    } else {
        row2('',
            mm_button_text(
                sprintf("cp_profile_edit.php?role=%d", COMPOSER),
                "Create composer profile", BUTTON_SMALL
            )
        );
    }
    end_table();

    echo "<h3>Performer</h3>";

    start_table();
    if (profile_exists($user->id, PERFORMER)) {
        $profile = read_profile($user->id, PERFORMER);
        echo cp_profile_summary_table($user, $profile, PERFORMER);
        row2('',
            mm_button_text(
                sprintf("cp_profile_edit.php?role=%d", PERFORMER),
                "Edit performer profile", BUTTON_SMALL
            )
        );
    } else {
        row2('',
            mm_show_button(
                sprintf("cp_profile_edit.php?role=%d", PERFORMER),
                "Create performer profile", BUTTON_SMALL
            )
        );
    }
    end_table();

    echo "<h3>Technician</h3>";
    start_table();
    if (profile_exists($user->id, TECHNICIAN)) {
        $profile = read_profile($user->id, TECHNICIAN);
        echo tech_profile_summary_table($user, $profile);
        row2('',
            mm_button_text(
                sprintf("tech_profile_edit.php", PERFORMER),
                "Edit technician profile", BUTTON_SMALL
            )
        );
    } else {
        row2('',
            mm_show_button(
                "tech_profile_edit.php",
                "Create technician profile"
            )
        );
    }
    end_table();

    echo "<h3>Teacher</h3>";
    start_table();
    if (profile_exists($user->id, TEACHER)) {
        $profile = read_profile($user->id, TEACHER);
        echo teacher_profile_summary_table($user, $profile);
        row2('',
            mm_button_text(
                sprintf("teacher_edit.php"),
                "Edit teacher profile", BUTTON_SMALL
            )
        );
    } else {
        row2('',
            mm_button_text(
                "teacher_edit.php",
                "Create teacher profile", BUTTON_SMALL
            )
        );
    }
    end_table();
}

function show_ens() {
    global $user;
    echo "<h3>My ensembles</h3>";
    start_table();
    show_ensembles($user);
    row2('',
        mm_button_text(
            "ensemble_edit.php", "Add ensemble", BUTTON_SMALL
        )
    );
    end_table();
}

function show_search() {
    echo "<h3>Search for</h3>";
    echo "<table><tr><td align=center width=18%>";
    echo '<p><img width=80% src=comp.png alt="Picture of a musical score"><p>';
    mm_show_button(
        sprintf("cp_search.php?role=%d", COMPOSER),
        "Composers", BUTTON_SMALL
    );
    echo "</td><td align=center width=18%>";
    echo '<p><img width=80% src=perf.png alt="Picture of a violinist"><p>';
    mm_show_button(
        sprintf("cp_search.php?role=%d", PERFORMER),
        "Performers", BUTTON_SMALL
    );
    echo "</td><td align=center width=18%>";
    echo '<p><img width=80% src=tech.png alt="Picture of a mixing board"><p>';
    mm_show_button(
        "tech_search.php",
        "Technicians", BUTTON_SMALL
    );
    echo "</td><td align=center width=18%>";
    echo '<p><img width=80% src=ens.png alt="Picture of an orchestra"><p>';
    mm_show_button(
        "ensemble_search.php",
        "Ensembles", BUTTON_SMALL
    );
    echo "</td><td align=center width=18%>";
    echo '<p><img width=80% src=teach.png alt="Picture of a cello student and teacher"><p>';
    mm_show_button(
        "teacher_search.php",
        "Teachers", BUTTON_SMALL
    );
    echo "
        </td></tr></table>
    ";

    global $user;
    $searches = Search::enum("user_id = $user->id");
    $n = count($searches);
    $nnew = 0;
    foreach ($searches as $s) {
        if ($s->rerun_nnew) {
            $nnew++;
        }
    }
    if ($n) {
        echo "<p><p>Your previous searches:
            <a href=search_list.php>View all $n</a>
        ";
        if ($nnew) {
            echo " &nbsp;&middot;&nbsp; <a href=search_list.php?new=1>View $nnew with new results</a>";
        }
    }
}

function right() {
    global $user;
    echo "<h3>Community</h3>";
    start_table();
    $tot = total_posts($user);
    if ($tot) {
        row2("Message boards",
            sprintf('<a href=%s>%d posts</a>',
                "forum_user_posts.php?userid=$user->id",
                $tot
            )
        );
    }

    row2("Private messages",
        sprintf(
            '<a href=%s>Inbox</a> <small>(%d messages, %d unread)</small><br><a href=%s>Sent</a>',
            'pm.php?action=inbox',
            pm_total($user),
            pm_unread($user),
            'pm.php?action=outbox'
        )
    );
    $following = BoincFriend::enum("user_src=$user->id");
    $x = [];
    foreach ($following as $friend) {
        $fuser = BoincUser::lookup_id($friend->user_dest);
        if (!$fuser) continue;
        $x[] = "<a href=user.php?user_id=$fuser->id>$fuser->name</a>";
    }
    if ($x) {
        row2("I'm following", implode('<br>', $x));
    }

    $followers = BoincFriend::enum("user_dest=$user->id");
    $x = [];
    foreach ($followers as $friend) {
        $fuser = BoincUser::lookup_id($friend->user_src);
        if (!$fuser) continue;
        $x[] = "<a href=user.php?user_id=$fuser->id>$fuser->name</a>";
    }
    if ($x) {
        row2('My followers', implode('<br>', $x));
    }
    if (has_picture($user)) {
        row2('My picture<br><a href=picture.php><font size=-1>edit</font></a>',
            sprintf('<img src=%s width=100>', picture_path($user))
        );
    } else {
        row2('My picture', '<a href=picture.php>Add</a>');
    }
    end_table();

    echo "<h3>Notifications</h3>";
    start_table();
    show_notifications($user);
    end_table();

    echo "<h3>Spread the word</h3>
        <p>
        Help us grow!
        <a href=email.php>Tell your musical friends and colleagues</a>
        about Music Match.
        <p>
    ";
    echo '<table><tr><td><a href="https://twitter.com/music_match_org?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-show-screen-name="false" data-show-count="false">Follow @music_match_org</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script></td>';
    echo "
        <td>&nbsp;&nbsp; </td>
        <td><a href=https://www.facebook.com/Music-matchorg-113556814665297>
        <img style=\"vertical-align:baseline\" height=20 src=fb.png></a>
        </td></tr></table>
    ";
}

function top() {
}

function home_page($user) {
    page_head("Home");
    echo "<hr>";
    grid('top', 'left', 'right', 6);
    page_tail();
}

$user = mm_get_logged_in_user();
update_visit_time($user);
BoincForumPrefs::lookup($user);

home_page($user);
?>
