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

// Music Match registration.
// There are 3 pages (forms).
// For convenience all forms and their handlers are in this file.
//
// page 1:
//  form:
//      user name, email addr
//  handler:
//      check if name or addr are taken
//      create unverified user record
//      send email with code
//      send cookie with auth
//
// page 2:
//  form:
//      code
//  handler:
//      check code
//
// page 3:
//  form:
//      password, country, postal code
//  handler:
//      check code again
//      update user record, mark as verified
//      show Intro page

$show_home_link = false;

require_once("../inc/util.inc");
require_once("../inc/user_util.inc");
require_once("../inc/boinc_db.inc");
require_once("../inc/account.inc");

function form1() {
    page_head("Create account");
    form_start("signup.php", "POST", "name=f");
    form_input_text("User name<br><font size=-1>Your real name or a pseudonym</font>", 'name');
    form_focus("f", "name");
    form_input_text("Email address", 'email_addr');
    form_submit("OK", 'name=action value=form1');
    form_end();
    page_tail();
}

function handler1() {
    $name = strip_tags(post_str('name'));
    $reason = '';
    if (!is_valid_user_name($name, $reason)) {
        error_page($reason);
    }
    $email_addr = strip_tags(post_str('email_addr'));
    if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
        error_page("Invalid email address");
    }

    $user = BoincUser::lookup_name(BoincDb::escape_string($name));
    if ($user) {
        page_head("User name in use");
        echo "The user name $name is already taken.  Try a different name.";
        page_tail();
        return;
    }
    $user = BoincUser::lookup_email_addr(BoincDb::escape_string($email_addr));
    if ($user) {
        if ($user->email_validated) {
            error_page("There's already an account with email address $email_addr.");
        }
        // otherwise contine
    } else {
        $user = make_user($email_addr, $name, '');
    }
    $code = random_int(1000, 9999);
    $user->update("seti_id=$code");
    send_email($user, 'Music Match registration',
        "Your Music Match verification code is $code"
    );
    send_cookie('auth', "$user->authenticator", true);
    form2($user);
}

// come here if mm_get_logged_in_user() found and unverified account
//
function verify() {
    $user = get_logged_in_user();
    $code = random_int(1000, 9999);
    $user->update("seti_id=$code");
    send_email($user, 'Music Match registration',
        "Your Music Match verification code is $code"
    );
    form2($user);
}

function form2($user) {
    page_head("Enter verification code");
    echo "
        <p>
        We emailed a verification code to $user->email_addr.
        Please enter it here:
        <p>
    ";
    form_start("signup.php", "POST");
    form_input_text("Verification code", 'code');
    form_submit('OK', 'name=action value=form2');
    form_end();
    echo "
        If you don't see the email, check your spam folder.
    ";
    page_tail();
}

function handler2() {
    $user = get_logged_in_user();
    $code = post_int('code');
    if ($code != $user->seti_id) {
        page_head("Verification code mismatch");
        echo "That code doesn't match the one we sent.  Please try again.";
        page_tail();
        return;
    }
    form3($code);
}

function form3($code) {
    page_head("Account setup");
    form_start("signup.php", "POST");
    form_input_text("Password", "passwd", "", "password", 'id="passwd"', passwd_visible_checkbox("passwd"));
    form_select('Country', 'country', country_select_options());
    form_input_text('Postal code<br><small>Used for identifying nearby users</small>', 'postal_code');
    form_input_hidden('code', $code);
    form_submit('OK', 'name=action value=form3');
    form_end();
    page_tail();
}

function handler3() {
    $user = get_logged_in_user();
    $code = post_int('code');
    if ($code != $user->seti_id) {
        error_page('Bad verification code');
    }
    $passwd = post_str('passwd');
    if (!$passwd) error_page("Password must be nonempty");
    if (!is_ascii($passwd)) error_page("Password must be ASCII");
    $passwd_hash = md5($passwd.$user->email_addr);

    $country = post_str('country', true);
    if ($country && !is_valid_country($country)) {
        error_page("invalid country");
    }

    $postal_code = strip_tags(post_str('postal_code'));

    $user->update(
        sprintf("email_validated=1, passwd_hash='%s', country='%s', postal_code='%s'",
            $passwd_hash, BoincDb::escape_string($country),
            BoincDb::escape_string($postal_code)
        )
    );
    Header("Location: home.php");
}

$action = post_str('action', true);
if ($action) {
    switch($action) {
    case 'form1':
        handler1();
        break;
    case 'form2':
        handler2();
        break;
    case 'form3':
        handler3();
        break;
    default:
        error_page("unknown post action $action");
    }
} else {
    $action = get_str('action', true);
    if ($action) {
        switch($action) {
        case 'verify':
            verify();
            break;
        default:
            error_page("unknown get action $action");
        }
    } else {
        form1();
    }
}

?>
