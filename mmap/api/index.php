<?php
require __DIR__ . '/bootstrap.php';
$db = mmap_db();
$in = json_input();
$action = $in['action'] ?? 'state';
$now = time();
if ($action === 'login') {
    $name = trim($in['name'] ?? 'مهمان'); $email = trim($in['email'] ?? '') ?: null;
    $stmt = $email ? $db->prepare('INSERT INTO users(name,email,created_at,last_seen) VALUES(?,?,?,?) ON CONFLICT(email) DO UPDATE SET name=excluded.name,last_seen=excluded.last_seen') : $db->prepare('INSERT INTO users(name,created_at,last_seen) VALUES(?,?,?)');
    $email ? $stmt->execute([$name,$email,$now,$now]) : $stmt->execute([$name,$now,$now]);
    $id = $email ? (int)$db->query('SELECT id FROM users WHERE email=' . $db->quote($email))->fetchColumn() : (int)$db->lastInsertId();
    setcookie('mmap_uid', (string)$id, time()+31536000, '/'); setcookie('mmap_installed','1',time()+31536000,'/'); respond(['ok'=>true,'id'=>$id,'name'=>$name]);
}
if ($action === 'location') { $id = current_user_id(); if (!$id) respond(['ok'=>false,'error'=>'ابتدا نام را ثبت کنید']); $db->prepare('UPDATE users SET lat=?,lng=?,last_seen=? WHERE id=?')->execute([(float)$in['lat'],(float)$in['lng'],$now,$id]); respond(['ok'=>true]); }
if ($action === 'event') { $id=current_user_id(); $db->prepare('INSERT INTO events(owner_id,title,note,lat,lng,allowed_names,group_only,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$id,trim($in['title']??'قرار جدید'),$in['note']??'',(float)$in['lat'],(float)$in['lng'],$in['allowed']??'',!empty($in['group_only'])?1:0,$now,$now]); respond(['ok'=>true]); }
if ($action === 'favorite') { $db->prepare('INSERT INTO favorites(user_id,title,lat,lng,created_at) VALUES(?,?,?,?,?)')->execute([current_user_id(),trim($in['title']??'نقطه محبوب'),(float)$in['lat'],(float)$in['lng'],$now]); respond(['ok'=>true]); }
if ($action === 'message') { $db->prepare('INSERT INTO messages(room,sender_id,sender_name,body,created_at) VALUES(?,?,?,?,?)')->execute([$in['room']??'global',current_user_id(),trim($in['name']??'مهمان'),trim($in['body']??''),$now]); respond(['ok'=>true]); }
if ($action === 'seen') { $name=trim($in['name']??'مهمان'); foreach ($db->query('SELECT id,seen_by FROM messages') as $m) { $seen=json_decode($m['seen_by'],true)?:[]; if(!in_array($name,$seen,true)){ $seen[]=$name; $db->prepare('UPDATE messages SET seen_by=? WHERE id=?')->execute([json_encode($seen,JSON_UNESCAPED_UNICODE),$m['id']]); } } respond(['ok'=>true]); }
if ($action === 'friend') { $db->prepare('INSERT INTO friends(requester,target,status,created_at) VALUES(?,?,"pending",?)')->execute([trim($in['name']??'مهمان'),trim($in['target']??''),$now]); respond(['ok'=>true]); }
if ($action === 'group') { $db->prepare('INSERT OR IGNORE INTO groups(name,members,created_at) VALUES(?,?,?)')->execute([trim($in['name']??'گروه'),json_encode([$in['owner']??'مهمان'],JSON_UNESCAPED_UNICODE),$now]); respond(['ok'=>true]); }
if ($action === 'block') { $db->prepare('INSERT OR IGNORE INTO blocks(blocker,blocked,created_at) VALUES(?,?,?)')->execute([trim($in['name']??'مهمان'),trim($in['target']??''),$now]); respond(['ok'=>true]); }
respond([
 'ok'=>true,
 'users'=>$db->query('SELECT id,name,lat,lng,last_seen FROM users WHERE lat IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC),
 'events'=>$db->query('SELECT * FROM events ORDER BY updated_at DESC')->fetchAll(PDO::FETCH_ASSOC),
 'messages'=>$db->query('SELECT * FROM messages ORDER BY id DESC LIMIT 80')->fetchAll(PDO::FETCH_ASSOC),
 'favorites'=>$db->query('SELECT * FROM favorites WHERE user_id='.(int)current_user_id().' ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC),
 'friends'=>$db->query('SELECT * FROM friends ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC),
 'groups'=>$db->query('SELECT * FROM groups ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC)
]);
