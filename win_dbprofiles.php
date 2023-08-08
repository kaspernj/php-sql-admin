<?
	//This class contains the window when showing the different kind of db-profiles.
	
	class WinDBProfiles{
		function __construct($win_main){
			$this->win_main = $win_main;
			
			$window = new GtkWindow();
			$window->set_title("Database profiles");
			$window->set_size_request(400, -1);
			$window->set_position(GTK_WIN_POS_CENTER);
			$window->set_border_width(4);
			$window->connect("destroy", array($this, "CloseWindow"));
			
			$this->clist_profiles = new knj_clist(array("Nr", "Titel", "Type", "Database"));
			$this->clist_profiles->set_size(-1, 250);
			$this->clist_profiles->columns[0]->set_visible(false);
			$this->UpdateCList();
			$this->clist_profiles->set_dbclick(array($this, "ConnectClicked"));
			
			$button_connect = new GtkButton("Connect");
			$button_connect->connect("clicked", array($this, "ConnectClicked"));
			
			$button_add = new GtkButton("Add new");
			$button_add->connect("clicked", array($this, "AddClicked"));
			
			$button_del = new GtkButton("Delete");
			$button_del->connect("clicked", array($this, "DelClicked"));
			
			$button_edit = new GtkButton("Edit");
			$button_edit->connect("clicked", array($this, "EditClicked"));
			
			$box_buttons = new GtkHBox();
			$box_buttons->pack_start($button_connect);
			$box_buttons->pack_start($button_add);
			$box_buttons->pack_start($button_del);
			$box_buttons->pack_start($button_edit);
			
			$box = new GtkVBox();
			$box->add($this->clist_profiles->scrwin);
			$box->pack_start($box_buttons, false, false);
			
			$window->add($box);
			$window->show_all();
			
			$this->window = $window;
			$this->win_main->window->hide();
		}
		
		function UpdateCList(){
			$this->clist_profiles->ls->clear();
			$mydb = $this->win_main->mydb;
			
			$f_gp = $mydb->query("SELECT * FROM profiles ORDER BY title") or die($mydb->query_error());
			while($d_gp = $mydb->query_fetch_assoc($f_gp)){
				$this->clist_profiles->add(array($d_gp[nr], $d_gp[title], $d_gp[type], $d_gp[database]));
			}
		}
		
		function ConnectClicked(){
			unset($this->win_main->dbconn->type);
			
			$mydb = $this->win_main->mydb;
			$value = $this->clist_profiles->get_value();
			
			if (!$value){
				return false;
			}
			
			$f_gd = $mydb->query("SELECT * FROM profiles WHERE nr = '$value[Nr]'") or die($mydb->query_error());
			$d_gd = $mydb->query_fetch_assoc($f_gd);
			
			if (!$d_gd){
				msgbox("Advarsel", "Database-profilen blev ikke fundet til '" . $value[1] . "'.", "warning");
				return false;
			}
			
			require_once "win_status.php";
			$win_status = new WinStatus($this);
			
			if ($d_gd[type] == "mysql"){
				$state = $this->win_main->dbconn->OpenConn("mysql", $d_gd[location], $d_gd[port], $d_gd[database], $d_gd[username], $d_gd[password], $win_status);
			}elseif($d_gd[type] == "pgsql"){
				$state = $this->win_main->dbconn->OpenConn("pgsql", $d_gd[location], $d_gd[port], $d_gd[database], $d_gd[username], $d_gd[password], $win_status);
			}elseif($d_gd[type] == "sqlite"){
				if (!file_exists($d_gd[location])){
					if (msgbox("Warning", "The database (" . $d_gd[location] . ") could not be found. Do you want to create it?", "yesno") == "yes"){
						$fp = fopen($d_gd[location], "w");
						
						if (!$fp){
							$win_status->CloseWindow();
							msgbox("Warning", "The database could not be created.", "warning");
							return false;
						}
					}else{
						$win_status->CloseWindow();
						msgbox("Warning", "The file " . $d_gd[location] . " could not be found - aborting.", "warning");
						return false;
					}
				}
				
				$state = $this->win_main->dbconn->OpenConn($d_gd[type], $d_gd[location]);
				//query("INSERT INTO 'sci_artikler' ('titel', 'beskrivelse', 'indhold', 'level', 'eksponeringer', 'diff', 'rela', 'encoder', 'lng', 'fag_id', 'user_id', 'group_id') VALUES ('.htacess', 'Lær en af de sikreste apache login''s.', '						[AFSNIT].htaccess[/AFSNIT]\r\nHej, jeg synes lige at jeg ville gå i dybden med lidt Apache server scripting. Den funktion jeg vil forklare lidt om er .htaccess, du kan måske undrer dig over der skal et \".\" foran, men det er fordi at så er filen hemmelig og den kan ikke blive vist til nogen uden for serveren, og på den måde er login*i*et næsten sikkert, folk kan kun komme ind hvis de har adgang til serveren.\r\n\r\nDet jeg først vil er at demonstrere .htaccess, gå til nederstående URL\:\r\nhttp://www.webcafe.dk/artikler/apache/htaccesspassword/blondiner.html\r\n[KODE]\r\nBrugernavnet er: blondine\r\nAdgangskoden er: altidfrisk\r\n[/KODE]\r\nDet virker meget godt ik? Lad os komme igang med at oprette vores egen .htaccess fil og få den til at virke.\r\n\r\nFørst opretter du filen .htaccess, den skal indeholde:\r\n[KODE]AuthName \"Navnet på på det .htaccess beskytter\"\r\nAuthType Basic\r\nAuthUserFile stien/til/.dine_gemte_adgangskoder\r\nrequire valid-user[/KODE]\r\nLad og gennemgå koderne.\r\n    1. \"AuthName\" er navnet på det .htaccess beskytter\r\n    2. \"AuthType Basic\" er måden filen skal fungere på. Du skal ikke lade dig snyde af at der står \"Basic\". Den er sikker.\r\n    3. \"AuthUserFile\" er stien til den fil der indeholder brugernavn og adgangskode info*i*et, jeg kommer til denne funktion.\r\n    4. \"require valid-user\" betyder at den skal åbne sig hvis du har intastet det rigtige brugernavn og det rigtige brugernavn.\r\n\r\nSå skal vi lave en fil der indeholder brugernavn og adgangskode info*i*et, navnet er ligegyldigt så længe at stien i \".htaccess\" passer med navnet på denne fil. Filen skal indeholde:\r\n[KODE]Brugernavn:krypteret_adgangskode[/KODE]\r\nNu tænker du sikkert, hvordan skal jeg kryptere en adgangskode?\r\nDet kan gøre ved hjælp af at oprette en PHP fil, du bestemmer selv navnet, den skal bare indeholde:\r\n[KODE]&lt;!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n&lt;title&gt;base64 kryptering&lt;/title&gt;\r\n&lt;/head&gt;\r\n  &lt;body bgcolor=\"white\"&gt;\r\n&lt;? if ($bruger && $pw) {\r\n	$krypteret = crypt(\"$pw\",base64_encode(\"$pw\"));\r\n  	echo  \"&lt;br&gt;&lt;br&gt;&lt;b&gt;Brugernavn:&lt;/b&gt; $bruger\"; \r\n  	echo  \"&lt;br&gt;&lt;b&gt;Krypteret password:&lt;/b&gt; $krypteret&lt;br&gt;&lt;br&gt;\\n\";  \r\n   	echo  \"I din \\\"httpasswd\\\"-fil skriver du sådan: \";\r\n   	echo  \"&lt;b&gt;$bruger:$krypteret&lt;/b&gt;&lt;br&gt;&lt;br&gt;\\n\";\r\n   	echo  \"&lt;b&gt;Kryptér nyt:&lt;/b&gt;&lt;br&gt;&lt;br&gt;\"; \r\n  } \r\n\r\n  echo  \"&lt;form action=\\\"$PHP_SELF\\\" method=\\\"post\\\"&gt;\"; \r\n  echo  \"Brugernavn: &lt;input name=\\\"bruger\\\" value=\\\"$bruger\\\"&gt;&lt;br&gt;\\n\"; \r\n  echo  \"Password: &lt;input name=\\\"pw\\\" value=\\\"$pw\\\"&gt;&lt;br&gt;&lt;br&gt;\\n\"; \r\n  echo  \"&lt;input type=\\\"submit\\\" value=\\\"Kryptér\\\"&gt;\\n\"; \r\n  echo  \"&lt;/form&gt;\";  ?&gt;\r\n&lt;/body&gt;\r\n&lt;/html&gt;[/KODE]\r\n\r\nSå skal skidtet bare uploades også burde det du, hvis du oplever fejl eller andre mangler så skal du være velkommen til sende en email der forklarer dit problem til christofferhp@msn.com.', '2', '38', '', '', '', 'da', '6', '81', '100')") or die("Fejl");
			}elseif($d_gd[type] == "access"){
				if (!file_exists($d_gd[location])){
					if (msgbox("Warning", $d_gd[location] . " does not exist. Do you want to create an empty Access database?", "yesno") == "yes"){
						copy("Data/Access/empty.mdb", $d_gd[location]);
					}else{
						$win_status->CloseWindow();
						msgbox("Warning", "The file " . $d_gd[location] . " could not be found - aborting.", "warning");
						return false;
					}
				}
				
				$state = $this->win_main->dbconn->OpenConn($d_gd[type], $d_gd[location]);
			}else{
				$win_status->CloseWindow();
				echo "Error?";
				exit;
			}
			
			//State will be false, if the connection to the db hasnt been made.
			if (!$state){
				$win_status->CloseWindow();
				msgbox("Warning", "An error occurred, when a connection to the database was made.\n\n" . $this->win_main->dbconn->query_error(), "warning");
				return false;
			}else{
				$this->win_main->TablesUpdate($win_status);
				$this->CloseWindow();
			}
		}
		
		function AddClicked(){
			require_once "win_dbprofiles_edit.php";
			
			$this->window->hide();
			$this->win_dbprofile_edit = new WinDBProfilesEdit($this, "add");
		}
		
		function EditClicked(){
			require_once "win_dbprofiles_edit.php";
			
			$value = $this->clist_profiles->get_value();
			
			if (!$value){
				msgbox("Warning", "You have to choose a profile to edit first.", "warning");
				return false;
			}
			
			$this->window->hide();
			$this->win_dbprofile_edit = new WinDBProfilesEdit($this, "edit");
		}
		
		function DelClicked(){
			$value = $this->clist_profiles->get_value();
			
			if (!$value){
				msgbox("Warning", "You have to choose a profile to edit first.", "warning");
				return false;
			}
			
			if (msgbox("Question", "Do you want to delete the chossen profile '$value[1]'?", "yesno") == "yes"){
				sqlite_query($this->win_main->mydb->conn, "DELETE FROM profiles WHERE nr = '$value[Nr]'");
				$this->UpdateCList();
			}
		}
		
		function CloseWindow(){
			$this->win_main->window->show();
			$this->window->hide();
			unset($this->win_main->win_dbprofiles);
		}
	}
?>