<?php
#Wei Shi#
#N10161894#
#ws1196# 
#---------------------------open suberblock and read data------------------------------#
$superblock_handle = fopen("fusedata.0", "r");
$superblock_str = fread($superblock_handle, filesize("fusedata.0"));
$superblock_array = split(",", $superblock_str);
fclose($superblock_handle);

$creationTime_str = $superblock_array[0];
$mounted_str = $superblock_array[1];
$devID_str = $superblock_array[2];
$freeStart_str = $superblock_array[3];
$freeEnd_str = $superblock_array[4];
$root_str = $superblock_array[5];
$maxBlocks_str = $superblock_array[6];

$freeStart_arr = split(":", $freeStart_str);
$freeStart_num = $freeStart_arr[1];

$freeEnd_arr = split(":", $freeEnd_str);
$freeEnd_num = $freeEnd_arr[1];

$maxBlocks_arr = split(":", $maxBlocks_str);
$maxBlocks = $maxBlocks_arr[1];

$devID_arr = split(":", $devID_str);
$devID = $devID_arr[1];

$creationTime_arr = split(":", $creationTime_str);
$creationTime = $creationTime_arr[1];

$root_arr = split(":", $root_str);
$root = $root_arr[1];
#---------------------------open suberblock and read data------------------------------#


#-------------------------------------check the device ID----------------------------------------------#
if($devID != 20){
	echo "Invild device ID\n";
}
#-------------------------------------check the device ID----------------------------------------------#


#-------------------------------check the creation time in the superblock----------------------------------#
if($creationTime - time() > 0){
	echo "Invild creation time\n";
}
#-------------------------------check the creation time in the superblock----------------------------------#


#------------------------store all contents of free block list----------------------------------#
$allFreeBlock_arr = array();
for($i = $freeStart_num; $i <= $freeEnd_num; $i++){
	$freeblock_handle = fopen("fusedata.".$i, "r");
	$freeblock_str = fread($freeblock_handle, filesize("fusedata.".$i));
	$freeblock_str = str_replace(" ", "", $freeblock_str); //delete all " " spaces in the string
	$allFreeBlock_arr = array_merge($allFreeBlock_arr, split(",", $freeblock_str)); 
}
#------------------------store all contents of free block list----------------------------------#


#traverse all directories, store all files and file inodes in an array and all directories in an array# 
#check all atime, ctime and mtime if they are in the future and check linkcount if they match#
static $dirBlock_arr = array();
$dirBlock_arr["root"] = $root;
static $fileinode_arr = array();
function getDir($root_para){
	$dir_handle = fopen("fusedata.".$root_para, "r");
	$dir_content_str = fread($dir_handle, filesize("fusedata.".$root_para));
	$nothing = split(": {", $dir_content_str);
	$inodeInfo_str = str_replace("}", "", $nothing[1]);
	$linkInfo_arr = split(", ", $inodeInfo_str);
	$dir_content_arr = split(", ", $dir_content_str);
	$dir_linkcount_arr = split(": ", $dir_content_arr[7]);
	$dir_linkcount = $dir_linkcount_arr[1];
	$dir_atime_arr = split(": ", $dir_content_arr[4]);
	$dir_ctime_arr = split(": ", $dir_content_arr[5]);
	$dir_mtime_arr = split(": ", $dir_content_arr[6]);
	$dir_atime = $dir_atime_arr[1];
	$dir_ctime = $dir_atime_arr[1];
	$dir_mtime = $dir_atime_arr[1];

	fclose($dir_handle);

#------------check time and linkcount in all directories-----------------------#
	if($dir_atime - time() > 0 or $dir_ctime - time() > 0 or $dir_mtime - time() > 0){
		echo "Invalid time!\n";
	}
	
	if($dir_linkcount != sizeof($linkInfo_arr)){
		echo "Linkcount doesn't match!\n";
	}
#------------check time and linkcount in all directories-----------------------#

	foreach ($linkInfo_arr as $value) {
		# code...
		$dot = 0;
		$dotdot = 0;
		if ($value[0] == "f") { //This is a file inode, so we store the file name and inode block number
			# code...
			$info = split(":", $value);
			$fileinode_arr[$info[1]] = $info[2];

		}elseif ($value[0] == "d") { //This is a directory
			# code...
			$info = split(":", $value);
			if($info[1] == "."){ //if include "."
				$dot = 1;
			}elseif($info[1] == ".."){ //if include ".."
				$dotdot = 1;
			}else{ //other directory
				$dirBlock_arr[$info[1]] = $info[2]; //store this directory
				getDir($info[2]); //recursion
			}
		}

#----------------check if all directories contain "." and ".."-----------------#
		if($dot == 0 or $dotdot == 0){
			echo "Invalid directory!\n";
		}
#----------------check if all directories contain "." and ".."-----------------#

	}

}

getDir($root);

#----------------check if directories are in the free block list-----------------#
foreach ($dirBlock_arr as $value) {
	# code...
	if(in_array($value, $allFreeBlock_arr)){
		echo "The block #".$value." is not free!\n";
	}
}
#----------------check if directories are in the free block list-----------------#
#traverse all directories, store all files and file inodes in an array and all directories in an array# 
#check all atime, ctime and mtime if they are in the future and check linkcount if they match#


foreach ($fileinode_arr as $value) {
	# code...
#---------------------open file inode and read data--------------------------------#
	$file_handle = fopen("fusedata.".$value, "r");
	$file_str = fread($file_handle, filesize("fusedata.".$value));
	fclose($file_handle);
	$file_str = str_replace("{", "", $file_str);
	$file_str = str_replace("}", "", $file_str);
	$file_arr = split(", ", $file_str);

	$file_size_arr = split(":", $file_arr[0]);
	$file_size = $file_size_arr[1];

	$file_indloc_arr = split(" ", $file_arr[8]);
	$file_indirect_arr = split(":", $file_indloc_arr[0]);
	$file_indexloc_arr = split(":", $file_indloc_arr[1]);
	$file_indirect = $file_indloc_arr[1];
	$file_indexloc = $file_indexloc_arr[1];

	$file_atime_arr = split(":", $file_arr[5]);
	$file_ctime_arr = split(":", $file_arr[6]);
	$file_mtime_arr = split(":", $file_arr[7]);
	$file_atime = $file_atime_arr[1];
	$file_ctime = $file_ctime_arr[1];
	$file_mtime = $file_mtime_arr[1];
#---------------------open file inode and read data--------------------------------#


#--------------------------check time in a file---------------------------------#
	if($file_atime - time() > 0 or $file_ctime - time() > 0 or $file_mtime - time() > 0){
		echo "Invalid time!\n";
	}
#--------------------------check time in a file---------------------------------#	


#--------------------------check file size and indirect---------------------------#
#-----------------check if file blocks in the free block list ------------------------#
	if($file_indirect == 0){ 
		if($file_size > 4096){
			echo "Invalid file size!\n";
		}
		if(in_array($value, $allFreeBlock_arr) or in_array($file_indexloc, $allFreeBlock_arr)){
			echo "The block #".$value." is not free!\n";
		}
	}elseif($file_indirect != 0){
		$index_handle = fopen("fusedata.".$file_indexloc, "r");
		$index_str = fread($index_handle, filesize("fusedata.".$file_indexloc));
		fclose($index_handle);
		$index_arr = split(",", $index_arr);
		$index_length = sizeof($index_arr);

		if($file_size > 4096 * $index_length or $file_size < 4096 * ($index_length - 1)){
			echo "Invalid file size!\n";
		}
		foreach ($index_arr as $value) {
			# code...
			if(in_array($value, $allFreeBlock_arr)){
				echo "The block #".$value." is not free!\n";
			}
		}
	}
#-----------------check if file blocks in the free block list ------------------------#
#--------------------------check file size and indirect---------------------------#

}

?>
