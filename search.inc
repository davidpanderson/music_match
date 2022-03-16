<?php

function record_search($user, $role, $args, $profiles) {
    $s = new stdClass;
    $s->user_id = $user->id;
    $s->role = $role;
    $s->args = $args;
    $results = [];
    foreach ($profiles as $id=>$p) {
        $results[] = $id;
    }
    $s->results = $results;
    echo json_encode($s, JSON_PRETTY_PRINT);
}

?>
