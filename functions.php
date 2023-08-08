<?
	//This file contains misc functions like converting danish chars and SQL-value-functions.
	
	function string_oneline($string){
		$string = str_replace("\n", "\\n", $string);
		$string = str_replace("\r", "\\r", $string);
		$string = str_replace("\t", "\\t", $string);
		
		return $string;
	}
	
	function danishchars($string){
		return strtr($string, array(
			"Ã¦" => "æ",
			"Ã¸" => "ø",
			"Ã¥" => "å",
			"Ã†" => "Æ",
			"Ã˜" => "Ø",
			"Ã…" => "Å"
		));
	}
	
	function danishchars_out($string){
		return strtr($string, array(
			"æ" => "Ã¦",
			"ø" => "Ã¸",
			"å" => "Ã¥",
			"Æ" => "Ã†",
			"Ø" => "Ã˜",
			"Å" => "Ã…"
		));
	}
	
	function parse_quotes($string){
		$string = str_replace("'", "\'", $string);
		$string = str_replace("\r", "\\r", $string);
		$string = str_replace("\n", "\\n", $string);
		
		if (substr($string, -1, 1) == "\\" && substr($string, -2, 2) !== "\\\\"){
			$string = substr($string, 0, -1) . "\\\\";
		}
		
		return $string;
	}
	
	function safedir($string){
		$string = str_replace("\\\\", "/", $string);
		$string = str_replace("\\", "/", $string);
		
		return danishchars($string);
	}
	
	function updwin(){
		while(gtk::events_pending()){
			gtk::main_iteration();
		}
	}
?>