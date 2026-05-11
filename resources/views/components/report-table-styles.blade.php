<style>
.report-table { width: 100%; border-collapse: collapse; }
.col-code {
    white-space: nowrap;
    width: 130px;
    min-width: 130px;
    font-family: ui-monospace, monospace;
    font-size: 0.8rem;
    color: #9CA3AF;
    padding: 9px 12px 9px 0;
    vertical-align: middle;
}
.col-name {
    font-size: 0.8125rem;
    padding: 9px 12px;
    vertical-align: middle;
}
.col-amount {
    white-space: nowrap;
    text-align: right;
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 9px 0 9px 12px;
    vertical-align: middle;
    width: 150px;
    min-width: 150px;
}
.col-amount.negative { color: #DC2626; }
.report-table .row-subtotal td {
    font-weight: 600;
    border-top: 1px solid #374151;
    padding-top: 10px;
}
.report-table .row-total td {
    font-weight: 700;
    font-size: 0.9rem;
    border-top: 2px solid #374151;
    padding-top: 10px;
}
.report-flex-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 12px;
    font-size: 0.8125rem;
    gap: 8px;
}
.report-flex-row .flex-code {
    white-space: nowrap;
    min-width: 130px;
    font-family: ui-monospace, monospace;
    color: #9CA3AF;
}
.report-flex-row .flex-name {
    flex: 1;
    min-width: 0;
}
.report-flex-row .flex-amount {
    white-space: nowrap;
    text-align: right;
    font-weight: 500;
    min-width: 140px;
    font-family: ui-monospace, monospace;
}
</style>
