<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
$task = $props['task'];
//$results = $props['results'];
$pcData = $props['pc_data'];
$results = $pcData['results'];
$fields = $props['fields'];
$tplPath = $props['tpl_path'];
//$workspace = WaicFrame::_()->getModule('workspace');
//var_dump($results);
//$params = WaicUtils::getArrayValue($task, 'params', array(), 2);
//$order = WaicUtils::getArrayValue($task, 'params', array(), 2);
?>
<div class="waic-post-results" data-post="<?php echo esc_attr($pcData['id']); ?>" data-post-status="<?php echo esc_attr($pcData['status']); ?>" data-can-publish="<?php echo $pcData['can_publish'] ? 1 : 0; ?>" data-can-update="<?php echo $pcData['can_update'] ? 1 : 0; ?>">
	<?php 
	foreach ($results as $block => $data) { 
		if ('image_alt' == $block && !isset($results['image'])) {
			$field = 'image';
		} else {
			$field = strpos($block, 'custom') === 0 ? 'custom' : $block;
		}

		if (isset($fields[$field]) && empty($fields[$field]['hidden'])) {
			$fileName = 'adminFieldResults' . waicStrFirstUp($field) . '.php';
			include_once file_exists(stream_resolve_include_path($fileName)) ? $fileName : $tplPath . $fileName; 
			//include 'adminFieldResults' . waicStrFirstUp($field) . '.php';
		} 
	}
	?>
</div>