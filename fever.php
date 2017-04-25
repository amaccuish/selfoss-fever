<?php

function mark($action, $id) {
    	$url = $GLOBALS['selfoss_url'] . $action . '/' . $id;
		$data = array('' => '');

		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
			)
		);

		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
}

function getFavicons() {
    $json = file_get_contents($GLOBALS['selfoss_url'] . 'sources/list');
	$feeds = json_decode($json, true);

	foreach ( $feeds as $k=>$v ) {
		if (!empty($v['icon'])) {
			$favicon_id = (int)$v['id'];
			$url = $GLOBALS['selfoss_url'] . 'favicons/' . $v['icon'];
			$favicon = base64_encode(file_get_contents($url));
			$obj = array('id' => $favicon_id, 'data' => image_type_to_mime_type(exif_imagetype($v['icon'])) . ';base64,' . $favicon);
			$favicons[] = $obj;
		}
	}

	$object = array('favicons' => $favicons);
    return $object;
}

function getSavedItemIds() {
    $json = file_get_contents($GLOBALS['selfoss_url'] . 'items?type=starred');
	$items = json_decode($json, true);

	// Get ids
	foreach ($items as $key => $value) {
		$items_list[] = $value['id'];
	}

	// Needs to be comma separated list
	$items_list = implode (",", $items_list);
	$object = array('saved_item_ids' => $items_list);
    return $object;
}

function getUnreadItemIds() {
    	$json = file_get_contents($GLOBALS['selfoss_url'] . 'items?type=unread');
	$items = json_decode($json, true);

	if (empty($items)) {
		$object = array('unread_item_ids' => '');
	}
	else {
		// Get ids
		foreach ($items as $key => $value) {
			$items_list[] = $value['id'];
		}

		// Needs to be comma separated list
		$items_list = implode (",", $items_list);

		$object = array('unread_item_ids' => $items_list);
	}
    return $object;
}
