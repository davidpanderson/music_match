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

// search for composers or performers

require_once("../inc/util.inc");
require_once("../inc/mm.inc");
require_once("../inc/cp_profile.inc");
require_once("../inc/search.inc");

function cp_search_form($profile, $role) {
    page_head(sprintf("Search for %s", $role==COMPOSER?"composers":"performers"));
    form_start("cp_search.php", "POST");
    form_input_hidden("role", $role);
    form_checkboxes(
        sprintf("who %s", $role==COMPOSER?"write for":"play"),
        items_list($role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, "inst"
        )
    );
    if ($role == COMPOSER) {
        form_checkboxes(
            "who write for",
            items_list(ENSEMBLE_TYPE_LIST, array(), "ens_type")
        );
    }
    form_checkboxes(
       "in styles including",
        items_list(STYLE_LIST, $profile->style, "style")
    );
    form_checkboxes(
        "in difficulty levels including",
        items_list(LEVEL_LIST, $profile->level, "level")
    );
    form_checkboxes(
        "Who live close to me", array(array('close', '', false))
    );
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

// parse form args; return object with arrays of attrs
//
function get_form_args($role) {
    $x = new StdClass;
    if ($role==COMPOSER) {
        $x->inst = parse_list(INST_LIST_COARSE, "inst");
        $x->ens_type = parse_list(ENSEMBLE_TYPE_LIST, "ens_type");
    } else {
        $x->inst = parse_list(INST_LIST_FINE, "inst");
    }
    $x->style = parse_list(STYLE_LIST, "style");
    $x->level = parse_list(LEVEL_LIST, "level");
    $x->close = post_str('close', true)=='on';
    return $x;
}

function cp_search_action($role, $req_user) {
    global $audio_head_extra;

    page_head(
        sprintf("%s search results", $role==COMPOSER?"Composer":"Performer"),
        null, false, "",
        $audio_head_extra
    );

    $form_args = get_form_args($role);
    $profiles = cp_search($role, $form_args, $req_user);
    if (!$profiles) {
        echo "No results found.  Try expanding your criteria.";
        page_tail();
        return;
    }

    start_table("table-striped");
    $enable_tag = '<br><a id="enable" onclick="remove()" href=#>Enable mouse-over audio</a>';

    // whether to show data in N columns
    //
    $ncol = true;

    if ($ncol) {
        $name_header = sprintf(
            'Name<br><small>click for details<br>mouse over to hear audio sample%s</small>',
            $enable_tag
        );
        cp_profile_summary_header($name_header, $role);
    } else {
        echo sprintf('<tr><th %s>%s<br><small>click for details<br>mouse over to hear audio sample%s</small></th><th %s>Summary</th></tr>',
            NAME_ATTRS,
            $role==COMPOSER?"Composer":"Performer",
            $enable_tag,
            VALUE_ATTRS
        );
    }
    foreach ($profiles as $user_id=>$profile) {
        if ($profile->signature_filename) {
            echo sprintf('<audio id=a%d><source src="%s/%d.mp3"></source></audio>',
                $user_id,
                role_dir($role),
                $user_id
            );
        }
        if ($ncol) {
            cp_profile_summary_row($profile->user, $profile, $role);
        } else {
            show_profile_2col($profile->user, $profile, $role);
        }
    }
    end_table();
    page_tail();

    //record_search($req_user, $role, $form_args, $profiles);
}

$user = get_logged_in_user(true);

$action = post_str("submit", true);
if ($action) {
    $role = post_int("role");
    cp_search_action($role, $user);
} else {
    $role = get_int("role");
    if ($user) {
        $profile = read_profile($user->id, $role);
    } else {
        $profile = read_profile(0, $role);
    }
    cp_search_form($profile, $role);
}

?>
