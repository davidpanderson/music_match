<?php

require_once("../inc/util.inc");

$user = get_logged_in_user();
$action = get_str("action");
$uid = get_int('user_id');
$u = BoincUser::lookup_id($uid);
if (!$u) {
    error_page("no user");
}
if ($action == 'follow') {
    $now = time();
    BoincFriend::replace("user_src=$user->id, user_dest=$uid, create_time=$now");
    page_head("Follow");
    echo "You are now following $u->name.";
    page_tail();
} else if ($action == 'unfollow') {
    BoincFriend::delete_aux("user_src = $user->id and user_dest = $uid");
    page_head("Unfollow");
    echo "You are no longer following $u->name.";
    page_tail();
} else {
    error_page("no such action");
}

?>
