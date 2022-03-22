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

define('DEBUG', false);

require_once("../inc/zip.inc");

// DB fields

// ensemble_member.status

define('EM_PENDING',    0);
define('EM_APPROVED',   1);
define('EM_DECLINED',   2);
define('EM_REMOVED',    3);

// notify.type

// 1-4 are defined in forum_db.inc
// friend req, friend accept, pm, subscribed post

// NOTIFY_FRIEND_REQ
//  opaque is ID of other user
// NOTIFY_FRIEND_ACCEPT
//  opaque is ID of other user
// NOTIFY_PM
//  opaque is message ID
// NOTIFY_SUBSCRIBED_POST
//  opaque is thread ID

define('NOTIFY_SEARCH',         5);
    // a previous search has new result
    // opaque and id2 are zero
define('NOTIFY_ENS_DEL',        6);
    // an ensemble you're in was deleted
    // opaque is ens ID
define('NOTIFY_PROFILE',        7);
    // a friend changed a profile
    // opaque is user ID; id2 is role
define('NOTIFY_ENS_JOIN_REQ',   8);
    // someone asked to join an ensemble you founded
    // opaque is ens ID, id2 is user ID
define('NOTIFY_ENS_JOIN_REPLY', 9);
    // your ensemble join request was accepted or declined
    // opaque is ens ID; id2 is 1 if accepted
define('NOTIFY_ENS_REMOVE',     10);
    // you were removed from an ensemble
    // opaque is ens ID
define('NOTIFY_ENS_QUIT',       11);
    // someone quit an ensemble you founded
    // opaque is ens ID; id2 is user ID

// values for "role"; each has an associated JSON structure
//
define('COMPOSER', 0);
define('PERFORMER', 1);
define('TECHNICIAN', 2);
define('ENSEMBLE', 3);

// removing or changing keys will invalidate existing profiles

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
    'cello' => 'Cello',
    'clarinet' => 'Clarinet',
    'bass' => 'Double bass',
    'flute' => 'Flute',
    'french_horn' => 'French horn',
    'guitar' => 'Guitar',
    'harp' => 'Harp',
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
    'opera' => 'Opera company',
    'chamber' => 'Chamber group',
    'duo' => 'Duo',
));

define('TECH_AREA_LIST', array(
    'score_edit' => 'Score editing',
    'recording' => 'Recording',
    'post_production' => 'Post-production',
    'live_stream' => 'Live streaming',
));

define('PROGRAM_LIST', array(
    'ableton' => 'Ableton Live',
    'ardour' => 'Ardour',
    'finale' => 'Finale',
    'garage_band' => 'Garage Band',
    'logic_pro' => 'Logic Pro',
    'musescore' => 'MuseScore',
    'pro_tools' => 'Pro Tools',
    'sibelius' => 'Sibelius',
));

// ---------- Functions for generating and parsing forms (search, edit)

// text for custom fields

define('INST_ADD', 'Other instrument');
define('STYLE_ADD', 'Other style');
define('INFLUENCE_ADD', 'Add influence');
define('LINK_ADD_URL', 'Add link: URL');
define('LINK_ADD_DESC', 'Description');
define('ENSEMBLE_TYPE_ADD', 'Other ensemble type');
define('TECH_AREA_ADD', 'Other area');
define('PROGRAM_ADD', 'Other program');

// ----- checkbox stuff

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
        $x[] = array(
            sprintf("%s_%d", $prefix, $i),
            $name,
            true
        );
    }
    return $x;
}

// same, for user-supplied links
//
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
// if $prefix_new is present, include that as well;
// apply strip_tags() to it.
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
        $x[] = strip_tags($y);
    }
    return $x;
}

// parse a boolean checkbox
//
function parse_post_bool($name) {
    return post_str($name, true)?1:0;
}

// ------- radio

// convert array to list of pairs
//
function radio_list($list) {
    $x = array();
    foreach ($list as $tag=>$name) {
        $x[] = array($tag, $name);
    }
    return $x;
}

// show yes/no/either radio buttons for a bool
//
function radio_bool($label, $name) {
    form_radio_buttons(
        $label,
        $name,
        array(
            array('yes', 'Yes'),
            array('no', 'No'),
            array('either', 'Either')
        ),
        'either'
    );
}

// ------- other

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

// If "close" was specified in a search (user or ensemble),
// decide what this means, and tell the user
//
function handle_close($form_args, $req_user) {
    $close_country = null;
    $close_zip = 0;
    if ($form_args->close) {
        $close_country = $req_user->country;
        if ($req_user->country) {
            if ($req_user->country == 'United States') {
                if ($req_user->postal_code){
                    $close_zip = str_to_zip($req_user->postal_code);
                    if ($close_zip) {
                        echo "<p>Distance estimates are based on your postal code ($req_user->postal_code)<p>";
                    } else {
                        echo "<p>Your postal code ($req_user->postal_code) isn't in our database.  Showing results from the United States.<p>";
                    }
                } else {
                    echo "<p>You haven't <a href=home.php>specified your postal code.  Showing results from the United States.<p>";
                }
            } else {
                echo "<p>Showing only results from $req_user->country.<p>";
            }
        } else {
            echo "<p>You haven't <a href=home.php>specified your country</a>, so we don't know who's close to you.<p>";
        }
    }
    return [$close_country, $close_zip];
}

// handle the upload of an MP3 file
// $id is that of either a user or an ensemble
//
function handle_audio_signature_upload($profile, $profile2, $role, $id) {
    if ($profile->signature_filename) {
        if (post_str('signature_check', true)) {
            $profile2->signature_filename = $profile->signature_filename;
        } else {
            $profile2->signature_filename = '';
            unlink(sprintf('%s/%d.mp3', role_dir($role), $id));
        }
    } else {
        $sig_file = $_FILES['signature_add'];
        $sig_name = $sig_file['tmp_name'];
        $orig_name = $sig_file['name'];
        if ($orig_name) {
            if (is_uploaded_file($sig_name)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if (finfo_file($finfo, $sig_name) != 'audio/mpeg') {
                    error_page("$orig_name is not an MP3 file.");
                }
                $new_name = sprintf('%s/%d.mp3',
                    role_dir($role), $id
                );
                if (!move_uploaded_file($sig_name, $new_name)) {
                    error_page("Couldn't move uploaded file.");
                }
                $profile2->signature_filename = $orig_name;
            } else {
                error_page("Couldn't upload $orig_name; it may be too large.");
            }
        }
    }
    return $profile2;
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

function role_dir($role) {
    switch ($role) {
    case COMPOSER: return "composer";
    case PERFORMER: return "performer";
    case TECHNICIAN: return "technician";
    case ENSEMBLE: return "ensemble";
    default: die("bad role");
    }
}

function role_name($role) {
    switch ($role) {
    case COMPOSER: return "Composer";
    case PERFORMER: return "Performer";
    case TECHNICIAN: return "Technician";
    case ENSEMBLE: return "Ensemble";
    default: die("bad role");
    }
}

// $id can be user ID or ensemble ID
//
function profile_exists($id, $role) {
    $dir = role_dir($role);
    $fname = "$dir/$id.json";
    return file_exists($fname);
}

function read_profile($id, $role) {
    $dir = role_dir($role);
    $fname = "$dir/$id.json";
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
        if (!array_key_exists('ens_type', $p)) $p->ens_type = array();
        if (!array_key_exists('ens_type_custom', $p)) $p->ens_type_custom = array();
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
    case TECHNICIAN:
        if (!array_key_exists('tech_area', $p)) $p->tech_area = array();
        if (!array_key_exists('tech_area_custom', $p)) $p->tech_area_custom = array();
        if (!array_key_exists('program', $p)) $p->program = array();
        if (!array_key_exists('program_custom', $p)) $p->program_custom = array();
        break;
    case ENSEMBLE:
        if (!array_key_exists('description', $p)) $p->description = '';
        if (!array_key_exists('type', $p)) $p->type = '';
        if (!array_key_exists('style', $p)) $p->style = array();
        if (!array_key_exists('style_custom', $p)) $p->style_custom = array();
        if (!array_key_exists('inst', $p)) $p->inst = array();
        if (!array_key_exists('inst_custom', $p)) $p->inst_custom = array();
        if (!array_key_exists('level', $p)) $p->level = array();
        if (!array_key_exists('signature_filename', $p)) $p->signature_filename = '';
        if (!array_key_exists('link', $p)) $p->link = array();
        if (!array_key_exists('seeking_members', $p)) $p->seeking_members = 0;
        if (!array_key_exists('perf_reg', $p)) $p->perf_reg = 0;
        if (!array_key_exists('perf_paid', $p)) $p->perf_paid = 0;
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

function delete_mm_profile($id, $role) {
    $dir = role_dir($role);
    $fname = "$dir/$id.json";
    unlink($fname);
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


// Javascript for mouse-over audio
//
$audio_head_extra = <<<EOT
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

// show a list of items (instruments, styles etc.) as a string
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

function links_to_string($links, $sep=',') {
    $x = "";
    $first = true;
    foreach ($links as $link) {
        if ($first) {
            $first = false;
        } else {
            $x .= $sep;
        }
        $x .= sprintf(" <a href=%s>%s</a>", $link->url, $link->desc);
    }
    return $x;
}

// return string showing country and - if relevant - distance
//
function country_distance($user, $dist, $sep=' ') {
    if ($dist >= 0) {
        return sprintf("%s%s<small>(%d miles from you)</small>", $user->country, $sep, (int)$dist);
    } else {
        return $user->country;
    }
}

// button styles
//
define('BUTTON_BIG',
    ['btn', '#00bc8c', 'black', '<font size=+2><b>', '</b></font>']
);
define('BUTTON_NORMAL',
    ['btn', ' #375a7f', 'white', '<font size=+0>', '</font>']
);
define('BUTTON_DANGER',
    ['btn', ' #ff6060', 'white', '<font size=+0>', '</font>']
);
define('BUTTON_SMALL',
    ['btn-sm', ' #375a7f', 'white', '<font size=+0>', '</font>']
);

function mm_button_text($url, $label, $style=BUTTON_NORMAL) {
    return sprintf(
        '<a class="%s" style="background-color:%s; color:%s" href=%s>%s%s%s</a>',
        $style[0], $style[1], $style[2], $url, $style[3], $label, $style[4]
    );
}

function mm_show_button($url, $label, $style=BUTTON_NORMAL) {
    echo mm_button_text($url, $label, $style);
}

// ------------ utilities -------------

function mm_get_logged_in_user() {
    $user = get_logged_in_user();
    if ($user && !$user->email_validated) {
        Header("Location: reg.php?action=verify");
    }
    return $user;
}

function str_starts_with ($haystack, $needle) {
    return strpos( $haystack , $needle ) === 0;
}

function str_ends_with($haystack, $needle) {
    $length = strlen($needle);
    return $length > 0 ? substr($haystack, -$length) === $needle : true;
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

function user_distance($u1, $u2) {
    if ($u1->country != 'United States') return -1;
    if ($u2->country != 'United States') return -1;
    $z1 = str_to_zip($u1->postal_code);
    if (!$z1) return -1;
    $z2 = str_to_zip($u2->postal_code);
    if (!$z2) return -1;
    return zip_dist($z1, $z2);
}

function em_status_string($status) {
    switch ($status) {
    case EM_PENDING: return 'Membership request pending';
    case EM_APPROVED: return 'Member';
    case EM_DECLINED: return 'Membership request declined';
    case EM_REMOVED: return 'Membership removed';
    }
    return 'unknown';
}

function home_button() {
    mm_show_button("home.php", "Return to home page");
}

function join_button() {
    echo "<p>";
    mm_show_button("reg.php", "JOIN", BUTTON_BIG);
}

// The user has modified their given profile.
// Notify their friends.
//
function profile_change_notify($user, $role) {
    $friends = BoincFriend::enum("user_src = $user->id");
    $now = time();
    foreach ($friends as $friend) {
        $user2 = BoincUser::lookup_id($friend->user_dest);
        if (!$user2) continue;
        BoincNotify::replace(
            sprintf("userid=%d, create_time=%d, type=%d, opaque=%d, id2=%d, sent_by_email=0",
                $user2->id, $now, NOTIFY_PROFILE, $user->id, $role
            )
        );
        notify_if_immediate($user2);
    }
}

?>
