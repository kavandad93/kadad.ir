<?php
session_start();
$usersFile = '../data/users.json';
$users = json_decode(file_get_contents($usersFile), true);

$user_id = $_POST['user_id'] ?? $_SESSION['user_id'] ?? '';

if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0 && isset($users[$user_id])) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if(in_array($ext, $allowed)) {
        $filename = $user_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/profiles/' . $filename);
        $users[$user_id]['profile_pic'] = $filename;
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>