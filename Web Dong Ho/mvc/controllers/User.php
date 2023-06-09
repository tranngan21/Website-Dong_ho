<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';


class user extends ControllerBase
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $user = $this->model("userModel");
            $result = $user->checkLogin($email, $password);
            if ($result) {
                // Get user
                $u = $result->fetch_assoc();
                // Set session
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['user_name'] = $u['fullName'];
                $_SESSION['role'] = $u['RoleName'];
                // cart

                $cart = $this->model("cartModel");
                $listCart = ($cart->getByUserId($_SESSION['user_id']));
                
                if (count($listCart) > 0) {
                    $_SESSION['cart'] = $listCart;
                }

                if ($u['RoleName'] == "Admin") {
                    $this->redirect("admin");
                }else {
                    $this->redirect("home");
                }
            } else {
                $this->view("client/login", [
                    "headTitle" => "Đăng nhập", "message" => "Tài khoản hoặc mật khẩu không đúng!"
                ]);
            }
        } else {
            $this->view("client/login", [
                "headTitle" => "Đăng nhập"
            ]);
        }
    }

    public function logout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['cart']);
        $this->redirect("user", "login");
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fullName = $_POST['fullName'];
            $email = $_POST['email'];
            $dob = $_POST['dob'];
            $address = $_POST['address'];
            $password = $_POST['password'];
            $phone = $_POST['phone'];
            $provinceId = $_POST['ls_province'];
            $districtId = $_POST['ls_district'];
            $wardId = $_POST['ls_ward'];

            $user = $this->model("userModel");
            $checkEmail = $user->checkEmail($email);
            if (!$checkEmail) {
                $checkPhone = $user->checkPhone($phone);
                if (!$checkPhone) {
                    $this->view("client/register", [
                        "headTitle" => "Đăng ký",
                        "messageEmail" => "Email đã tồn tại",
                        "messagePhone" => "Số điện thoại đã tồn tại",
                    ]);
                } else {
                    $this->view("client/register", [
                        "headTitle" => "Đăng ký",
                        "messageEmail" => "Email đã tồn tại",
                    ]);
                }
                return;
            } else {
                $checkPhone = $user->checkPhone($phone);
                if (!$checkPhone) {
                    $this->view("client/register", [
                        "headTitle" => "Đăng ký",
                        "messagePhone" => "Số điện thoại đã tồn tại",
                    ]);
                    return;
                }
            }
            $result = $user->insert($fullName, $email, $dob, $address, $password, $provinceId, $districtId, $wardId);
            if ($result) {
                $this->redirect("user", "confirm", ["email" => $email]);
            } else {
                $this->view("client/register", [
                    "headTitle" => "Đăng ký",
                    "cssClass" => "error",
                    "message" => "Đăng ký thất bại",
                ]);
            }
        } else {
            $this->view("client/register", [
                "headTitle" => "Đăng ký",
            ]);
        }
    }
    //gửi mã về mail
    function Send_Mail($to, $subject, $body) {
        require 'path/to/class.phpmailer.php';
        $from = "mail@gmail.com";
        $mail = new PHPMailer();
        $mail->IsSMTP();                // use SMTP
        $mail->IsHTML(true);
        $mail->SMTPAuth = true;         // enable SMTP authentication
        $mail->Host = "smtp.gmail.com"; // SMTP host
        $mail->Port = 587;             // set the SMTP port
        $mail->Username = "mail@gmail.com";  // SMTP username
        $mail->Password = "pass";  // SMTP password
        $mail->SMTPSecure = 'tls';      // enable TLS encryption, `ssl` also accepted
        $mail->SetFrom($from, 'From Name');
        $mail->AddReplyTo($from, 'From Name');
        $mail->Subject = $subject;
        $mail->MsgHTML($body);
        $address = $to;
        $mail->AddAddress($address, $to);
        if ($mail->Send()) {
            return true;
        } else {
            return false;
        }
    }
    public function confirm($email)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $captcha = $_POST['captcha'];

            $user = $this->model("userModel");
            $result = $user->confirm($email, $captcha);
            if ($result) {
                // Xác minh thành công
                $_SESSION["message"] = "Đăng ký thành công! Đăng nhập ngay";
                header("Location: ../login");
                exit();
            } else {
                // Xác minh không thành công
                $this->view("client/confirm", [
                    "headTitle" => "Xác minh tài khoản",
                    "cssClass" => "error",
                    "email" => $email,
                    "message" => "Mã xác minh không đúng",
                ]);
            }
        } else {
            $this->view("client/confirm", [
                "headTitle" => "Xác minh tài khoản",
                "email" => $email
            ]);
        }
    }


    public function info($message = "")
    {
        // Khởi tạo model
        $user = $this->model('userModel');
        $result = $user->getById($_SESSION['user_id']);
        $u = $result->fetch_assoc();
        $this->view('client/info', [
            "headTitle" => "Thông tin tài khoản",
            "user" => $u,
            "message" => $message
        ]);
    }

    public function edit()
    {
        // Khởi tạo model
        $user = $this->model('userModel');
        $result = $user->getById($_SESSION['user_id']);
        $u = $result->fetch_assoc();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $checkPhone = $user->checkPhoneUpdate($_POST['phone']);
            if (!$checkPhone) {
                $this->view("client/edit", [
                    "headTitle" => "Chỉnh sửa thông tin tài khoản",
                    "messagePhone" => "Số điện thoại đã tồn tại",
                    "user" => $u
                ]);
            } else {
                $r = $user->update($_POST);
                if ($r) {
                    $_SESSION['user_name'] = $_POST['fullName'];
                    $this->redirect("user", "info", [
                        "message" => "Cập nhật thành công!"
                    ]);
                } else {
                    $this->redirect("user", "info", [
                        "message" => "Lỗi!"
                    ]);
                }
            }
        } else {
            $this->view('client/edit', [
                "headTitle" => "Chỉnh sửa thông tin tài khoản",
                "user" => $u
            ]);
        }
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Khởi tạo model
            $user = $this->model('userModel');
            $result = $user->checkCurrentPassword($_SESSION['user_id'], $_POST['password']);
            if ($result) {
                $r = $user->updatePassword($_SESSION['user_id'], $_POST['newPassword']);
                if ($r) {
                    $this->redirect("user", "info", [
                        "message" => "Đổi mật khẩu thành công!"
                    ]);
                } else {
                    $this->redirect("user", "info", [
                        "message" => "Lỗi!"
                    ]);
                }
            } else {
                $this->view('client/resetPassword', [
                    "headTitle" => "Đổi mật khẩu",
                    "messagePassword" => "Mật khẩu hiện tại không đúng!"
                ]);
            }
        } else {
            $this->view('client/resetPassword', [
                "headTitle" => "Đổi mật khẩu"
            ]);
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Khởi tạo model
            $user = $this->model('userModel');
            $result = $user->checkCurrentPassword($_SESSION['user_id'], $_POST['password']);
            if ($result) {
                $r = $user->delete($_SESSION['user_id']);
                if ($r) {
                    $this->redirect("user", "logout");
                } else {
                    $this->view('client/delete', [
                        "headTitle" => "Xóa tài khoản",
                        "messagePassword" => "Lỗi!"
                    ]);
                }
            } else {
                $this->view('client/delete', [
                    "headTitle" => "Xóa tài khoản",
                    "messagePassword" => "Mật khẩu hiện tại không đúng!"
                ]);
            }
        } else {
            $this->view('client/delete', [
                "headTitle" => "Đổi mật khẩu"
            ]);
        }
    }
}
