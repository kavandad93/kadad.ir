<?php
header("Content-Type: application/json; charset=utf-8");

$data = json_decode(file_get_contents("php://input"), true);

$category = $data["category"] ?? "hot";
$model = $data["model"] ?? "";
$ingredients = $data["ingredients"] ?? [];
$cupSize = intval($data["cupSize"] ?? 250);

$recipes = json_decode(file_get_contents("recipes.json"), true);

$base = 250;
$multi = $cupSize / $base;

if(!isset($recipes[$category][$model])){
 echo json_encode(["error"=>"مدل انتخاب نشده"]);
 exit;
}

$r = $recipes[$category][$model];

$out = [];

foreach($r["ingredients"] as $i=>$v){
 if(!in_array($i,$ingredients)){
  echo json_encode(["error"=>"مواد کافی نیست"]);
  exit;
 }
 $out[$i]=round($v*$multi,2);
}

echo json_encode([
 "category"=>$category,
 "model"=>$model,
 "ingredients"=>$out,
 "steps"=>$r["steps"]
], JSON_UNESCAPED_UNICODE);
?>
