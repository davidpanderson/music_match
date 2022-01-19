<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

function search_form($profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;

    page_head(sprintf("Search for %s", $is_comp?"composers":"performers"));
    form_start("mm_search.php", "POST");
    form_input_hidden("comp", $is_comp?1:0);
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


// each match is a triple (inst, style, level).
// compute the "value" of the match (for ranking search results)
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

    // whether to show data in N columns
    //
    $ncol = true;

    if ($ncol) {
        $name_header = sprintf(
            'Name<br><small>click for details<br>mouse over to hear audio sample%s</small>',
            $enable_tag
        );
        profile_summary_header($name_header, $is_comp);
    } else {
        echo sprintf('<tr><th %s>%s<br><small>click for details<br>mouse over to hear audio sample%s</small></th><th %s>Summary</th></tr>',
            NAME_ATTRS,
            $is_comp?"Composer":"Performer",
            $enable_tag,
            VALUE_ATTRS
        );
    }
    $found = false;
    foreach ($profiles as $user_id=>$profile) {
        if ($profile->value == 0) {
            continue;
        }
        if ($user && $user->id == $user_id) {
            // don't show user their own profile
            continue;
        }
        $found = true;
        if ($profile->signature_filename) {
            echo sprintf('<audio id=a%d><source src="%s/%d.mp3"></source></audio>',
                $user_id,
                $is_comp?"composer":"performer",
                $user_id
            );
        }
        $user = BoincUser::lookup_id($user_id);
        if ($ncol) {
            profile_summary_row($user, $profile, $is_comp);
        } else {
            show_profile_2col($user, $profile, $is_comp);
        }
    }
    end_table();
    if (!$found) {
        echo "No results found.  Try expanding your criteria.";
    }
    page_tail();
}

$user = get_logged_in_user(true);

$action = post_str("submit", true);
if ($action) {
    $is_comp = post_int("comp");
    search_action($is_comp, $user);
} else {
    if ($user) {
        $profile = read_profile($user->id, $is_comp);
    } else {
        $profile = read_profile(0, $is_comp);
    }
    $is_comp = get_int("comp");
    search_form($profile, $is_comp);
}

?>
