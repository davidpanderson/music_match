<?php

// make some random simulated users

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

$mp3_files = array(
    'bach_babylon.mp3',
    'berio_wasser.mp3',
    'dvorak_waltz.mp3',
    'berg_rain.mp3',
    'building.mp3',
    'mompou_prelude_9.mp3',
);

function rnd_signature($user_id, $is_comp) {
    global $mp3_files;
    $n = count($mp3_files);
    $i = random_int(0, $n-1);
    $cmd = sprintf('cd %s; ln -s ../mp3/%s %d.mp3',
        $is_comp?"composer":"performer",
        $mp3_files[$i],
        $user_id
    );
    system($cmd);
    return $mp3_files[$i];
}

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

function rnd_influence() {
    $y = array("Zappa", "Mahler", "P.D.Q. Bach", "Sorabji");
    shuffle($y);
    $x = array();
    $n = random_int(0,2);
    for ($i=0; $i<$n; $i++) {
        $x[] = $y[$i];
    }
    return $x;
}

function rnd_link() {
    $x = array();
    $n = random_int(0,2);
    for ($i=0; $i<$n; $i++) {
        $y = new StdClass;
        $y->url = "https://google.com/";
        $y->desc = "influence $i";
        $x[] = $y;
    }
    return $x;
}

function rnd_perf($user_id) {
    global $inst_list_perf, $style_list, $level_list;
    $x = new StdClass;
    $x->inst = rnd_subset($inst_list_perf);
    $x->style = rnd_subset($style_list);
    $x->level = rnd_subset($level_list);
    $x->signature_filename = rnd_signature($user_id, PERFORMER);
    $x->link = rnd_link();
    return $x;
}

function rnd_comp($user_id) {
    global $inst_list_comp, $style_list, $level_list;
    $x = new StdClass;
    $x->inst = rnd_subset($inst_list_comp);
    $x->style = rnd_subset($style_list);
    $x->level = rnd_subset($level_list);
    $x->influence = rnd_influence();
    $x->signature_filename = rnd_signature($user_id, COMPOSER);
    $x->link = rnd_link();
    return $x;
}

function make_users() {
    $max_id = 99729;
    $n = 100;

    for ($i=$max_id-100; $i<=$max_id; $i++) {
        $x = random_int(1,10);
        if ($x < 5) {
            write_profile($i, rnd_comp($i), COMPOSER);
        } else if ($x < 9) {
            write_profile($i, rnd_perf($i), PERFORMER);
        } else {
            write_profile($i, rnd_comp($i), COMPOSER);
            write_profile($i, rnd_perf($i), PERFORMER);
        }
    }
}

make_users();


?>
