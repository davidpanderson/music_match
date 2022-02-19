<?php

define('DEBUG', false);

require_once("../inc/zip.inc");

define('STYLE_LIST', array(
    'baroque' => 'Baroque',
    'classical' => 'Classical',
    'romantic' => 'Romantic',
    'impressionist' => 'Impressionist',
    'minimalist' => 'Minimalist',
    'modern' => 'Modern',
    'new_age' => 'New age',
    'atonal' => 'Atonal'
));

define('INST_LIST_COARSE', array(
    'keyboard' => 'Piano/organ/keyboard',
    'woodwinds' => 'Woodwinds',
    'strings' => 'Strings',
    'brass' => 'Brass',
    'percussion' => 'Percussion',
    'vocal' => 'Vocal'
));

define('INST_LIST_FINE', array(
    'bass' => 'Double bass',
    'cello' => 'Cello',
    'clarinet' => 'Clarinet',
    'flute' => 'Flute',
    'guitar' => 'Guitar',
    'harp' => 'Harp',
    'french_horn' => 'French horn',
    'oboe' => 'Oboe',
    'organ' => 'Organ',
    'percussion' => 'Percussion',
    'piano' => 'Piano',
    'trombone' => 'Trombone',
    'trumpet' => 'Trumpet',
    'tuba' => 'Tuba',
    'viola' => 'Viola',
    'violin' => 'Violin',
    'vocal_soprano' => 'Vocal (Soprano)',
    'vocal_alto' => 'Vocal (Alto)',
    'vocal_tenor' => 'Vocal (Tenor)',
    'vocal_bass' => 'Vocal (Bass)',
));

define('LEVEL_LIST', array(
    'beg' => 'Beginning',
    'int' => 'Intermediate',
    'adv' => 'Advanced'
));

define('ENSEMBLE_TYPE_LIST', array(
    'orchestra' => 'Orchestra',
    'choir' => 'Choir',
    'chamber' => 'Chamber group',
    'solo' => 'Solo',
    'solo_accomp' => 'Accompanied solo'
));

define('TECH_AREA_LIST', array(
    'recording' => 'Recording',
    'score_edit' => 'Score editing',
    'post_production' => 'Post production',
    'live_stream' => 'Live streaming',
));

define('SOFTWARE_LIST', array(
    'logic_pro' => 'Logic Pro',
    'ableton' => 'Ableton Live',
    'garage_band' => 'Garage Band',
    'sibelius' => 'Sibelius',
    'finale' => 'Finale',
    'musescore' => 'MuseScore',
    'pro_tools' => 'Pro Tools',
));

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

// values for "role"
//
define('COMPOSER', 0);
define('PERFORMER', 1);
define('TECHNICIAN', 2);

function role_dir($role) {
    switch ($role) {
    case COMPOSER: return "composer";
    case PERFORMER: return "performer";
    case TECHNICIAN: return "technician";
    }
}

function profile_exists($user_id, $role) {
    $dir = role_dir($role);
    $fname = "$dir/$user_id.json";
    return file_exists($fname);
}

function read_profile($user_id, $role) {
    $dir = role_dir($role);
    $fname = "$dir/$user_id.json";
    if (file_exists($fname)) {
        $p = json_decode(file_get_contents($fname));
    } else {
        $p = new StdClass;
    }
    switch ($role) {
    case COMPOSER:
        if (!array_key_exists('style', $p)) $p->style = array();
        if (!array_key_exists('style_custom', $p)) $p->style_custom = array();
        if (!array_key_exists('inst', $p)) $p->inst = array();
        if (!array_key_exists('inst_custom', $p)) $p->inst_custom = array();
        if (!array_key_exists('level', $p)) $p->level = array();
        if (!array_key_exists('influence', $p)) $p->influence = array();
        if (!array_key_exists('signature_filename', $p)) $p->signature_filename = '';
        if (!array_key_exists('link', $p)) $p->link = array();
        break;
    case PERFORMER:
        if (!array_key_exists('style', $p)) $p->style = array();
        if (!array_key_exists('style_custom', $p)) $p->style_custom = array();
        if (!array_key_exists('inst', $p)) $p->inst = array();
        if (!array_key_exists('inst_custom', $p)) $p->inst_custom = array();
        if (!array_key_exists('level', $p)) $p->level = array();
        if (!array_key_exists('signature_filename', $p)) $p->signature_filename = '';
        if (!array_key_exists('link', $p)) $p->link = array();
        break;
    }
    return $p;
}

function write_profile($user_id, $profile, $role) {
    $dir = role_dir($role);
    $fname = "$dir/$user_id.json";
    $f = fopen($fname, "w");
    fwrite($f, json_encode($profile, JSON_PRETTY_PRINT));
    fclose($f);
}

function get_profiles($role) {
    $dir = role_dir($role);
    $x = array();
    foreach(scandir($dir) as $f) {
        if (str_starts_with($f, '.')) continue;
        if (!str_ends_with($f, '.json')) continue;
        $id = (int)$f;
        $x[$id] = read_profile($id, $role);
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
function profile_summary($user, $profile, $role) {
    $x = sprintf('Instruments: %s',
        lists_to_string(
            $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, $profile->inst_custom
        )
    );
    $x .= sprintf('<br>Styles: %s',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    $x .= sprintf('<br>Levels: %s',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    if ($role==COMPOSER && $profile->influence) {
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

function profile_summary_table($user, $profile, $role) {
    row2('Instruments',
        lists_to_string(
            $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
            $profile->inst, $profile->inst_custom
        )
    );
    row2('Styles',
        lists_to_string(
            STYLE_LIST, $profile->style, $profile->style_custom
        )
    );
    row2('Levels',
        lists_to_string(LEVEL_LIST, $profile->level)
    );
    if ($role==COMPOSER && $profile->influence) {
        row2('Influences',
            implode(", ", $profile->influence)
        );
    }
    if ($profile->link) {
        row2('Links',
            links_to_string($profile)
        );
    }
    $u = get_logged_in_user();
    if ($u->id != $user->id && $user->country) {
        row2('Country',
            country_distance($user)
        );
    }
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
function show_profile_2col($user, $profile, $role) {
    $x1 = name_link($user, $profile);
    $x2 = profile_summary($user, $profile, $role);
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
function profile_summary_row($user, $profile, $role) {
    $x = array();
    $x[] = name_link($user, $profile);
    $x[] = lists_to_string(
        $role==COMPOSER?INST_LIST_COARSE:INST_LIST_FINE,
        $profile->inst, $profile->inst_custom, "<br>"
    );
    $x[] = lists_to_string(
        STYLE_LIST, $profile->style, $profile->style_custom, "<br>"
    );
    $x[] = lists_to_string(LEVEL_LIST, $profile->level, null, "<br>");
    if ($role==COMPOSER) {
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
function profile_summary_header($name_header, $role) {
    $x= array($name_header, "Instruments", "Styles", "Level");
    if ($role==COMPOSER) $x[] = "Influences";
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
