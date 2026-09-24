<!-- <!DOCTYPE html> -->
<?php
// MySQL connect information: -------------------------------------------------
$client_key  = '/opt/php/mysql_certs/client-key.pem';
$client_cert = '/opt/php/mysql_certs/client-cert.pem';
$ca_cert   = '/opt/php/mysql_certs/ca.pem';
$cert_dir = '/opt/php/mysql_certs';

$host     = '172.1.0.3';
$username = 'root';
$password = 'my-secret-pw';
$database = 'mysql';
$port     = 3306;

$dsn = "mysql:host=$host;dbname=$database;";
$options = [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        PDO::MYSQL_ATTR_SSL_KEY      => $client_key,
        PDO::MYSQL_ATTR_SSL_CERT     => $client_cert,
        PDO::MYSQL_ATTR_SSL_CA       => $ca_cert,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::MYSQL_ATTR_LOCAL_INFILE => true,
      ];
$conn = new PDO($dsn, $username, $password, $options);


$target_path;
$columnCount = 0;
$table_name_create = "";
$table_name_select = "";

//Upload a file ---------------------------------------------------------------
// Define configuration rules
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_SIZE', 2 * 1024 * 1024); // 2 Megabytes
$allowed_extensions = ['csv', 'txt'];
$message = '';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $file = $_FILES['uploaded_file'];

    // 1. Check for standard PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . $file['error'];
    } else {
        // Gather file properties
        $file_name = basename($file['name']);
        $file_size = $file['size'];
        $file_tmp  = $file['tmp_name'];
        $table_name_create = pathinfo($file_name, PATHINFO_FILENAME);
        
        // Extract extension safely
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 2. Validate file extension
        if (!in_array($file_ext, $allowed_extensions)) {
            $message = "Error: Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
        }
        // 3. Validate file size limit
        elseif ($file_size > MAX_SIZE) {
            $message = "Error: File size exceeds the 2MB limit.";
        }
        // 4. Finalize upload securely
        else {
            // Sanitize filename to prevent directory traversal or collisions
            $safe_name = preg_replace("/[^A-Za-z0-9.]/", "_", pathinfo($file_name, PATHINFO_FILENAME));
            $final_name = time() . "_" . $safe_name . "." . $file_ext;
            $target_path = UPLOAD_DIR . $final_name;

            // Move the file from temp storage to destination
            if (move_uploaded_file($file_tmp, $target_path)) {
                $message = "Success! File uploaded as: " . htmlspecialchars($final_name);
            } else {
                $message = "Error: Could not save the file. Check folder permissions.";
            }
        }
    }
}


// Create database db1
  try {
    $conn->exec("CREATE DATABASE IF NOT EXISTS db1"); //Create database db1
  }
  catch (PDOException $e) {
    die("Database Connection error: " . $e->getMessage());
  }


// Count number of columns ----------------------------------------------------
if (file_exists($target_path)) {
  $handle = fopen($target_path, 'r');
  $firstLineData = fgetcsv($handle, 0, ',');
  $columnCount = count($firstLineData);
}


// Creat a table in db1 --------------------------------------------------------
# prepare: create table statement
if (file_exists($target_path)) {
  $sql = "CREATE TABLE IF NOT EXISTS db1.`$table_name_create` (\n";
  $columns = [];
  for ($i = 1; $i <= $columnCount; $i++) {
    $columns[] = "    `col$i` VARCHAR(255) NULL";
  }
  $sql .= implode(",\n", $columns);
  $sql .= "\n) ENGINE=InnoDB;";

  try {
    $conn->exec("CREATE DATABASE IF NOT EXISTS db1"); //Create database db1
    $conn->exec($sql);                                //Create a table
  }
  catch (PDOException $e) {
    die("Database Connection error: " . $e->getMessage());
  }

  # add uploaded file to table
  $sql = "LOAD DATA LOCAL INFILE " . $conn->quote($target_path) . " "; 
  $sql .= "INTO TABLE db1.`$table_name_create` ";
  $sql .= "FIELDS TERMINATED BY ',' "; 
  $sql .= "OPTIONALLY ENCLOSED BY '\"' "; 
  $sql .= "LINES TERMINATED BY '\n' ";
  $sql .= "IGNORE 0 ROWS;";

  try {
    $conn->exec($sql);                                //Insert csv file into table
  }
  catch (PDOException $e) {
    die("Database Connection error: " . $e->getMessage());
  }

  # Add primary key
  $sql = "ALTER TABLE db1.`$table_name_create`\n";
  $sql .= "ADD COLUMN id INT AUTO_INCREMENT FIRST,\n";
  $sql .= "ADD PRIMARY KEY (id);\n";
  try {
    $conn->exec($sql);
  }
  catch (PDOException $e) {
    die("Database Connection error: " . $e->getMessage());
  }


  # delete uploaded file;
  unlink($target_path);
}


// Reading a table
if ($table_name_select !== "") { 
  try {
      $stmt = $conn->query("SELECT * FROM db1.`$table_name_select`");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);    
  } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
  }
}


# Find all tables in database db1
try {
    $stmt = $conn->query("SHOW TABLES FROM db1");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>





<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP File Upload Page</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background: #f9f9f9; }
        .container { max-width: 500px; background: #fff; padding: 10px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .msg { padding: 10px; margin-bottom: 10px; border-radius: 3px; font-weight: bold; background: #e2e2e2; }
        table {
          border-collapse: collapse;
          width: 100%;
        }

        th, td {
          border: 1px solid #000000; /* width, style, color */
          padding: 2px;              /* Spacing inside cells */
          text-align: left;
        }
        table thead tr {
          background-color: #4caf50;
          color: white;
        }
        /* Styles every even row */
        table tbody tr:nth-child(even) {
          background-color: #e6ffe6;
        }
        /* Styles every odd row (optional) */
        table tbody tr:nth-child(odd) {
          background-color:  #ffefff;
        }
    </style>
</head>
<body>

<!-- Upload a file---------------------------------------------------------- -->
<div class="container">
    <h2>Import a Dataset into a Database Table</h2>
    
    <!-- Display feedback to the user -->
    <?php if (!empty($message)): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Form configuration requires POST method and multipart/form-data enctype -->
    <form action="database.php" method="POST" enctype="multipart/form-data">
        <label for="fileSelect">Select File: csv or txt</label>
        <p><input type="file" name="uploaded_file" id="fileSelect" required></p>
        <button type="submit">Import Now</button>
    </form>
</div>


<!-- Table selection ------------------------------------------------------ -->
<form action="<?php $table_name_select =  htmlspecialchars($_POST['alltables'])  ?>" method="POST">
<label for="table-select"><h2>Choose a Dataset to Display:</h2></label>
<select name="alltables" id="table-select">
  <option value="">--Please choose an option--</option>
  <?php foreach ($tables as $table): ?>
  <?php foreach ($table as $tb): ?> 
    <?php if ($tb == htmlspecialchars($_POST['alltables'])) { ?>
    <option value="<?= htmlspecialchars($tb) ?>" selected><?= htmlspecialchars($tb) ?></option>
    <?php } else { ?>
    <option value="<?= htmlspecialchars($tb) ?>"><?= htmlspecialchars($tb) ?></option>
    <?php } ?>
  <?php endforeach; ?>
  <?php endforeach; ?>
</select>
<button type="submit">Submit</button>
</form>



<!-- Display table -------------------------------------------------------- -->
<!-- Reading a table -->
<?php
if ($table_name_select !== "") {
  try {
      $stmt = $conn->query("SELECT * FROM db1.`$table_name_select`");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
  }
}
?>

<!-- Display table -->
<?php if (!empty($rows)): ?>
  <table>
    <thead>
      <tr>
        <!-- Print column headers dynamically -->
        <?php foreach (array_keys($rows[0]) as $column): ?>
          <th><?= htmlspecialchars($column) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <!-- Print row data -->
      <?php foreach ($rows as $row): ?>
      <tr>
      <?php foreach ($row as $cell): ?>
        <td><?= htmlspecialchars($cell) ?></td>
      <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No records found.</p>
<?php endif; ?>

</body>
</html>

