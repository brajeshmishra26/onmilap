<?php
if (!defined('ONMILAP_SUBSCRIPTION_STYLES')) {
    define('ONMILAP_SUBSCRIPTION_STYLES', true);
    ?>
    <style>
        .subscription-page {
            padding: 32px;
            max-width: 1200px;
            margin: 0 auto;
            color: #f5f5f7;
        }
        .subscription-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 32px;
            background: linear-gradient(135deg, rgba(16,24,40,0.9), rgba(88,28,135,0.85));
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            color: #f8fafc;
            box-shadow: 0 20px 60px rgba(15,23,42,0.4);
        }
        .subscription-hero h1 {
            margin: 4px 0 8px;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }
        .subscription-hero .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 12px;
            opacity: 0.75;
            margin: 0;
            color: rgba(226,232,240,0.85);
        }
        .subscription-hero .subtitle {
            margin: 0;
            opacity: 0.9;
            max-width: 560px;
            color: rgba(241,245,249,0.9);
        }
        .subscription-hero .back-to-chat,
        .subscription-hero .secondary-btn {
            border: none;
            padding: 10px 22px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            color: #111827;
            background: #f5f5f5;
            box-shadow: inset 0 -2px rgba(255,255,255,0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .subscription-hero .secondary-btn {
            background: transparent;
            color: #f8fafc;
            border: 1px solid rgba(248,250,252,0.4);
            box-shadow: none;
        }
        .subscription-widget-wrapper,
        .plan-details-panel,
        .history-panel {
            background: rgba(15,23,42,0.5);
            border: 1px solid rgba(255,255,255,0.12);
            padding: 24px;
            border-radius: 16px;
        }
        #subscription-widget {
            padding: 0;
            width: 100%;
        }
        #subscription-widget.py-4 {
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }
        #subscription-widget label.form-label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }
        #subscription-widget .fw-semibold { font-weight: 600; }
        #subscription-widget .fw-bold { font-weight: 700; }
        #subscription-widget .fs-3 { font-size: 1.75rem; }
        #subscription-widget .text-muted { opacity: 0.7; }
        #subscription-widget .mt-4 { margin-top: 1.5rem; }
        #subscription-widget .mt-2 { margin-top: 0.5rem; }
        #subscription-widget .mb-3 { margin-bottom: 1rem; }
        #subscription-widget .mb-1 { margin-bottom: 0.25rem; }
        #subscription-widget .mb-0 { margin-bottom: 0; }
        #subscription-widget .form-select,
        #subscription-widget select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(9,14,26,0.85);
            color: #f8fafc;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            box-shadow: inset 0 0 0 1px rgba(15,23,42,0.4);
        }
        #subscription-widget select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99,102,241,0.35);
            outline: none;
        }
        #subscription-widget select option {
            background: #0f172a;
            color: #f8fafc;
        }
        #subscription-widget .row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin: 0;
        }
        #subscription-widget .g-3 {
            gap: 16px;
        }
        #subscription-widget .col-md-4,
        #subscription-widget .col-md-3 {
            flex: 1 1 220px;
            padding: 0;
        }
        #subscription-widget .plan-card,
        .plan-stat-card,
        .history-card {
            height: 100%;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            background: rgba(15,23,42,0.85);
            transition: transform 0.2s ease, border-color 0.2s ease;
            color: #f8fafc;
        }
        #subscription-widget .shadow-sm {
            box-shadow: none;
        }
        #subscription-widget .card-body,
        .plan-stat-card .card-body,
        .history-card .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        #subscription-widget .plan-card:hover,
        .plan-stat-card:hover,
        .history-card:hover {
            transform: translateY(-4px);
            border-color: rgba(99,102,241,0.8);
        }
        #subscription-widget .plan-card.selected-plan {
            border-color: rgba(99,102,241,1);
            box-shadow: 0 10px 35px rgba(99,102,241,0.2);
        }
        #subscription-widget .plan-price,
        .stat-value {
            font-size: 32px;
            color: #e0e7ff;
        }
        #subscription-widget .plan-validity,
        #subscription-widget .plan-minutes,
        .stat-label {
            font-size: 14px;
            color: #cbd5f5;
        }
        #subscription-widget .btn,
        .history-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }
        #subscription-widget .btn-primary,
        .history-action {
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border: none;
            color: #fff;
        }
        #subscription-widget .w-100 {
            width: 100%;
        }
        #subscription-widget .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .plan-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .plan-meta-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .plan-meta-list li {
            padding: 12px 16px;
            border-radius: 12px;
            background: rgba(15,23,42,0.7);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .plan-meta-list span {
            display: block;
            font-size: 13px;
            opacity: 0.75;
        }
        .plan-meta-list strong {
            display: block;
            font-size: 16px;
            color: #f8fafc;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            color: #f8fafc;
        }
        .history-table th,
        .history-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-align: left;
            font-size: 14px;
        }
        .history-table th {
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 12px;
            opacity: 0.7;
        }
        .history-status {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            text-transform: capitalize;
        }
        .history-status.active {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
        }
        .history-status.pending {
            background: rgba(250,204,21,0.18);
            color: #facc15;
        }
        .history-empty {
            text-align: center;
            padding: 32px 16px;
            color: rgba(248,250,252,0.75);
        }
        @media (max-width: 768px) {
            .subscription-page {
                padding: 16px;
            }
            .subscription-hero {
                padding: 16px;
            }
            .history-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
    <?php
}
?>
