<?php include $_SERVER['DOCUMENT_ROOT'] . "/head.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Coffee Suggester</title>

<style>
@font-face {
  font-family: yekan;
  src: url("B-YEKAN.ttf");
}

body {
  font-family: yekan;
  direction: rtl;
  background: url("BG.png") no-repeat center center fixed;
  background-size: cover;
  color: white;
  text-align: center;
}

.box { background: rgba(0,0,0,0.7); padding: 15px; margin: 10px; border-radius: 10px; }

select, input[type=range] { width: 80%; }
button { padding: 10px; background: #00ff88; border: 0; cursor: pointer; }
</style>
</head>

<body>

<h2>☕ انتخاب قهوه</h2>

<div class="box">
<label>نوع</label><br>
<select id="category">
<option value="hot">گرم</option>
<option value="ice">سرد</option>
</select>
<br><br>

<label>مدل قهوه</label><br>
<select id="model"></select>
</div>

<div class="box">
<label>حجم لیوان (ml)</label>
<input type="range" id="cupSize" min="100" max="1000" value="250" oninput="cupValue.innerText=this.value">
<div>انتخاب: <span id="cupValue">250</span></div>
</div>

<div class="box">
<h3>مواد</h3>
<label><input type="checkbox" value="espresso"> اسپرسو</label>
<label><input type="checkbox" value="milk"> شیر</label>
<label><input type="checkbox" value="water"> آب</label>
<label><input type="checkbox" value="sugar"> شکر</label>
<label><input type="checkbox" value="ice"> یخ</label>

<br><br>
<button onclick="send()">ارسال</button>
</div>

<div class="box" id="result"></div>

<script>
const models = {
 hot: ["latte","dark_coffee","cappuccino","espresso","americano","mocha"],
 ice: ["latte","dark_coffee","cappuccino","iced_americano","frappe"]
};

let category = "hot";

function loadModels(){
 let m = document.getElementById("model");
 m.innerHTML="";
 models[category].forEach(x=>{
  let o=document.createElement("option");
  o.value=x; o.text=x;
  m.appendChild(o);
 });
}

document.getElementById("category").onchange=function(){
 category=this.value;
 loadModels();
};

loadModels();

function send(){
 let ing=[];
 document.querySelectorAll("input[type=checkbox]:checked").forEach(i=>ing.push(i.value));

 fetch("api.php",{
  method:"POST",
  headers:{"Content-Type":"application/json"},
  body:JSON.stringify({
   category,
   model:document.getElementById("model").value,
   ingredients:ing,
   cupSize:document.getElementById("cupSize").value
  })
}).then(r=>r.json()).then(d=>{
 document.getElementById("result").innerText=JSON.stringify(d,null,2);
});
}
</script>

</body>
</html>
