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
require_once("../inc/forum_db.inc");
require_once("../inc/mm_user.inc");

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
    case NOTIFY_PM:
        $pm = BoincPrivateMessage::lookup_id($n->opaque);
        if (!$pm) return "no pm $n->opaque";
        $u = BoincUser::lookup_id($pm->senderid);
        if (!$u) return "no user $pm->senderid";
        return sprintf(
            'You received <a href=%s>a message from %s</a>',
            'mm_pm.php?action=inbox', $u->name
        );
        break;
    case NOTIFY_SEARCH:
    case NOTIFY_ENS_DEL:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        return sprintf('The ensemble %s was removed by its founder', $ens->name);
    case NOTIFY_FRIEND_REQ:
        return friend_notify_req_web_line($n);
    case NOTIFY_FRIEND_ACCEPT:
        return friend_notify_accept_web_line($n);
    case NOTIFY_SUBSCRIBED_POST:
        return subscribed_post_web_line($n);
    case NOTIFY_PROFILE:
    case NOTIFY_ENS_JOIN_REQ:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        $u = BoincUser::lookup_id($n->id2);
        if (!$u) return null;
        return sprintf(
            '%s has asked to join %s.  <a href=%s&ens_id=%d>Accept or decline</a>.',
            $u->name, $ens->name,
            'ensemble_join.php?action=review', $ens->id
        );
    case NOTIFY_ENS_JOIN_REPLY:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        return sprintf(
            'Your request to join <a href=ensemble.php?ens_id=%d>%s</a> was %s.',
            $n->opaque,
            $ens->name, $n->id2?"accepted":"declined"
        );
    case NOTIFY_ENS_REMOVE:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        return sprintf('You were removed from %s', $ens->name);
    case NOTIFY_ENS_QUIT:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        $u = BoincUser::lookup_id($n->id2);
        return sprintf('%s resigned from %s', $u->name, $ens->name);
        break;
    default:
        return "unknown type $n->type";
    }
}

// Text for email; no links
//
function notification_email($n) {
    switch($n->type) {
    case NOTIFY_PM:
    case NOTIFY_SEARCH:
    case NOTIFY_ENS_DEL:
    case NOTIFY_FRIEND_REQ:
    case NOTIFY_FRIEND_REPLY:
    case NOTIFY_THREAD:
    case NOTIFY_PROFILE:
    case NOTIFY_ENS_JOIN:
    case NOTIFY_ENS_REMOVE:
    case NOTIFY_ENS_QUIT:
        break;
    }
}

function show_notifications($user) {
    $ns = BoincNotify::enum("userid = $user->id");
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
