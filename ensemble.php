<?php

// ensemble page

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/ensemble.inc");
require_once("../inc/mm_db.inc");

function show_ensemble($id, $user) {
    $profile = read_profile($id, ENSEMBLE);
    $e = Ensemble::lookup_id($id);
    page_head(sprintf("Ensemble: %s", $profile->name));
    start_table();
    row2("Ensemble type", ENSEMBLE_TYPE_LIST[$profile->type]);
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

    $founder = BoincUser::lookup_id($e->user_id);
    row2("Founder",
        "<a href=mm_user.php?user_id=$founder->id>$founder->name</a>"
    );

    $x = ens_members_string($e->id);
    if ($x) {
        row2("Other members", $x);
    }

    $x = sprintf("Performs regularly: %s<br>Typically paid to perform: %s",
        $profile->perf_reg?"yes":"no",
        $profile->perf_paid?"yes":"no"
    );
    row2("Performance", "$x");

    if ($e->user_id == $user->id) {
        // founder
        $x = $profile->seeking_members?"Seeking new members":"Not seeking new members";
    } else {
        $em = EnsembleMember::lookup("user_id=$user->id and ensemble_id=$id");
        if ($em) {
            $x = em_status_string($em->status);
        } else {
            if ($profile->seeking_members) {
                $x = "<a href=ensemble_join.php?ens_id=$e->id>Request membership</a>";
            } else {
                $x = "Not seeking new members";
            }
        }
    }
    row2("Membership", $x);

    end_table();
    page_tail();
}

$user = get_logged_in_user();
$id = get_int('ens_id');
show_ensemble($id, $user);

?>
