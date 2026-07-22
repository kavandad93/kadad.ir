<?php

$id = $_GET["id"];

echo file_get_contents("../rooms/$id/room.json");