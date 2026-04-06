<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$props = $this->props;
$task = $props['task'];
$pcData = $props['pc_data'];
$results = $pcData['results'];
$fields = $props['fields'];
?>
<div class="waic-post-results" data-post="<?php echo esc_attr($pcData['id']); ?>" data-post-status="<?php echo esc_attr($pcData['status']); ?>" data-can-publish="<?php echo $pcData['can_publish'] ? 1 : 0; ?>" data-can-update="<?php echo $pcData['can_update'] ? 1 : 0; ?>">
	<?php 
	foreach ($results as $block => $data) { 
		$field = strpos($block, 'custom') === 0 ? 'custom' : $block;
		if (isset($fields[$field])) {
			include 'adminFieldResults' . waicStrFirstUp($field) . '.php';
		}
	}
	?>
</div>
<?php 
// phpcs:enable
