<?php
require_once("tools.php");

Class JobQueue{
	var $init_ok=false;
	var $folder_src=false;
	var $chrono_set=false;
	
	function __construct($source,$name){
		//	$source = source file or folder - can be unique for the job or not
		//	$name	= name of the job, should be unique
		//	will create 
		if(!$source){
			trace("JobQueue: source cannot be empty");
			return false;
		}
			
		if(!$name){
			trace("JobQueue: name cannot be empty");
			return false;
		}
		
		if(!file_exists($source)){
			trace("JobQueue: source [$source] cannot be found");
			return false;
		}
		if(is_file($source)){
			$this->folder_src=dirname($source);
		}
		if(is_dir($source)){
			$this->folder_src=dirname($source."/."); // be sure there's no ending / or \
		}
		
		if(!$this->folder_src){
			trace("JobQueue: cannot work with source [$source]");
			return false;
		}
		
		$this->folder_lock=$this->folder_src."/.jq";
		if(!file_exists($this->folder_lock)){
			mkdir($this->folder_lock);
			if(!file_exists($this->folder_lock)){
				trace("JobQueue: cannot create folder [$this->folder_lock] (read-only?)");
				return false;
			}
		}
		$this->folder_log=$this->folder_lock."/log";
		if(!file_exists($this->folder_log)){
			mkdir($this->folder_log);
			if(!file_exists($this->folder_log)){
				trace("JobQueue: cannot create folder [$this->folder_log] (read-only?)");
				return false;
			}
		}
		$this->file_log=$this->folder_log."/jq.".date("Y_m_d").".log";
		if(!file_exists($this->file_log)){
			touch($this->file_log);
			if(!file_exists($this->file_log)){
				trace("JobQueue: cannot create log file [$this->file_log] (read-only?)");
				return false;
			}
		}
		$this->init_ok=true;
		$base=$this->text_cleanup($name);
		$base=substr($base,0,50) . "." . substr(md5($name),0,4);
		$this->file_busy=$this->folder_lock."/$base.busy.txt";
		$this->file_done=$this->folder_lock."/$base.done.txt";
		$this->name=$name;
		trace("JobQueue: job [$name] created in [$this->folder_lock]");
	
	}
	
	function test_busy(){
		if(file_exists($this->file_busy)){
			$this->logline("found BUSY lock for $this->name");
			return true;
		} else {
			$this->logline("(no BUSY lock for $this->name)","DEBUG");
			return false;
		}
	}
	
	function test_done(){
		if(file_exists($this->file_done)){
			$this->logline("found DONE lock for $this->name");
			return true;
		} else {
			$this->logline("(no DONE lock for $this->name)","DEBUG");
			return false;
		}
	}
	
	function test_todo(){
		if(file_exists($this->file_busy)){
			$this->logline("[$this->name] is BUSY - don't process this","DEBUG");
			return false;
		} 
		if(file_exists($this->file_done)){
			$this->logline("[$this->name] is DONE - don't process this","DEBUG");
			return false;
		} 
		$this->logline("[$this->name] is not yet DONE - process it");
		return true;
	}
	
	function set_busy(){
		if($this->test_todo()){
			$timestamp=date("c");
			$hostname=gethostname();
			$username=hw_getusername();
			$os=PHP_OS;
			$jobname=$this->name;
			file_put_contents($this->file_busy,"$timestamp ; $jobname : $username@$hostname ($os) ; JOB IS BUSY");
			$this->logline("JOB STARTED");
		} else {
			$this->logline("JOB ALREADY BUSY OR DONE ","ERROR");
		}
	}
	
	function chrono_start($code=false){
		if(!$code){
			$code=$this->name;
		}
		if(isset($this->chrono_set[$code])){
			trace("WARNING chrono_start: chrono [$code] was already set");
		} 
		$this->chrono_set[$code]=microtime(true);
		$time_0=$this->chrono_set[$code];
		//trace("chrono_start at $time_0");
	}
	
	function chrono_stop($code=false,$report=true,$output=false,$nbitems=1){
		if(!$code){
			$code=$this->name;
		}
		if(isset($this->chrono_set[$code])){
			$chrono_end=microtime(true);
			//trace("chrono_stop at $chrono_end");
			$dur_sec=$chrono_end-$this->chrono_set[$code];
			$this->chrono_set=false;
			if($report){
				$log="CHRONO: task [$code] finished ";
				if($dur_sec<10){
					$dur_est=number_format($dur_sec,3). " sec";
				} else {
					$dur_est=number_format($dur_sec,2). " sec";
				}
				$log.=" in $dur_est ";
				
				if($output AND file_exists($output)){
					if(is_file($output)){
						$outsize=filesize($output);
					}
					if(is_dir($output)){
						$du=cmdline("du.exe \"$output\" ");
						$output=trim($du[0]);
						$outsize=((int)$output)*1024;
						trace("chrono_stop: [$output] -> folder size = $outsize");
					}
					if($outsize){
						$bps=pretty_filesize($outsize/$dur_sec)."/s";
						$log.="[$bps] ";
					}
					if($nbitems>1){
						$tps=($nbitems/$dur_sec);
						if($tps>100){
							$log.="[" . number_format($tps,0)." #/sec] ";
						} elseif($tps>1){
							$log.="[" . number_format($tps,1)." #/sec] ";
						} else {
							$log.="[" . number_format(1/$tps,1)." sec/#] ";
						}
					}
				} 
				echo "$log\n";
			}
		} else {
			trace("ERROR chrono_stop: chrono [$code] was not set");
		}
	}
	
	function foldersize($folder,$extension=""){
		if($folder AND file_exists($folder)){
			if (stristr(PHP_OS, 'WIN')) {
				// use dir
				$output=cmdline("dir /-C \"$folder\\*$extension\" |  | findstr 'File(s)'");
			} else {
				$output=cmdline("du \"$folder\"");
			}
		}
	}
	
	function set_done(){
		if($this->test_busy()){
			$timestamp=date("c");
			$hostname=gethostname();
			$username=hw_getusername();
			$os=PHP_OS;
			$jobname=$this->name;
			file_put_contents($this->file_done,"$timestamp ; $jobname : $username@$hostname ($os) ; JOB IS DONE");
			if(file_exists($this->file_busy)){
				unlink($this->file_busy);
			}
			$this->logline("JOB DONE");
		} else {
			$this->logline("JOB NOT YET BUSY ","ERROR");
		}
	}
	
	function check_output($destin){
		if(!file_exists($destin)){
			trace("ERROR check_output: [$destin] not found");
			return false;
		}
		if(is_file($destin)){
			$fsize=fsize($destin);
		}
	}
	
	private function logline($text,$type="INFO"){
		if($this->init_ok){
			$fl=fopen($this->file_log,"a");
			$timestamp=date("c");
			$hostname=gethostname();
			$username=hw_getusername();
			$jobname=$this->name;
			$os=PHP_OS;
			if($fl){
				fwrite($fl,"$timestamp ; $jobname : $username@$hostname ($os) ; $type ; $text\n");
			}
		}
	}
	
	private function text_cleanup($text){
		$new=strtolower($text);
		$new=preg_replace("#[éèêë]#","e",$new);
		$new=preg_replace("#[àâáëã]#","a",$new);
		$new=preg_replace("#[ìíïî]#","i",$new);
		$new=preg_replace("#[ôöóòõ]#","o",$new);
		$new=str_replace(
			Array("ç","ñ","ß"),
			Array("c","n","ss"),
			$new);
		$new=preg_replace("#[^a-z0-9-_.]#","",$new);
		return $new;
	}
}

if(!function_exists("hw_getusername")){
	function hw_getusername(){
		if(getenv("USERNAME"))	return getenv("USERNAME");
		if(getenv("USER"))	return getenv("USER");
		return false;
	}
}


?>