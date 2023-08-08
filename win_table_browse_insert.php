<?
	class WinTableBrowseInsert{
		function __construct($win_table_browse){
			$this->win_table_browse = $win_table_browse;
			$this->dbconn = $this->win_table_browse->dbconn;
			
			$window = new GtkWindow();
			$this->window = $window;
			
			$window->set_title("Insert into table");
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_size_request(300, -1);
			$window->connect("destroy", array($this, "CloseWindow"));
			$window->set_resizable(false);
			
			$box = new GtkVBox();
			$table = new GtkTable();
			
			$table_selected = $this->win_table_browse->win_main->clist_tables->get_value();
			$columns = $this->dbconn->GetColumns($table_selected[0]);
			
			$this->columns =& $columns;
			$this->table_selected =& $table_selected;
			
			$count_rows = 0;
			foreach($columns AS $value){
				$this->labels[$count_rows] = new GtkLabel($value[name]);
				$this->labels[$count_rows]->set_alignment(0, 0.5);
				
				$this->entrys[$count_rows] = new GtkEntry();
				
				$table->attach($this->labels[$count_rows], 0, 1, $count_rows, $count_rows + 1);
				$table->attach($this->entrys[$count_rows], 1, 2, $count_rows, $count_rows + 1);
				
				$count_rows++;
			}
			
			$button_ok = new GtkButton("Save");
			$button_ok->connect("clicked", array($this, "ButtonOkClicked"));
			
			$button_cancel = new GtkButton("Cancel");
			$button_cancel->connect("clicked", array($this, "CloseWindow"));
			
			$table->attach($button_ok, 0, 1, $count_rows, $count_rows + 1);
			$table->attach($button_cancel, 1, 2, $count_rows, $count_rows + 1);
			
			$box->add($table);
			$window->add($box);
			$window->show_all();
			$this->win_table_browse->window->hide();
		}
		
		function ButtonOkClicked(){
			foreach($this->columns AS $key => $value){
				if ($this->entrys[$key]->get_text() != ""){
					$data[$value[name]] = $this->entrys[$key]->get_text();
				}elseif($value[notnull] == "yes" && $value[primarykey] != "yes"){
					//If we dont cancel this operation, it will make some kind of error.
					msgbox("Warning", "The value if the column: " . $value[name] . " may not be NULL. Try again.", "warning");
					return false;
				}
			}
			
			$sql = makesql_insert($this->dbconn->type, $this->table_selected[0], $data, &$this->columns);
			
			if (!query($sql)){
				msgbox("Warning", "Query failed.\n\n" . query_error(), "warning");
				return false;
			}
			
			$this->win_table_browse->UpdateClist();
			$this->win_table_browse->updated = true;
			$this->CloseWindow();
		}
		
		function CloseWindow(){
			$this->window->hide();
			$this->win_table_browse->window->show();
			unset($this->win_table_browse->win_table_browse_insert);
		}
	}
?>