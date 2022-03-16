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

// script to send notification emails

require_once("../inc/mm.inc");
require_once("../inc/notification.inc");
require_once("../inc/forum_db.inc");

// send notifications since max(last email, 1 week ago)
//
function send_notify_email($user) {
    $now = time();
    $cutoff = max($user->expavg_time, $now-7*86400);
    $ns = BoincNotify::enum("userid=$user->id and create_time>$cutoff order by create_time desc");
    if (!$ns) {
        return false;
    }
    $x = [];
    foreach ($ns as $n) {
        $x[] = notification_string($n, false);
    }
    if ($x) {
        $subject = "Music Match notifications";
        $body = sprintf(
'%s

For details, or to change email settings, visit Music Match:
https://isaac.ssl.berkeley.edu/mm/home.php
',
            implode($x, "\n\n")
        );
        $body_html = sprintf(
'%s
<p><p>
For details, or to change email settings, <a href=%s>visit Music Match</a>.
',
            implode($x, "<p><p>"),
            'https://isaac.ssl.berkeley.edu/mm/home.php'
        );
        send_email($user, $subject, $body, $body_html);
    }
    return true;
}

// scan through users, see which are due for an email
//
function send_notify_emails() {
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
        if (send_notify_email($user)) {
            $user->update("expavg_time = $now");
        }
    }
}

send_notify_emails();

?>
