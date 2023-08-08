<?
	//This class contains the controls for the table-creating window.
	
	class WinTableCreate{
		function __construct($win_main, $tablename, $mode, $numb_columns = 0){
			$this->win_main = $win_main;
			$this->dbconn = $this->win_main->dbconn;
			$this->tablename = $tablename;
			$window = new GtkWindow();
			$this->window = $window;
			$this->mode = $mode;
			
			if ($mode == "createtable"){
				$window->set_title("Create new table");
			}elseif($mode == "addcolumns"){
				$this->columns = $this->dbconn->GetColumns($tablename);
				$window->set_title("Add columns");
			}elseif($mode == "editcolumns"){
				$this->columns = $this->dbconn->GetColumns($tablename);
				$numb_columns = count($this->columns);
				$window->set_title("Edit table");
			}
			
			$this->numb_columns = $numb_columns;
			
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->connect("destroy", array($this, "CloseWindow"));
			$window->set_size_request(800, -1);
			$window->set_resizable(false);
			
			$table = new GtkTable();
			
			
			$lab_name = new GtkLabel("Name:");
			$lab_type = new GtkLabel("Type:");
			$lab_maxlength = new GtkLabel("Max length:");
			$lab_default = new GtkLabel("Default:");
			$lab_notnull = new GtkLabel("Not null:");
			$lab_autoincr = new GtkLabel("Auto inc:");
			$lab_prim = new GtkLabel("Prim key:");
			
			$table->attach($lab_name, 1, 2, 0, 1);
			$table->attach($lab_type, 2, 3, 0, 1);
			$table->attach($lab_maxlength, 3, 4, 0, 1);
			$table->attach($lab_default, 4, 5, 0, 1);
			$table->attach($lab_notnull, 5, 6, 0, 1);
			$table->attach($lab_autoincr, 6, 7, 0, 1);
			$table->attach($lab_prim, 7, 8, 0, 1);
			
			
			$tcount = 1;
			
			$count = 0;
			$countt = $numb_columns;
			
			$types["varchar"] = 0;
			$types["int"] = 1;
			$types["text"] = 2;
			$types["numeric"] = 3;
			$types["blob"] = 4;
			
			while($count < $countt){
				$this->labels[$count] = new GtkLabel("Column " . ($count + 1) . ":");
					$this->labels[$count]->set_alignment(0, 0.5);
				
				$this->names[$count] = new GtkEntry();
					$this->names[$count]->set_size_request(90, -1);
				
				$this->maxlengths[$count] = new GtkEntry();
					$this->maxlengths[$count]->set_max_length(3);
					$this->maxlengths[$count]->set_size_request(50, -1);
				
				$this->types[$count] = GtkComboBox::new_text();
					$this->types[$count]->append_text("VARCHAR");
					$this->types[$count]->append_text("INTEGER");
					$this->types[$count]->append_text("TEXT");
					$this->types[$count]->append_text("NUMERIC");
					$this->types[$count]->append_text("BLOB");
					$this->types[$count]->set_active(0);
				
				$this->defaults[$count] = new GtkEntry();
				$this->notnull[$count] = new GtkCheckButton();
				$this->autoincr[$count] = new GtkCheckButton();
				$this->prim[$count] = new GtkCheckButton();
				
				if ($mode == "editcolumns"){
					$this->names[$count]->set_text($this->columns[$count]['name']);
					$this->defaults[$count]->set_text($this->columns[$count]['default']);
					
					//Access cannot set a maxlength on integers and counters.
					if ($this->dbconn->type == "access"){
						if ($this->columns[$count][type] != "int" && $this->columns[$count][type] != "counter"){
							$this->maxlengths[$count]->set_text($this->columns[$count]['maxlength']);
						}
					}else{
						$this->maxlengths[$count]->set_text($this->columns[$count]['maxlength']);
					}
					
					if ($this->dbconn->type == "access" && $this->columns[$count]['type'] == "counter"){
						//sets to integer if the type is a Access-counter.
						$this->types[$count]->set_active(1);
					}else{
						$this->types[$count]->set_active($types[$this->columns[$count]['type']]);
					}
					
					if ($this->columns[$count]['notnull'] == "yes"){
						$this->notnull[$count]->clicked();
					}
					
					if ($this->columns[$count]['primarykey'] == "yes" || $this->columns[$count]['type'] == "counter"){
						$this->prim[$count]->clicked();
					}
					
					if ($this->columns[$count]['type'] == "counter"){
						$this->autoincr[$count]->clicked();
					}
				}elseif($mode == "createtable" || $mode == "addcolumns"){
					$this->notnull[$count]->clicked();
				}
				
				$table->attach($this->labels[$count], 0, 1, $tcount, $tcount + 1);
				$table->attach($this->names[$count], 1, 2, $tcount, $tcount + 1);
				$table->attach($this->types[$count], 2, 3, $tcount, $tcount + 1);
				$table->attach($this->maxlengths[$count], 3, 4, $tcount, $tcount + 1);
				$table->attach($this->defaults[$count], 4, 5, $tcount, $tcount + 1);
				$table->attach($this->notnull[$count], 5, 6, $tcount, $tcount + 1);
				$table->attach($this->autoincr[$count], 6, 7, $tcount, $tcount + 1);
				$table->attach($this->prim[$count], 7, 8, $tcount, $tcount + 1);
				
				$tcount++;
				$count++;
			}
			
			$button_ok = new GtkButton("Save");
			$button_ok->connect("clicked", array($this, "ButtonOkClicked"));
			
			$button_cancel = new GtkButton("Cancel");
			$button_cancel->connect("clicked", array($this, "CloseWindow"));
			
			$table->attach($button_ok, 0, 1, $tcount, $tcount + 1);
			$table->attach($button_cancel, 1, 2, $tcount, $tcount + 1);
			
			$window->add($table);
			$window->show_all();
			$win_main->window->hide();
		}
		
		function CloseWindow(){
			$this->window->hide();
			$this->win_main->window->show();
			unset($this->win_main->win_table_create);
		}
		
		function ButtonOkClicked(){
			for($count = 0; $count < $this->numb_columns; $count++){
				$maxlength = $this->maxlengths[$count]->get_text();
				$name = $this->names[$count]->get_text();
				$type = $this->types[$count]->get_active_text();
				$prim = $this->prim[$count]->active;
				$default = $this->defaults[$count]->get_text();
				$autoincr = $this->autoincr[$count]->active;
				$notnull = $this->notnull[$count]->active;
				
				//Tjecking that the column name isnt the same as an existing column.
				if ($mode == "addcolumns"){
					foreach($this->columns AS $value){
						if (trim(strtolower($name)) == strtolower($value[name])){
							msgbox("Warning", "Column " . ($count + 1) . " has the same name as an existing column.", "warning");
							return false;
						}
					}
				}
				
				if ($type == "VARCHAR"){
					$type = "varchar";
				}elseif($type == "INTEGER"){
					$type = "int";
				}
				
				if (!$prim){
					$prim = "no";
				}else{
					$prim = "yes";
				}
				
				if (!$autoincr){
					$autoincr = "no";
				}else{
					$autoincr = "yes";
				}
				
				if (!$notnull){
					$notnull = "no";
				}else{
					$notnull = "yes";
				}
				
				//Recognizable error-handeling.
				if ($this->dbconn->type == "access" && $type == "int" && $maxlength){
					msgbox("Warning", "Access does not support a maxlength-value on a integer-column.\n\nPlease leave the maxlength-textfield on the column '" . $name . "' empty.", "warning");
					return false;
				}
				
				if ($maxlength && !is_numeric($maxlength)){
					msgbox("Warning", "You have filled the maxlength-textfield at the column '" . $name . "' with a non-numeric-value.\n\nPlease change this to an empty- or a numeric value.", "warning");
					return false;
				}
				
				if ($prim == "yes" && $type != "int"){
					msgbox("Warning", "The primary key can only be a integer at the column '" . $name . "'.", "warning");
					return false;
				}
				
				if ($autoincr == "yes" && $type != "int"){
					msgbox("Warning", "You cant set autoincrement on a varchar at the column '" . $name . "'.");
					return false;
				}
				//End of the recognizable error-handeling.
				
				$newcolumns[] = array(
					"name" => $name,
					"type" => $type,
					"primarykey" => $prim,
					"notnull" => $notnull,
					"maxlength" => $maxlength
				);
			}
			
			if ($this->mode == "addcolumns"){
				//Running DBConn-command for adding columns.
				if (!$this->dbconn->AddColumns($this->tablename, $newcolumns)){
					msgbox("Warning", "An error oqurred while adding the columns.\n\n" . query_error(), "warning");
					return false;
				}
			}elseif($this->mode == "editcolumns"){
				//Running DBConn-command for editing columns.
				if (!$this->dbconn->EditColumns($this->tablename, $this->columns, $newcolumns)){
					msgbox("Warning", "An error oqurred while edditing the columns.\n\n" . query_error(), "warning");
					return false;
				}
			}elseif($this->mode == "createtable"){
				$sql = makesql_table($this->dbconn->type, $this->tablename, $newcolumns);
				
				if (!query($sql)){
					msgbox("Warning", "Could not create the table.\n\n" . query_error(), "warning");
					return false;
				}
			}
			
			//Update main-window and close this window.
			$this->win_main->TablesUpdate();
			$this->CloseWindow();
		}
	}
?>