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

// handler for follow/unfollow

require_once("../inc/util.inc");

$user = get_logged_in_user();
$action = get_str("action");
$uid = get_int('user_id');
$u = BoincUser::lookup_id($uid);
if (!$u) {
    error_page("no user");
}
if ($action == 'follow') {
    $now = time();
    BoincFriend::replace("user_src=$user->id, user_dest=$uid, create_time=$now");
    page_head("Follow");
    echo "You are now following $u->name.";
    page_tail();
} else if ($action == 'unfollow') {
    BoincFriend::delete_aux("user_src = $user->id and user_dest = $uid");
    page_head("Unfollow");
    echo "You are no longer following $u->name.";
    page_tail();
} else {
    error_page("no such action");
}

?>
