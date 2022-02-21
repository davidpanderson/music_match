<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// ensemble page

function show_ensemble($id) {
    $profile = read_profile($id, ENSEMBLE);
    page_head(sprintf("Ensemble: %s", $profile['name']));
    // type, instruments, styles, level, links

    // join
    page_tail();
}

$id = get_int('id');
show_ensemble($id);

?>
