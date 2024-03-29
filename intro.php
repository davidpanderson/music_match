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

page_head("About Music Match");
text_start();
echo <<<EOT
<p>
Music Match lets people involved in classical and modern music -
performers, composers, technicians -
discover each other, communicate, and collaborate.
<ul>
<li>
If you're a performer, you can find composers who write music
for your instrument, in your style and level.
Try their compositions, or ask them to write new pieces for you.
Or find local musicians to play and perform music with.

<li>
If you're a composer, Music Match can help you
find performers to play, perform, and record your compositions.
Or find other composers to
get (and give) help with score editing and rendering software.
</ul>
<p>
Music Match is designed to serve musicians of all levels,
both amateur and professional.

<p>
Music Match doesn't store scores or sound files;
sites like IMSLP, SoundCloud, and YouTube do that just fine.
Rather, Music Match is like LinkedIn or Match.com for classical music.
Its goal is to catalyze the composition and performance of new music
by connecting people.

<p>
<h2>Accounts</h2>
<p>
To use Music Match, you create an "account"
with a user name, email address, and password.
You can optionally provide your country and postal code
(e.g. ZIP code) -
this lets you search for musicians located near you.

<h2>Profiles</h2>
<p>
You can create "profiles" describing yourself
as a composer, as a performer, and/or as a technician.
For example, your composer profile includes
<ul>
<li> What styles of music you write.
<li> What instruments you write for.
<li> The difficulty levels of your compositions.
<li> Links to examples of your compositions or recordings on
other web sites, like IMSLP, SoundCloud, or YouTube.
<li> An "audio signature" -
a short MP3 file that gives an idea of what your compositions sound like.
This helps people browse search results.
</ul>
<p>
Performer profiles are similar.
Technician profiles say what areas and software you know about,

<p>
You don't have to create profiles.
If you don't,
you can still use Music Match to search for other musicians -
but no one will find you in their searches.

<h2>Ensembles</h2>
<p>
If you belong to an performance ensemble
(orchestra, choir, chamber group, etc.)
you can tell Music Match about the ensemble.
This has two purposes:
<ul>
<li> If you mark your ensemble as "looking for new members",
local performers can discover it and ask to join.

<li> Composers who write for that type of ensemble
can discover your ensemble,
communicate with you, and possibly compose music for your group.
</ul>

<p>
Each Music Match ensemble has an associated "founder".
If members of the (real-life) ensemble have Music Match accounts,
they can be linked to the ensemble on Music Match.

<h2>Search</h2>
<p>
Music Match lets you search for people
(performers, composers, technicians),
or ensembles.
You can specify the attributes - instruments, styles, difficulty levels -
that you're looking for.
You can limit your search to nearby people.
<p>
The result of a search is a list of people.
For each person, you see a summary of their profiles
(composer, performer, or technician).
If you asked for nearby people, you see how far away they are.
If they included an "audio signature" in their profile,
you can play it by mousing over their name.
This lets you browse search results quickly.

<h2>Communicate</h2>
<p>
When you find someone who interests you -
say a composer whose works you might want to perform -
you can browse their links.
Then you can communicate with them using "private message" -
perhaps to ask them a question about one of their works,
or to commission a new piece.
<p>
Music Match also has a message-board system,
with various top-level topics, for public discussions.
You can create "threads" and post in existing threads.
You can "subscribe" to a thread,
in which case you'll be notified of new posts in that thread.

<h2>Friends</h2>
<p>
If you connect with someone interesting on Music Match, you can become
"friends" with them - one of you makes a friend request, the other accepts it.
You'll be notified of your friends' activities.
When you view someone's profile, you see your friends too;
this is a good way to discover people.

<h2>Notifications</h2>
<p>
Music Match notifies you about various things that happen while you're away:
<ul>
<li> A search you previously made has new results.
<li> You received a private message.
<li> There's a new post in a thread you subscribed to.
<li> You received a friend request.
<li> Your friend request was accepted.
<li> A friend of yours posted a message or modified their profile.
<li> Your request to join an ensemble was accepted or declined.
<li> There was a request to join an ensemble that you founded.
</ul>

Recent notifications are shown on your Music Match "home page".
In addition, Music Match will send you emails summarizing
recent notifications.
You can have these delivered immediately,
or as daily or weekly digests,
or you can unsubscribe.
<hr>
<p>
Music Match is a non-profit project
based at the University of California, Berkeley,
led by <a href=https://boinc.berkeley.edu/anderson/>Dr. David P. Anderson</a>.
The source code is
<a href=https://github.com/davidpanderson/music_match/>on Github</a>.
<p>
The data collected by Music Match will not be
sold, distributed, or used for other purposes.
You can delete your account, in which case all
data about you will be removed.
<hr>
A <a href=https://docs.google.com/document/d/1WNvM1ALKSd74GRmRE9cOXQsWlRvvWL7c/edit?usp=sharing&ouid=104033427951329566684&rtpof=true&sd=true>design document</a>.
<p>
An
<a href=https://docs.google.com/document/d/1VePJZlnIn6rK91ymJ0Xo8uFGD3ZCRj66/edit?usp=sharing&ouid=104033427951329566684&rtpof=true&sd=true>earlier design document</a>.
EOT;
text_end();

$user = get_logged_in_user(true);
if (!$user) {
    join_button();
}

page_tail();

?>
