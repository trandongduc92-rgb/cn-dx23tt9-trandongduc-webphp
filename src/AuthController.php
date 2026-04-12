<?php
session_start();
include __DIR__ . '/../config/db.php';

if(isset($_POST['login'])){

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($password, $user['password'])){

            $_SESSION['user'] = $user;

            // redirect theo role
            header("Location: ../views/dashboard.php");
            exit();

        } else {
            echo "<script>
                alert('Sai email hoặc mật khẩu!');
                window.location.href='../login.php';
            </script>";
        }

    } catch (PDOException $e) {
        die("Lỗi DB: " . $e->getMessage());
    }
}
?>
