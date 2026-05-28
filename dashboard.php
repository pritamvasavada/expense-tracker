<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

// Fetch user ID
$user_id = $_SESSION['user_id'];

// Fetch total income
$incomeQuery = "SELECT SUM(amount) AS total_income FROM expenses WHERE user_id=$user_id AND type='income'";
$incomeResult = mysqli_query($conn, $incomeQuery);
$income = mysqli_fetch_assoc($incomeResult)['total_income'] ?? 0;

// Fetch total expenses
$expenseQuery = "SELECT SUM(amount) AS total_expense FROM expenses WHERE user_id=$user_id AND type='expense'";
$expenseResult = mysqli_query($conn, $expenseQuery);
$expense = mysqli_fetch_assoc($expenseResult)['total_expense'] ?? 0;

// Calculate balance
$balance = $income - $expense;

// Get recent expenses
$recentExpensesQuery = "SELECT * FROM expenses WHERE user_id=$user_id ORDER BY date DESC LIMIT 5";
$recentExpensesResult = mysqli_query($conn, $recentExpensesQuery);

// Get expense categories for charts
$categoriesQuery = "SELECT category, SUM(amount) as total FROM expenses 
                   WHERE user_id=$user_id AND type='expense' 
                   GROUP BY category ORDER BY total DESC LIMIT 7";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categories = [];
while ($row = mysqli_fetch_assoc($categoriesResult)) {
    $categories[$row['category']] = $row['total'];
}

// Get monthly expense data for charts
$monthlyQuery = "SELECT DATE_FORMAT(date, '%b') as month, SUM(amount) as total 
                FROM expenses 
                WHERE user_id=$user_id AND type='expense' AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(date, '%Y-%m') 
                ORDER BY date ASC";
$monthlyResult = mysqli_query($conn, $monthlyQuery);
$monthlyData = [];
while ($row = mysqli_fetch_assoc($monthlyResult)) {
    $monthlyData[$row['month']] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Expensio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .pro-badge {
            background-color: #1e88e5;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .card {
            background-color: #252525;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            color: #9e9e9e;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .finance-value {
            font-size: 32px;
            font-weight: bold;
            color: #fff;
            margin: 20px 0;
        }
        
        .income-value {
            color: #06d6a0;
        }
        
        .expense-value {
            color: #e53935;
        }
        
        .balance-value {
            color: #1e88e5;
        }
        
        .finance-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .finance-row {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #333;
        }
        
        .finance-label {
            flex: 1;
            display: flex;
            align-items: center;
        }
        
        .chart-container {
            grid-column: span 2;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .chart {
            height: 250px;
            display: flex;
            flex-direction: column;
        }
        
        .chart-title {
            margin-bottom: 15px;
            color: #fff;
        }
        
        .bar-chart {
            flex: 1;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            padding-top: 20px;
        }
        
        .bar-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .bar {
            width: 100%;
            background-color: #06d6a0;
            border-radius: 5px 5px 0 0;
        }
        
        .bar-finance {
            background-color: #00897b;
        }
        
        .bar-marketing {
            background-color: #5e35b1;
        }
        
        .bar-sales {
            background-color: #d81b60;
        }
        
        .bar-operations {
            background-color: #e53935;
        }
        
        .bar-label {
            font-size: 12px;
            color: #9e9e9e;
        }
        
        .quick-access {
            grid-column: span 2;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        
        .action-button {
            flex: 1;
            display: flex;
            align-items: center;
            background-color: #252525;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
        }
        
        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        
        .action-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 15px;
        }
        
        .icon-expense {
            background-color: #d81b60;
        }
        
        .icon-receipt {
            background-color: #3949ab;
        }
        
        .icon-report {
            background-color: #00897b;
        }
        
        .icon-trip {
            background-color: #e53935;
        }
        
        .icon-logout {
            background-color: #757575;
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
            
            .grid-container {
                grid-template-columns: 1fr;
            }
            
            .quick-access, .chart-container {
                grid-column: span 1;
            }
            
            .quick-access {
                flex-wrap: wrap;
            }
            
            .action-button {
                flex: 1 0 40%;
            }
            
            .chart-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
            <?php
                $profile_photo = 'assets/img/user.png'; // Default
                if (!empty($_SESSION['user_photo'])) {
                $profile_path = "assets/img/profiles/" . $_SESSION['user_photo'];
                if (file_exists($profile_path)) {
                $profile_photo = $profile_path;
                }
                }
            ?>
                <img src="<?php echo $profile_photo; ?>" alt="Profile" class="profile-img">
                <h3><?php echo $_SESSION['user_name']; ?></h3>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="view_expenses.php" class="menu-item">
                    <i class="fas fa-credit-card"></i> Expenses
                </a>
                <a href="settings.php" class="menu-item">
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
                <div class="logo">MONEY<span>Track</span></div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="main-header">
                <h2>Welcome back, <?php echo $_SESSION['user_name']; ?> 👋</h2>
                <div class="pro-badge">Pro</div>
            </div>
            
            <div class="grid-container">
                <div class="card">
                    <h3 class="card-title">TOTAL INCOME</h3>
                    <div class="finance-value income-value">
                        <i class="fas fa-arrow-circle-up finance-icon"></i>
                        ₹<?php echo number_format($income, 2); ?>
                    </div>
                    <p class="card-subtitle">From all income sources</p>
                </div>
                
                <div class="card">
                    <h3 class="card-title">TOTAL EXPENSES</h3>
                    <div class="finance-value expense-value">
                        <i class="fas fa-arrow-circle-down finance-icon"></i>
                        ₹<?php echo number_format($expense, 2); ?>
                    </div>
                    <p class="card-subtitle">From all expense categories</p>
                </div>
                
                <div class="card" style="grid-column: span 2;">
                    <h3 class="card-title">TOTAL BALANCE</h3>
                    <div class="finance-value balance-value">
                        <i class="fas fa-wallet finance-icon"></i>
                        ₹<?php echo number_format($balance, 2); ?>
                    </div>
                    <p class="card-subtitle">Current financial health</p>
                </div>
                
                <div class="quick-access">
                    <a href="add_expense.php" class="action-button">
                        <div class="action-icon icon-expense">
                            <i class="fas fa-plus"></i>
                        </div>
                        <span>+ New expense</span>
                    </a>
                    <a href="add_expense.php" class="action-button">
                        <div class="action-icon icon-receipt">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <span>+ Add income</span>
                    </a>
                    <a href="view_expenses.php" class="action-button">
                        <div class="action-icon icon-report">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span>View all transactions</span>
                    </a>
                    <a href="logout.php" class="action-button">
                        <div class="action-icon icon-logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <span>Logout</span>
                    </a>
                </div>
                
                <div class="chart-container">
                    <div class="card chart">
                        <h3 class="chart-title">Category Spending</h3>
                        <div class="bar-chart">
                            <?php
                            if (!empty($categories)) {
                                foreach ($categories as $category => $amount): 
                                    // Calculate percentage height based on the max value
                                    $max = max($categories);
                                    $height = ($amount / $max) * 80; // 80% max height
                            ?>
                            <div class="bar-container">
                                <div class="bar" style="height: <?php echo $height; ?>%"></div>
                                <span class="bar-label"><?php echo $category; ?></span>
                            </div>
                            <?php 
                                endforeach;
                            } else {
                                echo "<p>No category data available</p>";
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="card chart">
                        <h3 class="chart-title">Monthly Expenses</h3>
                        <div class="bar-chart">
                            <?php
                            if (!empty($monthlyData)) {
                                foreach ($monthlyData as $month => $amount): 
                                    // Calculate percentage height based on the max value
                                    $max = max($monthlyData);
                                    $height = ($amount / $max) * 80; // 80% max height
                            ?>
                            <div class="bar-container">
                                <div class="bar" style="height: <?php echo $height; ?>%"></div>
                                <span class="bar-label"><?php echo $month; ?></span>
                            </div>
                            <?php 
                                endforeach;
                            } else {
                                echo "<p>No monthly data available</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>