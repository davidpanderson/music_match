<?php

require_once("../inc/mm.inc");

// show notification on web, with links where relevant
//
function show_notification($n) {
    switch($n->type) {
    case NOTIFICATION_PM:
        $u = BoincUser::lookup_id($n->id1);
        return sprintf(
            'You received <a href=%s>a message from %s</a>',
            'pm.php?action=inbox', $u->name
        );
        break;
    case NOTIFICATION_SEARCH:
    case NOTIFICATION_ENS_DEL:
        $ens = Ensemble::lookup_id($n->id1);
        $ens_info = read_profile($ens->id, ENSEMBLE);
        return sprintf('The ensemble %s was removed by its founder', $ens_info->name);
    case NOTIFICATION_FRIEND_REQ:
    case NOTIFICATION_FRIEND_REPLY:
    case NOTIFICATION_THREAD:
    case NOTIFICATION_PROFILE:
    case NOTIFICATION_ENS_JOIN_REQ:
        $ens = Ensemble::lookup_id($n->id1);
        $ens_info = read_profile($ens->id, ENSEMBLE);
        $u = BoincUser::lookup_id($n->id2);
        return sprintf(
            '%s has asked to join %s.  <a href=%s&ens_id=%d>Accept or decline</a>.',
            $u->name, $ens_info->name,
            'ensemble_join.php?action=review', $ens->id
        );
    case NOTIFICATION_ENS_JOIN_REPLY:
        $ens = Ensemble::lookup_id($n->id1);
        $ens_info = read_profile($ens->id, ENSEMBLE);
        return sprintf(
            'Your request to join <a href=ensemble.php?ens_id=%d>%s</a> was %s.',
            $n->id1,
            $ens_info->name, $n->id2?"accepted":"declined"
        );
    case NOTIFICATION_ENS_REMOVE:
        $ens = Ensemble::lookup_id($n->id1);
        $ens_info = read_profile($ens->id, ENSEMBLE);
        return sprintf('You were removed from %s', $ens_info->name);
    case NOTIFICATION_ENS_QUIT:
        $ens = Ensemble::lookup_id($n->id1);
        $ens_info = read_profile($ens->id, ENSEMBLE);
        $u = BoincUser::lookup_id($n->id2);
        return sprintf('%s resigned from %s', $u->name, $ens_info->name);
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
    foreach ($ns as $n) {
        row2(date_str($n->create_time), show_notification($n));
        $n->update(sprintf('last_view=%d', time()));
    }
}


?>