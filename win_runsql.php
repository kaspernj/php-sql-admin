<?
	class WinRunSQL{
		function __construct($win_main){
			$this->win_main = $win_main;
			$this->dbconn = $win_main->dbconn;
			
			$window = new GtkWindow();
			$window->set_title("Run SQL");
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_size_request(500, -1);
			$window->set_resizable(false);
			$window->set_border_width(3);
			$window->connect("destroy", array($this, "CloseWindow"));
			
			$tex_file = new GtkEntry();
				$this->tex_file = $tex_file;
			
			$but_browse = new GtkButton("Browse...");
				$but_browse->connect("clicked", array($this, "BrowseClicked"));
				$but_browse->set_size_request(100, -1);
			
			$box_file = new GtkHBox();
				$box_file->add($tex_file);
				$box_file->add($but_browse);
			
			$but_readsql = new GtkButton("Execute file");
				$but_readsql->connect("clicked", array($this, "ReadSQLClicked"));
			
			$lab_info = new GtkLabel("Please choose a file and execute it:");
				$lab_info->set_alignment(0, 0.5);
			
			$box = new GtkVBox();
				$box->add($lab_info);
				$box->add($box_file);
				$box->add($but_readsql);
			
			$window->add($box);
			
			$window->show_all();
			$this->win_main->window->hide();
			$this->window = $window;
		}
		
		function CloseWindow(){
			$this->win_main->window->show();
			$this->window->hide();
			$this->win_main->win_runsql = null;
		}
		
		function BrowseClicked(){
			if ($this->fs){
				msgbox("Warning", "Please close the other browsing window before opening a new one.", "warning");
				return false;
			}
			
			$this->fs = new GtkFileSelection();
			$this->fs->set_title("Browse for SQL-file");
			$this->fs->set_position(GTK_WIN_POS_CENTER);
			$this->fs->ok_button->connect("clicked", array($this, "BrowseOkClicked"));
			$this->fs->cancel_button->connect("clicked", array($this, "BrowseCancelClicked"));
			$this->fs->connect("destroy", array($this, "BrowseCancelClicked"));
			$this->fs->show();
		}
		
		function BrowseCancelClicked(){
			$this->fs->hide();
			$this->fs = null;
		}
		
		function BrowseOkClicked(){
			$dir = safedir(substr($this->fs->selection_text->get(), 11));
			$file = safedir($this->fs->selection_entry->get_text());
			
			$this->fs->hide();
			$this->fs = null;
			
			$this->tex_file->set_text($dir . "/" . $file);
		}
		
		function ReadSQLClicked(){
			require_once "functions_knj_readfile.php";
			require_once "functions_gz.php";
			require_once "win_status.php";
			
			$filename = $this->tex_file->get_text();
			
			if (!$filename){
				msgbox("Warning", "Please choose a file before executing.", "warning");
				return false;
			}
			
			if (!file_exists($filename)){
				msgbox("Warning", "The file you have chosen does not exists.", "warning");
				return false;
			}
			
			if (substr($filename, -4, 4) != ".sql" && substr($filename, -7, 7) !== ".sql.gz"){
				msgbox("Warning", "Could not recognize the file extension. It is only possible to parse \".sql\" og \".sql.gz\"-files.", "warning");
				return false;
			}
			
			if (substr($filename, -4, 4) == ".sql"){
				$fp = fopen($filename, "r");
				$mode = "plain";
				
				$countt = filesize($filename);
			}elseif(substr($filename, -7, 7) == ".sql.gz"){
				$fp = gzopen($filename, "r");
				$mode = "gz";
				
				$countt = gzfilesize($filename);
			}
			
			
			$count = 0;
			
			$this->win_status = new WinStatus($this);
			$this->win_status->SetStatus(0, "Reading SQL - 0%)", true);
			
			while(!knj_feof($fp, $mode)){
				$line = knj_freadline($fp, $mode);
				
				if (!query($line)){
					echo $line;
					msgbox("Warning", "One of the lines failed to be executed. Returned the following error:\n\n" . query_error(), "warning");
				}
				
				$count += strlen($line);
				$perc = $count / $countt;
				$this->win_status->SetStatus($perc, "Reading SQL - " . number_format(($count / 1024) / 1024, 1) . " mb - " . round($perc * 100, 0) . "%");
			}
			
			$this->win_status->CloseWindow();
			
			if ($mode == "plain"){
				fclose($fp);
			}elseif($mode == "gz"){
				gzclose($fp);
			}
			
			msgbox("Done", "The SQL-file has been parsed and executed.", "info");
			$this->win_main->TablesUpdate();
			$this->CloseWindow();
		}
	}
?>