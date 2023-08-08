<?
	//The file contains functions for making SQL of the output given by a DBConn-object.
	
	function makesql_sep($dbtype){
		if ($dbtype == "mysql"){
			return "`";
		}elseif($dbtype == "pgsql"){
			return "";
		}elseif($dbtype == "sqlite"){
			return "'";
		}elseif($dbtype == "access"){
			return "`";
		}else{
			return "`";
		}
	}
	
	function makesql_index($dbtype, $tha_table, $index){
		$sep = makesql_sep($dbtype);
		
		return "CREATE INDEX " . $sep . $index[name] . $sep . " ON " . $sep . $tha_table . $sep . " (" . implode(", ", $index[columns]) . ");\n";
	}
	
	function makesql_table($dbtype, $tha_table, $columns){
		$sep = makesql_sep($dbtype);
		$sql = "CREATE TABLE " . $sep . $tha_table . $sep . " (";
		
		$first = true;
		foreach($columns AS $tha_column){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}
			
			$sql .= makesql_ColumnSpec($dbtype, $tha_column);
		}
		
		$sql .= ");\n";
		
		return $sql;
	}
	
	function makesql_rename($dbtype, $oldtable, $newtable){
		$sep = makesql_sep($dbtype);
		
		if ($dbtype == "mysql"){
			return "ALTER TABLE " . $sep . $oldtable . $sep . " RENAME TO " . $sep . $newtable . $sep;
		}elseif($dbtype == "pgsql"){
			return "ALTER TABLE " . $oldtable . " RENAME TO " . $newtable;
		}
	}
	
	function makesql_parsequotes($dbtype, $string){
		if ($dbtype == "access"){
			$string = str_replace("'", "''", $string);
			$string = str_replace("\r\n", "' & CHR(10) & CHR(13) & '", $string);
			$string = str_replace("\r", "' & CHR(10) & '", $string);
			$string = str_replace("\n", "' & CHR(13) & '", $string);
		}elseif($dbtype == "sqlite"){
			$string = str_replace("'", "''", $string);
			$string = str_replace("\\", "\\\\", $string);
			$string = str_replace("\r", "\\r", $string);
			$string = str_replace("\n", "\\n", $string);
			
			if (substr($string, -1, 1) == "\\" && substr($string, -2, 2) !== "\\\\"){
				$string = substr($string, 0, -1) . "\\\\";
			}
		}else{
			$string = str_replace("\\", "\\\\", $string);
			$string = str_replace("'", "\'", $string);
			$string = str_replace("\r", "\\r", $string);
			$string = str_replace("\n", "\\n", $string);
			
			if (substr($string, -1, 1) == "\\" && substr($string, -2, 2) !== "\\\\"){
				$string = substr($string, 0, -1) . "\\\\";
			}
		}
		
		return $string;
	}
	
	function makesql_insert($dbtype, $table, $data, $columns = false){
		$sep = makesql_sep($dbtype);
		
		if ($dbtype == "access"){
			if (!$columns){
				echo "Columns are needed to do makesql_insert() when using access...\n";
				return false;
			}
			
			foreach($columns AS $key => $value){
				$columns_info[$value[name]] = $value[type];
			}
		}
		
		$sql = "INSERT INTO " . $sep . $table . $sep . " (";
		
		$first = true;
		foreach($data AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}
			
			$sql .= $sep . $key . $sep;
		}
		
		$sql .= ") VALUES (";
		
		$first = true;
		foreach($data AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}
			
			if ($dbtype == "access" && $columns_info[$key] == "int" && is_numeric($value)){
				$sql .= $value;
			}else{
				$sql .= "'" . makesql_parsequotes($dbtype, $value) . "'";
			}
		}
		
		$sql .= ");\n";
		
		return $sql;
	}
	
	function makesql_delete($dbtype, $tablename, $columnwhere, $columns = false){
		if ($dbtype == "access" && !$columns){
			echo "makesql_delete() need the columns-argument, if the dbtype is Access.\n";
			return false;
		}
		
		$sep = makesql_sep($dbtype);
		$sql = "DELETE FROM " . $sep . $tablename . $sep . " WHERE ";
		
		$first = true;
		foreach($columnwhere AS $column_name => $column_value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= " AND ";
			}
			
			$sql .= $sep . $column_name . $sep . " = ";
			
			if ($dbtype == "access"){
				$tha_column = null;
				foreach($columns AS $column){
					if ($column[name] == $column_name){
						$tha_column = &$column;
						break;
					}
				}
				
				if ($tha_column == null){
					echo $column_name . " was not found.\n";
				}
				
				if ($tha_column[type] == "counter" || $tha_column[type] == "int"){
					$sql .= $column_value;
				}else{
					$sql .= "'" . $column_value . "'";
				}
			}else{
				$sql .= "'" . $column_value . "'";
			}
		}
		
		$sql .= ";\n";
		
		return $sql;
	}
	
	function makesql_ColumnSpec($dbtype, $column){
		$sep = makesql_sep($dbtype);
		$sql = $sep . $column['name'] . $sep . " ";
		
		//only MySQL supports the type "tinyint".
		if ($dbtype != "mysql" && $column[type] == "tinyint"){
			$column[type] = "int";
		}elseif($dbtype != "mysql" && $column[type] == "enum"){
			$column[type] = "varchar";
			$column[maxlength] = "";
		}elseif($dbtype == "pgsql" && $column[type] == "tinytext"){
			$column[type] = "varchar";
			$column[maxlength] = "255";
		}
		
		if ($column[type] == "int"){
			if ($dbtype == "mysql"){
				//A AUTO_INCR-type will have a kind of strange default value. This is the way, that we will parse it.
				if (preg_match("/nextval\('public\.(.+)_" . $column[name] . "_seq'::text\)/", $column['default'], $match)){
					$column['default'] = "";
					$ekstra = " AUTO_INCREMENT";
				}
				
				$sql .= "int";
				$col[type] = "int";
			}elseif($dbtype == "access" && $column[primarykey] == "yes"){
				$sql .= "counter";
				$col[type] = "counter";
			}elseif($dbtype == "postgresql" || $dbtype == "sqlite" || $dbtype == "access"){
				$sql .= "integer";
				$col[type] = "int";
			}else{
				$sql .= "int";
				$col[type] = "int";
			}
		}elseif($column[type] == "counter" && $dbtype == "mysql"){
			$sql .= "int";
			$col[type] = "int";
			
			$ekstra = " AUTO_INCREMENT";
		}elseif($dbtype == "access" && $column[type] == "tinytext"){
			//Access does not support tinytext.
			$sql .= "text";
		}elseif($dbtype != "mysql" && $column[type] == "decimal"){
			$sql .= "varchar";
		}elseif($dbtype == "mysql" && $column[type] == "decimal"){
			//The decimal-type is kind of special for MySQL. This code plays with it, so that is is shown 
			//correctly.
			if (!$column[value]){
				echo "No secret value for decimal-field.\n";
			}else{
				echo "Value altered for a decimal-field named " . $column[name] . ".\n";
				$maxlength = $column[value];
			}
		}else{
			$sql .= $column['type'];
		}
		
		if ($dbtype == "mysql" && ($column[type] == "datetime" || $column[type] == "tinytext" || $column[type] == "text")){
			//maxlength is not allowed in MySQL. So nothing goes here (Access can actually have a maxlength on a datetime).
		}elseif($dbtype == "pgsql" && ($column[type] == "int")){
			//maxlength is not allowed on integers in PostgreSQL.
		}elseif($dbtype == "access" && ($col[type] == "int" || $col[type] == "counter" || $column[type] == "int" || $column[type] == "counter")){
			//maxlength is not allowed in Access on a integer or a counter-type.
		}elseif($column['maxlength']){
			$sql .= "(" . $column['maxlength'] . ")";
		}
		
		if ($column['primarykey'] == "yes"){
			if ($dbtype == "mysql" || $dbtype == "sqlite"){
				$sql .= " PRIMARY KEY";
			}
		}
		
		if ($column['notnull'] == "yes"){
			$sql .= " NOT NULL";
		}
		
		if ($column['default'] && $dbtype != "access"){
			$sql .= " DEFAULT '" . parse_quotes($column['default']) . "'";
		}
		
		return $sql . $ekstra;
	}
	
	function makesql_addcolumns($dbtype, $tablename, $columns, $origcolumns = false){
		$sep = makesql_sep($dbtype);
		
		if ($dbtype == "sqlite"){
			//SQLite does not support ALTER TABLE in early version, so we this the VERY HARD way.
			//This is also the reason the the fourth argument: $origcolumns.
			
		}elseif($dbtype == "mysql" || $dbtype == "postgresql"){
			$sql = "ALTER TABLE " . $sep . $tablename . $sep . " ADD COLUMN (";
			
			$first = true;
			foreach($columns AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= makesql_ColumnSpec($dbtype, $column);
			}
			
			$sql .= ");\n";
			
			return $sql;
		}
	}
	
	function makesql_editcolumns($dbtype, $tablename, $oldcolumns, $newcolumns){
		$sep = makesql_sep($dbtype);
		
		$sql = "ALTER TABLE " . $sep . $tablename . $sep . " ";
		$first = true;
		$allchanged = false;
		
		foreach($oldcolumns AS $column_key => $column_value){
			$changed = false;
			
			foreach($column_value AS $data_key => $data_value){
				if ($data_value != $newcolumns[$column_key][$data_key]){
					$changed = true;
					$allchanged = true;
					$newcolumn = $newcolumns[$column_key];
					break;
				}
			}
			
			if ($changed == true){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				if ($column_value[name] != $newcolumn[name]){
					$sql .= "CHANGE " . $sep . $column_value[name] . $sep . " " . makesql_ColumnSpec($dbtype, $newcolumn);
				}else{
					$sql .= "MODIFY " . makesql_ColumnSpec($dbtype, $newcolumn);
				}
			}
		}
		
		if ($allchanged == true){
			$sql .= ";\n";
			
			return $sql;
		}else{
			return false;
		}
	}
?>