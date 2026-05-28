<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    if (!is_numeric($amount) || $amount <= 0) {
        $error = "Amount must be a positive number.";
    } else {
        $amount = number_format((float) $amount, 2, '.', '');
        $query = "INSERT INTO expenses (user_id, amount, type, category, description, date)
                  VALUES ('$user_id', '$amount', '$type', '$category', '$description', '$date')";
        if (mysqli_query($conn, $query)) {
            $success = "Record added successfully!";
        } else {
            $error = "Failed to add record.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Expense - MoneyTrack</title>
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

        .add-entry-container {
            background-color: #252525;
            border-radius: 10px;
            padding: 20px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .add-entry-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
        }

        .error {
            color: #e53935;
            margin-bottom: 15px;
        }

        .success {
            color: #06d6a0;
            margin-bottom: 15px;
        }

        form input,
        form select,
        form textarea,
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

        .add-entry-container p {
            text-align: center;
            margin-top: 20px;
            color: #9e9e9e;
        }

        .add-entry-container a {
            color: #06d6a0;
            text-decoration: none;
        }

        .add-entry-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">

        <div class="main-content">
            <div class="add-entry-container">
                <h2>Add New Entry</h2>
                <?php if ($error): ?>
                    <p class="error">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p class="success">
                        <?php echo $success; ?>
                    </p>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required><br>

                    <select name="type" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select><br>

                    <input type="text" name="category" placeholder="Category (e.g. Food, Salary)" required><br>

                    <textarea name="description" placeholder="Optional Description" rows="3"></textarea><br>

                    <input type="date" name="date" required><br>

                    <button type="submit">Add Entry</button>
                </form>
                <p><a href="dashboard.php">⬅ Back to Dashboard</a></p>
            </div>
        </div>
    </div>
</body>

</html>