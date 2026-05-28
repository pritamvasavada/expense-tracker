<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('includes/db.php');

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM expenses WHERE user_id=$user_id ORDER BY date DESC";
$result = mysqli_query($conn, $query);

// Get profile photo path
$profile_photo = 'assets/img/user.png'; // Default
if (!empty($_SESSION['user_photo'])) {
    $profile_path = "assets/img/profiles/" . $_SESSION['user_photo'];
    if (file_exists($profile_path)) {
        $profile_photo = $profile_path;
    }
}

// Get expense type totals
$incomeQuery = "SELECT SUM(amount) AS total FROM expenses WHERE user_id=$user_id AND type='income'";
$incomeResult = mysqli_query($conn, $incomeQuery);
$income = mysqli_fetch_assoc($incomeResult)['total'] ?? 0;

$expenseQuery = "SELECT SUM(amount) AS total FROM expenses WHERE user_id=$user_id AND type='expense'";
$expenseResult = mysqli_query($conn, $expenseQuery);
$expense = mysqli_fetch_assoc($expenseResult)['total'] ?? 0;

// Apply filters if set
$filterCondition = " AND user_id=$user_id";
$categoryFilter = "";
$typeFilter = "";
$dateFilter = "";

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $categoryFilter = mysqli_real_escape_string($conn, $_GET['category']);
    $filterCondition .= " AND category='$categoryFilter'";
}

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $typeFilter = mysqli_real_escape_string($conn, $_GET['type']);
    $filterCondition .= " AND type='$typeFilter'";
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $dateFrom = mysqli_real_escape_string($conn, $_GET['date_from']);
    $filterCondition .= " AND date >= '$dateFrom'";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $dateTo = mysqli_real_escape_string($conn, $_GET['date_to']);
    $filterCondition .= " AND date <= '$dateTo'";
}

// Get all categories for filter dropdown
$categoriesQuery = "SELECT DISTINCT category FROM expenses WHERE user_id=$user_id ORDER BY category ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

// Get filtered expenses
$filteredQuery = "SELECT * FROM expenses WHERE 1=1 $filterCondition ORDER BY date DESC";
$filteredResult = mysqli_query($conn, $filteredQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - Expensio</title>
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
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card {
            background-color: #252525;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-size: 14px;
            color: #9e9e9e;
            margin-bottom: 5px;
        }
        
        .form-control {
            padding: 8px 12px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: #fff;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #06d6a0;
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            background-color: #04a881;
        }
        
        .btn-secondary {
            background-color: #333;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background-color: #444;
        }
        
        .btn-reset {
            background-color: #676767;
            color: #fff;
        }
        
        .btn-reset:hover {
            background-color: #7a7a7a;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .transaction-table th,
        .transaction-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        .transaction-table th {
            background-color: #1a1a1a;
            color: #9e9e9e;
            font-weight: 500;
            position: sticky;
            top: 0;
        }
        
        .transaction-table tr:hover {
            background-color: #2a2a2a;
        }
        
        .table-container {
            max-height: 500px;
            overflow-y: auto;
            border-radius: 10px;
            background-color: #252525;
        }
        
        .income-text {
            color: #06d6a0;
            font-weight: 500;
        }
        
        .expense-text {
            color: #e53935;
            font-weight: 500;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background-color: #252525;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .summary-title {
            font-size: 14px;
            color: #9e9e9e;
            margin-bottom: 10px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
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
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 0;
            color: #9e9e9e;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 14px;
        }
        
        .btn-edit {
            background-color: #1e88e5;
            color: #fff;
        }
        
        .btn-delete {
            background-color: #e53935;
            color: #fff;
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
            
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
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
                <h3><?php echo $_SESSION['user_name']; ?></h3>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="view_expenses.php" class="menu-item active">
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
                <div class="logo">Money<span>Track</span></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="main-header">
                <h2>Transactions History</h2>
                <a href="add_expense.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-title">TOTAL INCOME</div>
                    <div class="summary-value income-value">
                        <i class="fas fa-arrow-circle-up"></i> ₹<?php echo number_format($income, 2); ?>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="summary-title">TOTAL EXPENSES</div>
                    <div class="summary-value expense-value">
                        <i class="fas fa-arrow-circle-down"></i> ₹<?php echo number_format($expense, 2); ?>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="summary-title">BALANCE</div>
                    <div class="summary-value balance-value">
                        <i class="fas fa-wallet"></i> ₹<?php echo number_format($income - $expense, 2); ?>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card">
                <h3 style="margin-bottom: 15px;">Filters</h3>
                <form class="filter-form" method="GET" action="">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php while ($category = mysqli_fetch_assoc($categoriesResult)): ?>
                                <option value="<?php echo $category['category']; ?>" <?php echo ($categoryFilter == $category['category']) ? 'selected' : ''; ?>>
                                    <?php echo $category['category']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select class="form-control" id="type" name="type">
                            <option value="">All Types</option>
                            <option value="expense" <?php echo ($typeFilter == 'expense') ? 'selected' : ''; ?>>Expense</option>
                            <option value="income" <?php echo ($typeFilter == 'income') ? 'selected' : ''; ?>>Income</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="view_expenses.php" class="btn btn-reset">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Transactions Table -->
            <div class="table-container">
                <?php if (mysqli_num_rows($filteredResult) > 0): ?>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($filteredResult)): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td class="<?php echo ($row['type'] == 'income') ? 'income-text' : 'expense-text'; ?>">
                                        <?php echo ucfirst($row['type']); ?>
                                    </td>
                                    <td class="<?php echo ($row['type'] == 'income') ? 'income-text' : 'expense-text'; ?>">
                                        ₹<?php echo number_format($row['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_expense.php?id=<?php echo $row['id']; ?>" class="btn btn-icon btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_expense.php?id=<?php echo $row['id']; ?>" class="btn btn-icon btn-delete" onclick="return confirm('Are you sure you want to delete this record?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No transactions found</h3>
                        <p>Try adjusting your filters or add new transactions</p>
                        <a href="add_expense.php" class="btn btn-primary" style="margin-top: 15px;">
                            Add New Transaction
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>