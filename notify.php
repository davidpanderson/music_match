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

// script to send notification emails

require_once("../inc/mm.inc");
require_once("../inc/notification.inc");

// scan through users, see which are due for an email
//
function main() {
    $now = time();
    $day = $now - 86400 - 3600;
    $week = $now - 7*86400 - 3600;

    $users = BoincUser::enum(
        sprintf('send_email<>%d', NOTIFY_NEVER)
    );
    foreach ($users as $user) {
        $sent_any = false;
        switch ($user->send_email) {
        case NOTIFY_IMMEDIATE:
            break;
        case NOTIFY_DAILY:
            if ($user->expavg_time > $day) continue;
            break;
        case NOTIFY_WEEKLY:
            if ($user->expavg_time > $week) continue;
            break;
        }
        $sent_any = send_notify_email($user);
        if ($sent_any) {
            echo "sent notification email to user $user->id\n";
            $user->update("expavg_time = $now");
        }
    }
}


echo date(DATE_RFC822), ": Starting\n";

main();

echo date(DATE_RFC822), ": Finished\n";

?>
