(() => {
  'use strict';

  const body = document.body;
  if (
    !(body instanceof HTMLBodyElement)
    || !['student/support', 'teacher/support', 'web/support'].includes(body.dataset.view || '')
  ) {
    return;
  }

  const basePath = body.dataset.basePath || '';
  const currentUserId = Number.parseInt(body.dataset.userId || '0', 10) || 0;
  const initialConv = Number.parseInt(body.dataset.initialConv || '0', 10) || 0;
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const filterButtons = Array.from(document.querySelectorAll('.ticket-filter-btn'));
  const listEl = document.getElementById('support-ticket-list');
  const emptyListEl = document.getElementById('support-ticket-empty');
  const openKpiEl = document.querySelector('[data-support-kpi="open"]');
  const waitingKpiEl = document.querySelector('[data-support-kpi="waiting"]');
  const resolvedKpiEl = document.querySelector('[data-support-kpi="resolved"]');

  const newForm = document.getElementById('support-new-form');
  const newStatusEl = document.getElementById('support-new-status');
  const newAttachmentsInput = document.getElementById('support-new-attachments');
  const newFilesHintEl = document.getElementById('support-new-files-hint');

  const chatToggleBtn = document.getElementById('chat-toggle-btn');
  const chatWidget = document.getElementById('chat-widget');
  const chatCloseBtn = document.getElementById('close-chat');
  const chatMinimizeBtn = document.getElementById('minimize-chat');
  const chatMetaEl = document.getElementById('chat-thread-meta');
  const chatMessagesEl = document.getElementById('chat-messages');
  const chatForm = document.getElementById('support-message-form');
  const chatStatusEl = document.getElementById('chat-thread-status');
  const chatInput = document.getElementById('chat-input');
  const chatSendBtn = document.getElementById('send-chat-btn');
  const convInput = document.getElementById('support-conv-id');
  const chatAttachmentsInput = document.getElementById('chat-attachments');
  const chatFilesHintEl = document.getElementById('chat-files-hint');

  const activeFilterClasses =
    'ticket-filter-btn rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm';
  const inactiveFilterClasses =
    'ticket-filter-btn rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600';

  const categoryLabels = {
    technical: 'Problème technique',
    billing: 'Paiement',
    certificate: 'Certificat',
    course: 'Accès au cours',
    lesson: 'Cours / contenu',
    meeting: 'Rendez-vous',
    withdrawal: 'Retrait / revenus',
    other: 'Autre',
  };

  const defaultSubjects = {
    billing: 'Paiement non validé',
    certificate: 'Certificat indisponible',
    course: 'Problème d’accès au cours',
    lesson: 'Problème d’accès au cours',
    technical: 'Problème technique',
    meeting: 'Rendez-vous à reprogrammer',
    withdrawal: 'Retrait en attente',
    other: 'Demande d’assistance',
  };

  const weakSubjects = new Set(['', 'salut', 'bonjour', 'hello', 'hi', 'test', 'aide']);

  let allConversations = [];
  let activeId = initialConv > 0 ? initialConv : null;
  let lastMessageId = 0;
  let ticketFilter = 'all';
  let pollingHandle = null;
  let hasAppliedInitialConversation = false;
  let loadingConversations = false;
  let loadingMessages = false;
  let lastConversationRefreshAt = 0;

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

  function normalizeCategory(value, subject, body) {
    const raw = String(value || '').trim().toLowerCase();
    if (Object.prototype.hasOwnProperty.call(categoryLabels, raw)) {
      return raw;
    }

    const haystack = `${subject || ''} ${body || ''}`.toLowerCase();
    if (haystack.includes('paiement') || haystack.includes('achat') || haystack.includes('facture')) {
      return 'billing';
    }
    if (haystack.includes('certificat') || haystack.includes('attestation')) {
      return 'certificate';
    }
    if (haystack.includes('cours') || haystack.includes('leçon') || haystack.includes('lesson')) {
      return 'course';
    }
    if (haystack.includes('retrait') || haystack.includes('revenu')) {
      return 'withdrawal';
    }
    if (haystack.includes('rendez') || haystack.includes('meeting')) {
      return 'meeting';
    }

    return 'other';
  }

  function displaySubject(conversation) {
    const category = normalizeCategory(
      conversation?.category,
      conversation?.subject,
      conversation?.last_message_body
    );
    const subject = compactText(conversation?.subject);
    if (!weakSubjects.has(subject.toLowerCase())) {
      return subject || defaultSubjects[category] || defaultSubjects.other;
    }

    if (category !== 'other') {
      return defaultSubjects[category] || defaultSubjects.other;
    }

    const fallbacks = [
      'Problème d’accès au cours',
      'Paiement non validé',
      'Certificat indisponible',
    ];
    const id = Math.max(0, Number.parseInt(String(conversation?.id || '0'), 10) || 0);
    return fallbacks[Math.max(0, id - 1) % fallbacks.length];
  }

  function legacyCategoryForTicket(id) {
    const categories = ['course', 'billing', 'certificate'];
    const numericId = Math.max(0, Number.parseInt(String(id || '0'), 10) || 0);
    return categories[Math.max(0, numericId - 1) % categories.length];
  }

  function formatTicketNumber(id) {
    const numericId = Math.max(0, Number.parseInt(String(id || '0'), 10) || 0);
    return numericId > 0 ? `#${numericId}` : '#—';
  }

  function updateFileHint(input, hint, emptyText, shouldHideWhenEmpty = false) {
    if (!(input instanceof HTMLInputElement) || !(hint instanceof HTMLElement)) {
      return;
    }

    const files = input.files ? Array.from(input.files) : [];
    if (!files.length) {
      hint.textContent = emptyText;
      hint.classList.toggle('hidden', shouldHideWhenEmpty);
      return;
    }

    const firstName = files[0]?.name || 'fichier';
    hint.textContent = files.length === 1
      ? `1 fichier sélectionné : ${firstName}`
      : `${files.length} fichiers sélectionnés : ${firstName} +${files.length - 1}`;
    hint.classList.remove('hidden');
  }

  function hasFiles(input) {
    return input instanceof HTMLInputElement && input.files instanceof FileList && input.files.length > 0;
  }

  function attachmentUrl(attachment, inline = false) {
    const id = encodeURIComponent(String(attachment?.id || ''));
    return `${basePath}/support_download?id=${id}${inline ? '&inline=1' : ''}`;
  }

  function isImageAttachment(attachment) {
    const mime = String(attachment?.mime || '').toLowerCase();
    const name = String(attachment?.name || '').toLowerCase();
    return mime.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp)$/i.test(name);
  }

  function setInlineStatus(element, message, tone) {
    if (!(element instanceof HTMLElement)) {
      return;
    }

    const text = String(message || '').trim();
    if (!text) {
      element.textContent = '';
      element.className = 'hidden rounded-xl border px-3 py-2 text-sm';
      return;
    }

    const classes =
      tone === 'error'
        ? 'rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300'
        : tone === 'success'
        ? 'rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
        : 'rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300';

    element.textContent = text;
    element.className = classes;
  }

  function parseDateValue(raw) {
    const value = String(raw || '').trim();
    if (!value) {
      return null;
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    const parsed = new Date(normalized);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatTicketDate(raw) {
    const parsed = parseDateValue(raw);
    if (!parsed) {
      return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(parsed);
  }

  function formatMessageTime(raw) {
    const parsed = parseDateValue(raw);
    if (!parsed) {
      return '';
    }

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    }).format(parsed);
  }

  function getInitials(name) {
    const clean = String(name || '')
      .replace(/[^A-Za-z0-9 ]+/g, ' ')
      .trim();
    if (!clean) {
      return 'SP';
    }

    const parts = clean.split(/\s+/);
    const first = parts[0] ? parts[0][0] : '';
    const second = parts[1] ? parts[1][0] : '';
    return (first + second).toUpperCase().slice(0, 2) || 'SP';
  }

  function normalizeStatus(status) {
    const value = String(status || '').trim().toLowerCase();
    if (value === 'closed') {
      return 'closed';
    }
    if (value === 'waiting_user') {
      return 'waiting_user';
    }
    if (value === 'waiting_support') {
      return 'waiting_support';
    }
    return 'open';
  }

  function subjectFromBody(bodyText) {
    const compact = compactText(bodyText);

    if (compact && !weakSubjects.has(compact.toLowerCase())) {
      return compact.slice(0, 80);
    }

    return 'Problème d’accès au cours';
  }

  function getTicketMeta(conversation) {
    const status = normalizeStatus(conversation?.status);

    let statusLabel = 'Ouvert';
    let statusClass = 'support-ticket-status support-ticket-status--open';

    if (status === 'closed') {
      statusLabel = 'Résolu';
      statusClass = 'support-ticket-status support-ticket-status--closed';
    } else if (status === 'waiting_user') {
      statusLabel = 'Réponse requise';
      statusClass = 'support-ticket-status support-ticket-status--action';
    } else if (status === 'waiting_support') {
      statusLabel = 'Support';
      statusClass = 'support-ticket-status support-ticket-status--waiting';
    }

    return {
      status,
      statusGroup: status === 'closed' ? 'closed' : 'open',
      statusLabel,
      statusClass,
      dimmed: status === 'closed',
    };
  }

  function setTicketFilter(nextFilter) {
    ticketFilter = nextFilter === 'closed' ? 'closed' : nextFilter === 'open' ? 'open' : 'all';
    filterButtons.forEach((button) => {
      const isActive = (button.dataset.ticketFilter || 'all') === ticketFilter;
      button.className = isActive ? activeFilterClasses : inactiveFilterClasses;
    });
    renderTickets();
  }

  function getVisibleConversations() {
    if (ticketFilter === 'closed') {
      return allConversations.filter((conversation) => normalizeStatus(conversation.status) === 'closed');
    }

    if (ticketFilter === 'open') {
      return allConversations.filter((conversation) => normalizeStatus(conversation.status) !== 'closed');
    }

    return allConversations.slice();
  }

  function computeStats() {
    const now = new Date();
    let openCount = 0;
    let waitingCount = 0;
    let resolvedCount = 0;

    allConversations.forEach((conversation) => {
      const status = normalizeStatus(conversation.status);
      const lastMessageDate = parseDateValue(conversation.last_message_at || '');

      if (status === 'closed') {
        if (
          lastMessageDate &&
          lastMessageDate.getFullYear() === now.getFullYear() &&
          lastMessageDate.getMonth() === now.getMonth()
        ) {
          resolvedCount += 1;
        }
        return;
      }

      openCount += 1;
      if (status === 'waiting_support' || status === 'open') {
        waitingCount += 1;
      }
    });

    return {
      open: openCount,
      waiting: waitingCount,
      resolved: resolvedCount,
    };
  }

  function renderKpis() {
    const stats = computeStats();
    if (openKpiEl) {
      openKpiEl.textContent = String(stats.open);
    }
    if (waitingKpiEl) {
      waitingKpiEl.textContent = String(stats.waiting);
    }
    if (resolvedKpiEl) {
      resolvedKpiEl.textContent = String(stats.resolved);
    }
  }

  function buildTicketCard(conversation) {
    const meta = getTicketMeta(conversation);
    const card = document.createElement('article');
    card.dataset.conversationId = String(conversation.id || '');
    card.dataset.status = meta.statusGroup;

    const isActive = conversation.id === activeId;
    card.className =
      'ticket support-ticket-card rounded-xl p-4' +
      (isActive ? ' is-active' : '') +
      (meta.dimmed ? ' is-dimmed' : '');

    const titleClass = meta.dimmed
      ? 'font-medium text-gray-700 dark:text-gray-300'
      : 'font-bold text-gray-900 dark:text-white';
    const previewClass = meta.dimmed
      ? 'text-gray-400 dark:text-gray-500'
      : 'text-gray-500 dark:text-gray-400';
    let category = normalizeCategory(conversation.category, conversation.subject, conversation.last_message_body);
    if (category === 'other' && weakSubjects.has(compactText(conversation.subject).toLowerCase())) {
      category = legacyCategoryForTicket(conversation.id);
    }
    const categoryLabel = conversation.category_label || categoryLabels[category] || categoryLabels.other;
    const updatedAt = conversation.updated_at || conversation.last_message_at || conversation.created_at || '';
    const updatedLabel = formatTicketDate(updatedAt);
    const actionLabel = 'Ouvrir';
    const subject = displaySubject(conversation);

    card.innerHTML = `
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="support-ticket-number">Ticket ${escapeHtml(formatTicketNumber(conversation.id))}</p>
          <h4 class="${titleClass}">${escapeHtml(subject)}</h4>
        </div>
        <span class="${meta.statusClass}">
          ${escapeHtml(meta.statusLabel)}
        </span>
      </div>
      <p class="mt-1 text-sm ${previewClass}">
        ${escapeHtml(conversation.last_message_body || 'Aucun message pour le moment.')}
      </p>
      <div class="support-ticket-meta mt-3">
        <span>
          <i class="fas fa-layer-group" aria-hidden="true"></i>
          ${escapeHtml(categoryLabel)}
        </span>
        <span>
          <i class="far fa-clock" aria-hidden="true"></i>
          Mis à jour : ${escapeHtml(updatedLabel)}
        </span>
      </div>
      <div class="support-ticket-footer mt-3">
        <button type="button" class="support-ticket-action" data-ticket-action>
          ${escapeHtml(actionLabel)}
          <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </button>
      </div>
    `;

    card.addEventListener('click', (event) => {
      if (event.target instanceof Element && event.target.closest('button, a')) {
        return;
      }
      selectConversation(Number.parseInt(String(conversation.id || '0'), 10) || 0, true);
    });

    const actionButton = card.querySelector('[data-ticket-action]');
    if (actionButton instanceof HTMLButtonElement) {
      actionButton.addEventListener('click', () => {
        selectConversation(Number.parseInt(String(conversation.id || '0'), 10) || 0, true);
      });
    }

    return card;
  }

  function renderTickets() {
    if (!(listEl instanceof HTMLElement) || !(emptyListEl instanceof HTMLElement)) {
      return;
    }

    listEl.innerHTML = '';
    const visibleConversations = getVisibleConversations();

    if (!visibleConversations.length) {
      emptyListEl.classList.remove('hidden');
      emptyListEl.textContent =
        allConversations.length === 0
          ? 'Aucun ticket pour le moment.'
          : ticketFilter === 'closed'
          ? 'Aucun ticket résolu.'
          : ticketFilter === 'open'
          ? 'Aucun ticket ouvert.'
          : 'Aucun ticket pour le moment.';
      return;
    }

    emptyListEl.classList.add('hidden');
    visibleConversations.forEach((conversation) => {
      listEl.appendChild(buildTicketCard(conversation));
    });
  }

  function getConversationById(conversationId) {
    return allConversations.find((conversation) => Number(conversation.id || 0) === Number(conversationId || 0)) || null;
  }

  function renderChatPlaceholder() {
    if (!(chatMessagesEl instanceof HTMLElement)) {
      return;
    }

    chatMessagesEl.innerHTML = `
      <div class="flex justify-start">
        <div class="max-w-[80%] px-4 py-2 text-sm chat-bubble-received">
          Sélectionnez un ticket ou envoyez un premier message pour ouvrir une nouvelle conversation.
        </div>
      </div>
    `;
  }

  function syncChatState() {
    const activeConversation = getConversationById(activeId);

    if (convInput instanceof HTMLInputElement) {
      convInput.value = activeConversation ? String(activeConversation.id) : '';
    }

    if (chatMetaEl instanceof HTMLElement) {
      if (!activeConversation) {
        chatMetaEl.textContent = 'Sélectionnez un ticket ou démarrez un nouveau message.';
      } else {
        const meta = getTicketMeta(activeConversation);
        chatMetaEl.textContent = `${displaySubject(activeConversation)} · ${meta.statusLabel}`;
      }
    }

    if (chatInput instanceof HTMLInputElement) {
      chatInput.placeholder = activeConversation
        ? 'Répondre à ce ticket...'
        : 'Écrivez votre message...';
    }

    if (chatSendBtn instanceof HTMLButtonElement) {
      chatSendBtn.disabled = false;
      chatSendBtn.classList.remove('opacity-60', 'cursor-not-allowed');
    }
  }

  function openWidget(forceOpen) {
    if (!(chatWidget instanceof HTMLElement)) {
      return;
    }

    const shouldOpen =
      typeof forceOpen === 'boolean' ? forceOpen : chatWidget.classList.contains('hidden');
    chatWidget.classList.toggle('hidden', !shouldOpen);

    if (!shouldOpen) {
      return;
    }

    if (!activeId) {
      const visibleConversations = getVisibleConversations();
      const preferred =
        visibleConversations.find((conversation) => normalizeStatus(conversation.status) !== 'closed') ||
        visibleConversations[0] ||
        null;

      if (preferred) {
        selectConversation(Number(preferred.id || 0), false);
      } else {
        syncChatState();
        renderChatPlaceholder();
      }
    }

    if (chatInput instanceof HTMLInputElement) {
      chatInput.focus();
    }
  }

  function renderMessage(message) {
    const wrapper = document.createElement('div');
    const isSelf = Number(message.sender_id || 0) === currentUserId;
    wrapper.className = `flex ${isSelf ? 'justify-end' : 'justify-start'}`;

    const bubble = document.createElement('div');
    bubble.className = `max-w-[85%] rounded-2xl px-4 py-2 text-sm ${isSelf ? 'chat-bubble-sent' : 'chat-bubble-received'}`;

    const text = document.createElement('div');
    text.className = 'whitespace-pre-line';
    text.textContent = String(message.body || '').trim() || '(message sans texte)';
    bubble.appendChild(text);

    if (Array.isArray(message.attachments) && message.attachments.length > 0) {
      const attachments = document.createElement('div');
      attachments.className = 'mt-2 flex flex-wrap gap-2';

      message.attachments.forEach((attachment) => {
        const link = document.createElement('a');
        link.href = attachmentUrl(attachment);
        if (isImageAttachment(attachment)) {
          link.className = 'support-chat-image-link';
          link.title = String(attachment.name || 'capture');
          const image = document.createElement('img');
          image.src = attachmentUrl(attachment, true);
          image.alt = String(attachment.name || 'Capture jointe');
          image.loading = 'lazy';
          image.className = 'support-chat-image-thumb';
          link.appendChild(image);
        } else {
          link.className =
            'rounded-full border border-gray-200 bg-white/70 px-2 py-1 text-[11px] text-gray-700 transition hover:border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200';
          link.textContent = String(attachment.name || 'fichier');
        }
        attachments.appendChild(link);
      });

      bubble.appendChild(attachments);
    }

    const meta = document.createElement('p');
    meta.className = `mt-1 text-[11px] ${isSelf ? 'text-right text-gray-400' : 'text-gray-400'}`;
    meta.textContent = `${String(message.sender_name || 'Support')} · ${formatMessageTime(message.created_at || '')}`;

    const stack = document.createElement('div');
    stack.className = 'max-w-[85%]';
    stack.appendChild(bubble);
    stack.appendChild(meta);

    wrapper.appendChild(stack);
    return wrapper;
  }

  async function fetchJson(url, options) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      ...options,
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.ok === false) {
      const error =
        payload.message ||
        payload.error ||
        (response.status >= 500 ? 'Erreur serveur.' : 'Requête impossible.');
      throw new Error(String(error));
    }

    return payload;
  }

  async function loadConversations(keepSelection = true) {
    if (loadingConversations) {
      return;
    }

    loadingConversations = true;
    try {
      const params = new URLSearchParams();
      params.set('scope', 'mine');
      params.set('limit', '200');

      const payload = await fetchJson(`${basePath}/api/support_conversations?${params.toString()}`);
      allConversations = Array.isArray(payload.conversations) ? payload.conversations : [];
      lastConversationRefreshAt = Date.now();
      renderKpis();
      renderTickets();

      const activeConversation = keepSelection ? getConversationById(activeId) : null;
      if (activeConversation) {
        syncChatState();
      } else if (keepSelection) {
        activeId = null;
        lastMessageId = 0;
        syncChatState();
        renderChatPlaceholder();
      }

      if (!hasAppliedInitialConversation && initialConv > 0) {
        hasAppliedInitialConversation = true;
        if (getConversationById(initialConv)) {
          selectConversation(initialConv, true);
          return;
        }
      }

      if (!keepSelection && activeId !== null && !getConversationById(activeId)) {
        activeId = null;
        lastMessageId = 0;
        syncChatState();
        renderChatPlaceholder();
      }
    } finally {
      loadingConversations = false;
    }
  }

  async function loadMessages(reset = true) {
    if (!(chatMessagesEl instanceof HTMLElement) || !activeId || loadingMessages) {
      return;
    }

    loadingMessages = true;
    try {
      const params = new URLSearchParams();
      params.set('conversation_id', String(activeId));
      if (!reset && lastMessageId > 0) {
        params.set('since_id', String(lastMessageId));
      }

      const payload = await fetchJson(`${basePath}/api/support_messages?${params.toString()}`);
      const messages = Array.isArray(payload.messages) ? payload.messages : [];

      if (reset) {
        chatMessagesEl.innerHTML = '';
        lastMessageId = 0;
      }

      messages.forEach((message) => {
        chatMessagesEl.appendChild(renderMessage(message));
        lastMessageId = Math.max(lastMessageId, Number(message.id || 0));
      });

      if (!chatMessagesEl.children.length) {
        renderChatPlaceholder();
      } else {
        chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
      }

      const activeConversation = getConversationById(activeId);
      if (activeConversation) {
        activeConversation.unread_count = 0;
        renderTickets();
      }
    } finally {
      loadingMessages = false;
    }
  }

  function selectConversation(conversationId, shouldOpenWidget = true) {
    const numericId = Number.parseInt(String(conversationId || '0'), 10) || 0;
    const conversation = getConversationById(numericId);
    if (!conversation) {
      return;
    }

    activeId = numericId;
    lastMessageId = 0;
    syncChatState();
    renderTickets();

    if (shouldOpenWidget) {
      openWidget(true);
    }

    loadMessages(true).catch((error) => {
      setInlineStatus(chatStatusEl, error instanceof Error ? error.message : 'Impossible de charger la conversation.', 'error');
    });
  }

  async function submitConversation(event) {
    event.preventDefault();
    if (!(newForm instanceof HTMLFormElement)) {
      return;
    }

    setInlineStatus(newStatusEl, '', '');

    const submitButton = newForm.querySelector('button[type="submit"]');
    if (submitButton instanceof HTMLButtonElement) {
      submitButton.disabled = true;
      submitButton.classList.add('opacity-80');
    }

    try {
      const formData = new FormData(newForm);
      if (!formData.get('_csrf') && csrfToken) {
        formData.append('_csrf', csrfToken);
      }

      const payload = await fetchJson(newForm.action, {
        method: 'POST',
        body: formData,
      });

      newForm.reset();
      updateFileHint(
        newAttachmentsInput,
        newFilesHintEl,
        'Capture d’écran ou PDF, 5 Mo maximum par fichier.'
      );
      setInlineStatus(newStatusEl, 'Ticket envoyé.', 'success');
      await loadConversations(false);

      const createdConversationId = Number.parseInt(String(payload.conversation_id || '0'), 10) || 0;
      if (createdConversationId > 0) {
        selectConversation(createdConversationId, true);
      }
    } catch (error) {
      setInlineStatus(
        newStatusEl,
        error instanceof Error ? error.message : 'Impossible d\'envoyer le ticket.',
        'error'
      );
    } finally {
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-80');
      }
    }
  }

  async function submitChatMessage(event) {
    event.preventDefault();
    if (!(chatInput instanceof HTMLInputElement) || !(chatForm instanceof HTMLFormElement)) {
      return;
    }

    const bodyText = chatInput.value.trim();
    const messageHasFiles = hasFiles(chatAttachmentsInput);
    if (!bodyText && !messageHasFiles) {
      setInlineStatus(chatStatusEl, 'Écrivez un message ou joignez une capture avant envoi.', 'error');
      return;
    }

    setInlineStatus(chatStatusEl, '', '');
    if (chatSendBtn instanceof HTMLButtonElement) {
      chatSendBtn.disabled = true;
      chatSendBtn.classList.add('opacity-60', 'cursor-not-allowed');
    }

    try {
      if (!activeId) {
        const creationPayload = new FormData(chatForm);
        if (csrfToken) {
          creationPayload.set('_csrf', csrfToken);
        }
        creationPayload.delete('conversation_id');
        creationPayload.set('subject', subjectFromBody(bodyText));
        creationPayload.set('body', bodyText);
        creationPayload.set('category', 'course');

        const payload = await fetchJson(`${basePath}/api/support_conversations`, {
          method: 'POST',
          body: creationPayload,
        });

        chatInput.value = '';
        if (chatAttachmentsInput instanceof HTMLInputElement) {
          chatAttachmentsInput.value = '';
        }
        updateFileHint(chatAttachmentsInput, chatFilesHintEl, '', true);
        await loadConversations(false);

        const createdConversationId = Number.parseInt(String(payload.conversation_id || '0'), 10) || 0;
        if (createdConversationId > 0) {
          selectConversation(createdConversationId, true);
        }

        setInlineStatus(chatStatusEl, 'Message envoyé.', 'success');
        return;
      }

      const formData = new FormData(chatForm);
      if (!formData.get('_csrf') && csrfToken) {
        formData.append('_csrf', csrfToken);
      }
      formData.set('conversation_id', String(activeId));
      formData.set('body', bodyText);

      await fetchJson(`${basePath}/api/support_messages`, {
        method: 'POST',
        body: formData,
      });

      chatInput.value = '';
      if (chatAttachmentsInput instanceof HTMLInputElement) {
        chatAttachmentsInput.value = '';
      }
      updateFileHint(chatAttachmentsInput, chatFilesHintEl, '', true);
      await loadMessages(false);
      await loadConversations(true);
      setInlineStatus(chatStatusEl, 'Message envoyé.', 'success');
    } catch (error) {
      setInlineStatus(
        chatStatusEl,
        error instanceof Error ? error.message : 'Impossible d\'envoyer le message.',
        'error'
      );
    } finally {
      if (chatSendBtn instanceof HTMLButtonElement) {
        chatSendBtn.disabled = false;
        chatSendBtn.classList.remove('opacity-60', 'cursor-not-allowed');
      }
    }
  }

  function startPolling() {
    if (pollingHandle) {
      return;
    }

    pollingHandle = window.setInterval(() => {
      if (document.hidden) {
        return;
      }

      if (activeId && !loadingMessages) {
        loadMessages(false).catch(() => {});
      }

      if (!loadingConversations && Date.now() - lastConversationRefreshAt >= 8000) {
        loadConversations(true).catch(() => {});
      }
    }, 2500);
  }

  filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      setTicketFilter(button.dataset.ticketFilter || 'all');
    });
  });

  if (newAttachmentsInput instanceof HTMLInputElement) {
    newAttachmentsInput.addEventListener('change', () => {
      updateFileHint(
        newAttachmentsInput,
        newFilesHintEl,
        'Capture d’écran ou PDF, 5 Mo maximum par fichier.'
      );
    });
  }

  if (newForm instanceof HTMLFormElement) {
    newForm.addEventListener('submit', submitConversation);
  }

  if (chatForm instanceof HTMLFormElement) {
    chatForm.addEventListener('submit', submitChatMessage);
  }

  if (chatToggleBtn instanceof HTMLButtonElement) {
    chatToggleBtn.addEventListener('click', () => openWidget());
  }

  if (chatCloseBtn instanceof HTMLButtonElement) {
    chatCloseBtn.addEventListener('click', () => openWidget(false));
  }

  if (chatMinimizeBtn instanceof HTMLButtonElement) {
    chatMinimizeBtn.addEventListener('click', () => openWidget(false));
  }

  if (chatInput instanceof HTMLInputElement) {
    chatInput.addEventListener('input', () => {
      setInlineStatus(chatStatusEl, '', '');
    });
  }

  if (chatAttachmentsInput instanceof HTMLInputElement) {
    chatAttachmentsInput.addEventListener('change', () => {
      setInlineStatus(chatStatusEl, '', '');
      updateFileHint(chatAttachmentsInput, chatFilesHintEl, '', true);
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      openWidget(false);
    }
  });

  setTicketFilter('all');
  syncChatState();
  renderChatPlaceholder();
  updateFileHint(
    newAttachmentsInput,
    newFilesHintEl,
    'Capture d’écran ou PDF, 5 Mo maximum par fichier.'
  );
  updateFileHint(chatAttachmentsInput, chatFilesHintEl, '', true);

  loadConversations(true)
    .catch((error) => {
      setInlineStatus(
        newStatusEl,
        error instanceof Error ? error.message : 'Impossible de charger les tickets.',
        'error'
      );
      setInlineStatus(
        chatStatusEl,
        error instanceof Error ? error.message : 'Impossible de charger les tickets.',
        'error'
      );
    })
    .finally(() => {
      startPolling();
    });
})();
