<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2021 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

require_once("../inc/boinc_db.inc");
require_once("../inc/email.inc");
require_once("../inc/pm.inc");
require_once("../inc/forum.inc");
require_once("../inc/akismet.inc");

check_get_args(array("replyto", "deleted", "userid", "action", "sent", "id", "tnow", "ttok", "teamid"));

function show_block_link($userid) {
    echo " <a href=\"pm.php?action=block&amp;id=$userid\">";
    show_image(REPORT_POST_IMAGE, tra("Block messages from this user"), tra("Block user"), REPORT_POST_IMAGE_HEIGHT);
    echo "</a>";
}

$logged_in_user = get_logged_in_user();
update_visit_time($logged_in_user);
BoincForumPrefs::lookup($logged_in_user);

function make_script() {
    echo "
        <script type=\"text/javascript\">
        function set_all(val) {
            f = document.msg_list;
            n = f.elements.length;
            for (i=0; i<n; i++) {
                e = f.elements[i];
                if (e.type=='checkbox') {
                    e.checked = val;
                }
            }
        }
        </script>
    ";
}

// show all private messages,
// and delete notifications of new messages
//
function do_inbox($logged_in_user) {
    page_head(tra("Private messages").": ".tra("Inbox"));

    make_script();
    if (get_int("sent", true) == 1) {
        echo "<h3>".tra("Your message has been sent.")."</h3>\n";
    }
    $options = get_output_options($logged_in_user);

    BoincNotify::delete_aux("userid=$logged_in_user->id and type=".NOTIFY_PM);

    $msgs = BoincPrivateMessage::enum(
        sprintf( "userid=%d and opened<>%d ORDER BY date DESC",
            $logged_in_user->id,
            PM_DELETED
        )
    );
    if (count($msgs) == 0) {
        echo tra("You have no private messages.");
    } else {
        echo "<form name=msg_list action=pm.php method=post>
            <input type=hidden name=action value=delete_selected>
        ";
        echo form_tokens($logged_in_user->authenticator);
        start_table('table-striped');
        row_heading_array(
            array(tra("Subject"), tra("Sender and date"), tra("Message")),
            array('style="width: 12em;"', 'style="width: 12em;"', "")
        );
        foreach($msgs as $msg) {
            $sender = BoincUser::lookup_id($msg->senderid);
            if (!$sender) {
                $msg->delete();
                continue;
            }
            echo "<tr>\n";
            $checkbox = "<input type=checkbox name=pm_select_$msg->id>";
            if ($msg->opened == PM_UNREAD) {
                $msg->update(sprintf("opened=%d", PM_READ));
            }
            echo "<td valign=top> $checkbox $msg->subject </td>\n";
            echo "<td valign=top>".user_links($sender, BADGE_HEIGHT_SMALL);
            echo "<br><small>".time_str($msg->date)."</small><br>";
            show_block_link($msg->senderid);
            echo "</td>\n";
            echo "<td valign=top>".output_transform($msg->content, $options)."<p>";
            $tokens = url_tokens($logged_in_user->authenticator);
            show_button_small(
                "pm.php?action=new&amp;replyto=$msg->id",
                tra("Reply"),
                tra("Reply to this message")
            );
            show_button_small(
                "pm.php?action=delete&amp;id=$msg->id&amp;$tokens",
                tra("Delete"),
                tra("Delete this message")
            );
            echo "</ul></td></tr>\n";
        }
        echo "
            <tr><td>
            <a href=\"javascript:set_all(1)\">".tra("Select all")."</a>
            |
            <a href=\"javascript:set_all(0)\">".tra("Unselect all")."</a>
            </td>
            <td colspan=2>
            <input class=\"btn btn-danger\" type=submit value=\"".tra("Delete selected messages")."\">
            </td></tr>
        ";
        end_table();
        echo "</form>\n";
    }
    page_tail();
}

function do_new($logged_in_user) {
    global $replyto, $userid;
    check_banished($logged_in_user);
    if (VALIDATE_EMAIL_TO_POST) {
        check_validated_email($logged_in_user);
    }
    pm_form($replyto, [$userid]);
}

function do_delete($logged_in_user) {
    $id = get_int("id", true);
    if ($id == null) {
        $id = post_int("id");
    }
    check_tokens($logged_in_user->authenticator);
    BoincPrivateMessage::delete_aux("userid=".$logged_in_user->id." AND id=$id");
    header("Location: pm.php");
}

function do_send($logged_in_user) {
    global $replyto, $userid;
    check_banished($logged_in_user);
    if (VALIDATE_EMAIL_TO_POST) {
        check_validated_email($logged_in_user);
    }
    check_tokens($logged_in_user->authenticator);

    $to = sanitize_tags(post_str("to_ids", true));
    $x = explode(',', $to);
    $to_ids = [];
    foreach ($x as $i) {
        $to_ids[] = intval($i);
    }
    $to_ids = array_unique($to_ids);

    $subject = post_str("subject", true);
    $content = post_str("content", true);

    if (post_str("preview", true) == tra("Preview")) {
        pm_form($replyto, $to_ids);
    }
    if (!$to_ids  || ($subject == null) && ($content == null)) {
        pm_form($replyto, $to_ids, tra("Please supply a subject and/or message"));
        return;
    }
    if (!akismet_check($logged_in_user, $content)) {
        pm_form($replyto, $to_ids, tra("Your message was flagged as spam
            by the Akismet anti-spam system.
            Please modify your text and try again.")
        );
    }

    $userlist = array();

    foreach ($to_ids as $userid) {
        $user = BoincUser::lookup_id($userid);
        if (!$user) {
            error_page("no user $userid");
        }
        BoincForumPrefs::lookup($user);
        if (is_ignoring($user, $logged_in_user)) {
            pm_form($replyto, $to_ids, tra("User %1 (ID: %2) is not accepting private messages from you.", $user->name, $user->id));
            return;
        }
        $userlist[] = $user;
    }

    foreach ($userlist as $user) {
        if (!is_moderator($logged_in_user, null)) {
            check_pm_count($logged_in_user->id);
        }
        pm_send_msg($logged_in_user, $user, $subject, $content, true);
    }

    Header("Location: pm.php?action=sent");
}

function do_block($logged_in_user) {
    $id = get_int("id");
    $user = BoincUser::lookup_id($id);
    if (!$user) {
        error_page(tra("No such user"));
    }
    page_head(tra("Block %1?", $user->name));
    echo "Are you sure you want to prevent $user->name from sending you private messages?<p>\n";

    echo "<form action=\"pm.php\" method=\"POST\">\n";
    echo form_tokens($logged_in_user->authenticator);
    echo "<input type=\"hidden\" name=\"action\" value=\"confirmedblock\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
    echo "<input class=\"btn btn-success\" type=\"submit\" value=\"".tra("Block")."\">\n";
    echo "</form>\n";
    page_tail();
}

function do_confirmedblock($logged_in_user) {
    check_tokens($logged_in_user->authenticator);
    $id = post_int("id");
    $blocked_user = BoincUser::lookup_id($id);
    if (!$blocked_user) error_page(tra("no such user"));
    add_ignored_user($logged_in_user, $blocked_user);

    page_head(tra("User %1 blocked", $blocked_user->name));

    echo "<div>".tra("User %1 has been blocked from sending you private messages.", $blocked_user->name)."\n";
    echo tra("To unblock, visit %1 message board preferences %2", "<a href=\"edit_forum_preferences_form.php\">", "</a>")."</div>\n";
    page_tail();
}

function do_delete_selected($logged_in_user) {
    check_tokens($logged_in_user->authenticator);

    $msgs = BoincPrivateMessage::enum(
        "userid=$logged_in_user->id"
    );
    foreach($msgs as $msg) {
        $x = "pm_select_$msg->id";
        if (post_str($x, true)) {
            $msg = BoincPrivateMessage::lookup_id($msg->id);
            $msg->update(sprintf('opened=%d', PM_DELETED));
        }
    }
    Header("Location: pm.php?action=inbox&deleted=1");
}

function do_outbox($user) {
    $msgs = BoincPrivateMessage::enum("senderid=$user->id order by date desc");
    $options = get_output_options($user);
    page_head("Private messages: sent");
    start_table('table-striped');
    row_heading_array(["To", "Subject", "Message"]);
    foreach($msgs as $m) {
        $u = BoincUser::lookup_id($m->userid);
        if (!$u) continue;
        row_array([
            sprintf('<a href=user.php?user_id=%d>%s</a><br><small>%s</small>',
                $u->id,
                $u->name,
                time_str($m->date)
            ),
            $m->subject,
            output_transform($m->content, $options)
        ]);
    }
    end_table();
    page_tail();
}

$replyto = get_int("replyto", true);
$userid = get_int("userid", true);

$action = sanitize_tags(get_str("action", true));
if (!$action) {
    $action = sanitize_tags(post_str("action", true));
}

if (!$action) {
    $action = "inbox";
}

if ($action == "inbox") {
    do_inbox($logged_in_user);
} elseif ($action == "new") {
    do_new($logged_in_user);
} elseif ($action == "delete") {
    do_delete($logged_in_user);
} elseif ($action == "send") {
    do_send($logged_in_user);
} elseif ($action == "block") {
    do_block($logged_in_user);
} elseif ($action == "confirmedblock") {
    do_confirmedblock($logged_in_user);
} elseif ($action == "delete_selected") {
    do_delete_selected($logged_in_user);
} elseif ($action == "sent") {
    page_head("Private messages");
    echo "Your message has been sent.";
    page_tail();
} elseif ($action == "outbox") {
    do_outbox($logged_in_user);
} else {
    error_page(tra("Unknown action"));
}

$cvs_version_tracker[]="\$Id: pm.php 14077 2007-11-03 04:26:47Z davea $";
?>
