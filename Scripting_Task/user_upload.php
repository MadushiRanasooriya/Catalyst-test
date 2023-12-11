<?php
// Function to establish MySQL connection
function connectMySQL($options) {
    // Check if the 'u', 'p', and 'h' options are provided
    if (!isset($options['u']) || !isset($options['p']) || !isset($options['h'])) {
        echo "Error: Please provide username (-u), password (-p), and host (-h) options.\n";
        exit(1);
    }

    // Get the connection details
    $username = $options['u'];
    $password = $options['p'];
    $host = $options['h'];

    // Connect to MySQL
    $mysqli = new mysqli($host, $username, $password);

    // Check the connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    return $mysqli;
}

// Parse command line arguments
$options = getopt("u:p:h:", ['create_table']);

if (isset($options['create_table'])) {
    // Establish MySQL connection
    $mysqli = connectMySQL($options);

    // Check if the 'user_details' database exists
    $result = $mysqli->query("SHOW DATABASES LIKE 'user_details'");

    if ($result && $result->num_rows < 0) {
        $mysqli->query("CREATE DATABASE IF NOT EXISTS user_details");
        echo "'user_details' database created.\n";
    }

    // Select 'user_details' database
    $mysqli->select_db("user_details");

    // Check if the 'users' table exists
    $tableCheck = $mysqli->query("SHOW TABLES LIKE 'users'");

    if ($tableCheck && $tableCheck->num_rows > 0) {
        // 'users' table exists, drop it
        $mysqli->query("DROP TABLE users");
    }

    // Create 'users' table
    $createTableQuery = "CREATE TABLE users (
    	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    	name VARCHAR(30) NOT NULL,
    	surname VARCHAR(30) NOT NULL,
    	email VARCHAR(50) NOT NULL UNIQUE
	);";

    if ($mysqli->query($createTableQuery)) {
        echo "'users' table created.\n";
    } else {
        echo "Error creating 'users' table: " . $mysqli->error . "\n";
    }

    // Close the MySQL connection
    $mysqli->close();
    
} else {
    echo "Error: Please specify a valid action, e.g., --create_table.\n";
    exit(1);
}
?>

