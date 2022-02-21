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

?>
