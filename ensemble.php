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

// ensemble page

require_once("../inc/mm_util.inc");
require_once("../inc/mm.inc");
require_once("../inc/ensemble.inc");
require_once("../inc/mm_db.inc");

function show_ensemble($ens_id, $user) {
    $profile = read_profile($ens_id, ENSEMBLE);
    $ens = Ensemble::lookup_id($ens_id);
    page_head(sprintf("Ensemble: %s", $ens->name));
    start_table();
    if (array_key_exists($profile->type, ENSEMBLE_TYPE_LIST)) {
        $t = ENSEMBLE_TYPE_LIST[$profile->type];
    } else {
        $t = $profile->type;
    }
    row2("Ensemble type", $t);
    row2("Instruments",
        lists_to_string(
            INST_LIST_FINE, $profile->inst, $profile->inst_custom
        )
    );
    row2("Styles", 
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    row2("Levels", 
        lists_to_string(LEVEL_LIST, $profile->level)
    );

    if ($profile->link) {
        row2("Links", links_to_string($profile->link));
    }

    $founder = BoincUser::lookup_id($ens->user_id);
    row2("Founder",
        "<a href=mm_user.php?user_id=$founder->id>$founder->name</a>"
    );

    $x = ens_members_string($ens->id);
    if ($x) {
        row2("Other members", $x);
    }

    row2("Description", $profile->description);
    $x = sprintf("Performs regularly: %s<br>Typically paid to perform: %s",
        $profile->perf_reg?"yes":"no",
        $profile->perf_paid?"yes":"no"
    );

    if ($profile->signature_filename) {
        row2('Audio signature',
            sprintf('<a href=%s/%d.mp3>%s</a>',
                role_dir(ENSEMBLE), $ens_id, $profile->signature_filename
            )
        );
    }

    row2("Performance", "$x");

    if ($ens->user_id == $user->id) {
        // founder
        $x = $profile->seeking_members?"Seeking new members":"Not seeking new members";
    } else {
        $em = EnsembleMember::lookup("user_id=$user->id and ensemble_id=$ens_id");
        if ($em) {
            $x = em_status_string($em->status);
        } else {
            if ($profile->seeking_members) {
                $x = "<a href=ensemble_join.php?ens_id=$ens_id>Request membership</a>";
            } else {
                $x = "Not seeking new members";
            }
        }
    }
    row2("Membership", $x);
    if ($user->id == $ens->user_id) {
        row2('', mm_button_text("ensemble_edit.php?ens_id=$ens_id", "Edit ensemble"));
    }

    end_table();
    home_button();
    page_tail();
}

$user = get_logged_in_user();
$id = get_int('ens_id');
show_ensemble($id, $user);

?>
