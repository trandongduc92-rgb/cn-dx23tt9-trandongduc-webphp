<?php
include "../config/database.php";

$id = $_POST['id'];
$progress = $_POST['progress'];

$status = 'pending';
if($progress == 100){
    $status = 'done';
} elseif($progress > 0){
    $status = 'in_progress';
}

$conn->query("UPDATE tasks SET progress=$progress, status='$status' WHERE id=$id");

header("Location: ../views/task_list.php");
