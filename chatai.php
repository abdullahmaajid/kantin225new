<?php
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with AI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" href="./image/K3MERAH.png" type="image/png">
    <script type="importmap">
        {
            "imports": {
                "@google/generative-ai": "https://esm.run/@google/generative-ai"
            }
        }
    </script>
    <style>
        .chat-container {
            overflow-y: auto; /* Allow vertical scrolling */
            max-height: 760px; /* Set maximum height */
        }
    </style>
</head>





<body class="flex min-h-screen bg-gray-100">


   <!-- Main Container -->
<div class="container mx-auto p-1 m-16 flex flex-col lg:flex-row">

<!-- Sidebar for Chat History (Mobile to Desktop responsiveness) -->
<aside class="w-full lg:w-1/4 bg-gray-200 p-4 border-b lg:border-r border-gray-300 flex flex-col lg:flex-col mb-4 lg:mb-0">
  <h2 class="text-xl font-bold text-gray-800 mb-4">Chat History</h2>
  <button class="bg-red-600 text-white w-full py-2 mb-4 rounded-lg hover:bg-red-700 focus:outline-none" id="newChat">
      New Chat
  </button>
  <ul id="historyList" class="flex flex-col gap-2">
      <!-- Chat history items will be appended here -->
  </ul>
</aside>

<!-- Main Chat Section -->
<div class="p-6 flex-1 flex flex-col">
  <div id="header" class="text-center py-4 border-b border-gray-300">
      <h1 class="text-2xl font-bold text-gray-800">Chat with AI</h1>
      <p class="text-gray-600">Siap Membantu Hari-hari Anda</p>
  </div>
<!-- Style Section -->
<style>
  #chatContainer {
    max-height: 600px; /* Limit height to 500px */
    overflow-y: auto;  /* Add vertical scrolling if content exceeds the height */
  }
</style>
  <div id="chatContainer" class="flex-1 chat-container bg-white p-4">
      <!-- Chat messages will be appended here -->
  </div>

  <div class="mt-6 border-t border-gray-300 flex overflow-hidden">
      <textarea id="inputPrompt" placeholder="Masukkan Pertanyaan Anda"
          class="flex-1 border border-gray-300 rounded-lg p-2 resize-none focus:outline-none focus:border-red-600"
          rows="2"></textarea>
      <button id="sendButton"
          class="bg-red-600 text-white rounded-lg ml-2 px-4 py-2 hover:bg-red-700 focus:outline-none">Send</button>
  </div>
</div>

</div>



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












    <script type="module">
        import { GoogleGenerativeAI } from "@google/generative-ai";

        const API_KEY = "AIzaSyDfjmSvom8myGKVvlN1Zti3-tAHM4QcR_A"; // Replace with your actual API key
        const genAI = new GoogleGenerativeAI(API_KEY);
        const model = genAI.getGenerativeModel({ model: "gemini-1.5-flash" });
        
        let chatHistories = {}; // Store chat histories for each session
        let currentSessionId = 0; // Track the current session
        
        document.getElementById('sendButton').addEventListener('click', sendMessage);
        document.getElementById('newChat').addEventListener('click', startNewChat);

        async function sendMessage() {
            const prompt = document.getElementById('inputPrompt').value;
            if (!prompt.trim()) return; // Prevent sending empty messages
            
            displayMessage(prompt, 'user');

            const result = await model.generateContent(prompt);
            const aiResponse = result.response.text();
            displayMessage(aiResponse, 'ai');

            document.getElementById('inputPrompt').value = ''; // Clear input field
            
            // Save the chat to the current session
            saveToHistory(prompt, aiResponse);
        }

        function displayMessage(message, type) {
            const chatContainer = document.getElementById('chatContainer');
            const messageBubble = document.createElement('div');
            // Adjust the classes for user and AI messages
            messageBubble.className = `p-2 my-2 rounded-lg w-full max-w-xl ${type === 'user' ? 'bg-red-600 text-white self-end ml-auto' : 'bg-gray-200 text-gray-800 self-start'}`;
            messageBubble.innerText = message;
            chatContainer.appendChild(messageBubble);
            chatContainer.scrollTop = chatContainer.scrollHeight; // Auto-scroll
        }
        
        function startNewChat() {
            currentSessionId++;
            chatHistories[currentSessionId] = []; // Create a new session
            document.getElementById('chatContainer').innerHTML = ''; // Clear chat
            document.getElementById('inputPrompt').value = ''; // Clear input field
            updateHistoryList(); // Update the history list
        }

        function saveToHistory(userMessage, aiResponse) {
            if (!chatHistories[currentSessionId]) {
                chatHistories[currentSessionId] = []; // Initialize if it doesn't exist
            }
            chatHistories[currentSessionId].push({ user: userMessage, ai: aiResponse });

            updateHistoryList();
        }

        function updateHistoryList() {
            const historyList = document.getElementById('historyList');
            historyList.innerHTML = ''; // Clear the current list
            Object.keys(chatHistories).forEach(sessionId => {
                const historyItem = document.createElement('li');
                historyItem.className = "p-2 bg-gray-300 rounded-lg cursor-pointer hover:bg-gray-400";
                historyItem.innerText = `Session ${sessionId}`; // Show session number
                historyItem.addEventListener('click', () => loadChat(sessionId));
                historyList.appendChild(historyItem);
            });
        }

        function loadChat(sessionId) {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.innerHTML = ''; // Clear current chat
            const messages = chatHistories[sessionId];
            messages.forEach(msg => {
                displayMessage(msg.user, 'user'); // Load user message
                displayMessage(msg.ai, 'ai'); // Load AI response
            });
        }
    </script>
</body>

</html>
