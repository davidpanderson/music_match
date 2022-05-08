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

// upload picture

require_once("../inc/util.inc");
require_once("../inc/mm_image.inc");

function upload_form($user) {
    page_head("Upload picture");
    echo "You can upload a picture of yourself.
        It will be shown with your name in
        search results and messages.
        <p>
    ";
    form_start("picture.php", "POST",
        'ENCTYPE="multipart/form-data"'
    );
    form_general(
        "Image file (JPEG, PNG or GIFF)",
        '<input name=picture type=file>'
    );
    form_submit("Upload", 'name=submit value=upload');
    form_end();
    page_tail();
}

function delete_form($user) {
    page_head("Your picture");
    form_start("picture.php", "POST");
    form_general('',
        sprintf("<img src=%s width=200>", picture_path($user))
    );
    form_submit("Delete picture", 'name=submit value=delete');
    form_end();
    page_tail();
}

function upload_action($user) {
    $pic_file = $_FILES['picture'];
    $pic_name = $pic_file['tmp_name'];
    $orig_name = $pic_file['name'];
    if (!$orig_name) {
        error_page("no file selected");
    }
    if (is_uploaded_file($pic_name)) {
        [$w, $y, $type] = getImageSize($pic_name);
        if ($type < 1 or $type > 3) {
            error_page("image must be JPEG, PNG, or GIFF");
        }
        $filename = sprintf('%d_%d.jpg', $user->id, time());
        $user->update("venue='$filename'");
        $user->venue = $filename;
        extract_middle_square($pic_name, picture_path($user), 512);
    } else {
        error_page("can't upload $orig_name; it may be too large.");
    }
    page_head("Picture uploaded");
    echo sprintf("<img src=%s width=200>", picture_path($user));
    page_tail();
}

function delete_action($user) {
    @unlink(picture_path($user));
    $user->update("venue=''");
    page_head("Picture deleted");
    echo "Your picture has been deleted.";
    page_tail();
}

$user = get_logged_in_user();

$submit = post_str('submit', true);
if ($submit == 'upload') {
    upload_action($user);
} else if ($submit == 'delete') {
    delete_action($user);
} else {
    if (has_picture($user)) {
        delete_form($user);
    } else {
        upload_form($user);
    }
}

?>
