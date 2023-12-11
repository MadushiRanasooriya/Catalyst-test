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
$options = getopt("u:p:h:", ['show_db', 'check_mytest']);

// Check if the 'show_db' option is provided
if (isset($options['show_db'])) {
    // Establish MySQL connection
    $mysqli = connectMySQL($options);

    // Show databases
    $result = $mysqli->query("SHOW DATABASES");

    if ($result) {
        echo "Databases:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Database'] . "\n";
        }
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }

    // Close the result set
    $result->close();

    // Close the MySQL connection
    $mysqli->close();
} elseif (isset($options['check_mytest'])) {
    // Establish MySQL connection
    $mysqli = connectMySQL($options);

    // Check if the 'mytest' database exists
    $result = $mysqli->query("SHOW DATABASES LIKE 'users'");

    if ($result && $result->num_rows > 0) {
        echo "Yes, 'users' database exists.\n";
    } else {
        echo "'users' database does not exist.\n";
    }

    // Close the result set
    $result->close();

    // Close the MySQL connection
    $mysqli->close();
} else {
    echo "Error: Please specify the action, e.g., --show_db or --check_mytest.\n";
    exit(1);
}
?>

