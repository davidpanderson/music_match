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

// send notification emails

require_once("../inc/mm.inc");
require_once("../inc/forum_db.inc");

// send notifications since max(last email, 1 week ago)
//
function send_email($user) {
    $now = time();
    $cutoff = max($user->expavg_time, $now-7*86400);
    $ns = BoincNotify::enum("userid=$user->id and create_time>$cutoff order by create_time desc");
    if (!$ns) return;
    $x = "";
    foreach ($ns as $n) {
        $x .= notification_email($n);
    }
    send_email($user);
}

// scan through users, see which are due for an email
//
function send_emails() {
    $now = time();
    $day = $now - 86400 - 3600;
    $week = $now - 7*86400 - 3600;

    $users = BoincUser::enum(
        sprintf(
            'send_email=%d and expavg_time<%d or send_email=%d and expavg_time<%d',
            NOTIFY_DAILY, $day, NOTIFY_WEEKLY, $week
        )
    );
    foreach ($users as $user) {
        send_email($user);
        $user->update("expavg_time = $now");
    }
}

?>
