<?php
session_start();
include 'admin/connections.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM account WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['Password']) { // Use password_verify if hashed
            $accountType = strtolower($row['AccountType'] ?? '');
            $_SESSION['email'] = $email;
            $_SESSION['user_id'] = $row['AccountID'] ?? $row['id'] ?? null;
            $_SESSION['first_name'] = $row['FirstName'] ?? '';
            $_SESSION['last_name'] = $row['LastName'] ?? '';
            $_SESSION['account_type'] = $accountType;
            if ($accountType === 'admin') {
                header("Location: admin/index.php");
                exit();
            } elseif ($accountType === 'staff') {
                header("Location: staff/staff_dashboard.php");
                exit();
            } else {
                $error = "Account type not recognized.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SIGN IN | Villa Valore Hotel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-green: #008000;
            --primary-green-dark: #006400;
            --text-dark: #222;
            --text-light: #fff;
            --border-radius: 18px;
            --input-bg: #f7f7f7;
            --input-border: #b2b2b2;
            --input-focus: #006400;
            --overlay-bg: rgba(0,0,0,0.38);
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow: hidden;
        }
        .main-wrapper {
            display: flex;
            align-items: stretch;
            justify-content: center;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 32px rgba(44, 204, 64, 0.10), 0 1.5px 8px rgba(44, 204, 64, 0.06);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            margin: 48px 0;
            border: 1.5px solid #e0e0e0;
            animation: fadeInMain 1.2s cubic-bezier(.77,0,.18,1) both;
            min-height: 650px;
        }
        @keyframes fadeInMain {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: none;}
        }
        .left-portrait {
            position: relative;
            min-width: 420px;
            max-width: 520px;
            width: 45vw;
            background: url('guest/images/samplebedroom.png') center center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: flex-start;
            border-right: 1.5px solid #e0e0e0;
            overflow: hidden;
            transition: box-shadow 0.4s;
            box-shadow: 0 0 0 0 rgba(0,0,0,0);
            animation: slideInLeft 1.1s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-60px);}
            to { opacity: 1; transform: none;}
        }
        .left-portrait::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--overlay-bg) 60%, rgba(0,0,0,0.18) 100%);
            z-index: 1;
            transition: background 0.4s;
        }
        .left-content {
            position: relative;
            z-index: 2;
            width: 100%;
            padding: 60px 48px 48px 48px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            animation: fadeInLeftContent 1.5s 0.2s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes fadeInLeftContent {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: none;}
        }
        .logo {
            width: 110px;
            height: 110px;
            border-radius: 14px;
            object-fit: contain;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(44,204,64,0.08);
            animation: popLogo 1.2s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes popLogo {
            0% { transform: scale(0.7); opacity: 0;}
            80% { transform: scale(1.08);}
            100% { transform: scale(1); opacity: 1;}
        }
        .hotel-name {
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 0;
            margin-top: 0;
            text-shadow: 0 2px 12px rgba(0,0,0,0.13);
        }
        .tagline {
            color: #fff;
            font-size: 1.35rem;
            font-weight: 500;
            margin-top: 22px;
            text-align: left;
            letter-spacing: 0.5px;
            margin-bottom: 0;
            text-shadow: 0 2px 12px rgba(0,0,0,0.13);
            animation: fadeInTagline 1.6s 0.4s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes fadeInTagline {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: none;}
        }
        .login-container {
            background: #fff;
            padding: 70px 64px 64px 64px;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            width: 100%;
            max-width: 540px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            animation: slideInRight 1.2s cubic-bezier(.77,0,.18,1) both;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(60px);}
            to { opacity: 1; transform: none;}
        }
        .login-container h1 {
            display: none;
        }
        .login-container h2 {
            color: #555;
            font-size: 1.2rem;
            margin-bottom: 34px;
            font-weight: 400;
        }
        .form-group {
            margin-bottom: 28px;
            text-align: left;
            position: relative;
        }
        .form-group label {
            display: block;
            color: var(--text-dark);
            font-size: 1.08rem;
            margin-bottom: 9px;
            font-weight: 500;
        }
        .form-group .input-icon {
            position: absolute;
            left: 16px;
            top: 40px;
            width: 26px;
            height: 26px;
            fill: #b2b2b2;
            pointer-events: none;
            transition: fill 0.2s;
        }
        .form-group input[type="email"]:focus ~ .input-icon,
        .form-group input[type="password"]:focus ~ .input-icon {
            fill: var(--primary-green-dark);
        }
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 15px 14px 15px 56px;
            border: 1.5px solid var(--input-border);
            border-radius: var(--border-radius);
            font-size: 1.13rem;
            background: var(--input-bg);
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            color: var(--text-dark);
        }
        .form-group input:focus {
            border-color: var(--input-focus);
            outline: none;
            box-shadow: 0 0 0 2px #b2f2bb;
        }
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, var(--primary-green) 60%, #43a047 100%);
            color: var(--text-light);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.18rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, transform 0.13s;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(44, 204, 64, 0.08);
        }
        .login-btn:hover {
            background: linear-gradient(90deg, var(--primary-green-dark) 60%, #388e3c 100%);
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 4px 18px rgba(44,204,64,0.13);
        }
        .links {
            margin-top: 22px;
            font-size: 1.08rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }
        .links a {
            color: var(--primary-green-dark);
            text-decoration: underline;
            font-weight: 500;
            padding: 0;
            border-radius: 0;
            background: none;
            border: none;
            width: auto;
            display: inline;
            transition: color 0.18s;
        }
        .links a:hover {
            color: var(--primary-green);
            background: none;
        }
        .error {
            margin-top: 18px;
            color: #d32f2f;
            background: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 7px;
            padding: 12px 0;
            font-size: 1.08rem;
            animation: shakeError 0.4s;
        }
        @keyframes shakeError {
            0% { transform: translateX(0);}
            20% { transform: translateX(-8px);}
            40% { transform: translateX(8px);}
            60% { transform: translateX(-6px);}
            80% { transform: translateX(6px);}
            100% { transform: translateX(0);}
        }
        @media (max-width: 1300px) {
            .main-wrapper {
                max-width: 98vw;
            }
            .left-portrait {
                min-width: unset;
                max-width: unset;
                width: 100%;
                height: 260px;
                border-right: none;
                border-bottom: 1.5px solid #e0e0e0;
            }
            .left-content {
                padding: 32px 18px 18px 18px;
            }
            .logo {
                width: 80px;
                height: 80px;
            }
            .hotel-name {
                font-size: 1.6rem;
            }
            .tagline {
                font-size: 1.1rem;
            }
            .login-container {
                border-radius: 0 0 var(--border-radius) var(--border-radius);
                padding: 38px 18px 28px 18px;
                max-width: 98vw;
            }
        }
        @media (max-width: 900px) {
            .main-wrapper {
                flex-direction: column;
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
                min-height: unset;
            }
            .left-portrait {
                height: 160px;
            }
            .left-content {
                padding: 14px 8px 8px 8px;
            }
            .logo {
                width: 48px;
                height: 48px;
            }
            .hotel-name {
                font-size: 1.1rem;
            }
            .tagline {
                font-size: 0.98rem;
            }
            .login-container {
                padding: 18px 6px 12px 6px;
                max-width: 100vw;
            }
        }
        @media (max-width: 600px) {
            .main-wrapper {
                flex-direction: column;
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
            }
            .left-portrait {
                height: 90px;
            }
            .left-content {
                padding: 6px 4px 4px 4px;
            }
            .logo {
                width: 28px;
                height: 28px;
            }
            .hotel-name {
                font-size: 0.8rem;
            }
            .tagline {
                font-size: 0.7rem;
            }
        }
        #policyModal.modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        #policyModal .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 700px;
            width: 95vw;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem 2.5rem 1.5rem 2.5rem;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        }
        #policyModal .close-btn {
            position: absolute;
            top: 18px;
            right: 22px;
            background: none;
            border: none;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
            z-index: 2;
        }
        #policyModal .close-btn:hover { color: #008000; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="left-portrait">
            <div class="left-content">
                <img src="admin/images/villavalorelogo.png" alt="Villa Valore Logo" class="logo">
                <div class="hotel-name">Villa Valore Hotel</div>
                <div class="tagline">Where Every Stay Feels Like Coming Home.</div>
            </div>
        </div>
        <div class="login-container">
            <h1>Villa Valore Hotel</h1>
            <h2>SIGN IN</h2>
            <form method="POST" action="login.php" autocomplete="off">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required autofocus>
                    <svg class="input-icon" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <svg class="input-icon" viewBox="0 0 24 24">
                        <path d="M12 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm6-7V7a6 6 0 0 0-12 0v3a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2zm-8-3a4 4 0 0 1 8 0v3H6V7z"/>
                    </svg>
                </div>
                <button type="submit" name="login" class="login-btn">SIGN IN</button>
                <?php if (!empty($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </form>
            <div class="links">
                <a href="forgot_password.php" class="forgot-link">FORGOT PASSWORD</a>
            </div>
        </div>
    </div>

</body>
</html> 