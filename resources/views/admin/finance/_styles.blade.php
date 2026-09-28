<style>
    .finance-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:20px;flex-wrap:wrap}
    .finance-heading h2{font-size:24px;font-weight:700;margin-bottom:7px}.finance-muted{color:#78636c;font-size:13px;line-height:1.6}
    .finance-actions{display:flex;gap:8px;flex-wrap:wrap}.finance-mode{display:inline-block;border-radius:20px;padding:4px 10px;font-size:11px;font-weight:700;background:#e7f5ef;color:#24734c}
    .finance-mode.demo{background:#fff0ce;color:#8b5c00}.finance-filters{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .finance-filters label{display:block;font-size:12px;font-weight:600;margin-bottom:6px}.finance-filters .form-control{height:40px;font-size:13px}
    .finance-filter-actions{grid-column:1/-1;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .finance-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px}
    .finance-metric{background:#fff;border:1px solid var(--border);border-radius:14px;padding:21px}.finance-metric-label{font-size:12px;color:#78636c;margin-bottom:9px}
    .finance-metric-value{font-size:23px;line-height:1.3;font-weight:700;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}.finance-metric-note{font-size:11px;color:#78636c;margin-top:7px}
    .finance-charts{display:grid;grid-template-columns:1.3fr 1fr;gap:20px}.finance-chart-title{font-size:16px;font-weight:700;margin-bottom:20px}
    .finance-bar-row{margin-bottom:17px}.finance-bar-label{display:flex;justify-content:space-between;gap:10px;font-size:12px;margin-bottom:6px}
    .finance-bar-track{height:9px;border-radius:10px;background:#f8edf0;overflow:hidden}.finance-bar-fill{height:100%;background:#c94d68;border-radius:10px}
    .finance-table td{min-width:95px}.finance-table .finance-amount{white-space:nowrap;font-weight:700;font-variant-numeric:tabular-nums}
    .finance-status{display:inline-block;font-size:11px;font-weight:600;white-space:nowrap;padding:5px 9px;border-radius:7px;background:#eef0f4;color:#536170}
    .finance-status.paid{background:#e5f5eb;color:#24734c}.finance-status.refund_pending,.finance-status.initiated{background:#fff2d7;color:#8b5c00}
    .finance-status.failed,.finance-status.cancelled{background:#ffe9ec;color:#ad3245}.finance-status.refunded{background:#eee9f7;color:#695092}
    .finance-reconcile{min-width:255px;max-width:300px}.finance-reconcile select,.finance-reconcile input{font-size:12px;height:36px}
    .finance-reconcile label{font-size:11px;margin:7px 0 4px;display:block}.finance-reconcile button{font-size:12px;margin-top:8px}
    .finance-audit{font-size:11px;margin-top:10px;line-height:1.6}.finance-audit summary{cursor:pointer;color:#9f3854}.finance-audit-entry{border-top:1px solid #eee4e7;margin-top:8px;padding-top:8px;overflow-wrap:anywhere}
    .finance-table-footer{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-top:18px}.finance-empty{text-align:center;padding:45px 20px!important;color:#78636c}
    .finance-explainer{padding:13px 16px;border-left:3px solid #c94d68;background:#fff3f6;border-radius:6px;font-size:12px;line-height:1.7;margin-bottom:20px}
    @media(max-width:1200px){.finance-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:800px){.finance-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.finance-charts{grid-template-columns:1fr}.finance-metric-value{font-size:20px}}
    @media(max-width:480px){.finance-filters,.finance-metrics{grid-template-columns:1fr}.finance-heading h2{font-size:21px}.finance-metric{padding:17px}}
</style>
