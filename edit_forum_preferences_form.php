<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
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

// This provides the form from which the user can edit his or her
// forum preferences.  It relies upon edit_forum_preferences_action.php
// to do anything.

require_once("../inc/util.inc");
require_once("../inc/forum.inc");

check_get_args(array());

$user = get_logged_in_user();
BoincForumPrefs::lookup($user);

page_head(tra("Community preferences"));

text_counter_script();

start_table();
echo "<form method=\"post\" action=\"edit_forum_preferences_action.php\" enctype=\"multipart/form-data\">";

if (!DISABLE_FORUMS) {
// ------------ Forum identity -----------

row1(tra("Message board signature"));

$signature_by_default = $user->prefs->no_signature_by_default==false?"checked=\"checked\"":"";

$signature=$user->prefs->signature;
$maxlen=250;
row2(
    tra("Signature for message board posts").bbcode_info(),
    textarea_with_counter("signature", 250, $signature)
    ."<br><input type=\"checkbox\" name=\"signature_by_default\" ".$signature_by_default."> ".tra("Attach signature by default")
);
if ($user->prefs->signature!=""){
    row2(tra("Signature preview").
        "<br><p class=\"text-muted\">".tra("This is how your signature will look in the forums")."</p>",
        output_transform($user->prefs->signature)
    );
}

// ------------ Message display  -----------

$forum_hide_avatars = $user->prefs->hide_avatars?"checked=\"checked\"":"";
$forum_hide_signatures = $user->prefs->hide_signatures?"checked=\"checked\"":"";
$forum_link_popup = $user->prefs->link_popup?"checked=\"checked\"":""; 
$forum_image_as_link = $user->prefs->images_as_links?"checked=\"checked\"":"";
$forum_jump_to_unread = $user->prefs->jump_to_unread?"checked=\"checked\"":"";
$forum_ignore_sticky_posts = $user->prefs->ignore_sticky_posts?"checked=\"checked\"":"";
$forum_highlight_special = $user->prefs->highlight_special?"checked=\"checked\"":"";

$forum_minimum_wrap_postcount = intval($user->prefs->minimum_wrap_postcount);
$forum_display_wrap_postcount = intval($user->prefs->display_wrap_postcount);

row1(tra("Message display"));
row2(
    tra("What to display"),
    "<input type=\"checkbox\" name=\"forum_hide_avatars\" ".$forum_hide_avatars."> ".tra("Hide avatar images")."<br>
    <input type=\"checkbox\" name=\"forum_hide_signatures\" ".$forum_hide_signatures."> ".tra("Hide signatures")."<br>
    <input type=\"checkbox\" name=\"forum_images_as_links\" ".$forum_image_as_link."> ".tra("Show images as links")."<br>
    <input type=\"checkbox\" name=\"forum_link_popup\" ".$forum_link_popup."> ".tra("Open links in new window/tab")."<br>
    <input type=\"checkbox\" name=\"forum_highlight_special\" ".$forum_highlight_special."> ".tra("Highlight special users")."<br>
    <input type=\"text\" name=\"forum_display_wrap_postcount\" size=3 value=\"".$forum_display_wrap_postcount."\"> ".tra("Display this many messages per page")."<br />
    "
);

row2(tra("How to sort"),
    tra("Threads:")." ".select_from_array("forum_sort", $forum_sort_styles, $user->prefs->forum_sorting)."<br>".tra("Posts:")." ".select_from_array("thread_sort", $thread_sort_styles, $user->prefs->thread_sorting)."<br>
    <input type=\"checkbox\" name=\"forum_jump_to_unread\" ".$forum_jump_to_unread."> ".tra("Jump to first new post in thread automatically")."<br>
    <input type=\"checkbox\" name=\"forum_ignore_sticky_posts\" ".$forum_ignore_sticky_posts."> ".tra("Don't move sticky posts to top")."<br>
    "
);
}   // DISABLE_FORUMS

// ------------ Message filtering  -----------


$filtered_userlist = get_ignored_list($user);
$forum_filtered_userlist = "";
for ($i=0; $i<sizeof($filtered_userlist); $i++){
    $id = (int)$filtered_userlist[$i];
    if ($id) {
        $filtered_user = BoincUser::lookup_id($id);
        if (!$filtered_user) {
            //echo "Missing user $id";
            continue;
        }
        $forum_filtered_userlist .= sprintf(
            '%s <input class="btn-sm btn-default" type="submit" name="remove%d" value="%s"><br>',
            user_links($filtered_user),
            $filtered_user->id,
            "Unblock"
        );
    }
}

if ($forum_filtered_userlist) {
    row1(tra("Blocked users"));
    row2(
        "Ignore message board posts and private messages from these users",
        $forum_filtered_userlist
    );
}

row1(tra("Update"));
row2(tra("Click here to update preferences"), "<input class=\"btn btn-success\" type=submit value=\"".tra("Update")."\">");
echo "</form>\n";
row1(tra("Reset"));
row2(tra("Or click here to reset preferences to the defaults"),
    "<form method=\"post\" action=\"edit_forum_preferences_action.php\"><input class=\"btn btn-warning\" type=\"submit\" value=\"".tra("Reset")."\"><input type=\"hidden\" name=\"action\" value=\"reset_confirm\"></form>"
);
end_table();
page_tail();

$cvs_version_tracker[]="\$Id$";  //Generated automatically - do not edit
?>
