<?php

define('DEBUG', false);

require_once("../inc/zip.inc");

$style_list = array(
    'baroque' => 'Baroque',
    'classical' => 'Classical',
    'romantic' => 'Romantic',
    'impressionist' => 'Impressionist',
    'minimalist' => 'Minimalist',
    'modern' => 'Modern',
    'new_age' => 'New age',
    'atonal' => 'Atonal'
);

$inst_list_comp = array(
    'keyboard' => 'Piano/organ/keyboard',
    'woodwinds' => 'Woodwinds',
    'strings' => 'Strings',
    'brass' => 'Brass',
    'percussion' => 'Percussion',
    'vocal' => 'Vocal'
);

$inst_list_perf = array(
    'cello' => 'Cello',
    'clarinet' => 'Clarinet',
    'oboe' => 'Oboe',
    'percussion' => 'Percussion',
    'piano' => 'Piano',
    'trumpet' => 'Trumpet',
    'viola' => 'Viola',
    'violin' => 'Violin',
    'vocal_soprano' => 'Vocal (Soprano)',
    'vocal_alto' => 'Vocal (Alto)',
    'vocal_tenor' => 'Vocal (Tenor)',
    'vocal_bass' => 'Vocal (Bass)',
);
$level_list = array(
    'beg' => 'Beginning',
    'int' => 'Intermediate',
    'adv' => 'Advanced'
);

// ---------- Functions for generating and parsing forms

// return a list of (tag/name/checked) triples (for form_checkboxes())
// for a predefined list of items (like instruments)
//
function items_list($list, $current, $prefix) {
    $x = array();
    foreach ($list as $tag => $name) {
        $x[] = array(sprintf("%s_%s", $prefix, $tag), $name, in_array($tag, $current));
    }
    return $x;
}

// Make checkbox list for custom (user-defined) items
//
function items_custom($current, $prefix) {
    $x = array();
    foreach ($current as $i=>$name) {
        $x[] = array(sprintf("%s_%d", $prefix, $i), $name, true);
    }
    return $x;
}

function items_link($current, $prefix) {
    $x = array();
    foreach ($current as $i=>$link) {
        $x[] = array(
            sprintf("%s_%d", $prefix, $i),
            sprintf("%s (%s)", $link->desc, $link->url),
            true
        );
    }
    return $x;
}

// return the items in $list for which $prefix_item is set
//
function parse_list($list, $prefix) {
    $x = array();
    foreach ($list as $tag=>$name) {
        if (post_str(sprintf("%s_%s", $prefix, $tag), true)) {
            $x[] = $tag;
        }
    }
    return $x;
}

// return the items in $current for which $prefix_item is set;
// if $prefix_new is present, include that as well
//
function parse_custom($current, $prefix, $exclude) {
    $x = array();
    foreach ($current as $i=>$name) {
        if (post_str(sprintf("%s_%d", $prefix, $i), true)) {
            $x[] = $name;
        }
    }
    $y = post_str(sprintf("%s_new", $prefix), true);
    if ($y && $y!=$exclude) {
        $x[] = $y;
    }
    return $x;
}

// return stuff to put into a text <input> to give it a value
// that goes away if you click there
//
function text_input_default($t) {
    return sprintf('
        onfocus="if(this.value==\'%s\'){this.value=\'\';}"
        onblur="if(this.value==\'\'){this.value=\'%s\';}"',
        $t, $t
    );
}

// ---------- reading and writing profiles

// example composer profile
//
//  {
//      "style": [
//          "modern",
//          "atonal"
//      ],
//      "style_custom": [
//          "Death Metal",
//      ],
//      "inst": [
//          "keyboard"
//      ],
//      "inst_custom": [
//          "Piano Trio"
//      ],
//      "level": [
//          1,
//          2
//      ]
//      "influence": [
//          "Igor Stravinsky"
//      ],
//      "link": [
//          {
//              "url": "https://a.b.c",
//              "desc": "Some of my works"
//          },
//          ...
//      ],
//      "signature_filename": "foo.mp3",
//      "comment": "blah blah"
//  }

define('COMPOSER', true);
define('PERFORMER', false);

function profile_exists($user_id, $is_comp) {
    $dir = $is_comp?"composer":"performer";
    $fname = "$dir/$user_id.json";
    return file_exists($fname);
}

function read_profile($user_id, $is_comp) {
    $dir = $is_comp?"composer":"performer";
    $fname = "$dir/$user_id.json";
    if (file_exists($fname)) {
        $p = json_decode(file_get_contents($fname));
    } else {
        $p = new StdClass;
    }
    if ($is_comp) {
        if (!array_key_exists('style', $p)) $p->style = array();
        if (!array_key_exists('style_custom', $p)) $p->style_custom = array();
        if (!array_key_exists('inst', $p)) $p->inst = array();
        if (!array_key_exists('inst_custom', $p)) $p->inst_custom = array();
        if (!array_key_exists('level', $p)) $p->level = array();
        if (!array_key_exists('influence', $p)) $p->influence = array();
        if (!array_key_exists('signature_filename', $p)) $p->signature_filename = '';
        if (!array_key_exists('link', $p)) $p->link = array();
    } else {
        if (!array_key_exists('style', $p)) $p->style = array();
        if (!array_key_exists('style_custom', $p)) $p->style_custom = array();
        if (!array_key_exists('inst', $p)) $p->inst = array();
        if (!array_key_exists('inst_custom', $p)) $p->inst_custom = array();
        if (!array_key_exists('level', $p)) $p->level = array();
        if (!array_key_exists('signature_filename', $p)) $p->signature_filename = '';
        if (!array_key_exists('link', $p)) $p->link = array();
    }
    return $p;
}

function write_profile($user_id, $profile, $is_comp) {
    $dir = $is_comp?"composer":"performer";
    $fname = "$dir/$user_id.json";
    $f = fopen($fname, "w");
    fwrite($f, json_encode($profile, JSON_PRETTY_PRINT));
    fclose($f);
}

function get_profiles($is_comp) {
    $dir = $is_comp?"composer":"performer";
    $x = array();
    foreach(scandir($dir) as $f) {
        if (str_starts_with($f, '.')) continue;
        if (!str_ends_with($f, '.json')) continue;
        $id = (int)$f;
        $x[$id] = read_profile($id, $is_comp);
    }
    return $x;
}

// ------------ display -------------

// show a list of instruments or styles as a string
//
function lists_to_string($master_list, $list, $list2=null, $sep=',') {
    $x = "";
    $first = true;
    foreach ($list as $i) {
        if ($first) {
            $first = false;
        } else {
            $x .= $sep;
        }
        $x .= ' ';
        $x .= $master_list[$i];
    }
    if (!$list2) {
        return $x;
    }
    foreach ($list2 as $i) {
        if ($first) {
            $first = false;
        } else {
            $x .= $sep;
        }
        $x .= ' ';
        $x .= "$i";
    }
    return $x;
}

function links_to_string($profile, $sep=',') {
    $x = "";
    $first = true;
    foreach ($profile->link as $link) {
        if ($first) {
            $first = false;
        } else {
            $x .= $sep;
        }
        $x .= sprintf(" <a href=%s>%s</a>", $link->url, $link->desc);
    }
    return $x;
}

// show profile as a list of name: value lines
//
function profile_summary($user, $profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;
    $x = sprintf('Instruments: %s',
        lists_to_string(
            $is_comp?$inst_list_comp:$inst_list_perf,
            $profile->inst, $profile->inst_custom
        )
    );
    $x .= sprintf('<br>Styles: %s',
        lists_to_string(
            $style_list, $profile->style, $profile->style_custom
        )
    );
    $x .= sprintf('<br>Levels: %s',
        lists_to_string($level_list, $profile->level)
    );
    if ($is_comp && $profile->influence) {
        $x .= "<br>Influences: ".implode(", ", $profile->influence);
    }
    if ($profile->link) {
        $x .= "<br>Links: ".links_to_string($profile);
    }
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        $x .= sprintf('<br>Country: %s', country_distance($user));
    }
    return $x;
}

// name, with link and audio mouseover
//
function name_link($user, $profile) {
    $audio = "";
    if ($profile->signature_filename) {
        $audio = sprintf(' onmouseenter="play_sound(\'a%d\');" onmouseleave="stop_sound(\'a%d\');" ',
            $user->id, $user->id
        );
    }
    return sprintf('<a %s href=mm_user.php?user_id=%d>%s</a>',
        $audio,
        $user->id, $user->name
    );
}

// show a 2-column table row summarizing a composer or performer profile
//
function show_profile_2col($user, $profile, $is_comp) {
    $x1 = name_link($user, $profile);
    $x2 = profile_summary($user, $profile, $is_comp);
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

// return country and - if possible - distance
//
function country_distance($user, $sep=' ') {
    $cur_user = get_logged_in_user(true);
    if (!$cur_user
        || ($cur_user->country != 'United States')
        || ($user->country != 'United States')
    ) {
        return $user->country;
    }
    $z1 = str_to_zip($cur_user->postal_code);
    $z2 = str_to_zip($user->postal_code);
    if (!$z1 || !$z2) {
        return $user->country;
    }
    $d = zip_dist($z1, $z2);
    return sprintf("%s%s<small>%d miles</small>", $user->country, $sep, (int)$d);
}

// show profile as an N-column table row
//
function profile_summary_row($user, $profile, $is_comp) {
    global $inst_list_comp, $inst_list_perf, $style_list, $level_list;
    $x = array();
    $x[] = name_link($user, $profile);
    $x[] = lists_to_string(
        $is_comp?$inst_list_comp:$inst_list_perf,
        $profile->inst, $profile->inst_custom, "<br>"
    );
    $x[] = lists_to_string(
        $style_list, $profile->style, $profile->style_custom, "<br>"
    );
    $x[] = lists_to_string($level_list, $profile->level, null, "<br>");
    if ($is_comp) {
        if ($profile->influence) {
            $x[] = implode("<br>", $profile->influence);
        } else {
            $x[] = '';
        }
    }
    $x[] = links_to_string($profile, "<br>");
    $x[] = country_distance($user, "<br>");
    row_array($x);
}

// table header for the above
//
function profile_summary_header($name_header, $is_comp) {
    $x= array($name_header, "Instruments", "Styles", "Level");
    if ($is_comp) $x[] = "Influences";
    $x[] = "Links";
    $x[] = "Country";
    row_heading_array($x);
}

// ------------ utilities -------------

function str_starts_with ($haystack, $needle) {
    return strpos( $haystack , $needle ) === 0;
}

function str_ends_with($haystack, $needle) {
    $length = strlen($needle);
    return $length > 0 ? substr($haystack, -$length) === $needle : true;
}

?>
