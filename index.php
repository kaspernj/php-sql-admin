<?
	//find the current dir, where we are running from.
	chdir(dirname(__FILE__));
	
	//load extensions
	require_once "functions_knj_extensions.php";
	knj_dl("mysql");
	knj_dl("gtk2");
	knj_dl("pdo");
	knj_dl("sqlite");
	knj_dl("pgsql");
	
	//knj ext stuff
	require_once "functions_knj_msgbox.php";
	require_once "functions_knj_makesql.php";
	require_once "class_knj_clist.php";
	
	//misc functions, database class and the main window
	require_once "functions.php";
	require_once "DBConn.php";
	require_once "win_main.php";
	
	//parse skin
	Gtk::rc_parse("gtkrc");
	
	//start the program and gtk-mainloop.
	$win_main = new WinMain();
	Gtk::main();
?>