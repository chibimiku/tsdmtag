<?php

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(!$_G['uid']) {
	showmessage('not_loggedin', NULL, array(), array('login' => 1));
}

$tpp = 1;
$page = max(1, intval($_G['gp_page']));
$start_limit = ($page - 1) * $tpp;

//config area
//$tblname_tagitem = 'plugin_tag_tag';
$tblname_tagitem = 'plugin_minerva_tags';
//$tblname_tagthread = 'plugin_tag_thread';
$tblname_tagthread = 'plugin_minerva_index';

if($_G['gp_key'] == 'tag'){
	
	$itid = intval($_G['gp_tid']);
	$mypower = max(1, $_G['adminid']*10);
	$status = 0;
	$itemid = 0;
	$needajax = false;
	
	switch($_G['gp_action']){	
		case 'addtag':
			$needajax = true;
			$input = trim(addslashes($_G['gp_tag']));
			if(!$input){
				//showmessage('input is empty...');
				$status = -1;
				break;
			}			
			
			$threadinfo = DB::fetch_first('SELECT tid,subject FROM '.DB::table('forum_thread')." WHERE tid=$itid");
			if(!$threadinfo){
				$status = -2;
				break;
			}
			
			$taginfo = DB::fetch_first('SELECT id,tag_name FROM '.DB::table('plugin_minerva_tags')." WHERE tag_name='$input'");
			//create new tag if not exists
			if(!$taginfo){
				DB::insert('plugin_minerva_tags', array(
					'tag_name' => $input,
					'count' => 0,
					'type' => 0
				));
				$tagid = DB::insert_id();
			}else{
				$tagid = $taginfo['id'];
			}
			$threadtaginfo = DB::fetch_first('SELECT * FROM '.DB::table('plugin_minerva_index')." WHERE thread_id=$itid AND tag_id=$tagid");
			//insert thread tag data
			if(!$threadtaginfo){
				DB::insert('plugin_minerva_index', array(
					'thread_tid' => $itid,
					'tag_id' => $tagid,
					'tagname' => $input,
					'power' => $mypower,
					'subject' => $threadinfo['subject']
				));
				$itemid = DB::insert_id();
				DB::insert('plugin_tag_log', array(
					'acttype' => 2, 
					'optuid' => $_G['uid'],
					'optusername' => $_G['username'],
					'power' => $mypower,
					'postive' => 1,
					'timestamp' => TIMESTAMP,
					'tid' => $itid,
				));
			}else{
				//modify tag status
				DB::update('plugin_minerva_index', array('power' => $threadtaginfo['power']+$mypower), "id=$threadtaginfo[id]");
				DB::insert('plugin_tag_log', array(
					'acttype' => 1, 
					'optuid' => $_G['uid'],
					'optusername' => $_G['username'],
					'power' => $mypower,
					'postive' => 1,
					'timestamp' => TIMESTAMP,
					'tid' => $itid
				));
			}
			//showmessage("add for tid $itid done...");
			break;
		case 'ranktag':
			$needajax = true;
			$postive = intval($_G['gp_postive']);
			$tagid = intval($_G['gp_tagid']);
			$threadtaginfo = DB::fetch_first('SELECT * FROM '.DB::table('plugin_minerva_index')." WHERE tag_id=$tagid");
			if(!$threadtaginfo){
				//showmessage('cannot find taginfo...');
				$status = -1;
			}
			//check taglog weather tagged.
			$mytaginfo = DB::fetch_first('SELECT logid FROM '.DB::table('plugin_tag_log')." WHERE tid=$threadtaginfo[tid] AND optuid=$_G[uid]");
			if($mytaginfo !== false){
				//showmessage('already tagged...');
				$status = -2;
			}
			if($status == 0){
				if($postive > 0){
					DB::update('plugin_minerva_index', array('power' => $threadtaginfo['power']+$mypower), "id=$threadtaginfo[id]");
				}else{
					DB::update('plugin_minerva_index', array('power' => $threadtaginfo['power']-$mypower), "id=$threadtaginfo[id]");
				}
				DB::insert('plugin_tag_log', array(
					'acttype' => 1, 
					'optuid' => $_G['uid'],
					'optusername' => $_G['username'],
					'power' => $mypower,
					'postive' => $postive,
					'timestamp' => TIMESTAMP,
					'tid' => $itid
				));
			}
			//echo '{"status":'.$status.'}';
			//showmessage('rank tag completed...');
			break;
		case 'searchtag':
			//loadcache
			$tagid = intval($_G['gp_tagid']);
			if(!$tagid){
				showmessage('msg_cannot_find_tagid');
			}
			$taginfo = DB::fetch_first('SELECT * FROM '.DB::table('plugin_minerva_tags')." WHERE id=$tagid");
			if(!$taginfo){
				showmessage('msg_cannot_find_tagid');
			}
			$tids = DB::result_array('SELECT tid,subject FROM '.DB::table('plugin_minerva_index')." WHERE tag_id=$tagid");
			if(!$tids || count($tids) == 0){
				showmessage('msg_tag_empty');
			}
			include template('tsdmtag:search');
			break;
		case 'showthreadtag':
			$tstags = DB::result_array('SELECT * FROM '.DB::table('plugin_minerva_index')." WHERE tid=$itid");
			echo '{"tstags":[';
			echo '"",""';
			echo ']}';
			break;
		case 'searchtag_keyword':
			$tagjar = array();
			$tagidjar = array();
			$findtag = DB::mysqli_escape($_G['gp_findtagname']);
			if(strlen($findtag) < 1){
				showmessage('tsdmtag_err_input_keyword_too_short', 'plugin.php?id=tsdmtag');
			}
			//var_dump($findtag);
			$rs = DB::query('SELECT * FROM '.DB::table('plugin_minerva_tags')." WHERE tag_name LIKE '%".$findtag."%' LIMIT 30");
			while($row = DB::fetch($rs)){
				$tagjar[] = $row;
				$tagidjar[] = $row['id'];
			}
			$wherestr = implode(',', $tagidjar);
			$threadjar = DB::result_array('SELECT * FROM '.DB::table('plugin_minerva_index')." WHERE tag_id IN (".$wherestr.")");
			
			//do uniq by tid
			$tidjar = array();
			$threadinfojar = array();
			foreach($threadjar as $row){
				if(!in_array($row['tid'], $tidjar)){
					$tidjar[] = $row['tid'];
					$threadinfojar[] = $row;
				}
			}
			
			//calc total page.
			$multipage = multi(count($tidjar), $tpp, $page, 'plugin.php?id=tsdmtag&key=tag&action=searchtag_keyword&findtagname='.urlencode($_G['gp_findtagname']));
			
			//take right things in finaljar:$tids
			$tids = array();
			$tidjar_final = array();
			$threadcount = 0;
			$in_jar_count = 0;
			foreach($threadinfojar as $row){
				if($in_jar_count >= $tpp){
					break;
				}
				if($threadcount >= $start_limit){
					$tids[] = $row;
					$tidjar_final[] = $row['tid'];
					++$in_jar_count;
				}
				++$threadcount;
			}
			
			//already got tids... take summary.
			$sum = take_summary($tidjar_final);
			
			include template('tsdmtag:search');
			break;
		default:
			include template('tsdmtag:main');
	}
	if($needajax){
		echo '{"status":'.$status.',"itemid":"'.$itemid.'"}';
	}
}else{
	//default action.
	//get count data.

	$item_count = DB::result_first('SELECT count(*) FROM '.DB::table('plugin_minerva_index'));
	$tag_count = DB::result_first('SELECT count(*) FROM '.DB::table('plugin_minerva_tags'));
	
	include template('tsdmtag:main');
}

function take_summary($tids_array){
	if(!is_array($tids_array)){
		return false;
	}
	$tidsjar = array();
	foreach($tids_array as $tid){
		$tmp = intval($tid);
		if($tmp > 0){
			$tidsjar[] = $tmp;
		}
	}
	if(count($tidsjar) <= 0){
		return false;
	}
	$querystr = implode(',',$tidsjar);
	$sum = DB::result_array('SELECT tid,message FROM '.DB::table('forum_post')." WHERE first=1 AND tid IN (".$querystr.")");
	$result_array = array();
	foreach($sum as $row){
		$result_array[$row['tid']] = $row['message'];
	}
	return $result_array;
}

function gbk_str($str){
	$mb = mb_strlen($str,'gbk');
	$st = strlen($str);
	if($st==$mb)
		return 0; //english
	if($st%$mb==0 && $st%2==0)
		return -1; //only chn char
	return 1; //chaos
}

?>