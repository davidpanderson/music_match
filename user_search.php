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

require_once("../inc/boinc_db.inc");
require_once("../inc/util.inc");
require_once("../inc/user.inc");

function show_user($user) {
    row_array([
        "<a href=user.php?user_id=$user->id>$user->name</a>",
        "$user->country",
        time_str($user->create_time)
    ]);
}

function user_search_form() {
    page_head(tra("User search"));
    form_start("user_search.php", "post");
    form_input_text("User name contains", 'search_string');
    form_general(
        "Country",
        '<select class="form-control" name="country"><option value="any" selected>Any</option>'.country_select_options("asdf")."</select>"
    );
    form_submit('Search', 'name=submit value=on');
    echo "
        <script>document.f.search_string.focus()</script>
    ";
            
    page_tail();
}

function search_action() {
    $where = "true";
    $search_string = post_str('search_string');
    if (strlen($search_string)<3) {
        error_page(tra("search string must be at least 3 characters"));
    }
    $s = BoincDb::escape_string($search_string);
    $s = escape_pattern($s);
    $where .= " and name like '%$s%'";
    $country = post_str('country');
    if ($country != 'any') {
        $s = BoincDb::escape_string($country);
        $where .= " and country='$s'";
    }
    $order_clause = "name desc";

    $users = BoincUser::enum($where, "order by $order_clause limit 100");
    page_head(tra("User search results"));
    if ($users) {
        start_table('table-striped');
        row_heading_array(
            array(
                tra("Name"),
                tra("Country"),
                tra("Joined")
            )
        );
        foreach ($users as $user) {
            show_user($user);
        }
        end_table();
    } else {
        echo tra("No users match your search criteria.");
    }
    page_tail();
}

$submit = post_str('submit', true);
if ($submit) {
    search_action();
} else {
    user_search_form();
}

?>
