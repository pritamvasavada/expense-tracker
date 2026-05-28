<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update name
    if (isset($_POST['update_name'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        
        if (empty($name)) {
            $error = "Name cannot be empty.";
        } else {
            $updateQuery = "UPDATE users SET name = '$name' WHERE id = $user_id";
            if (mysqli_query($conn, $updateQuery)) {
                $_SESSION['user_name'] = $name;
                $success = "Name updated successfully!";
                // Refresh user data
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = "Failed to update name.";
            }
        }
    }
    
    // Update profile photo
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['profile_photo']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($ext), $allowed)) {
            $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        } else {
            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = "assets/img/profiles/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
                // Delete old photo if exists and not default
                if (!empty($user['profile_photo']) && $user['profile_photo'] != 'user.png' && file_exists($upload_dir . $user['profile_photo'])) {
                    unlink($upload_dir . $user['profile_photo']);
                }
                
                $updateQuery = "UPDATE users SET profile_photo = '$new_filename' WHERE id = $user_id";
                if (mysqli_query($conn, $updateQuery)) {
                    $success = "Profile photo updated successfully!";
                    // Refresh user data
                    $result = mysqli_query($conn, $query);
                    $user = mysqli_fetch_assoc($result);
                } else {
                    $error = "Failed to update profile photo in database.";
                }
            } else {
                $error = "Failed to upload profile photo.";
            }
        }
    }
    
    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else if ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } else if (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $success = "Password updated successfully!";
            } else {
                $error = "Failed to update password.";
            }
        }
    }
}

// Get profile photo path
$profile_photo = 'assets/img/user.png'; // Default
if (!empty($user['profile_photo'])) {
    $profile_path = "assets/img/profiles/" . $user['profile_photo'];
    if (file_exists($profile_path)) {
        $profile_photo = $profile_path;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - MoneyTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
        }
        
        .settings-header {
            margin-bottom: 30px;
        }
        
        .settings-title {
            font-size: 24px;
            color: #fff;
            margin-bottom: 10px;
        }
        
        .settings-subtitle {
            color: #9e9e9e;
            font-size: 16px;
        }
        
        .settings-section {
            background-color: #252525;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #333;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 16px;
        }
        
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 16px;
            border: 1px solid #333;
        }
        
        .profile-preview {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .preview-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 20px;
            object-fit: cover;
            border: 3px solid #06d6a0;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #06d6a0;
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            background-color: #04a881;
        }
        
        .btn-danger {
            background-color: #e53935;
            color: #fff;
        }
        
        .btn-danger:hover {
            background-color: #c62828;
        }
        
        .error, .success {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error {
            background-color: rgba(229, 57, 53, 0.2);
            border: 1px solid #e53935;
            color: #e53935;
        }
        
        .success {
            background-color: rgba(6, 214, 160, 0.2);
            border: 1px solid #06d6a0;
            color: #06d6a0;
        }
        
        @media (max-width: 1200px) {
            .dashboard-container {
                flex-direction: column;
                height: auto;
            }
            
            .sidebar {
                width: 100%;
                border-radius: 20px 20px 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo $profile_photo; ?>" alt="Profile" class="profile-img">
                <h3><?php echo $user['name']; ?></h3>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="view_expenses.php" class="menu-item">
                    <i class="fas fa-credit-card"></i> Expenses
                </a>
                <a href="settings.php" class="menu-item active">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-headset"></i> Support
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <div class="sidebar-footer">
                <div class="logo">Money<span>Track</span></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="settings-header">
                <h2 class="settings-title">Account Settings</h2>
                <p class="settings-subtitle">Manage your account information and preferences</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Information -->
            <div class="settings-section">
                <h3 class="section-title">Profile Information</h3>
                
                <div class="profile-preview">
                    <img src="<?php echo $profile_photo; ?>" alt="Profile" class="preview-img">
                    <div>
                        <h4><?php echo $user['name']; ?></h4>
                        <p><?php echo $user['email']; ?></p>
                    </div>
                </div>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                    </div>
                    
                    <button type="submit" name="update_name" class="btn btn-primary">Update Name</button>
                </form>
            </div>
            
            <!-- Profile Photo -->
            <div class="settings-section">
                <h3 class="section-title">Profile Photo</h3>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_photo">Upload New Photo</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                        <small style="display: block; margin-top: 5px; color: #9e9e9e;">Recommended size: 200x200 pixels. Max size: 2MB.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Upload Photo</button>
                </form>
            </div>
            
            <!-- Password -->
            <div class="settings-section">
                <h3 class="section-title">Change Password</h3>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>