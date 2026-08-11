@props([
    'emailPreviewSubject',
    'emailPreviewBody',
    'emailPreviewRecipient',
    'emailPreviewOriginalSubject' => null,
    'emailPreviewOriginalBody'    => null,
    'showCcSelection'             => false,
    'recipientReadonly'           => false,
    'emailPreviewCcUsers'         => [],
    'emailPreviewCcUserIds'       => [],
])

{{--
    Composant : email-preview
    Contient :
      - Modal d'aperçu email éditable avant envoi
      - Onglets Éditer / Aperçu live
      - Éditeur rich-text (contenteditable) avec barre d'outils
      - Panneau d'aperçu destinataire (rendu live)
      - Boutons Annuler / Confirmer et envoyer

    Props :
      - emailPreviewSubject         : string      — sujet du mail
      - emailPreviewBody            : string      — corps HTML du mail
      - emailPreviewRecipient       : string      — adresse email du destinataire
      - emailPreviewOriginalSubject : string|null — sujet original (pour réinitialisation)
      - emailPreviewOriginalBody    : string|null — corps original  (pour réinitialisation)

    Alpine.js :
      La fonction `phoningEmailPreview(initial)` est définie dans le parent
      phoning-workflow.blade.php et est disponible globalement à l'exécution.
--}}

<div
    class="pw-email-preview-overlay"
    wire:key="email-preview-modal"
    x-data="phoningEmailPreview(@js([
        'subject'         => $emailPreviewSubject,
        'recipient'       => $emailPreviewRecipient,
        'body'            => $emailPreviewBody,
        'originalSubject' => $emailPreviewOriginalSubject ?? $emailPreviewSubject,
        'originalBody'    => $emailPreviewOriginalBody    ?? $emailPreviewBody,
    ]))"
    @keydown.escape.window="$wire.cancelEmailPreview()"
>
    <div class="pw-email-preview-modal" @click.outside.stop>

        {{-- ── En-tête ──────────────────────────────────────────────────── --}}
        <div class="pw-email-preview-header">
            <div>
                <span>Aperçu du mail avant envoi</span>
                <div class="pw-email-preview-header-meta">
                    <span class="pw-email-preview-badge" x-show="isDirty" x-cloak>Modifié</span>
                    <span x-text="plainTextLength + ' caractères'"></span>
                </div>
            </div>
            <button type="button" class="pw-email-preview-close" wire:click="cancelEmailPreview" aria-label="Fermer">
                ×
            </button>
        </div>

        {{-- ── Corps ───────────────────────────────────────────────────── --}}
        <div class="pw-email-preview-content">

            {{-- Onglets --}}
            <div class="pw-email-preview-tabs" role="tablist" aria-label="Mode d'affichage">
                <button type="button" class="pw-email-preview-tab" :class="{ 'active': activeTab === 'edit' }"    @click="switchTab('edit')">Éditer</button>
                <button type="button" class="pw-email-preview-tab" :class="{ 'active': activeTab === 'preview' }" @click="switchTab('preview')">Aperçu live</button>
            </div>

            <div class="pw-email-preview-split">

                {{-- ── Panneau éditeur ──────────────────────────────────── --}}
                <div
                    class="pw-email-preview-editor-pane"
                    :class="{ 'is-hidden-mobile': activeTab !== 'edit' }"
                >
                    <div class="pw-email-preview-preview-header">
                        <div class="pw-email-preview-preview-title">Composer le message</div>
                        <div class="pw-email-preview-helper">Modifiez le destinataire, le sujet et le contenu. L'aperçu se met à jour en temps réel.</div>
                    </div>

                    {{-- Destinataire --}}
                    <div class="pw-email-preview-section">
                        <label for="email-preview-recipient" class="pw-email-preview-label">Destinataire</label>
                        <input
                            id="email-preview-recipient"
                            type="email"
                            x-model="recipient"
                            @input="markDirty()"
                            class="pw-email-preview-input pw-email-preview-input-recipient"
                            autocomplete="off"
                            @readonly($recipientReadonly)
                        />
                        @if ($recipientReadonly)
                            <div class="pw-email-preview-helper">Le destinataire principal est le commercial assigné au prospect.</div>
                        @endif
                    </div>

                    {{-- Sujet --}}
                    <div class="pw-email-preview-section">
                        <label for="email-preview-subject" class="pw-email-preview-label">Sujet</label>
                        <input
                            id="email-preview-subject"
                            type="text"
                            x-model="subject"
                            @input="markDirty()"
                            class="pw-email-preview-input"
                            autocomplete="off"
                        />
                    </div>

                    @if ($showCcSelection)
                        <div class="pw-email-preview-section">
                            <label for="email-preview-cc-users" class="pw-email-preview-label">Copie à</label>
                            <select
                                id="email-preview-cc-users"
                                wire:model="emailPreviewCcUserIds"
                                multiple
                                class="pw-email-preview-input"
                                style="min-height: 7rem;"
                            >
                                @foreach ($emailPreviewCcUsers as $user)
                                    <option value="{{ $user['id'] }}">{{ $user['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="pw-email-preview-helper">Sélectionnez les utilisateurs actifs à mettre en copie.</div>
                        </div>
                    @endif

                    {{-- Corps du message --}}
                    <div class="pw-email-preview-section" style="flex:1; display:flex; flex-direction:column; min-height:0;">
                        <div class="pw-email-preview-label">Corps du message</div>

                        {{-- Barre d'outils --}}
                        <div class="pw-email-preview-toolbar" role="toolbar" aria-label="Barre d'outils de mise en forme">
                            <button type="button" class="pw-email-preview-toolbar-button" title="Gras (Ctrl+B)"      @click.prevent="format('bold')"><strong>B</strong></button>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Italique (Ctrl+I)"  @click.prevent="format('italic')"><em>I</em></button>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Souligné (Ctrl+U)"  @click.prevent="format('underline')"><span style="text-decoration:underline;">U</span></button>
                            <span class="pw-email-preview-toolbar-divider"></span>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Liste à puces"       @click.prevent="format('insertUnorderedList')">• Liste</button>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Liste numérotée"     @click.prevent="format('insertOrderedList')">1. Liste</button>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Insérer un lien"     @click.prevent="format('createLink')">Lien</button>
                            <span class="pw-email-preview-toolbar-divider"></span>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Annuler"             @click.prevent="format('undo')">↶</button>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Rétablir"            @click.prevent="format('redo')">↷</button>
                            <button type="button" class="pw-email-preview-toolbar-button" title="Effacer la mise en forme" @click.prevent="format('removeFormat')">Tx</button>
                            <button type="button" class="pw-email-preview-toolbar-button is-reset" title="Réinitialiser le modèle" @click.prevent="resetTemplate()">Réinitialiser</button>
                        </div>

                        {{-- Zone de saisie rich-text --}}
                        <div
                            x-ref="editor"
                            class="pw-email-preview-editor"
                            contenteditable="true"
                            spellcheck="true"
                            @input="handleEditorInput()"
                            @paste="handleEditorPaste($event)"
                            @keydown="handleEditorKeydown($event)"
                        ></div>

                        <div class="pw-email-preview-helper">
                            <span>Raccourcis : Ctrl+B, Ctrl+I, Ctrl+U · collage texte ou HTML supporté</span>
                            <span class="pw-email-preview-stats" :class="{ 'is-dirty': isDirty }" x-text="isDirty ? 'Modifications non enregistrées' : 'Modèle d\'origine'"></span>
                        </div>
                    </div>
                </div>

                {{-- ── Panneau aperçu ───────────────────────────────────── --}}
                <div
                    class="pw-email-preview-preview-pane"
                    :class="{ 'is-hidden-mobile': activeTab !== 'preview' }"
                >
                    <div class="pw-email-preview-preview-header">
                        <div class="pw-email-preview-preview-title">Aperçu destinataire</div>
                        <div class="pw-email-preview-helper">Rendu approximatif du mail tel qu'il sera reçu.</div>
                    </div>

                    <div class="pw-email-preview-preview-frame">
                        {{-- Barre décorative navigateur --}}
                        <div class="pw-email-preview-preview-frame-bar" aria-hidden="true">
                            <span class="pw-email-preview-preview-dot"></span>
                            <span class="pw-email-preview-preview-dot"></span>
                            <span class="pw-email-preview-preview-dot"></span>
                        </div>

                        {{-- Métadonnées À / Objet --}}
                        <div class="pw-email-preview-preview-meta">
                            <div class="pw-email-preview-preview-meta-row">
                                <span class="pw-email-preview-preview-meta-label">À</span>
                                <span class="pw-email-preview-preview-meta-value" x-text="recipient || '—'"></span>
                            </div>
                            <div class="pw-email-preview-preview-meta-row">
                                <span class="pw-email-preview-preview-meta-label">Objet</span>
                                <span class="pw-email-preview-preview-meta-value" x-text="subject || '(sans sujet)'"></span>
                            </div>
                        </div>

                        {{-- Corps rendu --}}
                        <div class="pw-email-preview-preview-body" x-html="body || '<p style=&quot;color:#9ca3af&quot;>Le corps du message apparaîtra ici…</p>'"></div>
                    </div>
                </div>

            </div>{{-- /.pw-email-preview-split --}}
        </div>{{-- /.pw-email-preview-content --}}

        {{-- ── Actions ──────────────────────────────────────────────────── --}}
        <div class="pw-email-preview-actions">
            <button type="button" wire:click="cancelEmailPreview"  class="pw-btn-secondary">Annuler</button>
            <button type="button" @click="confirmSend()"           class="pw-btn-primary">Confirmer et envoyer</button>
        </div>

    </div>{{-- /.pw-email-preview-modal --}}
</div>{{-- /.pw-email-preview-overlay --}}
