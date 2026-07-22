<?php
$db=new PDO('mysql:host=localhost;dbname=mess;charset=utf8mb4','root','');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
session_start();
?>