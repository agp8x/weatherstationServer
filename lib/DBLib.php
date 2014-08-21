<?php

//	MySQL wrapper OOP
//	Copyright (C) 2010  Clemens Klug (webmaster@clemensklug.de)
//	This program comes with ABSOLUTELY NO WARRANTY; for details see LICENSE.txt
//	This is free software, and you are welcome to redistribute it under certain
//	conditions; see LICENSE.txt for details.

#25.08.11 bug in select fixed (multiple from)+where without ''	#v2i
#26.07.12 select range added (not only)			#v3
#08.08.12 OOP, proper Link handling			#v4
#25.08.12 construct;toString;set/getPrefix;noExecute added, LinkHandling fixed	#v5
#04.09.12 various improvments				#v5.2
#04.02.14 port to mysqli 					#v6.1

class DBLib{

	private $dbLink;
	private $devmode=false;
	private $dbprefix="";
	private $connected=false;
	const   version=6.1;
	private $dbinfo=null;
	private $execute=true;
	
	public function __construct($host="",$user="",$pass="",$database=""){
		if(($host!="" || $user!="" || $pass!="" || $database!="")){
			$this->connect($host,$user,$pass,$database);
		}else{
			$this->dbLink=new mysqli();
		}
	}
	public function __destruct(){
		$this->close();
	}
	
	public function connect($host,$user,$pass,$database){
		if(!$this->checkExecute('connect')){
			$this->connected=true;
			$this->dbinfo=array('user'=>'noExecute','host'=>'noExecute','dbbase'=>'noExecute');
			return true;
		}
		$this->dbLink=new mysqli($host, $user, $pass, $database);
		if ($this->dbLink->connect_errno != 0) {
			$this->echoDev('Connection failed!');
			$this->connected=false;
		}else{
			$this->connected=true;
			$this->dbinfo=array('user'=>$user,'host'=>$host,'dbbase'=>$database);
		}
		return $this->connected;
	}

	public function close(){
		if ($this->dbLink instanceof mysqli) {
			$this->connected=$this->dbLink->close();
			$this->dbLink=null;
		}
		if(!$this->checkExecute('close')){
			return false;
		}
	}

	public function update($table, $values, $identifier=array(), $increment=false,$escapeIdentifier=true) {
		if (empty($values) || empty($table) || !$this->connected) {
			return false;
		}
		$where=$this->convertIdentifier($identifier,$escapeIdentifier);
		$dbTarget = "";
		if (!$increment) {
			$sql = "UPDATE `" . $this->dbEscape($this->tableName($table)) . "` SET ";
			foreach ($values as $key2 => $value2) {
				$sql.=" `" . $key2 . "`='" . $value2 . "',";
			}
			$sql = rtrim($sql, ",");
			$sql.=$where;
		} else {
			$sql = "UPDATE `" .$this-> dbEscape($this->tableName($table)) . "` SET `" .
					$this->dbEscape($values) . "`=`" . $this->dbEscape($values) . "`+" .
					$this->dbEscape($increment) . $where;
		}
		$this->echoDev($sql);
		if(!$this->checkExecute($sql)){
			return false;
		}
		return $this->executeSQL($sql);
	}

	public function insert($table, $values) {
		if (!is_array($values) || empty($values) || !$this->connected) {
			return false;
		}
		$keys = "";
		$content = "";
		foreach ($values as $key => $value) {
			$keys.= "`" . $this->dbEscape($key) . "`,";
			$content.= "'" . $this->dbEscape($value) . "',";
		}
		$keys = rtrim($keys, ",");
		$content = rtrim($content, ",");
		$sql = "INSERT INTO `" . $this->tableName($this->dbEscape($table)) ."`".
		" (" . $keys . ") VALUES (" . $content . ")";
		$this->echoDev($sql);
		if(!$this->checkExecute($sql)){
			return false;
		}
		return $this->executeSQL($sql);
	}
	public function delete($table,$identifier,$extra="",$escapeIdentifier=true){
		if(empty($table) || !$this->connected){
			return false;
		}
		$where=$this->convertIdentifier($identifier,$escapeIdentifier);
		$dbTarget = "";
		$sql="DELETE FROM `".$this->tableName($this->dbEscape($table))."`".$where." ".$this->dbEscape($extra);
		$this->echoDev($sql);
		if(!$this->checkExecute($sql)){
			return false;
		}
		return $this->executeSQL($sql);
	}

	public function select($table, $values, $identifier=array(), $extra="",$escapeIdentifier=true) {
		if (empty($values) || empty($table) || !$this->connected) {
			return false;
		}
		$output = array();
		$dbTarget=$this->convertTable($table);
		$where=$this->convertIdentifier($identifier,$escapeIdentifier);
		if(!('range'===$escapeIdentifier)){	//tricky booleans...
			$extra=$this->dbEscape($extra);
		}
		$sql = "SELECT " . $this->dbEscape($values) . " FROM " . $dbTarget . $where ." ". $extra;
		$this->echoDev($sql);
		if(!$this->checkExecute($sql)){
			return false;
		}
		$result = $this->dbLink->query($sql);
		if ($this->dbLink->errno == 0) {
			while($out=$result->fetch_assoc()){
				$output[]=$out;
			}
			if(sizeof($output)==0){
				return false;
			}
			return($output);
		} else {
			$this->echoDev("query failed: ".$this->dbLink->error);
			return false;
		}
	}

	private function executeSQL($sql){
		$this->dbLink->query($sql);
		if ($this->dbLink->errno == 0) {
			return true;
		}else{
			return false;
		}
	}

	public function tableName($table) {
		if(strlen($this->dbprefix)==0){
			return $table;
		}else{
			return($this->dbprefix .'_'. $table);
		}
	}

	public function dbEscape($string) {
		if($this->connected && $this->execute){
			return($this->dbLink->escape_string($string));
		}else{
			return $string;
		}
	}

	public function selectRange($table, $values, $range, $extra="",$escapeIdentifier=true) {
		$rangeStatement="";
		if(is_array($range) && !empty($range)){
			$rangeStatement="WHERE `".$this->dbEscape($range[0])."` BETWEEN ";
			if($escapeIdentifier){
				$rangeStatement.="'".$this->dbEscape($range[1])."' AND '".$this->dbEscape($range[2])."'";
			}else{
				$rangeStatement.="`".$this->dbEscape($range[1])."` AND `".$this->dbEscape($range[2])."`";
			}
			return $this->select($table,$values,array(),$rangeStatement.$this->dbEscape($extra),'range');
		}else{
			return false;
		}
	}
	
	public function __toString(){
		$string="";
		$noExecute=($this->execute===false) ? "noExecute- " : "";
		if($this->connected){
			$string=$noExecute."User '".$this->dbinfo['user']."' connected to Database '".$this->dbinfo['dbbase']."' at Host '".$this->dbinfo['host']."', using DBLib v".$this::version;
		}else{
			$string=$noExecute."--not connected-- DBLib v".$this::version;
		}
		if($this->devmode){
			$string.="_dev_";
		}
		return $string."\n";
	}
	
	public function toggleDevmode(){
		$this->devmode=!$this->devmode;
	}
	
	public function isDevmode(){
		return $this->devmode;
	}
	
	public function setNoExecute($set=false){
		$this->execute=true;
		if($set===true){//involve devmode setting??
			$this->execute=false;
		}
	}
	
	public function setPrefix($prefix){
		$this->dbprefix=$prefix;
	}
	
	public function getPrefix(){
		return $this->dbprefix;
	}
	
	public function isConnected(){
		return $this->connected;
	}
	private function convertIdentifier($identifier,$escapeIdentifier=true){
		$where = "";
		if (is_array($identifier) && !empty($identifier)) {
			$where = " WHERE true";
			foreach ($identifier as $key => $value) {
				if($escapeIdentifier){
					$where.= " AND `" . $this->dbEscape($key) . "`='" . $this->dbEscape($value) . "'";
				}else{
					$where.= " AND `" . $this->dbEscape($key) . "`=`" . $this->dbEscape($value) . "`";
				}
			}
		}
		return $where;
	}
	private function checkExecute($message){
		if($this->execute===false){
			echo "<!--NoExecute-\"".$message."\"-->\n";
			return false;
		}
		return true;
	}
	private function echoDev($message){
		if ($this->devmode) {
			echo "<!--" . $message . "--> \n";
		}
	}
	private function convertTable($tables){
		$dbTarget="";
		if (is_array($tables)) {
			foreach ($tables as $table) {
				$dbTarget .= ",`" . $this->tableName($this->dbEscape($table))."`";
			}
			$dbTarget = ltrim($dbTarget, ",");
		} else {
			$dbTarget = "`".$this->tableName($this->dbEscape($tables))."`";
		}
		return $dbTarget;
	}
}
//EOF
?>
