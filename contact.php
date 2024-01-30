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

require_once("../inc/util.inc");
require_once("../inc/email.inc");
require_once("../inc/mm.inc");
require_once("../inc/recaptchalib.php");


function form($user) {
    global $recaptcha_public_key;
    page_head("Contact Music Match", null, null, null, boinc_recaptcha_get_head_extra());
    echo "
        <p>
        Please let us know if
        <p>
        <ul>
        <li> Something doesn't work or is confusing.
        <li> There's a feature you'd like to see.
        <li> Other users are behaving inappropriately
            (spam, abusive language, etc.)
        </ul>
        <p><br>
    ";
    form_start("contact.php", "POST");
    if ($user) {
        form_input_hidden("user_id", $user->id);
    } else {
        form_input_text('Your email address', 'email_addr');
    }
    form_input_textarea("Message to Music Match", 'message');
    if (!$user) {
        form_general('', boinc_recaptcha_get_html($recaptcha_public_key));
    }
    form_submit("Send", "name=submit value=on");
    form_end();
    echo "
        <p><br>
        If you're familiar with Github,
        you can also create an 'issue' on
        <a href=https://github.com/davidpanderson/music_match/> the Music Match Github repository</a>.
    ";

    page_tail();
}

function action($user) {
    global $recaptcha_private_key;
    $message = post_str('message');
    if (!$message) {
        error_page('No message');
    }

    if (strpos($message, 'SEO')!==false) {
        error_page('get lost, spammer');
    }
    if ($user) {
        $message = "(message from user $user->name email $user->email_addr ID $user->id)\n".$message;
    } else {
        if (!boinc_recaptcha_isValidated($recaptcha_private_key)) {
            error_page(
                tra("Your reCAPTCHA response was not correct. Please try again.")
            );
        }
        $e = post_str('email_addr');
        $message = "(message from $e)\n".$message;
    }
    $user = new StdClass;
    $user->email_addr = SYS_ADMIN_EMAIL;
    $user->name = "Music Match admin";
    send_email($user, "Music Match feedback", $message);

    page_head("Message sent");
    echo "
        Thanks for your feedback.
    ";
    page_tail();
}

if (post_str('submit', true)) {
    $user = get_logged_in_user(false);
    action($user);
} else {
    $user = get_logged_in_user(false);
    form($user);
}

?>
