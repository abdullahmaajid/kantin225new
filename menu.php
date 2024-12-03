
<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
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

// Check if form data is submitted for adding a new menu item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $category = $_POST['category'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Handle image upload
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
        $img = file_get_contents($_FILES['img']['tmp_name']); // Read the image file

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO menu_items (category, name, price, img, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $category, $name, $price, $img, $description);

        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>Swal.fire('Success', 'Menu item added successfully!', 'success');</script>";
        } else {
            echo "<script>Swal.fire('Error', 'Error adding menu item: " . $stmt->error . "', 'error');</script>";
        }

        // Close statement
        $stmt->close();
    } else {
        echo "<script>Swal.fire('Error', 'Image upload failed. Error code: " . $_FILES['img']['error'] . "', 'error');</script>";
    }
}

// Check if form data is submitted for updating a menu item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $category = $_POST['category'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Handle image upload if new image is provided
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
        $img = file_get_contents($_FILES['img']['tmp_name']); // Read the image file

        // Prepare and bind
        $stmt = $conn->prepare("UPDATE menu_items SET category=?, name=?, price=?, img=?, description=? WHERE id=?");
        $stmt->bind_param("ssdssi", $category, $name, $price, $img, $description, $id);
    } else {
        // Update without changing the image
        $stmt = $conn->prepare("UPDATE menu_items SET category=?, name=?, price=?, description=? WHERE id=?");
        $stmt->bind_param("ssdsi", $category, $name, $price, $description, $id);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>Swal.fire('Success', 'Menu item updated successfully!', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Error updating menu item: " . $stmt->error . "', 'error');</script>";
    }

    // Close statement
    $stmt->close();
}

// Check if a menu item should be deleted
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Prepare and bind
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id=?");
    $stmt->bind_param("i", $id);

    // Execute the statement
    if ($stmt->execute()) {
        $alertMessage = "Menu item deleted successfully!";
    } else {
        $alertMessage = "Error deleting menu item: " . $stmt->error;
    }

    // Close statement
    $stmt->close();
}


// Retrieve all menu items
$result = $conn->query("SELECT * FROM menu_items");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu CRUD</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
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
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* styles.css */
        .menu-table-container {
            height: 680px;          /* Set the height to 640px */
            overflow-y: auto;      /* Enable vertical scrolling */
            overflow-x: auto;    /* Hide horizontal overflow */
        }

        .menu-table {
            min-width: 100%;       /* Ensure the table uses full width */
        }

        /* Custom Styles */
        .btn-red,.bg-red-500,.bg-gray-300 {
            background-color: #da0010; /* Custom red color */
            color: white;               /* White text */
        }

        .btn-red:hover {
            background-color: #b8000b; /* Darker red on hover */
        }

        .table-header {
            background-color: #da0010; /* Custom red header */
            color: white;               /* White text in header */
        }
        .add-menu-box, .update-menu-box {
            height: 770px; /* Set height */
            overflow-y: auto; /* Allow vertical scrolling if content exceeds the height */
        }


/* Mobile (up to 640px) */
@media (max-width: 640px) {
    .add-menu-box,
    .update-menu-box {
        margin: 0 0 1rem; /* Reduce margin for mobile */
    }

    /* Stack form sections vertically */
    .flex {
        flex-direction: column; /* Stack items vertically */
    }

    .menu-table-container {
        overflow-x: auto; /* Enable horizontal scroll */
    }
    .menu-table-container {
            height: 650px;          /* Set the height to 640px */
            overflow-y: auto;      /* Enable vertical scrolling */
            overflow-x: auto;    /* Hide horizontal overflow */
    }
    .add-menu-box, .update-menu-box {
            height:650px; /* Set height */
            overflow-y: auto; /* Allow vertical scrolling if content exceeds the height */
    }
}

/* Tablet/Pad (641px - 1024px) */
@media (min-width: 641px) and (max-width: 1024px) {
    .menu-table-container {
            height: 850px;          /* Set the height to 640px */
            overflow-y: auto;      /* Enable vertical scrolling */
            overflow-x: auto;    /* Hide horizontal overflow */
    }
    .add-menu-box, .update-menu-box {
            height:940px; /* Set height */
            overflow-y: auto; /* Allow vertical scrolling if content exceeds the height */
    }

    
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





















    <div class="container mx-auto mt-8 p-4">
        <h1 class="text-3xl font-bold text-black text-center mb-6">Menu</h1>

        <div class="flex flex-col md:flex-row space-x-0 md:space-x-4">
            <!-- Left Side: Add Menu Item -->
            <div class="w-full md:w-1/4 mb-4 md:mb-0"> <!-- 1 Kolom -->
                <div class="bg-white shadow-md rounded-lg p-6 mb-7 add-menu-box">
                    <h2 class="text-xl font-semibold mb-10">Add Menu Item</h2>
                    <form id="menuForm" enctype="multipart/form-data" method="POST">
                        <div class="grid grid-cols-1 gap-3 mb-10">
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700" for="category">Category</label>
                                <input type="text" name="category" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Category" required>
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="name">Item Name</label>
                                <input type="text" name="name" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Item Name" required>
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="price">Price</label>
                                <input type="number" name="price" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Price" required>
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="img">Image</label>
                                <input type="file" name="img" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" accept="image/*" required>
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="description">Description</label>
                                <textarea name="description" class="form-textarea mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Description" required></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add" class="btn-red mt-3 font-bold py-2 px-4 rounded hover:bg-red-500 transition">Add Menu Item</button>
                    </form>
                </div>
            </div>

            <!-- Middle Side: Update Form -->
            <div class="w-full md:w-1/4 mb-4 md:mb-0"> <!-- 1 Kolom -->
                <div id="updateForm" class="bg-white shadow-md rounded-lg p-6 mb-7 update-menu-box">
                    <h2 class="text-xl font-semibold mb-10">Update Menu Item</h2>
                    <form enctype="multipart/form-data" method="POST">
                        <input type="hidden" name="id" id="updateId">
                        <div class="grid grid-cols-1 gap-3 mb-10">
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700" for="updateCategory">Category</label>
                                <input type="text" name="category" id="updateCategory" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Category">
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="updateName">Item Name</label>
                                <input type="text" name="name" id="updateName" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Item Name">
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="updatePrice">Price</label>
                                <input type="number" name="price" id="updatePrice" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Price">
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="img">Image</label>
                                <input type="file" name="img" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm" accept="image/*">
                            </div>
                            <div>
                                <label class="text-xl block text-sm font-bold text-gray-700 mt-5" for="updateDescription">Description</label>
                                <textarea name="description" id="updateDescription" class="form-textarea mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Description"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update" class="btn-red mt-3 font-bold py-2 px-4 rounded hover:bg-blue-500 transition">Update Menu Item</button>
                        <button type="button" class="mt-3 bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded hover:bg-gray-200 transition" onclick="clearUpdateForm()">Clear</button>
                    </form>
                </div>
            </div>

            <!-- Right Side: Menu Items Table -->
<div class="w-full md:w-1/2"> <!-- 2 Kolom -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-7">
        <h2 class="text-xl font-semibold mb-4">Menu Items</h2>
        <div class="menu-table-container overflow-x-auto overflow-y-auto h-120"> <!-- Allow horizontal and vertical scrolling -->
            <?php if ($result->num_rows > 0): ?>
                <table class="menu-table border border-gray-300 rounded-lg overflow-hidden">
                    <thead>
                        <tr class="table-header text-left">
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Price</th>
                            <th class="py-3 px-4">Image</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($row['category']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($row['price']); ?></td>
                                <td class="py-3 px-4">
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['img']); ?>" alt="Menu Item" class="w-20 h-20 object-cover rounded">
                                </td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="py-3 px-4 flex flex-col space-y-2">
                                    <button class="btn-red rounded px-4 py-2" onclick="openUpdateForm(<?php echo htmlspecialchars($row['id']); ?>, '<?php echo htmlspecialchars($row['category']); ?>', '<?php echo htmlspecialchars($row['name']); ?>', <?php echo htmlspecialchars($row['price']); ?>, '<?php echo htmlspecialchars($row['description']); ?>')">Edit</button>
                                    <button class="bg-red-500 text-white rounded px-4 py-2" onclick="confirmDelete(<?php echo htmlspecialchars($row['id']); ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-600">No menu items found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>



            
        </div>
    </div>










<script>
    function confirmDelete(id) {
    const confirmation = confirm("Are you sure you want to delete this menu item? You won't be able to revert this action.");
    if (confirmation) {
        // Redirect to menu.php with the delete parameter
        window.location.href = "menu.php?delete=" + id; // Use 'delete' here
    }
        
}




    function openUpdateForm(id, category, name, price, description) {
        document.getElementById('updateId').value = id;
        document.getElementById('updateCategory').value = category;
        document.getElementById('updateName').value = name;
        document.getElementById('updatePrice').value = price;
        document.getElementById('updateDescription').value = description;
        document.getElementById('updateForm').scrollIntoView({ behavior: 'smooth' });
    }

    function clearUpdateForm() {
        document.getElementById('updateId').value = '';
        document.getElementById('updateCategory').value = '';
        document.getElementById('updateName').value = '';
        document.getElementById('updatePrice').value = '';
        document.getElementById('updateDescription').value = '';
    }
</script>
</body>
</html>




 
