<?
	//This class shows and controls status-windows, which can be used by other classes. E.g. when backing up a database 
	//a window with a statusbar will show.
	
	class WinStatus{
		function __construct($win_parent){
			$this->win_parent = $win_parent;
			
			$window = new GtkWindow();
			$window->set_title("Status");
			$window->set_resizable(false);
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->connect("destroy", array($this, "CloseWindow"));
			$window->set_size_request(500, -1);
			$window->set_border_width(3);
			$window->set_skip_taskbar_hint(true);
			
			$adj = new GtkAdjustment(0.5, 100.0, 200.0, 0.0, 0.0, 0.0);
			$this->progress = new GtkProgressBar($adj);
			$this->progress = $this->progress;
			@$this->progress->set_percentage(0);
			
			$this->label = new GtkLabel("Status: Afventer");
			$this->label->set_alignment(0, 0.5);
			
			$box = new GtkVBox();
			$box->add($this->label);
			$box->add($this->progress);
			
			$window->add($box);
			$window->show_all();
			
			$this->window = $window;
		}
		
		function CloseWindow(){
			$this->window->hide();
			$this->win_parent->win_status = null;
		}
		
		function SetStatus($perc, $text, $doupd = false){
			//These two lines optimized the executing of a backup dramaticly. The reason for this, is that it takes time 
			//every time that the status has to be updated. Actually with a database with 10.000 rows, it will be updated 
			//10.000 times. But since the human eye can only see about hundred, there is no reason to show more.
			//And that is what is done here by comparing two variables.
			$perc = round($perc, 3);
			
			if ($perc != $this->perc || $doupd){
				$this->label->set_text("Status: " . $text);
				
				$this->perc = $perc;
				@$this->progress->set_percentage($perc);
				updwin();
			}
		}
	}
?>