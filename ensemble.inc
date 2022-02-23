<?php

require_once("../inc/mm_db.inc");

// show ensembles the user either owns or belongs to

function show_ensembles($user) {
    $ensembles_own = Ensemble::enum("user_id = $user->id");
    $ensembles_belong = EnsembleMember::enum("user_id = $user->id");
    if (!$ensembles_own && !$ensembles_belong) return;
    start_table("table-striped");
    foreach ($ensembles_own as $e) {
        $ens_info = read_profile($e->id, ENSEMBLE);
        row2($ens_info->name,
            "<a href=ensemble_edit.php?ens_id=$e->id>edit</a>"
        );
    }
    end_table();
}

function ens_profile_summary_header() {
    row_heading_array(array(
        "Name", "Type", "Instruments"
    ));
}

function ens_profile_summary_row($e) {
    $p = $e->profile;
    row_array(array(
        $p->name,
        ENSEMBLE_TYPE[$p->type],
        lists_to_string(INST_LIST_FINE, $p->inst, $p->inst_custom, "<br>")
    ));
}

?>
