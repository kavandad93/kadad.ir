<?php

$id = $_GET["id"];

$file = "../rooms/$id/room.json";

$room = json_decode(file_get_contents($file),true);

$count = count($room["players"]);

if($count<5 || $count>15)
    die("invalid players");

$roles = [];

foreach($room["roles_config"] as $role=>$num)
{
    for($i=0;$i<$num;$i++)
        $roles[]=$role;
}

while(count($roles)<$count)
{
    $roles[]="civilian";
}

shuffle($roles);

$result = [];

foreach($room["players"] as $i=>$p)
{
    $result[$p["name"]] = $roles[$i];
}

file_put_contents("../rooms/$id/roles.json",
json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

$room["started"]=true;

file_put_contents($file,json_encode($room,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

header("Location: ../game.php?id=$id");