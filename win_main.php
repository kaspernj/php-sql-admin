<?
	//This class controls the main window.
	
	class WinMain{
		function __construct(){
			//Opens the SQLite database, that this program uses.
			$this->mydb = new DBConn();
			$this->mydb->OpenConn("sqlite", "knj_sqladmin.sqlite");
			
			//Primary object for working with a database.
			$this->dbconn = new DBConn();
			
			$window = new GtkWindow();
			$window->set_title("knj SQL-admin");
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_size_request(-1, -1);
			$window->connect("destroy", array($this, "CloseWindow"));
			
			
			$menubar = new GtkMenuBar();
			
			$menu_file = new GtkMenuItem("_Database");
			$menu_file_menu = new GtkMenu();
			$menu_file_menu->set_size_request(135, -1);
			
			//$menu_file_new = new GtkMenuItem("C_reate new database");
			//$menu_file_new->connect("activate", array($this, "CreateNewDatabaseClicked"));
			//$menu_file_menu->append($menu_file_new);
			
			$menu_file_open = new GtkMenuItem("_Open database");
			$menu_file_open->connect("activate", array($this, "OpenDatabaseClicked"));
			$menu_file_menu->append($menu_file_open);
			
			$menu_file_closedb = new GtkMenuItem("_Close database");
			$menu_file_closedb->connect("activate", array($this, "CloseDatabaseClicked"));
			$menu_file_menu->append($menu_file_closedb);
			
			$menu_file_selectdb = new GtkMenuItem("_Select other db");
			$menu_file_selectdb->connect("activate", array($this, "SelectOtherDbClicked"));
			$menu_file_menu->append($menu_file_selectdb);
			
			$menu_file_truncate = new GtkMenuItem("_Truncate this db");
			$menu_file_truncate->connect("activate", array($this, "TruncateClicked"));
			$menu_file_menu->append($menu_file_truncate);
			
			$menu_file_truncateall = new GtkMenuItem("T_runcate all dbs");
			$menu_file_truncateall->connect("activate", array($this, "TruncateAllClicked"));
			$menu_file_menu->append($menu_file_truncateall);
			
			$menu_file_backup = new GtkMenuItem("_Backup database");
			$menu_file_backup->connect("activate", array($this, "BackupDBClicked"));
			$menu_file_menu->append($menu_file_backup);
			
			$menu_file_runsql = new GtkMenuItem("_Run SQL");
			$menu_file_runsql->connect("activate", array($this, "RunSQLClicked"));
			$menu_file_menu->append($menu_file_runsql);
			
			$menu_file_close = new GtkMenuItem("C_lose program");
			$menu_file_close->connect("activate", array($this, "CloseWindow"));
			$menu_file_menu->append($menu_file_close);
			
			$menu_file->set_submenu($menu_file_menu);
			$menubar->append($menu_file);
			
			
			$menu_table = new GtkMenuItem("_Table");
			$menu_table_menu = new GtkMenu();
			$menu_table_menu->set_size_request(135, -1);
			
			$menu_table_new = new GtkMenuItem("_Create new table");
			$menu_table_new->connect("activate", array($this, "TableCreateClicked"));
			$menu_table_menu->append($menu_table_new);
			
			$menu_table_edit = new GtkMenuItem("_Edit table");
			$menu_table_edit->connect("activate", array($this, "TableEditClicked"));
			$menu_table_menu->append($menu_table_edit);
			
			$menu_table_drop = new GtkMenuItem("_Drop table");
			$menu_table_drop->connect("activate", array($this, "TableDropClicked"));
			$menu_table_menu->append($menu_table_drop);
			
			$menu_table_rename = new GtkMenuItem("_Rename table");
			$menu_table_rename->connect("activate", array($this, "TableRenameClicked"));
			$menu_table_menu->append($menu_table_rename);
			
			$menu_table_browse = new GtkMenuItem("_Browse");
			$menu_table_browse->connect("activate", array($this, "TableBrowseClicked"));
			$menu_table_menu->append($menu_table_browse);
			
			$menu_table->set_submenu($menu_table_menu);
			$menubar->append($menu_table);
			
			
			$menu_column = new GtkMenuItem("_Column");
			$menu_column_menu = new GtkMenu();
			$menu_column_menu->set_size_request(135, -1);
			
			$menu_column_add = new GtkMenuItem("_Add columns");
			$menu_column_add->connect("activate", array($this, "ColumnAddClicked"));
			$menu_column_menu->append($menu_column_add);
			
			$menu_column_remove = new GtkMenuItem("_Remove selected column");
			$menu_column_remove->connect("activate", array($this, "ColumnRemoveClicked"));
			$menu_column_menu->append($menu_column_remove);
			
			$menu_column->set_submenu($menu_column_menu);
			$menubar->append($menu_column);
			
			
			$menu_index = new GtkMenuItem("_Index");
			$menu_index_menu = new GtkMenu();
			$menu_index_menu->set_size_request(135, -1);
			
			$menu_index_add = new GtkMenuItem("_Add index");
			$menu_index_add->connect("activate", array($this, "IndexAddClicked"));
			$menu_index_menu->append($menu_index_add);
			
			$menu_index_drop = new GtkMenuItem("_Drop index");
			$menu_index_drop->connect("activate", array($this, "IndexDropClicked"));
			$menu_index_menu->append($menu_index_drop);
			
			$menu_index->set_submenu($menu_index_menu);
			$menubar->append($menu_index);
			
			
			$box = new GtkVBox();
			$box->pack_start($menubar, false, false);
			
			
			$lab_tables = new GtkLabel("Tables:");
			$lab_tables->set_alignment(0, 0.5);
			
			$clist_tables = new knj_clist(array("Title", "Columns", "Rows", "Engine", "Collation"));
			$clist_tables->set_changed(array($this, "TablesClicked"));
			$clist_tables->set_dbclick(array($this, "TableBrowseClicked"));
			$clist_tables->set_size(380, 250);
			$clist_tables->set_rightclickmenu(array("Browse", "Create new", "Edit", "Rename", "Drop"), array($this, "ClistTablesRightclickMenu"));
			
			$clist_columns = new knj_clist(array("Title", "Type", "Max length", "Not null", "Default", "Primary"));
			$clist_columns->set_changed(array($this, "ColumnsClicked"));
			$clist_columns->set_size(380, 250);
			$clist_columns->set_rightclickmenu(array("Add new columns", "Drop column"), array($this, "ClistColumnsRightclickmenu"));
			
			$clist_indexes = new knj_clist(array("Title", "Columns"));
			$clist_indexes->set_size(380, 250);
			
			$nb_tops = new GtkNotebook();
			$nb_tops->append_page(
				$clist_columns->scrwin,
				new GtkLabel("Columns")
			);
			$nb_tops->append_page(
				$clist_indexes->scrwin,
				new GtkLabel("Indexes")
			);
			
			
			$table = new GtkTable();
			
			$table->attach($lab_tables, 0, 1, 0, 1, GTK_FILL, GTK_SHRINK);
			$table->attach($clist_tables->scrwin, 0, 1, 1, 2);
			
			$table->attach($nb_tops, 1, 2, 0, 2);
			
			$box->add($table);
			
			$window->add($box);
			$window->show_all();
			
			$this->clist_tables = $clist_tables;
			$this->clist_columns = $clist_columns;
			$this->clist_indexes = $clist_indexes;
			$this->window = $window;
		}
		
		function ClistTablesRightclickMenu($mode){
			if ($mode == "Browse"){
				$this->TableBrowseClicked();
			}elseif($mode == "Create new"){
				$this->TableCreateClicked();
			}elseif($mode == "Edit"){
				$this->TableEditClicked();
			}elseif($mode == "Rename"){
				$this->TableRenameClicked();
			}elseif($mode == "Drop"){
				$this->TableDropClicked();
			}else{
				msgbox("Advarsel", "What the fucks?", "warning");
			}
		}
		
		function ClistColumnsRightclickmenu($mode){
			if ($mode == "Add new columns"){
				$this->ColumnAddClicked();
			}elseif($mode == "Drop column"){
				$this->ColumnRemoveClicked();
			}else{
				msgbox("Advarsel", "What the fucks?", "warning");
			}
		}
		
		function TruncateAllClicked(){
			if ($this->dbconn->conn){
				if (msgbox("Question", "Do you really want to truncate all databases on the current connection?", "yesno") == "yes"){
					$dbs = $this->dbconn->GetDBs();
					
					foreach($dbs AS $value){
						$this->dbconn->ChooseDB($value);
						$tables = $this->dbconn->GetTables($value);
						
						foreach($tables AS $table){
							if (!$this->dbconn->TruncateTable($table[name])){
								msgbox("Warning", "Truncation of the table '" . $table[name] . "' on the database '" . $value . "' failed because:\n\n" . $this->dbconn->query_error(), "warning");
							}
						}
					}
					
					$this->TablesUpdate();
				}
			}else{
				msgbox("Warning", "You need to open a database, before you can truncate its databases", "warning");
			}
		}
		
		function SelectOtherDbClicked(){
			if ($this->dbconn->type != "mysql" && $this->dbconn->type != "pgsql"){
				msgbox("Warning", "You have to open either a MySQL- or a PostgreSQL database, before choosing this option.", "warning");
				return false;
			}else{
				require_once "win_databases.php";
				
				$this->win_dbs = new WinDatabases($this);
				$this->window->hide();
			}
		}
		
		function IndexAddClicked(){
			$table = $this->clist_tables->get_value();
			
			if (!$table){
				msgbox("Warning", "Please select a table and try again.", "warning");
				return false;
			}
			
			$column = $this->clist_columns->get_value();
			
			if (!$column){
				msgbox("Warning", "Please select a column to create a index of.", "warning");
				return false;
			}
			
			if (!$this->dbconn->AddIndex($table[0], array($column[0]))){
				msgbox("Warning", "Creating index failed.\n\n" . query_error(), "warning");
				return false;
			}
			
			$this->TablesClicked();
			msgbox("Information", "The index was created with a success.", "info");
		}
		
		function IndexDropClicked(){
			$index = $this->clist_indexes->get_value();
			$table = $this->clist_tables->get_value();
			
			if (!$index){
				msgbox("Warning", "Please select a index to drop and try again.", "warning");
				return false;
			}
			
			if (!$this->dbconn->DropIndex($table[0], $index[0])){
				msgbox("Warning", "Dropping of index failed.\n\n" . query_error(), "warning");
				return false;
			}
			
			$this->TablesClicked();
			return true;
		}
		
		function RunSQLClicked(){
			if (!$this->dbconn->conn){
				msgbox("Warning", "You must open a database, before you can execute a SQL-script.", "warning");
				return false;
			}
			
			require_once "win_runsql.php";
			$this->win_runsql = new WinRunSQL($this);
		}
		
		function BackupDBClicked(){
			if (!$this->dbconn->conn){
				msgbox("Warning", "You must open a database, before you can do a backup.", "warning");
				return false;
			}
			
			require_once "win_backup.php";
			$this->win_backup = new WinBackup($this);
		}
		
		function TableRenameClicked(){
			//Getting the marked table and run some possible error-handeling.
			$table = $this->clist_tables->get_value();
			
			if (!$table){
				msgbox("Warning", "Please select the table, that you would like to rename.", "warning");
				return false;
			}
			
			//Getting the new table-name from the user.
			$tablename = knj_input("New table name", "Please enter the new table-name:", $table[0]);
			if ($tablename == "cancel"){
				return false;
			}
			
			//If he has enteret the same name.
			if (strtolower($tablename) == strtolower($table[0])){
				msgbox("Warning", "The entered name was the same as the current table-name.", "warning");
				return false;
			}
			
			//Checking if the new table-name if valid.
			if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]+$/", $tablename, $match)){
				msgbox("Warning", "The enteret name was not a valid table-name - Try again.", "warning");
				return false;
			}
			
			//Renaming table and refreshing clist.
			if (!$this->dbconn->RenameTable($table[0], $tablename)){
				msgbox("Warning", "An error oqurred while renaming the table.\n\n" . query_error(), "warning");
			}
			
			$this->TablesUpdate();
		}
		
		function TableEditClicked(){
			$table = $this->clist_tables->get_value();
			
			if (!$table){
				msgbox("Warning", "You have to select a table to edit.", "warning");
				return false;
			}
			
			//require and show the window-class.
			require_once "win_table_create.php";
			$table = $this->clist_tables->get_value();
			$this->win_table_create = new WinTableCreate($this, $table[0], "editcolumns");
		}
		
		function ColumnAddClicked(){
			$table = $this->clist_tables->get_value();
			
			if (!$table){
				msgbox("Warning", "You have to select a table to add columns to.", "warning");
				return false;
			}
			
			$input = knj_input("Number of columns", "Write the number of columns, you would like to add to the table:");
			
			if ($input == "cancel"){
				return false;
			}elseif(!is_numeric($input)){
				msgbox("Warning", "Please write numbers only. Try again.", "warning");
				return false;
			}
			
			//require and show the window-class.
			require_once "win_table_create.php";
			$table = $this->clist_tables->get_value();
			$this->win_column_add = new WinTableCreate($this, $table[0], "addcolumns", $input);
		}
		
		function ColumnRemoveClicked(){
			$column = $this->clist_columns->get_value();
			$table = $this->clist_tables->get_value();
			
			if (!$column){
				msgbox("Warning", "You have not selected a column.", "warning");
				return false;
			}
			
			if (msgbox("Question", "Do you want to remove the selected column: " . $column[0] . "?", "yesno") == "yes"){
				if (!$this->dbconn->RemoveColumn($table[0], $column[0])){
					msgbox("Warning", "Couldnt remove the column.\n\n" . query_error(), "warning");
				}
				
				$this->TablesClicked();
			}
		}
		
		function CreateNewDatabaseClicked(){
			msgbox("Warning", "This function is not yet implemented in the program - sorry.", "warning");
			return false;
		}
		
		function TableCreateClicked(){
			if (!$this->dbconn->conn){
				msgbox("Warning", "Currently there is no active database.", "warning");
				return false;
			}
			
			$input = knj_input("Name", "Please enter the table name:");
			
			if ($input == "cancel"){
				return false;
			}
			
			if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]+$/", $input, $match)){
				msgbox("Warning", "The name you chooce is not a valid table-name.", "warning");
				return false;
			}
			
			$tablename = $input;
			
			
			$input = knj_input("Columns", "Please enter the number of columns you want:");
			
			if ($input == "cancel"){
				msgbox("Warning", "The action has been terminated.", "warning");
				return false;
			}
			
			$columns = $input;
			
			
			require_once "win_table_create.php";
			$this->win_table_create = new WinTableCreate($this, $tablename, "createtable", $columns);
		}
		
		function TableBrowseClicked(){
			require_once "win_table_browse.php";
			$this->win_table_browse = new WinTableBrowse($this);
		}
		
		function TableDropClicked(){
			$table = $this->clist_tables->get_value();
			
			if (!$table){
				msgbox("Warning", "You have not selected a table to drop.", "warning");
				return false;
			}
			
			if (msgbox("Question", "Are you sure you want to drop the table: " . $table[0] . "?", "yesno") == "yes"){
				query("DROP TABLE " . $table[0]) or die(query_error());
				$this->TablesUpdate();
			}
		}
		
		function TablesClicked(){
			$this->clist_columns->ls->clear();
			$this->clist_indexes->ls->clear();
			
			$table = $this->clist_tables->get_value();
			$columns = $this->dbconn->GetColumns($table[0]);
			$indexes = $this->dbconn->GetIndexes($table[0]);
			
			if ($columns){
				foreach($columns AS $value){
					$this->clist_columns->add(array($value['name'], $value['type'], $value['maxlength'], $value['notnull'], $value['default'], $value['primarykey']));
				}
			}
			
			if ($indexes){
				foreach($indexes AS $value){
					$this->clist_indexes->add(array($value['name'], $value['columns_text']));
				}
			}
		}
		
		function ColumnsClicked(){
			
		}
		
		function OpenDatabaseClicked(){
			require_once "win_dbprofiles.php";
			$this->win_dbprofiles = new WinDBProfiles($this);
		}
		
		function CloseDatabaseClicked(){
			if ($this->dbconn->conn){
				$this->dbconn->CloseConn();
				$this->clist_tables->ls->clear();
				$this->clist_columns->ls->clear();
			}else{
				msgbox("Warning", "There is no database-connection open at this time.", "warning");
				return false;
			}
		}
		
		function TablesUpdate(WinStatus $win_status = null){
			$this->clist_columns->ls->clear();
			$this->clist_tables->ls->clear();
			
			//updating status-window.
			if ($win_status){
				$win_status->SetStatus(0, "Adding tables to clist (querying)...", true);
			}
			
			$tables = $this->dbconn->GetTables($win_status);
			
			if ($tables){
				$count = 0;
				$countt = count($tables);
				foreach($tables AS $value){
					if ($win_status){
						$count++;
						$win_status->SetStatus($count / $countt, "Adding tables to clist (" . $value[name] . ")...", true);
					}
					
					if (!$value[rows]){
						$f_cr = query("SELECT COUNT(*) AS count FROM " . $value[name]);
						$d_cr = query_fetch_assoc($f_cr);
						
						$rows_count = $d_cr['count'];
					}else{
						$rows_count = $value[rows];
					}
					
					$columns = $this->dbconn->GetColumns($value[name]);
					$this->clist_tables->add(array($value[name], count($columns), number_format($rows_count, 0, ",", "."), $value[engine], $value[collation]));
				}
			}
			
			if ($win_status){
				$win_status->CloseWindow();
			}
		}
		
		function CloseWindow(){
			$this->window->hide();
			Gtk::main_quit();
		}
	}
?>