<?
	//The class controls the backup-window and the execution of the functions in it.
	
	class WinBackup{
		function __construct($win_main){
			$this->win_main = $win_main;
			$this->dbconn = $win_main->dbconn;
			
			$window = new GtkWindow();
			$window->set_title("Backup");
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_size_request(400, -1);
			$window->connect("destroy", array($this, "CloseWindow"));
			
			$box = new GtkVBox();
			$table = new GtkTable();
			
			$clist = new knj_clist(array("Tablename"));
			$clist->set_size(-1, 400);
			$clist->set_multiple_select();
			$this->clist = &$clist;
			
			$tables = $this->dbconn->GetTables();
			foreach($tables AS $value){
				$clist->add(array($value[name]));
			}
			$table->attach($clist->scrwin, 0, 1, 0, 6);
			
			$this->ch_structure = new GtkCheckButton("Structure");
			$this->ch_data = new GtkCheckButton("Data");
			$this->ch_gz = new GtkCheckButton("GZIP-format");
			$this->ch_cinserts = new GtkCheckButton("Complete inserts");
			
			$lab_format = new GtkLabel("Format:");
			$lab_format->set_alignment(0, 0.5);
			
			$this->format = GtkComboBox::new_text();
				$this->format->append_text("MySQL");
				$this->format->append_text("PostgreSQL");
				$this->format->append_text("SQLite");
				$this->format->append_text("MS-SQL");
				$this->format->append_text("Access");
				$this->format->set_active(0);
			
			$button_save = new GtkButton("Save file");
			$button_save->set_size_request(200, -1);
			$button_save->connect("clicked", array($this, "SaveClicked"));
			
			$table->attach($this->ch_structure, 1, 3, 0, 1, GTK::FILL, Gtk::SHRINK);
			$table->attach($this->ch_data, 1, 3, 1, 2, GTK::FILL, Gtk::SHRINK);
			$table->attach($this->ch_gz, 1, 3, 2, 3, GTK::FILL, Gtk::SHRINK);
			$table->attach($this->ch_cinserts, 1, 3, 3, 4, GTK::FILL, Gtk::SHRINK);
			
			$table->attach($lab_format, 1, 2, 4, 5, GTK::FILL, Gtk::SHRINK);
			$table->attach($this->format, 2, 3, 4, 5, Gtk::FILL, Gtk::SHRINK);
			
			$table->attach($button_save, 1, 3, 6, 7, GTK::EXPAND, Gtk::SHRINK);
			
			$box->add($table);
			$window->add($box);
			$window->show_all();
			$win_main->window->hide();
			
			$this->window = $window;
		}
		
		function SaveClicked(){
			//Validating if any tables have been choosen.
			$values = $this->clist->get_value_all();
			if (!$values){
				msgbox("Advarsel", "Du skal først vælge hvilke tabeller, som du vil lave backup af.", "warning");
				return false;
			}
			
			//If a file-selection is already open, we shouldnt open another (could cause a fatal error).
			if ($this->fs_backup_db){
				msgbox("Warning", "Please close the folder-selection, before you try to make a new backup.", "warning");
				return false;
			}
			
			//Prompting for a directory to place the backup-file in and register events. At the last showing the window.
			$this->fs_backup_db = new GtkFileSelection("Save backup-file");
			$this->fs_backup_db->connect("destroy", array($this, "BackupCancel"));
			$this->fs_backup_db->ok_button->connect("clicked", array($this, "BackupOk"));
			$this->fs_backup_db->cancel_button->connect("clicked", array($this, "BackupCancel"));
			$this->fs_backup_db->show();
		}
		
		function BackupOk(){
			//Make a status-window ready.
			require_once "win_status.php";
			$this->win_status = new WinStatus($this);
			$this->win_status->SetStatus(0, "Preparing the backup-process...", true);
			
			//Get the format.
			$format = $this->format->get_active_text();
			if ($format == "MySQL"){
				$format = "mysql";
			}elseif($format == "PostgreSQL"){
				$format = "pgsql";
			}elseif($format == "SQLite"){
				$format = "sqlite";
			}elseif($format == "Access"){
				$format = "access";
			}else{
				msgbox("Warning", "Format hasnt been supported yet. Sorry.", "warning");
				return false;
			}
			
			//Get the filename.
			$dir = safedir(substr($this->fs_backup_db->selection_text->get(), 11));
			$filename = $dir . "/" . $this->fs_backup_db->selection_entry->get_text();
			
			//Hide the fileselection-window.
			$this->fs_backup_db->hide();
			$this->fs_backup_db = null;
			
			//Validate if we should open and read a gz-file or a plaintext sql-file.
			if ($this->ch_gz->active){
				if (strtolower(substr($filename, -7, 7)) != ".sql.gz"){
					$filename .= ".sql.gz";
				}
				
				$mode = "gz";
				$fp = gzopen($filename, "w9");
			}else{
				if (strtolower(substr($filename, -4, 4)) != ".sql"){
					$filename .= ".sql";
				}
				
				$mode = "plain";
				$fp = fopen($filename, "w");
			}
			
			if ($this->ch_data->active){
				$data = true;
			}
			
			if ($this->ch_structure->active){
				$struc = true;
			}
			
			if ($this->cinserts->active){
				$cins = true;
			}
			
			
			//Read the tables from the database.
			$this->win_status->SetStatus(0, "Counting...", true);
			$tables = $this->dbconn->GetTables();
			
			//Validating which tables should be backed up.
			$values = $this->clist->get_value_all();
			foreach($values AS $value){
				$tables_back[$value[0]] = true;
			}
			
			//Counting how many SQL-lines should be wrote as "points" (to make a status of the operation).
			$count_points = 0;
			$countt_points = count($tables);
			foreach($tables AS $tha_table){
				if ($tables_back[$tha_table[name]]){
					$this->win_status->SetStatus(0, "Counting (" . $tha_table[name] . ")...", true);
					
					$f_cd = query("SELECT COUNT(*) AS count FROM " . $tha_table[name]);
					$d_cd = query_fetch_assoc($f_cd);
					
					$countt_points += $d_cd['count'];
				}
			}
			
			//Backup of the database-structure (tables etc.)
			$this->win_status->SetStatus(0, "Executing backup (0/" . $count_points . ")", true);
			if ($struc == true){
				foreach($tables AS $tha_table){
					if ($tables_back[$tha_table[name]]){
						//Making SQL for the structure.
						$columns = $this->dbconn->GetColumns($tha_table[name]);
						$indexes = $this->dbconn->GetIndexes($tha_table[name]);
						
						$sql .= makesql_table($format, $tha_table[name], $columns);
						
						if ($indexes){
							foreach($indexes AS $index){
								$sql .= makesql_index($format, $tha_table[name], $index);
							}
						}
						
						//Flushing SQL to the file.
						$this->BackupFlush($fp, $mode, $sql);
						
						//Updating status-window.
						$count_points++;
						$this->win_status->SetStatus($count_points / $countt_points, "Executing backup (" . $count_points . "/" . $countt_points . ")");
					}
				}
			}
			
			//Backup of the data (inserts).
			if ($data == true){
				foreach($tables AS $tha_table){
					if ($tables_back[$tha_table[name]]){
						$columns = $this->dbconn->GetColumns($tha_table[name]);
						$this->win_status->SetStatus($perc, "Executing backup (" . $count_points . "/" . $countt_points . ") (Querying " . $tha_table[name] . "...).", true);
						
						$f_gd = query("SELECT * FROM " . $tha_table[name]);
						while($d_gd = query_fetch_assoc($f_gd)){
							$sql .= makesql_insert($format, $tha_table[name], $d_gd, &$columns);
							
							$this->BackupFlush($fp, $mode, $sql);
							$count_points++;
							$perc = $count_points / $countt_points;
							$this->win_status->SetStatus($perc, "Executing backup (" . $count_points . "/" . $countt_points . ") (Reading " . $tha_table[name] . ").");
						}
					}
				}
			}
			
			//Flushing rest of data (there shouldnt be any - just to be safe).
			$this->BackupFlush($fp, $mode, $sql);
			
			//Closing file-pointer.
			if ($mode == "gz"){
				gzclose($fp);
			}elseif($mode == "plain"){
				fclose($fp);
			}
			
			//Closing status-window and reset the operation.
			$this->win_status->CloseWindow();
			msgbox("Completed", "The backup execution has ended, and the backup-file has been written.", "info");
		}
		
		function BackupFlush($fp, $mode, &$sql){
			if ($mode == "gz"){
				gzwrite($fp, $sql);
			}elseif($mode == "plain"){
				fwrite($fp, $sql);
			}
			
			$sql = "";
		}
		
		function BackupCancel(){
			$this->fs_backup_db->hide();
			$this->fs_backup_db = null;
		}
		
		function CloseWindow(){
			$this->window->hide();
			$this->win_main->window->show();
			$this->win_main->win_backup = null;
		}
	}
?>