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

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

page_head("Music Match");
text_start();

echo "
<p>
Music Match lets people involved in classical and modern music -
performers, composers, technicians -
find each other, communicate, and collaborate.
<p>
<h3>Performers:</h3>
<ul>
<li> Find composers who write music for your instrument,
in your style and level.
Check out their compositions, or get them to write new ones for you.
<li> Find local musicians to play and perform music with.

</ul>

<h3>Composers:</h3>
<ul>
<li> Find performers to play, perform, and record
your compositions.
<li> Get (and give) help with score editing and rendering software.
</ul>
<p>
<a href=intro.php>Learn more about Music Match.</a>
<br><br>
<center>
";

$user = get_logged_in_user(true);
if ($user) {
    update_visit_time($user);
    home_button();
} else {
    join_button();
}

echo "
</center>
<hr>
<p>
Music Match is a non-profit open-source project
based at the University of California, Berkeley.
<p>
The data collected by Music Match will not be
sold, distributed, or used for other purposes.
You can delete your account, in which case all
data about you will be removed.
<hr>
<font color=#eebb44>Music Match is being developed and tested.
The database is populated with artificial users and ensembles.
You're welcome to create an account, test things,
and <a href=contact.php>give feedback</a>,
but at some point we'll reset the database
and your account will disappear.
</font>
";

text_end();
$show_home_link = false;
page_tail();

?>
