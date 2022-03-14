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

// view and edit account settings
//
// main page shows settings and edit links
// various forms and handlers for changing things

require_once("../inc/mm_util.inc");
require_once("../inc/user_util.inc");
require_once("../inc/account.inc");
require_once("../inc/mm.inc");
require_once("../inc/notification.inc");

function show_settings($user) {
    page_head("Account settings");
    start_table();
    row2("User name",
        sprintf('%s &nbsp;&nbsp; %s',
            $user->name,
            mm_button_text("settings.php?action=name", "edit", BUTTON_SMALL)
        )
    );
    row2("Email address",
        sprintf('%s &nbsp;&nbsp; %s',
            $user->email_addr,
            mm_button_text("settings.php?action=email_addr", "edit", BUTTON_SMALL)
        )
    );
    row2("Password",
        mm_button_text("settings.php?action=password", "edit", BUTTON_SMALL)
    );
    row2("Country",
        sprintf('%s &nbsp;&nbsp; %s',
            $user->country,
            mm_button_text("settings.php?action=country", "edit", BUTTON_SMALL)
        )
    );
    row2("Postal code",
        sprintf('%s &nbsp;&nbsp; %s',
            $user->postal_code,
            mm_button_text("settings.php?action=postal_code", "edit", BUTTON_SMALL)
        )
    );
    row2("Notification emails",
        sprintf('%s &nbsp;&nbsp; %s',
            NOTIFY_LIST[$user->send_email],
            mm_button_text("settings.php?action=notification", "edit", BUTTON_SMALL)
        )
    );
    row2("Member since", date_str($user->create_time));
    end_table();
    home_button();
    page_tail();
}

$user = get_logged_in_user();

$action = post_str('action', true);
if ($action) {
    // actions start here
    //
    switch($action) {
    case 'name':
        $name = strip_tags(post_str('name'));
        $reason = '';
        if (!is_valid_user_name($name, $reason)) {
            error_page($reason);
        }
        if ($name == $user->name) break;
        $user2 = BoincUser::lookup_name(BoincDb::escape_string($name));
        if ($user2) {
            page_head("User name in use");
            echo "The user name $name is already taken.  Try a different name.";
            page_tail();
            return;
        }
        $user->update("name='$name'");
        break;
    case 'email_addr':
        $passwd = strip_tags(post_str('passwd'));
        $passwd_hash = md5($passwd.$user->email_addr);
        if ($passwd_hash != $user->passwd_hash) {
            error_page("Wrong password");
        }
        $email_addr = strip_tags(post_str('email_addr'));
        if ($email_addr == $user->email_addr) break;
        if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
            error_page("Invalid email address");
        }
        $user2 = BoincUser::lookup_email_addr(BoincDb::escape_string($email_addr));
        if ($user2) {
            error_page("There's already an account with email address $email_addr.");
        }
        $code = random_int(100000, 999999);
        $passwd_hash = md5($passwd.$email_addr);
        $user->update(
            sprintf("seti_id=%d, email_validated=0, email_addr='%s', passwd_hash='%s'",
                $code, BoincDb::escape_string($email_addr), $passwd_hash
            )
        );
        $user->email_addr = $email_addr;
        send_email($user, 'Music Match registration',
            "Your Music Match verification code is $code"
        );
        page_head("Enter verification code");
        echo "
            We emailed a verification code to $email_addr.
            Please enter it here:
        ";
        form_start("settings.php", "POST");
        form_input_text("Verification code", 'code');
        form_submit('OK', 'name=action value=email_confirm');
        form_end();
        echo "
            If you don't see the email, check your spam folder.
        ";
        page_tail();
        exit;
    case 'email_confirm':
        $code = post_int('code');
        if ($code != $user->seti_id) {
            page_head("Verification code mismatch");
            echo "That code doesn't match the one we sent.  Please try again.";
            page_tail();
            return;
        }
        $user->update('email_validated=1');
        break;
    case 'password':
        $passwd = post_str('passwd');
        if (!$passwd) error_page("Password must be nonempty");
        if (!is_ascii($passwd)) error_page("Password must be ASCII");
        $passwd_hash = md5($passwd.$user->email_addr);
        $user->update("passwd_hash='$passwd_hash'");
        break;
    case 'country':
        $country = post_str('country', true);
        if ($country && !is_valid_country($country)) {
            error_page("invalid country");
        }
        $country = BoincDb::escape_string($country);
        $user->update("country='$country'");
        break;
    case 'postal_code':
        $postal_code = strip_tags(post_str('postal_code'));
        $postal_code = BoincDb::escape_string($postal_code);
        $user->update("postal_code='$postal_code'");
        break;
    case 'notification':
        $period = post_int('period');
        $user->update("send_email=$period");
        break;
    default:
        error_page("bad action");
    }
    Header("Location: settings.php");
} else {
    // forms start here
    //
    $action = get_str('action', true);
    if ($action) {
        switch($action) {
        case 'name':
            page_head("Change user name");
            form_start("settings.php", "POST");
            form_input_text('User name', 'name', $user->name);
            form_submit('Update', 'name=action value=name');
            form_end();
            home_button();
            page_tail();
            break;
        case 'email_addr':
            page_head("Change email address");
            form_start("settings.php", "POST");
            form_input_text('Email address', 'email_addr', $user->email_addr);
            form_input_text("Password", "passwd", "", "password", 'id="passwd"', passwd_visible_checkbox("passwd"));
            form_general('', '<a href=get_passwd.php>Forgot password?</a>');
            form_submit('Update email address', 'name=action value=email_addr');
            form_end();
            home_button();
            page_tail();
            break;
        case 'password':
            page_head("Change password");
            form_start("settings.php", "POST");
            form_input_text("New password", "passwd", "", "password", 'id="passwd"', passwd_visible_checkbox("passwd"));
            form_submit('Update', 'name=action value=password');
            form_end();
            home_button();
            page_tail();
            break;
        case 'country':
            page_head("Change country");
            form_start("settings.php", "POST");
            form_select('Country', 'country', country_select_options());
            form_submit('Update', 'name=action value=country');
            form_end();
            home_button();
            page_tail();
            break;
        case 'postal_code':
            page_head("Change postal code");
            form_start("settings.php", "POST");
            form_input_text('Postal code', 'postal_code', $user->postal_code);
            form_submit('Update', 'name=action value=postal_code');
            form_end();
            home_button();
            page_tail();
            break;
        case 'notification':
            page_head("Change notification setting");
            form_start("settings.php", "POST");
            form_radio_buttons('How often should we email you new notifications?',
                'period',
                radio_list(NOTIFY_LIST),
                $user->send_email
            );
            form_submit('Update', 'name=action value=notification');
            form_end();
            home_button();
            page_tail();
            break;
        default:
            error_page("bad action");
        }
    } else {
        show_settings($user);
    }
}

?>
