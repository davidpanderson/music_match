<?php
require_once("../inc/mm_db.inc");

function main() {
    $searches = Search::enum("id=40");
    foreach ($searches as $search) {
        //if ($search->id != 40) continue;
        $x = json_decode($search->view_results);
        echo "search $search->id\n";
        //$x = array_diff($x, [""]);
        print_r($x);
        $y = json_encode(array_values((array)$x));
        echo "$y\n";
        $search->update("view_results='$y'");
    }
}

main();
?>
