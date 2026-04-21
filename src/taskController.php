<?php
session_start();
include __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['create'])){

    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    
    $user = is_array($_POST['assigned_to']) ? $_POST['assigned_to'][0] : $_POST['assigned_to'];
    
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    if(empty($title)){
        die("Tiêu đề không được để trống");
    }

    try {
        $sql = "INSERT INTO tasks (title, description, assigned_by, assigned_to, status, deadline)
                VALUES (:title, :desc, :assigned_by, :assigned_to, 'in progress', :deadline)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title'       => $title,
            ':desc'        => $desc,
            ':assigned_by' => $_SESSION['user'],
            ':assigned_to' => $user,
            ':deadline'    => $deadline
        ]);

        header("Location: ../views/task_list.php");
        exit();

    } catch(PDOException $e){
        die("Lỗi: " . $e->getMessage());
    }
}
?>
