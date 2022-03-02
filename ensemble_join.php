<?php

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

function join_form($ens, $ens_info) {
    page_head("Request membership in $ens_info->name");
    echo "Click below to request membership in $ens_info->name.
        You will be notified when the founder
        accepts or declines your request.
        <p>
    ";
    form_start("ensemble_join.php", "POST");
    form_input_hidden('ens_id', $ens->id);
    form_input_textarea("Message", 'message');
    form_submit("Request membership", 'name=submit value=on');
    form_end();
    page_tail();
}

function join_action($ens, $ens_info, $user) {
    EnsembleMember::insert(
        sprintf(
            "(create_time, ensemble_id, user_id, status) values (%d, %d, %d, %d)",
            time(), $ens->id, $user->id, EM_PENDING
        )
    );
    Notification::insert(
        sprintf(
            "(create_time, user_id, type, id1, id2) values (%d, %d, %d, %d, %d)",
            time(), $ens->user_id, NOTIFICATION_ENS_JOIN_REQ, $ens->id, $user->id
        )
    );
    page_head("Request submitted");
    echo "Your request to join $ens_info->name has been submitted.<p>";
    mm_show_button("mm_home.php", "Return to home page");
    page_tail();
}

// show pending membership requests
//
function review_form($ens, $ens_info) {
    $ems = EnsembleMember::enum("ensemble_id=$ens->id");
    page_head("Membership requests for $ens_info->name");
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
    echo "<a href=hm_user.php?user_id=$user->id>$user->name</a> has requested membership in $ens_info->name.<p>";
    form_start("ensemble_join.php");
    form_input_hidden('user_id', $user_id);
    form_input_hidden('ens_id', $ens->id);
    form_input_hidden('action', 'confirm');
    form_input_text("Message to $user->name", 'message');
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
    Notification::insert(
        sprintf(
            "(create_time, user_id, type, id1, id2) values (%d, %d, %d, %d, %d)",
            time(), $user_id, NOTIFICATION_ENS_JOIN_REPLY, $ens->id, $accept
        )
    );
    page_head(
        sprintf('Membership request %s', $accept?'accepted':'declined')
    );
    $user = BoincUser::lookup_id($user_id);
    echo sprintf(
        'You %s the request by %s for membership in %s.<p>',
        $accept?"accepted":"declined",
        $user->name,
        $ens_info->name
    );
    mm_show_button('mm_home.php', 'Return to home page');
    page_tail();
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
    } else {
        if (!$ens_info->seeking_members) error_page("Not seeking members");
        join_form($ens, $ens_info);
    }
}

?>