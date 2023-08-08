<?
	class WinDatabases{
		function __construct($win_main){
			$this->win_main = $win_main;
			$this->dbconn = $win_main->dbconn;
			
			$window = new GtkWindow();
			$this->window = $window;
			$window->set_title("Select other database");
			$window->set_resizable(false);
			$window->set_size_request(300, -1);
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->connect("destroy", array($this, "CloseWindow"));
			$window->set_border_width(2);
			
			$box = new GtkVBox();
			
			$this->clist = new knj_clist(array("Title"));
			$this->clist->set_size(-1, 300);
			$this->clist->set_dbclick(array($this, "CListDblClick"));
			$this->UpdateDBList();
			
			$box->add($this->clist->scrwin);
			
			$window->add($box);
			$window->show_all();
		}
		
		function CListDblClick($clist_value){
			$this->ChooseDB();
		}
		
		function ChooseDB(){
			require_once "win_status.php";
			$win_status = new WinStatus($this);
			$win_status->SetStatus(0, "Changing database...", true);
			
			$clist_value = $this->clist->get_value();
			$state = $this->dbconn->ChooseDB($clist_value[0]);
			
			if (!$state){
				$win_status->CloseWindow();
				msgbox("Warning", "Failed to change database.\n\n" . query_error(), "warning");
				return false;
			}
			
			$this->win_main->TablesUpdate($win_status);
			$this->CloseWindow();
		}
		
		function UpdateDBList(){
			foreach($this->dbconn->GetDBs() AS $value){
				$this->clist->add(array($value));
			}
		}
		
		function CloseWindow(){
			$this->window->hide();
			$this->win_main->window->show();
		}
	}
?>