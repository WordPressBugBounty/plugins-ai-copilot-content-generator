// Generated JavaScript lives in assets/js/admin.insights.js. Run `npm run build` from modules/insights.
type NumericValue = number | string | null | undefined;
type FormValueMap = Record<string, string>;
type I18nMap = Record<string, string | undefined>;
type PlainRecord = Record<string, unknown>;
type InsightsTab = 'overview' | 'problems' | 'outcomes' | 'usage' | 'kb-attribution' | 'commerce-gaps' | 'mcp-audit' | 'actions' | 'conversations';

interface WaicAjaxGlobals {
	ajaxurl: string;
	waicNonce: string;
}

interface SerializedField {
	name: string;
	value: string;
}

interface JQueryEvent {
	target: EventTarget | null;
	key?: string;
	preventDefault(): void;
}

type JQueryEventHandler = (this: Element, event: JQueryEvent) => void;

interface JQueryObject {
	[index: number]: Element | HTMLFormElement | undefined;
	length: number;
	addClass(className: string): JQueryObject;
	removeClass(className: string): JQueryObject;
	toggleClass(className: string, state?: boolean): JQueryObject;
	attr(name: string): string | undefined;
	attr(name: string, value: string | number | boolean): JQueryObject;
	attr(attrs: Record<string, string | number | boolean>): JQueryObject;
	prop(name: string, value: boolean): JQueryObject;
	text(): string;
	text(value: string | number): JQueryObject;
	val(): unknown;
	val(value: string): JQueryObject;
	empty(): JQueryObject;
	find(selector: string): JQueryObject;
	on(eventName: string, handler: JQueryEventHandler): JQueryObject;
	on(eventName: string, selector: string, handler: JQueryEventHandler): JQueryObject;
	off(eventName?: string): JQueryObject;
	append(...contents: Array<JQueryObject | Node | string>): JQueryObject;
	appendTo(target: JQueryObject): JQueryObject;
	data(name: string): unknown;
	serializeArray(): SerializedField[];
	each(callback: (this: Element, index: number, element: Element) => void): JQueryObject;
	trigger(eventName: string): JQueryObject;
}

interface AjaxSettings {
	url: string;
	method: 'POST' | 'GET';
	data?: PlainRecord | FormData;
	dataType: 'json';
	timeout: number;
	processData?: boolean;
	contentType?: boolean;
}

interface AjaxRequest<T> {
	done(callback: (response: T) => void): AjaxRequest<T>;
	fail(callback: (xhr: unknown, textStatus?: string) => void): AjaxRequest<T>;
	always(callback: () => void): AjaxRequest<T>;
}

interface AjaxWhen<TFirst, TSecond> {
	done(callback: (first: [TFirst, string, unknown], second: [TSecond, string, unknown]) => void): AjaxWhen<TFirst, TSecond>;
	fail(callback: (xhr: unknown, textStatus?: string) => void): AjaxWhen<TFirst, TSecond>;
	always(callback: () => void): AjaxWhen<TFirst, TSecond>;
}

interface JQueryStatic {
	(ready: () => void): JQueryObject;
	(selector: string | Element | Document | Window | JQueryObject): JQueryObject;
	ajax<T>(settings: AjaxSettings): AjaxRequest<T>;
	extend(target: PlainRecord, ...sources: Array<PlainRecord | undefined>): PlainRecord;
	when<TFirst, TSecond>(first: AjaxRequest<TFirst>, second: AjaxRequest<TSecond>): AjaxWhen<TFirst, TSecond>;
}

interface ChartInstance {
	destroy(): void;
}

interface ChartDataset {
	label: string;
	data: number[];
	borderColor: string;
	backgroundColor: string;
	fill?: boolean;
	tension?: number;
}

interface ChartConfig {
	type: 'line' | 'bar';
	data: {
		labels: string[];
		datasets: ChartDataset[];
	};
	options?: PlainRecord;
}

interface ChartConstructor {
	new (canvas: Element, config: ChartConfig): ChartInstance;
}

interface Window {
	WAIC_INSIGHTS_I18N?: I18nMap;
	Chart?: ChartConstructor;
}

interface CostFields {
	cost_display?: string;
	display_cost?: string;
	cost_display_amount?: NumericValue;
	cost?: NumericValue;
}

interface OverviewKpis extends CostFields {
	conversations?: NumericValue;
	events?: NumericValue;
	cost_per_event_display?: string;
	cost_per_event?: NumericValue;
	low_conf?: NumericValue;
}

interface TrendRow extends CostFields {
	day?: string;
}

interface FeatureBreakdownRow extends CostFields {
	label?: string;
	key?: string;
	events_count?: NumericValue;
	total_tokens?: NumericValue;
}

interface AttentionRow {
	label?: string;
}

interface OverviewData {
	kpis?: OverviewKpis;
	trend?: TrendRow[];
	breakdowns?: {
		features?: FeatureBreakdownRow[];
	};
	attention?: AttentionRow[];
}

interface UsageChartRow extends CostFields {
	day?: string;
	group?: string;
}

interface UsageChartData {
	rows?: UsageChartRow[];
}

interface UsageTableRow extends CostFields {
	label?: string;
	key?: string;
	events_count?: NumericValue;
	sessions?: NumericValue;
	input_tokens?: NumericValue;
	output_tokens?: NumericValue;
	total_tokens?: NumericValue;
}

interface UsageTableData {
	rows?: UsageTableRow[];
}

interface ConversationRow {
	conversation_id?: NumericValue;
	created?: string;
	title?: string;
	status_label?: string;
	snippet?: string;
}

interface ConversationsData {
	rows?: ConversationRow[];
	total?: NumericValue;
	page?: NumericValue;
	total_pages?: NumericValue;
}

interface ConversationMessage {
	role?: string;
	text?: string;
}

interface ConversationDetail {
	title?: string;
	created?: string;
	mode_label?: string;
	status_label?: string;
	messages?: ConversationMessage[];
}

interface ConversationData {
	conversation?: ConversationDetail;
}

interface PricingStatus {
	source_mode?: string;
	source?: string;
	version?: NumericValue;
	generated_at?: string;
	last_sync_at?: string;
	last_sync_message?: string;
	auto_sync_enabled?: NumericValue;
	sync_enabled?: NumericValue;
	custom_url?: string;
	display_currency?: string;
}

interface PricingActionData {
	pricing_status?: PricingStatus;
}

interface WaicResponse<T> {
	data?: T;
	error?: boolean;
	errors?: string[];
	messages?: string[];
}

declare const WAIC_DATA: WaicAjaxGlobals;
declare const jQuery: JQueryStatic;

(function($: JQueryStatic): void {
	'use strict';

	const strings: I18nMap = window.WAIC_INSIGHTS_I18N || {};

	function i18n(key: string, fallback: string): string {
		return strings[key] || fallback;
	}

	function isRecord(value: unknown): value is PlainRecord {
		return typeof value === 'object' && value !== null;
	}

	function stringValue(value: unknown, fallback: string = ''): string {
		if (typeof value === 'undefined' || value === null) {
			return fallback;
		}
		return String(value);
	}

	function finiteNumber(value: unknown, fallback: number = 0): number {
		const raw = typeof value === 'number' ? value : parseFloat(stringValue(value, String(fallback)));
		return isFinite(raw) ? raw : fallback;
	}

	function finiteInteger(value: unknown, fallback: number = 0): number {
		const raw = typeof value === 'number' ? value : parseInt(stringValue(value, String(fallback)), 10);
		return isFinite(raw) ? raw : fallback;
	}

	function rowsOf<T>(rows: T[] | undefined): T[] {
		return Array.isArray(rows) ? rows : [];
	}

	function money(value: NumericValue | CostFields | OverviewKpis, precision?: number): string {
		let amount: unknown = value;
		if (isRecord(value)) {
			if (typeof value.cost_display === 'string' && value.cost_display) {
				return value.cost_display;
			}
			if (typeof value.display_cost === 'string' && value.display_cost) {
				return value.display_cost;
			}
			if (typeof value.cost_display_amount !== 'undefined') {
				amount = value.cost_display_amount;
			} else if (typeof value.cost !== 'undefined') {
				amount = value.cost;
			}
		}
		const fixedPrecision = typeof precision === 'number' ? precision : 4;
		return i18n('currencySymbol', '$') + finiteNumber(amount).toFixed(fixedPrecision);
	}

	function numberText(value: NumericValue): string {
		return String(finiteInteger(value));
	}

	function paginationText(total: number, page: number, pages: number): string {
		return i18n('pagination', '%1$d conversations, page %2$d of %3$d')
			.replace('%1$d', String(total))
			.replace('%2$d', String(page))
			.replace('%3$d', String(pages));
	}

	function errorMessage(res?: WaicResponse<unknown> | null): string {
		return res && res.errors && res.errors.length ? res.errors.join(' ') : i18n('loadError', 'Could not load Insights data.');
	}

	function formValues(form: JQueryObject): FormValueMap {
		const values: FormValueMap = {};
		form.serializeArray().forEach((item: SerializedField): void => {
			values[item.name] = item.value;
		});
		return values;
	}

	function isInsightsTab(value: string): value is InsightsTab {
		return value === 'overview'
			|| value === 'problems'
			|| value === 'outcomes'
			|| value === 'usage'
			|| value === 'kb-attribution'
			|| value === 'commerce-gaps'
			|| value === 'mcp-audit'
			|| value === 'actions'
			|| value === 'conversations';
	}

	class Insights {
		private readonly root: JQueryObject;
		private readonly loading: JQueryObject;
		private readonly empty: JQueryObject;
		private readonly error: JQueryObject;
		private readonly modal: JQueryObject;
		private readonly overviewForm: JQueryObject;
		private readonly usageForm: JQueryObject;
		private readonly conversationForm: JQueryObject;
		private readonly pricingForm: JQueryObject;
		private readonly pricingImportForm: JQueryObject;
		private overviewChart: ChartInstance | null;
		private usageChart: ChartInstance | null;
		private pending: number;
		private currentSeq: number;
		private lastFocus: HTMLElement | null;

		constructor(root: Element) {
			this.root = $(root);
			this.loading = this.root.find('[data-testid="waic-insights-loading"]');
			this.empty = this.root.find('[data-testid="waic-insights-empty-state"]');
			this.error = this.root.find('[data-testid="waic-insights-error-state"]');
			this.modal = this.root.find('[data-testid="aiwu-insights-conversation-modal"]');
			this.overviewForm = this.root.find('.aiwu-insights-overview-filters');
			this.usageForm = this.root.find('.aiwu-usage-filters');
			this.conversationForm = this.root.find('.aiwu-conversation-filters');
			this.pricingForm = this.root.find('.aiwu-pricing-form');
			this.pricingImportForm = this.root.find('.aiwu-pricing-import-form');
			this.overviewChart = null;
			this.usageChart = null;
			this.pending = 0;
			this.currentSeq = 0;
			this.lastFocus = null;
			this.bind();
			this.updatePricingMode();
			this.switchTab(this.initialTab());
			this.loadOverview();
			this.loadUsage();
		}

		private initialTab(): InsightsTab {
			const params = new URLSearchParams(window.location.search || '');
			const sub = params.get('sub') || 'overview';
			return isInsightsTab(sub) ? sub : 'overview';
		}

		private bind(): void {
			const self = this;
			this.root.on('click', '[data-aiwu-insights-tab]', function(): void {
				const tab = stringValue($(this).data('aiwu-insights-tab'), 'overview');
				self.switchTab(isInsightsTab(tab) ? tab : 'overview');
			});
			this.overviewForm.on('submit change', function(e: JQueryEvent): void {
				e.preventDefault();
				self.applyPeriod();
				self.loadOverview();
			});
			this.usageForm.on('submit', function(e: JQueryEvent): void {
				e.preventDefault();
				self.loadUsage();
			});
			this.conversationForm.on('submit change', function(e: JQueryEvent): void {
				e.preventDefault();
				self.loadConversations(1);
			});
			this.pricingForm.on('submit', function(e: JQueryEvent): void {
				e.preventDefault();
				self.savePricingSettings();
			});
			this.pricingImportForm.on('submit', function(e: JQueryEvent): void {
				e.preventDefault();
				self.importPricing();
			});
			this.root.on('change', '[name="pricing_source_mode"]', function(): void {
				self.updatePricingMode();
			});
			this.root.on('click', '[data-testid="waic-pricing-manual-sync"]', function(): void {
				self.syncPricing();
			});
			this.root.on('click', '[data-testid="waic-pricing-reset"]', function(): void {
				self.resetPricing();
			});
			this.root.on('click', '[data-aiwu-view-conversation]', function(): void {
				self.openConversation($(this).data('aiwu-view-conversation'));
			});
			this.root.on('click', '[data-testid="aiwu-insights-modal-close"]', function(): void {
				self.closeModal();
			});
			this.modal.on('click', function(e: JQueryEvent): void {
				if (e.target === self.modal[0]) {
					self.closeModal();
				}
			});
			$(document).on('keydown.aiwuInsights', function(e: JQueryEvent): void {
				if (e.key === 'Escape') {
					self.closeModal();
				}
			});
		}

		private currentPricingMode(): string {
			return stringValue(this.root.find('[name="pricing_source_mode"]:checked').val(), 'bundled') || 'bundled';
		}

		private updatePricingMode(): void {
			const mode = this.currentPricingMode();
			this.root.find('[data-aiwu-pricing-panel]').addClass('hidden');
			this.root.find('[data-aiwu-pricing-panel="' + mode + '"]').removeClass('hidden');
			this.root.find('[data-testid="waic-pricing-manual-sync"]').prop('disabled', mode !== 'custom_url');
		}

		private applyPeriod(): void {
			const period = stringValue(this.overviewForm.find('[name="period"]').val());
			const dateTo = stringValue(this.overviewForm.find('[name="date_to"]').val());
			const days = period === '90d' ? 90 : (period === '30d' ? 30 : 7);
			if (!dateTo) {
				return;
			}
			const end = new Date(dateTo + 'T00:00:00');
			if (isNaN(end.getTime())) {
				return;
			}
			const start = new Date(end.getTime());
			start.setDate(start.getDate() - days + 1);
			const from = start.toISOString().slice(0, 10);
			this.overviewForm.find('[name="date_from"]').val(from);
			this.usageForm.find('[name="date_from"]').val(from);
			this.conversationForm.find('[name="date_from"]').val(from);
		}

		private switchTab(tab: InsightsTab): void {
			this.root.find('[data-aiwu-insights-tab]').removeClass('current').attr('aria-selected', 'false');
			this.root.find('[data-aiwu-insights-tab="' + tab + '"]').addClass('current').attr('aria-selected', 'true');
			this.root.find('[data-aiwu-insights-panel]').addClass('hidden');
			this.root.find('[data-aiwu-insights-panel="' + tab + '"]').removeClass('hidden');
			if (tab === 'conversations') {
				this.loadConversations(1);
			}
		}

		private request<T>(action: string, data?: PlainRecord): AjaxRequest<WaicResponse<T>> {
			const payload = $.extend({}, data || {}, {
				mod: 'insights',
				action: action,
				pl: 'waic',
				reqType: 'ajax',
				waicNonce: WAIC_DATA.waicNonce
			});
			return $.ajax<WaicResponse<T>>({
				url: WAIC_DATA.ajaxurl,
				method: 'POST',
				data: payload,
				dataType: 'json',
				timeout: 30000
			});
		}

		private requestFormData<T>(action: string, formData: FormData): AjaxRequest<WaicResponse<T>> {
			formData.append('mod', 'insights');
			formData.append('action', action);
			formData.append('pl', 'waic');
			formData.append('reqType', 'ajax');
			formData.append('waicNonce', WAIC_DATA.waicNonce);
			return $.ajax<WaicResponse<T>>({
				url: WAIC_DATA.ajaxurl,
				method: 'POST',
				data: formData,
				dataType: 'json',
				processData: false,
				contentType: false,
				timeout: 30000
			});
		}

		private setLoading(active: boolean): void {
			this.pending += active ? 1 : -1;
			if (this.pending < 0) {
				this.pending = 0;
			}
			this.loading.toggleClass('hidden', this.pending === 0);
		}

		private showError(message?: string | null): void {
			this.error.text(message || i18n('loadError', 'Could not load Insights data.')).removeClass('hidden');
		}

		private clearStates(): void {
			this.empty.addClass('hidden');
			this.error.addClass('hidden');
		}

		private loadOverview(): void {
			const self = this;
			this.currentSeq += 1;
			const seq = this.currentSeq;
			this.clearStates();
			this.setLoading(true);
			this.request<OverviewData>('getOverview', formValues(this.overviewForm))
				.done(function(res: WaicResponse<OverviewData>): void {
					if (seq !== self.currentSeq || (res && res.error)) {
						if (res && res.error) {
							self.showError(errorMessage(res));
						}
						return;
					}
					self.renderOverview(res.data || {});
				})
				.fail(function(_xhr: unknown, textStatus?: string): void {
					if (seq === self.currentSeq) {
						self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
					}
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private loadUsage(): void {
			const self = this;
			const filters = formValues(this.usageForm);
			this.setLoading(true);
			$.when(
				this.request<UsageChartData>('getUsageChart', filters),
				this.request<UsageTableData>('getUsageTable', filters)
			).done(function(chartRes: [WaicResponse<UsageChartData>, string, unknown], tableRes: [WaicResponse<UsageTableData>, string, unknown]): void {
				const chartPayload = chartRes[0] || {};
				const tablePayload = tableRes[0] || {};
				if (chartPayload.error || tablePayload.error) {
					self.showError(errorMessage(chartPayload.error ? chartPayload : tablePayload));
					return;
				}
				self.renderUsageChart(chartPayload.data || {});
				self.renderUsageTable(tablePayload.data || {});
			}).fail(function(_xhr: unknown, textStatus?: string): void {
				self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
			}).always(function(): void {
				self.setLoading(false);
			});
		}

		private loadConversations(page: number): void {
			const self = this;
			this.setLoading(true);
			this.request<ConversationsData>('getConversations', $.extend({}, formValues(this.conversationForm), { page_num: page || 1 }))
				.done(function(res: WaicResponse<ConversationsData>): void {
					if (res && res.error) {
						self.showError(errorMessage(res));
						return;
					}
					self.renderConversations(res.data || {});
				})
				.fail(function(_xhr: unknown, textStatus?: string): void {
					self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private savePricingSettings(): void {
			const self = this;
			this.setLoading(true);
			this.request<PricingActionData>('savePricingSettings', formValues(this.pricingForm))
				.done(function(res: WaicResponse<PricingActionData>): void {
					if (res && res.data && res.data.pricing_status) {
						self.renderPricingStatus(res.data.pricing_status);
					}
					if (res && res.error) {
						self.showError(errorMessage(res));
					} else {
						self.loadOverview();
						self.loadUsage();
					}
				})
				.fail(function(): void {
					self.showError(i18n('loadError', 'Could not load Insights data.'));
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private importPricing(): void {
			const self = this;
			const form = this.pricingImportForm[0] as HTMLFormElement | undefined;
			if (!form || !window.FormData) {
				return;
			}
			this.setLoading(true);
			this.requestFormData<PricingActionData>('importPricing', new FormData(form))
				.done(function(res: WaicResponse<PricingActionData>): void {
					if (res && res.data && res.data.pricing_status) {
						self.renderPricingStatus(res.data.pricing_status);
					}
					if (res && res.error) {
						self.showError(errorMessage(res) || i18n('pricingImportFailed', 'Pricing import did not complete.'));
					} else {
						self.pricingImportForm.find('[name="pricing_snapshot_json"]').val('');
						self.pricingImportForm.find('[name="pricing_snapshot_file"]').val('');
						self.loadOverview();
						self.loadUsage();
					}
				})
				.fail(function(): void {
					self.showError(i18n('pricingImportFailed', 'Pricing import did not complete.'));
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private resetPricing(): void {
			const self = this;
			this.setLoading(true);
			this.request<PricingActionData>('resetPricing', {})
				.done(function(res: WaicResponse<PricingActionData>): void {
					if (res && res.data && res.data.pricing_status) {
						self.renderPricingStatus(res.data.pricing_status);
					}
					if (res && res.error) {
						self.showError(errorMessage(res));
					} else {
						self.loadOverview();
						self.loadUsage();
					}
				})
				.fail(function(): void {
					self.showError(i18n('loadError', 'Could not load Insights data.'));
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private syncPricing(): void {
			const self = this;
			this.setLoading(true);
			this.request<PricingActionData>('syncPricing', {})
				.done(function(res: WaicResponse<PricingActionData>): void {
					if (res && res.data && res.data.pricing_status) {
						self.renderPricingStatus(res.data.pricing_status);
					}
					if (res && res.error) {
						self.showError(errorMessage(res) || i18n('pricingSyncFailed', 'Pricing sync did not complete. Last valid pricing remains active.'));
					} else {
						self.loadOverview();
						self.loadUsage();
					}
				})
				.fail(function(): void {
					self.showError(i18n('pricingSyncFailed', 'Pricing sync did not complete. Last valid pricing remains active.'));
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private renderPricingStatus(status: PricingStatus = {}): void {
			const mode = status.source_mode || status.source || 'bundled';
			this.root.find('[name="pricing_source_mode"][value="' + mode + '"]').prop('checked', true);
			this.root.find('[data-testid="waic-pricing-source-mode"]').text(mode);
			this.root.find('[data-testid="waic-pricing-source"]').text(status.source || 'bundled');
			this.root.find('[data-testid="waic-pricing-version"]').text(stringValue(status.version, '0'));
			this.root.find('[data-testid="waic-pricing-generated"]').text(status.generated_at || '');
			this.root.find('[data-testid="waic-pricing-last-sync"]').text(status.last_sync_at || 'Never');
			this.root.find('[data-testid="waic-pricing-last-status"]').text(status.last_sync_message || '');
			this.root.find('[data-testid="waic-pricing-auto-sync-enabled"]').prop('checked', finiteInteger(status.auto_sync_enabled || status.sync_enabled) === 1);
			this.root.find('[data-testid="waic-pricing-custom-url"]').val(status.custom_url || '');
			this.root.find('[data-testid="waic-pricing-display-currency"]').val(status.display_currency || 'USD');
			this.updatePricingMode();
		}

		private renderOverview(data: OverviewData): void {
			const kpis = data.kpis || {};
			this.root.find('[data-testid="waic-insights-kpi-cost"] strong').text(money(kpis, 2));
			this.root.find('[data-testid="waic-insights-kpi-conversations"] strong').text(numberText(kpis.conversations || kpis.events));
			this.root.find('[data-testid="waic-insights-kpi-cost-per-conv"] strong').text(kpis.cost_per_event_display || money(kpis.cost_per_event, 4));
			this.root.find('[data-testid="waic-insights-kpi-low-conf"] strong').text(numberText(kpis.low_conf));
			this.renderOverviewChart(rowsOf(data.trend));
			this.renderFeatureBreakdown(data.breakdowns && data.breakdowns.features ? data.breakdowns.features : []);
			this.renderAttention(rowsOf(data.attention));
		}

		private renderOverviewChart(rows: TrendRow[]): void {
			const canvas = this.root.find('[data-testid="waic-insights-daily-cost-trend"]')[0];
			const Chart = window.Chart;
			if (!canvas || !Chart) {
				return;
			}
			const labels = rows.map((row: TrendRow): string => stringValue(row.day));
			const values = rows.map((row: TrendRow): number => finiteNumber(row.cost_display_amount || row.cost));
			if (this.overviewChart) {
				this.overviewChart.destroy();
			}
			this.overviewChart = new Chart(canvas, {
				type: 'line',
				data: { labels: labels, datasets: [{ label: 'Cost', data: values, borderColor: '#FF5C35', backgroundColor: 'rgba(255,92,53,0.16)', fill: true, tension: 0.25 }] },
				options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
			});
		}

		private renderFeatureBreakdown(rows: FeatureBreakdownRow[]): void {
			const tbody = this.root.find('[data-testid="waic-insights-feature-breakdown"] tbody').empty();
			rows.forEach((row: FeatureBreakdownRow): void => {
				$('<tr/>')
					.append($('<td/>').text(row.label || row.key || ''))
					.append($('<td/>').text(numberText(row.events_count)))
					.append($('<td/>').text(numberText(row.total_tokens)))
					.append($('<td/>').text(money(row, 4)))
					.appendTo(tbody);
			});
		}

		private renderAttention(rows: AttentionRow[]): void {
			const list = this.root.find('[data-testid="waic-insights-attention-list"]').empty();
			rows.forEach((row: AttentionRow): void => {
				$('<li/>')
					.attr('data-testid', 'waic-insights-attention-item')
					.text(row.label || '')
					.appendTo(list);
			});
		}

		private renderUsageChart(data: UsageChartData): void {
			const canvas = this.root.find('[data-testid="waic-usage-chart"]')[0];
			const rows = rowsOf(data.rows);
			const Chart = window.Chart;
			if (!canvas || !Chart) {
				return;
			}
			const labels: string[] = [];
			const groups: Record<string, Record<string, number>> = {};
			rows.forEach((row: UsageChartRow): void => {
				const day = stringValue(row.day);
				const group = stringValue(row.group, i18n('unknown', 'unknown'));
				if (labels.indexOf(day) === -1) {
					labels.push(day);
				}
				if (!groups[group]) {
					groups[group] = {};
				}
				groups[group][day] = finiteNumber(row.cost_display_amount || row.cost);
			});
			const colors = ['#FF5C35', '#2E7CF6', '#1B9A7A', '#7B61FF', '#C57B00', '#59636E', '#D14343'];
			const datasets = Object.keys(groups).map((group: string, index: number): ChartDataset => {
				return {
					label: group,
					data: labels.map((day: string): number => groups[group][day] || 0),
					borderColor: colors[index % colors.length],
					backgroundColor: colors[index % colors.length],
					tension: 0.2
				};
			});
			if (this.usageChart) {
				this.usageChart.destroy();
			}
			this.usageChart = new Chart(canvas, {
				type: 'bar',
				data: { labels: labels, datasets: datasets },
				options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
			});
		}

		private renderUsageTable(data: UsageTableData): void {
			const tbody = this.root.find('[data-testid="waic-usage-table"] tbody').empty();
			const rows = rowsOf(data.rows);
			rows.forEach((row: UsageTableRow): void => {
				$('<tr/>')
					.attr('data-testid', 'waic-usage-drill-row')
					.append($('<td/>').text(row.label || row.key || ''))
					.append($('<td/>').text(numberText(row.events_count)))
					.append($('<td/>').text(row.sessions === null ? '-' : numberText(row.sessions)))
					.append($('<td/>').text(numberText(row.input_tokens)))
					.append($('<td/>').text(numberText(row.output_tokens)))
					.append($('<td/>').text(numberText(row.total_tokens)))
					.append($('<td/>').text(money(row, 4)))
					.appendTo(tbody);
			});
			this.empty.toggleClass('hidden', rows.length !== 0);
		}

		private renderConversations(data: ConversationsData): void {
			const self = this;
			const tbody = this.root.find('[data-testid="aiwu-insights-conversations-table"] tbody').empty();
			const rows = rowsOf(data.rows);
			rows.forEach((row: ConversationRow): void => {
				const btn = $('<button/>')
					.attr('type', 'button')
					.attr('data-aiwu-view-conversation', stringValue(row.conversation_id))
					.addClass('wbw-button wbw-button-small')
					.text(i18n('view', 'View'));
				$('<tr/>')
					.append($('<td/>').text(row.created || ''))
					.append($('<td/>').text(row.title || ''))
					.append($('<td/>').text(row.status_label || ''))
					.append($('<td/>').text(row.snippet || ''))
					.append($('<td/>').append(btn))
					.appendTo(tbody);
			});
			const total = finiteInteger(data.total);
			const page = Math.max(1, finiteInteger(data.page, 1));
			const pages = Math.max(0, finiteInteger(data.total_pages));
			const pagination = this.root.find('[data-testid="aiwu-insights-pagination"]').empty();
			pagination.append($('<span/>').text(paginationText(total, page, pages)));
			for (let p = 1; p <= pages; p += 1) {
				const button = $('<button/>').attr({ type: 'button', 'data-page': p }).text(p);
				if (p === page) {
					button.addClass('current').attr('aria-current', 'page');
				}
				pagination.append(' ', button);
			}
			pagination.off('click.aiwuInsights').on('click.aiwuInsights', 'button', function(): void {
				self.loadConversations(finiteInteger($(this).data('page'), 1));
			});
		}

		private openConversation(id: unknown): void {
			const self = this;
			this.setLoading(true);
			this.request<ConversationData>('getConversation', $.extend({}, formValues(this.conversationForm), { conversation_id: stringValue(id) }))
				.done(function(res: WaicResponse<ConversationData>): void {
					if (res && res.error) {
						self.showError(errorMessage(res));
						return;
					}
					self.renderModal(res.data && res.data.conversation ? res.data.conversation : {});
				})
				.fail(function(_xhr: unknown, textStatus?: string): void {
					self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
				})
				.always(function(): void {
					self.setLoading(false);
				});
		}

		private renderModal(conversation: ConversationDetail): void {
			const messages = this.modal.find('.aiwu-insights-modal-messages').empty();
			this.lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
			this.modal.find('.aiwu-insights-modal-title').text(conversation.title || i18n('conversation', 'Conversation'));
			this.modal.find('.aiwu-insights-modal-meta').text((conversation.created || '') + ' - ' + (conversation.mode_label || '') + ' - ' + (conversation.status_label || ''));
			rowsOf(conversation.messages).forEach((message: ConversationMessage): void => {
				$('<div/>')
					.addClass('aiwu-insights-message')
					.toggleClass('is-assistant', message.role === 'assistant')
					.text(message.text || '')
					.appendTo(messages);
			});
			this.modal.removeClass('hidden').attr('aria-hidden', 'false');
			this.modal.find('[data-testid="aiwu-insights-modal-close"]').trigger('focus');
		}

		private closeModal(): void {
			this.modal.addClass('hidden').attr('aria-hidden', 'true');
			if (this.lastFocus) {
				this.lastFocus.focus();
			}
		}
	}

	$(function(): void {
		$('[data-testid="waic-insights-page"]').each(function(): void {
			new Insights(this);
		});
	});
})(jQuery);
