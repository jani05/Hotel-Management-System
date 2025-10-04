<?php
// Handle form submission
if (isset($_POST['submit'])) {
    $email = $_POST['email'] ?? '';
    // Here you would check if the email exists in the DB
    // Then send a reset link or show a success message

    $msg = "If this email exists in our records, you will receive password reset instructions.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password | Villa Valore Hotel</title>
    <link rel="stylesheet" type="text/css" href="styles/forgotpassword.css">
    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            background: #fff;
            width: 350px;
            margin: 60px auto;
            padding: 30px 30px 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            margin-bottom: 25px;
            color: #333;
        }
        input[type="email"] {
            width: 90%;
            padding: 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 15px;
        }
        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background: #28a745; /* Green */
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #218838; /* Darker green */
        }
        .back-link {
            display: inline-block;
            margin-top: 18px;
            color: #28a745; /* Green */
            text-decoration: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #218838; /* Darker green */
            text-decoration: underline;
        }
        .message {
            margin-top: 18px;
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Request Password Change</h2>
        <form method="POST" autocomplete="off">
            <input type="email" name="email" placeholder="Email Address" required>
            <button type="submit" name="submit">Submit</button>
        </form>
        <?php if (isset($msg)) echo "<div class='message'>$msg</div>"; ?>
        <a href="login.php" class="back-link">BACK TO SIGN IN</a>
    </div>
</body>
</html>
