<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// make some random simulated users

function rnd_subset($list) {
    $x = array();
    $n = random_int(1,3);
    $list2 = array_keys($list);
    shuffle($list2);
    for ($i=0; $i<$n; $i++) {
        $x[] = $list2[$i];
    }
    return $x;
}

function rnd_perf() {
    global $inst_list_perf, $style_list, $level_list;
    $x = new StdClass;
    $x->inst = rnd_subset($inst_list_perf);
    $x->style = rnd_subset($style_list);
    $x->level = rnd_subset($level_list);
    return $x;
}

function rnd_comp() {
    global $inst_list_comp, $style_list, $level_list;
    $x = new StdClass;
    $x->inst = rnd_subset($inst_list_comp);
    $x->style = rnd_subset($style_list);
    $x->level = rnd_subset($level_list);
    return $x;
}

function make_users() {
    $max_id = 99729;
    $n = 100;

    for ($i=$max_id-100; $i<=$max_id; $i++) {
        $x = random_int(1,10);
        if ($x < 5) {
            write_profile($i, rnd_comp(), COMPOSER);
        } else if ($x < 9) {
            write_profile($i, rnd_perf(), PERFORMER);
        } else {
            write_profile($i, rnd_comp(), COMPOSER);
            write_profile($i, rnd_perf(), PERFORMER);
        }
    }
}

make_users();


?>
