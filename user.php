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

// show info on another user

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/cp_profile.inc");
require_once("../inc/tech.inc");
require_once("../inc/teacher.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/user.inc");

function show_ensembles($user) {
    $es = Ensemble::enum("user_id=$user->id");
    $ems = EnsembleMember::enum(
        sprintf('user_id=%d and status=%d', $user->id, EM_APPROVED)
    );
    if (!$es && !$ems) return;
    echo "<h3>Ensembles</h3>";
    start_table();
    if ($es) {
        $x = array();
        foreach ($es as $e) {
            $x[] = "<a href=ensemble.php?ens_id=$e->id>$e->name</a>";
        }
        row2(
            "Founder of",
            implode($x, '<br>')
        );
    }
    if ($ems) {
        $x = array_map(
            function($em) {
                $e = Ensemble::lookup_id($em->ensemble_id);
                return "<a href=ensemble.php?ens_id=$e->id>$e->name</a>";
            },
            $ems
        );
        row2(
            "Member of",
            implode($x, '<br>')
        );
    }
    end_table();
}

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

    if (profile_exists($user->id, TEACHER)) {
        echo "<h3>Teacher profile</h3>";
        $profile = read_profile($user->id, TEACHER);
        start_table();
        echo teacher_profile_summary_table($user, $profile);
        end_table();
    }


    show_ensembles($user);

}

function right() {
    global $user;
    $u = get_logged_in_user();
    start_table();
    community_links($user, $u);
    if ($u->id != $user->id && $user->country) {
        row2('Country',
            country_distance($user, user_distance($u, $user), '  ')
        );
    }
    row2("Member since", date_str($user->create_time));
    $v = get_visit_time($user);
    if ($v) {
        row2("Last seen", interval_to_str(time() - $v));
    }
    end_table();
}

function show_user($user) {
    page_head($user->name);
    grid(null, 'left', 'right', 6);
    page_tail();
}

$user = get_logged_in_user();
update_visit_time($user);

$user_id = get_int("user_id");
$user = BoincUser::lookup_id($user_id);
if (!$user) error_page("No such user");

show_user($user);

?>
