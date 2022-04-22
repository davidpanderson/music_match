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

// remove a user and things that refer to them

require_once("../inc/boinc_db.inc");
require_once("../inc/forum_db.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/mm.inc");

function remove_user_ensemble($user) {
    // remove ensembles the user founded
    //
    $enss = Ensemble::enum("user_id=$user->id");
    foreach ($enss as $ens) {
        EnsembleMember::delete_aux("ensemble_id=$ens->id");
        $ens->delete();
    }

    // remove membership in other ensembles
    //
    EnsembleMember::delete_aux("user_id=$user->id");
}

function remove_user_notify($user) {
    // remove notifications to this user
    //
    BoincNotify::delete_aux("userid=$user->id");

    // remove notifications that refer to this user
    // (don't bother)
}

function remove_user_search($user) {
    // remove user's searches
    //
    Search::delete_aux("user_id=$user->id");

    // remove user from search results
    //
    $searches = Search::enum("");
    foreach ($searches as $search) {
        $results = json_decode($search->view_results);
        if (in_array($user->id, $results)) {
            $new_res = array_diff($results, [$user->id]);
            $r = json_encode(array_values($new_res));
            $search->update("view_results='$r'");
            echo "updated search $search->id\n";
        }
    }
}

function remove_user_pm($user) {
    BoincPrivateMessage::delete_aux("userid=$user->id or senderid=$user->id");
}

function remove_user_friend($user) {
    BoincFriend::delete_aux("user_src=$user->id or user_dest=$user->id");
}

function remove_user_profiles($user_id) {
    delete_mm_profile($user_id, COMPOSER);
    delete_mm_profile($user_id, PERFORMER);
    delete_mm_profile($user_id, TECHNICIAN);
    delete_mm_profile($user_id, TEACHER);
}

function remove_user($user) {
    remove_user_ensemble($user);
    remove_user_notify($user);
    remove_user_search($user);
    remove_user_pm($user);
    remove_user_friend($user);
    BoincForumPrefs::lookup($user);
    $user->prefs->delete();
    $user->delete();
    remove_user_profiles($user->id);
}

$users = BoincUser::enum("email_addr like 'fake%'");
foreach ($users as $user) {
    echo "removing $user->id $user->email_addr\n";
    remove_user($user);
}

?>
