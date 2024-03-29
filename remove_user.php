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

// remove_user.php userID
// remove a user
// (or edit to remove fake users)

die('foo');

require_once("../inc/remove_user.inc");

function remove_fake_users() {
    $users = BoincUser::enum("email_addr like 'fake%'");
    foreach ($users as $user) {
        echo "removing $user->id $user->email_addr\n";
        remove_user($user);
    }
}

if ($argc != 2) die("remove_user.php userID\n");
$user = BoincUser::lookup_id($argv[1]);
if (!$user) die("no user\n");
remove_user($user);

?>
