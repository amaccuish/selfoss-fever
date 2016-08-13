<?php

header('Content-Type: application/json');

// URL of Selfoss instance, WITH leading slash
$selfoss_url = "";
$fever_usr = "admin@test.com";
$fever_pwd = "password";

if (!isset($_GET['api'])) {
	die();
}

if (isset($_POST['api_key'])) {
	$api_key = md5($fever_usr.':'.$fever_pwd);
	if ($api_key != $_POST['api_key']) {
		die('{"api_version": 3, "auth": 0}');
	}
}
else {
	die('{"api_version": 3, "auth": 0}');
}

if (isset($_POST['mark']) && isset($_POST['as'])) {
	if ($_POST['mark'] == 'item' && $_POST['as'] == 'read') {
		$url = $selfoss_url . 'mark/' . $_POST['id'];
		$data = array('' => '');

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
//				'content' => http_build_query($data)
			)
		);

		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
	}

	if ($_POST['mark'] == 'item' && $_POST['as'] == 'saved') {
		$url = $selfoss_url . 'starr/' . $_POST['id'];
		$data = array('' => '');

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
//				'content' => http_build_query($data)
			)
		);

		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
	}

	if ($_POST['mark'] == 'item' && $_POST['as'] == 'unsaved') {
		$url = $selfoss_url . 'unstarr/' . $_POST['id'];
		$data = array('' => '');

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
//				'content' => http_build_query($data)
			)
		);

		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
	}
}
if (isset($_GET['items'])) {

	$json = file_get_contents($selfoss_url . 'items?items=200&type=unread');

	$items = json_decode($json, true);

	// Get noOfItems before further filtering
	$noOfItems = count($items);

	foreach ( $items as $k=>$v ) {
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

		// Unused attributes
		unset($items[$k]['icon']);
		unset($items[$k]['uid']);
		unset($items[$k]['thumbnail']);
		unset($items[$k]['tags']);
		unset($items[$k]['updatetime']);
		unset($items[$k]['sourcetitle']);

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

	// We don't implement this
	if (isset($_GET['since_id'])) {
		$items = array();
	}


	$object = array('total_items' => $noOfItems, 'items' => $items);
}

if (isset($_GET['unread_item_ids'])) {
	$json = file_get_contents($selfoss_url . 'items?type=unread');
	$items = json_decode($json, true);
/*
  $filter_array = array(
  	'unread' => 1
	);

	// filter the array
	$items= array_filter($items, function ($val_array) use ($filter_array) {
    $intersection = array_intersect_assoc($val_array, $filter_array);
    return (count($intersection)) === count($filter_array);
	});
*/
	// Get ids
	foreach ($items as $key => $value) {
		$items_list[] = $value['id'];
	}

	// Needs to be comma separated list
	$items_list = implode (",", $items_list);
	$object = array('unread_item_ids' => $items_list);
}

if (isset($_GET['saved_item_ids'])) {
	$json = file_get_contents($selfoss_url . 'items?type=starred');
	$items = json_decode($json, true);

	// Get ids
	foreach ($items as $key => $value) {
		$items_list[] = $value['id'];
	}

	// Needs to be comma separated list
	$items_list = implode (",", $items_list);
	$object = array('saved_item_ids' => $items_list);
}


if (isset($_GET['groups']) OR isset($_GET['feeds'])) {
	$json = file_get_contents($selfoss_url . 'tags');
	$groups = json_decode($json, true);
	$json = file_get_contents($selfoss_url . 'sources/list');
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

if (isset($_GET['favicons'])) {
	$json = file_get_contents($selfoss_url . 'sources/list');
	$feeds = json_decode($json, true);

	foreach ( $feeds as $k=>$v ) {
		if (!empty($v['icon'])) {
			$favicon_id = (int)$v['id'];
			$url = $selfoss_url . 'favicons/' . $v['icon'];
			$favicon = base64_encode(file_get_contents($url));
			$obj = array('id' => $favicon_id, 'data' => image_type_to_mime_type(exif_imagetype($v['icon'])) . ';base64,' . $favicon);
			$favicons[] = $obj;
		}
	}
// print_r($favicons);
	$object = array('favicons' => $favicons);
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
