<?php

// read image $src, write middle square as NxN image to $dest
//
function extract_middle_square($src, $dst, $size) {
    [$w, $h, $type] = getimagesize($src);
    if ($w > $h) {
        $src_offx = ($w-$h)/2;
        $src_offy = 0;
        $src_size = $h;
    } else {
        $src_offx = 0;
        $src_offy = ($h-$w)/2;
        $src_size = $w;
    }

    switch ($type) {
    case 1:
        $src_img = imageCreateFromGif($src);
        break;
    case 2:
        $src_img = imageCreateFromJpeg($src);
        break;
    case 3:
        $src_img = imageCreateFromPng($src);
        break;
    }
    $dst_img = imageCreateTrueColor($size, $size);

    imagecopyresampled(
        $dst_img,
        $src_img,
        0,0,
        $src_offx, $src_offy,
        $size, $size,
        $src_size, $src_size
    );

    ImageJPEG($dst_img, $dst);
}

?>
