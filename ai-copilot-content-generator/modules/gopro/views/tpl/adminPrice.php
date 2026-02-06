<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$props = $this->props;
?>
<div class="waic-gopro-parag">
	<?php echo esc_html('If you’d like to unlock the PRO version of the plugin with advanced features and priority support, please place your order on this page or on', 'ai-copilot-content-generator') . ' <a href="https://aiwuplugin.com/#pricing" target="_blank">' . esc_html('our website', 'ai-copilot-content-generator') . '</a>.'; ?>
</div>
<div class="waic-price-form">
	<div class="waic-price-columns">
		<div class="waic-price-column" id="waicYearlyColumn">
			<div class="waic-column-label">Yearly</div>
			<div class="waic-plan-list">
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="yearly-starter" data-price="49" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=9495&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Starter</div>
						<div class="waic-plan-desc">1 Website</div>
					</div>
					<div class="waic-plan-price">$49</div>
				</label>
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="yearly-standard" data-price="99" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=9499&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Standard</div>
						<div class="waic-plan-desc">5 Websites</div>
					</div>
					<div class="waic-plan-price">$99</div>
				</label>
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="yearly-business" data-price="159" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=16931&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Business</div>
						<div class="waic-plan-desc">25 Websites</div>
					</div>
					<div class="waic-plan-price">$159</div>
				</label>
			</div>
		</div>
		<div class="waic-price-column active" id="waicLifetimeColumn">
			<div class="waic-column-label">Lifetime</div>
			<div class="waic-plan-list">
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="lifetime-starter" data-price="89" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=9496&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Starter</div>
						<div class="waic-plan-desc">1 Website</div>
					</div>
					<div class="waic-plan-price">$89</div>
				</label>
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" checked="" value="lifetime-standard" data-price="149" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=9500&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Standard</div>
						<div class="waic-plan-desc">5 Websites</div>
					</div>
					<div class="waic-plan-price">$149</div>
				</label>
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="lifetime-business" data-price="249" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=16932&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Business</div>
						<div class="waic-plan-desc">25 Websites</div>
					</div>
					<div class="waic-plan-price">$249</div>
				</label>
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="lifetime-agency" data-price="499" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=16933&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Agency</div>
						<div class="waic-plan-desc">100 Websites</div>
					</div>
					<div class="waic-plan-price">$499</div>
				</label>
				<label class="waic-plan-block">
					<input type="radio" name="waicPlan" value="lifetime-enterprise" data-price="999" data-url="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=9498&amp;quantity=1">
					<div class="waic-plan-info">
						<div class="waic-plan-name">Enterprise</div>
						<div class="waic-plan-desc">Unlimited</div>
					</div>
					<div class="waic-plan-price">$999</div>
				</label>
			</div>
		</div>
	</div>
	<a class="wbw-button wbw-button-pro" id="waicPurchaseBtn" target="_blank" href="https://aiwuplugin.com/?add-to-cart=9492&amp;variation_id=9500&amp;quantity=1"><?php esc_html_e('Buy Now', 'ai-copilot-content-generator'); ?></a>
</div>

<?php include_once 'adminContact.php'; ?>