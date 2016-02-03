<?php 

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(!$_G['uid']) {
	showmessage('not_loggedin', NULL, array(), array('login' => 1));
}

//config
$friends_recommend_num = 20;

//get common data.
$mytags = DB::result_array('SELECT * FROM '.DB::table('plugin_minerva_scribe')." WHERE uid=$_G[uid]");

switch ($_G['gp_action']){
	case 'addscribe':
		//check
		$addtagid = intval($_G['gp_addtagid']);
		if(!$addtagid){
			showmessage('err_tagid_not_vaild', dreferer());
		}
		$taginfo = DB::fetch_first('SELECT * FROM '.DB::table('plugin_minerva_tags')." WHERE id=$addtagid");
		if(!$taginfo){
			showmessage('err_tagid_not_found', dreferer());
		}
		foreach($mytags as $row){
			if($row['tag_id'] == $addtagid){
				showmessage('err_tagid_already_scribed');
			}
		}
		//check done
		DB::insert('plugin_minerva_scribe', array(
			'tag_id' => $taginfo['id'],
			'tag_name' => $taginfo['tag_name'],
			'uid' => $_G['uid'],
			'new' => 0,
			'refresh_timestamp' => $_G['timestamp']
		));
		showmessage('succ_add_tag_scribe_done', dreferer());
		break;
	case 'rmscribe':
		//check 
		$subid = intval($_G['gp_subid']);
		$delinfo = DB::fetch_first('SELECT * FROM '.DB::table('plugin_minerva_scribe')." WHERE subid=$subid");
		if(!$delinfo){
			showmessage('err_del_optid_not_found',dreferer());
		}
		if($delinfo['uid'] != $_G['uid']){
			showmessage('err_cannot_opt_others_scribe',dreferer()); 
		}
		//check end
		DB::delete('plugin_minerva_scribe',"subid=$subid");
		showmessage('succ_del_tag_scribe_done',dreferer());
		break;
	case 'seekmate':
		//check 
		$seektagid = intval($_G['gp_seektagid']);
		if(!$seektagid){
			showmessage('err_tagid_not_vaild', dreferer());
		}
		$taginfo = DB::fetch_first('SELECT * FROM '.DB::table('plugin_minerva_tags')." WHERE id=$seektagid");
		if(!$taginfo){
			showmessage('err_tagid_not_found', dreferer());
		}
		//check end
		$mateinfos = DB::result_array('SELECT uid FROM '.DB::table('plugin_minerva_scribe')." WHERE tag_id=$seektagid");
		$randkeys = array_rand($mateinfos, $friends_recommend_num);
		$member_jar = array();
		foreach($randkeys as $row){
			$seekuidinfo = DB::fetch_first('SELECT uid,username FROM '.DB::table('common_member')." WHERE uid=$row[uid]");
			if(is_array($seekuidinfo)){
				$member_jar[] = $seekuidinfo;
			}
		}
		break;
	default:
		//show my tags list in template.
}

include template('tsdmtag:mytag');

?>