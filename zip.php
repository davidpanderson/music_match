<?php

$lats = array();
$longs = array();

function test() {
    global $lats, $longs;
    $lines = file('zip.csv');
    foreach ($lines as $line) {
        $x = explode(',', $line);
        $zip = (int)$x[0];
        $lats[$zip]= (double)$x[1];
        $longs[$zip]= (double)$x[2];
    }
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
    return sph_dist_miles($lats[$z1], $longs[$z1], $lats[$z2], $longs[$z2]);
}

test();
echo zip_dist(94702, 94060);


?>
