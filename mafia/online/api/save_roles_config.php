<?php

$id = $_GET["id"];

$data = json_decode(file_get_contents("php://input"),true);

$file = "../rooms/$id/room.json";

$room = json_decode(file_get_contents($file),true);

$room["roles_config"] = $data;

file_put_contents($file,json_encode($room,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo "saved";