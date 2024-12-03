<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Pengaturan koneksi
$host = 'localhost';
$dbname = 'kantin225';
$username = 'root';
$password = '';

// Menghubungkan ke database
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fungsi untuk mengambil semua pesanan dengan informasi dasar (ID, nama, tanggal, created_at)
function getAllOrders($conn) {
    $query = "SELECT id, customer_name, date, created_at FROM orders ORDER BY id DESC";

    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mengambil detail pesanan berdasarkan ID
function getOrderById($conn, $orderId) {
    $query = "
        SELECT o.id AS order_id, o.customer_name, o.date, o.payment_method, o.total_price, o.amount_paid, o.change_due, o.transfer_proof, 
               o.created_at, oi.item_name, oi.quantity, oi.price, oi.total
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = :order_id
    ";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mengambil semua pesanan
$orders = getAllOrders($conn);

// Mengecek apakah ada ID pesanan yang dimasukkan dan mengambil detail pesanan
$order = [];
if (isset($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);
    $rows = getOrderById($conn, $orderId);

    if ($rows) {
        $order = [
            'customer_name' => $rows[0]['customer_name'],
            'date' => $rows[0]['date'],
            'payment_method' => $rows[0]['payment_method'],
            'total_price' => $rows[0]['total_price'],
            'amount_paid' => $rows[0]['amount_paid'],
            'change_due' => $rows[0]['change_due'],
            'created_at' => $rows[0]['created_at'],
            'transfer_proof' => $rows[0]['transfer_proof'],
            'items' => []
        ];

        foreach ($rows as $row) {
            $order['items'][] = [
                'item_name' => $row['item_name'],
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'total' => $row['total']
            ];
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>


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
<style>
    /* Tombol Biru yang Diubah menjadi Merah */
    .btn-blue {
        background-color: #da0010;
    }
    .btn-blue:hover {
        background-color: #c4000d;
    }

    /* Tombol Hijau yang Diubah menjadi Merah */
    .btn-green {
        background-color: #da0010;
    }
    .btn-green:hover {
        background-color: #c4000d;
    }

    /* Header Tabel Item Pesanan yang Diubah menjadi Merah */
    .table-header {
        background-color: #da0010;
        color: white;
    }
</style>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">





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
        Rekap
      </a>
    </li>

    <li>
      <a href="print.php">
        <img src="./image/logo/print.png" alt="Logout Icon" class="sidebar-icon">
        Print Struk
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
        .order-container {
            margin-top: 0px;
            overflow-y: auto;
            height: 800px;
        }
    </style>
</head>
<body>
    <div class="container mx-auto p-8 bg-white rounded-lg shadow-md order-container">
        <h2 class="text-2xl font-semibold mb-6 text-gray-800 text-center">Lihat Pesanan Tersedia</h2>


        <style>
        /* Custom Styles for the Select Element */
        select {
            background-color: #da0010; /* Set background to red */
            color: white; /* Text color white */
            padding: 12px 20px; /* Add padding for better spacing */
            border: 2px solid #da0010; /* Border with matching red color */
            border-radius: 8px; /* Rounded corners */
            font-size: 16px; /* Font size */
            width: 100%; /* Make it responsive */
            appearance: none; /* Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            transition: all 0.3s ease; /* Smooth transition for hover and focus */
        }

        
    </style>
        <form method="GET">
        <select name="order_id" id="order_id" class="block w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4 hover:border-gray-400 focus:border-blue-500" required onchange="this.form.submit()">
            <option value="">-- Pilih Pesanan --</option>
            <?php foreach ($orders as $orderItem): 
                $createdDate = date('l, j F Y H:i', strtotime($orderItem['created_at']));
            ?>
                <option value="<?= htmlspecialchars($orderItem['id']) ?>" <?= (isset($_GET['order_id']) && $_GET['order_id'] == $orderItem['id']) ? 'selected' : '' ?>>
                    ID: <?= htmlspecialchars($orderItem['id']) ?> - Nama: <?= htmlspecialchars($orderItem['customer_name']) ?> - Tanggal: <?= $createdDate ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>




        <?php if (isset($order)): ?>
          <style>
        .order-box {
            margin-top: 0px;
            overflow-y: auto;
            height: 560px;
        }
    </style>

<div class="flex flex-col md:flex-row gap-8">
        <!-- Detail Pesanan -->
        <div id="order-details" class="w-full md:w-1/3 p-6 bg-gray-50 rounded-lg shadow-lg order-box">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Detail Pesanan</h2>
                    <table class="w-full text-left text-gray-700">
                        <tbody>
                            <tr>
                                <td class="font-semibold py-2">Nama Pelanggan</td>
                                <td class="py-2">: <?= htmlspecialchars($order['customer_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-semibold py-2">Tanggal</td>
                                <td class="py-2">: <?= htmlspecialchars($order['date']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-semibold py-2">Metode Pembayaran</td>
                                <td class="py-2">: <?= htmlspecialchars($order['payment_method']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-semibold py-2">Total Harga</td>
                                <td class="py-2">: <?= htmlspecialchars($order['total_price']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-semibold py-2">Jumlah Dibayar</td>
                                <td class="py-2">: <?= htmlspecialchars($order['amount_paid']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-semibold py-2">Kembalian</td>
                                <td class="py-2">: <?= htmlspecialchars($order['change_due']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-semibold py-2">Dibuat pada</td>
                                <td class="py-2">: <?= htmlspecialchars($order['created_at']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>




                <style>
        .order-item {
            margin-top: 0px;
            overflow-y: auto;
            height: 560px;
        }
    </style>

    <div class="w-full md:w-2/3 p-6 bg-gray-50 rounded-lg shadow-lg order-item">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Item Pesanan</h3>
        <style>
        .rounded-table {
            border-collapse: collapse; /* Menghindari border ganda antara sel */
            width: 100%;
            border: 2px solid #ccc; /* Border horizontal */
            border-radius: 8px; /* Sudut membulat */
            overflow: hidden; /* Menjaga border-radius terlihat pada sel */
        }
        .rounded-table th, .rounded-table td {
            padding: 8px;
            text-align: left;
        }
        /* Hapus border vertikal */
        .rounded-table td, .rounded-table th {
            border-left: none;
            border-right: none;
        }
        /* Border horizontal hanya pada baris */
        .rounded-table tr {
            border-bottom: 1px solid #ddd; /* Border horizontal */
        }

        /* Hover effect for rows */
        .rounded-table tr:hover {
            background-color: #f5f5f5; /* Light gray background on hover */
            cursor: pointer; /* Cursor changes to pointer */
        }

        /* Optional: Hover effect for table header */
        .rounded-table th:hover {
            background-color: #e0e0e0; /* Light gray for header hover */
        }
    </style>
                     <table class="rounded-table">
                        <thead>
                            <tr class="table-header">
                                <th class="p-2 border">Nama Item</th>
                                <th class="p-2 border">Kuantitas</th>
                                <th class="p-2 border">Harga</th>
                                <th class="p-2 border">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td class="p-2 border"><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td class="p-2 border"><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td class="p-2 border"><?= htmlspecialchars($item['price']) ?></td>
                                    <td class="p-2 border"><?= htmlspecialchars($item['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tombol Cetak Struk -->
            <button onclick="generatePDF()" class="btn-green mt-6 w-full text-white font-semibold py-2 rounded-lg shadow-md transition duration-200">
                Cetak Struk
            </button>





            
            
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.21/jspdf.plugin.autotable.min.js"></script>




            <script>
    async function generatePDF() {
        const { jsPDF } = window.jspdf;

        // Initialize PDF with custom receipt size
        const doc = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: [80, 150]
        });

        // Load and add logo
        const logo = await fetch("./image/K3MERAH.png")
            .then(response => response.blob())
            .then(blob => new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.readAsDataURL(blob);
            }));

        let yPosition = 6;  // Initial vertical position

        // Add Logo Centered
        doc.addImage(logo, 'PNG', 30, yPosition, 20, 20);
        yPosition += 25;

        // Header Section: Title and Address
        doc.setFontSize(10);
        doc.setFont("Helvetica", "bold");
        doc.text("INVOICE", 40, yPosition, { align: "center" });
        yPosition += 8;

        doc.setFontSize(7);
        doc.setFont("Helvetica", "normal");
        doc.text("Jl. Tegal Arum No.225, Gamping Kidul,", 40, yPosition, { align: "center" });
        yPosition += 4;
        doc.text("Ambarketawang, Kec. Gamping,", 40, yPosition, { align: "center" });
        yPosition += 4;
        doc.text("Kabupaten Sleman, Yogyakarta 55291", 40, yPosition, { align: "center" });
        yPosition += 8;

        // Order Items Table with left-aligned text and red background for header
        const itemTableColumns = ["Item", "Qty", "Harga", "Total"];
        const itemTableRows = [
            <?php foreach ($order['items'] as $item): ?>
                [
                    "<?= htmlspecialchars($item['item_name']) ?>", 
                    "<?= htmlspecialchars($item['quantity']) ?>", 
                    "<?= htmlspecialchars($item['price']) ?>", 
                    "<?= htmlspecialchars($item['total']) ?>"
                ],
            <?php endforeach; ?>
        ];

        doc.autoTable({
            head: [itemTableColumns],
            body: itemTableRows,
            startY: yPosition,
            theme: 'plain',  // Minimal styling
            headStyles: { 
                fontSize: 7, 
                font: "Helvetica", 
                fontStyle: "bold", 
                halign: 'center', 
                fillColor: '#da0010',  // Red background for headers
                textColor: '#ffffff'   // White text color for headers
            },
            bodyStyles: { 
                fontSize: 6, 
                font: "Helvetica", 
                halign: 'left',  // Left-aligned text in the item columns
                valign: 'middle', 
                cellPadding: 1
            },
            margin: { left: 5, right: 5 },
            tableWidth: 'auto',
            // Add gray horizontal borders and remove vertical borders
            styles: {
                lineColor: [200, 200, 200], // Gray color for horizontal borders
                lineWidth: 0.2,  // Border thickness
                cellPadding: 2,
                halign: 'center',
                valign: 'middle',
                lineJoin: 'miter' // Ensure sharp corners on borders
            },
            columnStyles: {
                0: { halign: 'left' }, // Left-align item name column
                1: { halign: 'center' },
                2: { halign: 'right' },
                3: { halign: 'right' }
            },
            // Remove vertical borders and keep horizontal borders
            tableLineWidth: 0.1,
            tableLineColor: [200, 200, 200]
        });

        yPosition = doc.lastAutoTable.finalY + 6;

        // Order Details Section (No borders)
        const orderDetails = [
            ["Nama Pelanggan", "<?= htmlspecialchars($order['customer_name']) ?>"],
            ["Tanggal", "<?= htmlspecialchars($order['date']) ?>"],
            ["Waktu Pesanan", "<?= htmlspecialchars($order['created_at']) ?>"],
            ["Metode Pembayaran", "<?= htmlspecialchars($order['payment_method']) ?>"],
            ["Total Harga", "<?= htmlspecialchars($order['total_price']) ?>"],
            ["Dibayar", "<?= htmlspecialchars($order['amount_paid']) ?>"],
            ["Kembalian", "<?= htmlspecialchars($order['change_due']) ?>"]
        ];

        doc.autoTable({
            body: orderDetails,
            startY: yPosition,
            theme: 'plain',  // Minimal styling
            columnStyles: {
                0: { cellWidth: 30, halign: 'left', fontStyle: "bold" },
                1: { cellWidth: 40, halign: 'right' }
            },
            bodyStyles: { fontSize: 6, font: "Helvetica", cellPadding: 0.8 },
            margin: { left: 5, right: 5 },
            // No borders around the order details section
            styles: {
                lineWidth: 0, // No border
            }
        });

        // Get current date and day in Indonesian
        const currentDate = new Date();
        const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
        const dayOfWeek = days[currentDate.getDay()];
        const dateString = currentDate.toLocaleDateString('id-ID').replace(/\//g, '_'); // format as dd_mm_yyyy

        const customerName = "<?= htmlspecialchars($order['customer_name']) ?>";

        // Construct filename
        const filename = `Orders_${customerName}_${dayOfWeek}_${dateString}.pdf`;

        // Save the PDF with the custom filename
        doc.save(filename);
    }
</script>



        <?php endif; ?>
    </div>
</body>
</html>
