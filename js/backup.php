<?php
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'enrollment';

if (isset($_GET['action'])) {
	if ($_GET['action'] == 'backup') {

		function backup_tables($host, $user, $pass, $name, $tables = '*')
		{
			$link = mysqli_connect($host, $user, $pass, $name);
			mysqli_set_charset($link, "utf8");

			if (!$link) {
				echo "Error: Unable to connect to MySQL.". PHP_EOL;
				echo "Debugging errno: ". mysqli_connect_errno(). PHP_EOL;
				echo "Debugging error: ". mysqli_connect_error(). PHP_EOL;
				exit;
			}

			if ($tables == '*') {
				$tables = array();
				$result = mysqli_query($link, 'SHOW TABLES');
				while ($row = mysqli_fetch_row($result)) {
					$tables[] = $row[0];
				}
			} else {
				$tables = is_array($tables)? $tables : explode(',', $tables);
			}

			$return = '';
			foreach ($tables as $table) {
				$result = mysqli_query($link, 'SELECT * FROM '. $table);
				$num_fields = mysqli_num_fields($result);

				$return.= 'DROP TABLE IF EXISTS '. $table. ';';
				$row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE '. $table));
				$return.= "\n\n". $row2[1]. ";\n\n";

				for ($i = 0; $i < $num_fields; $i++) {
					while ($row = mysqli_fetch_row($result)) {
						$return.= 'INSERT INTO '. $table. ' VALUES(';
						for ($j = 0; $j < $num_fields; $j++) {
							$row[$j] = addslashes($row[$j]);
							$row[$j] = str_replace("\n", "\\n", $row[$j]);
							$return.= isset($row[$j])? '"'. $row[$j]. '"' : 'NULL';
							$return.= $j < ($num_fields - 1)? ',' : '';
						}
						$return.= ");\n";
					}
				}
				$return.= "\n\n\n";
			}

			//save file
			$handle = fopen('databases/enrollment-backup_'. date('m-d-Y'). '_'. time(). '.sql', 'w+');
			$dbnameSQL = 'enrollment-backup_'. date('m-d-Y'). '_'. time(). '.sql';

			$sqlINSERT = mysqli_query($link, "INSERT INTO `db` (`db_name`, `date_added`) VALUES ('$dbnameSQL', NOW())");
			if ($sqlINSERT) {
				header("Location:page.php?page=backup_restore&result=success");
			} else {
				header("Location:page.php?page=backup_restore&result=failed");
			}

			fwrite($handle, $return);
			fclose($handle);
		}

		backup_tables($dbhost, $dbuser, $dbpass, $dbname);

		// RESTORE DATABASE
	} else {

		ini_set('memory_limit', '8192M'); // set memory limit here
		$db = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die('not connected');

		$name = $_GET['name'];

		$fp = fopen("databases/$name", 'r');
		$fetchData = fread($fp, filesize("databases/$name"));
		$sqlInfo = explode(";\n", $fetchData); // explode dump sql as a array data

		foreach ($sqlInfo AS $sqlData) {
			mysqli_query($db, $sqlData) or die('Query not executed');

			header("Location:page.php?page=backup_restore&result=restore_success");
		}

		echo 'Done';
	}
}
?>