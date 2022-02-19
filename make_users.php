<?php

// make some random simulated users

require_once("../inc/util.inc");
require_once("../inc/user_util.inc");
require_once("../inc/zip.inc");
require_once("../inc/mm.inc");

$mp3_files = array(
    'bach_babylon.mp3',
    'berio_wasser.mp3',
    'dvorak_waltz.mp3',
    'berg_rain.mp3',
    'building.mp3',
    'mompou_prelude_9.mp3',
);

function rnd_signature($user_id, $role) {
    global $mp3_files;
    $n = count($mp3_files);
    $i = random_int(0, $n-1);
    $cmd = sprintf('cd %s; ln -s ../mp3/%s %d.mp3',
        role_dir($role),
        $mp3_files[$i],
        $user_id
    );
    system($cmd);
    return $mp3_files[$i];
}

function rnd_subset($list) {
    $x = array();
    $n = random_int(1,3);
    $keys = array_keys($list);
    shuffle($keys);
    $keys = array_slice($keys, 0, $n);
    foreach ($list as $key=>$val) {
        if (in_array($key, $keys)) {
            $x[] = $key;
        }
    }
    return $x;
}

$influences = array("Zappa", "Mahler", "P.D.Q. Bach", "Sorabji");

function rnd_influence() {
    global $influences;
    $y = $influences;
    shuffle($y);
    $x = array();
    $n = random_int(0,2);
    for ($i=0; $i<$n; $i++) {
        $x[] = $y[$i];
    }
    return $x;
}

$link_desc = array("Home page", "Soundcloud", "YouTube");

function rnd_link() {
    global $link_desc;
    $d = $link_desc;
    shuffle($d);
    $x = array();
    $n = random_int(0,2);
    for ($i=0; $i<$n; $i++) {
        $y = new StdClass;
        $y->url = "https://google.com/";
        $y->desc = $d[$i];
        $x[] = $y;
    }
    return $x;
}

function rnd_perf($user_id) {
    $x = new StdClass;
    $x->inst = rnd_subset(INST_LIST_FINE);
    $x->style = rnd_subset(STYLE_LIST);
    $x->level = rnd_subset(LEVEL_LIST);
    $x->signature_filename = rnd_signature($user_id, PERFORMER);
    $x->link = rnd_link();
    return $x;
}

function rnd_comp($user_id) {
    $x = new StdClass;
    $x->inst = rnd_subset(INST_LIST_COARSE);
    $x->style = rnd_subset(STYLE_LIST);
    $x->level = rnd_subset(LEVEL_LIST);
    $x->influence = rnd_influence();
    $x->signature_filename = rnd_signature($user_id, COMPOSER);
    $x->link = rnd_link();
    return $x;
}

function random_name() {
    $first = array("John", "Bob", "Mary", "Alice", "Lucy", "Fred");
    $last = array("Smith", "Jones", "Adams", "Green", "Brown", "Williams");
    shuffle($first);
    shuffle($last);
    return sprintf("%s %s", $first[0], $last[0]);
}

function make_users() {
    for ($i=0; $i<100; $i++) {
        $pc = "";
        switch (random_int(1,10)) {
        case 0:
            $c = "Canada";
            break;
        case 1:
            $c = "Italy";
            break;
        default:
            $c = "United States";
            $pc = strval(rnd_zip(94000, 94999));
        }
        $user = make_user(
            "fake_user_$i@foo.com",
            random_name(),
            "",
            $c,
            $pc
        );
        if (!$user) {
            echo "no user"; exit;
        }
        $id = $user->id;


        $x = random_int(1,10);
        if ($x < 5) {
            write_profile($id, rnd_comp($id), COMPOSER);
        } else if ($x < 9) {
            write_profile($id, rnd_perf($id), PERFORMER);
        } else {
            write_profile($id, rnd_comp($id), COMPOSER);
            write_profile($id, rnd_perf($id), PERFORMER);
        }
    }
}

make_users();


?>
