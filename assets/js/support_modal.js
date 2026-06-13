(() => {
  'use strict';

  const body = document.body;
  const view = body?.dataset?.view || document.documentElement?.dataset?.view || '';
  const supportPageViews = new Set(['student/support', 'teacher/support', 'web/support']);

  if (!body || supportPageViews.has(view) || window.ENSupportModalInitialized) {
    return;
  }

  const triggers = Array.from(document.querySelectorAll('[data-support-fab]'));
  if (triggers.length === 0 || typeof window.fetch !== 'function') {
    return;
  }

  window.ENSupportModalInitialized = true;

  const labels = {
    categories: {
      student: [
        ['course', 'Accès au cours'],
        ['billing', 'Paiement'],
        ['certificate', 'Certificat'],
        ['technical', 'Problème technique'],
        ['other', 'Autre'],
      ],
      teacher: [
        ['technical', 'Problème technique'],
        ['lesson', 'Cours / contenu'],
        ['meeting', 'Rendez-vous'],
        ['withdrawal', 'Retrait / revenus'],
        ['other', 'Autre'],
      ],
    },
    priorities: [
      ['low', 'Faible'],
      ['normal', 'Normal'],
      ['high', 'Urgent'],
    ],
    defaultSubjects: {
      billing: 'Paiement non validé',
      certificate: 'Certificat indisponible',
      course: 'Problème d’accès au cours',
      lesson: 'Problème d’accès au cours',
      meeting: 'Rendez-vous à reprogrammer',
      withdrawal: 'Retrait en attente',
      technical: 'Problème technique',
      other: 'Demande d’assistance',
    },
  };

  const state = {
    activeId: null,
    basePath: '',
    conversations: [],
    currentTrigger: null,
    lastMessageId: 0,
    loadingConversations: false,
    loadingMessages: false,
    pollTimer: null,
    space: 'student',
  };

  const els = {
    modal: null,
    panel: null,
    title: null,
    subtitle: null,
    status: null,
    ticketList: null,
    emptyTickets: null,
    messages: null,
    form: null,
    subject: null,
    category: null,
    priority: null,
    body: null,
    attachments: null,
    fileHint: null,
    submit: null,
    newFields: null,
    newButton: null,
  };

  function escapeHtml(value) {
    return String(value || '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function compactText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
  }

  function normalizeStatus(value) {
    const status = String(value || '').trim().toLowerCase();
    if (status === 'closed') return 'closed';
    if (status === 'waiting_user') return 'waiting_user';
    if (status === 'waiting_support') return 'waiting_support';
    return 'open';
  }

  function statusLabel(value) {
    const status = normalizeStatus(value);
    if (status === 'closed') return 'Résolu';
    if (status === 'waiting_user') return 'Votre réponse est attendue';
    if (status === 'waiting_support') return 'En attente du support';
    return 'Ouvert';
  }

  function shortStatusLabel(value) {
    const status = normalizeStatus(value);
    if (status === 'closed') return 'Résolu';
    if (status === 'waiting_user') return 'Réponse requise';
    if (status === 'waiting_support') return 'En attente support';
    return 'Ouvert';
  }

  function parseDateValue(raw) {
    const value = String(raw || '').trim();
    if (!value) return null;
    const parsed = new Date(value.includes('T') ? value : value.replace(' ', 'T'));
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatDate(raw) {
    const parsed = parseDateValue(raw);
    if (!parsed) return '';
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    }).format(parsed);
  }

  function formatTicketNumber(id) {
    const numeric = Number.parseInt(String(id || '0'), 10) || 0;
    return numeric > 0 ? `#${numeric}` : '#—';
  }

  function inferSpace(trigger) {
    const href =
      trigger instanceof HTMLElement
        ? trigger.dataset.supportUrl || (trigger instanceof HTMLAnchorElement ? trigger.getAttribute('href') || '' : '')
        : '';
    try {
      const url = new URL(href, window.location.href);
      const querySpace = url.searchParams.get('space');
      if (querySpace === 'teacher' || querySpace === 'student') return querySpace;
    } catch (_error) {
      /* Keep fallback below. */
    }

    if (body.dataset.supportSpace === 'teacher' || body.classList.contains('app-role-teacher') || view.startsWith('teacher/')) {
      return 'teacher';
    }

    return 'student';
  }

  function inferBasePath(trigger) {
    const href =
      trigger instanceof HTMLElement
        ? trigger.dataset.supportUrl || (trigger instanceof HTMLAnchorElement ? trigger.getAttribute('href') || '' : '')
        : '';
    try {
      const url = new URL(href, window.location.href);
      const pathname = url.pathname.replace(/\/+$/, '');
      if (pathname.endsWith('/support')) {
        return pathname.slice(0, -'/support'.length) || '';
      }
    } catch (_error) {
      /* Keep fallback below. */
    }

    if (body.dataset.basePath) {
      return String(body.dataset.basePath || '').replace(/\/+$/, '');
    }

    return '';
  }

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta instanceof HTMLMetaElement && meta.content) {
      return meta.content;
    }

    const input = document.querySelector('input[name="_csrf"]');
    return input instanceof HTMLInputElement ? input.value : '';
  }

  function apiUrl(path) {
    const base = String(state.basePath || '').replace(/\/+$/, '');
    return `${base}${path}`;
  }

  function attachmentUrl(attachment, inline = false) {
    const id = encodeURIComponent(String(attachment?.id || ''));
    return `${apiUrl('/support_download')}?id=${id}${inline ? '&inline=1' : ''}`;
  }

  function isImageAttachment(attachment) {
    const mime = String(attachment?.mime || '').toLowerCase();
    const name = String(attachment?.name || '').toLowerCase();
    return mime.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp)$/i.test(name);
  }

  function getActiveConversation() {
    return state.conversations.find((conversation) => Number(conversation.id || 0) === Number(state.activeId || 0)) || null;
  }

  function preferredConversation() {
    return (
      state.conversations.find((conversation) => normalizeStatus(conversation.status) === 'waiting_user') ||
      state.conversations.find((conversation) => Number(conversation.unread_count || 0) > 0) ||
      state.conversations.find((conversation) => normalizeStatus(conversation.status) !== 'closed') ||
      state.conversations[0] ||
      null
    );
  }

  async function fetchJson(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...options,
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || payload.ok === false) {
      const uploadErrors = Array.isArray(payload.errors) ? payload.errors.join(' ') : '';
      const message =
        uploadErrors ||
        payload.message ||
        payload.error ||
        (response.status >= 500 ? 'Erreur serveur.' : 'Requête impossible.');
      throw new Error(String(message));
    }

    return payload;
  }

  function setStatus(message, tone = 'info') {
    if (!(els.status instanceof HTMLElement)) return;
    const text = String(message || '').trim();
    els.status.textContent = text;
    els.status.className = `en-support-modal__status ${text ? 'is-visible' : ''} en-support-modal__status--${tone}`;
  }

  function syncTriggerBadges() {
    const unread = state.conversations.reduce((total, conversation) => {
      return total + Math.max(0, Number.parseInt(String(conversation.unread_count || '0'), 10) || 0);
    }, 0);

    document.querySelectorAll('[data-live-support-badge]').forEach((badge) => {
      if (!(badge instanceof HTMLElement)) return;
      badge.textContent = unread > 99 ? '99+' : String(unread);
      badge.classList.toggle('hidden', unread <= 0);
      badge.hidden = unread <= 0;
    });
  }

  function updateFileHint() {
    if (!(els.attachments instanceof HTMLInputElement) || !(els.fileHint instanceof HTMLElement)) return;
    const files = els.attachments.files ? Array.from(els.attachments.files) : [];
    if (!files.length) {
      els.fileHint.textContent = '';
      els.fileHint.hidden = true;
      return;
    }

    const firstName = files[0]?.name || 'fichier';
    els.fileHint.hidden = false;
    els.fileHint.textContent =
      files.length === 1
        ? `1 fichier sélectionné : ${firstName}`
        : `${files.length} fichiers sélectionnés : ${firstName} +${files.length - 1}`;
  }

  function setSubmitLoading(isLoading) {
    if (!(els.submit instanceof HTMLButtonElement)) return;
    els.submit.disabled = isLoading;
    els.submit.classList.toggle('is-loading', isLoading);
  }

  function categoryOptionsHtml() {
    const options = labels.categories[state.space] || labels.categories.student;
    return options
      .map(([value, label], index) => `<option value="${escapeHtml(value)}"${index === 0 ? ' selected' : ''}>${escapeHtml(label)}</option>`)
      .join('');
  }

  function priorityOptionsHtml() {
    return labels.priorities
      .map(([value, label]) => `<option value="${escapeHtml(value)}"${value === 'normal' ? ' selected' : ''}>${escapeHtml(label)}</option>`)
      .join('');
  }

  function injectStyles() {
    if (document.getElementById('en-support-modal-style')) return;

    const style = document.createElement('style');
    style.id = 'en-support-modal-style';
    style.textContent = `
      .en-support-modal[hidden] { display: none !important; }
      .en-support-modal { position: fixed; inset: 0; z-index: 80; display: flex; align-items: flex-end; justify-content: flex-end; padding: 1rem; font-family: "Inter", system-ui, sans-serif; }
      .en-support-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.58); backdrop-filter: blur(7px); }
      .en-support-modal__panel { position: relative; z-index: 1; display: flex; max-height: min(760px, calc(100vh - 2rem)); width: min(460px, calc(100vw - 2rem)); flex-direction: column; overflow: hidden; border: 1px solid rgba(148, 163, 184, 0.34); border-radius: 24px; background: #ffffff; color: #0f172a; box-shadow: 0 32px 90px -36px rgba(15, 23, 42, 0.72); }
      .dark .en-support-modal__panel, html[data-theme="dark"] .en-support-modal__panel { border-color: rgba(71, 85, 105, 0.7); background: #111827; color: #f8fafc; }
      .en-support-modal__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem; border-bottom: 1px solid rgba(226, 232, 240, 0.92); background: linear-gradient(135deg, #2563eb, #06b6d4); color: #ffffff; }
      .dark .en-support-modal__header, html[data-theme="dark"] .en-support-modal__header { border-bottom-color: rgba(30, 41, 59, 0.8); }
      .en-support-modal__header-icon { display: inline-flex; width: 2.75rem; height: 2.75rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 999px; background: rgba(255, 255, 255, 0.16); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2); }
      .en-support-modal__title { margin: 0; font-size: 1rem; font-weight: 800; line-height: 1.25; }
      .en-support-modal__subtitle { margin: 0.2rem 0 0; color: rgba(255, 255, 255, 0.86); font-size: 0.78rem; line-height: 1.35; }
      .en-support-modal__close { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 0; border-radius: 999px; background: rgba(255, 255, 255, 0.14); color: #ffffff; cursor: pointer; transition: background 0.2s ease; }
      .en-support-modal__close:hover { background: rgba(255, 255, 255, 0.24); }
      .en-support-modal__tickets { display: flex; gap: 0.5rem; overflow-x: auto; padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc; scrollbar-width: thin; }
      .dark .en-support-modal__tickets, html[data-theme="dark"] .en-support-modal__tickets { border-bottom-color: #1e293b; background: #0f172a; }
      .en-support-modal__new, .en-support-ticket-chip { flex: 0 0 auto; border: 1px solid #dbeafe; border-radius: 14px; background: #ffffff; color: #1e3a8a; cursor: pointer; font-size: 0.76rem; font-weight: 800; padding: 0.55rem 0.75rem; transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease; }
      .en-support-modal__new:hover, .en-support-ticket-chip:hover { border-color: #93c5fd; transform: translateY(-1px); }
      .en-support-modal__new.is-active, .en-support-ticket-chip.is-active { border-color: #2563eb; background: #dbeafe; color: #1d4ed8; }
      .dark .en-support-modal__new, .dark .en-support-ticket-chip, html[data-theme="dark"] .en-support-modal__new, html[data-theme="dark"] .en-support-ticket-chip { border-color: rgba(59, 130, 246, 0.35); background: #1e293b; color: #bfdbfe; }
      .dark .en-support-modal__new.is-active, .dark .en-support-ticket-chip.is-active, html[data-theme="dark"] .en-support-modal__new.is-active, html[data-theme="dark"] .en-support-ticket-chip.is-active { border-color: #60a5fa; background: rgba(37, 99, 235, 0.24); color: #dbeafe; }
      .en-support-ticket-chip { max-width: 13rem; text-align: left; }
      .en-support-ticket-chip__top { display: flex; align-items: center; gap: 0.4rem; white-space: nowrap; }
      .en-support-ticket-chip__subject { display: block; margin-top: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #334155; font-weight: 700; }
      .dark .en-support-ticket-chip__subject, html[data-theme="dark"] .en-support-ticket-chip__subject { color: #e2e8f0; }
      .en-support-ticket-chip__badge { border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 0.64rem; padding: 0.18rem 0.38rem; }
      .en-support-modal__body { display: flex; min-height: 0; flex: 1; flex-direction: column; }
      .en-support-modal__status { display: none; margin: 0.75rem 1rem 0; border-radius: 12px; border: 1px solid transparent; padding: 0.7rem 0.85rem; font-size: 0.82rem; font-weight: 650; }
      .en-support-modal__status.is-visible { display: block; }
      .en-support-modal__status--error { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
      .en-support-modal__status--success { border-color: #bbf7d0; background: #f0fdf4; color: #047857; }
      .en-support-modal__status--info { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
      .dark .en-support-modal__status--error, html[data-theme="dark"] .en-support-modal__status--error { border-color: rgba(248, 113, 113, 0.4); background: rgba(127, 29, 29, 0.32); color: #fecaca; }
      .dark .en-support-modal__status--success, html[data-theme="dark"] .en-support-modal__status--success { border-color: rgba(52, 211, 153, 0.35); background: rgba(6, 78, 59, 0.34); color: #bbf7d0; }
      .dark .en-support-modal__status--info, html[data-theme="dark"] .en-support-modal__status--info { border-color: rgba(96, 165, 250, 0.36); background: rgba(30, 64, 175, 0.3); color: #dbeafe; }
      .en-support-modal__messages { min-height: 190px; max-height: 320px; flex: 1; overflow-y: auto; padding: 1rem; scroll-behavior: smooth; }
      .en-support-modal__placeholder { border: 1px dashed #bfdbfe; border-radius: 18px; background: #eff6ff; color: #1e40af; padding: 0.9rem; font-size: 0.84rem; line-height: 1.45; }
      .dark .en-support-modal__placeholder, html[data-theme="dark"] .en-support-modal__placeholder { border-color: rgba(96, 165, 250, 0.38); background: rgba(30, 64, 175, 0.22); color: #dbeafe; }
      .en-support-message { display: flex; margin-bottom: 0.7rem; }
      .en-support-message.is-self { justify-content: flex-end; }
      .en-support-message__stack { max-width: 84%; }
      .en-support-message__bubble { border-radius: 18px; background: #f1f5f9; color: #0f172a; padding: 0.72rem 0.85rem; font-size: 0.86rem; line-height: 1.45; white-space: pre-line; }
      .en-support-message.is-self .en-support-message__bubble { background: #2563eb; color: #ffffff; }
      .dark .en-support-message__bubble, html[data-theme="dark"] .en-support-message__bubble { background: #1e293b; color: #f8fafc; }
      .dark .en-support-message.is-self .en-support-message__bubble, html[data-theme="dark"] .en-support-message.is-self .en-support-message__bubble { background: #2563eb; color: #ffffff; }
      .en-support-message__meta { margin: 0.22rem 0 0; color: #64748b; font-size: 0.68rem; }
      .en-support-message.is-self .en-support-message__meta { text-align: right; }
      .dark .en-support-message__meta, html[data-theme="dark"] .en-support-message__meta { color: #94a3b8; }
      .en-support-message__attachments { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.55rem; }
      .en-support-message__file { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 999px; border: 1px solid rgba(148, 163, 184, 0.45); background: rgba(255, 255, 255, 0.78); color: #334155; padding: 0.35rem 0.55rem; font-size: 0.7rem; text-decoration: none; }
      .en-support-message__image { display: block; width: 92px; height: 68px; overflow: hidden; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.55); background: rgba(15, 23, 42, 0.06); }
      .en-support-message__image img { width: 100%; height: 100%; object-fit: cover; display: block; }
      .en-support-modal__form { border-top: 1px solid #e2e8f0; padding: 0.9rem 1rem 1rem; background: #ffffff; }
      .dark .en-support-modal__form, html[data-theme="dark"] .en-support-modal__form { border-top-color: #1e293b; background: #111827; }
      .en-support-modal__new-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; margin-bottom: 0.7rem; }
      .en-support-modal__new-fields.is-hidden { display: none; }
      .en-support-modal__field { display: flex; flex-direction: column; gap: 0.25rem; }
      .en-support-modal__field--full { grid-column: 1 / -1; }
      .en-support-modal__label { color: #475569; font-size: 0.72rem; font-weight: 800; }
      .dark .en-support-modal__label, html[data-theme="dark"] .en-support-modal__label { color: #cbd5e1; }
      .en-support-modal__input, .en-support-modal__select, .en-support-modal__textarea { width: 100%; border: 1px solid #dbe3ef; border-radius: 13px; background: #f8fafc; color: #0f172a; font: inherit; font-size: 0.86rem; outline: none; padding: 0.72rem 0.8rem; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
      .en-support-modal__textarea { min-height: 82px; max-height: 150px; resize: vertical; }
      .en-support-modal__input:focus, .en-support-modal__select:focus, .en-support-modal__textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
      .dark .en-support-modal__input, .dark .en-support-modal__select, .dark .en-support-modal__textarea, html[data-theme="dark"] .en-support-modal__input, html[data-theme="dark"] .en-support-modal__select, html[data-theme="dark"] .en-support-modal__textarea { border-color: #334155; background: #0f172a; color: #f8fafc; }
      .en-support-modal__actions { display: flex; align-items: center; gap: 0.55rem; margin-top: 0.7rem; }
      .en-support-modal__attach { display: inline-flex; width: 2.55rem; height: 2.55rem; flex: 0 0 auto; align-items: center; justify-content: center; border: 1px solid #dbe3ef; border-radius: 13px; background: #f8fafc; color: #2563eb; cursor: pointer; transition: border-color 0.2s ease, background 0.2s ease; }
      .en-support-modal__attach:hover { border-color: #93c5fd; background: #eff6ff; }
      .dark .en-support-modal__attach, html[data-theme="dark"] .en-support-modal__attach { border-color: #334155; background: #0f172a; color: #bfdbfe; }
      .en-support-modal__submit { display: inline-flex; min-height: 2.55rem; flex: 1; align-items: center; justify-content: center; gap: 0.45rem; border: 0; border-radius: 13px; background: #2563eb; color: #ffffff; cursor: pointer; font-weight: 850; padding: 0.7rem 1rem; transition: background 0.2s ease, opacity 0.2s ease; }
      .en-support-modal__submit:hover { background: #1d4ed8; }
      .en-support-modal__submit.is-loading { cursor: wait; opacity: 0.72; }
      .en-support-modal__file-hint { margin: 0.5rem 0 0; color: #64748b; font-size: 0.72rem; }
      .dark .en-support-modal__file-hint, html[data-theme="dark"] .en-support-modal__file-hint { color: #94a3b8; }
      .en-support-modal { pointer-events: none; align-items: flex-end; justify-content: flex-end; padding: 0 1rem 1rem; }
      .en-support-modal__backdrop { display: none; }
      .en-support-modal__panel { pointer-events: auto; width: min(480px, calc(100vw - 1rem)); max-height: min(620px, calc(100vh - 1rem)); border-color: rgba(226, 232, 240, 0.9); border-radius: 18px; box-shadow: 0 24px 64px -28px rgba(15, 23, 42, 0.72); }
      .en-support-modal__header { align-items: center; gap: 0.75rem; padding: 1.15rem 1.25rem; border-bottom: 0; background: #2563eb; }
      .en-support-modal__header-icon { width: 2.6rem; height: 2.6rem; border: 2px solid rgba(255, 255, 255, 0.88); background: rgba(255, 255, 255, 0.18); font-size: 1rem; }
      .en-support-modal__title { font-size: 1.05rem; font-weight: 850; }
      .en-support-modal__subtitle { font-size: 0.86rem; font-weight: 700; }
      .en-support-modal__header-actions { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 0.25rem; }
      .en-support-modal__close { width: 1.85rem; height: 1.85rem; background: transparent; font-size: 0.95rem; }
      .en-support-modal__close:hover { background: rgba(255, 255, 255, 0.16); }
      .en-support-modal__tickets, .en-support-modal__empty-tickets, [data-support-ticket-list] { display: none !important; }
      .en-support-modal__body { min-height: 0; background: #ffffff; }
      .dark .en-support-modal__body, html[data-theme="dark"] .en-support-modal__body { background: #111827; }
      .en-support-modal__status { margin: 0.75rem 1rem 0; padding: 0.55rem 0.7rem; font-size: 0.78rem; }
      .en-support-modal__messages { min-height: 265px; max-height: 380px; padding: 1rem 1.15rem; background: #ffffff; }
      .dark .en-support-modal__messages, html[data-theme="dark"] .en-support-modal__messages { background: #111827; }
      .en-support-modal__placeholder { border: 0; background: #f1f5f9; color: #475569; }
      .dark .en-support-modal__placeholder, html[data-theme="dark"] .en-support-modal__placeholder { background: #1e293b; color: #cbd5e1; }
      .en-support-message { margin-bottom: 0.82rem; }
      .en-support-message__stack { max-width: 86%; }
      .en-support-message__bubble { border-radius: 18px; background: #f1f5f9; padding: 0.62rem 0.95rem; color: #334155; font-size: 0.9rem; }
      .en-support-message.is-self .en-support-message__bubble { border-radius: 18px 18px 6px 18px; background: #2563eb; color: #ffffff; }
      .en-support-message:not(.is-self) .en-support-message__bubble { border-radius: 18px 18px 18px 6px; }
      .en-support-message__meta { color: #94a3b8; font-size: 0.76rem; font-weight: 650; }
      .en-support-message__image { width: 142px; height: 96px; border-radius: 12px; }
      .en-support-modal__new-fields, .en-support-modal__new-fields.is-hidden { display: none !important; }
      .en-support-modal__form { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 0.5rem; padding: 0.9rem 1rem 1rem; }
      .en-support-modal__composer { display: contents; }
      .en-support-modal__field { display: contents; }
      .en-support-modal__label { display: none; }
      .en-support-modal__textarea { min-height: 3rem; height: 3rem; max-height: 7rem; resize: none; border: 2px solid #3b82f6; border-radius: 16px; background: #ffffff; padding: 0.78rem 0.95rem; font-size: 0.92rem; font-weight: 650; line-height: 1.35; }
      .dark .en-support-modal__textarea, html[data-theme="dark"] .en-support-modal__textarea { border-color: #60a5fa; background: #0f172a; }
      .en-support-modal__actions { display: contents; margin: 0; }
      .en-support-modal__attach, .en-support-modal__submit { width: 3rem; height: 3rem; min-height: 3rem; border-radius: 16px; }
      .en-support-modal__attach { border: 0; background: #f1f5f9; color: #475569; font-size: 1.05rem; }
      .en-support-modal__attach:hover { background: #e2e8f0; color: #2563eb; }
      .en-support-modal__submit { flex: 0 0 auto; padding: 0; font-size: 1.05rem; }
      .en-support-modal__submit span { display: none; }
      .en-support-modal__file-hint { grid-column: 1 / -1; margin: -0.2rem 0 0; }
      .en-support-modal__panel {
        width: min(435px, calc(100vw - 1rem));
        max-height: min(508px, calc(100vh - 1rem));
        border-color: #334155;
        border-radius: 18px;
        background: #1f2937;
        color: #f8fafc;
        box-shadow: 0 24px 60px -26px rgba(2, 6, 23, 0.9);
      }
      .en-support-modal__header {
        min-height: 76px;
        padding: 1rem 1.125rem;
        background: #2563eb;
      }
      .en-support-modal__header-icon {
        width: 2.3rem;
        height: 2.3rem;
        border: 2px solid rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
      }
      .en-support-modal__title {
        font-size: 1rem;
        line-height: 1.12;
        white-space: nowrap;
      }
      .en-support-modal__subtitle {
        margin-top: 0.25rem;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.82rem;
        font-weight: 750;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .en-support-modal__body,
      .en-support-modal__messages,
      .en-support-modal__form {
        background: #1f2937;
      }
      .en-support-modal__messages {
        min-height: 295px;
        max-height: 330px;
        padding: 0.95rem 1rem 0.8rem;
      }
      .en-support-modal__placeholder {
        border: 0;
        border-radius: 17px;
        background: #374151;
        color: #dbe3ef;
      }
      .en-support-message__bubble {
        background: #374151;
        color: #f1f5f9;
        font-size: 0.94rem;
        font-weight: 720;
        line-height: 1.36;
        padding: 0.68rem 1rem;
      }
      .en-support-message.is-self .en-support-message__bubble {
        background: #3b82f6;
        color: #ffffff;
      }
      .en-support-message__meta {
        color: #9ca3af;
        font-size: 0.78rem;
        font-weight: 760;
      }
      .en-support-message__image {
        width: 132px;
        height: 88px;
        border-color: rgba(255, 255, 255, 0.28);
      }
      .en-support-modal__form {
        border-top-color: #334155;
        grid-template-columns: minmax(0, 1fr) 44px 44px;
        gap: 0.5rem;
        padding: 0.9rem 0.85rem 0.75rem;
      }
      .en-support-modal__textarea {
        height: 48px;
        min-height: 48px;
        max-height: 48px;
        overflow: hidden;
        scrollbar-width: none;
        border: 2px solid #3b82f6;
        border-radius: 16px;
        background: #1f2937;
        color: #f8fafc;
        font-size: 0.9rem;
        font-weight: 760;
        line-height: 1.25;
        padding: 0.82rem 0.92rem;
      }
      .en-support-modal__textarea::-webkit-scrollbar {
        display: none;
      }
      .en-support-modal__textarea::placeholder {
        color: #9ca3af;
      }
      .en-support-modal__attach,
      .en-support-modal__submit {
        width: 44px;
        height: 48px;
        min-height: 48px;
        border-radius: 16px;
      }
      .en-support-modal__attach {
        background: #374151;
        color: #cbd5e1;
      }
      .en-support-modal__attach:hover {
        background: #475569;
        color: #ffffff;
      }
      .en-support-modal__submit {
        background: #2563eb;
        color: #ffffff;
      }
      .en-support-modal__submit:hover {
        background: #1d4ed8;
      }
      .en-support-modal__file-hint {
        color: #9ca3af;
      }
      .en-support-modal {
        align-items: flex-end;
        justify-content: flex-end;
        padding: 0 1rem 6rem 0;
        pointer-events: none;
      }
      .en-support-modal__panel {
        width: min(24rem, calc(100vw - 2rem));
        max-height: min(520px, calc(100vh - 7rem));
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: #ffffff;
        color: #1f2937;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
        pointer-events: auto;
        transform-origin: bottom right;
      }
      html.dark .en-support-modal__panel,
      html[data-theme="dark"] .en-support-modal__panel {
        border-color: #374151;
        background: #1f2937;
        color: #f3f4f6;
      }
      .en-support-modal__header {
        min-height: auto;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #2563eb;
      }
      .en-support-modal__header-icon {
        width: 2rem;
        height: 2rem;
        border: 2px solid #ffffff;
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        font-size: 0.9rem;
      }
      .en-support-modal__title {
        font-size: 0.875rem;
        font-weight: 800;
        line-height: 1.2;
      }
      .en-support-modal__subtitle {
        margin-top: 0.1rem;
        color: #dbeafe;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.25;
      }
      .en-support-modal__close {
        width: 1.55rem;
        height: 1.55rem;
        border-radius: 0.25rem;
        background: transparent;
        color: #ffffff;
      }
      .en-support-modal__close:hover {
        background: #1d4ed8;
      }
      .en-support-modal__body,
      .en-support-modal__messages,
      .en-support-modal__form {
        background: #ffffff;
      }
      html.dark .en-support-modal__body,
      html.dark .en-support-modal__messages,
      html.dark .en-support-modal__form,
      html[data-theme="dark"] .en-support-modal__body,
      html[data-theme="dark"] .en-support-modal__messages,
      html[data-theme="dark"] .en-support-modal__form {
        background: #1f2937;
      }
      .en-support-modal__messages {
        min-height: 150px;
        max-height: 300px;
        padding: 0.75rem;
      }
      .en-support-modal__placeholder {
        border: 0;
        border-radius: 18px 18px 18px 4px;
        background: #f3f4f6;
        color: #1f2937;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
      html.dark .en-support-modal__placeholder,
      html[data-theme="dark"] .en-support-modal__placeholder {
        background: #374151;
        color: #f3f4f6;
      }
      .en-support-message {
        margin-bottom: 0.75rem;
      }
      .en-support-message__stack {
        max-width: 80%;
      }
      .en-support-message__bubble {
        border-radius: 18px 18px 18px 4px;
        background: #f3f4f6;
        color: #1f2937;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.45;
      }
      .en-support-message.is-self .en-support-message__bubble {
        border-radius: 18px 18px 4px 18px;
        background: #2563eb;
        color: #ffffff;
      }
      html.dark .en-support-message__bubble,
      html[data-theme="dark"] .en-support-message__bubble {
        background: #374151;
        color: #f3f4f6;
      }
      html.dark .en-support-message.is-self .en-support-message__bubble,
      html[data-theme="dark"] .en-support-message.is-self .en-support-message__bubble {
        background: #3b82f6;
        color: #ffffff;
      }
      .en-support-message__meta {
        color: #9ca3af;
        font-size: 0.72rem;
        font-weight: 500;
      }
      .en-support-message__image {
        width: 7rem;
        max-width: 42vw;
        height: 5rem;
        border-radius: 0.68rem;
      }
      .en-support-modal__form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 2.5rem 2.5rem;
        gap: 0.5rem;
        align-items: center;
        border-top: 1px solid #f3f4f6;
        padding: 0.75rem;
      }
      html.dark .en-support-modal__form,
      html[data-theme="dark"] .en-support-modal__form {
        border-top-color: #374151;
      }
      .en-support-modal__textarea {
        height: 2.5rem;
        min-height: 2.5rem;
        max-height: 2.5rem;
        overflow: hidden;
        border: 0;
        border-radius: 0.75rem;
        background: #f3f4f6;
        color: #1f2937;
        padding: 0.62rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.25;
      }
      .en-support-modal__textarea:focus {
        box-shadow: 0 0 0 2px #2563eb;
      }
      html.dark .en-support-modal__textarea,
      html[data-theme="dark"] .en-support-modal__textarea {
        background: #374151;
        color: #f3f4f6;
      }
      .en-support-modal__textarea::placeholder {
        color: #9ca3af;
      }
      .en-support-modal__attach,
      .en-support-modal__submit {
        width: 2.5rem;
        height: 2.5rem;
        min-height: 2.5rem;
        border-radius: 0.75rem;
      }
      .en-support-modal__attach {
        background: #f3f4f6;
        color: #4b5563;
      }
      .en-support-modal__attach:hover {
        background: #e0ecff;
        color: #1d4ed8;
      }
      html.dark .en-support-modal__attach,
      html[data-theme="dark"] .en-support-modal__attach {
        background: #374151;
        color: #d1d5db;
      }
      html.dark .en-support-modal__attach:hover,
      html[data-theme="dark"] .en-support-modal__attach:hover {
        background: rgba(37, 99, 235, 0.28);
        color: #bfdbfe;
      }
      .en-support-modal__submit {
        background: #2563eb;
      }
      .en-support-modal__submit:hover {
        background: #1d4ed8;
      }
      .en-support-modal__status {
        margin: 0.75rem 0.75rem 0;
      }
      .en-support-modal__file-hint {
        grid-column: 1 / -1;
        margin: -0.25rem 0 0;
        color: #6b7280;
      }
      html.dark .en-support-modal__file-hint,
      html[data-theme="dark"] .en-support-modal__file-hint {
        color: #9ca3af;
      }
      @media (max-width: 640px) {
        .en-support-modal { justify-content: center; padding: 0 1rem 6rem; }
        .en-support-modal__panel { width: calc(100vw - 2rem); max-height: min(520px, calc(100vh - 7rem)); border-radius: 1rem; }
        .en-support-modal__messages { min-height: 150px; max-height: 300px; }
      }
    `;

    document.head.appendChild(style);
  }

  function ensureModal() {
    if (els.modal instanceof HTMLElement) {
      return;
    }

    injectStyles();

    const modal = document.createElement('div');
    modal.className = 'en-support-modal';
    modal.hidden = true;
    modal.setAttribute('data-support-modal', '1');
    modal.innerHTML = `
      <div class="en-support-modal__backdrop" data-support-modal-close></div>
      <section class="en-support-modal__panel" role="dialog" aria-modal="true" aria-labelledby="en-support-modal-title">
        <header class="en-support-modal__header">
          <div class="en-support-modal__header-icon" aria-hidden="true">
            <i class="fas fa-headset"></i>
          </div>
          <div style="min-width:0; flex:1;">
            <h2 id="en-support-modal-title" class="en-support-modal__title">Assistance École Numérique</h2>
            <p class="en-support-modal__subtitle" data-support-modal-subtitle>Support · Prêt à répondre</p>
          </div>
          <div class="en-support-modal__header-actions">
            <button type="button" class="en-support-modal__close" data-support-modal-close aria-label="Minimiser">
              <i class="fas fa-minus" aria-hidden="true"></i>
            </button>
            <button type="button" class="en-support-modal__close" data-support-modal-close aria-label="Fermer">
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </div>
        </header>
        <div class="en-support-modal__tickets">
          <button type="button" class="en-support-modal__new" data-support-new-ticket>
            <i class="fas fa-plus-circle" aria-hidden="true"></i>
            Nouveau
          </button>
          <div class="en-support-modal__empty-tickets" data-support-empty-tickets>Aucun ticket.</div>
          <div data-support-ticket-list style="display:flex;gap:.5rem;"></div>
        </div>
        <div class="en-support-modal__body">
          <p class="en-support-modal__status" data-support-modal-status></p>
          <div class="en-support-modal__messages" data-support-messages></div>
          <form class="en-support-modal__form" data-support-modal-form enctype="multipart/form-data">
            <div class="en-support-modal__new-fields is-hidden" data-support-new-fields hidden></div>
            <input type="hidden" name="subject" data-support-subject>
            <select name="category" data-support-category hidden></select>
            <select name="priority" data-support-priority hidden></select>
            <div class="en-support-modal__composer">
              <label class="en-support-modal__field">
                <span class="en-support-modal__label" data-support-message-label>Message</span>
                <textarea class="en-support-modal__textarea" name="body" rows="1" data-support-body placeholder="Répondre à ce ticket..."></textarea>
              </label>
              <label class="en-support-modal__attach" title="Joindre une capture" aria-label="Joindre une capture">
                <i class="fas fa-paperclip" aria-hidden="true"></i>
                <input type="file" name="attachments[]" accept="image/*,.pdf" multiple data-support-attachments hidden>
              </label>
              <button type="submit" class="en-support-modal__submit" data-support-submit>
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                <span>Envoyer</span>
              </button>
            </div>
            <p class="en-support-modal__file-hint" data-support-file-hint>Capture ou PDF, 5 Mo maximum.</p>
          </form>
        </div>
      </section>
    `;

    document.body.appendChild(modal);

    els.modal = modal;
    els.panel = modal.querySelector('.en-support-modal__panel');
    els.title = modal.querySelector('#en-support-modal-title');
    els.subtitle = modal.querySelector('[data-support-modal-subtitle]');
    els.status = modal.querySelector('[data-support-modal-status]');
    els.ticketList = modal.querySelector('[data-support-ticket-list]');
    els.emptyTickets = modal.querySelector('[data-support-empty-tickets]');
    els.messages = modal.querySelector('[data-support-messages]');
    els.form = modal.querySelector('[data-support-modal-form]');
    els.subject = modal.querySelector('[data-support-subject]');
    els.category = modal.querySelector('[data-support-category]');
    els.priority = modal.querySelector('[data-support-priority]');
    els.body = modal.querySelector('[data-support-body]');
    els.attachments = modal.querySelector('[data-support-attachments]');
    els.fileHint = modal.querySelector('[data-support-file-hint]');
    els.submit = modal.querySelector('[data-support-submit]');
    els.newFields = modal.querySelector('[data-support-new-fields]');
    els.newButton = modal.querySelector('[data-support-new-ticket]');

    modal.querySelectorAll('[data-support-modal-close]').forEach((button) => {
      button.addEventListener('click', closeModal);
    });

    if (els.newButton instanceof HTMLButtonElement) {
      els.newButton.addEventListener('click', () => {
        state.activeId = null;
        state.lastMessageId = 0;
        renderTickets();
        renderNewConversation();
      });
    }

    if (els.form instanceof HTMLFormElement) {
      els.form.addEventListener('submit', submitMessage);
    }

    if (els.attachments instanceof HTMLInputElement) {
      els.attachments.addEventListener('change', () => {
        setStatus('');
        updateFileHint();
      });
    }

    if (els.body instanceof HTMLTextAreaElement) {
      els.body.addEventListener('input', () => setStatus(''));
    }
  }

  function setupModalForTrigger(trigger) {
    state.currentTrigger = trigger;
    state.basePath = inferBasePath(trigger);
    state.space = inferSpace(trigger);

    if (els.category instanceof HTMLSelectElement) {
      els.category.innerHTML = categoryOptionsHtml();
    }

    if (els.priority instanceof HTMLSelectElement) {
      els.priority.innerHTML = priorityOptionsHtml();
    }

    if (els.subject instanceof HTMLInputElement) {
      els.subject.placeholder =
        state.space === 'teacher'
          ? 'Ex. Problème de publication du cours'
          : 'Ex. Problème d’accès au cours';
    }
  }

  function openModal(trigger) {
    ensureModal();
    setupModalForTrigger(trigger);

    if (!(els.modal instanceof HTMLElement)) return;
    els.modal.hidden = false;
    document.documentElement.classList.add('en-support-modal-open');
    setStatus('');
    renderLoading();

    loadConversations(true)
      .then(() => {
        const selected = getActiveConversation() || preferredConversation();
        if (selected) {
          selectConversation(Number(selected.id || 0), false);
        } else {
          renderNewConversation();
        }
      })
      .catch((error) => {
        renderNewConversation();
        setStatus(error instanceof Error ? error.message : 'Impossible de charger vos tickets.', 'error');
      })
      .finally(() => {
        startPolling();
        if (els.body instanceof HTMLTextAreaElement) {
          window.setTimeout(() => els.body?.focus(), 80);
        }
      });
  }

  function closeModal() {
    if (els.modal instanceof HTMLElement) {
      els.modal.hidden = true;
    }
    document.documentElement.classList.remove('en-support-modal-open');
    stopPolling();
  }

  function renderLoading() {
    if (els.messages instanceof HTMLElement) {
      els.messages.innerHTML = '<div class="en-support-modal__placeholder">Chargement de vos tickets...</div>';
    }
  }

  function renderNewConversation() {
    const defaultCategory = (labels.categories[state.space] || labels.categories.student)[0]?.[0] || 'course';
    state.activeId = null;
    state.lastMessageId = 0;

    if (els.title instanceof HTMLElement) {
      els.title.textContent = 'Assistance École Numérique';
    }
    if (els.subtitle instanceof HTMLElement) {
      els.subtitle.textContent = 'Nouveau message · Support';
    }
    if (els.newFields instanceof HTMLElement) {
      els.newFields.classList.add('is-hidden');
    }
    if (els.category instanceof HTMLSelectElement) {
      els.category.value = defaultCategory;
    }
    if (els.priority instanceof HTMLSelectElement) {
      els.priority.value = 'normal';
    }
    if (els.submit instanceof HTMLButtonElement) {
      const label = els.submit.querySelector('span');
      if (label) label.textContent = 'Envoyer';
    }
    if (els.messages instanceof HTMLElement) {
      els.messages.innerHTML = `
        <div class="en-support-modal__placeholder">
          Expliquez le problème, le cours concerné et ajoutez une capture si possible.
        </div>
      `;
    }
    renderTickets();
  }

  function renderTickets() {
    if (!(els.ticketList instanceof HTMLElement) || !(els.emptyTickets instanceof HTMLElement)) return;

    els.ticketList.innerHTML = '';
    const conversations = state.conversations.slice(0, 12);
    els.emptyTickets.hidden = conversations.length > 0;
    els.emptyTickets.style.display = conversations.length > 0 ? 'none' : 'inline-flex';

    if (els.newButton instanceof HTMLButtonElement) {
      els.newButton.classList.toggle('is-active', !state.activeId);
    }

    conversations.forEach((conversation) => {
      const id = Number(conversation.id || 0);
      const unread = Math.max(0, Number.parseInt(String(conversation.unread_count || '0'), 10) || 0);
      const status = normalizeStatus(conversation.status);
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `en-support-ticket-chip${id === Number(state.activeId || 0) ? ' is-active' : ''}`;
      button.innerHTML = `
        <span class="en-support-ticket-chip__top">
          <span>${escapeHtml(formatTicketNumber(id))}</span>
          <span>${escapeHtml(statusLabel(status))}</span>
          ${unread > 0 ? `<span class="en-support-ticket-chip__badge">${unread}</span>` : ''}
        </span>
        <span class="en-support-ticket-chip__subject">${escapeHtml(conversation.subject || 'Demande support')}</span>
      `;
      button.addEventListener('click', () => selectConversation(id, true));
      els.ticketList.appendChild(button);
    });
  }

  function renderConversationHeader(conversation) {
    if (els.title instanceof HTMLElement) {
      els.title.textContent = 'Assistance École Numérique';
    }

    if (els.subtitle instanceof HTMLElement) {
      const subject = compactText(conversation?.subject || 'Ticket support');
      els.subtitle.textContent = `${subject} · ${shortStatusLabel(conversation?.status)}`;
    }

    if (els.newFields instanceof HTMLElement) {
      els.newFields.classList.add('is-hidden');
    }

    if (els.submit instanceof HTMLButtonElement) {
      const label = els.submit.querySelector('span');
      if (label) label.textContent = normalizeStatus(conversation?.status) === 'closed' ? 'Réouvrir et répondre' : 'Répondre';
    }
  }

  function renderMessage(message) {
    const conversation = getActiveConversation();
    const isSelf = conversation && Number(message.sender_id || 0) === Number(conversation.created_by || 0);
    const wrapper = document.createElement('div');
    wrapper.className = `en-support-message${isSelf ? ' is-self' : ''}`;

    const attachments = Array.isArray(message.attachments) ? message.attachments : [];
    const attachmentHtml = attachments.length
      ? `<div class="en-support-message__attachments">${attachments
          .map((attachment) => {
            if (isImageAttachment(attachment)) {
              return `<a class="en-support-message__image" href="${escapeHtml(attachmentUrl(attachment))}" target="_blank" rel="noopener"><img src="${escapeHtml(attachmentUrl(attachment, true))}" alt="${escapeHtml(attachment.name || 'Capture jointe')}" loading="lazy"></a>`;
            }
            return `<a class="en-support-message__file" href="${escapeHtml(attachmentUrl(attachment))}" target="_blank" rel="noopener"><i class="fas fa-paperclip" aria-hidden="true"></i>${escapeHtml(attachment.name || 'Fichier')}</a>`;
          })
          .join('')}</div>`
      : '';
    const text = compactText(message.body) !== '' ? escapeHtml(message.body) : (attachments.length ? '' : '(message sans texte)');

    wrapper.innerHTML = `
      <div class="en-support-message__stack">
        <div class="en-support-message__bubble">${text}${attachmentHtml}</div>
        <p class="en-support-message__meta">${escapeHtml(message.sender_name || (isSelf ? 'Vous' : 'Support'))} · ${escapeHtml(formatDate(message.created_at || ''))}</p>
      </div>
    `;

    return wrapper;
  }

  async function loadConversations(keepSelection = true) {
    if (state.loadingConversations) return;
    state.loadingConversations = true;
    try {
      const params = new URLSearchParams();
      params.set('scope', 'mine');
      params.set('limit', '100');
      const payload = await fetchJson(`${apiUrl('/api/support_conversations')}?${params.toString()}`);
      state.conversations = Array.isArray(payload.conversations) ? payload.conversations : [];
      if (!keepSelection || (state.activeId && !getActiveConversation())) {
        state.activeId = null;
        state.lastMessageId = 0;
      }
      syncTriggerBadges();
      renderTickets();
    } finally {
      state.loadingConversations = false;
    }
  }

  async function loadMessages(reset = true) {
    if (!state.activeId || state.loadingMessages || !(els.messages instanceof HTMLElement)) return;

    state.loadingMessages = true;
    try {
      const params = new URLSearchParams();
      params.set('conversation_id', String(state.activeId));
      if (!reset && state.lastMessageId > 0) {
        params.set('since_id', String(state.lastMessageId));
      }

      const payload = await fetchJson(`${apiUrl('/api/support_messages')}?${params.toString()}`);
      const messages = Array.isArray(payload.messages) ? payload.messages : [];
      if (reset) {
        els.messages.innerHTML = '';
        state.lastMessageId = 0;
      }

      messages.forEach((message) => {
        els.messages.appendChild(renderMessage(message));
        state.lastMessageId = Math.max(state.lastMessageId, Number(message.id || 0));
      });

      if (!els.messages.children.length) {
        els.messages.innerHTML = '<div class="en-support-modal__placeholder">Aucun message dans ce ticket. Vous pouvez répondre ci-dessous.</div>';
      }

      els.messages.scrollTop = els.messages.scrollHeight;

      const conversation = getActiveConversation();
      if (conversation) {
        conversation.unread_count = 0;
        syncTriggerBadges();
        renderTickets();
      }
    } finally {
      state.loadingMessages = false;
    }
  }

  function selectConversation(id, focusInput) {
    const numericId = Number.parseInt(String(id || '0'), 10) || 0;
    const conversation = state.conversations.find((item) => Number(item.id || 0) === numericId);
    if (!conversation) {
      renderNewConversation();
      return;
    }

    state.activeId = numericId;
    state.lastMessageId = 0;
    setStatus('');
    renderConversationHeader(conversation);
    renderTickets();
    if (els.messages instanceof HTMLElement) {
      els.messages.innerHTML = '<div class="en-support-modal__placeholder">Chargement de la discussion...</div>';
    }

    loadMessages(true).catch((error) => {
      setStatus(error instanceof Error ? error.message : 'Impossible de charger la discussion.', 'error');
    });

    if (focusInput && els.body instanceof HTMLTextAreaElement) {
      window.setTimeout(() => els.body?.focus(), 60);
    }
  }

  function resetComposer() {
    if (els.body instanceof HTMLTextAreaElement) {
      els.body.value = '';
    }
    if (els.subject instanceof HTMLInputElement) {
      els.subject.value = '';
    }
    if (els.attachments instanceof HTMLInputElement) {
      els.attachments.value = '';
    }
    updateFileHint();
  }

  function hasAttachments() {
    return els.attachments instanceof HTMLInputElement && els.attachments.files instanceof FileList && els.attachments.files.length > 0;
  }

  function subjectFromMessage(message, category) {
    const text = compactText(message);
    if (text) {
      return text.slice(0, 80);
    }
    return labels.defaultSubjects[category] || labels.defaultSubjects.other;
  }

  async function submitMessage(event) {
    event.preventDefault();
    if (!(els.form instanceof HTMLFormElement) || !(els.body instanceof HTMLTextAreaElement)) return;

    const message = els.body.value.trim();
    if (!message && !hasAttachments()) {
      setStatus('Écrivez un message ou joignez une capture avant envoi.', 'error');
      return;
    }

    const csrf = getCsrfToken();
    const formData = new FormData(els.form);
    if (csrf) {
      formData.set('_csrf', csrf);
    }
    formData.set('body', message);

    setStatus('');
    setSubmitLoading(true);

    try {
      if (!state.activeId) {
        const category = String(formData.get('category') || 'course');
        if (!compactText(String(formData.get('subject') || ''))) {
          formData.set('subject', subjectFromMessage(message, category));
        }

        const payload = await fetchJson(apiUrl('/api/support_conversations'), {
          method: 'POST',
          body: formData,
        });
        resetComposer();
        await loadConversations(false);
        const createdId = Number.parseInt(String(payload.conversation_id || '0'), 10) || 0;
        if (createdId > 0) {
          selectConversation(createdId, false);
        }
        setStatus('Message envoyé au support.', 'success');
        return;
      }

      formData.set('conversation_id', String(state.activeId));
      await fetchJson(apiUrl('/api/support_messages'), {
        method: 'POST',
        body: formData,
      });
      resetComposer();
      await loadMessages(false);
      await loadConversations(true);
      setStatus('Réponse envoyée.', 'success');
    } catch (error) {
      setStatus(error instanceof Error ? error.message : 'Impossible d’envoyer le message.', 'error');
    } finally {
      setSubmitLoading(false);
    }
  }

  function startPolling() {
    stopPolling();
    state.pollTimer = window.setInterval(() => {
      if (document.hidden || !(els.modal instanceof HTMLElement) || els.modal.hidden) {
        return;
      }
      if (state.activeId) {
        loadMessages(false).catch(() => {});
      }
      loadConversations(true).catch(() => {});
    }, 8000);
  }

  function stopPolling() {
    if (state.pollTimer) {
      window.clearInterval(state.pollTimer);
      state.pollTimer = null;
    }
  }

  document.addEventListener(
    'click',
    (event) => {
      const target = event.target instanceof Element ? event.target.closest('[data-support-fab]') : null;
      if (!(target instanceof HTMLElement)) return;

      event.preventDefault();
      event.stopPropagation();
      openModal(target);
    },
    true
  );

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && els.modal instanceof HTMLElement && !els.modal.hidden) {
      closeModal();
    }
  });
})();
