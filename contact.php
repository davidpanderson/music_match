<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

function form($user) {
    page_head("Contact Music Match");
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
    }
    form_input_textarea("Message to Music Match", 'message');
    form_submit("Send", "name=submit value=on");
    form_end();
    echo "
        <p><br>
        If you're familiar with Github,
        you can also create an 'issue' on
        <a href=https://github.com/davidpanderson/music_match/> the Music Match Github repository</a>.
    ";

    home_button();
    page_tail();
}

function action() {
    $message = post_str('message');
    if (!$message) {
        error_page('No message');
    }
    $user_id = post_int('user_id', true);
    if ($user_id) {
        $message = "(message from user $user_id)\n".$message;
    }
    $user = new StdClass;
    $user->email_addr = SYS_ADMIN_EMAIL;
    $user->name = "Music Match admin";
    send_email($user, "Music Match feedback", $message);

    page_head("Message sent");
    echo "
        Thanks for your feedback.
    ";
    home_button();
    page_tail();
}

if (post_str('submit', true)) {
    action();
} else {
    $user = get_logged_in_user(true);
    form($user);
}

?>
