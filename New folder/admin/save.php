<?php
$data=json_decode(file_get_contents("../recipes.json"),true);

$name=$_POST["name"];
$type=$_POST["type"];
$ing=json_decode($_POST["ingredients"],true);
$steps=json_decode($_POST["steps"],true);

$data[$type][$name]=["ingredients"=>$ing,"steps"=>$steps];

file_put_contents("../recipes.json",json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

echo "OK";
?>
