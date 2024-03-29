<?php

// This file is part of Music Match.
// Copyright (C) 2024 David P. Anderson
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

require_once('../inc/util.inc');
require_once('../inc/user_util.inc');
require_once('../inc/remove_user.inc');

// delete a user.
// there will still be references to them e.g. in search results.
// So need to check user lookup
//
function action($user) {
    $passwd = post_str('password');
    $passwd_hash = md5($passwd.$user->email_addr);
    if (!check_passwd_hash($user, $passwd_hash)) {
        error_page("Invalid password");
    }
    remove_user($user);
    clear_cookie('auth');
    header('Location: index.php');
}

function form() {
    page_head('Delete account');
    echo "
        <p>
        To delete your account, enter your password and
        click the button below.
        <p>
        This will remove all your information from Music Match.
        It cannot be undone.
    ";
    form_start('mm_delete_account.php', 'post');
    form_input_text('Password', 'password', '', 'password');
    form_input_hidden('submit', 1);
    form_submit('Delete my account');
    form_end();
    page_tail();
}

$user = get_logged_in_user();

if (post_int('submit', true)) {
    action($user);
} else {
    form();
}

?>
