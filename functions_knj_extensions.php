<?
	//The file contains functions for loading PHP-modules on different versions of PHP-installations with different 
	//configurations and different operation systems (takes auto-loading and different OS into mind).
	
	if (strpos(strtolower($_SERVER[OS]), "windows") !== false){
		$knj_os = "windows";
	}else{
		$knj_os = "linux";
	}
	
	function knj_dl($extension){
		global $knj_os;
		
		if (!extension_loaded($extension)){
			if ($knj_os == "windows"){
				$pre = "php_";
				$app = ".dll";
				
				if ($extension == "gtk2"){
					$pre = "php-";
				}
			}else{
				$app = ".so";
				
				if ($extension == "gtk2"){
					$pre = "php_";
				}
			}
			
			if ($extension == "gtk2" && extension_loaded("php-gtk")){
				return false;
			}
			
			dl($pre . $extension . $app);
			return true;
		}
		
		return false;
	}
?>