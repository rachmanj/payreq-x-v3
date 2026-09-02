<style>
/* â”€â”€ VJ Show page design system â”€â”€ */
.vj-show .card-outline {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: none;
    transition: box-shadow 0.2s ease;
}

.vj-show .card-outline:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transform: none;
}

.vj-show .card-outline .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-radius: 10px 10px 0 0;
    padding: 0.65rem 1rem;
}

.vj-show .card-outline .card-header .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0;
}

.vj-show .card-outline .card-header .card-title i {
    color: #6c757d;
    margin-right: 0.35rem;
}

.vj-show .card-outline .card-body {
    padding: 0.85rem 1rem;
}

.vj-show dl dt {
    font-size: 0.8125rem;
    color: #6c757d;
    font-weight: 500;
}

.vj-show dl dd {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.vj-show dl dd:last-child {
    margin-bottom: 0;
}

/* Chips (badges) */
.vj-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.3;
    border: 1px solid transparent;
    white-space: nowrap;
}

.vj-chip > i.fas,
.vj-chip > i.far,
.vj-chip > i.fab {
    font-size: 0.75rem;
    line-height: 1;
    flex-shrink: 0;
    margin-right: 0.25rem;
}

.vj-chip-info {
    background: #e8f7fa;
    border-color: #b2ebf2;
    color: #0c6674;
}

.vj-chip-neutral {
    background: #f1f3f5;
    border-color: #dee2e6;
    color: #495057;
}

.vj-chip-success {
    background: #e8f5e9;
    border-color: #c8e6c9;
    color: #198754;
}

.vj-chip-danger {
    background: #ffebee;
    border-color: #ffcdd2;
    color: #c62828;
}

.vj-chip-warning {
    background: #fff8e1;
    border-color: #ffe082;
    color: #b8860b;
}

.vj-chip-primary {
    background: #e7f1ff;
    border-color: #b8d4fe;
    color: #0d47a1;
}

.vj-chip-on-dark {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 0.6);
    color: #343a40;
    font-size: 0.875rem;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
}

/* Financial stat cards */
.vj-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.vj-stat {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    background: #fff;
}

.vj-stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.vj-stat-info .vj-stat-icon {
    background: #e8f7fa;
    color: #17a2b8;
    border: 1px solid #b2ebf2;
}

.vj-stat-success .vj-stat-icon {
    background: #e8f5e9;
    color: #198754;
    border: 1px solid #c8e6c9;
}

.vj-stat-danger .vj-stat-icon {
    background: #ffebee;
    color: #dc3545;
    border: 1px solid #ffcdd2;
}

.vj-stat-neutral .vj-stat-icon {
    background: #f1f3f5;
    color: #495057;
    border: 1px solid #dee2e6;
}

.vj-stat-grid-4 {
    grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 992px) {
    .vj-stat-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

.vj-stat-body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.vj-stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6c757d;
}

.vj-stat-value {
    font-size: 1rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.2;
}

/* Notes & alerts */
.vj-note {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #495057;
    font-size: 0.875rem;
    line-height: 1.45;
}

.vj-note i {
    margin-top: 0.15rem;
    color: #6c757d;
}

.vj-alert {
    display: flex;
    align-items: flex-start;
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    font-size: 0.875rem;
    line-height: 1.45;
    border: 1px solid;
}

.vj-alert > i.fas,
.vj-alert > i.far,
.vj-alert > i.fab {
    font-size: 0.875rem;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 0.15rem;
    margin-right: 0.5rem;
}

.vj-alert-danger {
    background: #fff5f5;
    border-color: #f1c2c7;
    color: #842029;
}

.vj-alert-danger code {
    background: transparent;
    color: inherit;
    font-size: 0.8125rem;
    white-space: pre-wrap;
    word-break: break-word;
}

.vj-alert-secondary {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #495057;
}

/* Table */
.vj-show .table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.04);
}

/* Timeline */
.vj-show .timeline {
    position: relative;
    padding: 0.5rem 0;
}

.vj-show .timeline > div:not(.time-label) {
    position: relative;
    padding-left: 3.25rem;
}

.vj-show .timeline-item {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 0.85rem 1rem;
    margin: 0 0 1.25rem 0;
    position: relative;
}

.vj-show .timeline-item::before {
    content: '';
    position: absolute;
    left: -9px;
    top: 1.1rem;
    display: block;
    width: 0;
    height: 0;
    border: solid transparent;
    border-width: 7px;
    border-right-color: #e9ecef;
}

.vj-show .time-label {
    position: relative;
    padding: 0.5rem 0 0.25rem;
}

.vj-timeline-date {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.65rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid transparent;
}

.vj-timeline-date-success {
    background: #e8f5e9;
    border-color: #c8e6c9;
    color: #198754;
}

.vj-timeline-date-danger {
    background: #ffebee;
    border-color: #ffcdd2;
    color: #c62828;
}

.vj-timeline-date-neutral {
    background: #f1f3f5;
    border-color: #dee2e6;
    color: #495057;
}

.vj-show .timeline > div:not(.time-label) > i.fas {
    position: absolute;
    left: 0;
    top: 0.65rem;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    border: 1px solid transparent;
}

.vj-timeline-icon-success {
    background: #e8f5e9;
    border-color: #c8e6c9;
    color: #198754;
}

.vj-timeline-icon-danger {
    background: #ffebee;
    border-color: #ffcdd2;
    color: #c62828;
}

.vj-timeline-icon-neutral {
    background: #f1f3f5;
    border-color: #dee2e6;
    color: #495057;
}

.vj-show .timeline-header {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #343a40;
}

.vj-show .timeline-body {
    padding-top: 0.25rem;
    font-size: 0.875rem;
    color: #495057;
}

.vj-timeline-time {
    float: right;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.55rem;
    background: #f1f3f5;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6c757d;
}

.vj-actions {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 0.85rem 1rem;
}

.vj-actions-primary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.vj-actions-note {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.65rem 0.75rem;
    margin-bottom: 0.75rem;
    border-radius: 8px;
    background: #e7f3ff;
    border: 1px solid #b8daff;
    color: #0c5460;
    font-size: 0.875rem;
    line-height: 1.45;
}

.vj-actions-note i {
    margin-top: 0.15rem;
    color: #17a2b8;
}

.vj-actions-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    padding-top: 0.15rem;
    border-top: 1px solid #f1f3f5;
}

.vj-actions-primary + .vj-actions-toolbar {
    padding-top: 0.75rem;
}

.vj-actions-note + .vj-actions-toolbar {
    padding-top: 0.75rem;
}

.vj-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.9rem;
    border-radius: 8px;
    border: 1px solid transparent;
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.2;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.vj-btn > i.fas,
.vj-btn > i.far,
.vj-btn > i.fab,
.vj-btn > i.fal,
.vj-btn > i.fad {
    font-size: 0.875rem;
    line-height: 1;
    flex-shrink: 0;
    margin-right: 0.35rem;
}

.vj-btn-success {
    background: #198754;
    color: #fff;
}

.vj-btn-success:hover {
    background: #157347;
    color: #fff;
}

.vj-btn-danger {
    background: #dc3545;
    color: #fff;
}

.vj-btn-danger:hover {
    background: #bb2d3b;
    color: #fff;
}

.vj-btn-danger-outline {
    background: #fff;
    color: #dc3545;
    border-color: #f1c2c7;
}

.vj-btn-danger-outline:hover {
    background: #fff5f5;
    color: #bb2d3b;
    border-color: #f1aeb5;
}

.vj-action-item,
.vj-action-item-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.7rem;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #495057;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.vj-action-edit {
    background: #fff8e1;
    border-color: #ffe082;
    color: #b8860b;
}

.vj-action-edit i {
    color: #f0ad4e;
}

.vj-action-edit:hover:not(.is-disabled) {
    background: #ffecb3;
    border-color: #ffd54f;
    color: #8a6d0a;
    text-decoration: none;
}

.vj-action-export {
    background: #e8f7fa;
    border-color: #b2ebf2;
    color: #0c6674;
}

.vj-action-export i {
    color: #17a2b8;
}

.vj-action-export:hover {
    background: #d1f2f7;
    border-color: #80deea;
    color: #0a4f5a;
    text-decoration: none;
}

.vj-action-success {
    background: #e8f5e9;
    border-color: #c8e6c9;
    color: #198754;
}

.vj-action-success i {
    color: #198754;
}

.vj-action-success:hover {
    background: #d4edd7;
    border-color: #a5d6a7;
    color: #146c43;
    text-decoration: none;
}

.vj-action-print {
    background: #f1f3f5;
    border-color: #dee2e6;
    color: #495057;
}

.vj-action-print i {
    color: #6c757d;
}

.vj-action-print:hover {
    background: #e9ecef;
    border-color: #ced4da;
    color: #212529;
    text-decoration: none;
}

.vj-action-sap {
    background: #fff3e0;
    border-color: #ffcc80;
    color: #e65100;
}

.vj-action-sap i {
    color: #ff9800;
}

.vj-action-sap:hover:not(:disabled):not(.is-disabled) {
    background: #ffe0b2;
    border-color: #ffb74d;
    color: #bf360c;
}

.vj-action-cancel {
    background: #ffebee;
    border-color: #ffcdd2;
    color: #c62828;
}

.vj-action-cancel i {
    color: #dc3545;
}

.vj-action-cancel:hover:not(:disabled):not(.is-disabled) {
    background: #ffcdd2;
    border-color: #ef9a9a;
    color: #b71c1c;
}

.vj-action-item:hover,
.vj-action-item-btn:hover:not(:disabled):not(.is-disabled) {
    text-decoration: none;
}

.vj-action-item i,
.vj-action-item-btn i {
    width: 0.95rem;
    text-align: center;
    font-size: 0.8rem;
}

.vj-action-item.is-disabled,
.vj-action-item-btn.is-disabled,
.vj-action-item-btn:disabled {
    opacity: 0.45;
    pointer-events: none;
    cursor: not-allowed;
}

.vj-action-item-form {
    display: inline-flex;
    margin: 0;
}

.vj-inline-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    vertical-align: middle;
}

.vj-action-item-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.vj-action-item-xs i {
    font-size: 0.7rem;
    width: 0.85rem;
}

.vj-action-back {
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 0.6);
    color: #343a40;
}

.vj-action-back:hover {
    background: #fff;
    border-color: #fff;
    color: #212529;
    text-decoration: none;
}

@media (max-width: 768px) {
    .vj-stat-grid {
        grid-template-columns: 1fr;
    }

    .vj-show .timeline > div:not(.time-label) {
        padding-left: 2.75rem;
    }

    .btn-block {
        font-size: 14px;
        padding: 8px 12px;
    }

    .info-box {
        margin-bottom: 10px;
    }

    .card-body {
        padding: 15px;
    }

    .timeline-item {
        margin-left: 40px;
    }
}

.vj-btn-primary {
    background: #0d6efd;
    color: #fff;
}

.vj-btn-primary:hover:not(:disabled) {
    background: #0b5ed7;
    color: #fff;
}

.vj-btn-primary:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.vj-show #mypayreqs thead th,
.vj-show #realizations thead th,
.vj-show #details-table thead th,
.vj-show #vj_details thead th,
.vj-show #bills-table thead th,
.vj-show #customers-table thead th,
.vj-show #approveds thead th,
.vj-show #outgoings thead th,
.vj-show #incomings thead th,
.vj-show #cashier-modal thead th {
    background: #f8f9fa;
    border-color: #e9ecef;
    color: #495057;
    font-size: 0.8125rem;
    font-weight: 600;
    vertical-align: middle;
}

.vj-show .table-bordered {
    border-color: #e9ecef;
}

.vj-show .table-bordered td,
.vj-show .table-bordered th {
    border-color: #e9ecef;
}

.vj-modal-type-options {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.vj-modal-type-options .vj-btn {
    justify-content: center;
    width: 100%;
    padding: 0.65rem 1rem;
}

.vj-btn-warning {
    background: #ffc107;
    color: #212529;
}

.vj-btn-warning:hover:not(:disabled) {
    background: #e0a800;
    color: #212529;
}

.vj-show .vj-form-panel {
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    background: #f8f9fa;
    margin-bottom: 1rem;
}

.vj-show .form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
}

.vj-show .form-control[readonly] {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

.vj-show .vj-form-actions .vj-actions-primary {
    margin-bottom: 0;
}

.vj-alert-warning {
    background: #fff8e1;
    border-color: #ffe082;
    color: #856404;
}

/* SweetAlert2 — VJ Soft UI */
.swal2-container.vj-swal-container {
    background-color: rgba(33, 37, 41, 0.35) !important;
}

.vj-swal-popup.swal2-popup {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    padding: 1.1rem 1rem 0.9rem;
    font-family: inherit;
}

.vj-swal-popup .swal2-title {
    font-size: 1rem;
    font-weight: 600;
    color: #343a40;
    padding: 0 0.25rem;
}

.vj-swal-popup .swal2-html-container {
    font-size: 0.875rem;
    color: #6c757d;
    line-height: 1.45;
    margin: 0.35rem 0 0;
}

.vj-swal-popup .swal2-icon {
    width: 3rem;
    height: 3rem;
    margin: 0.65rem auto 0.5rem;
    border-width: 2px;
}

.vj-swal-popup .swal2-icon .swal2-icon-content {
    font-size: 1.75rem;
    font-weight: 700;
}

.vj-swal-popup .swal2-icon.swal2-warning {
    border-color: #ffe082;
    color: #b8860b;
}

.vj-swal-popup .swal2-icon.swal2-warning .swal2-icon-content {
    color: #b8860b;
}

.vj-swal-popup .swal2-icon.swal2-success {
    border-color: #c8e6c9;
    color: #198754;
}

.vj-swal-popup .swal2-icon.swal2-success [class^='swal2-success-line'] {
    background-color: #198754;
}

.vj-swal-popup .swal2-icon.swal2-success .swal2-success-ring {
    border-color: rgba(25, 135, 84, 0.3);
}

.vj-swal-popup .swal2-icon.swal2-error {
    border-color: #ffcdd2;
    color: #c62828;
}

.vj-swal-popup .swal2-icon.swal2-error [class^='swal2-x-mark-line'] {
    background-color: #c62828;
}

.vj-swal-popup .swal2-icon.swal2-info {
    border-color: #b2ebf2;
    color: #0c6674;
}

.vj-swal-popup .swal2-icon.swal2-question {
    border-color: #b8d4fe;
    color: #0d47a1;
}

.vj-swal-actions.swal2-actions {
    gap: 0.5rem;
    margin: 0.85rem 0 0.15rem;
    width: 100%;
    flex-wrap: wrap;
    justify-content: center;
}

.vj-swal-actions .vj-btn,
.vj-swal-actions .vj-action-item {
    margin: 0;
    min-width: 7rem;
    justify-content: center;
}

.vj-swal-popup.vj-swal-popup-wide {
    width: min(60rem, 96vw) !important;
    max-width: 96vw;
}

.vj-swal-html .vj-swal-summary {
    text-align: left;
}

.vj-swal-panel {
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    background: #f8f9fa;
    height: 100%;
}

.vj-swal-panel-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.65rem;
}

.vj-swal-panel-title i {
    color: #6c757d;
    margin-right: 0.35rem;
}

.vj-swal-meta {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.35rem 0.75rem;
    margin: 0;
}

.vj-swal-meta dt {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6c757d;
    margin: 0;
}

.vj-swal-meta dd {
    font-size: 0.875rem;
    color: #212529;
    margin: 0;
}

.vj-swal-summary .vj-note {
    margin-top: 0.85rem;
}

.vj-swal-summary .vj-note ul {
    margin-bottom: 0;
    padding-left: 1.1rem;
}

.vj-swal-summary .vj-alert {
    margin-top: 0.85rem;
}

.vj-approval-doc-tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    gap: 0;
    margin-top: 0.75rem;
    padding: 0 1rem;
    border-bottom: 1px solid #e9ecef;
}

.vj-approval-doc-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.7rem 1.1rem;
    margin-bottom: -1px;
    border: 1px solid transparent;
    border-radius: 8px 8px 0 0;
    background: transparent;
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.2;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
}

.vj-approval-doc-tab i {
    font-size: 0.875rem;
    line-height: 1;
    flex-shrink: 0;
    margin-right: 0.35rem;
    opacity: 0.85;
}

.vj-approval-doc-tab:hover:not(.is-active) {
    color: #0d6efd;
    background: #f8f9fa;
    text-decoration: none;
}

.vj-approval-doc-tab.is-active {
    color: #0d6efd;
    background: #fff;
    border-color: #e9ecef;
    border-bottom-color: #fff;
    font-weight: 600;
    cursor: default;
}

.vj-approval-doc-tab.is-active i {
    opacity: 1;
}

.vj-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: #dc3545;
    color: #fff;
    font-size: 0.6875rem;
    font-weight: 700;
    line-height: 1;
}

.vj-show .card-header.vj-card-header-tabs {
    background: #f8f9fa;
    border-bottom: none;
}

.vj-show .card-header.vj-card-header-tabs .card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #343a40;
}

@media (max-width: 768px) {
    .vj-approval-doc-tabs {
        padding: 0 0.5rem;
        overflow-x: auto;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .vj-approval-doc-tab {
        padding: 0.6rem 0.85rem;
        font-size: 0.8125rem;
    }
}

.vj-decision-approve {
    background: #d4edda;
    border-color: #b7dfc5;
    color: #155724;
}

.vj-decision-approve:hover:not(:disabled):not(.is-disabled) {
    background: #c3e6cb;
    border-color: #a3d9b1;
    color: #0f5132;
}

.vj-decision-revise {
    background: #e8f7fa;
    border-color: #b2ebf2;
    color: #0c6674;
}

.vj-decision-revise:hover:not(:disabled):not(.is-disabled) {
    background: #d1f2f7;
    border-color: #80deea;
    color: #0a4f5a;
}

</style>
