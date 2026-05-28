<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Login logic
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('includes/db.php');

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Sanitize input
    $email = mysqli_real_escape_string($conn, $email);

    // Check user
    $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_photo'] = $user['profile_photo'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MoneyTrack - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #b19cd9;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            background-color: #1e1e1e;
            width: 90%;
            max-width: 1400px;
            height: 85vh;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .sidebar {
            width: 250px;
            background-color: #131313;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            border-radius: 20px 0 0 20px;
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #2a2a2a;
        }

        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
            object-fit: cover;
        }

        .sidebar-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            gap: 5px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #9e9e9e;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background-color: #1a1a1a;
            color: #06d6a0;
        }

        .menu-item.active {
            background-color: #1a1a1a;
            color: #06d6a0;
            border-left: 4px solid #06d6a0;
        }

        .menu-item i {
            margin-right: 15px;
        }

        .sidebar-footer {
            padding: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .logo span {
            color: #06d6a0;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background-color: #252525;
            border-radius: 10px;
            padding: 20px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
        }

        .error {
            color: #e53935;
            margin-bottom: 15px;
        }

        form input,
        form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }

        form button {
            background-color: #06d6a0;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background-color: #04a575;
        }

        .login-container p {
            text-align: center;
            margin-top: 20px;
            color: #9e9e9e;
        }

        .login-container a {
            color: #06d6a0;
            text-decoration: none;
        }

        .login-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">


        <div class="main-content">
            <div class="login-container">
                <h2>Login to MoneyTrack</h2>
                <?php if ($error): ?>
                    <p class="error">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="email" name="email" placeholder="Email" required><br>
                    <input type="password" name="password" placeholder="Password" required><br>
                    <button type="submit">Login</button>
                </form>
                <p>Don't have an account? <a href="register.php">Register</a></p>
            </div>
        </div>
    </div>
</body>

</html>