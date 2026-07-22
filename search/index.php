<?php

$baseDir = realpath(__DIR__ . "/..");
$blacklist = json_decode(file_get_contents(__DIR__ . "/black_list.json"), true) ?? [];

// =====================
// INDEX CHECK
// =====================
function hasIndex($dir) {
    return file_exists($dir . "/index.php") || file_exists($dir . "/index.html");
}

// =====================
// SEARCH (TOP LEVEL ONLY)
// =====================
function searchTop($baseDir, $blacklist, $q) {

    $res = [];

    foreach (@scandir($baseDir) ?: [] as $item) {

        if ($item === "." || $item === "..") continue;
        if (in_array($item, $blacklist)) continue;

        $path = $baseDir . "/" . $item;

        if (!is_dir($path)) continue;
        if (!hasIndex($path)) continue;

        if ($q === "" || stripos($item, $q) !== false) {
            $res[] = [
                "name" => $item,
                "path" => str_replace($baseDir, "", $path)
            ];
        }
    }

    return $res;
}

// =====================
// API MODE (IMPORTANT)
// =====================
if (isset($_GET['api'])) {
    header("Content-Type: application/json");
    echo json_encode(searchTop($baseDir, $blacklist, $_GET['q'] ?? ""));
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$results = $q ? searchTop($baseDir, $blacklist, $q) : [];

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Site Search By Kavan</title>

<style>
body {
    margin:0;
    font-family:Arial;
    background: url('BG.png') no-repeat center center fixed;
    color:white;
}

/* header smooth */
.header {
    padding:60px 20px;
    transition:0.4s ease;
}

.header.active {
    padding:15px 20px;
}

/* title slide */
h2 {
    margin:0;
    transition:0.4s ease;
}

.header.active h2 {
    transform: translateX(-120px) translateY(-10px);
}

/* form slide */
form {
    margin-top:20px;
    transition:0.4s ease;
}

.header.active form {
    transform: translateY(-30px);
}

/* input */
input {
    width:300px;
    padding:12px;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    border: 3px solid rgba(255, 255, 255, 1);
    border-radius: 10px;

}

button {
    padding:12px;
    background: rgba(255, 255, 255, 0.32);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    border: 3px solid rgba(255, 255, 255, 1);
    border-radius: 10px;
    cursor:pointer;
}

/* bubble */
.bubble {
    width:420px;
    margin:0 auto;
    background: rgba(255, 255, 255, 0.32);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    border: 3px solid rgba(255, 255, 255, 1);
    border-radius: 10px;
    text-align:center;
    box-shadow: 10px 10px 20px #babfc6, -10px -10px 20px #ffffff;

}

.item {
    padding:10px;
    border-bottom:10px solid #333;
    background: rgba(255, 255, 255, 0.5);
    color: #ffffff;
    border-radius: 8px;
    border: none;
    

}

.item:last-child {
    border-bottom:none;
}
</style>

</head>

<body>

<div class="header <?php echo $q ? 'active' : ''; ?>">

    <h2>🔎 Folder Search</h2>

    <form id="form">
        <input id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="search folders..." />
        <button type="submit">Search</button>
    </form>

</div>

<div class="bubble" id="bubble">

<?php if (!$q): ?>
    <div class="item">Start typing...</div>

<?php elseif (!$results): ?>
    <div class="item">No results</div>

<?php else: ?>

    <?php foreach ($results as $r): ?>
        <a href="<?php echo $r["path"]; ?>" style="color:white;text-decoration:none;">
            <div class="item">📁 <?php echo htmlspecialchars($r["name"]); ?></div>
        </a>
    <?php endforeach; ?>

<?php endif; ?>

</div>

<script>

const input = document.getElementById("q");
const bubble = document.getElementById("bubble");
const form = document.getElementById("form");

// =====================
// LIVE SEARCH (FIXED)
// =====================
let t;

async function fetchSearch(q) {
    let r = await fetch("?api=1&q=" + encodeURIComponent(q));
    return await r.json();
}

input.addEventListener("input", () => {

    clearTimeout(t);

    let q = input.value.trim();

    if (!q) {
        bubble.innerHTML = "<div class='item'>Start typing...</div>";
        return;
    }

    t = setTimeout(async () => {

        let data = await fetchSearch(q);

        bubble.innerHTML = data.length
            ? data.map(i => `<a href='${i.path}' style='color:white;text-decoration:none'><div class='item'>📁 ${i.name}</div></a>`).join("")
            : "<div class='item'>No results</div>";

    }, 150);
});

// =====================
// ENTER = REAL SEARCH (?q)
// =====================
form.addEventListener("submit", (e) => {
    e.preventDefault();
    let q = input.value.trim();
    if (!q) return;
    window.location = "?q=" + encodeURIComponent(q);
});

</script>

</body>
</html>