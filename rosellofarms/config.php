<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration for XAMPP
$host = "localhost";
$username = "root";          // Default XAMPP MySQL username
$password = "";              // Default XAMPP MySQL password (empty)
$database = "rosellofarms";  // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br><br>Please check:<br>1. MySQL is running in XAMPP<br>2. Database 'rosellofarms' exists<br>3. Database credentials are correct");
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user type
function getUserType() {
    return isset($_SESSION['user_type_id']) ? $_SESSION['user_type_id'] : null;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (getUserType() != 3) { // Admin user type
        header("Location: customer_dashboard.php");
        exit();
    }
}

// Function to redirect if not employee
function requireEmployee() {
    requireLogin();
    if (getUserType() != 2) { // Employee user type
        header("Location: customer_dashboard.php");
        exit();
    }
}
?>