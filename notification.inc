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

// stuff related to notifications.

require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/forum_db.inc");
require_once("../inc/user.inc");

define('NOTIFY_SEARCH_NRESULTS', 10);
    // notify the top N search results that user looked for them

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

// return a string describing the notification.
// If $web is true, it's for a web page, so include <a> links where relevant.
// Otherwise it's for a (non-HTML) email.
//
function notification_string($n, $web) {
    switch($n->type) {
    case NOTIFY_PM:
        $pm = BoincPrivateMessage::lookup_id($n->opaque);
        if (!$pm) return null;
        $u = BoincUser::lookup_id($pm->senderid);
        if (!$u) return null;
        if ($web) {
            return sprintf(
                'You received <a href=%s>a message from %s</a>.',
                'pm.php?action=inbox', $u->name
            );
        } else {
            return sprintf('You received a message from %s.', $u->name);
        }
    case NOTIFY_SEARCH:
        if ($web) {
            return "One of your <a href=search_list.php>previous searches</a> has a new result.";
        } else {
            return "One of your previous searches has a new result.";
        }
    case NOTIFY_ENS_DEL:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        return sprintf('The ensemble %s was removed by its founder.', $ens->name);
    case NOTIFY_FOLLOW:
        $u = BoincUser::lookup_id($n->opaque);
        if (!$u) return null;
        if ($web) {
            return sprintf("<a href=user.php?user_id=%d>%s</a> is following you.",
                $u->id, $u->name
            );
        } else {
            return "$u->name is following you.";
        }
    case NOTIFY_FRIEND_ACCEPT:
        return null;
    case NOTIFY_SUBSCRIBED_POST:
        if ($web) {
            return subscribed_post_web_line($n);
        } else {
            return subscribed_post_email_line($n);
        }
    case NOTIFY_PROFILE:
        $user = BoincUser::lookup_id($n->opaque);
        if (!$user) return null;
        if ($web) {
            return sprintf("<a href=user.php?user_id=%d>%s</a> changed their %s profile.",
                $user->id, $user->name, role_name($n->id2)
            );
        } else {
            return sprintf("%s changed their %s profile.",
                $user->name, role_name($n->id2)
            );
        }
    case NOTIFY_ENS_JOIN_REQ:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        $u = BoincUser::lookup_id($n->id2);
        if (!$u) return null;
        if ($web) {
            return sprintf(
                '%s asked to join %s.  <a href=%s&ens_id=%d>Accept or decline</a>.',
                $u->name, $ens->name,
                'ensemble_join.php?action=review', $ens->id
            );
        } else {
            return sprintf('%s asked to join %s.', $u->name, $ens->name);
        }
    case NOTIFY_ENS_JOIN_REPLY:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        if ($web) {
            return sprintf(
                'Your request to join <a href=ensemble.php?ens_id=%d>%s</a> was %s.',
                $ens->id, $ens->name, $n->id2?"accepted":"declined"
            );
        } else {
            return sprintf(
                'Your request to join %s was %s.',
                $ens->name, $n->id2?"accepted":"declined"
            );
        }
    case NOTIFY_ENS_REMOVE:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        return sprintf(
            'You were removed from <a href=ensemble.php?ens_id=%d>%s</a>.',
            $ens->id, $ens->name
        );
    case NOTIFY_ENS_QUIT:
        $ens = Ensemble::lookup_id($n->opaque);
        if (!$ens) return null;
        $u = BoincUser::lookup_id($n->id2);
        if ($web) {
            return sprintf('<a href=%s>%s</a> resigned from <a href=%s>%s</a>.',
                "user.php?user_id=$u->id",
                $u->name,
                "ensemble.php?ens_id=$ens->id",
                $ens->name
            );
        } else {
            return sprintf('%s resigned from %s.', $u->name, $ens->name);
        }
        break;
    case NOTIFY_LOOKING_FOR_YOU:
        $u = BoincUser::lookup_id($n->id2);
        if ($web) {
            return sprintf('<a href=%s>%s</a> searched for %ss like you.',
                "user.php?user_id=$u->id",
                $u->name,
                role_dir($n->opaque)
            );
        } else {
            return sprintf('%s searched for %ss like you',
                $u->name, role_dir($n->opaque)
            );
        }
        break;
    default:
        return "unknown type $n->type";
    }
}

// show notifications (on home page)
//
function show_notifications($user) {
    $t = time() - 7*86400;
    $ns = BoincNotify::enum("userid = $user->id and create_time>$t order by id desc");
    if (!$ns) {
        row2('No recent notifications', '');
        return;
    }
    foreach ($ns as $n) {
        $s = notification_string($n, true);
        if (!$s) continue;
        row2(time_str($n->create_time), $s);
        $n->update(sprintf('last_view=%d', time()));
    }
}

// send notifications not previously emailed
// return true if anything was sent
// 
//
function send_notify_email($user) {
    $now = time();
    $ns = BoincNotify::enum("userid=$user->id and sent_by_email=0 order by create_time desc");
    if (!$ns) {
        return false;
    }
    $x = [];
    foreach ($ns as $n) {
        $x[] = notification_string($n, false);
        $n->update("sent_by_email=$now");
    }
    if ($x) {
        $subject = "Music Match notifications";
        $body = sprintf(
'%s

For details, or to change email settings, visit Music Match:
https://music-match.org/home.php

Spread the word about Music Match: visit
https://music-match.org/email.php
',
            implode("\n\n", $x)
        );
        $body_html = sprintf(
'%s
<p><p>
For details, or to change email settings, <a href=%s>visit Music Match</a>.
<p><p>
<a href=https://music-match.org/email.php>Spread the word</a> about Music Match.
',
            implode("<p><p>", $x),
            'https://music-match.org/home.php'
        );
        send_email($user, $subject, $body, null);
    }
    return true;
}

// user has a new notification.
// If their email setting is "immediate", send them an email
//
function email_if_immediate($user) {
    if ($user && $user->send_email == NOTIFY_IMMEDIATE) {
        send_notify_email($user);
    }
}

// The given user X just did a search.
// Notify the top N users that X is looking for someone like them.
//
function notify_search_results($user, $role, $profiles) {
    $n = 0;
    $now = time();
    foreach ($profiles as $p) {
        BoincNotify::replace(
            sprintf("userid=%d, create_time=%d, type=%d, opaque=%d, id2=%d, sent_by_email=0",
                $p->user->id, $now, NOTIFY_LOOKING_FOR_YOU, $role, $user->id
            )
        );
        email_if_immediate($p->user);
        $n++;
        if ($n == NOTIFY_SEARCH_NRESULTS) {
            break;
        }
    }
}

// The user has modified their given profile.
// Notify the people who follow them
//
function notify_profile_change($user, $role) {
    $friends = BoincFriend::enum("user_dest = $user->id");
    $now = time();
    foreach ($friends as $friend) {
        $user2 = BoincUser::lookup_id($friend->user_src);
        if (!$user2) continue;
        BoincNotify::replace(
            sprintf("userid=%d, create_time=%d, type=%d, opaque=%d, id2=%d, sent_by_email=0",
                $user2->id, $now, NOTIFY_PROFILE, $user->id, $role
            )
        );
        email_if_immediate($user2);
    }
}

?>
