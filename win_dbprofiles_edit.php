<?
	//This class controls the window when making and editing db-profiles.
	
	class WinDBProfilesEdit{
		function __construct($win_dbprofile, $mode){
			$window = new GtkWindow();
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_size_request(400, -1);
			$window->set_resizable(false);
			$window->connect("destroy", array($this, "CloseWindow"));
			
			//Typer der kan bruges.
			$this->types["mysql"] = "MySQL";
			$this->types["pgsql"] = "PostgreSQL";
			$this->types["sqlite"] = "SQLite";
			$this->types["mssql"] = "MS-SQL";
			$this->types["access"] = "Access";
			
			$this->types_text["mysql"] = 0;
			$this->types_text["pgsql"] = 1;
			$this->types_text["sqlite"] = 2;
			$this->types_text["mssql"] = 3;
			$this->types_text["access"] = 4;
			
			$this->types_nr[0] = "mysql";
			$this->types_nr[1] = "pgsql";
			$this->types_nr[2] = "sqlite";
			$this->types_nr[3] = "mssql";
			$this->types_nr[4] = "access";
			
			//Titlen på vinduet.
			if ($mode == "add"){
				$window->set_title("Add new profile");
				$this->mode = "add";
			}else{
				$window->set_title("Edit profile");
				$this->mode = "edit";
			}
			
			//Felter der skal vises og deres indstillinger.
			$lab_title = new GtkLabel("Title:");
			$lab_title->set_alignment(0, 0.5);
			$this->tex_title = new GtkEntry();
			
			$lab_location = new GtkLabel("Location or IP:");
			$lab_location->set_alignment(0, 0.5);
			$this->tex_location = new GtkEntry();
			
			$lab_type = new GtkLabel("Type:");
			$lab_type->set_alignment(0, 0.5);
			$this->tex_type = GtkComboBox::new_text();
			foreach($this->types AS $value){
				$this->tex_type->append_text($value);
			}
			
			$lab_port = new GtkLabel("Port:");
			$lab_port->set_alignment(0, 0.5);
			$this->tex_port = new GtkEntry();
			
			$lab_user = new GtkLabel("Username:");
			$lab_user->set_alignment(0, 0.5);
			$this->tex_user = new GtkEntry();
			
			$lab_pass = new GtkLabel("Password:");
			$lab_pass->set_alignment(0, 0.5);
			$this->tex_pass = new GtkEntry();
			$this->tex_pass->set_visibility(false);
			
			$lab_db = new GtkLabel("Database:");
			$lab_db->set_alignment(0, 0.5);
			$this->tex_db = new GtkEntry();
			
			$but_save = new GtkButton("Save");
			$but_save->connect("clicked", array($this, "SaveClicked"));
			
			$but_cancel = new GtkButton("Cancel");
			$but_cancel->connect("clicked", array($this, "CloseWindow"));
			
			$table = new GtkTable();
			
			$number = 0;
			$table->attach($lab_title, 0, 1, $number, $number + 1);
			$table->attach($this->tex_title, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($lab_location, 0, 1, $number, $number + 1);
			$table->attach($this->tex_location, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($lab_type, 0, 1, $number, $number + 1);
			$table->attach($this->tex_type, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($lab_port, 0, 1, $number, $number + 1);
			$table->attach($this->tex_port, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($lab_user, 0, 1, $number, $number + 1);
			$table->attach($this->tex_user, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($lab_pass, 0, 1, $number, $number + 1);
			$table->attach($this->tex_pass, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($lab_db, 0, 1, $number, $number + 1);
			$table->attach($this->tex_db, 1, 2, $number, $number + 1);
			
			$number++;
			$table->attach($but_save, 0, 1, $number, $number + 1);
			$table->attach($but_cancel, 1, 2, $number, $number + 1);
			
			$window->add($table);
			
			if ($this->mode == "edit"){
				$editvalue = $win_dbprofile->clist_profiles->get_value();
				
				$f_gd = sqlite_query($win_dbprofile->win_main->mydb->conn, "SELECT * FROM profiles WHERE nr = '$editvalue[Nr]'");
				$this->edit_data = sqlite_fetch_array($f_gd);
				
				$this->tex_title		->set_text($this->edit_data[title]);
				$this->tex_location	->set_text($this->edit_data[location]);
				$this->tex_user		->set_text($this->edit_data[username]);
				$this->tex_pass		->set_text($this->edit_data[password]);
				$this->tex_db			->set_text($this->edit_data[database]);
				$this->tex_port		->set_text($this->edit_data[port]);
				$this->tex_type		->set_active($this->types_text[$this->edit_data[type]]);
			}
			
			$window->show_all();
			$win_dbprofile->window->hide();
			
			$this->win_dbprofile = $win_dbprofile;
			$this->window = $window;
			$this->mode = $mode;
		}
		
		function SaveClicked(){
			$nr =			$this->edit_data[nr];
			$title =		$this->tex_title->get_text();
			$type =		$this->types_nr[$this->tex_type->get_active()];
			$port =		$this->tex_port->get_text();
			$location =	$this->tex_location->get_text();
			$username =	$this->tex_user->get_text();
			$password =	$this->tex_pass->get_text();
			$db =			$this->tex_db->get_text();
			
			if ($this->mode == "edit"){
				sqlite_query($this->win_dbprofile->win_main->mydb->conn, "
					UPDATE
						profiles
					
					SET
						title = '$title',
						type = '$type',
						port = '$port',
						location = '$location',
						username = '$username',
						password = '$password',
						database = '$db'
					
					WHERE
						nr = '$nr'
				") or die(sqlite_error());
			}elseif($this->mode == "add"){
				sqlite_query($this->win_dbprofile->win_main->mydb->conn, "
					INSERT INTO
						profiles
					
					(
						title,
						type,
						port,
						location,
						username,
						password,
						database
					) VALUES (
						'$title',
						'$type',
						'$port',
						'$location',
						'$username',
						'$password',
						'$db'
					)
				") or die(sqlite_error());
			}
			
			$this->win_dbprofile->UpdateCList();
			$this->CloseWindow();
		}
		
		function CloseWindow(){
			$this->window->hide();
			$this->win_dbprofile->window->show();
		}
	}
?>