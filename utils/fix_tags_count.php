<?php 

//fix_tags_count.php
//update count data for minerva_tags
//chibimiku@TSDM.net

define('IN_MAIN', true);

require('meekrodb.inc.php');
require('conf.inc.php');

//tbl names
define('tblname_index', TABLE_PRE.'_plugin_minerva_index');
define('tblname_tags', TABLE_PRE.'_plugin_minerva_tags');
define('tblname_thread', TABLE_PRE.'_forum_thread');

$starttime = intval(time());
echo "Task started at:$starttime\n";
do_base();

$endtime = intval(time());
echo "Loop finished at:$endtime\n";
$cost = $endtime - $starttime;

echo "Task done... cost:".$cost." ms \n";

function do_base(){
	//get tags
	$tagsinfo = DB::query('SELECT * FROM '.tblname_tags);
	$docount = 0;
	foreach($tagsinfo as $tag){
		if($docount % 2000 == 0){
			echo "Proc... $docount \n";
		}
		$num = DB::queryFirstField('SELECT count(*) FROM '.tblname_index." WHERE tag_id=".$tag['id']);
		DB::update(tblname_tags,array('count' => $num),"id=".$tag['id']);
		++$docount;
	}
}
?>