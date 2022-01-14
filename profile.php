<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

// create/edit a composer or performer profile

// ---------------- form ------------------

// text for custom fields

define('INST_ADD', 'Add instrument');
define('STYLE_ADD', 'Add style');
define('INFLUENCE_ADD', 'Add influence');
define('LINK_ADD_URL', 'Add link: URL');
define('LINK_ADD_DESC', 'Description');

function form($profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;
    page_head($is_comp?"Composer profile":"Performer profile");
    form_start("profile.php?foo=1", "POST", 'ENCTYPE="multipart/form-data"');
    form_input_hidden("comp", $is_comp?"1":"0");
    form_checkboxes(
        $is_comp?"Instruments you write for:":"Instruments you play",
        array_merge(
            items_list(
                $is_comp?$inst_list_comp:$inst_list_perf,
                $profile->inst, "inst"
            ),
            items_custom($profile->inst_custom, "inst_custom")
        )
    );
    form_input_text('', 'inst_custom_new', INST_ADD, 'text',
        text_input_default(INST_ADD).'class="sm" size="20"'
    );
    echo "<hr>";

    form_checkboxes(
        $is_comp?"Styles you write in:":"Styles you play",
        array_merge(
            items_list($style_list, $profile->style, "style"),
            items_custom($profile->style_custom, "style_custom")
        )
    );
    form_input_text(
        '', 'style_custom_new', STYLE_ADD, 'text',
        text_input_default(STYLE_ADD).'class="sm" size="20"'
    );

    echo "<hr>";

    form_checkboxes(
        $is_comp?"Technical levels you write for:":"Technical levels you play",
        items_list($level_list, $profile->level, "level")
    );
    echo "<hr>";

    if ($is_comp) {
        $x = "Composers/musicians who influence your work:";
        if ($profile->influence) {
            form_checkboxes(
                $x,
                items_custom($profile->influence, "influence")
            );
            form_input_text(
                '', 'influence_new', INFLUENCE_ADD, 'text',
                text_input_default(INFLUENCE_ADD).'class="sm" size="20"'
            );
        } else {
            form_input_text(
                $x, 'influence_new', INFLUENCE_ADD, 'text',
                text_input_default(INFLUENCE_ADD).'class="sm" size="20"'
            );
        }
        echo "<hr>";
    }

    $sig_title = sprintf('Audio signature MP3<br><small>A short, representative example of your %s.<br>Max size 128 MB.</small>',
        $is_comp?"composition":"playing"
    );
    if ($profile->signature_filename) {
        form_checkboxes($sig_title,
            array(array("signature_check", $profile->signature_filename, true))
        );
    } else {
        form_general($sig_title, '<input name=signature_add type=file>');
    }
    echo "<hr>";

    // links

    $in_url = sprintf(
        '<input name=link_url size=40 %s value="%s">',
        text_input_default(LINK_ADD_URL), LINK_ADD_URL
    );
    $in_desc = sprintf(
        '<input name=link_desc size=40 %s value="%s">',
        text_input_default(LINK_ADD_DESC), LINK_ADD_DESC
    );
    $title = 'Links<br><small>... to web pages with examples of your work.</small>';
    if ($profile->link) {
        form_checkboxes($title, items_link($profile->link, "link"));
        form_general('', "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    } else {
        form_general($title, "$in_url &nbsp;&nbsp;&nbsp; $in_desc");
    }

    form_submit("Update", 'name=submit value=on');
    form_end();

    show_button_small('mm_home.php', 'Return to home page');
    page_tail();
}

// ------------ handle submitted form ---------------

function action($user_id, $profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;

    $profile2 = new StdClass;
    $profile2->inst = parse_list(
        $is_comp?$inst_list_comp:$inst_list_perf, "inst"
    );
    $profile2->inst_custom = parse_custom(
        $profile->inst_custom, "inst_custom", INST_ADD
    );
    $profile2->style = parse_list($style_list, "style");
    $profile2->style_custom = parse_custom(
        $profile->style_custom, "style_custom", STYLE_ADD
    );
    $profile2->level = parse_list($level_list, "level");

    if ($is_comp) {
        $profile2->influence = parse_custom(
            $profile->influence, "influence", INFLUENCE_ADD
        );
    }

    if ($profile->signature_filename) {
        if (post_str(sprintf('signature_check'), true)) {
            $profile2->signature_filename = $profile->signature_filename;
        } else {
            $profile2->signature_filename = '';
            // remove MP3 file?
        }
    } else {
        $sig_file = $_FILES['signature_add'];
        $sig_name = $sig_file['tmp_name'];
        $orig_name = $sig_file['name'];
        if ($orig_name) {
            if (is_uploaded_file($sig_name)) {
                if (!str_ends_with(strtolower($orig_name), ".mp3")) {
                    error_page("$orig_name is not an MP3 file.");
                }
                // check if it's actully an MP3 file?
                $new_name = sprintf('%s/%d.mp3',
                    $is_comp?"composer":"performer", $user_id
                );
                if (!move_uploaded_file($sig_name, $new_name)) {
                    error_page("Couldn't move uploaded file.");
                }
                $profile2->signature_filename = $orig_name;
            } else {
                error_page("Couldn't upload $sig_name; it may be too large.");
            }
        }
    }

    foreach ($profile->link as $i=>$link) {
        if (post_str(sprintf('link_%d', $i), true)) {
            $profile2->link[] = $link;
        }
    }
    $link_url = post_str('link_url');
    if ($link_url != LINK_ADD_URL) {
        if (!filter_var($link_url, FILTER_VALIDATE_URL)) {
            error_page("$link_url is not a valid URL");
        }
        $link_desc = post_str('link_desc');
        if ($link_desc == LINK_ADD_DESC) {
            error_page("You must supply a link description");
        }
        $x = new StdClass;
        $x->url = $link_url;
        $x->desc = $link_desc;
        $profile2->link[] = $x;
    }

    return $profile2;
}

//$user = get_logged_in_user();
$user = BOINCUser::lookup_id(1);

if (post_str('submit', true)) {
    $is_comp = post_int('comp');
    $profile = read_profile($user->id, $is_comp);
    $profile = action($user->id, $profile, $is_comp);
    write_profile($user->id, $profile, $is_comp);
    Header("Location: profile.php?comp=$is_comp");
} else {
    $is_comp = get_int('comp');
    $profile = read_profile($user->id, $is_comp);
    form($profile, $is_comp);
}

?>