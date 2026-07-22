<?php

$id = rand(100000,999999);

mkdir("../rooms/$id");

file_put_contents("../rooms/$id/room.json", json_encode([
    "id"=>$id,
    "players"=>[],
    "roles_config"=>[],
    "started"=>false
], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

file_put_contents("../data/rooms.json","[]");

echo $id;