# cn-dx23tt9-trandongduc-webphp
Thiết kế website quản lý giao việc cho nhân viên công ty Nam Long

# Chức năng
- Website cho phép quản lý tạo,phân công và cập nhật tiến độ công việc cho từng nhân viên.
- Hệ thống giúp theo dõi trạng thái công việc theo thời gian thực.
- Nhân viên có thể nhận việc, báo cáo tiến độ và phản hồi trực tiếp trên hệ thống.
- Website hỗ trợ phân quyền người dùng như quản lý và nhân viên để đảm bảo tính bảo mật.
- Hệ thống còn cung cấp chức năng thống kê và báo cáo công việc
  
# Công cụ
- PHP
- MySQL
  
# Cách khởi chạy
- Cài XAMPP
- Import database
- Chạy localhost

## mình sẽ code php bằng visual studio code (vs code)
1. cài visual studio code
2. tao project "task quanlynhanvien_cty_Namlong"
3. tạo file index.php

## cài môi trường chạy php XAMPP
1. mở XAMPP chạy apache,sql
2. coppy project vào 
C:\xampp\htdocs\
cây reponsitory
task quanlynhanvien_cty_Namlong/
├── index.php
├── config/
├── controllers/
├── models/
├── views/
├── assets/
## cài đặt visual studio tiến hành code
1. code phần login cho trang web
   [ code]
   [```php
<?php session_start(); ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nam Long - Login</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icon -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Poppins', sans-serif; }

        body {
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #ffe4ec, #f8cdda);
            overflow: hidden;
        }

        .container-fluid { height: 100vh; }

        /* LEFT */
        .left {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            margin: auto;
        }

        .carousel-item {
            text-align: center;
            transition: transform 0.8s ease-in-out;
        }

        /* RIGHT */
        .right {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            width: 370px;
            padding: 35px;
            border-radius: 20px;
            background: rgba(255,255,255,0.95);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from {opacity:0; transform: translateY(30px);}
            to {opacity:1; transform: translateY(0);}
        }

        .login-box h2 {
            font-weight: 600;
            color: #d63384;
        }

        .input-group-text {
            background: #fff;
            border: none;
            color: #d63384;
        }

        .form-control {
            border-radius: 10px;
        }

        .btn-login {
            background: linear-gradient(to right, #ff9a9e, #fad0c4);
            border: none;
            border-radius: 10px;
            color: white;
        }

        .btn-login:hover {
            transform: scale(1.05);
        }

        .extra { font-size: 14px; }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row h-100">

        <!-- LEFT: SLIDER -->
        <div class="col-md-6 left d-none d-md-flex">

            <div class="col-md-6 left d-none d-md-flex flex-column">

    <div class="d-flex justify-content-center gap-4">

        <!-- CEO -->
        <div class="text-center">
            <img src="../public/images/CEO.jpg" class="avatar">
            <h6 class="mt-2">Trần Đông Đức</h6>
            <p class="text-muted text-center mb-0" style="font-size:13px;">
    <strong>CEO</strong><br>
    Giám Đốc Đều Hành
</p>
        </div>

        <!-- CFO -->
        <div class="text-center">
            <img src="../public/images/CFO.jpg" class="avatar">
            <h6 class="mt-2">Nguyễn Quang Huy</h6>
            <p class="text-muted text-center mb-0" style="font-size:13px;">
    <strong>CFO</strong><br>
    Giám Đốc Tài Chính
</p>
        </div>

        <!-- QUALITY -->
        <div class="text-center">
            <img src="../public/images/QC.jpg" class="avatar">
            <h6 class="mt-2">Lê Hoàng Anh</h6>
            <p class="text-muted text-center mb-0" style="font-size:13px;">
    <strong>QMR</strong><br>
    Giám Đốc Chất Lượng
</p>
        </div>

    </div>

</div>

        </div>

        <!-- RIGHT LOGIN -->
        <div class="col-md-6 right">

            <div class="login-box">

                <h2 class="text-center mb-3">NAM LONG</h2>
                <p class="text-center mb-4">Hệ thống quản lý công việc</p>

                <form method="POST" action="../controllers/AuthController.php">

                    <!-- EMAIL -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>

                    <!-- PASSWORD -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                        <span class="input-group-text" onclick="togglePass()" style="cursor:pointer;">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>

                    <!-- OPTIONS -->
                    <div class="d-flex justify-content-between extra mb-3">
                        <label><input type="checkbox"> Ghi nhớ</label>
                        <a href="#">Quên mật khẩu?</a>
                    </div>

                    <!-- BUTTON -->
                    <button name="login" class="btn btn-login w-100">
                        Đăng nhập
                    </button>

                </form>

                <p class="text-center mt-4 extra">
                    © 2026 Nam Long Company
                </p>

            </div>

        </div>

    </div>
</div>

<script>
function togglePass(){
    let x = document.getElementById("password");
    x.type = x.type === "password" ? "text" : "password";
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
```
]













  

# 👨‍🎓Sinh viên
- Tên: [TRẦN ĐÔNG ĐỨC]

# GitHub
https://github.com/trandongduc92-rgb/cn-dx23tt9-trandongduc-webphp.git
