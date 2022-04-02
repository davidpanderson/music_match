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

// create/edit a teacher profile

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// ---------------- form ------------------

function teacher_form($user, $profile) {
    page_head("Teacher profile");
    form_start(
        "teacher_edit.php",
        "POST",
        'name="fname" onsubmit="return validate_link()"'
    );
    form_general(
        "Things I teach",
        checkbox_array(
            array_merge(
                items_list(
                    TOPIC_LIST,
                    $profile->topic, "topic"
                ),
                items_custom($profile->topic_custom, "topic_custom")
            ), 3,
            form_input_text_field('topic_custom_new', TOPIC_ADD, 'text',
                text_input_default(TOPIC_ADD).'class="sm" size="20"'
            )
        )
    );

    form_general(
        "Styles I teach",
        checkbox_array(
            array_merge(
                items_list(STYLE_LIST, $profile->style, "style"),
                items_custom($profile->style_custom, "style_custom")
            ), 3,
            form_input_text_field(
                'style_custom_new', STYLE_ADD, 'text',
                text_input_default(STYLE_ADD).'class="sm" size="20"'
            )
        )
    );

    form_general(
        "Technical levels I teach",
        checkbox_array(
            items_list(LEVEL_LIST, $profile->level, "level"),
            3
        )
    );

    form_checkboxes(
        "Where I teach",
        items_list(WHERE_LIST, $profile->where, "where")
    );

    form_input_textarea(
        'Introduction<br><small>My background as a teacher</small>',
        'description',
        $profile->description
    );

    // links

    $in_url = sprintf(
        '<input name=link_url size=40 %s value="%s">',
        text_input_default(LINK_ADD_URL), LINK_ADD_URL
    );
    $in_desc = sprintf(
        '<input name=link_desc size=40 %s value="%s">',
        text_input_default(LINK_ADD_DESC), LINK_ADD_DESC
    );
    $title = 'Links<br><small>... to web pages about my teaching.</small>';
    validate_link_script('fname', 'link_url', 'link_desc');

    if ($profile->link) {
        form_checkboxes($title, items_link($profile->link, "link"));
        form_general('', "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    } else {
        form_general($title, "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    }

    $have_profile = profile_exists($user->id, TEACHER);
    form_submit($have_profile?"Update profile":"Create profile", 'name=submit value=on');
    form_end();

    if ($have_profile) {
        echo "<p>";
        mm_show_button(
            "teacher_edit.php?action=delete",
            "Delete profile",
            BUTTON_SMALL
        );
    }
    page_tail();
}

// ------------ handle submitted form ---------------

function action($user_id, $profile) {
    $profile2 = new StdClass;
    $profile2->topic = parse_list(TOPIC_LIST, 'topic');
    $profile2->topic_custom = parse_custom(
        $profile->topic_custom, "topic_custom", TOPIC_ADD
    );

    $profile2->style = parse_list(STYLE_LIST, "style");
    $profile2->style_custom = parse_custom(
        $profile->style_custom, "style_custom", STYLE_ADD
    );
    $profile2->level = parse_list(LEVEL_LIST, "level");
    $profile2->where = parse_list(WHERE_LIST, "where");
    $profile2->description = strip_tags(post_str('description'));

    foreach ($profile->link as $i=>$link) {
        if (post_str(sprintf('link_%d', $i), true)) {
            $profile2->link[] = $link;
        }
    }
    $link_url = post_str('link_url');
    if ($link_url != LINK_ADD_URL) {
        if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
            $link_url = 'https://'.$link_url;
            if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
                error_page("$link_url is not a valid URL");
            }
        }
        $link_desc = strip_tags(post_str('link_desc'));
        if (!$link_desc || $link_desc == LINK_ADD_DESC) {
            error_page("You must supply a link description");
        }
        $x = new StdClass;
        $x->url = $link_url;
        $x->desc = $link_desc;
        $profile2->link[] = $x;
    }

    return $profile2;
}

function delete_confirm_form() {
    page_head('Confirm delete profile');
    echo "<p>Are you sure you want to delete your Teacher profile?<p>";
    mm_show_button("teacher_edit.php?action=confirm",
        "Delete profile",
        BUTTON_DANGER
    );
    page_tail();
}

function do_delete_profile($user) {
    page_head("Profile deleted");
    delete_mm_profile($user->id, TEACHER);
    echo "Your Teacher profile has been deleted.";
    page_tail();
}

$user = get_logged_in_user();
update_visit_time($user);

if (post_str('submit', true)) {
    $profile = read_profile($user->id, TEACHER);
    $profile = action($user->id, $profile);
    write_profile($user->id, $profile, TEACHER);
    profile_change_notify($user, TEACHER);
    Header("Location: home.php");
} else {
    $action = get_str('action', true);
    if ($action == 'delete') {
        delete_confirm_form();
    } else if ($action == 'confirm') {
        do_delete_profile($user);
    } else {
        $profile = read_profile($user->id, TEACHER);
        teacher_form($user, $profile);
    }
}

?>
