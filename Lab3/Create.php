<!DOCTYPE html>
<html>
<head>
    <title>Create Database and Table</title>
</head>
<body>

<?php

function generateSalt($length = 16) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password, $salt) {
    return hash('sha256', $password . $salt);
}

function verifyPassword($password, $salt, $hash) {
    $calculatedHash = hashPassword($password, $salt);
    return hash_equals($hash, $calculatedHash);
}

if (isset($_GET['create_db'])) {
    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = ""; 

    // Create connection
    $conn = new mysqli($servername, $username, $password);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL to create database
    $dbName = "MyDatabase";
    $sql = "CREATE DATABASE IF NOT EXISTS $dbName";
    if ($conn->query($sql) === TRUE) {
        echo "Database '$dbName' created successfully.<br>";
    } else {
        die("Error creating database: " . $conn->error);
    }

    // Select the database
    $conn->select_db($dbName);

    // SQL to create table
    $tableSql = "CREATE TABLE IF NOT EXISTS Users (
        User_ID INT AUTO_INCREMENT PRIMARY KEY,
        Username VARCHAR(50) NOT NULL,
        Salt VARCHAR(50) NOT NULL,
        Password VARCHAR(255) NOT NULL
    )";

    if ($conn->query($tableSql) === TRUE) {
        echo "Table 'Users' created successfully.";
    } else {
        die("Error creating table: " . $conn->error);
    }

	// Insert dummy records into the Users table
    $salt1 = generateSalt();
    $salt2 = generateSalt();
    $salt3 = generateSalt();

    $sqlInsertDummyData = "
        INSERT IGNORE INTO Users (Username, Salt, Password)
        VALUES 
            ('testuser1', '$salt1', '" . hashPassword('password1', $salt1) . "'),
            ('testuser2', '$salt2', '" . hashPassword('password2', $salt2) . "'),
            ('testuser3', '$salt3', '" . hashPassword('password3', $salt3) . "')
    ";
	if ($conn->query($sqlInsertDummyData) === TRUE) {
		echo "<p>Dummy records inserted successfully.</p>";
	} else {
		echo "<p>Error inserting dummy records: " . $conn->error . "</p>";
	}
	
    // Close the connection
    $conn->close();
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database connection details
    $servername = "localhost";
    $dbUsername = "jack";
    $dbPassword = "jack"; // Replace with your MySQL password if needed
    $dbName = "MyDatabase";

    // Create connection
    $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get user input
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prevent SQL injection
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);

    if (isset($_POST['login'])) {
        // Query to check for matching username and password
        $sql = "SELECT * FROM Users WHERE Username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Fetch the user data
            $row = $result->fetch_assoc();

            // Verify the password (assuming passwords are stored as plain text; 
            $salt = $row['Salt'];
            $hashedPassword = $row['Password'];

            if (verifyPassword($password, $salt, $hashedPassword)) {
                echo "<p>Login successful! Welcome, " . htmlspecialchars($row['Username']) . ".</p>";
            } else {
                echo "<p>Invalid password. Please try again.</p>";
            }
        } else {
            echo "<p>Invalid username. Please try again.</p>";
        }
    }

    if (isset($_POST['register'])) {
        // Check if the username is already taken
        $checkExistingUser = "SELECT * FROM Users WHERE Username = '$username'";
        $result = $conn->query($checkExistingUser);

        if ($result->num_rows > 0) {
            echo "<p>Username already taken. Please choose another one.</p>";
        } else {
            // Generate a random salt
            $salt = generateSalt();

            // Hash the password
            $hashedPassword = hashPassword($password, $salt);

            // Insert the new user record
            $sql = "INSERT INTO Users (Username, Salt, Password) VALUES ('$username', '$salt', '$hashedPassword')";

            if ($conn->query($sql) === TRUE) {
                echo "<p>Registration successful! Welcome, " . htmlspecialchars($username) . ".</p>";
            } else {
                echo "<p>Error: " . $sql . "<br>" . $conn->error . "</p>";
            }
        }
    }

    // Close the connection
    $conn->close();
}
?>


<!-- HTML Form -->
<form method="GET">
    <button type="submit" name="create_db">Create Database and Table</button>
</form>


<!-- HTML Form -->
<h1>Login Here</h1>
<form method="post">
    <label for="username">Username:</label><br>
    <input type="text" id="username" name="username" required><br><br>

    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br><br>

    <button type="submit" name="login">Login</button>
</form>

<br><br>

<h1>Register Here</h1>
<form method="post">
    <label for="username">Username:</label><br>
    <input type="text" id="username" name="username" required><br><br>

    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br><br>

    <button type="submit" name="register">Register</button>
</form>

</body>
</html>