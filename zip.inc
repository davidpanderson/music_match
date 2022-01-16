<?php

$lats = array();
$longs = array();

function read_data() {
    global $lats, $longs;
    $lines = file('zip.csv');
    foreach ($lines as $line) {
        $x = explode(',', $line);
        $zip = (int)$x[0];
        $lats[$zip]= (double)$x[1];
        $longs[$zip]= (double)$x[2];
    }
}

// return zip or zero
//
function str_to_zip($postal_code) {
    global $lats, $longs;
    if (!$lats) read_data();
    $zip = (int)$postal_code;
    if (array_key_exists($zip, $lats)) {
        return $zip;
    }
    return 0;
}

function sph_dist_miles($lat1, $long1, $lat2, $long2) {
    $r = M_PI/180;
    $lat1 *= $r;
    $lat2 *= $r;
    $long1 *= $r;
    $long2 *= $r;
    $x = ($long2-$long1) * cos(($lat2+$lat1)/2);
    $y = ($lat2-$lat1);
    return sqrt($x*$x + $y*$y) * 3958.8;      // earth radius in miles
}

function zip_dist($z1, $z2) {
    global $lats, $longs;
    if (!$lats) read_data();
    return sph_dist_miles($lats[$z1], $longs[$z1], $lats[$z2], $longs[$z2]);
}

// e.g. $z= rnd_zip(94000, 94999);
//
function rnd_zip($min, $max) {
    global $lats, $longs;
    if (!$lats) read_data();
    while (1){
        $zip = random_int($min, $max);
        if (array_key_exists($zip, $lats)) {
            return $zip;
        }
    }
}

?>
