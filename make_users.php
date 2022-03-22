#! /usr/bin/env php
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

// make some random simulated users

// before running this, delete in the DB
//
// user with id>1
// ensemble
// ensemble_member
// private_messages
// forum_preferences with userid>1
// notify
// friend
// search
//
// and delete from project
// ensemble/*, composer/*, performer/*, technician/*

require_once("../inc/util.inc");
require_once("../inc/user_util.inc");
require_once("../inc/zip.inc");
require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");

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

// select 1..n keys randomly from the given list
//
function rnd_subset($list, $n) {
    $x = array();
    $m = random_int(1,$n);
    $keys = array_keys($list);
    shuffle($keys);
    $keys = array_slice($keys, 0, $m);
    foreach ($list as $key=>$val) {
        if (in_array($key, $keys)) {
            $x[] = $key;
        }
    }
    return $x;
}

function rnd_key($list) {
    $keys = array_keys($list);
    shuffle($keys);
    return $keys[0];
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
    $x->inst = rnd_subset(INST_LIST_FINE, 3);
    $x->style = rnd_subset(STYLE_LIST, 4);
    $x->level = rnd_subset(LEVEL_LIST, 2);
    $x->signature_filename = rnd_signature($user_id, PERFORMER);
    $x->link = rnd_link();
    return $x;
}

function rnd_comp($user_id) {
    $x = new StdClass;
    $x->inst = rnd_subset(INST_LIST_COARSE, 3);
    $x->ens_type = rnd_subset(ENSEMBLE_TYPE_LIST, 2);
    $x->style = rnd_subset(STYLE_LIST, 4);
    $x->level = rnd_subset(LEVEL_LIST, 2);
    $x->influence = rnd_influence();
    $x->signature_filename = rnd_signature($user_id, COMPOSER);
    $x->link = rnd_link();
    return $x;
}

function rnd_tech() {
    $x = new StdClass;
    $x->tech_area = rnd_subset(TECH_AREA_LIST, 2);
    $x->program = rnd_subset(PROGRAM_LIST, 3);
    return $x;
}

function rnd_ens($id) {
    $x = new StdClass;
    $x->description = "description of ensemble $id";
    $x->inst = rnd_subset(INST_LIST_FINE, random_int(1, 5));
    $x->type = rnd_key(ENSEMBLE_TYPE_LIST);
    $x->style = rnd_subset(STYLE_LIST, 4);
    $x->level = rnd_subset(LEVEL_LIST, 2);
    $x->link = rnd_link();
    $x->signature_filename = rnd_signature($id, ENSEMBLE);
    $x->seeking_members = random_int(0,1);
    $x->perf_reg = random_int(0,1);
    $x->perf_paid = random_int(0,1);
    return $x;
}

function make_users($n) {
    $names = file("names.txt");
    for ($i=0; $i<$n; $i++) {
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
            $names[$i],
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
        if (random_int(1,5) == 1) {
            write_profile($id, rnd_tech(), TECHNICIAN);
        }
    }
}

function make_ensemble($name, $users) {
    $nusers = count($users);
    $user = $users[random_int(0, $nusers-1)];
    $ens_id = Ensemble::insert(
        sprintf("(create_time, user_id, name) values (%d, %d, '%s')",
            time(), $user->id, $name
        )
    );
    if (!$ens_id) die("insert failed");
    echo "made ensemble $name; ID $ens_id\n";
    $ens = Ensemble::lookup_id($ens_id);
    $ens->update("name='ensemble $ens_id'");
    write_profile($ens_id, rnd_ens($ens_id), ENSEMBLE);

    // add some members
    $members = array();
    for ($i=0; $i<random_int(0,10); $i++) {
        $u = $users[random_int(0, $nusers-1)];
        if ($u->id == $user->id) continue;
        $members[] = $u->id;
    }
    $members = array_unique($members);
    foreach ($members as $id) {
        EnsembleMember::insert(
            sprintf("(create_time, ensemble_id, user_id, status) values(%d, %d, %d, %d)",
                time(), $ens_id, $id, EM_APPROVED
            )
        );
    }
}

function make_ensembles($n) {
    $users = BoincUser::enum("id>1");
    for ($i=0; $i<$n; $i++) {
        make_ensemble("ensemble $i", $users);
    }
}

//make_users(100);
make_ensembles(20);


?>
