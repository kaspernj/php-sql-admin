<?
	//This class (and its functions) can connect to different type of databases. It can also output 
	//identical data from each database, so that it can be used by the windows, backup-modules etc.
	//
	//It is very vital for the whole program.
	
	class DBConn{
		function OpenConn($type, $ip, $port = "", $database = "", $username = "", $password = "", WinStatus $win_status = null){
			if ($this->conn){
				$this->CloseConn();
			}
			
			$this->type_try = $type;
			
			if ($type == "mysql"){
				if ($port){
					$ip .= ":" . $port;
				}
				
				//updating the status-window.
				if ($win_status){
					$win_status->SetStatus(0, "Connecting...", true);
				}
				
				if (!$username && !$password){
					$this->conn = @mysql_connect($ip);
				}elseif($username && $password){
					$this->conn = @mysql_connect($ip, $username, $password, true);
				}
				
				//If connection is not set, return false and unset connection.
				if (!$this->conn){
					$this->lasterror = "MySQL error: " . mysql_error();
					unset($this->conn);
					return false;
				}
				
				//updating the status-window.
				if ($win_status){
					$win_status->SetStatus(0, "Selecting the database...", true);
				}
				
				//If a selection of the default database cant be made, close the connection and return false.
				if (!mysql_select_db($database, $this->conn)){
					$this->lasterror = "MySQL error: " . mysql_error();
					mysql_close($this->conn);
					unset($this->conn);
					return false;
				}
				
				$this->type = "mysql";
				
				return true;
			}elseif($type == "pgsql"){
				$this->conn = pg_connect("host=" . $ip . " port=" . $port . " dbname=" . $database . " user=" . $username . " password=" . $password);
				
				$this->pg_ip = $ip;
				$this->pg_port = $port;
				$this->pg_db = $database;
				$this->pg_user = $username;
				$this->pg_pass = $password;
				
				if (!$this->conn){
					$this->lasterror = pg_last_error();
					return false;
				}
				
				$this->type = "pgsql";
				
				return true;
			}elseif($type == "sqlite"){
				$this->conn = sqlite_open($ip);
				
				if ($this->conn){
					$this->type = "sqlite";
					return true;
				}else{
					msgbox("Warning", "The database (" . $ip . ") could not be read.");
					return false;
				}
			}elseif($type == "access"){
				if (!file_exists($ip)){
					$this->lasterror = "The file could not be found (" . $ip . ")";
					return false;
				}
				
				$odbc = "DRIVER={Microsoft Access Driver (*.mdb)};\r\n";
				$odbc .= "DBQ=" . $ip . "\r\n";
				
				$this->conn = odbc_connect($odbc, "Administrator", "");
				
				if (!$this->conn){
					return false;
				}else{
					$this->type = "access";
					return true;
				}
			}
		}
		
		function GetDBs(){
			if ($this->type == "mysql"){
				$f_gdbs = mysql_query("SHOW DATABASES", $this->conn);
				while($d_gdbs = mysql_fetch_assoc($f_gdbs)){
					if ($d_gdbs[Database] != "mysql" && $d_gdbs[Database] != "information_schema"){
						$dbs[] = $d_gdbs[Database];
					}
				}
				
				return $dbs;
			}elseif($this->type == "pgsql"){
				$f_gdbs = pg_query($this->conn, "SELECT datname FROM pg_database");
				while($d_gdbs = pg_fetch_assoc($f_gdbs)){
					$dbs[] = $d_gdbs[datname];
				}
				
				return $dbs;
			}elseif($this->type == "sqlite"){
				//You cant list databases in SQLite, since it is one database per file.
			}
		}
		
		function TruncateTable($table){
			if ($this->type == "mysql"){
				return mysql_query("TRUNCATE " . $table);
			}elseif($this->type == "pgsql"){
				
			}elseif($this->type == "sqlite"){
				
			}
		}
		
		function ChooseDB($db){
			if ($this->type == "mysql"){
				return mysql_select_db($db, $this->conn);
			}elseif($this->type == "pgsql"){
				$this->CloseConn();
				return $this->OpenConn("pgsql", $this->pg_ip, $this->pg_port, $db, $this->pg_user, $this->pg_pass);
			}elseif($this->type == "sqlite"){
				//You cant change database in SQLite, since it is one database per file.
			}
		}
		
		function CloseConn(){
			if ($this->conn){
				if ($this->type == "mysql"){
					$state = mysql_close($this->conn);
				}elseif($this->type == "pgsql"){
					$state =  pg_close($this->conn);
				}elseif($this->type == "sqlite"){
					$state =  sqlite_close($this->conn);
				}elseif($this->type == "access"){
					$state = odbc_close($this->conn);
				}
				
				$this->conn = false;
				return $state;
			}
		}
		
		function query($string){
			if ($this->type == "mysql"){
				return mysql_query($string, $this->conn);
			}elseif($this->type == "pgsql"){
				return pg_query($this->conn, $string);
			}elseif($this->type == "sqlite"){
				return sqlite_query($this->conn, $string);
			}elseif($this->type == "access"){
				return odbc_exec($this->conn, $string);
			}
		}
		
		function query_unbuffered($string){
			if ($this->type == "mysql"){
				return mysql_unbuffered_query($string, $this->conn);
			}elseif($this->type == "pgsql"){
				return pg_query($this->conn, $string);
			}elseif($this->type == "sqlite"){
				return sqlite_unbuffered_query($this->conn, $string);
			}elseif($this->type == "access"){
				return odbc_exec($this->conn, $string);
			}
		}
		
		function query_fetch_assoc($ident){
			if ($this->type == "mysql"){
				return mysql_fetch_assoc($ident);
			}elseif($this->type == "pgsql"){
				return pg_fetch_assoc($ident);
			}elseif($this->type == "sqlite"){
				$data = sqlite_fetch_array($ident);
				
				//Makes sqlite_fetch_array() works lige an assoc-function.
				if ($data){
					foreach($data AS $key => $value){
						if (is_numeric($key)){
							unset($data[$key]);
						}
					}
				}
				
				return $data;
			}elseif($this->type == "access"){
				return odbc_fetch_array($ident);
			}
		}
		
		function query_error(){
			if ($this->type == "mysql"){
				return "MySQL error: " . mysql_error($this->conn);
			}elseif($this->type == "pgsql"){
				return "PostgreSQL error: " . pg_last_error($this->conn);
			}elseif($this->type == "sqlite"){
				return "SQLite error: " . sqlite_error_string(sqlite_last_error($this->conn));
			}elseif($this->type == "access"){
				return "Access error: " . odbc_error($this->conn) . ", " . odbc_errormsg($this->conn);
			}elseif($this->lasterror){
				return $this->lasterror;
			}
		}
		
		function GetTables(WinStatus $win_status = null){
			if ($this->conn){
				if ($this->type == "mysql"){
					if ($win_status){
						$win_status->SetStatus(0, "Quering for tables.", true);
					}
					
					$f_gt = mysql_unbuffered_query("SHOW TABLE STATUS", $this->conn);
					while($d_gt = mysql_fetch_assoc($f_gt)){
						$return[] = array(
							"name" => $d_gt[Name],
							"engine" => $d_gt[Engine],
							"collation" => $d_gt[Collation],
							"rows" => $d_gt[Rows]
						);
					}
					
					return $return;
				}elseif($this->type == "pgsql"){
					$f_gt = pg_query($this->conn, "SELECT * FROM information_schema.tables WHERE table_schema = 'public'");
					while($d_gt = pg_fetch_assoc($f_gt)){
						$return[] = array(
							"name" => $d_gt[table_name],
							"engine" => "pgsql",
							"collation" => "pgsql"
						);
					}
					
					return $return;
				}elseif($this->type == "sqlite"){
					$f_gt = sqlite_query($this->conn, "SELECT name FROM sqlite_master WHERE type = 'table'") or die(sqlite_last_error($this->conn));
					while($d_gt = sqlite_fetch_array($f_gt)){
						$return[] = array(
							"name" => $d_gt[name],
							"engine" => "sqlite",
							"collation" => "sqlite"
						);
					}
					
					return $return;
				}elseif($this->type == "access"){
					$f_gt = odbc_tables($this->conn);
					while($d_gt = odbc_fetch_array($f_gt)){
						if ($d_gt[TABLE_TYPE] == "TABLE"){
							$return[] = array(
								"name" => $d_gt[TABLE_NAME],
								"engine" => "access",
								"collation" => "access"
							);
						}
					}
					
					return $return;
				}
			}
		}
		
		function GetColumns($tablename){
			if ($this->conn && $tablename){
				if ($this->type == "mysql"){
					$f_gc = mysql_unbuffered_query("SHOW COLUMNS FROM " . $tablename) or die(mysql_error($this->conn));
					while($d_gc = mysql_fetch_assoc($f_gc)){
						$value = "";
						
						if ($d_gc['Null'] == "YES"){
							$notnull = "yes";
						}else{
							$notnull = "no";
						}
						
						if ($d_gc['Key'] == "PRI"){
							$primarykey = "yes";
						}else{
							$primarykey = "no";
						}
						
						if (preg_match("/^decimal\(([0-9]+),([0-9]+)\)$/", $d_gc[Type], $match)){
							//this is a decimal-field.
							$type = "decimal";
							$maxlength = $match[1];
							$value = $match[1] . "," . $match[2];
						}elseif(preg_match("/^enum\((.+)\)$/", $d_gc[Type], $match)){
							//this is a enum-field.
							$type = "enum";
							$value = $match[1];
							$maxlength = "";
						}elseif(preg_match("/([a-zA-Z]+)\(([0-9]+)\)/", $d_gc[Type], $match)){
							$type = $match[1];
							$maxlength = $match[2];
						}else{
							$type = $d_gc['Type'];
							
							if ($d_gc['Type'] == "tinytext"){
								$maxlength = 255;
							}else{
								$maxlength = "";
							}
						}
						
						$columns[] = array(
							"name" => $d_gc['Field'],
							"notnull" => $notnull,
							"type" => $type,
							"maxlength" => $maxlength,
							"default" => $d_gc['Default'],
							"primarykey" => $primarykey,
							"value" => $value
						);
					}
				}elseif($this->type == "pgsql"){
					$f_gc = pg_query($this->conn, "SELECT * FROM information_schema.columns WHERE table_name = '$tablename' ORDER BY ordinal_position");
					while($d_gc = pg_fetch_assoc($f_gc)){
						if ($d_gc[is_nullable] == "YES"){
							$notnull = "no";
						}else{
							$notnull = "yes";
						}
						
						if ($d_gc[data_type] == "character varying"){
							$type = "varchar";
						}else{
							$type = $d_gc[data_type];
						}
						
						$primarykey = "no";
						$maxlength = "";
						
						$columns[] = array(
							"name" => $d_gc['column_name'],
							"notnull" => $notnull,
							"type" => $type,
							"maxlength" => $d_gc['character_maximum_length'],
							"default" => $d_gc['column_default'],
							"primarykey" => $primarykey
						);
					}
				}elseif($this->type == "sqlite"){
					$f_gc = sqlite_query($this->conn, "PRAGMA table_info(" . $tablename . ")") or die(sqlite_last_error($this->conn));
					while($d_gc = sqlite_fetch_array($f_gc)){
						if (!$d_gc['notnull']){
							$notnull = "no";
						}else{
							$notnull = "yes";
						}
						
						if ($d_gc['pk'] == "1"){
							$primarykey = "yes";
						}else{
							$primarykey = "no";
						}
						
						if (preg_match("/([a-zA-Z]+)\(([0-9]+)\)/", $d_gc[type], $match)){
							$type = $match[1];
							$maxlength = $match[2];
						}else{
							$type = $d_gc['type'];
							$maxlength = "";
						}
						
						$columns[] = array(
							"name" => $d_gc['name'],
							"notnull" => $notnull,
							"type" => $type,
							"maxlength" => $maxlength,
							"default" => $d_gc['dflt_value'],
							"primarykey" => $primarykey
						);
					}
				}elseif($this->type == "access"){
					$f_gc = odbc_columns($this->conn);
					while($d_gc = odbc_fetch_array($f_gc)){
						if ($d_gc[TABLE_NAME] == $tablename){
							if ($d_gc[IS_NULLABLE] == "YES"){
								$notnull = "no";
							}else{
								$notnull = "yes";
							}
							
							$columns[] = array(
								"name" => $d_gc[COLUMN_NAME],
								"type" => $d_gc[TYPE_NAME],
								"maxlength" => $d_gc[COLUMN_SIZE],
								"notnull" => $notnull
							);
						}
					}
				}
				
				//So that all types seems the same to the program.
				foreach($columns AS $key => $value){
					if (strtolower($value[type]) == "integer"){
						$columns[$key][type] = "int";
					}elseif(strtolower($value[type]) == "varchar"){
						$columns[$key][type] = "varchar";
					}elseif(strtolower($value[type]) == "text"){
						$columns[$key][type] = "text";
					}elseif(strtolower($value[type]) == "counter"){
						$columns[$key][type] = "counter";
						$columns[$key][primarykey] = "yes";
					}elseif(strtolower($value[type]) == "datetime"){
						$columns[$key][type] = "datetime";
					}
				}
				
				return $columns;
			}
		}
		
		function GetIndexes($tablename){
			if ($this->conn && $tablename){
				if ($this->type == "mysql"){
					$f_gi = mysql_query("SHOW INDEX FROM " . $tablename);
					while($d_gi = mysql_fetch_assoc($f_gi)){
						if ($d_gi[Key_name] != "PRIMARY"){
							$key = $d_gi[Key_name];
							
							$index[$key][name] = $d_gi[Key_name];
							$index[$key][columns][] = $d_gi[Column_name];
							
							if ($index[$key][columns_text]){
								$index[$key][columns_text] .= ", ";
							}
							
							$index[$key][columns_text] .= $d_gi[Column_name];
						}
					}
					
					//Making keys to numbers (as in SQLite).
					if ($index){
						foreach($index AS $key => $value){
							$return[] = $value;
						}
					}
					
					return $return;
				}elseif($this->type == "pgsql"){
					//Extraction index (fuck you very much PostgreSQL)
					//Read this link for documentation: http://www.postgresql.org/docs/7.4/static/catalog-pg-index.html
					
					$f_gi = pg_query($this->conn, "
						SELECT
							table_data.relname AS table_name,
							index_data.relname AS index_name,
							pg_index.indkey AS column_numbers
						
						FROM
							pg_index
						
						LEFT JOIN pg_class AS index_data ON
							index_data.oid = pg_index.indexrelid
						
						LEFT JOIN pg_class AS table_data ON
							table_data.oid = pg_index.indrelid
						
						WHERE
							table_data.relname = '$tablename'
					");
    				while($d_gi = pg_fetch_assoc($f_gi)){
    					$column_numbers = explode(" ", $d_gi[column_numbers]);
    					foreach($column_numbers AS $value){
    						$cn[$value] = true;
    					}
    					
    					$columns = array();
    					
    					$count = 0;
    					$f_gc = pg_query($this->conn, "SELECT column_name FROM information_schema.columns WHERE table_name = '$tablename' ORDER BY ordinal_position");
    					while($d_gc = pg_fetch_Assoc($f_gc)){
    						$count++;
    						
    						if ($cn[$count]){
    							$columns[] = $d_gc[column_name];
    						}
    					}
    					
    					$return[] = array(
    						"name" => $d_gi[index_name],
    						"columns" => $columns,
    						"columns_text" => implode(", ", $columns)
    					);
    				}
    				
    				return $return;
				}elseif($this->type == "sqlite"){
					$f_gi = sqlite_query($this->conn, "PRAGMA index_list(" . $tablename . ")");
					while($d_gi = sqlite_fetch_array($f_gi)){
						if (strpos($d_gi[name], $tablename . " autoindex") === false){
							$index = array();
							$index[name] = $d_gi[name];
							
							$first = true;
							$columns_text = "";
							
							$f_gid = sqlite_query($this->conn, "PRAGMA index_info('" . $d_gi['name'] . "')");
							while($d_gid = sqlite_fetch_array($f_gid)){
								if ($first == true){
									$first = false;
								}else{
									$columns_text .= ", ";
								}
								
								$columns_text .= $d_gid[name];
								$index[columns][] = $d_gid[name];
							}
							
							$index[columns_text] = $columns_text;
							
							$return[] = $index;
						}
					}
					
					return $return;
				}elseif($this->type == "access"){
					//Thanks for making it impossible to read indexes (even just to read them) without manually
					//editting it through Microsoft Access. Way to go fucking Microsoft.
					
					return false;
				}
			}
		}
		
		function AddIndex($tablename, $columns, $title = false){
			if (!$title){
				$title = implode("_", $columns);
			}
			
			$index[name] = &$title;
			$index[columns] = &$columns;
			
			$sql = makesql_index($this->type, $tablename, $index);
			
			if ($this->query($sql)){
				return true;
			}else{
				return false;
			}
		}
		
		function AddIndexFromGet($tablename, $indexes){
			if ($indexes){
				foreach($indexes AS $index){
					if (!$this->AddIndex($tablename, $index[columns], $index[name])){
						return false;
					}
				}
			}
			
			return true;
		}
		
		function DropIndex($tablename, $indexname){
			if ($this->type == "mysql"){
				return mysql_query("DROP INDEX `" . $indexname . "` ON `" . $tablename . "`");
			}elseif($this->type == "pgsql"){
				return pg_query("DROP INDEX `" . $indexname . "`");
			}elseif($this->type == "sqlite"){
				if (!sqlite_query($this->conn, "DROP INDEX '" . $indexname . "'")){
					return false;
				}
				
				if (!sqlite_query($this->conn, "VACUUM " . $tablename)){
					return false;
				}
				
				return true;
			}
		}
		
		function RenameTable($oldtable, $newtable){
			$oldtable = trim($oldtable);
			$newtable = trim($newtable);
			
			if ($oldtable == $newtable){
				return false;
			}
			
			if ($this->type == "mysql" || $this->type == "pgsql"){
				$sql = makesql_rename($this->type, $oldtable, $newtable);
				return $this->query($sql);
			}elseif($this->type == "sqlite" || $this->type == "access"){
				//Fuck you very much SQLite. This is just pure pain... No "ALTER TABLE" :'(
				//Generating SQL for the table and replaces it with a new name.
				$columns = $this->GetColumns($oldtable);
				$sql_new = makesql_table($this->type, $newtable, $columns);
				
				//This line is used for re-creating the possible indexes the table could have had.
				$indexes = $this->GetIndexes($oldtable);
				
				//Executing the creating of the new table.
				if (!$this->query($sql_new)){
					return false;
				}
				
				//Inserting the old data.
				if (!$this->query("INSERT INTO " . $newtable . " SELECT * FROM " . $oldtable)){
					return false;
				}
				
				//Recreating indexes for the new table.
				$this->AddIndexFromGet($newtable, $indexes);
				
				//Dropping the old table.
				if (!$this->query("DROP TABLE " . $oldtable)){
					return false;
				}
				
				return true;
			}
		}
		
		function AddColumns($table, $columns, $oldcolumns = false){
			if ($this->type == "mysql" || $this->type == "pgsql"){
				$sql = makesql_addcolumns($this->type, $table, $columns, $oldcolumns);
				return $this->query($sql);
			}elseif($this->type == "sqlite"){
				//Again again... SQLite does not have a alter table... Fucking crap.
				//Starting by creating a name for the temp-table.
				$tempname = $table . "_temp";
				
				//Editing the index-array for renamed columns.
				$indexes = $this->GetIndexes($table);
				
				//Making SQL.
				$oldcolumns = $this->GetColumns($table);
				$actual_columns = array_merge($oldcolumns, $columns);
				$sql = makesql_table($this->type, $table, $actual_columns);
				
				//Renaming the table to the temp-name.
				if (!$this->RenameTable($table, $tempname)){
					return false;
				}
				
				//Creating the new table.
				if (!$this->query($sql)){
					return false;
				}
				
				//If we are adding columns, the new columns are at their defaults, so we just have to add the old data.
				//Making SQL for insert into new table.
				$sql_insert = "INSERT INTO " . $table . " (";
				
				//Creating the fields that should be insertet into for the SQL.
				$first = true;
				foreach($oldcolumns AS $column){
					if ($first == true){
						$first = false;
					}else{
						$sql_insert .= ", ";
					}
					
					$sql_insert .= $column['name'];
				}
				
				//If a new column has set "notnull" to be true, then we MUST insert into it (thanks evil devil).
				foreach($columns AS $column){
					if ($column['notnull'] && !$column['default']){
						$sql_insert .= ", " . $column['name'];
					}
				}
				
				$sql_insert .= ") SELECT ";
				
				$first = true;
				foreach($oldcolumns AS $column){
					if ($first == true){
						$first = false;
					}else{
						$sql_insert .= ", ";
					}
					
					$sql_insert .= $column[name];
				}
				
				//If a new column has set "notnull" to be true, then we MUST insert into it (thanks evil devil). So 
				//we are just emulating an empty string, which will be insertet.
				foreach($columns AS $column){
					if ($column[notnull] && !$column['default']){
						$sql_insert .= ", '' AS " . $column[name];
					}
				}
				
				$sql_insert .= " FROM " . $tempname;
				
				//Execute the insert-SQL.
				if (!$this->query($sql_insert)){
					return false;
				}
				
				//Drop the tempoary table.
				if (!$this->query("DROP TABLE " . $tempname)){
					return false;
				}
				
				if (!$this->AddIndexFromGet($table, $indexes)){
					return false;
				}
				
				return true;
			}
		}
		
		function EditColumns($table, $oldcolumns, $newcolumns){
			if ($this->type == "mysql"){
				$sql = makesql_editcolumns($this->type, $table, $oldcolumns, $newcolumns);
				
				//It will return false, if nothing is changed.
				if ($sql){
					if (!$this->query($sql)){
						return false;
					}
				}
				
				return true;
			}elseif($this->type == "sqlite"){
				//Setting the temp-name for a temp-table.
				$tempname = $table . "_temp";
				
				//Getting the indexes for later use.
				$indexes = $this->GetIndexes($table);
				
				//Rename the current table to the temp-name.
				if (!$this->RenameTable($table, $tempname)){
					return false;
				}
				
				//Makinig SQL for creating the new table with updated columns and executes it.
				$sql_createtable = makesql_CreateTable($table, $newcolumns, $this->type);
				if (!$this->query($sql_createtable)){
					return false;
				}
				
				//Making SQL for inserting into it from the temp-table.
				$sql_insert = "INSERT INTO '" . $table . "' (";
				$sql_select = "SELECT ";
				
				$first = true;
				foreach($oldcolumns AS $key => $value){
					if ($first == true){
						$first = false;
					}else{
						$sql_insert .= ", ";
						$sql_select .= ", ";
					}
					
					$sql_insert .= $newcolumns[$key][name];
					$sql_select .= $value[name] . " AS " . $value[name];
				}
				
				$sql_select .= " FROM " . $tempname;
				$sql_insert .= ") " . $sql_select;
				
				if (!$this->query($sql_insert)){
					return false;
				}
				
				//Dropping the temp-table. This must be done before re-creating the indexes. If not we will 
				//try to create a index, with a index-id which already exists (we will therefore fail).
				if (!$this->query("DROP TABLE " . $tempname)){
					return false;
				}
				
				//Creating indexes again from the array, that we saved at the beginning. In short terms this will 
				//rename the columns which have indexes to the new names, so that they wont be removed.
				if ($indexes){
					foreach($indexes AS $index_key => $index){
						foreach($index[columns] AS $column_key => $column){
							foreach($oldcolumns AS $ocolumn_key => $ocolumn){
								if ($column == $ocolumn[name]){
									//Updating index-array.
									$indexes[$index_key][columns][$column_key] = $newcolumns[$ocolumn_key][name];
								}
							}
						}
					}
				}
				
				if (!$this->AddIndexFromGet($table, $indexes)){
					return false;
				}
				
				return true;
			}
		}
		
		function RemoveColumn($table, $column, $oldcolumns = false){
			if ($this->type == "mysql" || $this->type == "pgsql"){
				return $this->query("ALTER TABLE " . $table . " DROP COLUMN " . $column, $this->conn);
			}elseif($this->type == "sqlite"){
				//Again... SQLite has no "ALTER TABLE".
				$columns = $this->GetColumns($table);
				$indexes = $this->GetIndexes($table);
				$tempname = $table . "_temp";
				
				if (!$this->RenameTable($table, $tempname)){
					return false;
				}
				
				//Removing the specifik removing column from the array.
				foreach($columns AS $key => $value){
					if ($value[name] == $column){
						unset($columns[$key]);
						break;
					}
				}
				
				$sql = makesql_CreateTable($table, $columns, $this->type);
				$sql_insert = "INSERT INTO " . $table . " SELECT ";
				
				$first = true;
				foreach($columns AS $value){
					if ($value['name'] != $column){
						if ($first == true){
							$first = false;
						}else{
							$sql_insert .= ", ";
						}
						
						$sql_insert .= $value['name'];
					}
				}
				
				$sql_insert .= " FROM " . $table . "_temp";
				
				if (!$this->query($sql)){
					return false;
				}
				
				if (!$this->query($sql_insert)){
					return false;
				}
				
				if (!$this->query("DROP TABLE " . $table . "_temp")){
					return false;
				}
				
				if (!$this->AddIndexFromGet($table, $indexes)){
					return false;
				}
				
				return true;
			}
		}
	}
	
	//Makes it MUCH more easy to send queries.
	function query($string){
		global $win_main;
		return $win_main->dbconn->query($string);
	}
	
	function query_unbuffered($string){
		global $win_main;
		return $win_main->dbconn->query_unbuffered($string);
	}
	
	function query_fetch_assoc($ident){
		global $win_main;
		return $win_main->dbconn->query_fetch_assoc($ident);
	}
	
	function query_error(){
		global $win_main;
		return $win_main->dbconn->query_error();
	}
?>