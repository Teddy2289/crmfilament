@php
$rappelCodes = $this->getRappelStatusCodes();
$maxTentatives = app(\App\Services\Crm\CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
$tentativesActuelles = $this->getTentativesAppel();
@endphp

<x-filament-panels::page>
    @push('styles')
    <style>
        .pw-wrap * {
            box-sizing: border-box;
        }

        .pw-wrap {
            padding: 0;
            position: relative;
        }

        #login-container .login #login_front {
            z-index: 2 !important;
        }

        .pw-supervision {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.5rem 1rem;
            background: rgb(239 246 255);
            border-bottom: 1px solid rgb(219 234 254);
            font-size: 0.8125rem;
            color: rgb(55 65 81);
        }

        .dark .pw-supervision {
            background: rgb(23 37 84 / 0.2);
            border-bottom-color: rgb(30 58 138 / 0.3);
            color: rgb(147 197 253);
        }

        .pw-sup-avatar {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.5rem;
            background: rgb(37 99 235);
            color: white;
            font-weight: 700;
            font-size: 0.6875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s;
        }

        .pw-sup-avatar:hover {
            background: rgb(29 78 216);
        }

        .pw-sup-avatar.active {
            background: rgb(37 99 235);
            box-shadow: 0 0 0 2px white, 0 0 0 4px rgb(37 99 235);
        }

        .pw-sup-self {
            background: rgb(22 163 74);
        }

        .pw-sup-self:hover {
            background: rgb(15 118 65);
        }

        .pw-sup-divider {
            width: 1px;
            height: 1.25rem;
            background: rgb(191 219 254);
            margin: 0 0.25rem;
        }

        /* ── Icônes ── */
        .pw-icon {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        .pw-icon-sm {
            width: 0.8125rem;
            height: 0.8125rem;
            flex-shrink: 0;
        }

        .pw-icon-lg {
            width: 1.375rem;
            height: 1.375rem;
            flex-shrink: 0;
        }

        .pw-icon-xl {
            width: 2rem;
            height: 2rem;
            flex-shrink: 0;
        }

        /* ── Card entreprise (résumé en haut) ── */
        .pw-summary-card {
            margin: 1rem 1.25rem;
            background: white;
            border: 1px solid rgb(229 231 235);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
            overflow: hidden;
        }

        .dark .pw-summary-card {
            background: rgb(17 24 39);
            border-color: rgb(31 41 55);
        }

        .pw-summary-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.125rem 1.25rem 0.875rem;
            flex-wrap: wrap;
        }

        .pw-summary-identity {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            min-width: 0;
        }

        .pw-summary-avatar {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.875rem;
            background: linear-gradient(135deg, rgb(96 165 250), rgb(37 99 235));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pw-summary-name-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .pw-summary-name {
            font-size: 1.0625rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .pw-alert-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3125rem;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.1875rem 0.5625rem;
            border-radius: 9999px;
        }

        .pw-alert-badge-red {
            background: rgb(254 226 226);
            color: rgb(153 27 27);
        }

        .pw-alert-badge-amber {
            background: rgb(254 243 199);
            color: rgb(146 64 14);
        }

        .pw-summary-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .pw-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem 1.75rem;
            padding: 0.875rem 1.25rem 1.125rem;
            border-top: 1px solid rgb(243 244 246);
        }

        .dark .pw-summary-grid {
            border-top-color: rgb(31 41 55);
        }

        @media (max-width: 900px) {
            .pw-summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .pw-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        .pw-summary-field {
            display: flex;
            align-items: flex-start;
            gap: 0.5625rem;
            min-width: 0;
        }

        .pw-summary-field-icon {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.5rem;
            background: rgb(243 244 246);
            color: rgb(100 116 139);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .pw-summary-field-icon {
            background: rgb(31 41 55);
            color: rgb(148 163 184);
        }

        .pw-summary-field-text {
            min-width: 0;
        }

        .pw-summary-field-label {
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 700;
            color: rgb(148 163 184);
            margin-bottom: 0.0625rem;
        }

        .pw-summary-field-value {
            font-size: 0.8125rem;
            font-weight: 600;
            color: rgb(30 41 59);
            line-height: 1.35;
            overflow-wrap: break-word;
        }

        .dark .pw-summary-field-value {
            color: rgb(226 232 240);
        }

        .pw-contact-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.25rem;
            background: white;
            border-bottom: 1px solid rgb(229 231 235);
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .dark .pw-contact-bar {
            background: rgb(17 24 39);
            border-bottom-color: rgb(31 41 55);
        }

        .pw-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .pw-badge-rpc {
            background: rgb(204 251 241);
            color: rgb(17 94 89);
        }

        .pw-badge-rp {
            background: rgb(220 252 231);
            color: rgb(20 83 45);
        }

        .pw-badge-std_joint {
            background: rgb(219 234 254);
            color: rgb(30 64 175);
        }

        .pw-badge-ac {
            background: rgb(243 244 246);
            color: rgb(55 65 81);
            border: 1px solid rgb(229 231 235);
        }

        .pw-badge-std_nr {
            background: rgb(243 244 246);
            color: rgb(107 114 128);
        }

        .pw-badge-cse_nr {
            background: rgb(255 237 213);
            color: rgb(154 52 18);
        }

        .pw-badge-ko {
            background: rgb(254 226 226);
            color: rgb(153 27 27);
        }

        .pw-badge-info {
            background: rgb(219 234 254);
            color: rgb(30 64 175);
        }

        .pw-badge-gray {
            background: rgb(243 244 246);
            color: rgb(55 65 81);
        }

        .pw-timer {
            text-align: right;
        }

        .pw-timer-label {
            font-size: 0.6875rem;
            color: rgb(107 114 128);
            text-transform: uppercase;
        }

        .pw-timer-value {
            font-size: 1.25rem;
            font-family: monospace;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .pw-btn-call {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.375rem;
            background: rgb(34 197 94);
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            border-radius: 0.625rem;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.15s;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
        }

        .pw-btn-call:hover {
            background: rgb(22 163 74);
        }

        .pw-btn-call-alt {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            background: rgb(243 244 246);
            color: rgb(55 65 81);
            font-weight: 600;
            font-size: 0.8125rem;
            border-radius: 0.625rem;
            border: 1px solid rgb(229 231 235);
            cursor: pointer;
            transition: background 0.15s;
        }

        .pw-btn-call-alt:hover {
            background: rgb(229 231 235);
        }

        .pw-en-file {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgb(249 250 251);
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            padding: 0.5rem 0.875rem;
            min-width: 3.5rem;
            text-align: center;
        }

        .dark .pw-en-file {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
        }

        .pw-en-file-num {
            font-size: 1rem;
            font-weight: 700;
        }

        .pw-en-file-label {
            font-size: 0.525rem;
            text-transform: uppercase;
            color: rgb(107 114 128);
        }

        .pw-body {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 0;
            height: auto;
        }

        @media (max-width: 1024px) {
            .pw-body {
                grid-template-columns: 1fr;
                height: auto;
                overflow: visible;
            }
        }

        .pw-left {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 0 1.25rem 1.5rem;
            border-right: 1px solid rgb(229 231 235);
        }

        .dark .pw-left {
            border-right-color: rgb(31 41 55);
        }

        .pw-card {
            background: white;
            border: 1px solid rgb(229 231 235);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        .dark .pw-card {
            background: rgb(17 24 39);
            border-color: rgb(31 41 55);
        }

        .pw-result-panel {
            padding: 1rem 1.25rem 1.125rem;
        }

        .pw-result-header {
            display: flex;
            align-items: center;
            gap: 0.5625rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(30 41 59);
            margin-bottom: 0.875rem;
        }

        .dark .pw-result-header {
            color: rgb(226 232 240);
        }

        .pw-result-header-icon {
            width: 1.875rem;
            height: 1.875rem;
            border-radius: 0.625rem;
            background: rgb(219 234 254);
            color: rgb(37 99 235);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .pw-result-header-icon {
            background: rgb(30 58 138 / 0.4);
            color: rgb(147 197 253);
        }

        /* ── Onglets "Cas" du résultat d'appel ── */
        .pw-case-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-bottom: 0.875rem;
        }

        .pw-case-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: rgb(71 85 105);
            background: rgb(241 245 249);
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s ease;
        }

        .dark .pw-case-tab {
            background: rgb(30 41 59);
            color: rgb(148 163 184);
        }

        .pw-case-tab:hover {
            background: rgb(226 232 240);
        }

        .dark .pw-case-tab:hover {
            background: rgb(51 65 81);
        }

        .pw-case-tab.active {
            background: rgb(37 99 235);
            color: white;
        }

        .pw-case-panel {
            display: none;
        }

        .pw-case-panel.active {
            display: block;
        }

        .pw-chip-group {
            display: flex;
            flex-direction: column;
            gap: 0.3125rem;
            padding: 0.5rem 0.5625rem;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.75rem;
            background: rgb(249 250 251);
        }

        .dark .pw-chip-group {
            border-color: rgb(51 65 85);
            background: rgb(30 41 59 / .4);
        }

        .pw-chip-group-label {
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgb(100 116 139);
        }

        .dark .pw-chip-group-label {
            color: rgb(148 163 184);
        }

        .pw-chip-row {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .pw-chip {
            display: flex;
            align-items: flex-start;
            gap: 0.4375rem;
            padding: 0.3125rem 0.5rem;
            border-radius: 0.5rem;
            border-left: 3px solid transparent;
            background: white;
            cursor: pointer;
            transition: all .15s ease;
        }

        .dark .pw-chip {
            background: rgb(31 41 55);
        }

        .pw-chip:hover {
            background: rgb(243 244 246);
        }

        .dark .pw-chip:hover {
            background: rgb(51 65 81);
        }

        .pw-chip-icon {
            font-size: 0.8125rem;
            line-height: 1.15;
            flex-shrink: 0;
        }

        .pw-chip-main {
            flex: 1;
            min-width: 0;
        }

        .pw-chip-label-row {
            display: flex;
            align-items: center;
            gap: 0.3125rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(30 41 59);
        }

        .dark .pw-chip-label-row {
            color: rgb(226 232 240);
        }

        .pw-chip-sub {
            display: block;
            font-size: 0.6000rem;
            color: rgb(100 116 139);
            margin-top: 1px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .pw-chip-sub {
            color: rgb(148 163 184);
        }

        .pw-chip-star {
            font-size: 0.625rem;
        }

        .pw-infos {
            background: white;
        }

        .dark .pw-infos {
            background: rgb(17 24 39);
        }

        .pw-infos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            background: rgb(249 250 251);
            border-bottom: 1px solid rgb(229 231 235);
        }

        .dark .pw-infos-header {
            background: rgb(31 41 55);
            border-bottom-color: rgb(55 65 81);
        }

        .pw-infos-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgb(30 41 59);
        }

        .dark .pw-infos-title {
            color: rgb(226 232 240);
        }

        .pw-infos-title-icon {
            width: 1.875rem;
            height: 1.875rem;
            border-radius: 0.625rem;
            background: rgb(219 234 254);
            color: rgb(37 99 235);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dark .pw-infos-title-icon {
            background: rgb(30 58 138 / 0.4);
            color: rgb(147 197 253);
        }

        .pw-info-tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 1px solid rgb(229 231 235);
            background: rgb(249 250 251);
        }

        .dark .pw-info-tabs {
            background: rgb(31 41 55);
            border-bottom-color: rgb(55 65 81);
        }

        .pw-info-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(107 114 128);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
        }

        .pw-info-tab.active {
            color: rgb(37 99 235);
            border-bottom-color: rgb(37 99 235);
        }

        .pw-info-panel {
            padding: 1rem 1.25rem;
        }

        .pw-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem 1.25rem;
        }

        .pw-info-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem 1rem;
        }

        .pw-field-label {
            font-size: 0.6875rem;
            text-transform: uppercase;
            font-weight: 600;
            color: rgb(107 114 128);
            margin-bottom: 0.25rem;
        }

        .pw-field-value {
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .pw-field-value.copyable {
            cursor: pointer;
            text-decoration: underline dotted;
            color: rgb(37 99 235);
        }

        .pw-field-value.copyable:hover {
            text-decoration: underline;
        }

        .pw-rappel-late {
            color: rgb(239 68 68);
            font-weight: 700;
        }

        .pw-field-input {
            width: 100%;
            padding: 0.375rem 0.625rem;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            color: inherit;
            outline: none;
        }

        .dark .pw-field-input {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
        }

        .pw-field-full {
            grid-column: span 2;
        }

        .pw-field-full-3 {
            grid-column: span 3;
        }

        .pw-section-divider {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgb(100 116 139);
            margin: 1.375rem 0 0.75rem;
            padding-top: 1.125rem;
            border-top: 1px dashed rgb(226 232 240);
        }

        .dark .pw-section-divider {
            color: rgb(148 163 184);
            border-top-color: rgb(51 65 85);
        }

        .pw-interlocuteur-card {
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            background: rgb(249 250 251);
        }

        .dark .pw-interlocuteur-card {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
        }

        .pw-notes-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .pw-note-item {
            background: rgb(249 250 251);
            border-radius: 0.5rem;
            padding: 0.625rem 0.875rem;
            font-size: 0.8125rem;
            border-left: 3px solid rgb(229 231 235);
        }

        .dark .pw-note-item {
            background: rgb(31 41 55);
        }

        .pw-note-date {
            font-size: 0.6875rem;
            color: rgb(107 114 128);
            margin-bottom: 0.25rem;
        }

        .pw-right {
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: white;
            padding: 1rem;
        }

        .dark .pw-right {
            background: rgb(17 24 39);
        }

        /* ── Widget Ringover : on lui redonne toute la largeur du panneau ── */
        /* #ringover-embed-phoning {
            width: calc(100% + 2rem);
            margin: 0 -1rem 1rem -1rem;
            box-sizing: border-box;
        } */

        #ringover-embed-phoning iframe {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            border: none !important;
        }

        .pw-nr-box {
            background: rgb(249 250 251);
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            margin-bottom: 1.25rem;
            border: 1px solid rgb(229 231 235);
        }

        .dark .pw-nr-box {
            background: rgb(31 41 55);
            border-color: rgb(55 65 81);
        }

        .pw-nr-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(107 114 128);
        }

        .pw-nr-subtitle {
            font-size: 0.6875rem;
            color: rgb(156 163 175);
        }

        .pw-nr-count {
            font-size: 1rem;
            color: rgb(158, 9, 9);
            font-weight: 700;
        }

        .pw-nr-tentatives {
            font-size: 0.875rem;
            color: rgb(107 114 128);
        }

        .pw-issue-title {
            font-size: 0.875rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .pw-issue-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.875rem;
            margin-bottom: 0.4rem;
            border-radius: 0.5rem;
            cursor: pointer;
            border: 1px solid rgb(229 231 235);
            transition: all 0.15s;
        }

        .dark .pw-issue-option {
            border-color: rgb(55 65 81);
        }

        .pw-issue-option:hover {
            background: rgb(249 250 251);
        }

        .dark .pw-issue-option:hover {
            background: rgb(31 41 55);
        }

        .pw-issue-label {
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .pw-issue-sub {
            font-size: 0.6875rem;
            color: rgb(107 114 128);
        }

        .pw-opt-bar {
            width: 3px;
            height: 2rem;
            border-radius: 9999px;
            flex-shrink: 0;
            margin-right: 0.625rem;
        }

        .pw-opt-active-gray {
            border-color: rgb(107 114 128) !important;
            background: rgb(249 250 251);
        }

        .pw-opt-active-blue {
            border-color: rgb(59 130 246) !important;
            background: rgb(239 246 255);
        }

        .pw-opt-active-orange {
            border-color: rgb(249 115 22) !important;
            background: rgb(255 247 237);
        }

        .pw-opt-active-green {
            border-color: rgb(34 197 94) !important;
            background: rgb(240 253 244);
        }

        .pw-opt-active-teal {
            border-color: rgb(20 184 166) !important;
            background: rgb(240 253 250);
        }

        .pw-opt-active-red {
            border-color: rgb(239 68 68) !important;
            background: rgb(254 242 242);
        }

        .dark .pw-opt-active-gray {
            background: rgb(17 24 39 / 0.5);
        }

        .dark .pw-opt-active-blue {
            background: rgb(23 37 84 / 0.3);
        }

        .dark .pw-opt-active-orange {
            background: rgb(124 45 18 / 0.3);
        }

        .dark .pw-opt-active-green {
            background: rgb(5 46 22 / 0.3);
        }

        .dark .pw-opt-active-teal {
            background: rgb(19 78 74 / 0.3);
        }

        .dark .pw-opt-active-red {
            background: rgb(127 29 29 / 0.3);
        }

        .pw-rappel-box {
            background: rgb(240 253 244);
            border: 1px solid rgb(187 247 208);
            border-radius: 0.75rem;
            padding: 0.75rem;
            margin-top: 0.75rem;
            display: none;
        }

        .pw-rappel-box.visible {
            display: block;
        }

        .pw-rappel-box-title {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: rgb(22 101 52);
            margin-bottom: 0.5rem;
        }

        .pw-fiche-recap {
            margin-top: 0.75rem;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 2px solid transparent;
        }

        .pw-fiche-recap-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .pw-textarea {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.625rem;
            font-size: 0.8125rem;
            background: rgb(249 250 251);
            color: inherit;
            resize: vertical;
            outline: none;
            margin-top: 1rem;
            transition: box-shadow 0.15s;
            min-height: 4rem;
        }

        .pw-textarea:focus {
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.4);
            border-color: rgb(59 130 246);
        }

        .dark .pw-textarea {
            background: rgb(31 41 55 / 0.5);
            border-color: rgb(55 65 81);
        }

        .pw-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.875rem;
        }

        .pw-btn-primary {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4375rem;
            padding: 0.625rem;
            background: rgb(34 197 94);
            color: white;
            font-weight: 700;
            font-size: 0.8125rem;
            border-radius: 0.625rem;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
        }

        .pw-btn-primary:hover {
            background: rgb(22 163 74);
        }

        .pw-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.4375rem;
            padding: 0.625rem 1rem;
            background: rgb(229 231 235);
            color: rgb(55 65 81);
            font-weight: 600;
            font-size: 0.8125rem;
            border-radius: 0.625rem;
            border: none;
            cursor: pointer;
        }

        .pw-btn-secondary:hover {
            background: rgb(209 213 219);
        }

        .dark .pw-btn-secondary {
            background: rgb(55 65 81);
            color: rgb(209 213 219);
        }
    </style>

    <script>
        let timerInterval = null;
        let timerSeconds = 0;

        function startTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerSeconds = 0;
            }
            timerInterval = setInterval(() => {
                timerSeconds++;
                const m = String(Math.floor(timerSeconds / 60)).padStart(2, '0');
                const s = String(timerSeconds % 60).padStart(2, '0');
                const el = document.querySelector('.pw-timer-value');
                if (el) el.textContent = m + ' : ' + s;
            }, 1000);
        }

        const pwRappelCodes = @json($rappelCodes);

        function toggleRappel(val) {
            const box = document.getElementById('pw-rappel-box');
            if (box) {
                box.classList.toggle('visible', pwRappelCodes.includes(val));
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                Livewire.dispatch('notify', {
                    message: 'Copié !'
                });
            });
        }

        function switchInfoTab(tab) {
            document.querySelectorAll('.pw-info-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.pw-info-panel[data-tab]').forEach(p => p.style.display = 'none');
            document.querySelector(`.pw-info-tab[data-tab="${tab}"]`).classList.add('active');
            document.querySelector(`.pw-info-panel[data-tab="${tab}"]`).style.display = 'block';
        }

        function switchCaseTab(tab) {
            document.querySelectorAll('.pw-case-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.pw-case-panel').forEach(p => p.classList.remove('active'));
            const btn = document.querySelector(`.pw-case-tab[data-case-tab="${tab}"]`);
            const panel = document.querySelector(`.pw-case-panel[data-case-panel="${tab}"]`);
            if (btn) btn.classList.add('active');
            if (panel) panel.classList.add('active');
        }

        document.addEventListener('livewire:navigated', () => {
            switchInfoTab('contact');
        });
        document.addEventListener('DOMContentLoaded', () => {
            switchInfoTab('contact');
        });
    </script>
    @endpush

    @push('scripts')
    <script>
        // Fonction d'appel unifiée qui s'appuie uniquement sur l'instance globale du Panel Provider
        function appelerAvecRingover(phoneNumber) {
            if (!phoneNumber) return;

            const cleanedPhone = phoneNumber.replace(/[^0-9+]/g, '');

            if (window.ringoverPhone && typeof window.ringoverPhone.dial === 'function') {
                window.ringoverPhone.show();
                window.ringoverPhone.dial(cleanedPhone);
            } else {
                // Fallback si le SDK n'est pas encore prêt
                window.location.href = 'tel:' + cleanedPhone;
            }
        }

        function rechercherAppelEntrant(numero) {
            if (!numero) return;
            const normalized = String(numero).replace(/[^0-9+]/g, '');
            if (!normalized) return;
            Livewire.dispatch('search-incoming-call', { phone: normalized });
        }

        window.appelerAvecRingover = appelerAvecRingover;
        window.rechercherAppelEntrant = rechercherAppelEntrant;
    </script>
    @endpush

    @php
    $info = $this->getContactInfo();
    $tel = $info['telephone'] ?? null;
    $teleprospecteurs = $this->getTeleprospecteurs();
    $nbEnFile = $this->getContactsRestantsCount();
    $progress = $this->progress;
    $statutsGroupes = $this->getStatutsPhoningGroupes();
    $options = $this->getStatutsPhoning();
    $callHistory = $this->getCallHistory();
    $statutCls = 'pw-badge-' . ($info['statut'] ?? 'ac');
    if (!isset($info['statut'])) {
    $statutCls = 'pw-badge-gray';
    }
    $statutLabel = $info['statut_label'] ?? ($info['statut'] ?? 'AC');
    $notes = $info['notes'] ?? null;
    $noteLines = [];
    if ($notes) {
    foreach (explode("\n", $notes) as $line) {
    $line = trim($line);
    if (!$line) {
    continue;
    }
    if (preg_match('/^\[(\d{2}\/\d{2}\/\d{4}[^\]]*)\]\s*(.+)$/', $line, $m)) {
    $noteLines[] = ['date' => $m[1], 'text' => $m[2]];
    } else {
    $noteLines[] = ['date' => null, 'text' => $line];
    }
    }
    }
    // Onglet "cas" actif : celui contenant le statut sélectionné, sinon le premier.
    $activeGroupeKey = null;
    foreach ($statutsGroupes as $gKey => $g) {
    foreach ($g['statuts'] as $gOption) {
    if ($gOption['value'] === $statut_resultat) {
    $activeGroupeKey = $gKey;
    break 2;
    }
    }
    }
    if ($activeGroupeKey === null) {
    $activeGroupeKey = array_key_first($statutsGroupes);
    }
    @endphp

    <div class="pw-wrap">

        <div style="display:flex; align-items:center; gap:1rem; padding:0.5rem 1rem; background:rgb(249 250 251); border-bottom:1px solid rgb(229 231 235); flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:0.5rem; flex:1; min-width:200px; position:relative;">
                <svg style="width:1.125rem;height:1.125rem;color:rgb(156 163 175);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text"
                    wire:model.live="searchQuery"
                    wire:keydown.escape="clearSearch"
                    placeholder="Rechercher un contact (nom, téléphone, SIRET...)"
                    class="pw-field-input"
                    style="flex:1; padding:0.375rem 0.75rem; font-size:0.8125rem; border:1px solid rgb(209 213 219); border-radius:0.5rem; outline:none;">
                @if ($searchQuery)
                <button wire:click="clearSearch"
                    style="position:absolute; right:0.5rem; background:none; border:none; color:rgb(156 163 175); cursor:pointer; font-size:1rem;">
                    ✕
                </button>
                @endif
            </div>
        </div>

        {{-- RÉSULTATS DE RECHERCHE --}}
        @if ($showSearchResults && count($searchResults) > 0)
        <div style="position:absolute; z-index:1000; background:white; border:1px solid rgb(229 231 235); border-radius:0.75rem; box-shadow:0 10px 25px rgba(0,0,0,0.15); max-height:400px; overflow-y:auto; width:100%; max-width:600px; margin-top:0.25rem;">
            @foreach ($searchResults as $result)
            <div wire:click="selectSearchResult({{ $result['id'] }}, '{{ $result['type'] }}')"
                style="display:flex; align-items:center; justify-content:space-between; padding:0.625rem 1rem; cursor:pointer; border-bottom:1px solid rgb(243 244 246); transition:background 0.15s;"
                onmouseover="this.style.background='rgb(249 250 251)'" onmouseout="this.style.background='white'">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                        <span style="font-weight:600; font-size:0.875rem; color:rgb(17 24 39);">{{ $result['nom'] }}</span>
                        <span style="font-size:0.625rem; padding:0.125rem 0.375rem; border-radius:9999px; background:rgb(219 234 254); color:rgb(30 64 175); font-weight:600;">
                            {{ $result['type_entite'] }}
                        </span>
                        @if ($result['statut'])
                        <span style="font-size:0.625rem; padding:0.125rem 0.375rem; border-radius:9999px; background:rgb(243 244 246); color:rgb(55 65 81);">
                            {{ $result['statut'] }}
                        </span>
                        @endif
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem; font-size:0.75rem; color:rgb(107 114 128); margin-top:0.125rem; flex-wrap:wrap;">
                        @if ($result['telephone'])
                        <span style="display:inline-flex; align-items:center; gap:0.25rem;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            {{ $result['telephone'] }}
                        </span>
                        @endif
                        @if ($result['ville'])
                        <span style="display:inline-flex; align-items:center; gap:0.25rem;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            {{ $result['ville'] }}
                        </span>
                        @endif
                    </div>
                </div>
                <button onclick="event.stopPropagation(); appelerAvecRingover('{{ $result['telephone'] }}')" style="padding:0.25rem 0.75rem; background:rgb(34 197 94); color:white; border:none; border-radius:0.5rem; font-size:0.6875rem; font-weight:600; cursor:pointer; white-space:nowrap; flex-shrink:0;">
                    Appeler →
                </button>
            </div>
            @endforeach
        </div>
        @elseif ($showSearchResults && strlen($searchQuery) >= 2)
        <div style="position:absolute; z-index:1000; background:white; border:1px solid rgb(229 231 235); border-radius:0.75rem; box-shadow:0 10px 25px rgba(0,0,0,0.15); padding:1rem; text-align:center; color:rgb(107 114 128); width:100%; max-width:600px; margin-top:0.25rem;">
            <svg style="width:2rem;height:2rem;margin:0 auto 0.5rem;color:rgb(203 213 225);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <div style="font-size:0.875rem;">Aucun contact trouvé pour "{{ $searchQuery }}"</div>
        </div>
        @endif
        @if ($incomingCallPhone || $incomingCallMatches)
        <div style="margin:1rem 1.25rem 0; background:rgb(239 246 255); border:1px solid rgb(191 219 254); border-radius:0.75rem; padding:0.875rem 1rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div>
                    <div style="font-size:0.625rem; text-transform:uppercase; letter-spacing:0.08em; color:rgb(30 64 175); font-weight:700;">Appel entrant détecté</div>
                    <div style="font-size:1rem; font-weight:700; color:rgb(30 41 59); margin-top:0.25rem;">{{ $incomingCallPhone ?? 'Numéro inconnu' }}</div>
                </div>
                @if ($incomingCallMatches)
                <button type="button" onclick="rechercherAppelEntrant('{{ $incomingCallPhone }}')" style="padding:0.5rem 0.875rem; border:none; border-radius:0.5rem; background:rgb(37 99 235); color:white; font-weight:600; cursor:pointer;">
                    Rechercher la fiche
                </button>
                @endif
            </div>

            @if ($incomingCallMatches)
            <div style="margin-top:0.75rem; display:flex; flex-direction:column; gap:0.5rem;">
                @foreach ($incomingCallMatches as $match)
                <button type="button" wire:click="selectSearchResult({{ $match['id'] }}, '{{ $match['type'] }}')" style="text-align:left; padding:0.625rem 0.75rem; border:1px solid rgb(191 219 254); border-radius:0.625rem; background:white; cursor:pointer;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
                        <div>
                            <div style="font-weight:700; color:rgb(17 24 39);">{{ $match['nom'] }}</div>
                            <div style="font-size:0.75rem; color:rgb(75 85 99); margin-top:0.125rem;">{{ $match['type_entite'] }} · {{ $match['telephone'] ?? '—' }}</div>
                        </div>
                        @if ($match['statut'])
                        <span style="font-size:0.625rem; padding:0.15rem 0.45rem; border-radius:9999px; background:rgb(243 244 246); color:rgb(55 65 81); font-weight:600;">{{ $match['statut'] }}</span>
                        @endif
                    </div>
                </button>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        @if ($currentContact)

        {{-- ── CARD ENTREPRISE ── --}}
        <div class="pw-summary-card">
            <div class="pw-summary-top">
                <div class="pw-summary-identity">
                    <div class="pw-summary-avatar">
                        <svg class="pw-icon-xl" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M4.5 3h6.75v18M4.5 3v18M11.25 3h8.25v18M11.25 3v18M7.5 6.75h.008v.008H7.5V6.75Zm0 3h.008v.008H7.5V9.75Zm0 3h.008v.008H7.5v-.008Zm7.5-6h.008v.008h-.008V6.75Zm0 3h.008v.008h-.008V9.75Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="pw-summary-name-row">
                            <h2 class="pw-summary-name">
                                {{ Str::upper(trim(($info['prenom'] ?? '') . ' ' . ($info['nom'] ?? ''))) ?: 'CONTACT SANS NOM' }}
                            </h2>
                            <span class="pw-badge {{ $statutCls }}">{{ $statutLabel }}</span>
                            @if (!empty($info['rappel_en_retard']) && $info['rappel_en_retard'])
                            <span class="pw-alert-badge pw-alert-badge-red">
                                <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                Rappel en retard
                            </span>
                            @endif
                            @if (!empty($info['difficile']))
                            <span class="pw-alert-badge pw-alert-badge-amber">
                                <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                                Fiche difficile
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pw-summary-actions">
                    <button wire:click="callNow" onclick="startTimer(); appelerAvecRingover('{{ $tel }}')" class="pw-btn-call">
                        <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ $tel ?? 'Appeler' }}
                    </button>

                    <div class="pw-en-file">
                        <span class="pw-en-file-num">{{ $nbEnFile }}</span>
                        <span class="pw-en-file-label">EN FILE</span>
                    </div>
                </div>
            </div>

            <div class="pw-summary-grid">
                <div class="pw-summary-field">
                    <span class="pw-summary-field-icon">
                        <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                    </span>
                    <span class="pw-summary-field-text">
                        <span class="pw-summary-field-label">SIRET</span>
                        <span class="pw-summary-field-value" title="{{ $info['siret'] ?? '' }}">{{ $info['siret'] ?? '—' }}</span>
                    </span>
                </div>

                <div class="pw-summary-field">
                    <span class="pw-summary-field-icon">
                        <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    </span>
                    @php
                    $localisation = collect([$info['ville'] ?? null, $info['code_postal'] ?? null])->filter()->implode(' · ');
                    if (!empty($info['departement'])) {
                    $localisation .= $localisation ? ' — Dépt ' . $info['departement'] : 'Dépt ' . $info['departement'];
                    }
                    @endphp
                    <span class="pw-summary-field-text">
                        <span class="pw-summary-field-label">Localisation</span>
                        <span class="pw-summary-field-value" title="{{ $localisation ?: '' }}">{{ $localisation ?: '—' }}</span>
                    </span>
                </div>

                <div class="pw-summary-field">
                    <span class="pw-summary-field-icon">
                        <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                    </span>
                    <span class="pw-summary-field-text">
                        <span class="pw-summary-field-label">Adresse</span>
                        <span class="pw-summary-field-value" title="{{ $info['adresse_complete'] ?? '' }}">{{ $info['adresse_complete'] ?? '—' }}</span>
                    </span>
                </div>

                <div class="pw-summary-field">
                    <span class="pw-summary-field-icon">
                        <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </span>
                    <span class="pw-summary-field-text">
                        <span class="pw-summary-field-label">Secteur d'activité</span>
                        <span class="pw-summary-field-value" title="{{ $info['secteur_activite'] ?? '' }}">{{ $info['secteur_activite'] ?? '—' }}</span>
                    </span>
                </div>

                <div class="pw-summary-field">
                    <span class="pw-summary-field-icon">
                        <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </span>
                    <span class="pw-summary-field-text">
                        <span class="pw-summary-field-label">Nombre de salariés</span>
                        <span class="pw-summary-field-value">{{ $info['nb_salaries'] ?? '—' }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="pw-body">
            <div class="pw-left">

                {{-- ── DOSSIER PROSPECT (en premier) ── --}}
                <div class="pw-infos pw-card">
                    <div class="pw-infos-header">
                        <span class="pw-infos-title">
                            <span class="pw-infos-title-icon">
                                <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5" /></svg>
                            </span>
                            Dossier Prospect
                        </span>
                        @php
                            $editActionRoute = null;
                            if (!empty($info['id'])) {
                                match ($info['type'] ?? '') {
                                    'prospect' => $editActionRoute = \App\Filament\NsConseil\Resources\ProspectResource::getUrl('edit', ['record' => $info['id']]),
                                    'partenaire' => $editActionRoute = \App\Filament\NsConseil\Resources\PartenaireResource::getUrl('edit', ['record' => $info['id']]),
                                    'client' => $editActionRoute = \App\Filament\NsConseil\Resources\ClientResource::getUrl('edit', ['record' => $info['id']]),
                                    default => null,
                                };
                            }
                        @endphp
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                            @if (!empty($info['id']) && $info['type'] === 'prospect')
                            <a href="{{ route('filament.ns-conseil.resources.prospects.view', $info['id']) }}"
                                target="_blank"
                                style="font-size:0.75rem; color:rgb(37 99 235); text-decoration:underline;">
                                Ouvrir le dossier complet →
                            </a>
                            @endif
                            @if ($editActionRoute)
                            <a href="{{ $editActionRoute }}"
                                target="_blank"
                                style="font-size:0.75rem; color:rgb(14 116 144); text-decoration:underline;">
                                Modifier la fiche →
                            </a>
                            @endif
                        </div>
                    </div>

                    <div class="pw-info-tabs">
                        <button class="pw-info-tab active" data-tab="contact" onclick="switchInfoTab('contact')">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            Contact
                        </button>
                        @if (!empty($info['interlocuteur_nom']) || !empty($info['interlocuteur']))
                        <button class="pw-info-tab" data-tab="interlocuteur" onclick="switchInfoTab('interlocuteur')">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Interlocuteur
                        </button>
                        @endif
                        @if (($info['type'] ?? '') === 'prospect')
                        <button class="pw-info-tab" data-tab="standard-cse" onclick="switchInfoTab('standard-cse')">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                            Standard / CSE
                        </button>
                        @endif
                        @if (($info['type'] ?? '') === 'prospect' && !empty($info['notes']))
                        <button class="pw-info-tab" data-tab="notes" onclick="switchInfoTab('notes')">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            Notes
                        </button>
                        @endif
                        <button class="pw-info-tab" data-tab="journal" onclick="switchInfoTab('journal')">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                            Journal
                            @if (count($callHistory) > 0)
                            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:1.25rem;height:1.25rem;padding:0 0.25rem;border-radius:9999px;background:rgb(99 102 241);color:white;font-size:0.65rem;font-weight:700;">{{ count($callHistory) }}</span>
                            @endif
                        </button>
                        @if (($info['type'] ?? '') !== 'client')
                        <button class="pw-info-tab" data-tab="rdv" onclick="switchInfoTab('rdv')">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                            RDV
                        </button>
                        @endif
                    </div>

                    <div class="pw-info-panel" data-tab="contact">
                        <div class="pw-info-grid">
                            <div class="pw-field-full">
                                <div class="pw-field-label">Téléphone principal</div>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <span style="padding:0.375rem 0.5rem; background:rgb(249 250 251); border:1px solid rgb(209 213 219); border-radius:0.5rem; font-size:0.75rem; color:rgb(107 114 128);">🇫🇷 +33</span>
                                    <input type="text" value="{{ $info['telephone'] ?? '' }}" readonly
                                        class="pw-field-input"
                                        style="flex:1; font-weight:600; font-size:0.9375rem; letter-spacing:0.025em;"
                                        onclick="this.select(); document.execCommand('copy');"
                                        title="Cliquer pour copier">
                                    @if (!empty($info['telephone']))
                                    <a href="tel:{{ $info['telephone'] }}"
                                        style="padding:0.375rem 0.625rem; background:rgb(34 197 94); color:white; border-radius:0.5rem; font-size:0.75rem; font-weight:600; text-decoration:none; white-space:nowrap;">
                                        Appeler
                                    </a>
                                    @endif
                                </div>
                            </div>

                            @if (!empty($info['telephone_alt']))
                            <div class="pw-field-full">
                                <div class="pw-field-label">Téléphone secondaire</div>
                                <input type="text" value="{{ $info['telephone_alt'] }}" readonly
                                    class="pw-field-input"
                                    onclick="this.select(); document.execCommand('copy');"
                                    title="Cliquer pour copier">
                            </div>
                            @endif

                            <div>
                                <div class="pw-field-label">Email</div>
                                @if (!empty($info['email']))
                                <a href="mailto:{{ $info['email'] }}"
                                    style="font-size:0.8125rem; color:rgb(37 99 235);">{{ $info['email'] }}</a>
                                @else
                                <span style="font-size:0.8125rem; color:rgb(156 163 175);">—</span>
                                @endif
                            </div>

                            <div>
                                <div class="pw-field-label">Localisation</div>
                                <div class="pw-field-value">
                                    {{ collect([$info['ville'] ?? null, $info['code_postal'] ?? null, $info['departement'] ?? null])->filter()->implode(' · ') ?: '—' }}
                                </div>
                            </div>

                            @if (!empty($info['adresse_complete']))
                            <div class="pw-field-full">
                                <div class="pw-field-label">Adresse complète</div>
                                <div class="pw-field-value" style="font-size:0.8125rem;">
                                    {{ $info['adresse_complete'] }}
                                </div>
                            </div>
                            @endif
                        </div>

                        @if (($info['type'] ?? '') !== 'client')
                        <div class="pw-section-divider">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            Suivi commercial
                        </div>
                        <div class="pw-info-grid">
                            <div>
                                <div class="pw-field-label">Statut actuel</div>
                                <span class="pw-badge {{ $statutCls }}" style="font-size:0.8125rem; padding:0.25rem 0.625rem;">
                                    {{ $statutLabel }}
                                </span>
                            </div>
                            <div>
                                <div class="pw-field-label">Téléprospecteur</div>
                                <div class="pw-field-value">{{ $info['teleprospecteur'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="pw-field-label">Commercial</div>
                                <div class="pw-field-value">{{ $info['commercial'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="pw-field-label">1er contact</div>
                                <div class="pw-field-value">
                                    {{ $info['date_premier_contact'] ?? 'Jamais contacté' }}
                                </div>
                            </div>
                            @if (!empty($info['rappel_planifie_at']))
                            <div class="pw-field-full">
                                <div class="pw-field-label">Rappel planifié</div>
                                <div class="pw-field-value {{ $info['rappel_en_retard'] ? 'pw-rappel-late' : '' }}" style="display:flex; align-items:center; gap:0.375rem;">
                                    <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $info['rappel_planifie_at'] }}
                                    @if ($info['rappel_en_retard'])
                                    — EN RETARD
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if (!empty($info['statut_description']))
                            <div class="pw-field-full">
                                <div class="pw-field-label">Description statut</div>
                                <div class="pw-field-value" style="font-size:0.8125rem; color:rgb(107 114 128);">
                                    {{ $info['statut_description'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>

                    @if (!empty($info['interlocuteur_nom']) || !empty($info['interlocuteur']))
                    <div class="pw-info-panel" data-tab="interlocuteur" style="display:none;">
                        <div>
                            <div class="pw-field-label" style="margin-bottom:0.5rem;">Nouveau contact identifié à l'appel</div>
                            <div class="pw-info-grid">
                                <div>
                                    <div class="pw-field-label">Prénom</div>
                                    <input type="text" class="pw-field-input" placeholder="Prénom">
                                </div>
                                <div>
                                    <div class="pw-field-label">Nom</div>
                                    <input type="text" class="pw-field-input" placeholder="Nom">
                                </div>
                                <div>
                                    <div class="pw-field-label">Fonction</div>
                                    <input type="text" class="pw-field-input" placeholder="Fonction">
                                </div>
                                <div>
                                    <div class="pw-field-label">Téléphone</div>
                                    <input type="text" class="pw-field-input" placeholder="Téléphone">
                                </div>
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Email</div>
                                    <input type="email" class="pw-field-input" placeholder="email@domaine.fr">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if (($info['type'] ?? '') === 'prospect')
                    <div class="pw-info-panel" data-tab="standard-cse" style="display:none;">
                        <div style="margin-bottom:1rem;">
                            <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:rgb(100 116 139); margin-bottom:0.625rem; padding-bottom:0.375rem; border-bottom:1px solid rgb(241 245 249); display:flex; align-items:center; gap:0.375rem;">
                                <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                Interlocuteur Standard
                            </div>
                            <div class="pw-info-grid">
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Nom interlocuteur standard</div>
                                    <input type="text" wire:model="nom_interlocuteur_standard"
                                        class="pw-field-input" placeholder="Nom obtenu au standard">
                                </div>
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Créneaux de permanence CSE</div>
                                    <input type="text" wire:model="creneaux_permanence_cse"
                                        class="pw-field-input" placeholder="ex : Lundi 14h-16h">
                                </div>
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Email général (si obtenu au standard)</div>
                                    <input type="email" wire:model="email_general_standard"
                                        class="pw-field-input" placeholder="contact@entreprise.fr">
                                </div>
                            </div>
                        </div>

                        <div>
                            <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:rgb(100 116 139); margin-bottom:0.625rem; padding-bottom:0.375rem; border-bottom:1px solid rgb(241 245 249); display:flex; align-items:center; gap:0.375rem;">
                                <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                Interlocuteur CSE
                                <span style="font-size:0.6rem; color:rgb(239 68 68); font-weight:700;">* Obligatoire QF</span>
                            </div>
                            <div class="pw-info-grid">
                                <div class="pw-field-full">
                                    <div class="pw-field-label">
                                        Prénom / Nom du CSE
                                        <span style="color:rgb(239 68 68);">*</span>
                                    </div>
                                    <input type="text" wire:model="interlocuteur_nom"
                                        class="pw-field-input" placeholder="Prénom Nom du responsable CSE">
                                </div>
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Fonction du CSE</div>
                                    <select wire:model="interlocuteur_fonction" class="pw-field-input">
                                        <option value="">— Sélectionner —</option>
                                        <option value="Secrétaire">Secrétaire</option>
                                        <option value="Trésorier">Trésorier</option>
                                        <option value="Président">Président</option>
                                        <option value="Élu">Élu</option>
                                    </select>
                                </div>
                                <div>
                                    <div class="pw-field-label">Téléphone direct CSE</div>
                                    <input type="tel" wire:model="interlocuteur_telephone"
                                        class="pw-field-input" placeholder="06 XX XX XX XX">
                                </div>
                                <div>
                                    <div class="pw-field-label">
                                        Email CSE
                                        <span style="font-size:0.6rem; color:rgb(100 116 139);">Déclenche Mail 1</span>
                                    </div>
                                    <input type="email" wire:model="interlocuteur_email"
                                        class="pw-field-input" placeholder="cse@entreprise.fr">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if (!empty($noteLines))
                    <div class="pw-info-panel" data-tab="notes" style="display:none;">
                        <div class="pw-notes-list">
                            @foreach (array_reverse($noteLines) as $note)
                            <div class="pw-note-item">
                                @if ($note['date'])
                                <div class="pw-note-date">{{ $note['date'] }}</div>
                                @endif
                                <div style="font-size:0.8125rem;">{{ $note['text'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="pw-info-panel" data-tab="journal" style="display:none;">
                        @if (count($callHistory) === 0)
                        <div style="text-align:center; padding:2rem 1rem; color:rgb(156 163 175);">
                            <svg style="width:2.5rem;height:2.5rem;margin:0 auto 0.5rem;color:rgb(203 213 225);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                            <div style="font-size:0.875rem;">Aucun appel enregistré pour ce contact.</div>
                        </div>
                        @else
                        <div style="display:flex; flex-direction:column; gap:0.625rem;">
                            @foreach ($callHistory as $appel)
                            @php
                            $jColor = match ($appel['statut'] ?? '') {
                            'std_nr', 'cse_nr' => [
                            'bg' => 'rgb(243 244 246)',
                            'border' => 'rgb(209 213 219)',
                            'badge' => 'rgb(107 114 128)',
                            ],
                            'std_joint' => [
                            'bg' => 'rgb(239 246 255)',
                            'border' => 'rgb(147 197 253)',
                            'badge' => 'rgb(59 130 246)',
                            ],
                            'rp' => [
                            'bg' => 'rgb(240 253 244)',
                            'border' => 'rgb(134 239 172)',
                            'badge' => 'rgb(22 163 74)',
                            ],
                            'rpc' => [
                            'bg' => 'rgb(240 253 250)',
                            'border' => 'rgb(94 234 212)',
                            'badge' => 'rgb(13 148 136)',
                            ],
                            'ko' => [
                            'bg' => 'rgb(255 241 242)',
                            'border' => 'rgb(252 165 165)',
                            'badge' => 'rgb(220 38 38)',
                            ],
                            default => [
                            'bg' => 'rgb(248 250 252)',
                            'border' => 'rgb(226 232 240)',
                            'badge' => 'rgb(100 116 139)',
                            ],
                            };
                            @endphp
                            <div style="border-radius:0.625rem; border:1px solid {{ $jColor['border'] }}; background:{{ $jColor['bg'] }}; padding:0.625rem 0.75rem;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.375rem;">
                                    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                        <span style="font-size:0.7rem; font-weight:700; padding:0.125rem 0.5rem; border-radius:9999px; background:{{ $jColor['badge'] }}; color:white; text-transform:uppercase; letter-spacing:0.05em;">
                                            {{ $appel['statut_label'] }}
                                        </span>
                                        @if ($appel['campagne'] ?? null)
                                        <span style="font-size:0.7rem; padding:0.125rem 0.375rem; border-radius:9999px; background:rgb(238 242 255); color:rgb(79 70 229); border:1px solid rgb(199 210 254);">
                                            {{ $appel['campagne'] }}
                                        </span>
                                        @endif
                                    </div>
                                    <span style="font-size:0.7rem; color:rgb(107 114 128); white-space:nowrap; margin-left:0.5rem;">{{ $appel['date'] }}</span>
                                </div>
                                <div style="font-size:0.75rem; color:rgb(55 65 81); margin-bottom:{{ $appel['notes'] ? '0.25rem' : '0' }};">
                                    <span style="font-weight:600;">{{ $appel['agent'] }}</span>
                                </div>
                                @if ($appel['notes'])
                                <div style="font-size:0.75rem; color:rgb(75 85 99); background:rgba(255,255,255,0.6); border-radius:0.375rem; padding:0.25rem 0.5rem; margin-top:0.25rem; border-left:2px solid {{ $jColor['badge'] }};">
                                    {{ $appel['notes'] }}
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <div class="pw-info-panel" data-tab="rdv" style="display:none;">
                        <div class="pw-info-grid">
                            <div>
                                <div class="pw-field-label">Date RDV</div>
                                <input type="date" wire:model="rappel_date" class="pw-field-input">
                            </div>
                            <div>
                                <div class="pw-field-label">Heure RDV</div>
                                <input type="time" wire:model="rappel_heure" class="pw-field-input">
                            </div>
                            <div class="pw-field-full">
                                <div class="pw-field-label">Lieu / Adresse du RDV</div>
                                <input type="text" class="pw-field-input" placeholder="Adresse ou visioconférence">
                            </div>
                            <div class="pw-field-full">
                                <div class="pw-field-label">Note RDV</div>
                                <textarea class="pw-field-input" rows="2" placeholder="Précisions sur le rendez-vous..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── RÉSULTAT DE L'APPEL (en second, sous forme d'onglets par cas) ── --}}
                <div class="pw-result-panel pw-card">
                    <div class="pw-result-header">
                        <span class="pw-result-header-icon">
                            <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>
                        </span>
                        <span>Résultat de l'appel</span>
                    </div>

                    <div class="pw-case-tabs">
                        @foreach ($statutsGroupes as $groupeKey => $groupe)
                        <button type="button"
                            class="pw-case-tab {{ $groupeKey === $activeGroupeKey ? 'active' : '' }}"
                            data-case-tab="{{ $groupeKey }}"
                            onclick="switchCaseTab('{{ $groupeKey }}')">
                            {{ $groupe['label'] }}
                        </button>
                        @endforeach
                    </div>

                    @foreach ($statutsGroupes as $groupeKey => $groupe)
                    <div class="pw-case-panel {{ $groupeKey === $activeGroupeKey ? 'active' : '' }}" data-case-panel="{{ $groupeKey }}">
                        <div class="pw-chip-group">
                            <div class="pw-chip-row">
                                @foreach ($groupe['statuts'] as $option)
                                @php
                                $isActive = $statut_resultat === $option['value'];
                                $optColor = trim(\Illuminate\Support\Str::after($option['bar'], 'background:'));
                                $optionStyle = 'border-left-color: '.$optColor.';';
                                if ($isActive) {
                                $optionStyle .= ' '.$option['bar'].'; color:white;';
                                }
                                @endphp
                                <label wire:click="$set('statut_resultat', '{{ $option['value'] }}')"
                                    onclick="toggleRappel('{{ $option['value'] }}')"
                                    title="{{ $option['label'] }} — {{ $option['sub'] }}{{ $option['action'] ? ' → '.$option['action'] : '' }}"
                                    class="pw-chip"
                                    style="{{ $optionStyle }}">
                                    <span class="pw-chip-icon">{{ $option['icon'] ?? '•' }}</span>
                                    <span class="pw-chip-main">
                                        <span class="pw-chip-label-row {{ $isActive ? 'pw-chip-label-row--active' : '' }}">
                                            {{ $option['label'] }}
                                            @if (!empty($option['prioritaire']))
                                            <span class="pw-chip-star" title="Prioritaire">★</span>
                                            @endif
                                        </span>
                                        <span class="pw-chip-sub" style="{{ $isActive ? 'color:rgba(255,255,255,.85);' : '' }}">
                                            {{ $option['sub'] }}{{ $option['action'] ? ' → '.$option['action'] : '' }}
                                        </span>
                                    </span>
                                    <input type="radio" wire:model="statut_resultat" value="{{ $option['value'] }}" style="display:none;">
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div id="pw-rappel-box"
                        class="pw-rappel-box {{ in_array($statut_resultat, $rappelCodes) ? 'visible' : '' }}">
                        <div class="pw-rappel-box-title">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                            @if (in_array($statut_resultat, ['rapl_elu', 'rapl_std']))
                            Créneau de rappel
                            @else
                            Planifier le rappel / RDV
                            @endif
                        </div>
                        @if ($statut_resultat === 'rapl_elu')
                        <div style="font-size:0.7rem; background:#fffbe6; border:1px dashed #d4a800; border-radius:0.5rem; padding:4px 8px; color:#7a5c00; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.375rem;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            Note obligatoire dans le compte rendu : date + heure + nom de l'élu
                        </div>
                        @elseif ($statut_resultat === 'rapl_std')
                        <div style="font-size:0.7rem; background:#fffbe6; border:1px dashed #d4a800; border-radius:0.5rem; padding:4px 8px; color:#7a5c00; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.375rem;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            Note obligatoire dans le compte rendu : date + heure + nom du standard
                        </div>
                        @endif
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                            <div>
                                <div class="pw-field-label">Date</div>
                                <input type="date" wire:model="rappel_date" class="pw-field-input">
                            </div>
                            <div>
                                <div class="pw-field-label">Heure</div>
                                <input type="time" wire:model="rappel_heure" class="pw-field-input">
                            </div>
                        </div>
                    </div>

                    @if ($statut_resultat === 'rdv')
                    <div class="pw-fiche-recap" style="background:rgb(239 246 255); border-color:rgb(59 130 246);">
                        <div class="pw-fiche-recap-header" style="background:rgb(59 130 246); color:white;">
                            <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            FICHE RECAP RDV PRIS
                        </div>
                        <div style="padding:0.875rem; display:flex; flex-direction:column; gap:0.625rem;">
                            <div>
                                <div class="pw-field-label">Lieu du RDV</div>
                                <input type="text" wire:model="lieu_rdv" class="pw-field-input" placeholder="Adresse / Agence AOPIA / Visioconférence">
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                                <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem; cursor:pointer; padding:0.375rem; background:white; border-radius:0.5rem; border:1px solid rgb(209 213 219);">
                                    <input type="checkbox" wire:model="invitation_agenda_envoyee" style="width:1rem;height:1rem;">
                                    Invitation agenda envoyée
                                </label>
                                <div>
                                    <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem; cursor:pointer; padding:0.375rem; background:white; border-radius:0.5rem; border:1px solid rgb(209 213 219);">
                                        <input type="checkbox" wire:model="enregistrement_appel_joint" style="width:1rem;height:1rem;">
                                        Enregistrement joint
                                    </label>
                                    @if (!$enregistrement_appel_joint)
                                    <input type="text" wire:model="enregistrement_raison" class="pw-field-input" style="margin-top:0.25rem; font-size:0.75rem;" placeholder="Raison...">
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="pw-field-label">Besoins exprimés par le CSE</div>
                                <textarea wire:model="besoins_exprimes" rows="2" class="pw-field-input" style="resize:vertical; margin-top:0;" placeholder="Résumé des besoins / attentes identifiées..."></textarea>
                            </div>
                            <div>
                                <div class="pw-field-label">Objections soulevées</div>
                                <textarea wire:model="objections_soulevees" rows="2" class="pw-field-input" style="resize:vertical; margin-top:0;" placeholder="Objections rencontrées et façon dont elles ont été traitées..."></textarea>
                            </div>
                            <div>
                                <div class="pw-field-label">Points d'attention pour le RDV</div>
                                <textarea wire:model="points_attention_rdv" rows="2" class="pw-field-input" style="resize:vertical; margin-top:0;" placeholder="Éléments particuliers à transmettre au Responsable de Secteur..."></textarea>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($statut_resultat === 'cse_ni')
                    <div class="pw-fiche-recap" style="background:rgb(255 251 235); border-color:rgb(234 179 8);">
                        <div class="pw-fiche-recap-header" style="background:rgb(234 179 8); color:rgb(66 32 6);">
                            <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            FICHE RECAP RDV À PRENDRE — Rappel J+7
                        </div>
                        <div style="padding:0.875rem; font-size:0.8125rem; color:rgb(92 52 8);">
                            <p style="margin:0 0 0.5rem; font-weight:600;">Un email sera envoyé par l'assistante commerciale.</p>
                            <ul style="margin:0; padding-left:1.25rem; font-size:0.75rem; color:rgb(120 53 15); line-height:1.7;">
                                <li>Coordonnées CSE → onglet <strong>Standard / CSE</strong> ci-contre</li>
                                <li>Commentaires → champ compte rendu ci-dessous</li>
                                <li>Date rappel J+7 → bloc rappel ci-dessus (auto : {{ now()->addDays(7)->format('d/m/Y') }})</li>
                            </ul>
                        </div>
                    </div>
                    @endif

                    @if (in_array($statut_resultat, ['bloc2', 'ncse_50', 'ncse_plus50', 'cse_zone']))
                    <div class="pw-fiche-recap" style="background:rgb(240 253 244); border-color:rgb(34 197 94);">
                        <div class="pw-fiche-recap-header" style="background:rgb(34 197 94); color:white;">
                            <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                            FICHE RECAP RDV À CONCLURE — Commercial
                        </div>
                        <div style="padding:0.875rem; display:flex; flex-direction:column; gap:0.625rem;">
                            <div>
                                <div class="pw-field-label">Présence d'un CSE</div>
                                <select wire:model="presence_cse" class="pw-field-input">
                                    <option value="">— Sélectionner —</option>
                                    <option value="oui">Oui</option>
                                    <option value="non">Non</option>
                                    <option value="a_confirmer">À confirmer</option>
                                </select>
                            </div>
                            <div>
                                <div class="pw-field-label">Jour disponible pour l'appel</div>
                                <input type="text" wire:model="jour_dispo_appel" class="pw-field-input" placeholder="ex : Lundi matin, Mercredi 14h-16h">
                            </div>
                            <div style="font-size:0.75rem; color:rgb(22 101 52); background:rgb(220 252 231); border-radius:0.5rem; padding:0.5rem 0.75rem; line-height:1.6;">
                                Coordonnées CSE → onglet <strong>Standard / CSE</strong> · Commentaires → champ ci-dessous
                            </div>
                        </div>
                    </div>
                    @endif

                    <textarea wire:model="commentaires" rows="4"
                        placeholder="Compte rendu : interlocuteur joint, objections, décision, prochaine étape..." class="pw-textarea"></textarea>

                    @if ($statut_resultat && in_array($statut_resultat, ['rapl_elu', 'rapl_std']) && !$commentaires)
                    <div style="font-size:0.75rem; color:rgb(220 38 38); margin-top:0.5rem; display:flex; align-items:center; gap:0.375rem;">
                        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        Note obligatoire : date + heure + nom {{ $statut_resultat === 'rapl_elu' ? 'de l\'élu' : 'du standard' }}.
                    </div>
                    @elseif ($statut_resultat && !$commentaires && !in_array($statut_resultat, ['nrp', 'fax', 'maj']))
                    <div style="font-size:0.75rem; color:rgb(249 115 22); margin-top:0.5rem; display:flex; align-items:center; gap:0.375rem;">
                        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        Ajoutez un commentaire avant d'enregistrer.
                    </div>
                    @endif

                    <div class="pw-actions">
                        <button wire:click="submitResult" class="pw-btn-primary"
                            {{ !$statut_resultat ? 'disabled style=opacity:.5;cursor:not-allowed' : '' }}>
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Enregistrer &amp; suivant
                        </button>
                        <button wire:click.prevent="skipCall" class="pw-btn-secondary" title="Repousser en fin de file">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            Passer
                        </button>
                    </div>
                </div>
            </div>

            <div class="pw-right">
                <div class="pw-nr-box">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                        <span class="pw-nr-title">Sans réponse</span>
                        <span class="pw-nr-subtitle">08:00 – 18:00</span>
                    </div>
                    <div style="display:flex; align-items:baseline; gap:0.375rem; margin-bottom:0.5rem;">
                        <span class="pw-nr-count">{{ $tentativesActuelles }}</span>
                        <span class="pw-nr-tentatives">/ {{ $maxTentatives }} tentatives</span>
                    </div>
                    <div style="display:flex; gap:0.25rem;">
                        @for ($i = 0; $i < $maxTentatives; $i++)
                            <div style="flex:1; height:0.25rem; border-radius:9999px; background:{{ $i < $tentativesActuelles ? 'rgb(249 115 22)' : 'rgb(229 231 235)' }};">
                    </div>
                    @endfor
                </div>
            </div>
            <div wire:ignore id="ringover-embed-phoning"
                style="width:100%; max-width:100%; height:560px; border-radius:0.75rem; overflow:hidden; box-sizing:border-box; border:1px solid rgb(229 231 235); margin-bottom:1rem;">
            </div>
        </div>
    </div>
    @else
    <div style="display:flex; align-items:center; justify-content:center; min-height:60vh;">
        <div style="text-align:center;">
            <div style="width:5rem; height:5rem; border-radius:9999px; background:rgb(243 244 246); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem auto;">
                <svg style="width:2.25rem;height:2.25rem;color:rgb(156 163 175);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
            </div>
            <h3 style="font-size:1.125rem; font-weight:700; margin:0 0 0.5rem;">File vide</h3>
            <p style="color:rgb(107 114 128); margin:0 0 1.5rem;">
                Tous les contacts ont été traités ou aucun prospect n'est assigné à ce téléprospecteur.
            </p>
            <div style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
                <button wire:click="refreshQueue"
                    style="padding:0.5rem 1.5rem; background:rgb(37 99 235); color:white; border-radius:0.625rem; font-weight:600; border:none; cursor:pointer;">
                    Rafraîchir
                </button>
                @if ($isSupervisorMode)
                <a href="{{ route('filament.ns-conseil.pages.phoning-back-office') }}"
                    style="display:inline-flex; align-items:center; gap:0.4375rem; padding:0.5rem 1.5rem; background:rgb(249 250 251); color:rgb(55 65 81); border-radius:0.625rem; font-weight:600; border:1px solid rgb(229 231 235); text-decoration:none;">
                    <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Gérer la file
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif
    </div>

    @push('scripts')
    <script>
        (function() {
            const RINGOVER_SDK_URL = 'https://webcdn.ringover.com/resources/SDK/1.1.3/ringover-sdk.js';
            const RINGOVER_LOAD_TIMEOUT_MS = 8000;

            function loadRingoverSdk(onReady, onError) {
                if (window.RingoverSDK) {
                    onReady();
                    return;
                }

                let script = document.getElementById('ringover-sdk-script');
                if (script && script.dataset.failed === '1') {
                    // tentative précédente échouée : on repart d'un script propre
                    script.remove();
                    script = null;
                }

                if (script) {
                    script.addEventListener('load', onReady);
                    script.addEventListener('error', onError);
                    return;
                }

                script = document.createElement('script');
                script.id = 'ringover-sdk-script';

                // Certains bloqueurs/proxys avalent la requête sans jamais déclencher
                // 'error' (pas de réponse réseau) : ce filet de sécurité évite un widget
                // qui reste silencieusement vide indéfiniment.
                const timeout = setTimeout(() => {
                    script.dataset.failed = '1';
                    onError();
                }, RINGOVER_LOAD_TIMEOUT_MS);

                script.onload = () => {
                    clearTimeout(timeout);
                    onReady();
                };
                script.onerror = () => {
                    clearTimeout(timeout);
                    script.dataset.failed = '1';
                    onError();
                };
                script.src = RINGOVER_SDK_URL;
                document.head.appendChild(script);
            }

            function toE164Fr(raw) {
                let cleaned = (raw || '').replace(/[^0-9+]/g, '');
                if (cleaned.startsWith('+')) return cleaned;
                if (cleaned.startsWith('0')) return '+33' + cleaned.slice(1);
                return '+33' + cleaned;
            }

            function destroyRingoverWidget() {
                if (window.ringoverPhone) {
                    try {
                        window.ringoverPhone.destroy();
                    } catch (e) {}
                    window.ringoverPhone = null;
                }
            }

            function showRingoverError(container) {
                container.innerHTML = `
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; padding:1.5rem; text-align:center; background:rgb(249 250 251);">
                        <div style="width:3rem; height:3rem; border-radius:9999px; background:rgb(254 226 226); display:flex; align-items:center; justify-content:center; margin-bottom:0.75rem;">
                            <svg style="width:1.5rem;height:1.5rem;color:rgb(220 38 38);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <p style="font-weight:600; color:rgb(31 41 55); margin:0 0 0.375rem; font-size:0.875rem;">Ringover n'a pas pu se charger</p>
                        <p style="font-size:0.75rem; color:rgb(107 114 128); margin:0 0 1rem; max-width:22rem; line-height:1.4;">
                            Vérifiez votre connexion, désactivez un éventuel bloqueur de pub pour ce site, et assurez-vous d'être connecté(e) à Ringover — puis réessayez.
                            En navigation privée, la session Ringover ne peut pas toujours être conservée.
                        </p>
                        <button type="button" id="ringover-retry-btn" style="display:inline-flex; align-items:center; gap:0.4375rem; padding:0.5rem 1.25rem; background:rgb(37 99 235); color:white; border:none; border-radius:0.5rem; font-weight:600; font-size:0.8125rem; cursor:pointer;">
                            <svg style="width:0.9375rem;height:0.9375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            Réessayer
                        </button>
                    </div>
                `;
                const retryBtn = container.querySelector('#ringover-retry-btn');
                if (retryBtn) {
                    retryBtn.addEventListener('click', () => {
                        container.innerHTML = '';
                        initRingoverWidget(true);
                    });
                }
            }

            function initRingoverWidget(forceRetry) {
                const container = document.getElementById('ringover-embed-phoning');
                // sécurité : on ne fait rien si on n'est pas sur cette page
                if (!container) return;

                if (!forceRetry && window.ringoverPhone && window.ringoverPhone.__mountedIn === container) {
                    // déjà monté ici, rien à refaire
                    return;
                }

                loadRingoverSdk(() => {
                    // au cas où une instance orpheline traînait
                    destroyRingoverWidget();
                    container.innerHTML = '';

                    window.ringoverPhone = new window.RingoverSDK({
                        type: 'relative',
                        size: 'auto',
                        container: 'ringover-embed-phoning',
                        border: false,
                        trayicon: false,
                        animation: false,
                    });
                    window.ringoverPhone.__mountedIn = container;
                    window.ringoverPhone.__ready = false;
                    window.ringoverPhone.generate();
                    window.ringoverPhone.on('dialerReady', () => {
                        window.ringoverPhone.__ready = true;
                    });
                }, () => {
                    showRingoverError(container);
                });
            }

            window.appelerAvecRingover = function(numero) {
                if (!numero) return;
                const e164 = toE164Fr(numero);
                const nowIso = new Date().toISOString();
                const lifecycle = {
                    callId: null,
                    startedAt: nowIso,
                    endedAt: null,
                };

                const captureLifecycle = (payload = null, type = 'generic') => {
                    const rawStarted = payload && (payload.started_at || payload.startedAt || payload.start_time || payload.startTime || payload.started || payload.begin_at || payload.beginAt);
                    const rawEnded = payload && (payload.ended_at || payload.endedAt || payload.end_time || payload.endTime || payload.ended || payload.end_at || payload.endAt);

                    if (rawStarted) {
                        lifecycle.startedAt = new Date(rawStarted).toISOString();
                    }

                    if (rawEnded) {
                        lifecycle.endedAt = new Date(rawEnded).toISOString();
                    }

                    const payloadCallId = payload && (payload.call_id || payload.callId || payload.uuid || payload.id || payload.call_uuid || payload.uuid_call);
                    if (payloadCallId) {
                        lifecycle.callId = String(payloadCallId);
                    }

                    Livewire.dispatch('ringover-call-lifecycle', {
                        callId: lifecycle.callId,
                        startedAt: lifecycle.startedAt,
                        endedAt: lifecycle.endedAt,
                        type,
                    });
                };

                const lancerAppel = () => {
                    if (!window.ringoverPhone) return;
                    window.ringoverPhone.show();
                    if (typeof window.ringoverPhone.getCallId === 'function') {
                        lifecycle.callId = window.ringoverPhone.getCallId();
                    }
                    if (typeof window.ringoverPhone.on === 'function') {
                        ['callStarted', 'call:started', 'started', 'call.started'].forEach((eventName) => {
                            try {
                                window.ringoverPhone.on(eventName, (payload) => {
                                    const direction = payload && (payload.direction || payload.type || payload.call_direction || payload.callDirection);
                                    const caller = payload && (payload.from_number || payload.fromNumber || payload.caller_number || payload.callerNumber || payload.from || payload.caller);
                                    const isInbound = direction === 'inbound' || String(direction).toLowerCase().includes('inbound') || String(direction).toLowerCase().includes('incoming');
                                    captureLifecycle(payload, eventName);
                                    if (isInbound && caller) {
                                        rechercherAppelEntrant(caller);
                                    }
                                });
                            } catch (e) {}
                        });

                        ['callEnded', 'call:ended', 'ended', 'call.ended', 'hangup'].forEach((eventName) => {
                            try {
                                window.ringoverPhone.on(eventName, (payload) => {
                                    if (!payload || !payload.endedAt && !payload.ended_at && !payload.endTime && !payload.end_time && !payload.ended) {
                                        lifecycle.endedAt = new Date().toISOString();
                                    }
                                    captureLifecycle(payload, eventName);
                                });
                            } catch (e) {}
                        });
                    }
                    window.ringoverPhone.dial(e164);
                };
                Livewire.dispatch('ringover-call-lifecycle', { callId: null, startedAt: lifecycle.startedAt, endedAt: null });
                if (window.ringoverPhone && window.ringoverPhone.__ready) {
                    lancerAppel();
                } else if (window.ringoverPhone) {
                    window.ringoverPhone.on('dialerReady', lancerAppel);
                }
            };

            // Montage à l'arrivée sur cette page (chargement direct ou navigation SPA)
            document.addEventListener('DOMContentLoaded', () => initRingoverWidget(false));
            document.addEventListener('livewire:navigated', () => initRingoverWidget(false));

            // Démontage dès qu'on quitte n'importe quelle page (no-op si rien n'est monté)
            document.addEventListener('livewire:navigating', destroyRingoverWidget);
        })();
    </script>
    @endpush




</x-filament-panels::page>
