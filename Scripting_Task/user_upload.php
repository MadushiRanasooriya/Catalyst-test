<?php

// Parse command line arguments
$options = getopt("u:p:h:", ['create_table', 'file:', 'dry_run', 'help']);


if (isset($options['help'])) {
	if (isset($options['create_table']) || isset($options['file']) || isset($options['dry_run']) || isset($options['u']) || isset($options['p']) || isset($options['h'])) {
		echo "INCORRECT COMMAND\nUsage: php user_upload.php --help\n\n";
	} else {
		showHelp();
	}
} elseif (isset($options['create_table'])) {
	if (isset($options['file']) || isset($options['dry_run'])) {
		echo "INCORRECT COMMAND\nUsage: php user_upload.php --create_table -u [MySQL username] -p [MySQL password] -h [MySQL host] \n\n";
	} else {
		// Establish MySQL connection
    		$mysqli = connectMySQL($options);

    		createTable($mysqli);

    		// Close the MySQL connection
    		$mysqli->close();
	}
} elseif (isset($options['file'])) {
	if (isset($options['dry_run'])) {
		echo "DRY RUN MODE.\n";
		handleFile($options, true);
		echo "MySQL table is not updated.\n";
	}else{
		handleFile($options, false);
	}
} else {
    echo "ERROR: Please specify a valid action, e.g., --create_table, --file[csv file], --dry_run, --help.\n\n";
    exit(1);
}

// Function to establish MySQL connection
function connectMySQL($options) {
    // Check if the 'u', 'p', and 'h' options are provided
    if (!isset($options['u']) || !isset($options['p']) || !isset($options['h'])) {
        echo "ERROR: Please provide username (-u), password (-p), and host (-h) options.\n\n";
        exit(1);
    }

    // Get the connection details
    $username = $options['u'];
    $password = $options['p'];
    $host = $options['h'];
    
    echo "Starting MySQL connection.....\n";

    try {
        // Connect to MySQL
        $mysqli = new mysqli($host, $username, $password);

        // Check the connection
        if ($mysqli->connect_error) {
            throw new mysqli_sql_exception("Connection failed: " . $mysqli->connect_error);
        }

        echo "Connected to MySQL.\n";
        return $mysqli;
    } catch (mysqli_sql_exception $e) {
        // Handle the exception (e.g., log the error, display a message, etc.)
        echo "ERROR: Unable to connect to MySQL. " . $e->getMessage() . "\n\n";
        exit(1);
    }

}

// Function to display help information
function showHelp() {
    echo "\nOptions:\n";
    echo "--file [csv file name]\tName of the CSV to be parsed\n";
    echo "--create_table\t\tBuild the MySQL users table (no further action will be taken)\n";
    echo "--dry_run\t\tRun the script without inserting into the DB (other functions will be executed)\n";
    echo "-u\t\t\tMySQL username\n";
    echo "-p\t\t\tMySQL password\n";
    echo "-h\t\t\tMySQL host\n";
    echo "\nUsage:\n";
    echo "php user_upload.php --file [csv file name] -u [MySQL username] -p [MySQL password] -h [MySQL host]\n";
    echo "php user_upload.php --create_table -u [MySQL username] -p [MySQL password] -h [MySQL host]\n";
    echo "php user_upload.php --dry_run --file [csv file name] -u [MySQL username] -p [MySQL password] -h [MySQL host]\n";
    echo "php user_upload.php --help\n\n";
    
}


// Function to handle the create_table action
function createTable($mysqli) {
    // Check if the 'user_details' database exists
    $result = $mysqli->query("SHOW DATABASES LIKE 'user_details'");

    // Create 'user_details' database if it doesn't exist
    $mysqli->query("CREATE DATABASE IF NOT EXISTS user_details");

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
        echo "ERROR: Creating 'users' table unsuccessful\n\n";
    }
}

// Function to handle the file action
function handleFile($options, $is_dry_run) {
    // Get the file name from the command line
    $csvFileName = $options['file'];

    // Check if the file exists
    if (!file_exists($csvFileName)) {
        echo "ERROR: File does not exist.\n\n";
        exit(1);
    }

    // Check if the file contains the expected columns
    $csvFile = fopen($csvFileName, 'r');
    $header = fgetcsv($csvFile);

    // Check if the header is in the correct format
    $expectedHeader = ['name', 'surname', 'email'];
    $headerMatches = count($header) === count($expectedHeader) && array_map('strtolower', array_map('trim', $header)) === $expectedHeader;

    if ($header === false || !$headerMatches) {
        echo "ERROR: File is not in the correct format.\nIt should contain columns: name, surname, email.\n\n";
    } else {
        echo "File is in the correct format.\n";
        if($is_dry_run==false){
        	echo "Start reading & inserting user data.......\n";
        }
        else{
        	echo "Start reading user data.......\n";
        }

        // Establish MySQL connection
        $mysqli = connectMySQL($options);
        // Select 'user_details' database
        $mysqli->select_db("user_details");
        // Prepare the insert statement
        $insertStatement = $mysqli->prepare("INSERT INTO users (name, surname, email) VALUES (?, ?, ?)");
        // Initialize row number
        $rowNumber = 1;

        // Iterate through each row in the CSV file
        while (($row = fgetcsv($csvFile)) !== false) {
            $row[0] = ucfirst(strtolower(trim($row[0]))); // Capitalize 'name'
            $row[1] = ucfirst(strtolower(trim($row[1]))); // Capitalize 'surname'

            $row[2] = strtolower(trim($row[2]));// Convert email to lowercase

            // Check if the email already exists in the database
            $checkDuplicate = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
            $checkDuplicate->bind_param('s', $row[2]);
            $checkDuplicate->execute();
            $checkDuplicate->store_result();

            // Validate the email address
            if (filter_var($row[2], FILTER_VALIDATE_EMAIL)) {
                if ($checkDuplicate->num_rows == 0) {
                	if($is_dry_run==false){
		            $insertStatement->bind_param('sss', $row[0], $row[1], $row[2]);
		            $insertStatement->execute();
		            
		        }
		        
                } else {
                    echo "WARNING: Duplicate email found. Skipped inserting row $rowNumber.\n";
                }
            } else {
                echo "WARNING: Invalid email found. Skipped inserting row $rowNumber.\n";
            }

            // Increment row number
            $rowNumber++;
        }
        // Close the checkDuplicate statement
        $checkDuplicate->close();
    }
    if($is_dry_run==false){
	echo "Finished inserting user data.\n\n";
    }
    else{
    	echo "Finished reading user data.\n\n";
    }

    // Close the file
    fclose($csvFile);
}
?>

