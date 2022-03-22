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

// functions involving ensemble membership:
//
// request: user asks to join ensemble
// resign: user resigns from ensemble; confirm
// review: founder views membership requests
// grant:
// deny:
// remove: founder removes member

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/mm_db.inc");
require_once("../inc/notification.inc");

function join_form($ens, $ens_info) {
    page_head("Request membership in $ens->name");
    echo "Click below to request membership in $ens->name.
        You will be notified when the founder
        accepts or declines your request.
        <p>
    ";
    form_start("ensemble_join.php", "POST");
    form_input_hidden('ens_id', $ens->id);
    form_submit("Request membership", 'name=submit value=on');
    form_end();
    page_tail();
}

function join_action($ens, $ens_info, $user) {
    EnsembleMember::delete_aux(
        sprintf("ensemble_id=%d and user_id=%d", $ens->id, $user->id)
    );
    EnsembleMember::insert(
        sprintf(
            "(create_time, ensemble_id, user_id, status) values (%d, %d, %d, %d)",
            time(), $ens->id, $user->id, EM_PENDING
        )
    );
    BoincNotify::replace(
        sprintf(
            "create_time=%d, userid=%d, type=%d, opaque=%d, id2=%d",
            time(), $ens->user_id, NOTIFY_ENS_JOIN_REQ, $ens->id, $user->id
        )
    );
    email_if_immediate(BoincUser::lookup_id($ens->user_id));
    page_head("Request submitted");
    echo "Your request to join $ens->name has been submitted.<p>
        If you like, <a href=pm.php?action=new&userid=$ens->user_id>send the founder a message</a>.
    ";
    page_tail();
}

// show pending membership requests
//
function review_form($ens, $ens_info) {
    $ems = EnsembleMember::enum("ensemble_id=$ens->id");
    page_head("Membership requests for $ens->name");
    start_table();
    table_header("Name", "When", "Click to accept or reject");
    foreach ($ems as $em) {
        if ($em->status !=EM_PENDING) continue;
        $user = BoincUser::lookup_id($em->user_id);
        table_row(
            "<a href=user.php?user_id=$em->user_id>$user->name</a>",
            date_str($em->create_time),
            "<a href=ensemble_join.php?action=decide&ens_id=$ens->id&user_id=$em->user_id>Decide</a>"
        );
    }
    end_table();
    page_tail();
}

// make sure the request is still pending
//
function check_request_pending($ens_id, $user_id) {
    $em = EnsembleMember::lookup("ensemble_id=$ens_id and user_id=$user_id");
    if (!$em) {
        error_page("No request found");
    }
    if ($em->status != EM_PENDING) {
        error_page("Request is not pending");
    }
}

function decide_form($ens, $ens_info, $user_id) {
    check_request_pending($ens->id, $user_id);
    page_head("Accept or decline membership request");
    $user = BoincUser::lookup_id($user_id);
    echo "<a href=hm_user.php?user_id=$user->id>$user->name</a> has requested membership in $ens->name.<p>";
    form_start("ensemble_join.php");
    form_input_hidden('user_id', $user_id);
    form_input_hidden('ens_id', $ens->id);
    form_input_hidden('action', 'confirm');
    form_radio_buttons('', 'accept', [[1, 'Accept'], [0, 'Decline']], 1);
    form_submit('OK', 'name=confim value=on');
    form_end();
    page_tail();
}

function decide_action($ens, $ens_info, $user_id) {
    check_request_pending($ens->id, $user_id);
    $accept = get_int('accept');
    EnsembleMember::update(
        sprintf('status=%d where ensemble_id=%d and user_id=%d',
            $accept?EM_APPROVED:EM_DECLINED,
            $ens->id, $user_id
        )
    );

    // notify the requester
    //
    BoincNotify::replace(
        sprintf(
            "create_time=%d, userid=%d, type=%d, opaque=%d, id2=%d, sent_by_email=0",
            time(), $user_id, NOTIFY_ENS_JOIN_REPLY, $ens->id, $accept
        )
    );
    $user = BoincUser::lookup_id($user_id);
    email_if_immediate($user);
    page_head(
        sprintf('Membership request %s', $accept?'accepted':'declined')
    );
    echo sprintf(
        'You %s the request by %s for membership in %s.<p>',
        $accept?"accepted":"declined",
        $user->name,
        $ens->name
    );
    echo "
        If you like, <a href=pm.php?action=new&userid=$user_id>send $user->name a message</a>.
    ";
    page_tail();

    // remove the notification to founder, if any
    //
    $user = get_logged_in_user();
    BoincNotify::delete_aux(
        sprintf("userid=%d and type=%d and opaque=%d and id2=%d",
            $user->id, NOTIFY_ENS_JOIN_REQ, $ens->id, $user_id
        )
    );
}

function resign($ens, $user) {
    $em = EnsembleMember::lookup(
        sprintf("ensemble_id=%d and user_id=%d and status=%d",
            $ens->id, $user->id, EM_APPROVED
        )
    );
    if (!$em) {
        error_page("You're not a member of $ens->name");
    }
    page_head("Confirm resignation from $ens->name");
    echo "Do you really want to resign from $ens->name?<p>";
    mm_show_button(
        "ensemble_join.php?action=resign_confirmed&ens_id=$ens->id",
        "Resign"
    );
    page_tail();
}

function resign_confirmed($ens, $user) {
    $em = EnsembleMember::lookup(
        sprintf("ensemble_id=%d and user_id=%d and status=%d",
            $ens->id, $user->id, EM_APPROVED
        )
    );
    if (!$em) {
        error_page("You're not a member of $ens->name");
    }
    $em->delete_aux("user_id=$user->id and ensemble_id=$ens->id");
    page_head("Resigned from $ens->name");
    echo "You have resigned from $ens->name.
        <p><p>
        If you like,
        <a href=pm.php?action=new&userid=$ens->user_id>send the founder a message</a>.
    ";
    page_tail();

    // notify the founder
    //
    BoincNotify::replace(
        sprintf(
            "userid=%d, create_time=%d, type=%d, opaque=%d, id2=%d, sent_by_email=0",
            $ens->user_id, time(), NOTIFY_ENS_QUIT, $ens->id, $user->id
        )
    );
    email_if_immediate(BoincUser::lookup_id($ens->user_id));
}

function remove_list($ens) {
    page_head("Remove members from $ens->name");
    $ems = EnsembleMember::enum(
        sprintf("ensemble_id=%d and status=%d", $ens->id, EM_APPROVED)
    );
    start_table();
    foreach ($ems as $em) {
        $user = BoincUser::lookup_id($em->user_id);
        row2(
            "<a href=user.php?user_id=$user->id>$user->name</a>",
            mm_button_text(
                "ensemble_join.php?action=remove&ens_id=$ens->id&user_id=$user->id",
                "Remove", BUTTON_SMALL
            )
        );
    }
    end_table();
    page_tail();
}

function remove($ens, $rem_user_id) {
    $user = BoincUser::lookup_id($rem_user_id);
    if (!$user) error_page("no such user");
    page_head("Confirm remove member");
    echo "Are you sure you want to remove $user->name from $ens->name?<p>";
    mm_show_button("ensemble_join.php?action=remove_confirmed&ens_id=$ens->id&user_id=$user->id",
        "Remove member"
    );
    page_tail();
}

function remove_confirmed($ens, $rem_user_id) {
    EnsembleMember::delete_aux("ensemble_id=$ens->id and user_id=$rem_user_id");
    $user = BoincUser::lookup_id($rem_user_id);
    page_head("Member removed");
    echo "$user->name has been removed from $ens->name.";
    echo "
        If you like, <a href=pm.php?action=new&userid=$user->id>send $user->name a message</a>.
    ";
    page_tail();

    // notify the removed user
    //
    BoincNotify::replace(
        sprintf(
            "userid=%d, create_time=%d, type=%d, opaque=%d, id2=0, sent_by_email=0",
            $rem_user_id, time(), NOTIFY_ENS_REMOVE, $ens->id
        )
    );
    email_if_immediate($user);
}

$user = get_logged_in_user();

if (post_str('submit', true)) {
    $ens_id = post_int('ens_id');
} else {
    $ens_id = get_int('ens_id');
}
$ens = Ensemble::lookup_id($ens_id);
if (!$ens) error_page("No such ensemble");
$ens_info = read_profile($ens->id, ENSEMBLE);
if (!$ens_info) error_page("No such ensemble");

if (post_str('submit', true)) {
    join_action($ens, $ens_info, $user);
} else {
    $action = get_str('action', true);
    if ($action == 'review') {
        if ($ens->user_id != $user->id) error_page("not founder");
        $ens_info = read_profile($ens_id, ENSEMBLE);
        review_form($ens, $ens_info);
    } else if ($action == 'decide') {
        if ($ens->user_id != $user->id) error_page("not founder");
        $user_id = get_int('user_id');
        decide_form($ens, $ens_info, $user_id);
    } else if ($action == 'confirm') {
        if ($ens->user_id != $user->id) error_page("not founder");
        $user_id = get_int('user_id');
        decide_action($ens, $ens_info, $user_id);
    } else if ($action == 'resign') {
        resign($ens, $user);
    } else if ($action == 'resign_confirmed') {
        resign_confirmed($ens, $user);
    } else if ($action == 'remove_list') {
        if ($ens->user_id != $user->id) error_page("not founder");
        remove_list($ens);
    } else if ($action == 'remove') {
        if ($ens->user_id != $user->id) error_page("not founder");
        $rem_user_id = get_int("user_id");
        remove($ens, $rem_user_id);
    } else if ($action == 'remove_confirmed') {
        if ($ens->user_id != $user->id) error_page("not founder");
        $rem_user_id = get_int("user_id");
        remove_confirmed($ens, $rem_user_id);
    } else {
        if (!$ens_info->seeking_members) error_page("Not seeking members");
        join_form($ens, $ens_info);
    }
}

?>
