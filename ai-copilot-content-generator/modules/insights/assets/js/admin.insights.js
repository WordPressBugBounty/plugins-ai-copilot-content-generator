"use strict";
(function ($) {
    'use strict';
    var strings = window.WAIC_INSIGHTS_I18N || {};
    function i18n(key, fallback) {
        return strings[key] || fallback;
    }
    function isRecord(value) {
        return typeof value === 'object' && value !== null;
    }
    function stringValue(value, fallback) {
        if (fallback === void 0) { fallback = ''; }
        if (typeof value === 'undefined' || value === null) {
            return fallback;
        }
        return String(value);
    }
    function finiteNumber(value, fallback) {
        if (fallback === void 0) { fallback = 0; }
        var raw = typeof value === 'number' ? value : parseFloat(stringValue(value, String(fallback)));
        return isFinite(raw) ? raw : fallback;
    }
    function finiteInteger(value, fallback) {
        if (fallback === void 0) { fallback = 0; }
        var raw = typeof value === 'number' ? value : parseInt(stringValue(value, String(fallback)), 10);
        return isFinite(raw) ? raw : fallback;
    }
    function rowsOf(rows) {
        return Array.isArray(rows) ? rows : [];
    }
    function money(value, precision) {
        var amount = value;
        if (isRecord(value)) {
            if (typeof value.cost_display === 'string' && value.cost_display) {
                return value.cost_display;
            }
            if (typeof value.display_cost === 'string' && value.display_cost) {
                return value.display_cost;
            }
            if (typeof value.cost_display_amount !== 'undefined') {
                amount = value.cost_display_amount;
            }
            else if (typeof value.cost !== 'undefined') {
                amount = value.cost;
            }
        }
        var fixedPrecision = typeof precision === 'number' ? precision : 4;
        return i18n('currencySymbol', '$') + finiteNumber(amount).toFixed(fixedPrecision);
    }
    function numberText(value) {
        return String(finiteInteger(value));
    }
    function paginationText(total, page, pages) {
        return i18n('pagination', '%1$d conversations, page %2$d of %3$d')
            .replace('%1$d', String(total))
            .replace('%2$d', String(page))
            .replace('%3$d', String(pages));
    }
    function errorMessage(res) {
        return res && res.errors && res.errors.length ? res.errors.join(' ') : i18n('loadError', 'Could not load Insights data.');
    }
    function formValues(form) {
        var values = {};
        form.serializeArray().forEach(function (item) {
            values[item.name] = item.value;
        });
        return values;
    }
    function isInsightsTab(value) {
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
    var Insights = /** @class */ (function () {
        function Insights(root) {
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
        Insights.prototype.initialTab = function () {
            var params = new URLSearchParams(window.location.search || '');
            var sub = params.get('sub') || 'overview';
            return isInsightsTab(sub) ? sub : 'overview';
        };
        Insights.prototype.bind = function () {
            var self = this;
            this.root.on('click', '[data-aiwu-insights-tab]', function () {
                var tab = stringValue($(this).data('aiwu-insights-tab'), 'overview');
                self.switchTab(isInsightsTab(tab) ? tab : 'overview');
            });
            this.overviewForm.on('submit change', function (e) {
                e.preventDefault();
                self.applyPeriod();
                self.loadOverview();
            });
            this.usageForm.on('submit', function (e) {
                e.preventDefault();
                self.loadUsage();
            });
            this.conversationForm.on('submit change', function (e) {
                e.preventDefault();
                self.loadConversations(1);
            });
            this.pricingForm.on('submit', function (e) {
                e.preventDefault();
                self.savePricingSettings();
            });
            this.pricingImportForm.on('submit', function (e) {
                e.preventDefault();
                self.importPricing();
            });
            this.root.on('change', '[name="pricing_source_mode"]', function () {
                self.updatePricingMode();
            });
            this.root.on('click', '[data-testid="waic-pricing-manual-sync"]', function () {
                self.syncPricing();
            });
            this.root.on('click', '[data-testid="waic-pricing-reset"]', function () {
                self.resetPricing();
            });
            this.root.on('click', '[data-aiwu-view-conversation]', function () {
                self.openConversation($(this).data('aiwu-view-conversation'));
            });
            this.root.on('click', '[data-testid="aiwu-insights-modal-close"]', function () {
                self.closeModal();
            });
            this.modal.on('click', function (e) {
                if (e.target === self.modal[0]) {
                    self.closeModal();
                }
            });
            $(document).on('keydown.aiwuInsights', function (e) {
                if (e.key === 'Escape') {
                    self.closeModal();
                }
            });
        };
        Insights.prototype.currentPricingMode = function () {
            return stringValue(this.root.find('[name="pricing_source_mode"]:checked').val(), 'bundled') || 'bundled';
        };
        Insights.prototype.updatePricingMode = function () {
            var mode = this.currentPricingMode();
            this.root.find('[data-aiwu-pricing-panel]').addClass('hidden');
            this.root.find('[data-aiwu-pricing-panel="' + mode + '"]').removeClass('hidden');
            this.root.find('[data-testid="waic-pricing-manual-sync"]').prop('disabled', mode !== 'custom_url');
        };
        Insights.prototype.applyPeriod = function () {
            var period = stringValue(this.overviewForm.find('[name="period"]').val());
            var dateTo = stringValue(this.overviewForm.find('[name="date_to"]').val());
            var days = period === '90d' ? 90 : (period === '30d' ? 30 : 7);
            if (!dateTo) {
                return;
            }
            var end = new Date(dateTo + 'T00:00:00');
            if (isNaN(end.getTime())) {
                return;
            }
            var start = new Date(end.getTime());
            start.setDate(start.getDate() - days + 1);
            var from = start.toISOString().slice(0, 10);
            this.overviewForm.find('[name="date_from"]').val(from);
            this.usageForm.find('[name="date_from"]').val(from);
            this.conversationForm.find('[name="date_from"]').val(from);
        };
        Insights.prototype.switchTab = function (tab) {
            this.root.find('[data-aiwu-insights-tab]').removeClass('current').attr('aria-selected', 'false');
            this.root.find('[data-aiwu-insights-tab="' + tab + '"]').addClass('current').attr('aria-selected', 'true');
            this.root.find('[data-aiwu-insights-panel]').addClass('hidden');
            this.root.find('[data-aiwu-insights-panel="' + tab + '"]').removeClass('hidden');
            if (tab === 'conversations') {
                this.loadConversations(1);
            }
        };
        Insights.prototype.request = function (action, data) {
            var payload = $.extend({}, data || {}, {
                mod: 'insights',
                action: action,
                pl: 'waic',
                reqType: 'ajax',
                waicNonce: WAIC_DATA.waicNonce
            });
            return $.ajax({
                url: WAIC_DATA.ajaxurl,
                method: 'POST',
                data: payload,
                dataType: 'json',
                timeout: 30000
            });
        };
        Insights.prototype.requestFormData = function (action, formData) {
            formData.append('mod', 'insights');
            formData.append('action', action);
            formData.append('pl', 'waic');
            formData.append('reqType', 'ajax');
            formData.append('waicNonce', WAIC_DATA.waicNonce);
            return $.ajax({
                url: WAIC_DATA.ajaxurl,
                method: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                timeout: 30000
            });
        };
        Insights.prototype.setLoading = function (active) {
            this.pending += active ? 1 : -1;
            if (this.pending < 0) {
                this.pending = 0;
            }
            this.loading.toggleClass('hidden', this.pending === 0);
        };
        Insights.prototype.showError = function (message) {
            this.error.text(message || i18n('loadError', 'Could not load Insights data.')).removeClass('hidden');
        };
        Insights.prototype.clearStates = function () {
            this.empty.addClass('hidden');
            this.error.addClass('hidden');
        };
        Insights.prototype.loadOverview = function () {
            var self = this;
            this.currentSeq += 1;
            var seq = this.currentSeq;
            this.clearStates();
            this.setLoading(true);
            this.request('getOverview', formValues(this.overviewForm))
                .done(function (res) {
                if (seq !== self.currentSeq || (res && res.error)) {
                    if (res && res.error) {
                        self.showError(errorMessage(res));
                    }
                    return;
                }
                self.renderOverview(res.data || {});
            })
                .fail(function (_xhr, textStatus) {
                if (seq === self.currentSeq) {
                    self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
                }
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.loadUsage = function () {
            var self = this;
            var filters = formValues(this.usageForm);
            this.setLoading(true);
            $.when(this.request('getUsageChart', filters), this.request('getUsageTable', filters)).done(function (chartRes, tableRes) {
                var chartPayload = chartRes[0] || {};
                var tablePayload = tableRes[0] || {};
                if (chartPayload.error || tablePayload.error) {
                    self.showError(errorMessage(chartPayload.error ? chartPayload : tablePayload));
                    return;
                }
                self.renderUsageChart(chartPayload.data || {});
                self.renderUsageTable(tablePayload.data || {});
            }).fail(function (_xhr, textStatus) {
                self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
            }).always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.loadConversations = function (page) {
            var self = this;
            this.setLoading(true);
            this.request('getConversations', $.extend({}, formValues(this.conversationForm), { page_num: page || 1 }))
                .done(function (res) {
                if (res && res.error) {
                    self.showError(errorMessage(res));
                    return;
                }
                self.renderConversations(res.data || {});
            })
                .fail(function (_xhr, textStatus) {
                self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.savePricingSettings = function () {
            var self = this;
            this.setLoading(true);
            this.request('savePricingSettings', formValues(this.pricingForm))
                .done(function (res) {
                if (res && res.data && res.data.pricing_status) {
                    self.renderPricingStatus(res.data.pricing_status);
                }
                if (res && res.error) {
                    self.showError(errorMessage(res));
                }
                else {
                    self.loadOverview();
                    self.loadUsage();
                }
            })
                .fail(function () {
                self.showError(i18n('loadError', 'Could not load Insights data.'));
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.importPricing = function () {
            var self = this;
            var form = this.pricingImportForm[0];
            if (!form || !window.FormData) {
                return;
            }
            this.setLoading(true);
            this.requestFormData('importPricing', new FormData(form))
                .done(function (res) {
                if (res && res.data && res.data.pricing_status) {
                    self.renderPricingStatus(res.data.pricing_status);
                }
                if (res && res.error) {
                    self.showError(errorMessage(res) || i18n('pricingImportFailed', 'Pricing import did not complete.'));
                }
                else {
                    self.pricingImportForm.find('[name="pricing_snapshot_json"]').val('');
                    self.pricingImportForm.find('[name="pricing_snapshot_file"]').val('');
                    self.loadOverview();
                    self.loadUsage();
                }
            })
                .fail(function () {
                self.showError(i18n('pricingImportFailed', 'Pricing import did not complete.'));
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.resetPricing = function () {
            var self = this;
            this.setLoading(true);
            this.request('resetPricing', {})
                .done(function (res) {
                if (res && res.data && res.data.pricing_status) {
                    self.renderPricingStatus(res.data.pricing_status);
                }
                if (res && res.error) {
                    self.showError(errorMessage(res));
                }
                else {
                    self.loadOverview();
                    self.loadUsage();
                }
            })
                .fail(function () {
                self.showError(i18n('loadError', 'Could not load Insights data.'));
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.syncPricing = function () {
            var self = this;
            this.setLoading(true);
            this.request('syncPricing', {})
                .done(function (res) {
                if (res && res.data && res.data.pricing_status) {
                    self.renderPricingStatus(res.data.pricing_status);
                }
                if (res && res.error) {
                    self.showError(errorMessage(res) || i18n('pricingSyncFailed', 'Pricing sync did not complete. Last valid pricing remains active.'));
                }
                else {
                    self.loadOverview();
                    self.loadUsage();
                }
            })
                .fail(function () {
                self.showError(i18n('pricingSyncFailed', 'Pricing sync did not complete. Last valid pricing remains active.'));
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.renderPricingStatus = function (status) {
            if (status === void 0) { status = {}; }
            var mode = status.source_mode || status.source || 'bundled';
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
        };
        Insights.prototype.renderOverview = function (data) {
            var kpis = data.kpis || {};
            this.root.find('[data-testid="waic-insights-kpi-cost"] strong').text(money(kpis, 2));
            this.root.find('[data-testid="waic-insights-kpi-conversations"] strong').text(numberText(kpis.conversations || kpis.events));
            this.root.find('[data-testid="waic-insights-kpi-cost-per-conv"] strong').text(kpis.cost_per_event_display || money(kpis.cost_per_event, 4));
            this.root.find('[data-testid="waic-insights-kpi-low-conf"] strong').text(numberText(kpis.low_conf));
            this.renderOverviewChart(rowsOf(data.trend));
            this.renderFeatureBreakdown(data.breakdowns && data.breakdowns.features ? data.breakdowns.features : []);
            this.renderAttention(rowsOf(data.attention));
        };
        Insights.prototype.renderOverviewChart = function (rows) {
            var canvas = this.root.find('[data-testid="waic-insights-daily-cost-trend"]')[0];
            var Chart = window.Chart;
            if (!canvas || !Chart) {
                return;
            }
            var labels = rows.map(function (row) { return stringValue(row.day); });
            var values = rows.map(function (row) { return finiteNumber(row.cost_display_amount || row.cost); });
            if (this.overviewChart) {
                this.overviewChart.destroy();
            }
            this.overviewChart = new Chart(canvas, {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Cost', data: values, borderColor: '#FF5C35', backgroundColor: 'rgba(255,92,53,0.16)', fill: true, tension: 0.25 }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        };
        Insights.prototype.renderFeatureBreakdown = function (rows) {
            var tbody = this.root.find('[data-testid="waic-insights-feature-breakdown"] tbody').empty();
            rows.forEach(function (row) {
                $('<tr/>')
                    .append($('<td/>').text(row.label || row.key || ''))
                    .append($('<td/>').text(numberText(row.events_count)))
                    .append($('<td/>').text(numberText(row.total_tokens)))
                    .append($('<td/>').text(money(row, 4)))
                    .appendTo(tbody);
            });
        };
        Insights.prototype.renderAttention = function (rows) {
            var list = this.root.find('[data-testid="waic-insights-attention-list"]').empty();
            rows.forEach(function (row) {
                $('<li/>')
                    .attr('data-testid', 'waic-insights-attention-item')
                    .text(row.label || '')
                    .appendTo(list);
            });
        };
        Insights.prototype.renderUsageChart = function (data) {
            var canvas = this.root.find('[data-testid="waic-usage-chart"]')[0];
            var rows = rowsOf(data.rows);
            var Chart = window.Chart;
            if (!canvas || !Chart) {
                return;
            }
            var labels = [];
            var groups = {};
            rows.forEach(function (row) {
                var day = stringValue(row.day);
                var group = stringValue(row.group, i18n('unknown', 'unknown'));
                if (labels.indexOf(day) === -1) {
                    labels.push(day);
                }
                if (!groups[group]) {
                    groups[group] = {};
                }
                groups[group][day] = finiteNumber(row.cost_display_amount || row.cost);
            });
            var colors = ['#FF5C35', '#2E7CF6', '#1B9A7A', '#7B61FF', '#C57B00', '#59636E', '#D14343'];
            var datasets = Object.keys(groups).map(function (group, index) {
                return {
                    label: group,
                    data: labels.map(function (day) { return groups[group][day] || 0; }),
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
        };
        Insights.prototype.renderUsageTable = function (data) {
            var tbody = this.root.find('[data-testid="waic-usage-table"] tbody').empty();
            var rows = rowsOf(data.rows);
            rows.forEach(function (row) {
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
        };
        Insights.prototype.renderConversations = function (data) {
            var self = this;
            var tbody = this.root.find('[data-testid="aiwu-insights-conversations-table"] tbody').empty();
            var rows = rowsOf(data.rows);
            rows.forEach(function (row) {
                var btn = $('<button/>')
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
            var total = finiteInteger(data.total);
            var page = Math.max(1, finiteInteger(data.page, 1));
            var pages = Math.max(0, finiteInteger(data.total_pages));
            var pagination = this.root.find('[data-testid="aiwu-insights-pagination"]').empty();
            pagination.append($('<span/>').text(paginationText(total, page, pages)));
            for (var p = 1; p <= pages; p += 1) {
                var button = $('<button/>').attr({ type: 'button', 'data-page': p }).text(p);
                if (p === page) {
                    button.addClass('current').attr('aria-current', 'page');
                }
                pagination.append(' ', button);
            }
            pagination.off('click.aiwuInsights').on('click.aiwuInsights', 'button', function () {
                self.loadConversations(finiteInteger($(this).data('page'), 1));
            });
        };
        Insights.prototype.openConversation = function (id) {
            var self = this;
            this.setLoading(true);
            this.request('getConversation', $.extend({}, formValues(this.conversationForm), { conversation_id: stringValue(id) }))
                .done(function (res) {
                if (res && res.error) {
                    self.showError(errorMessage(res));
                    return;
                }
                self.renderModal(res.data && res.data.conversation ? res.data.conversation : {});
            })
                .fail(function (_xhr, textStatus) {
                self.showError(textStatus === 'timeout' ? i18n('timeoutError', 'Insights data request timed out. Please try a smaller date range.') : null);
            })
                .always(function () {
                self.setLoading(false);
            });
        };
        Insights.prototype.renderModal = function (conversation) {
            var messages = this.modal.find('.aiwu-insights-modal-messages').empty();
            this.lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            this.modal.find('.aiwu-insights-modal-title').text(conversation.title || i18n('conversation', 'Conversation'));
            this.modal.find('.aiwu-insights-modal-meta').text((conversation.created || '') + ' - ' + (conversation.mode_label || '') + ' - ' + (conversation.status_label || ''));
            rowsOf(conversation.messages).forEach(function (message) {
                $('<div/>')
                    .addClass('aiwu-insights-message')
                    .toggleClass('is-assistant', message.role === 'assistant')
                    .text(message.text || '')
                    .appendTo(messages);
            });
            this.modal.removeClass('hidden').attr('aria-hidden', 'false');
            this.modal.find('[data-testid="aiwu-insights-modal-close"]').trigger('focus');
        };
        Insights.prototype.closeModal = function () {
            this.modal.addClass('hidden').attr('aria-hidden', 'true');
            if (this.lastFocus) {
                this.lastFocus.focus();
            }
        };
        return Insights;
    }());
    $(function () {
        $('[data-testid="waic-insights-page"]').each(function () {
            new Insights(this);
        });
    });
})(jQuery);
