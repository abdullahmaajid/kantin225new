<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Koneksi ke database
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'kantin225';

$conn = new mysqli($host, $user, $password, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil rentang tanggal dari form
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-01-01'; // Default to earliest date
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query untuk menghitung total pendapatan dalam rentang tanggal
$totalRevenueQuery = "
    SELECT SUM(total_price) AS total_revenue 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate'
";
$totalRevenueResult = $conn->query($totalRevenueQuery);
$totalRevenue = $totalRevenueResult->fetch_assoc()['total_revenue'] ?: 0;

// Query untuk menghitung total jenis menu yang tersedia dalam rentang tanggal
$totalUniqueMenuQuery = "
    SELECT COUNT(DISTINCT item_name) AS total_unique_menu 
    FROM order_items 
    JOIN orders ON orders.id = order_items.order_id 
    WHERE orders.date BETWEEN '$startDate' AND '$endDate'
";
$totalUniqueMenuResult = $conn->query($totalUniqueMenuQuery);
$totalUniqueMenu = $totalUniqueMenuResult->fetch_assoc()['total_unique_menu'] ?: 0;


// Query untuk menghitung total pelanggan dalam rentang tanggal
$totalCustomersQuery = "
    SELECT COUNT(DISTINCT customer_name) AS total_customers 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate'
";
$totalCustomersResult = $conn->query($totalCustomersQuery);
$totalCustomers = $totalCustomersResult->fetch_assoc()['total_customers'] ?: 0;

// Query untuk menghitung total order dalam rentang tanggal
$totalOrdersQuery = "
    SELECT COUNT(*) AS total_orders 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate'
";
$totalOrdersResult = $conn->query($totalOrdersQuery);
$totalOrders = $totalOrdersResult->fetch_assoc()['total_orders'] ?: 0;

// Query untuk mendapatkan data pelanggan, total harga, dan jumlah pesanan
$customerPaymentsQuery = "
    SELECT customer_name, COUNT(*) AS total_orders, SUM(total_price) AS total_harga 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY customer_name 
    ORDER BY total_harga DESC
";
$customerPaymentsResult = $conn->query($customerPaymentsQuery);

// Query untuk mendapatkan data makanan dan jumlah total pesanan setiap item dalam rentang tanggal
$itemOrdersQuery = "
    SELECT item_name, SUM(quantity) AS total_quantity_ordered 
    FROM order_items 
    JOIN orders ON orders.id = order_items.order_id 
    WHERE orders.date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY item_name 
    ORDER BY total_quantity_ordered DESC
";
$itemOrdersResult = $conn->query($itemOrdersQuery);

// Query untuk grafik pendapatan harian
$dailyRevenueQuery = "
    SELECT DATE(date) AS order_date, SUM(total_price) AS total_revenue 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY DATE(date)
";
$dailyRevenueResult = $conn->query($dailyRevenueQuery);
$dailyRevenueData = [];
while ($row = $dailyRevenueResult->fetch_assoc()) {
    $dailyRevenueData[] = [
        'date' => $row['order_date'],
        'revenue' => $row['total_revenue']
    ];
}



// Query untuk grafik pendapatan bulanan
$monthlyRevenueQuery = "
    SELECT DATE_FORMAT(date, '%Y-%m') AS order_month, SUM(total_price) AS total_revenue 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY order_month
";
$monthlyRevenueResult = $conn->query($monthlyRevenueQuery);
$monthlyRevenueData = [];
while ($row = $monthlyRevenueResult->fetch_assoc()) {
    $monthlyRevenueData[] = [
        'month' => $row['order_month'],
        'revenue' => $row['total_revenue']
    ];
}

// Query untuk grafik pendapatan tahunan
$yearlyRevenueQuery = "
    SELECT YEAR(date) AS order_year, SUM(total_price) AS total_revenue 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY order_year
";
$yearlyRevenueResult = $conn->query($yearlyRevenueQuery);
$yearlyRevenueData = [];
while ($row = $yearlyRevenueResult->fetch_assoc()) {
    $yearlyRevenueData[] = [
        'year' => $row['order_year'],
        'revenue' => $row['total_revenue']
    ];
}

// Query untuk mendapatkan 3 pelanggan terbaik
$topCustomerQuery = "
    SELECT customer_name, COUNT(*) AS order_count, SUM(total_price) AS total_spent 
    FROM orders 
    WHERE date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY customer_name 
    ORDER BY total_spent DESC 
    LIMIT 10
";
$topCustomerResult = $conn->query($topCustomerQuery);

// Query untuk mendapatkan menu terlaris
$topMenuQuery = "
    SELECT item_name, SUM(quantity) AS total_ordered 
    FROM order_items 
    JOIN orders ON orders.id = order_items.order_id 
    WHERE orders.date BETWEEN '$startDate' AND '$endDate' 
    GROUP BY item_name 
    ORDER BY total_ordered DESC 
    LIMIT 10
";
$topMenuResult = $conn->query($topMenuQuery);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

<style>
        /* Menggunakan font dari Google Fonts pada elemen body */
        body {
          font-family: "Inter", sans-serif;
          font-optical-sizing: auto;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">




<style>
  /* Sidebar styles */
  #sidebar {
    z-index: 1000; /* Ensure sidebar is above the main content */
    transition: transform 1s ease; /* Smooth transition */
    background-color: #da0010; /* Custom background color */
    width: 160px; /* Set sidebar width when visible */
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* Align items at the top */
  }

  .sidebar-hidden {
    transform: translateX(-100%); /* Off-screen to the left */
  }

  .sidebar-visible {
    transform: translateX(0); /* Back to the normal position */
  }

  /* Icon styles */
  .sidebar-icon {
    width: 24px; /* Set icon width */
    height: 24px; /* Set icon height */
    filter: brightness(0) invert(1); /* Make icons white */
    margin-right: 8px; /* Add space between icon and text */
  }

  /* Sidebar header styles */
  .sidebar-header {
    display: flex;
    justify-content: center; /* Center the header content */
    align-items: center;
    margin-bottom: 1rem; /* Space below header */
  }

  /* Menu item styles */
  .sidebar-menu a {
    display: flex;
    align-items: center;
    color: white; /* Text color */
    text-decoration: none; /* Remove underline */
    transition: color 0.3s, transform 0.3s; /* Smooth color and scale transition */
  }

  .sidebar-menu a:hover {
    color: #ffcccc; /* Change color on hover */
    transform: scale(1.1); /* Zoom effect on hover */
  }

  /* Spacing for menu items */
  .sidebar-menu {
    list-style-type: none; /* Remove bullet points */
    padding: 0; /* Remove default padding */
    margin: 0; /* Remove default margin */
  }

  .sidebar-menu li {
    margin-bottom: 1rem; /* Space between items */
  }
</style>


<!-- Button to open sidebar (K3MERAH.png) -->
<button id="openSidebarBtn" class="fixed top-4 left-4 z-50">
    <img src="./image/K3MERAH.png" alt="Open Sidebar" class="w-12 h-12">
</button>

<!-- Sidebar -->
<div id="sidebar" class="fixed top-0 left-0 h-full text-white p-4 sidebar-hidden">
  <!-- Sidebar header with close button (K3PUTIH.png) -->
  <div class="sidebar-header">
    <button id="closeSidebarBtn">
      <img src="./image/K3PUTIH.png" alt="Close Sidebar" class="w-12 h-12">
    </button>
  </div>

  <!-- Sidebar menu -->
  <ul class="sidebar-menu mb-6">

    <li>
      <a href="dashboard.php">
        <img src="./image/logo/dash.png" alt="Dashboard Icon" class="sidebar-icon">
        Dashboard
      </a>
    </li>

    <li>
      <a href="menu.php">
        <img src="./image/logo/menu.png" alt="Menu Icon" class="sidebar-icon">
        Menu
      </a>
    </li>

    <li>
      <a href="index.php">
        <img src="./image/logo/order.png" alt="Order Icon" class="sidebar-icon">
        Order
      </a>
    </li>

    <li>
      <a href="rekap.php">
        <img src="./image/logo/rekap.png" alt="Rekap Icon" class="sidebar-icon">
        Report
      </a>
    </li>

    <li>
      <a href="print.php">
        <img src="./image/logo/print.png" alt="Logout Icon" class="sidebar-icon">
        Print
      </a>
    </li>

    <li>
      <a href="chatai.php">
        <img src="./image/logo/chatai.png" alt="Chat AI Icon" class="sidebar-icon">
        Chat AI
      </a>
    </li>

    <li>
      <a href="login.php">
        <img src="./image/logo/login.png" alt="Login Icon" class="sidebar-icon">
        Login
      </a>
    </li>

    <li>
      <a href="logout.php">
        <img src="./image/logo/logout.png" alt="Logout Icon" class="sidebar-icon">
        Logout
      </a>
    </li>

  </ul>
</div>

<script>
  const openSidebarBtn = document.getElementById('openSidebarBtn');
  const closeSidebarBtn = document.getElementById('closeSidebarBtn');
  const sidebar = document.getElementById('sidebar');

  // Function to open the sidebar
  openSidebarBtn.addEventListener('click', () => {
    sidebar.classList.remove('sidebar-hidden');
    sidebar.classList.add('sidebar-visible'); // Add the visible class
    openSidebarBtn.style.display = 'none';  // Hide the open button
  });

  // Function to close the sidebar
  closeSidebarBtn.addEventListener('click', () => {
    sidebar.classList.remove('sidebar-visible'); // Remove the visible class
    sidebar.classList.add('sidebar-hidden');
    openSidebarBtn.style.display = 'block';  // Show the open button again
  });
</script>









<div class="container mx-auto mt-8 p-4">
    <h1 class="text-3xl font-bold text-black text-center mb-6">Dashboard</h1>
<!-- Form Pilih Tanggal -->
<form method="GET" class="mb-6 flex flex-col items-center justify-center bg-white shadow-md rounded-lg p-6 space-y-4">
    <div class="flex flex-row w-full space-x-4 items-center">
        <div class="flex flex-col w-1/2">
            <label for="start_date" class="text-lg font-semibold text-gray-700">Starting from:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo isset($startDate) ? $startDate : ''; ?>" class="border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600 transition duration-200 w-full" onchange="this.form.submit()">
        </div>
        <div class="flex flex-col w-1/2">
            <label for="end_date" class="text-lg font-semibold text-gray-700">Ending on:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo isset($endDate) ? $endDate : ''; ?>" class="border border-gray-300 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-600 transition duration-200 w-full" onchange="this.form.submit()">
        </div>
    </div>
</form>


<style>
    .bg-custom-red {
        background-color: #da0010;
    }

    .bg-custom-red:hover {
        background-color: #b7000b; /* Darker shade for hover effect */
    }
</style>















    
    <style>
        :root {
            --merah1: #da0010; /* Define the color variable */
        }

        .icon-container {
            background-color: var(--merah1); /* Use the variable for background color */
            border-radius: 8px; /* Slightly round the corners */
            padding: 10px; /* Add some padding for better spacing */
            margin-right: 10px; /* Add margin to the right to space it from the text */
        }

        .icon-container img {
            filter: brightness(0) invert(1); /* Invert icon color to white */
        }
    </style>

    <div class="container mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Pendapatan -->
            <div class="bg-white shadow-md rounded-lg p-4 flex items-center justify-center">
                <div class="flex items-center">
                    <div class="icon-container">
                        <img src="./image/logo/pendapatan.png" alt="Total Pendapatan Icon" class="w-12 h-12 p-1">
                    </div>
                    <div class="text">
                        <h2 class="text-left text-xl font-semibold mb-1" style="color: var(--merah1);">Total Revenue</h2>
                        <p class="text-center text-2xl font-bold">Rp <?php echo number_format($totalRevenue, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Menu yang Ada -->
            <div class="bg-white shadow-md rounded-lg p-4 flex items-center justify-center">
                <div class="flex items-center">
                    <div class="icon-container">
                        <img src="./image/logo/menu2.png" alt="Total Menu Icon" class="w-12 h-12 p-1">
                    </div>
                    <div class="text-center">
                        <h2 class="text-xl font-semibold mb-1" style="color: var(--merah1);">Total Items</h2>
                        <p class="text-2xl font-bold"><?php echo $totalUniqueMenu; ?> Items</p>
                    </div>
                </div>
            </div>

            <!-- Total Pelanggan -->
            <div class="bg-white shadow-md rounded-lg p-4 flex items-center justify-center">
                <div class="flex items-center">
                    <div class="icon-container">
                        <img src="./image/logo/pelanggan.png" alt="Total Pelanggan Icon" class="w-12 h-12 p-1">
                    </div>
                    <div class="text-center">
                        <h2 class="text-xl font-semibold mb-1" style="color: var(--merah1);">Total Customers</h2>
                        <p class="text-2xl font-bold"><?php echo $totalCustomers; ?> Customers</p>
                    </div>
                </div>
            </div>

            <!-- Total Order -->
            <div class="bg-white shadow-md rounded-lg p-4 flex items-center justify-center">
                <div class="flex items-center">
                    <div class="icon-container">
                        <img src="./image/logo/order2.png" alt="Total Order Icon" class="w-12 h-12 p-1">
                    </div>
                    <div class="text-center">
                        <h2 class="text-xl font-semibold mb-1" style="color: var(--merah1);">Total Orders</h2>
                        <p class="text-2xl font-bold"><?php echo $totalOrders; ?> Order</p>
                    </div>
                </div>
            </div>
        </div>
    </div>










    





<style>
table {
    table {
    border-radius: 10px; /* Rounded corners for the entire table */
}
}

th {
    font-weight: bold;
    text-align: left;
}

td {
    transition: background-color 0.2s;
}

/* Hover effect for table rows */
tr:hover td {
    background-color: #f7f7f7; /* Light gray on hover */
}

</style>



        <!-- Section for Customer Payments and Most Ordered Items -->
<div class="flex flex-wrap justify-between mb-6 mt-5">

<!-- Customer Payments -->
<div class="w-full md:w-1/2 flex p-1">
    <div class="bg-white shadow-lg rounded-lg p-4 flex-grow" style="height: 513px; overflow-y: auto;">
        <h2 class="text-2xl font-semibold mb-4" style="color: #da0010;">Customer Payment</h2>
        <table class="min-w-full border-collapse rounded-lg overflow-hidden">
            <thead>
                <tr style="background-color: #da0010; color: white;">
                    <th class="px-4 py-2">Customer Name</th>
                    <th class="px-4 py-2">Total Payment</th>
                    <th class="px-4 py-2">Order Quantity</th> <!-- Kolom baru untuk jumlah pesanan -->
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $customerPaymentsResult->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-100 transition-colors duration-200 border-b border-gray-300">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td class="px-4 py-2">Rp <?php echo number_format($row['total_harga'], 2, ',', '.'); ?></td>
                        <td class="px-4 py-2"><?php echo $row['total_orders']; ?></td> <!-- Menampilkan jumlah pesanan -->
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>




<!-- Most Ordered Items -->
<div class="w-full md:w-1/2 flex p-1">
    <div class="bg-white shadow-lg rounded-lg p-4 flex-grow" style="height: 513px; overflow-y: auto;">
        <h2 class="text-2xl font-semibold mb-4" style="color: #da0010;">Best-Selling Menu</h2>
        <table class="min-w-full border-collapse rounded-lg overflow-hidden">
            <thead>
                <tr style="background-color: #da0010; color: white;">
                    <th class="px-4 py-2">Menu Name</th>
                    <th class="px-4 py-2">Total Ordered</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $itemOrdersResult->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-100 transition-colors duration-200 border-b border-gray-300">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td class="px-4 py-2"><?php echo $row['total_quantity_ordered']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</div>




<!-- Chart Section -->
<div class="chart-section">

<div class="chart-container" style="background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
    <h2 class="text-2xl font-semibold mb-4" style="color: #da0010;">Daily Revenue</h2>
    <canvas id="dailyRevenueChart" class="mb-6" style="height: 400px;"></canvas>
</div>

    <div class="chart-container mt-8" style="background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <h2 class="text-2xl font-semibold mb-4" style="color: #da0010;">Monthly Revenue</h2>
        <canvas id="monthlyRevenueChart" class="mb-6" style="height: 400px;"></canvas>
    </div>

    <div class="chart-container mt-8" style="background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <h2 class="text-2xl font-semibold mb-4" style="color: #da0010;">Annual Revenue</h2>
        <canvas id="yearlyRevenueChart" class="mb-6" style="height: 400px;"></canvas>
    </div>
</div>

<script>

    // Daily Revenue Chart
    const dailyRevenueData = <?php echo json_encode($dailyRevenueData); ?>;
    const dailyRevenueChart = new Chart(document.getElementById('dailyRevenueChart'), {
        type: 'line',
        data: {
            labels: dailyRevenueData.map(item => item.date),
            datasets: [{
                label: 'Daily Revenue',
                data: dailyRevenueData.map(item => item.revenue),
                borderColor: '#da0010',
                backgroundColor: '#da0010',
                fill: true,
                lineTension: 0.4, // Adjust this value for roundness (0 for sharp corners, 1 for very smooth curves)
                borderJoinStyle: 'round', // Round joins between segments
                borderCapStyle: 'round', // Round caps on the line
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });


    // Monthly Revenue Chart
    const monthlyRevenueData = <?php echo json_encode($monthlyRevenueData); ?>;
    const monthlyRevenueChart = new Chart(document.getElementById('monthlyRevenueChart'), {
        type: 'bar',
        data: {
            labels: monthlyRevenueData.map(item => item.month),
            datasets: [{
                label: 'Monthly Revenue',
                data: monthlyRevenueData.map(item => item.revenue),
                backgroundColor: '#da0010',
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Yearly Revenue Chart
    const yearlyRevenueData = <?php echo json_encode($yearlyRevenueData); ?>;
    const yearlyRevenueChart = new Chart(document.getElementById('yearlyRevenueChart'), {
        type: 'bar',
        data: {
            labels: yearlyRevenueData.map(item => item.year),
            datasets: [{
                label: 'Annual Revenue',
                data: yearlyRevenueData.map(item => item.revenue),
                backgroundColor: 
                    '#da0010',
            }]
        },
        options: {
            responsive: true
        }
    });
</script>


</body>
</html>



<?php
// Tutup koneksi
$conn->close();
?>
