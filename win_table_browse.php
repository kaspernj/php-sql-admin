<?
	class WinTableBrowse{
		function __construct($win_main){
			$this->win_main = $win_main;
			$this->dbconn = $win_main->dbconn;
			
			$window = new GtkWindow();
			$this->window = $window;
			
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_title("Browse table");
			$window->set_size_request(800, -1);
			$window->connect("destroy", array($this, "CloseWindow"));
			
			$box = new GtkVBox();
			
			//Construct the menubar.
			$menubar = new GtkMenuBar();
			
			$menu_file = new GtkMenuItem("_File");
			
			$menu_file_menu = new GtkMenu();
			
			$menu_file_insert = new GtkMenuItem("_Insert into table");
			$menu_file_insert->connect("activate", array($this, "InsertIntoClicked"));
			$menu_file_menu->append($menu_file_insert);
			
			$menu_file_delete = new GtkMenuItem("_Delete selected row");
			$menu_file_delete->connect("activate", array($this, "DeleteSelectedClicked"));
			$menu_file_menu->append($menu_file_delete);
			
			$menu_file_empty = new GtkMenuItem("_Empty all rows");
			$menu_file_empty->connect("activate", array($this, "EmptyClicked"));
			$menu_file_menu->append($menu_file_empty);
			
			$menu_file_delall = new GtkMenuItem("De_lete all rows");
			$menu_file_delall->connect("activate", array($this, "DelAllClicked"));
			$menu_file_menu->append($menu_file_delall);
			
			$menu_file->set_submenu($menu_file_menu);
			$menubar->append($menu_file);
			
			$box->pack_start($menubar, false, false);
			
			
			//Construct the window.
			$this->table_selected = $win_main->clist_tables->get_value();
			
			if (!$this->table_selected){
				msgbox("Error", "No table is currently selected.", "warning");
				$this->CloseWindow();
				return false;
			}
			
			$columns = $this->dbconn->GetColumns($this->table_selected[0]);
			
			if (!$columns){
				msgbox("Error", "No columns found for this table.", "warning");
				$this->CloseWindow();
				return false;
			}
			
			foreach($columns AS $value){
				$this->clist_items[] = $value[name];
			}
			
			$clist = new knj_clist($this->clist_items);
			$clist->set_size(-1, 500);
			$this->clist = $clist;
			$this->UpdateClist();
			
			$box->add($clist->scrwin);
			$window->add($box);
			$this->win_main->window->hide();
			$window->show_all();
		}
		
		function DelAllClicked(){
			$table =& $this->table_selected;
			
			query("DELETE FROM " . $table[0]) or die(sqlite_last_error());
			$this->UpdateClist();
			$this->updated = true;
		}
		
		function EmptyClicked(){
			$table =& $this->table_selected;
			
			query("TRANCUATE TABLE " . $table[0]) or die(sqlite_last_error());
			$this->UpdateClist();
			$this->updated = true;
		}
		
		function DeleteSelectedClicked(){
			//Get required data.
			$selected = $this->clist->get_value();
			$table =& $this->table_selected;
			
			//Tjeck for possible failure and interrupt.
			if (!$selected){
				msgbox("Warning", "You have not selected any rows.\n\nThe action was terminated.", "warning");
				return false;
			}
			
			//Think about what to tell the database.
			$columns = $this->dbconn->GetColumns($this->table_selected[0]);
			$count = 0;
			foreach($columns AS $value){
				$columns_del[$value[name]] = $selected[$count];
				$count++;
			}
			$sql = makesql_delete($this->dbconn->type, $table[0], $columns_del, &$columns);
			
			//Yell it to the database.
			if (!query($sql)){
				echo "SQL delete failed: " . $sql;
				msgbox("Error", "The selected row could not be deleted.\n\n" . query_error(), "warning");
			}
			
			//Update clist and mark as updated for closing procedures.
			$this->UpdateClist();
			$this->updated = true;
		}
		
		function UpdateClist(){
			//The following code is used for the status-window.
			$status_countt = str_replace(".", "", $this->table_selected[2]);
			$status_count = 0;
			
			require_once "win_status.php";
			$this->win_status = new WinStatus($this);
			$this->win_status->SetStatus(0, "Reading table rows (" . $status_count . "/" . $status_countt . ").", true);
			
			//Clear the clist.
			$this->clist->ls->clear();
			
			//Read the table from the db.
			$f_gc = query_unbuffered("SELECT * FROM " . $this->table_selected[0]) or die(sqlite_last_error());
			while($d_gc = query_fetch_assoc($f_gc)){
				//Prepare data.
				unset($array);
				
				$count = 0;
				foreach($this->clist_items AS $value){
					$array[] = string_oneline($d_gc[$value]);
					$count++;
				}
				
				//Insert into the clist.
				$this->clist->add($array);
				
				//Update status-window
				$status_count++;
				$this->win_status->SetStatus($status_count / $status_countt, "Reading table rows (" . $status_count . "/" . $status_countt . ").");
			}
			
			$this->win_status->CloseWindow();
		}
		
		function InsertIntoClicked(){
			//require the insert-class.
			require_once "win_table_browse_insert.php";
			
			//Show the insert-class (it automatically hides this window from the insert-constructor).
			$this->win_table_browse_insert = new WinTableBrowseInsert($this);
		}
		
		function CloseWindow(){
			if ($this->updated == true){
				//An update can have occoured while in the browse-window.
				$this->win_main->TablesUpdate();
			}
			
			//Show main-window.
			$this->win_main->window->show();
			
			//Hide browse-window (this window).
			$this->window->hide();
			
			//Unset this window in the main-window.
			unset($this->win_main->win_table_browse);
		}
	}
?>