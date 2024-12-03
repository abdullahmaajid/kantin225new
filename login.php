
<?php
session_start();
$host = 'localhost';
$dbname = 'kantin225';
$username = 'root';
$password = '';

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$notif = '';

// Function to generate a random color
function getRandomColor() {
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

// Random background color
$backgroundColor = getRandomColor();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['notif_success'] = "Login successful! Welcome back, " . htmlspecialchars($user['username']) . "!";
            header("Location: index.php");
            exit();
        } else {
            $notif = "Incorrect username or password!";
        }
    } elseif (isset($_POST['register'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            $notif = "Registration successful! Please log in.";
        } else {
            $notif = "Registration failed!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
    

    <style>
    /* Custom notification color */
    .notif-error {
        color: #da0010; /* Red color for notification */
        font-size: 1rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    /* Overlay for background image with grayscale */
    .background-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.3); /* Dark overlay with 30% opacity */
        z-index: -1;
    }

    /* Glass effect for the form container */
    .glass-effect {
        background: rgba(255, 255, 255, 0.01);
        border-radius: 10px;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Glass effect for inputs with icons */
    .glass-input {
        background: rgba(255, 255, 255, 0.01);
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 8px 0;
        outline: none;
        padding-left: 2.5rem;
        transition: border-bottom-color 0.3s;
    }

    /* Red underline on focus */
    .glass-input:focus {
        border-bottom-color: #da0010;
    }

    /* Icon positioning */
    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 255, 255, 0.7);
    }

    /* Solid button style */
    .solid-button {
        background-color: #da0010; /* Updated color */
        color: white;
        transition: background-color 0.3s;
    }

    .solid-button:hover {
        background-color: #b3000d; /* Slightly darker shade of #da0010 for hover */
    }

    /* Placeholder color */
    ::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }
</style>

</head>
<body class="relative flex items-center justify-center h-screen bg-cover bg-center" style="background-image: url('./image/logo/kantin2.png');">

    <!-- Background Overlay with Grayscale Effect -->
    <div class="background-overlay"></div>

    <div class="w-full max-w-md glass-effect shadow-lg p-6 relative z-10">
        
        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <img src="./image/K3MBP.png" alt="Logo" class="w-20 h-20">
        </div>

        <h2 class="text-2xl font-semibold text-center text-white mb-4">
            <?php echo isset($_POST['register']) || (isset($_GET['action']) && $_GET['action'] == 'register') ? 'Register' : 'Login'; ?>
        </h2>

        <!-- Notification for errors -->
        <?php if ($notif): ?>
            <p class="notif-error"><?php echo $notif; ?></p>
        <?php endif; ?>

        <!-- Registration Form -->
        <?php if (isset($_POST['register']) || (isset($_GET['action']) && $_GET['action'] == 'register')): ?>
            <form action="login.php" method="POST" class="space-y-4 relative">
                
                <!-- Username field with icon -->
                <div class="relative">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" placeholder="Username" required class="w-full glass-input focus:ring-0 focus:border-blue-500">
                </div>

                <!-- Email field with icon -->
                <div class="relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Email" required class="w-full glass-input focus:ring-0 focus:border-blue-500">
                </div>

                <!-- Password field with icon and visibility toggle -->
                <div class="relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Password" required id="password" class="w-full glass-input focus:ring-0 focus:border-blue-500">
                    <i class="fas fa-eye-slash text-white absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer" onclick="togglePassword()" id="toggleIcon"></i>
                </div>

                <button type="submit" name="register" class="w-full solid-button py-2 rounded-lg hover:bg-blue-600 transition duration-300">Register</button>
                <p class="text-center text-gray-300">
                    Sudah punya akun? <a href="login.php" class="hover:underline" style="color: #da0010;">Login di sini</a>
                </p>
            </form>
        <?php else: ?>
            <form action="login.php" method="POST" class="space-y-4">
                
                <!-- Username field with icon -->
                <div class="relative">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" placeholder="Username" required class="w-full glass-input focus:ring-0 focus:border-blue-500">
                </div>

                <!-- Password field with icon and visibility toggle -->
                <div class="relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Password" required id="loginPassword" class="w-full glass-input focus:ring-0 focus:border-blue-500">
                    <i class="fas fa-eye-slash text-white absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer" onclick="toggleLoginPassword()" id="toggleLoginIcon"></i>
                </div>

                <button type="submit" name="login" class="w-full solid-button py-2 rounded-lg hover:bg-blue-600 transition duration-300">Login</button>
                <p class="text-center text-gray-300">
                    Belum punya akun? <a href="login.php?action=register" class="hover:underline" style="color: #da0010;">Daftar di sini</a>
                </p>
            </form>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script>
        // Toggle password visibility
        function togglePassword() {
            var passwordField = document.getElementById('password');
            var toggleIcon = document.getElementById('toggleIcon');
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            }
        }

        // Toggle password visibility for login
        function toggleLoginPassword() {
            var passwordField = document.getElementById('loginPassword');
            var toggleIcon = document.getElementById('toggleLoginIcon');
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            }
        }
    </script>
</body>
</html>
