<?php



// Parse command line arguments
$options = getopt("u:p:h:", ['create_table', 'file:', 'dry_run', 'help']);

if (isset($options['help'])) {
	if (isset($options['create_table']) || isset($options['file']) || isset($options['dry_run']) || isset($options['u']) || isset($options['p']) || isset($options['h'])) {
		echo "INCORRECT COMMAND\nUsage: php script.php --help\n";
	} else {
		showHelp();
	}
} elseif (isset($options['create_table'])) {
	if (isset($options['file']) || isset($options['dry_run'])) {
		echo "INCORRECT COMMAND\nUsage: php script.php --create_table -u [MySQL username] -p [MySQL password] -h [MySQL host] \n";
	} else {
		// Establish MySQL connection
    		$mysqli = connectMySQL($options);

    		createTable($mysqli);

    		// Close the MySQL connection
    		$mysqli->close();
	}
} elseif (isset($options['file'])) {
	if (isset($options['dry_run'])) {
		echo "Dry run mode.\n";
		handleFile($options, true);
		echo "MySQL table is not updated.\n";
	}else{
		handleFile($options, false);
	}
} else {
    echo "Error: Please specify a valid action, e.g., --create_table, --file[csv file], --help.\n";
    exit(1);
}

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

// Function to display help information
function showHelp() {
    echo "Usage:\n";
    echo "php script.php --file [csv file name] -u [MySQL username] -p [MySQL password] -h [MySQL host]\n";
    echo "php script.php --create_table -u [MySQL username] -p [MySQL password] -h [MySQL host]\n";
    echo "php script.php --dry_run --file [csv file name] -u [MySQL username] -p [MySQL password] -h [MySQL host]\n";
    echo "php script.php --help\n";
    echo "\nOptions:\n";
    echo "--file [csv file name]    Name of the CSV to be parsed\n";
    echo "--create_table           Build the MySQL users table (no further action will be taken)\n";
    echo "--dry_run                Run the script without inserting into the DB (other functions will be executed)\n";
    echo "-u                       MySQL username\n";
    echo "-p                       MySQL password\n";
    echo "-h                       MySQL host\n";
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
        echo "Error creating 'users' table: " . $mysqli->error . "\n";
    }
}

// Function to handle the file action
function handleFile($options, $is_dry_run) {
    // Get the file name from the command line
    $csvFileName = $options['file'];

    // Check if the file exists
    if (!file_exists($csvFileName)) {
        echo "Error: File does not exist.\n";
        exit(1);
    }

    // Check if the file contains the expected columns
    $csvFile = fopen($csvFileName, 'r');
    $header = fgetcsv($csvFile);

    // Check if the header is in the correct format
    $expectedHeader = ['name', 'surname', 'email'];
    $headerMatches = count($header) === count($expectedHeader) && array_map('strtolower', array_map('trim', $header)) === $expectedHeader;

    if ($header === false || !$headerMatches) {
        echo "File is not in the correct format. It should contain columns: name, surname, email.\n";
    } else {
        echo "File is in the correct format. Inserting rows:\n";

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
                    echo "Skipped inserting row $rowNumber: Duplicate email.\n";
                }
            } else {
                echo "Error: Invalid email at row $rowNumber. Skipping the row.\n";
            }

            // Increment row number
            $rowNumber++;
        }
        // Close the checkDuplicate statement
        $checkDuplicate->close();
    }

    // Close the file
    fclose($csvFile);
}
?>

