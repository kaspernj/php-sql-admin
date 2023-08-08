<?
	function knj_freadline($fp, $mode = "plain"){
		global $temp;
		
		if ($mode == "plain"){
			$content = $temp . fread($fp, 4096);
		}elseif($mode == "gz"){
			$content = $temp . gzread($fp, 4096);
		}
		
		$pos = strpos($content, "\n");
		unset($temp);
		
		global $temp;
		
		if ($pos !== false){
			$temp = substr($content, $pos + 1);
			return substr($content, 0, $pos);
		}else{
			$temp = $content;
			return knj_freadline($fp);
		}
	}
	
	function knj_feof($fp, $mode = "plain"){
		global $temp;
		
		if ($mode == "plain"){
			if (!$temp && feof($fp)){
				return true;
			}else{
				return false;
			}
		}elseif($mode == "gz"){
			if (!$temp && gzeof($fp)){
				return true;
			}else{
				return false;
			}
		}
	}
?>