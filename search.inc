<?php

// functions to do searches of various types

require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");

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
        $profile->match = cp_match_args($role, $profile, $form_args);
        $profile->value = cp_match_value($role, $profile->match);
        if ($profile->value == 0) {
            // skip if no criteria matched
            continue;
        }
        $user = BoincUser::lookup_id($user_id);
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
    return $x;
}

function tech_match_value($match) {
    $x = 0;
    if ($match->tech_area) $x += 100 + $match->tech_area;
    if ($match->program) $x += 100 + $match->program;
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
    return $x;
}

function ens_match_value($match) {
    $x = 0;
    if ($match->type) $x += 100 + $match->type;
    if ($match->inst) $x += 100 + $match->inst;
    if ($match->style) $x += 100 + $match->style;
    if ($match->level) $x += 100 + $match->level;
    return $x;
}

function check_bool($arg, $value) {
    switch ($arg) {
    case 'yes': return $value;
    case 'no': return !$value;
    }
    return true;
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
        $ensembles[] = $e;
    }
    uasort($ensembles, 'compare_value');
    return $ensembles;
}

// -------------- utilities ------------------

// record a search in the DB, or update existing record
//
function record_search($user, $role, $args, $profiles) {
    $params = new stdClass;
    $params->role = $role;
    $params->args = $args;
    $params_json = json_encode($params, JSON_PRETTY_PRINT);
    $params_md5 = md5($params_json);
    $results = [];
    foreach ($profiles as $id=>$p) {
        $results[] = $id;
    }
    $results_json = json_encode($results);
    $search = Search::lookup(
        sprintf("user_id=%d and params_md5='%s'",
            $user->id, $params_md5
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
                time(), $user->id, $params_json, $params_md5, $results_json, time()
            )
        );
    }
}

?>