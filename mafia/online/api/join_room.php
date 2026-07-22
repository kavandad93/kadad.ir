<?php

$id = $_GET["id"];
$name = $_GET["name"];

$file = "../rooms/$id/room.json";

$room = json_decode(file_get_contents($file),true);

foreach($room["players"] as $p)
{
    if($p["name"]==$name)
        die("exists");
}

if(count($room["players"])>=15)
    die("full");

$room["players"][] = ["name"=>$name];

file_put_contents($file,json_encode($room,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo "ok";