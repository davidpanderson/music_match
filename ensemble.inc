<?php

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

function ens_profile_summary_header($name_header) {
    row_heading_array(array(
        $name_header, "Type", "Instruments", "Style", "Level", "Founder", "Country"
    ));
}

// name, with link and audio mouseover
//
function ens_name_link($ens, $profile) {
    $audio = "";
    if ($profile->signature_filename) {
        $audio = sprintf(' onmouseenter="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $ens->id, $ens->id
        );
    }
    return sprintf('<a %s href=ensemble.php?ens_id=%d>%s%s</a>',
        $audio,
        $ens->id, $ens->name,
        $profile->signature_filename?"&nbsp;&nbsp;<img height=24px src=note.png>":""
    );
}
function ens_profile_summary_row($e) {
    $p = $e->profile;
    $user = $e->user;
    row_array(array(
        ens_name_link($e, $p),
        ENSEMBLE_TYPE_LIST[$p->type],
        lists_to_string(INST_LIST_FINE, $p->inst, $p->inst_custom, "<br>"),
        lists_to_string(STYLE_LIST, $p->style, $p->style_custom, "<br>"),
        lists_to_string(LEVEL_LIST, $p->level, null, "<br>"),
        "<a href=mm_user.php?user_id=$user->id>$user->name</a>",
        country_distance($user, $e->dist, "<br>")
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
        $x[] = "<a href=mm_user.php?user_id=$u->id>$u->name</a>";
    }
    return implode("<br>", $x);
}

?>
