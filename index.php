<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost"; // Your database server
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "kantin225"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize cart


session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Display success notification if available
$successMessage = '';
if (isset($_SESSION['notif_success'])) {
    $successMessage = $_SESSION['notif_success'];
    unset($_SESSION['notif_success']); // Unset after displaying
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding items to the cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    // Retrieve item details from the database
    $stmt = $conn->prepare("SELECT name, price FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        // Update quantity if item already exists in the cart
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] += $quantity; // Add to existing quantity
        } else {
            $item['quantity'] = $quantity;
            $_SESSION['cart'][$item_id] = $item; // Add item to cart
        }
        echo "<script>Swal.fire('Success', 'Item added to cart!', 'success');</script>";
    }

    $stmt->close();
}

// Handle quantity update in the cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quantity'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];

    // Update quantity in the cart
    if (isset($_SESSION['cart'][$item_id])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$item_id]); // Remove item from cart if quantity is 0
        } else {
            $_SESSION['cart'][$item_id]['quantity'] = $quantity; // Update quantity
        }
    }
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $customer_name = trim($_POST['customer_name']);
    $payment_method = $_POST['payment_method'];
    $amount_paid = $_POST['amount_paid'];
    $total_price = 0;

    // Calculate total price
    foreach ($_SESSION['cart'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    // Validate input fields
    if (empty($customer_name) || empty($payment_method) || empty($amount_paid) || $amount_paid <= 0) {
        echo "<script>Swal.fire('Error', 'All fields must be filled and the amount must be greater than 0!', 'error');</script>";
        return;
    }

    // Check if the paid amount is sufficient
    $change_due = $amount_paid - $total_price;
    if ($change_due < 0) {
        echo "<script>Swal.fire('Error', 'Insufficient payment. Please provide a higher amount!', 'error');</script>";
        return;
    }

    // Handle file upload
    $transfer_proof = null;
    if (isset($_FILES['transfer_proof']) && $_FILES['transfer_proof']['error'] === UPLOAD_ERR_OK) {
        // Check the MIME type
        $fileType = mime_content_type($_FILES['transfer_proof']['tmp_name']);
        if (in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
            $transfer_proof = file_get_contents($_FILES['transfer_proof']['tmp_name']); // Read the image as binary
        } else {
            echo "<script>Swal.fire('Error', 'Invalid file type. Only JPEG, PNG, and GIF are allowed.', 'error');</script>";
            return;
        }
    } elseif (isset($_FILES['transfer_proof']['error']) && $_FILES['transfer_proof']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle file upload errors
        echo "<script>Swal.fire('Error', 'An error occurred while uploading the file.', 'error');</script>";
        return;
    }

    // Prepare to insert order
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, date, payment_method, total_price, amount_paid, change_due, transfer_proof) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddsb", $customer_name, $payment_method, $total_price, $amount_paid, $change_due, $transfer_proof);

    if ($transfer_proof !== null) {
        $stmt->send_long_data(5, $transfer_proof); // Send binary data for the last parameter
    } else {
        $transfer_proof = ''; // Handle the case where no file was uploaded
    }

    // Execute the statement
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id; // Get the last inserted order ID

        // Insert order items into the database
        foreach ($_SESSION['cart'] as $item_id => $item) {
            $quantity = $item['quantity'];
            $total = $item['price'] * $quantity;

            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
            $stmt_item->bind_param("isidd", $order_id, $item['name'], $quantity, $item['price'], $total);
            if (!$stmt_item->execute()) {
                echo "<script>Swal.fire('Error', 'Error adding item to order: " . $stmt_item->error . "', 'error');</script>";
            }
            $stmt_item->close();
        }

        // Clear cart
        $_SESSION['cart'] = [];
        echo "<script>Swal.fire('Success', 'Order placed successfully! Change due: Rp " . number_format($change_due, 2, ',', '.') . "', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Error placing order: " . $stmt->error . "', 'error');</script>";
    }

    $stmt->close();
}

// Retrieve all menu items
$result = $conn->query("SELECT * FROM menu_items");

// Calculate total amount for the cart
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Get the search term if it exists
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';

// Retrieve all menu items based on the search term
$sql = "SELECT * FROM menu_items WHERE name LIKE ?";
$stmt = $conn->prepare($sql);
$searchParam = "%" . $searchTerm . "%";
$stmt->bind_param("s", $searchParam);
$stmt->execute();
$result = $stmt->get_result();

// Retrieve all categories
$categories = $conn->query("SELECT DISTINCT category FROM menu_items");

// Retrieve all transfer proofs
$transfer_proofs = $conn->query("SELECT transfer_proof FROM orders WHERE transfer_proof IS NOT NULL");

?> 







<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <style>
        .menu-img {
            height: 200px;
            object-fit: cover;
            border-top-left-radius: 0.375rem; /* Tailwind's rounded-t-md */
            border-top-right-radius: 0.375rem; /* Tailwind's rounded-t-md */
        }
        .btn-primary {
            background-color: #be2623;
        }
        .btn-primary:hover {
            background-color: #a5211e;
        }
        /* Main content styles */
            .container {
            z-index: 0; /* Lower z-index for main content */
            position: relative; /* Establish a positioning context */
        }   

      
    </style>
</head>



  

<body class="bg-gray-100">



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

</style>



<div class="container mx-auto mt-8 p-4">
        <h1 class="text-3xl font-bold text-black text-center mb-6">Order</h1>



    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Kolom Kiri: Menu Items -->
        <div>
            <h1 class="text-2xl font-bold mb-4 mt-4">Menu Items</h1>
            



<style>
    @media (min-width: 1025px){
    #menuItems {
    height: 560px; /* Default height for desktop */
    overflow-y: auto; /* Enable vertical scrolling */
}
    }

/* For tablets */
@media (min-width: 769px) and (max-width: 1024px) {
    #menuItems {
        overflow-y: auto; /* Enable vertical scrolling */
        height: 825px; /* Set height to 100px for tablet */
    }
}

/* For mobile devices */
@media (max-width: 480px) {
    #menuItems {
        overflow-y: auto; /* Enable vertical scrolling */
        height: 530px; /* Set height to 100px for mobile */
    }
}

.custom-add-to-cart-button {
                                                background-color: #da0010; /* Custom color */
                                            } 


</style>

<style>
    #searchArea {
        background-color: #ffffff; /* Optional: to ensure the search area has a white background */
    }

    #categoryFilters {
        display: flex; /* Use flexbox */
        justify-content: center; /* Center all items horizontally */
        align-items: center; /* Center all items vertically */
    }

    .category-box {

        background-color: #da0010; /* No background color by default */
        border: 2px solid white; /* Light gray border */
        border-radius: 8px; /* Rounded corners */
        padding: 10px; /* Space inside the box */
        transition: background-color 0.3s, transform 0.3s, border-color 0.3s; /* Smooth transition effects */
        display: flex; /* Center contents */
        align-items: center; /* Center vertically */
        justify-content: center; /* Center horizontally */
        cursor: pointer; /* Change cursor to pointer on hover */
 
    }

    .category-box:hover {
        background-color: #da0010; /* Light gray background on hover */
        border-color: #ffffff; /* Dark gray border on hover */
        transform: scale(1.05); /* Slightly increase size on hover */
    }

    .category-box.active {
        background-color: #da0010; /* Red background when active (clicked) */
        border-color: #ffffff; /* Change border to match background when active */
    }

    .category-icon {
        width: 40px; /* Adjust icon size */
        height: 40px; /* Adjust icon size */
        filter: invert(1); /* Default color */
        transition: filter 0.3s; /* Smooth transition for icon color change */
    }

    .category-box:hover .category-icon {
        filter: invert(1); /* Return to original color when hovered */
    }

    .category-box.active .category-icon {
        filter: invert(1); /* Invert color to white when active */
    }

    .menu-item-name {
        min-height: 48px; /* Adjust based on your longest title */
    }

    .menu-item-description {
        min-height: 56px; /* Adjust based on your longest description */
    }
</style>


<div id="searchArea" class="p-4 rounded-lg">
    <input type="text" id="searchInput" placeholder="Search menu items..." class="border rounded-md p-2 w-full mb-4" onkeyup="filterItems()">
    <div id="categoryFilters" class="flex space-x-2">
        <div class="category-box" data-category="all" onclick="filterByCategory('all', this)">
            <img src="./image/logo/all4.png" alt="category-icon" class="category-icon"> <!-- Add the class here -->
        </div>
        <div class="category-box" data-category="Makanan" onclick="filterByCategory('Makanan', this)">
            <img src="./image/logo/makanan2.png" alt="" class="category-icon">
        </div>
        <div class="category-box" data-category="Minuman" onclick="filterByCategory('Minuman', this)">
            <img src="./image/logo/minuman2.png" alt="" class="category-icon">
        </div>
        <div class="category-box" data-category="Topping" onclick="filterByCategory('Topping', this)">
            <img src="./image/logo/topping2.png" alt="" class="category-icon">
        </div>
        <div class="category-box" data-category="Jajanan" onclick="filterByCategory('Jajanan', this)">
            <img src="./image/logo/jajanan.png" alt="" class="category-icon">
        </div>
    </div>

    <div id="menuItems" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-white rounded-lg mt-4">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="menu-item bg-white rounded-lg hover:shadow-xl transition-transform transform hover:-translate-y-1 flex flex-col" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['img']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="menu-img rounded-t-lg w-full h-64 object-cover">
                    <div class="p-4 flex-grow">
                        <h5 class="menu-item-name text-lg font-semibold"><?php echo htmlspecialchars($row['name']); ?></h5>
                        <p class="menu-item-description text-gray-600"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="text-l menu-item-price font-bold mt-2 mb-4">Rp <?php echo htmlspecialchars(number_format($row['price'], 2, ',', '.')); ?></p>
                        
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                            <div class="flex flex-col lg:flex-row items-stretch lg:items-center mt-2 space-y-2 lg:space-y-0 lg:space-x-2 mt-2">
                                <input type="number" name="quantity" value="1" min="1" class="menu-item-quantity border border-gray-300 rounded-md py-1 px-2 w-full lg:w-16" placeholder="Qty">
                                <button type="submit" name="add_to_cart" class="custom-add-to-cart-button text-white rounded-md py-1 px-4 w-full">Add to Cart</button>
                            </div>
                        </form>


                        
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No menu items found.</p>
        <?php endif; ?>
    </div>
</div>


<style>
    .menu-item-name {
    min-height: 48px; /* Adjust based on your longest title */
}

.menu-item-description {
    min-height: 56px; /* Adjust based on your longest description */
}





</style>
<script>
    window.onload = function() {
        const names = document.querySelectorAll('.menu-item-name');
        const descriptions = document.querySelectorAll('.menu-item-description');

        let maxNameHeight = 0;
        let maxDescriptionHeight = 0;

        names.forEach(name => {
            const height = name.clientHeight;
            if (height > maxNameHeight) {
                maxNameHeight = height;
            }
        });

        descriptions.forEach(description => {
            const height = description.clientHeight;
            if (height > maxDescriptionHeight) {
                maxDescriptionHeight = height;
            }
        });

        names.forEach(name => {
            name.style.minHeight = `${maxNameHeight}px`;
        });

        descriptions.forEach(description => {
            description.style.minHeight = `${maxDescriptionHeight}px`;
        });
    };

    function filterItems() {
        var input, filter, items, item, txtValue;
        input = document.getElementById('searchInput');
        filter = input.value.toLowerCase();
        items = document.querySelectorAll('.menu-item');

        items.forEach(item => {
            txtValue = item.querySelector('.menu-item-name').textContent || item.querySelector('.menu-item-name').innerText;
            item.style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
        });
    }

    function filterByCategory(category, clickedBox) {
        var items = document.querySelectorAll('.menu-item');

        items.forEach(item => {
            if (category === 'all' || item.getAttribute('data-category') === category) {
                item.style.display = ""; // Show item
            } else {
                item.style.display = "none"; // Hide item
            }
        });

        // Remove active class from all category boxes
        const categoryBoxes = document.querySelectorAll('.category-box');
        categoryBoxes.forEach(box => {
            box.classList.remove('active');
        });

        // Add active class to the clicked category box
        clickedBox.classList.add('active');

        // Reset search input
        document.getElementById('searchInput').value = '';
    }
</script>



        </div>
      















<!-- Kolom Kanan: Form Order -->
<div>
    <h1 class="text-2xl font-bold mb-4 mt-4">Your Cart</h1>

    <style>
@media (min-width: 1025px) {
        .cart-scroll {
    height: 255px; /* Default height for larger screens */
    overflow-y: auto;
        }
        }


        @media (min-width: 769px) and (max-width: 1024px) {
        .cart-scroll {
    height: 440px; /* Default height for larger screens */
    overflow-y: auto;
        }
        }


@media (max-width: 480px) { /* For mobile devices */
    .cart-scroll {
        height: 275px; /* Adjust height for mobile */
        overflow-y: auto;
    }
}

    </style>

    <style>
        th, td {
    width: 33.33%; /* Setiap kolom mendapatkan 1/3 dari lebar tabel */
    text-align: center; /* Rata tengah teks */
}

td {
    padding: 10px; /* Tambahkan padding untuk estetika */
}

    </style>

<div class="bg-white rounded-lg p-4">
    <div class="cart-scroll">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-center">Item</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Price</th>
                    <th class="text-center">Action</th> <!-- Add Action column -->
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <?php foreach ($_SESSION['cart'] as $item_id => $item): ?>
                        <tr class="border-b">
                            <td class="py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="py-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                    <div class="flex flex-col items-center">
                                        <div class="flex items-center">
                                            <style>
                                                .qtyy {
                                                    color: #da0010; /* Button text color */
                                                }
                                            </style>
                                            <button type="button" class="qtyy bg-white border border-white rounded-md py-1 px-2 w-10" onclick="updateQuantity('<?php echo $item_id; ?>', -1)">-</button>
                                            <input type="text" name="quantity" id="quantity-<?php echo $item_id; ?>" value="<?php echo $item['quantity']; ?>" class="border border-gray rounded-md py-1 px-2 w-10 text-black text-center mx-1" readonly>
                                            <button type="button" class="qtyy bg-white border border-white rounded-md py-1 px-2 w-10" onclick="updateQuantity('<?php echo $item_id; ?>', 1)">+</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="quantity" id="quantity-hidden-<?php echo $item_id; ?>" value="<?php echo $item['quantity']; ?>">
                                </form>
                            </td>
                            <td class="text-center py-2">Rp <?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2, ',', '.')); ?></td>
                            <td class="text-center py-2">
                                <!-- Remove Item Button styled as per the design -->
                                <button type="button" class="text-red-500 py-1 px-2 rounded-md hover:bg-gray-200" onclick="removeItemFromCart('<?php echo $item_id; ?>')">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-2">Your cart is empty.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>


<script>
    function updateQuantity(itemId, change) {
    const quantityInput = document.getElementById(`quantity-${itemId}`);
    const hiddenInput = document.getElementById(`quantity-hidden-${itemId}`);
    
    let currentQuantity = parseInt(quantityInput.value);
    currentQuantity += change;

    // Ensure the quantity doesn't go below 0
    if (currentQuantity < 1) {
        currentQuantity = 0;
    }

    quantityInput.value = currentQuantity;
    hiddenInput.value = currentQuantity;

    // If quantity is 0, remove the item
    if (currentQuantity === 0) {
        removeItemFromCart(itemId);
    } else {
        // Submit form to update quantity on the server
        hiddenInput.form.submit();
    }
}


    function removeItemFromCart(itemId) {
        // Create hidden form to send remove item request
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const itemIdInput = document.createElement('input');
        itemIdInput.type = 'hidden';
        itemIdInput.name = 'remove_item_id';
        itemIdInput.value = itemId;
        form.appendChild(itemIdInput);

        // Append form to body and submit to remove item
        document.body.appendChild(form);
        form.submit();
    }
</script>

<?php
// Handle item removal
if (isset($_POST['remove_item_id'])) {
    $item_id_to_remove = $_POST['remove_item_id'];
    // Check if the item exists in the cart, then remove it
    if (isset($_SESSION['cart'][$item_id_to_remove])) {
        unset($_SESSION['cart'][$item_id_to_remove]);
    }
}
?>








<style>
    .total-amount-display {
    background-color: #da0010; /* Red background */
    color: white; /* White text */
    font-weight: bold; /* Bold text */
    border-radius: 5px; /* Rounded corners */
    padding: 0.5rem 1rem; /* Padding for spacing */
    text-align: center; /* Right alignment */
    width: 100%; /* Full width */
    margin-top: 1rem; /* Top margin */
    box-sizing: border-box; /* Ensures padding doesn’t exceed width */
    display: block; /* Full width for block element */
}



</style>
<h5 class="total-amount-display">Total: Rp <?php echo htmlspecialchars(number_format($total_amount, 2, ',', '.')); ?></h5>






<!-- Order Form -->
<h1 class=" text-2xl font-bold mb-4 mt-5">Place Your Order</h1>
<form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label for="customer_name" class="block text-gray-700">Customer Name</label>
            <input type="text" name="customer_name" class="border border-gray-300 rounded-md w-full p-2" required>
        </div>
        <div>
            <label for="payment_method" class="block text-gray-700">Payment Method</label>
            <select name="payment_method" class="border border-gray-300 rounded-md w-full p-2" required>
                <option value="cash">Cash</option>
                <option value="transfer">Bank Transfer</option>
            </select>
        </div>
        <div>
            <label for="amount_paid" class="block text-gray-700">Amount Paid</label>
            <input type="number" id="amount_paid" name="amount_paid" class="border border-gray-300 rounded-md w-full p-2" min="0" required onchange="calculateChange()">
        </div>
        <div>
            <label for="change_due" class="block text-gray-700">Change Due</label>
            <input type="text" id="change_due" class="border border-gray-300 rounded-md w-full p-2" readonly>
        </div>
        <div>
            <label for="required_payment" class="block text-gray-700">Additional Amount Needed</label>
            <input type="text" id="required_payment" class="border border-gray-300 rounded-md w-full p-2" readonly>
        </div>
        <div>
            <label for="transfer_proof" class="block text-gray-700">Transfer Proof (optional)</label>
            <input type="file" name="transfer_proof" class="border border-gray-300 rounded-md w-full p-2" accept="image/jpeg,image/png,image/gif">
        </div>
    </div>

    <style>
        .placeorder {
            background-color: #da0010; /* Custom color */
        } 
    </style>
    <button type="submit" name="place_order" class="placeorder text-white rounded-md py-2 px-4 w-full mt-2 font-bold">Place Order</button>

</form>






<script>
    const totalAmount = <?php echo json_encode($total_amount); ?>; // Get total amount from PHP to JS

    function calculateChange() {
        const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
        const changeDue = amountPaid - totalAmount;

        // Display change due or indicate no change due
        document.getElementById('change_due').value = changeDue >= 0 ? `Rp ${changeDue.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}` : 'Not Applicable';

        // Calculate additional amount needed if payment is insufficient
        const requiredPayment = totalAmount - amountPaid;
        document.getElementById('required_payment').value = requiredPayment > 0 ? `Rp ${requiredPayment.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}` : 'Paid in Full';
    }

    function validateForm() {
        const amountPaid = parseFloat(document.getElementById('amount_paid').value) || 0;
        if (amountPaid < totalAmount) {
            Swal.fire('Error', 'Insufficient payment. Please provide an additional amount of Rp ' + (totalAmount - amountPaid).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + '!', 'error');
            return false; // Prevent form submission
        }
        return true; // Allow form submission
    }
</script>





</body>
</html>





<?php
$conn->close();
?>
