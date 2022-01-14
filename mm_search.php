<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

function search_form($profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;

    if ($is_comp) {
        page_head("Search for composers");
        form_start("mm_search.php", "POST");
        form_input_hidden("comp", "on");
    } else {
        page_head("Search for performers");
        form_start("mm_search.php", "POST");
    }
    form_checkboxes(
        sprintf("Who %s at least one of:", $is_comp?"write for":"play"),
        items_list($is_comp?$inst_list_comp:$inst_list_perf,
            $profile->inst, "inst"
        )
    );
    echo "<hr>";
    form_checkboxes(
       "in styles including at least one of:",
        items_list($style_list, $profile->style, "style")
    );
    echo "<hr>";
    form_checkboxes(
        "in difficulty levels including at least one of:",
        items_list($level_list, $profile->level, "level")
    );
    echo "<hr>";
    form_checkboxes(
        "Who live close to me", array(array('close', '', false))
    );
    echo "<hr>";
    form_submit("Search", 'name=submit value=on');
    form_end();
    page_tail();
}

// parse form args; return object with arrays of attrs
//
function get_form_args($is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;
    $x = new StdClass;
    if ($is_comp) {
        $x->inst = parse_list($inst_list_comp, "inst");
    } else {
        $x->inst = parse_list($inst_list_perf, "inst");
    }
    $x->style = parse_list($style_list, "style");
    $x->level = parse_list($level_list, "level");
    return $x;
}

// compare profile with form args
// Return object w/ number of matches of each type
// (inst, style, level)
//
function match_args($profile, $args) {
    $x = new StdClass;
    $x->inst = 0;
    $x->style = 0;
    $x->level = 0;
    foreach ($profile->inst as $i) {
        if (in_array($i, $args->inst)) {
            $x->inst++;
        }
    }
    foreach ($profile->style as $i) {
        if (in_array($i, $args->style)) {
            $x->style++;
        }
    }
    foreach ($profile->level as $i) {
        if (in_array($i, $args->level)) {
            $x->level++;
        }
    }
    return $x;
}

// show a list of instruments or styles as a string
//
function lists_to_string($title, $master_list, $list, $list2=array()) {
    $x = "<b>$title:</b>";
    $first = true;
    foreach ($list as $i) {
        if ($first) {
            $first = false;
        } else {
            $x .= ',';
        }
        $x .= ' ';
        $x .= $master_list[$i];
    }
    foreach ($list2 as $i) {
        if ($first) {
            $first = false;
        } else {
            $x .= ',';
        }
        $x .= ' ';
        $x .= "$i";
    }
    return $x;
}

// show a list of levels as string
//
function levels_to_string($title, $list) {
    $x = "<b>$title:</b>";
    $first = true;
    foreach ($list as $i) {
        if ($first) {
            $first = false;
        } else {
            $x .= ',';
        }
        $x .= " $i";
    }
    return $x;
}

// show a table row summarizing a composer profile
//
function show_profile_short($user_id, $profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;
    $user = BoincUser::lookup_id($user_id);
    $audio = "";
    if ($profile->signature_filename) {
        $audio = sprintf(' onmouseenter="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $user_id, $user_id
        );
    }
    $x1 = sprintf('<a %s href=mm_user.php?user_id=%d>%s</a>',
        $audio,
        $user_id, $user->name
    );
    $x2 = sprintf('%s<br>%s<br>%s',
        lists_to_string(
            "Instruments", $is_comp?$inst_list_comp:$inst_list_perf,
            $profile->inst, $profile->inst_custom
        ),
        lists_to_string(
            "Styles", $style_list, $profile->style, $profile->style_custom
        ),
        levels_to_string("Levels", $profile->level)
    );
    if (DEBUG) {
        $x2 .= sprintf('<br>match: %d (%d, %d, %d)',
            $profile->value,
            $profile->match->inst,
            $profile->match->style,
            $profile->match->level
        );
    }
    row2($x1, $x2);
}


// each match is a triple (inst, style, level).
// compute the the value (for ranking)
//
function match_value($match) {
    $x = 0;
    if ($match->inst) $x += 100 + $match->inst;
    if ($match->style) $x += 100 + $match->style;
    if ($match->level) $x += 100 + $match->level;
    return $x;
}

function compare_value($p1, $p2) {
    if ($p1->value > $p2->value) {
        return -1;
    } else if ($p1->value < $p2->value) {
        return 1;
    } else {
        return 0;
    }
}

function search_action($is_comp, $user) {
    $head_extra = <<<EOT
<script language="javascript" type="text/javascript">
function play_sound(id) {
    var audio = document.getElementById(id);
    audio.currentTime = 0;
    audio.play();
}

function stop_sound(id) {
    var audio = document.getElementById(id);
    audio.pause();
}
function remove() {
    var e = document.getElementById("enable");
    e.innerHTML = "";
}

</script>
EOT;

    page_head(
        sprintf("%s search results", $is_comp?"Composer":"Performer"),
        null, false, "",
        $head_extra
    );
    $form_args = get_form_args($is_comp);
    $profiles = get_profiles($is_comp);
    foreach ($profiles as $user_id=>$profile) {
        $profile->match = match_args($profile, $form_args);
        $profile->value = match_value($profile->match);
    }
    uasort($profiles, 'compare_value');
    start_table("table-striped");
    $enable_tag = '<br><a id="enable" onclick="remove()" href=#>Enable mouse-over audio</a>';
    echo sprintf('<tr><th %s>%s<br><small>click for details<br>mouse over to hear audio sample%s</small></th><th %s>Summary</th></tr>',
        NAME_ATTRS,
        $is_comp?"Composer":"Performer",
        $enable_tag,
        VALUE_ATTRS
    );
    $found = false;
    foreach ($profiles as $user_id=>$profile) {
        if ($profile->value == 0) {
            continue;
        }
        if ($user && $user->id == $user_id) {
            // don't show user their own profile
            continue;
        }
        if ($profile->signature_filename) {
            echo sprintf('<audio id=a%d><source src="%s/%d.mp3"></source></audio>',
                $user_id,
                $is_comp?"composer":"performer",
                $user_id
            );
        }
        show_profile_short($user_id, $profile, $is_comp);
    }
    end_table();
    if (!$found) {
        echo "No results found.  Try expanding your criteria.";
    }
    page_tail();
}

$user = get_logged_in_user(true);

if ($user) {
    $profile = read_profile($user->id, false);
} else {
    $profile = read_profile(0, false);
}

$action = post_str("submit", true);
if ($action) {
    $is_comp = post_str("comp", true);
    search_action($is_comp, $user);
} else {
    $is_comp = get_str("comp", true);
    search_form($profile, $is_comp);
}

?>
