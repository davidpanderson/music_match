<?php

require_once("../inc/util.inc");
require_once("../inc/mm.inc");

function main() {
    page_head("Spread the word");
    text_start();
    echo '
        <p>
        The more musicians use Music Match,
        the more we can all get from it.
        Please help spread the word
        by telling all your musical friends about it.
        <p>
        Edit the message below as you see fit.
        Then click "Copy message", and paste the message
        into emails to your friends (or social media posts).
        <p>
        Thanks for helping!
        <p>
        <textarea id=foo rows=8 cols=80>
I\'ve been using a new web site for classical musicians called Music Match:
    https://music-match.org
It lets you find and connect with other musicians: for example, composers who write music for your instrument and style, or performers to play music you\'ve composed, or ensembles looking for new members.  It\'s like LinkedIn for musicians.

Check it out - I think you\'ll find it useful.</textarea>
        <p>
        <script>
            function copy() {
                var t = document.getElementById("foo");
                t.select();
                t.setSelectionRange(0,99999);
                navigator.clipboard.writeText(t.value);
                alert("Message copied to clipboard");
            }
        </script>
    ';
    echo sprintf('
        <a class="%s" style="background-color:%s; color:%s" href=# onclick="copy()">Copy message</a>',
        BUTTON_NORMAL[0],
        BUTTON_NORMAL[1],
        BUTTON_NORMAL[2]
    );
    text_end();
    page_tail();
}

main();
?>
