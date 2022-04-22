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

// Utility functions for searches.
// For each search type (comp/perf, tech, ensemble, teacher)
// *_match_args()
//      Take parsed form args and count number of matches with given profile
// *_match_value()
//      Compute "value" of the above
// *_search()
//      given form args, return value-ordered list of profiles
// *_args_to_str()
//      convert search args to string (for search list display)
//
// Searches have two types of parameters:
//
// soft parameters:
//      Things like instrument, style, level, and writing.
//      Each profile is given a "value"
//      depending on how many total matches there are,
//      and how many soft parameters have >0 matches
// hard parameters:
//      booleans like "paid", where the parameter is yes/no/either
//      profiles for which a hard parameter doesn't match
//      are not included in the results.

require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");

// if you add new search params, set them here so old searches don't break
//
function add_missing_args($args, $role) {
    if (!isset($args->writing)) $args->writing=null;
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        if (!isset($args->paid)) $args->paid = "either";
        break;
    }
    return $args;
}

// $query and $text are lists of words.
// return the number of words in $query that are in $text
//
function word_matches($query, $text) {
    $n = 0;
    foreach ($query as $w) {
        if (in_array($w, $text)) {
            $n++;
        }
    }
    return $n;
}

// parse the given string into words; downcase and return a list of them
//
function parse_words($string) {
    static $delim = " \n\t,.!?:;/";
    $x = [];
    $word = strtok($string, $delim);
    while ($word) {
        $x[] = strtolower($word);
        $word = strtok($delim);
    }
    return $x;
}

function parse_words_array($arr) {
    $x = [];
    foreach ($arr as $w) {
        $x = array_merge($x, parse_words($w));
    }
    return $x;
}

// return true if the form arg (yes/no/either) matches the profile value (0/1)
//
function check_bool($arg, $value) {
    switch ($arg) {
    case 'yes': return $value;
    case 'no': return !$value;
    }
    return true;
}

// ---------------- Composer/performer --------------

// compare profile with form args
// Return object w/ number of matches of each type
// (inst, style, level)
//
function cp_match_args($role, $profile, $args) {
    $x = new StdClass;
    $x->inst = 0;
    $x->style = 0;
    $x->level = 0;
    foreach ($profile->inst as $i) {
        if (in_array($i, $args->inst)) {
            $x->inst++;
        }
    }
    foreach ($profile->style as $i) {
        if (in_array($i, $args->style)) {
            $x->style++;
        }
    }
    foreach ($profile->level as $i) {
        if (in_array($i, $args->level)) {
            $x->level++;
        }
    }
    if ($role == COMPOSER) {
        $x->ens_type = 0;
        foreach ($profile->ens_type as $i) {
            if (in_array($i, $args->ens_type)) {
                $x->ens_type++;
            }
        }
    }
    $x->writing = word_matches(
        parse_words($args->writing),
        array_merge(
            parse_words_array($profile->style_custom),
            parse_words_array($profile->inst_custom),
            parse_words($profile->description)
        )
    );

    return $x;
}

// each match is a triple (inst, style, level).
// compute the "value" of the match (for ranking search results)
//
function cp_match_value($role, $match) {
    $x = 0;
    if ($match->inst) $x += 100 + $match->inst;
    if ($match->style) $x += 100 + $match->style;
    if ($match->level) $x += 100 + $match->level;
    if ($role == COMPOSER) {
        if ($match->ens_type) $x += 100 + $match->ens_type;
    }
    if ($match->writing) $x += 100 + $match->writing;
    return $x;
}

// return array of id => profile.
// The user record has been attached to each profile
//
function cp_search($role, $form_args, $req_user) {
    [$close_country, $close_zip] = handle_close($form_args, $req_user);
    $profiles_in = get_profiles($role);
    $profiles = array();
    foreach ($profiles_in as $user_id=>$profile) {
        if ($req_user->id == $user_id) {
            // don't show user their own profile
            continue;
        }
        if (!check_bool($form_args->paid, $role==COMPOSER?$profile->comp_paid:$profile->perf_paid)) {
            continue;
        }
        $profile->match = cp_match_args($role, $profile, $form_args);
        $profile->value = cp_match_value($role, $profile->match);
        if ($profile->value == 0) {
            // skip if no criteria matched
            continue;
        }
        $user = BoincUser::lookup_id($user_id);
        if (!$user) {
            continue;
        }
        if ($close_country && $close_country != $user->country) {
            continue;
        }
        if ($close_zip) {
            $other_zip = str_to_zip($user->postal_code);
            if (!$other_zip) continue;
            $dist = zip_dist($close_zip, $other_zip);
            if ($dist > 60) continue;
            $profile->value -= $dist;
            $profile->dist = $dist;
        } else {
            $profile->dist = -1;
        }
        $profile->user = $user;
        $profiles[$user->id] = $profile;
    }
    uasort($profiles, 'compare_value');
    return $profiles;
}

function cp_args_to_str($args, $role) {
    $s = '';
    if ($args->inst) {
        $s .= "Instruments: ";
        $x = [];
        foreach ($args->inst as $i) {
            $x[] = $role==COMPOSER?INST_LIST_COARSE[$i]:INST_LIST_FINE[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($role == COMPOSER) {
        if ($args->ens_type) {
            $s .= 'Ensemble types: ';
            $x = [];
            foreach ($args->ens_type as $i) {
                $x[] = COMPOSE_FOR_LIST[$i];
            }
            $s .= implode(', ', $x);
            $s .= '<br>';
        }
    }
    if ($args->style) {
        $s .= 'Style: ';
        $x = [];
        foreach ($args->style as $i) {
            $x[] = STYLE_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->level) {
        $s .= 'Level: ';
        $x = [];
        foreach ($args->level as $i) {
            $x[] = LEVEL_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->paid != 'either') {
        $s .= 'Usually paid: ';
        $s .= $args->paid;
        $s .= '<br>';
    }
    if ($args->close) {
        $s .= 'Nearby: ';
        $s .= 'yes';
        $s .= '<br>';
    }
    if ($args->writing) {
        $s .= "Keywords: <i>$args->writing</i><br>";
    }
    return $s;
}

// ---------------- Technician --------------

function tech_match_args($profile, $args) {
    $x = new StdClass;
    $x->tech_area = 0;
    $x->program = 0;
    foreach ($profile->tech_area as $i) {
        if (in_array($i, $args->tech_area)) {
            $x->tech_area++;
        }
    }
    foreach ($profile->program as $i) {
        if (in_array($i, $args->program)) {
            $x->program++;
        }
    }
    $x->writing = word_matches(
        parse_words($args->writing),
        array_merge(
            parse_words_array($profile->tech_area_custom),
            parse_words_array($profile->program_custom),
            parse_words($profile->description)
        )
    );
    return $x;
}

function tech_match_value($match) {
    $x = 0;
    if ($match->tech_area) $x += 100 + $match->tech_area;
    if ($match->program) $x += 100 + $match->program;
    if ($match->writing) $x += 100 + $match->writing;
    return $x;
}

function tech_search($form_args, $req_user) {
    [$close_country, $close_zip] = handle_close($form_args, $req_user);

    $profiles_in = get_profiles(TECHNICIAN);
    $profiles = array();
    foreach ($profiles_in as $user_id=>$profile) {
        if ($req_user->id == $user_id) continue;
        $profile->match = tech_match_args($profile, $form_args);
        $profile->value = tech_match_value($profile->match);
        if ($profile->value == 0) continue;
        $user = BoincUser::lookup_id($user_id);
        if (!$user) {
            continue;
        }
        if ($close_country && $close_country != $user->country) {
            continue;
        }
        if ($close_zip) {
            $other_zip = str_to_zip($user->postal_code);
            if (!$other_zip) continue;
            $dist = zip_dist($close_zip, $other_zip);
            if ($dist > 60) continue;
            $profile->value -= $dist;
            $profile->dist = $dist;
        } else {
            $profile->dist = -1;
        }
        $profile->user = $user;
        $profiles[$user->id] = $profile;
    }
    uasort($profiles, 'compare_value');
    return $profiles;
}

function tech_args_to_str($args) {
    $s = '';
    if ($args->tech_area) {
        $s = 'Areas: ';
        $x = [];
        foreach ($args->tech_area as $i) {
            $x[] = TECH_AREA_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->program) {
        $s .= 'Software: ';
        $x = [];
        foreach ($args->program as $i) {
            $x[] = PROGRAM_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->close) {
        $s .= 'Nearby: ';
        $s .= 'yes';
        $s .= '<br>';
    }
    if (isset($args->writing)) {
        $s .= "Key words: <i>$args->writing</i><br>";
    }
    return $s;
}

// ---------------- Ensemble --------------

function ens_match_args($profile, $args) {
    $x = new StdClass;
    $x->type = 0;
    $x->inst = 0;
    $x->style = 0;
    $x->level = 0;
    if (in_array($profile->type, $args->type)) {
        $x->type++;
    }
    foreach ($profile->inst as $i) {
        if (in_array($i, $args->inst)) {
            $x->inst++;
        }
    }
    foreach ($profile->style as $i) {
        if (in_array($i, $args->style)) {
            $x->style++;
        }
    }
    foreach ($profile->level as $i) {
        if (in_array($i, $args->level)) {
            $x->level++;
        }
    }
    $x->writing = word_matches(
        parse_words($args->writing),
        array_merge(
            parse_words($profile->type),
            parse_words_array($profile->style_custom),
            parse_words_array($profile->inst_custom),
            parse_words($profile->description)
        )
    );
    return $x;
}

function ens_match_value($match) {
    $x = 0;
    if ($match->type) $x += 100 + $match->type;
    if ($match->inst) $x += 100 + $match->inst;
    if ($match->style) $x += 100 + $match->style;
    if ($match->level) $x += 100 + $match->level;
    if ($match->writing) $x += 100 + $match->writing;
    return $x;
}

function ens_search($form_args, $req_user) {
    [$close_country, $close_zip] = handle_close($form_args, $req_user);
    $ensembles_in = Ensemble::enum("");
    $ensembles = array();
    foreach ($ensembles_in as $e) {
        $profile = read_profile($e->id, ENSEMBLE);
        if (!check_bool($form_args->seeking_members, $profile->seeking_members)) {
            continue;
        }
        if (!check_bool($form_args->perf_reg, $profile->perf_reg)) {
            continue;
        }
        if (!check_bool($form_args->perf_paid, $profile->perf_paid)) {
            continue;
        }
        $e->profile = $profile;
        $e->match = ens_match_args($e->profile, $form_args);
        $e->value = ens_match_value($e->match);
        if ($e->value == 0) continue;
        $user = BoincUser::lookup_id($e->user_id);
        if ($close_country && $close_country != $user->country) {
            continue;
        }
        if ($close_zip) {
            $other_zip = str_to_zip($user->postal_code);
            if (!$other_zip) continue;
            $dist = zip_dist($close_zip, $other_zip);
            if ($dist > 60) continue;
            $e->value -= $dist;
            $e->dist = $dist;
        } else {
            $e->dist = -1;
        }
        $e->user = $user;
        $ensembles[$e->id] = $e;
    }
    uasort($ensembles, 'compare_value');
    return $ensembles;
}

function ens_args_to_str($args) {
    $s = '';
    if ($args->type) {
        $s = 'Ensemble type: ';
        $x = [];
        foreach ($args->type as $i) {
            $x[] = ENSEMBLE_TYPE_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->inst) {
        $s .= 'Instruments: ';
        $x = [];
        foreach ($args->inst as $i) {
            $x[] = INST_LIST_FINE[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->level) {
        $s .= 'Level: ';
        $x = [];
        foreach ($args->level as $i) {
            $x[] = LEVEL_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->seeking_members != 'either') {
        $s .= 'Seeking members: ';
        $s .= $args->seeking_members;
        $s .= '<br>';
    }
    if ($args->perf_reg != 'either') {
        $s .= 'Perform regularly: ';
        $s .= $args->perf_reg;
        $s .= '<br>';
    }
    if ($args->perf_paid != 'either') {
        $s .= 'Paid to perform: ';
        $s .= $args->perf_paid;
        $s .= '<br>';
    }
    if ($args->close) {
        $s .= 'Nearby: ';
        $s .= 'yes';
        $s .= '<br>';
    }
    if (isset($args->writing)) {
        $s .= "Key words: <i>$args->writing</i><br>";
    }
    return $s;
}

// -------------- teacher ------------------

function teacher_match_args($profile, $args) {
    $x = new StdClass;
    $x->topic = 0;
    $x->style = 0;
    $x->level = 0;
    foreach ($profile->topic as $i) {
        if (in_array($i, $args->topic)) {
            $x->topic++;
        }
    }
    foreach ($profile->style as $i) {
        if (in_array($i, $args->style)) {
            $x->style++;
        }
    }
    foreach ($profile->level as $i) {
        if (in_array($i, $args->level)) {
            $x->level++;
        }
    }
    $x->writing = word_matches(
        parse_words($args->writing),
        array_merge(
            parse_words_array($profile->topic_custom),
            parse_words_array($profile->style_custom),
            parse_words($profile->description)
        )
    );
    return $x;
}

function teacher_match_value($match) {
    $x = 0;
    if ($match->topic) $x += 100 + $match->topic;
    if ($match->style) $x += 100 + $match->style;
    if ($match->level) $x += 100 + $match->level;
    if ($match->writing) $x += 100 + $match->writing;
    return $x;
}

// return array of id => profile.
// The user record has been attached to each profile
//
function teacher_search($form_args, $req_user) {
    [$close_country, $close_zip] = handle_close($form_args, $req_user);
    $profiles_in = get_profiles(TEACHER);
    $profiles = array();
    foreach ($profiles_in as $user_id=>$profile) {
        if ($req_user->id == $user_id) {
            // don't show user their own profile
            continue;
        }
        $profile->match = teacher_match_args($profile, $form_args);
        $profile->value = teacher_match_value($profile->match);
        if ($profile->value == 0) {
            // skip if no criteria matched
            continue;
        }
        $user = BoincUser::lookup_id($user_id);
        if (!$user) {
            continue;
        }
        if ($close_country && $close_country != $user->country) {
            continue;
        }
        if ($close_zip) {
            $other_zip = str_to_zip($user->postal_code);
            if (!$other_zip) continue;
            $dist = zip_dist($close_zip, $other_zip);
            if ($dist > 60) continue;
            $profile->value -= $dist;
            $profile->dist = $dist;
        } else {
            $profile->dist = -1;
        }
        $profile->user = $user;
        $profiles[$user->id] = $profile;
    }
    uasort($profiles, 'compare_value');
    return $profiles;
}

function teacher_args_to_str($args) {
    $s = '';
    if ($args->topic) {
        $s .= 'Topic: ';
        $x = [];
        foreach ($args->topic as $i) {
            $x[] = TOPIC_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->style) {
        $s .= 'Style: ';
        $x = [];
        foreach ($args->style as $i) {
            $x[] = STYLE_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->level) {
        $s .= 'Level: ';
        $x = [];
        foreach ($args->level as $i) {
            $x[] = LEVEL_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if (isset($args->where) && count($args->where)>0) {
        $s .= 'Where: ';
        $x = [];
        foreach ($args->where as $i) {
            $x[] = WHERE_LIST[$i];
        }
        $s .= implode(', ', $x);
        $s .= '<br>';
    }
    if ($args->close) {
        $s .= 'Nearby: ';
        $s .= 'yes';
        $s .= '<br>';
    }
    if (isset($args->writing)) {
        $s .= "Key words: <i>$args->writing</i><br>";
    }
    return $s;
}

// -------------- utilities ------------------

// record a search in the DB, or update existing record
//
function record_search($user, $role, $args, $profiles) {
    if (!args_to_str($args, $role)) {
        // don't record empty search
        return;
    }
    $params = new stdClass;
    $params->role = $role;
    $params->args = $args;
    $params_json = json_encode($params, JSON_PRETTY_PRINT);
    $params_hash = md5($params_json);
    $results = [];
    foreach ($profiles as $id=>$p) {
        $results[] = $id;
    }
    $results_json = json_encode($results);
    $search = Search::lookup(
        sprintf("user_id=%d and params_hash='%s'",
            $user->id, $params_hash
        )
    );
    if ($search) {
        $search->update(
            sprintf("view_results='%s', view_time=%d, rerun_time=%d, rerun_nnew=0",
                $results_json, time(), time()
            )
        );
    } else {
        Search::insert(
            sprintf("(create_time, user_id, params, params_hash, view_results, view_time) values (%d, %d, '%s', '%s', '%s', %d)",
                time(), $user->id, $params_json, $params_hash, $results_json, time()
            )
        );
    }
}

// convert search args to a list of lines
//
function args_to_str($args, $role) {
    switch ($role) {
    case COMPOSER:
    case PERFORMER:
        return cp_args_to_str($args, $role);
    case TECHNICIAN:
        return tech_args_to_str($args, $role);
    case ENSEMBLE:
        return ens_args_to_str($args);
    case TEACHER:
        return teacher_args_to_str($args);
    }
    die('args_to_str');
}

?>
