<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Koneksi ke database
$host = "localhost";
$username = "root";
$password = "";
$database = "kantin225";

$conn = new mysqli($host, $username, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Get the start and end date from the URL parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Adjust the query based on the selected dates
$query = "
    SELECT 
        o.id AS order_id,
        o.customer_name,
        o.date,
        o.payment_method,
        o.total_price,
        o.amount_paid,
        o.change_due,
        o.transfer_proof,
        o.created_at
    FROM orders o
";

// Add date filter to the query if dates are selected
if ($start_date && $end_date) {
    $query .= " WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date' ";
}

$query .= " ORDER BY o.created_at DESC";

$result = $conn->query($query);

// Fungsi untuk mengubah nama hari dan tanggal
function formatTanggalIndo($tanggal) {
    $hari = array(
        'Sunday' => 'Minggu', 
        'Monday' => 'Senin', 
        'Tuesday' => 'Selasa', 
        'Wednesday' => 'Rabu', 
        'Thursday' => 'Kamis', 
        'Friday' => 'Jumat', 
        'Saturday' => 'Sabtu'
    );

    $bulan = array(
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    );

    // Mengubah format tanggal ke "Hari, Tanggal Bulan Tahun"
    $date = new DateTime($tanggal);
    $hariIndonesia = $hari[$date->format('l')];
    $bulanIndonesia = $bulan[$date->format('m')];
    $tanggalLengkap = $date->format('d') . ' ' . $bulanIndonesia . ' ' . $date->format('Y');

    return $hariIndonesia . ', ' . $tanggalLengkap;
}

// Export to Excel function
if (isset($_POST['export_to_excel'])) {
  // Get the current date in the format "Senin 15 Januari 2025"
  $current_date = formatTanggalIndo(date('Y-m-d')); // Get today's date

  // Remove the day of the week (e.g., "Senin") and format it to fit the filename
  $date_parts = explode(', ', $current_date);
  $formatted_date = $date_parts[1]; // Get the "15 Januari 2025" part

  // Set the filename for export
  $file_name = "Data Rekap " . $formatted_date . ".xls";

  header("Content-Type: application/xls");
  header("Content-Disposition: attachment; filename=$file_name");

  // Output table headers
  echo "Order ID\tCustomer Name\tDate\tPayment Method\tTotal Price\tAmount Paid\tChange Due\tCreated At\tItem Name\tPrice\tQuantity\tTotal Price\n";

  // Loop through orders and fetch item details
  while ($row = $result->fetch_assoc()) {
      // Fetch items for each order
      $order_id = $row['order_id'];
      $items_query = "
          SELECT 
              oi.item_name, 
              oi.price, 
              oi.quantity, 
              (oi.price * oi.quantity) AS total_item_price
          FROM order_items oi
          WHERE oi.order_id = '$order_id'
      ";
      
      $items_result = $conn->query($items_query);

      // Output order data and item details
      while ($item = $items_result->fetch_assoc()) {
          echo $row['order_id'] . "\t" 
              . $row['customer_name'] . "\t" 
              . $row['date'] . "\t" 
              . $row['payment_method'] . "\t" 
              . $row['total_price'] . "\t" 
              . $row['amount_paid'] . "\t" 
              . $row['change_due'] . "\t" 
              . formatTanggalIndo($row['created_at']) . "\t"  // Corrected to show created_at as formatted date
              . $item['item_name'] . "\t" 
              . $item['price'] . "\t" 
              . $item['quantity'] . "\t" 
              . $item['total_item_price'] . "\n";
      }
  }

  exit;
}

?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Orders - Kantin225</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
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


<style>
    body {
      background-color: #f3f4f6; /* bg-gray-100 */
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh; /* Make sure the body takes at least the full viewport height */
      margin: 0; /* Remove default margin */
    }

    .container {
      background-color: white;
      padding: 24px; /* p-6 */
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* shadow-md */
      border-radius: 8px; /* rounded-lg */
      width: 100%;
      margin-top: 0px;
      overflow-y: auto;
  
    }

</style>

</head>
<body>
<div class="container">
    <h1 class="text-2xl font-bold text-gray-700 mb-6 text-center">Data Orders</h1>

    <div class="flex items-center justify-center space-x-1 mb-6 flex-wrap">
    <!-- Date Inputs -->
    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="px-2 py-2 border rounded-md date-input" placeholder="Pilih Rentang Tanggal">
    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="px-2 py-2 border rounded-md date-input" placeholder="Pilih Rentang Tanggal">
    
    <!-- Export Button -->
    <form method="post" class="w-full md:w-auto">
        <button type="submit" name="export_to_excel" class="ml-1 px-2 py-2 bg-blue-500 text-white rounded-md custom-btn w-full md:w-auto">Export to Excel</button>
    </form>
</div>

<style>
    .custom-btn {
        background-color: #da0010; /* Set background color */
        color: white;              /* Set text color to white */
        font-weight: bold;         /* Make text bold */
    }

    .custom-btn:hover {
        background-color: #da0010; /* Optional: change color on hover */
    }

    /* Responsive design for mobile */
    @media (max-width: 768px) {
        .flex {
            flex-direction: column; /* Stack elements vertically on mobile */
            align-items: center;    /* Center items horizontally */
        }

        /* Ensure the date inputs and button are aligned properly */
        .date-input {
            width: 90%; /* Take up 90% of the container width */
            max-width: 250px; /* Optional: limit the width for better fit */
            margin-bottom: 10px; /* Add space between date inputs and button */
        }

        /* Ensure button width matches the combined width of date inputs */
        .custom-btn {
            width: 90%; /* Button width is same as date inputs */
            max-width: 530px; /* Optional: maximum width */
        }

        /* Adjust spacing between date inputs */
        .space-x-1 {
            gap: 10px; /* Adjust gap between date inputs */
        }
    }

    /* Web (Desktop) view */
    @media (min-width: 769px) {
        .flex {
            flex-direction: row; /* Keep elements in a row on desktop */
        }

        .date-input, .custom-btn {
            width: auto; /* Let elements take natural width */
        }

        .space-x-1 {
            gap: 10px; /* Adjust gap between date inputs and button */
        }
    }
</style>

<script>
    // Get the date input elements
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Add event listeners to update the URL with selected dates
    startDateInput.addEventListener('change', updateFilter);
    endDateInput.addEventListener('change', updateFilter);

    function updateFilter() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Reload the page with the selected date range as query parameters
        window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
    }
</script>

<div class="overflow-x-auto">
    <style>
        .rounded-table {
            border-collapse: collapse; /* Menghindari border ganda antara sel */
            width: 100%;
            height: 605px; /* Tinggi tabel diubah ke 300px */
            border-radius: 8px; /* Sudut membulat */
            overflow-y: auto; /* Menambahkan overflow vertikal untuk scroll */
            display: block; /* Mengubah menjadi block agar overflow bekerja */
        }
        .rounded-table th, .rounded-table td {
            padding: 8px;
            text-align: center; 
        }
        .rounded-table td, .rounded-table th {
            border-left: none;
            border-right: none;
        }
        .rounded-table tr {
            border-bottom: 1px solid #ddd; /* Border horizontal */
        }


        
    </style>
</div>








</styl>

<table class="rounded-table bg-gray-50 overflow-y-auto">
           
           
           <style>
                .custom-table-header {
  background-color: #da0010;
  font-weight: bold;
  color: white;
}

            </style>
                <thead class="custom-table-header">
                    <tr>
                    <th class="px-4 py-2 border">ID</th>
<th class="px-4 py-2 border">Customer Name</th>
<th class="px-4 py-2 border">Date</th>
<th class="px-4 py-2 border">Payment Method</th>
<th class="px-4 py-2 border">Total Price</th>
<th class="px-4 py-2 border">Amount Paid</th>
<th class="px-4 py-2 border">Change</th>
<th class="px-4 py-2 border">Ordered Items</th>
<th class="px-4 py-2 border">Transfer Proof</th>
<th class="px-4 py-2 border">Created At</th>

                    </tr>
                </thead>
                <tbody>
                <style>
    .min-w-full {
        width: 100%;
        border-collapse: collapse; /* Prevent double borders */
        border-radius: 8px; /* Rounded corners for the entire table */
        overflow: hidden; /* Ensure the rounded corners are visible */
    }

    .min-w-full th, .min-w-full td {
        padding: 8px;
        text-align: left;
        border-left: none;  /* Remove vertical borders */
        border-right: none; /* Remove vertical borders */
    }

    .min-w-full th {
        background-color: #da0010; /* Red header background */
        color: white; /* White text for the header */
    }

    .min-w-full tr {
        border-bottom: 1px solid #ddd; /* Horizontal border between rows */
    }



    /* Optional: Add a border around the entire table */
    .min-w-full {
        border: 2px solid #ccc; /* Optional: Outer border for the entire table */
    }
</style>


                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr class='text-sm text-gray-600 hover:bg-gray-100'>";
                            echo "<td class='px-4 py-2 border text-center'>{$row['order_id']}</td>";
                            echo "<td class='px-4 py-2 border'>{$row['customer_name']}</td>";
                            echo "<td class='px-4 py-2 border'>{$row['date']}</td>";
                            echo "<td class='px-4 py-2 border'>{$row['payment_method']}</td>";
                            echo "<td class='px-4 py-2 border'>Rp " . number_format($row['total_price'], 2, ',', '.') . "</td>";
                            echo "<td class='px-4 py-2 border'>Rp " . number_format($row['amount_paid'], 2, ',', '.') . "</td>";
                            echo "<td class='px-4 py-2 border'>Rp " . number_format($row['change_due'], 2, ',', '.') . "</td>";

                            // Sub-tabel untuk items
                            echo "<td class='px-4 py-2 border'>";
                            $items_query = "
                                SELECT 
                                    item_name, 
                                    price, 
                                    quantity, 
                                    (price * quantity) AS total 
                                FROM order_items 
                                WHERE order_id = {$row['order_id']}
                            ";
                            $items_result = $conn->query($items_query);

                        
if ($items_result->num_rows > 0) {
    echo "<table class='min-w-full border border-gray-200'>";
    echo "<thead class='bg-[#da0010] text-white'>";  // Change header background to red and text color to white
    echo "<tr>";
    echo "<th class='px-2 py-1'>Item</th>";
    echo "<th class='px-2 py-1'>Harga</th>";
    echo "<th class='px-2 py-1'>Qty</th>";
    echo "<th class='px-2 py-1'>Total</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

                                while ($item = $items_result->fetch_assoc()) {
                                    echo "<tr class='text-sm text-gray-600 hover:bg-gray-50'>";
                                    echo "<td class='px-2 py-1 border'>{$item['item_name']}</td>";
                                    echo "<td class='px-2 py-1 border'>Rp " . number_format($item['price'], 2, ',', '.') . "</td>";
                                    echo "<td class='px-2 py-1 border text-center'>{$item['quantity']}</td>";
                                    echo "<td class='px-2 py-1 border'>Rp " . number_format($item['total'], 2, ',', '.') . "</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody>";
                                echo "</table>";
                            } else {
                                echo "<span class='text-gray-500 italic'>Tidak ada items</span>";
                            }
                            echo "</td>";

                            // Menampilkan gambar bukti transfer dengan opsi popup dan download
                            echo "<td class='px-4 py-2 border text-center'>";
                            if (!empty($row['transfer_proof'])) {
                                // Format nama file download
                                $formatted_date = formatTanggalIndo($row['created_at']);
                                $file_name = "bukti_transfer_{$row['customer_name']}_{$formatted_date}";
                                $image_base64 = base64_encode($row['transfer_proof']);
                                echo "<a href='data:image/jpeg;base64,$image_base64' download='{$file_name}.jpg'>
                                        <img class='w-40 h-40 object-cover cursor-pointer' src='data:image/jpeg;base64,$image_base64' alt='Bukti Transfer'>
                                      </a>";
                            } else {
                                echo "<span class='text-gray-500'>Tidak ada bukti transfer</span>";
                            }
                            echo "</td>";

                            // Format tanggal pembuatan
                            echo "<td class='px-4 py-2 border'>" . formatTanggalIndo($row['created_at']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='10' class='px-4 py-2 border text-center text-red-500'>Tidak ada data</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
// Tutup koneksi
$conn->close();
?>
