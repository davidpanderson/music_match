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

require_once("../inc/email.inc");
require_once("../inc/util.inc");
require_once("../inc/forum.inc");
require_once("../inc/friend.inc");
require_once("../inc/forum_db.inc");
require_once("../inc/notify.inc");

function total_posts($user) {
    return BoincPost::count("user=$user->id");
}

function weak_auth($user) {
    $x = md5($user->authenticator.$user->passwd_hash);
    return "{$user->id}_$x";
}

// originally user URLs were assumed to be http://,
// and this prefix wasn't stored.
// Now the prefix can be http:// or https://.
// This function takes a user URL in any form and converts
// it to a canonical form, with the protocol prefix.
//
function normalize_user_url($url) {
    $x = strtolower($url);
    if (substr($x, 0, 7) == 'http://') {
        return 'http://'.substr($url, 7);
    }
    if (substr($x, 0, 8) == 'https://') {
        return 'https://'.substr($url, 8);
    }
    return 'http://'.$url;
}

// show static user info (private)
//
function show_user_info_private($user) {
    row2(tra("Name"), $user->name);
    if (LDAP_HOST && is_ldap_email($user->email_addr)) {
        row2("LDAP ID", ldap_email_to_uid($user->email_addr));
    } else {
        $email_text = $user->email_addr;
        if (defined("SHOW_NONVALIDATED_EMAIL_ADDR") && !$user->email_validated) {
            $email_text .= " (<a href=validate_email_addr.php>must be validated</a>)";
        }
        row2(tra("Email address"), $email_text);
    }
    if (USER_URL) {
        if (strlen($user->url)) {
            $u = normalize_user_url($user->url);
            row2(tra("URL"), sprintf('<a href="%s">%s</a>', $u, $u));
        }
    }
    if (USER_COUNTRY) {
        row2(tra("Country"), $user->country);
    }
    if (POSTAL_CODE) {
        row2(tra("Postal code"), $user->postal_code);
    }
    row2(tra("%1 member since", PROJECT), date_str($user->create_time));
    $url_tokens = url_tokens($user->authenticator);
    if (LDAP_HOST && is_ldap_email($user->email_addr)) {
        // LDAP accounts can't change email or password
        //
        row2(tra("Change"),
            "<a href=\"edit_user_info_form.php?$url_tokens\">Account info</a>"
        );
    } else {
        $delete_account_str = "";
        $config = get_config();
        if (parse_bool($config, "enable_delete_account")) {
            $delete_account_str = " &middot; <a href=\"delete_account_request.php\">".tra("delete account")."</a>";
        }

        row2(tra("Change"),
            "<a href=\"edit_email_form.php\">".tra("email address")."</a>
            &middot; <a href=\"".secure_url_base()."/edit_passwd_form.php\">".tra("password")."</a>
            &middot; <a href=\"edit_user_info_form.php?$url_tokens\">".tra("other account info")."</a>"
            .$delete_account_str
        );
    }
    row2(tra("User ID")."<br/><p class=\"small\">".tra("Used in community functions")."</p>", $user->id);
    if (!NO_COMPUTING) {
        row2(
            tra("Account keys"),
            "<a href=\"weak_auth.php\">".tra("View")."</a>"
        );
        
        require_once("../inc/account_ownership.inc");
        if (file_exists($account_ownership_private_key_file_path)) {
          // If the server has keys configured show the account ownership form
          row2(
              tra("Account Ownership"),
              "<a href=\"account_ownership.php?$url_tokens\">Generate ownership proof</a>"
          );
        }

    }
}

// show user name, with links to profile if present.
// if $badge_height is > 0, show badges
// if $name_limit, limit name to N chars
//
function user_links($user, $badge_height=0, $name_limit=0) {
    BoincForumPrefs::lookup($user);
    if (is_banished($user)) {
        return "(banished: ID $user->id)";
    }
    $x = "";
    if ($user->has_profile) {
        $img_url = url_base()."img/head_20.png";
        $x .= ' <a href="'.url_base().'view_profile.php?userid='.$user->id.'"><img title="View the profile of '.$user->name.'" src="'.$img_url.'" alt="Profile"></a>';
    }
    $name = $user->name;
    if ($name_limit && strlen($name) > $name_limit) {
        $name = substr($name, 0, $name_limit)."...";
    }
    $x .= " <a href=\"".url_base()."user.php?user_id=".$user->id."\">".$name."</a>";
    if (function_exists("project_user_links")){
        $x .= project_user_links($user);
    }
    if ($badge_height) {
        $x .= badges_string(true, $user, $badge_height);
    }
    return $name_limit?"<nobr>$x</nobr>":$x;
}

// show summary of dynamic and static info (public)
//
function show_user_summary_public($user) {
    global $g_logged_in_user;
    row2(tra("User ID"), $user->id);
    row2(tra("%1 member since", PROJECT), date_str($user->create_time));
    if (USER_COUNTRY) {
        row2(tra("Country"), $user->country);
    }
    if (USER_URL) {
        // don't show URL if user has no recent credit (spam suppression)
        //
        if (strlen($user->url)) {
            if (!NO_COMPUTING || $user->expavg_credit > 1) {
                $u = normalize_user_url($user->url);
                row2(tra("URL"), sprintf('<a href="%s">%s</a>', $u, $u));
            }
        }
    }
    if (!NO_COMPUTING) {
        show_credit($user);

        if ($user->show_hosts) {
            row2(tra("Computers"), "<a href=\"".url_base()."hosts_user.php?userid=$user->id\">".tra("View")."</a>");
        } else {
            row2(tra("Computers"), tra("hidden"));
        }
    }
    if (function_exists("project_user_summary_public")) {
        project_user_summary_public($user);
    }
}

function user_link($user) {
    return sprintf("<a href=user.php?user_id=%d>%s</a>", $user->id, $user->name);
}

function community_links($user, $logged_in_user){
    $following = BoincFriend::enum("user_src=$user->id");
    $followers = BoincFriend::enum("user_dest=$user->id");
    $tot = total_posts($user);

    if ($tot) {
        row2(tra("Message boards"), "<a href=\"".url_base()."forum_user_posts.php?userid=$user->id\">".tra("%1 posts", $tot)."</a>");
    }

    if ($logged_in_user->id != $user->id) {
        row2("Contact",
            mm_button_text(
                "pm.php?action=new&userid=$user->id",
                "Send private message",
                BUTTON_SMALL
            )
        );
    }

    $x = [];
    $found = false;
    foreach ($followers as $f) {
        if ($f->user_src == $logged_in_user->id) {
            $found = true;
        } else {
            $u = BoincUser::lookup_id($f->user_src);
            $x[] = user_link($u);
        }
    }
    if (!$x) $x[] = '---';
    if ($found) {
        $x[] = sprintf("You are following %s. %s",
            $user->name,
            mm_button_text(
                "follow.php?action=unfollow&user_id=$user->id",
                "Unfollow", BUTTON_SMALL
            )
        );
    } else if ($user->id != $logged_in_user->id) {
        $x[] = mm_button_text(
            "follow.php?action=follow&user_id=$user->id",
            "Follow $user->name",
            BUTTON_SMALL
        );
    }
    row2("Followers", implode($x, '<br>'));

    $x = [];
    foreach ($following as $f) {
        $u = BoincUser::lookup_id($f->user_dest);
        $x[] = user_link($u);
    }
    if ($x) {
        row2("$user->name is following", implode($x, '<br>'));
    }
}

function show_profile_link($user) {
    if ($user->has_profile) {
        row2(tra("Profile"), "<a href=\"view_profile.php?userid=$user->id\">".tra("View")."</a>");
    }
}

function show_account_private($user) {
    grid(
        false,
        function() use ($user) {
            start_table();
            row1(tra("Account information"), 2, 'heading');
            show_user_info_private($user);
            show_preference_links();
            show_user_stats_private($user);

            if (function_exists('show_user_donations_private')) {
                show_user_donations_private($user);
            }
            end_table();
            if (!NO_COMPUTING) {
                show_other_projects($user, true);
            }
            if (function_exists("project_user_page_private")) {
                project_user_page_private($user);
            }
        },
        function() use ($user) {
            start_table();
            row1(tra("Community"));
            show_community_private($user);
            end_table();
        }
    );
}


$cvs_version_tracker[]="\$Id$";  //Generated automatically - do not edit

?>
