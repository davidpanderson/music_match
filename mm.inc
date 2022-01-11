<?php

define('DEBUG', true);

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
    1 => '1 (beginning)',
    2 => '2',
    3 => '3 (intermediate)',
    4 => '4',
    5 => '5 (advanced)'
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

// return the items in $list for which $prefix_item is set
//
function parse_list($list, $prefix) {
    $x = array();
    foreach ($list as $tag=>$name) {
        if (get_str(sprintf("%s_%s", $prefix, $tag), true)) {
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
        if (get_str(sprintf("%s_%d", $prefix, $i), true)) {
            $x[] = $name;
        }
    }
    $y = get_str(sprintf("%s_new", $prefix), true);
    if ($y && $y!=$exclude) {
        $x[] = $y;
    }
    return $x;
}

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
//      ]
//  }

define('COMPOSER', true);
define('PERFORMER', false);

function profile_exists($user, $is_comp) {
    $dir = $is_comp?"composer":"performer";
    $fname = "$dir/$user->id";
    return file_exists($fname);
}

function read_profile($user_id, $is_comp) {
    $dir = $is_comp?"composer":"performer";
    $fname = "$dir/$user_id";
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
    } else {
        if (!array_key_exists('style', $p)) $p->style = array();
        if (!array_key_exists('style_custom', $p)) $p->style_custom = array();
        if (!array_key_exists('inst', $p)) $p->inst = array();
        if (!array_key_exists('inst_custom', $p)) $p->inst_custom = array();
        if (!array_key_exists('level', $p)) $p->level = array();
    }
    return $p;
}

function write_profile($user_id, $profile, $is_comp) {
    $dir = $is_comp?"composer":"performer";
    $fname = "$dir/$user_id";
    $f = fopen($fname, "w");
    fwrite($f, json_encode($profile, JSON_PRETTY_PRINT));
    fclose($f);
}

function get_profiles($is_comp) {
    $dir = $is_comp?"composer":"performer";
    $x = array();
    foreach(scandir($dir) as $f) {
        if (str_starts_with($f, '.')) continue;
        $id = (int)$f;
        $x[$id] = read_profile($id, $is_comp);
    }
    return $x;
}

// ------------ utilities -------------

function str_starts_with ( $haystack, $needle ) {
  return strpos( $haystack , $needle ) === 0;
}
?>
