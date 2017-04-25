<?php

require('fever.php');

header('Content-Type: application/json');

// URL of Selfoss instance, WITH leading slash
$GLOBALS['selfoss_url'] = "";
$fever_usr = "admin@test.com";
$fever_pwd = "password";

if (!isset($_GET['api'])) {
	die('{"api_version": 3, "auth": 0}');
}

// Perform authentication
if (isset($_POST['api_key'])) {
	$api_key = md5($fever_usr.':'.$fever_pwd);
	if ($api_key != $_POST['api_key']) {
		die('{"api_version": 3, "auth": 0}');
	}
}
// Client did not provide api_key
else {
	die('{"api_version": 3, "auth": 0}');
}

// Write operations
if (isset($_POST['mark']) && isset($_POST['as'])) {
	if ($_POST['mark'] == 'item') {
		if ($_POST['as'] == 'read') {
			mark("mark", $_POST['id']);
		}

		if ($_POST['as'] == 'saved') {
			mark("starr", $_POST['id']);
		}

		if ($_POST['as'] == 'unsaved') {
			mark("unstarr", $_POST['id']);
		}
	}
}

// Send items
if (isset($_GET['items'])) {

	$items = [];
	$count = 0;
	$finished = false;

	// Perform filtering
	if (isset($_GET['since_id'])) {
		$since_id 	= isset($_GET["since_id"]) ? intval($_GET["since_id"]) : 0;

		do {
			if ($count == 0) {
				$json = file_get_contents($GLOBALS['selfoss_url'] . 'items?items=200');
			}
			else {
				$offset = $count * 200;
				$json = file_get_contents($GLOBALS['selfoss_url'] . 'items?items=200&offset=' . $offset);
			}
			$preItems = json_decode($json, true);
			$items = array_merge($items, $preItems);
			$noOfItems = count($preItems);

			foreach ( $items as $k=>$v ) {
				if ($items[$k]['id'] == ($since_id)) {
					$finished = true;
				}
			}
			$count++;
		} while ($finished == false);

		$filtered = false;
		foreach ( $items as $k=>$v ) {
			if ($items[$k]['id'] > ($since_id)) {
				$filtered = true;
				$wantedItems[] = $items[$k];
			}
		}
		if ($filtered == true && isset($wantedItems)) {
			$items = $wantedItems;
		}
		else {
			$items = [];
		}
	}
	else {
		do {
			if ($count == 0) {
				$json = file_get_contents($GLOBALS['selfoss_url'] . 'items?items=200');
			}
			else {
				$offset = $count * 200;
				$json = file_get_contents($GLOBALS['selfoss_url'] . 'items?items=200&offset=' . $offset);
			}
			$preItems = json_decode($json, true);
			$items = array_merge($items, $preItems);
			$noOfItems = count($preItems);

			if ($noOfItems != 200) {
				$finished = true;
			}
			$count++;
		} while ($finished == false);
	}

	$noOfItems = sizeof($items);

	if ( $noOfItems == 0 ) {
		$object = array('total_items' => $noOfItems, 'items' => $items);
	}
	else {

	foreach ( $items as $k=>$v) {
		$items[$k] ['id'] = (int)$items[$k] ['id'];
		// content -> html
		$items[$k] ['html'] = $items[$k] ['content'];
		unset($items[$k]['content']);

		// source -> feed_id
		$items[$k] ['feed_id'] = (int)$items[$k] ['source'];
		unset($items[$k]['source']);

		// datetime -> created_on_time as unix timestamp
		$items[$k] ['created_on_time'] = (int)strtotime($items[$k] ['datetime']);
		unset($items[$k]['datetime']);

		// link -> url
		$items[$k] ['url'] = $items[$k] ['link'];
		unset($items[$k]['link']);

		// unread -> is_read
		$items[$k] ['is_read'] = $items[$k] ['unread'];
		unset($items[$k]['unread']);
		$items[$k] ['is_read'] = (int)!$items[$k] ['is_read'];

		// unread -> is_read
		$items[$k] ['is_saved'] = (int)$items[$k] ['starred'];
		unset($items[$k]['starred']);

		if (empty($items[$k]['author'])) {
			$items[$k]['author'] = "Unknown";
		}

		// Unused attributes
		unset($items[$k]['icon']);
		unset($items[$k]['uid']);
		unset($items[$k]['thumbnail']);
		unset($items[$k]['tags']);
		unset($items[$k]['updatetime']);
		unset($items[$k]['sourcetitle']);

	}
}
	if (isset($_GET['with_ids'])) {
		$with_ids = urldecode($_GET['with_ids']);
		$with_ids = explode(',', $with_ids);
		foreach ( $items as $k=>$v ) {
			if (in_array($items[$k]['id'], $with_ids)) {
				$wantedItems[] = $items[$k];
			}
		}
		if (isset($wantedItems)){
			$items = $wantedItems;
		}
	}


	$object = array('total_items' => $noOfItems, 'items' => $items);
}

// Send just the ids of unread items
if (isset($_GET['unread_item_ids'])) {
	$object = getUnreadItemIds();
}

// Send just the ids of saved/starred items
if (isset($_GET['saved_item_ids'])) {
	$object = getSavedItemIds();
}

// Send either groups (selfoss:tags) or feeds (selfoss:sources) with feed_groups (tag membership)
if (isset($_GET['groups']) OR isset($_GET['feeds'])) {
	$json = file_get_contents($GLOBALS['selfoss_url'] . 'tags');
	$groups = json_decode($json, true);
	$json = file_get_contents($GLOBALS['selfoss_url'] . 'sources/list');
	$feeds = json_decode($json, true);

	foreach ( $groups as $k=>$v ) {
    	$groups[$k] ['title'] = $groups[$k] ['tag'];

		$id = hexdec(substr(sha1($groups[$k]['color']), 0, 14));
		$groups[$k]['id'] = $id;
    	unset($groups[$k]['color']);
    	unset($groups[$k]['unread']);

		foreach ( $feeds as $x=>$z ) {
			if ($z['tags'] == $groups[$k]['tag']){
				$feedsGroupIds[] = $z['id'];
			}
		}
		unset($groups[$k]['tag']);
		$feedsGroupIdsStr = implode (",", $feedsGroupIds);
		$feedsGroupIds = '';
		$feedsGroup[] =  array('group_id' => $id, 'feed_ids' => $feedsGroupIdsStr);
	}

	if (isset($_GET['feeds'])){
		foreach ( $feeds as $k=>$v ) {
			$feeds[$k] ['id'] = (int)$feeds[$k] ['id'];
			// params/url -> url
			$feeds[$k] ['url'] = $feeds[$k] ['params'] ['url'];
			unset($feeds[$k]['params']);
			$url_scheme = parse_url($feeds[$k] ['url'], PHP_URL_SCHEME);
			$url_host = parse_url($feeds[$k] ['url'], PHP_URL_HOST);
			$url = $url_scheme . '://' . $url_host;
			$feeds[$k]['site_url'] = $url;

			$feeds[$k] ['last_updated_on_time'] = (int)$feeds[$k] ['lastentry'];
			unset($feeds[$k]['lastentry']);

			//icon -> favicon_id
			unset($feeds[$k]['icon']);
			$feeds[$k]['favicon_id'] = $feeds[$k] ['id'];

			$feeds[$k] ['is_spark'] = (int)0;

			// Unused attributes
			unset($feeds[$k]['spout']);
			unset($feeds[$k]['filter']);
			unset($feeds[$k]['tags']);
			unset($feeds[$k]['lastentry']);
			unset($feeds[$k]['error']);
		}
	}
	if (isset($_GET['groups'])) {
		$object = array('groups' => $groups, 'feeds_group' => $feedsGroup);
	}
	else {
		$object = array('feeds' => $feeds, 'feeds_group' => $feedsGroup);
	}
}

// Send favicons; not all clients support this method
if (isset($_GET['favicons'])) {
	$object = getFavicons();
}

// We don't support links
if (isset($_GET['links'])) {
	$links = array();
	$object = array('links' => $links);
}

$response = array('api_version' => 3, 'auth' => 1);
if (!empty($object)) {
	$response = array_merge($response, $object);
}
print(json_encode($response));
?>
