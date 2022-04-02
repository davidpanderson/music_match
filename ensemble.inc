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

require_once("../inc/mm_db.inc");

// show ensembles the user either owns or belongs to

function show_ensembles($user) {
    $ensembles_founder = Ensemble::enum("user_id = $user->id");
    $ensembles_member = EnsembleMember::enum("user_id = $user->id");
    if (!$ensembles_founder && !$ensembles_member) return;
    foreach ($ensembles_founder as $ens) {
        $ens_info = read_profile($ens->id, ENSEMBLE);
        row2(
            sprintf('<a href=%s>%s</a> (founder)',
                "ensemble.php?ens_id=$ens->id",
                $ens->name
            ),
            mm_button_text("ensemble_edit.php?ens_id=$ens->id", 'Edit', BUTTON_SMALL)
        );
    }
    foreach ($ensembles_member as $em) {
        if ($em->status == EM_REMOVED) continue;
        if ($em->status == EM_DECLINED) continue;
        $ens = Ensemble::lookup_id($em->ensemble_id);
        $ens_info = read_profile($ens->id, ENSEMBLE);
        row2(
            sprintf('<a href=%s>%s</a>',
                "ensemble.php?ens_id=$ens->id",
                $ens->name
            ),
            em_status_string($em->status)
        );
    }
}

function ens_profile_summary_header() {
    enable_audio();
    row_heading_array(array(
        'Name<br><small>click for details',
        music_sample_header(),
        "Type", "Instruments", "Style", "Level", "Founder", "Country"
    ));
}

// name, with link and audio mouseover
//
function ens_listen_link($ens, $profile) {
    $x = '';
    if ($profile->signature_filename) {
        $audio = sprintf(' onclick="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $ens->id, $ens->id
        );
        $x .= sprintf('&nbsp;<a %s href=#><img height=24px src=note.png></a>', $audio);
    }
    return $x;
}

function ens_profile_summary_row($ens) {
    $p = $ens->profile;
    if ($p->signature_filename) {
        echo sprintf('<audio id=a%d><source src="%s/%d.mp3"></source></audio>',
            $ens->id,
            role_dir(ENSEMBLE),
            $ens->id
        );
    }
    $user = $ens->user;
    row_array(array(
        sprintf('<a href=ensemble.php?ens_id=%d title="%s">%s</a>',
            $ens->id,
            $p->description,
            $ens->name
        ),
        ens_listen_link($ens, $p),
        ENSEMBLE_TYPE_LIST[$p->type],
        lists_to_string(INST_LIST_FINE, $p->inst, $p->inst_custom, "<br>"),
        lists_to_string(STYLE_LIST, $p->style, $p->style_custom, "<br>"),
        lists_to_string(LEVEL_LIST, $p->level, null, "<br>"),
        "<a href=user.php?user_id=$user->id>$user->name</a>",
        country_distance($user, $ens->dist, "<br>")
    ));
}

// return members list as links
//
function ens_members_string($ens_id) {
    $ms = EnsembleMember::enum(
        sprintf("ensemble_id=%d and status=%d", $ens_id, EM_APPROVED)
    );
    if (!$ms) return null;
    $x = array();
    foreach ($ms as $m) {
        $u = BoincUser::lookup_id($m->user_id);
        $x[] = "<a href=user.php?user_id=$u->id>$u->name</a>";
    }
    return implode("<br>", $x);
}

?>
