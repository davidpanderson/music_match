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

require_once("../inc/mm.inc");

// we use:
//      user.send_email to store prefs
//      user.expavg_time to store time of last email

define('NOTIFY_NEVER', 0);
define('NOTIFY_DAILY', 1);
define('NOTIFY_WEEKLY', 2);
define('NOTIFY_IMMEDIATE', 3);

define('NOTIFY_LIST', array(
    NOTIFY_DAILY => 'Daily',
    NOTIFY_WEEKLY => 'Weekly',
    NOTIFY_IMMEDIATE => 'Immediately',
    NOTIFY_NEVER => 'Never'
));

// show notification on web, with links where relevant
//
function show_notification($n) {
    switch($n->type) {
    case NOTIFICATION_PM:
        $u = BoincUser::lookup_id($n->id1);
        if (!$u) return null;
        return sprintf(
            'You received <a href=%s>a message from %s</a>',
            'pm.php?action=inbox', $u->name
        );
        break;
    case NOTIFICATION_SEARCH:
    case NOTIFICATION_ENS_DEL:
        $ens = Ensemble::lookup_id($n->id1);
        if (!$ens) return null;
        return sprintf('The ensemble %s was removed by its founder', $ens->name);
    case NOTIFICATION_FRIEND_REQ:
    case NOTIFICATION_FRIEND_REPLY:
    case NOTIFICATION_THREAD:
    case NOTIFICATION_PROFILE:
    case NOTIFICATION_ENS_JOIN_REQ:
        $ens = Ensemble::lookup_id($n->id1);
        if (!$ens) return null;
        $u = BoincUser::lookup_id($n->id2);
        if (!$u) return null;
        return sprintf(
            '%s has asked to join %s.  <a href=%s&ens_id=%d>Accept or decline</a>.',
            $u->name, $ens->name,
            'ensemble_join.php?action=review', $ens->id
        );
    case NOTIFICATION_ENS_JOIN_REPLY:
        $ens = Ensemble::lookup_id($n->id1);
        if (!$ens) return null;
        return sprintf(
            'Your request to join <a href=ensemble.php?ens_id=%d>%s</a> was %s.',
            $n->id1,
            $ens->name, $n->id2?"accepted":"declined"
        );
    case NOTIFICATION_ENS_REMOVE:
        $ens = Ensemble::lookup_id($n->id1);
        if (!$ens) return null;
        return sprintf('You were removed from %s', $ens->name);
    case NOTIFICATION_ENS_QUIT:
        $ens = Ensemble::lookup_id($n->id1);
        if (!$ens) return null;
        $u = BoincUser::lookup_id($n->id2);
        return sprintf('%s resigned from %s', $u->name, $ens->name);
        break;
    }
}

// Text for email; no links
//
function notification_email($n) {
    switch($n->type) {
    case NOTIFICATION_PM:
    case NOTIFICATION_SEARCH:
    case NOTIFICATION_ENS_DEL:
    case NOTIFICATION_FRIEND_REQ:
    case NOTIFICATION_FRIEND_REPLY:
    case NOTIFICATION_THREAD:
    case NOTIFICATION_PROFILE:
    case NOTIFICATION_ENS_JOIN:
    case NOTIFICATION_ENS_REMOVE:
    case NOTIFICATION_ENS_QUIT:
        break;
    }
}

function show_notifications($user) {
    $ns = Notification::enum("user_id = $user->id");
    if (!$ns) {
        row2('No recent notifications', '');
        return;
    }
    foreach ($ns as $n) {
        $s = show_notification($n);
        if (!$s) continue;
        row2(date_str($n->create_time), $s);
        $n->update(sprintf('last_view=%d', time()));
    }
}

?>
