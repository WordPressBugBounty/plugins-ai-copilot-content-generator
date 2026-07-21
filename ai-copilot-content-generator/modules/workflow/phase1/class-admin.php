<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WaicWorkflowPhase1Admin {
	public static function renderPanel() {
		if ( ! WaicWorkflowPhase1Config::isEnabled( 'ui' ) || ! current_user_can( 'aiwu_edit_workflows' ) ) {
			return '';
		}
		self::enqueue();
		$snapshot = WaicWorkflowPhase1Config::aiSnapshot();
		ob_start();
		?>
		<section id="aiwu-phase1" class="aiwu-p1" data-state="idle" aria-labelledby="aiwu-p1-title">
			<header class="aiwu-p1__header">
				<div><p class="aiwu-p1__eyebrow"><?php esc_html_e( 'Phase 1 · local and default-off', 'ai-copilot-content-generator' ); ?></p><h2 id="aiwu-p1-title"><?php esc_html_e( 'Workflow Library', 'ai-copilot-content-generator' ); ?></h2></div>
				<span class="aiwu-p1__safety"><?php esc_html_e( 'Imports create inactive drafts only', 'ai-copilot-content-generator' ); ?></span>
			</header>
			<div class="aiwu-p1__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Workflow library sections', 'ai-copilot-content-generator' ); ?>">
				<button type="button" role="tab" id="aiwu-tab-library" aria-controls="aiwu-panel-library" aria-selected="true"><?php esc_html_e( 'Library', 'ai-copilot-content-generator' ); ?></button>
				<button type="button" role="tab" id="aiwu-tab-import" aria-controls="aiwu-panel-import" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Import pack', 'ai-copilot-content-generator' ); ?></button>
				<button type="button" role="tab" id="aiwu-tab-installed" aria-controls="aiwu-panel-installed" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Installed packs', 'ai-copilot-content-generator' ); ?></button>
			</div>
			<div id="aiwu-panel-library" role="tabpanel" aria-labelledby="aiwu-tab-library">
				<div class="aiwu-p1__card"><span class="aiwu-p1__kind"><?php esc_html_e( 'Local source', 'ai-copilot-content-generator' ); ?></span><h3><?php esc_html_e( 'Bring a workflow pack', 'ai-copilot-content-generator' ); ?></h3><p><?php esc_html_e( 'Validate a standalone workflow JSON or bounded AIWU pack before anything is written.', 'ai-copilot-content-generator' ); ?></p><button type="button" class="button button-primary" data-open-import><?php esc_html_e( 'Start safe import', 'ai-copilot-content-generator' ); ?></button></div>
			</div>
			<div id="aiwu-panel-import" role="tabpanel" aria-labelledby="aiwu-tab-import" hidden>
				<ol class="aiwu-p1__steps" aria-label="<?php esc_attr_e( 'Import progress', 'ai-copilot-content-generator' ); ?>"><li data-step="selected"><?php esc_html_e( 'Select', 'ai-copilot-content-generator' ); ?></li><li data-step="preflighting"><?php esc_html_e( 'Validate', 'ai-copilot-content-generator' ); ?></li><li data-step="awaiting_confirmation"><?php esc_html_e( 'Review', 'ai-copilot-content-generator' ); ?></li><li data-step="committing"><?php esc_html_e( 'Commit', 'ai-copilot-content-generator' ); ?></li><li data-step="installed"><?php esc_html_e( 'Installed', 'ai-copilot-content-generator' ); ?></li></ol>
				<form id="aiwu-p1-import-form" novalidate>
					<label for="aiwu-p1-input-type"><?php esc_html_e( 'Input type', 'ai-copilot-content-generator' ); ?></label><select id="aiwu-p1-input-type" name="input_type"><option value="workflow_pack_zip"><?php esc_html_e( 'AIWU pack (.zip)', 'ai-copilot-content-generator' ); ?></option><option value="standalone_json_file"><?php esc_html_e( 'Workflow JSON (.json)', 'ai-copilot-content-generator' ); ?></option></select>
					<label for="aiwu-p1-pack"><?php esc_html_e( 'Pack file', 'ai-copilot-content-generator' ); ?></label><input id="aiwu-p1-pack" name="pack" type="file" accept=".zip,.json,application/zip,application/json" required>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Validate pack', 'ai-copilot-content-generator' ); ?></button>
				</form>
				<div id="aiwu-p1-result" class="aiwu-p1__result" tabindex="-1" hidden><h3><?php esc_html_e( 'Server validation', 'ai-copilot-content-generator' ); ?></h3><p data-result-message></p><dl data-result-summary></dl><ul data-result-issues></ul><label data-confirm-wrap hidden><input type="checkbox" data-confirm> <?php esc_html_e( 'I reviewed the server-derived risks and affected data paths.', 'ai-copilot-content-generator' ); ?></label><button type="button" class="button button-primary" data-commit disabled><?php esc_html_e( 'Import as inactive drafts', 'ai-copilot-content-generator' ); ?></button></div>
			</div>
			<div id="aiwu-panel-installed" role="tabpanel" aria-labelledby="aiwu-tab-installed" hidden><p><button type="button" class="button" data-refresh><?php esc_html_e( 'Refresh installed packs', 'ai-copilot-content-generator' ); ?></button></p><div class="aiwu-p1__table-wrap"><table class="widefat striped"><thead><tr><th scope="col"><?php esc_html_e( 'Package', 'ai-copilot-content-generator' ); ?></th><th scope="col"><?php esc_html_e( 'Version', 'ai-copilot-content-generator' ); ?></th><th scope="col"><?php esc_html_e( 'Source and state', 'ai-copilot-content-generator' ); ?></th><th scope="col"><?php esc_html_e( 'Actions', 'ai-copilot-content-generator' ); ?></th></tr></thead><tbody data-installed><tr><td colspan="4"><?php esc_html_e( 'Load installed packs to begin.', 'ai-copilot-content-generator' ); ?></td></tr></tbody></table></div></div>
			<?php if ( WaicWorkflowPhase1Config::isEnabled( 'ai' ) ) : ?>
			<div class="aiwu-p1__ai" aria-labelledby="aiwu-ai-title"><h3 id="aiwu-ai-title"><?php esc_html_e( 'AI draft builder', 'ai-copilot-content-generator' ); ?></h3><p><?php echo esc_html( sprintf( __( 'Pinned provider: %1$s · model: %2$s. The result is never published or run.', 'ai-copilot-content-generator' ), isset( $snapshot['provider'] ) ? $snapshot['provider'] : '', isset( $snapshot['model_id'] ) ? $snapshot['model_id'] : '' ) ); ?></p><label for="aiwu-ai-intent"><?php esc_html_e( 'Workflow intent (do not include personal data or secrets)', 'ai-copilot-content-generator' ); ?></label><textarea id="aiwu-ai-intent" maxlength="8192" rows="4"></textarea><button type="button" class="button" data-ai-compile><?php esc_html_e( 'Generate safe draft preview', 'ai-copilot-content-generator' ); ?></button><div data-ai-preview hidden></div></div>
			<?php endif; ?>
			<p id="aiwu-p1-live" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function enqueue() {
		$base = plugins_url( 'assets/', __FILE__ );
		$version = WaicWorkflowPhase1Bootstrap::version();
		wp_enqueue_style( 'aiwu-workflow-phase1', $base . 'admin-phase1.css', array(), $version );
		wp_enqueue_script( 'aiwu-workflow-phase1', $base . 'admin-phase1.js', array(), $version, true );
		wp_localize_script( 'aiwu-workflow-phase1', 'AIWU_WORKFLOW_PHASE1', array(
			'root' => esc_url_raw( rest_url( 'aiwu/v1/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ),
			'aiEnabled' => WaicWorkflowPhase1Config::isEnabled( 'ai' ),
			'snapshotId' => isset( WaicWorkflowPhase1Config::aiSnapshot()['snapshot_id'] ) ? WaicWorkflowPhase1Config::aiSnapshot()['snapshot_id'] : '',
			'i18n' => array( 'working' => __( 'Working…', 'ai-copilot-content-generator' ), 'blocked' => __( 'The server blocked this action.', 'ai-copilot-content-generator' ), 'installed' => __( 'Pack installed as inactive drafts.', 'ai-copilot-content-generator' ), 'uninstallConfirm' => __( 'Preview uninstall impact for this pack?', 'ai-copilot-content-generator' ) ),
		) );
	}
}
