<?
	//This file contains different gz-functions for making up the lag in PHP's gz-functions (finding the corrent 
	//uncompressed filesize of a GZ-compressed file, which can be very useful, when you want to show status 
	//of ucompressing).
	
	function gzfilesize($fname){
		$fp = fopen($fname, "r");
		
		if (!$fp){
			return false;
		}
		
		if (fseek($fp, -4, SEEK_END)){
			$fsize = false;
		}else{
			$fsize = current(unpack("V", fread($fp, 4)));
		}
		
		fclose($fp);
		
		return $fsize;
	}
?>