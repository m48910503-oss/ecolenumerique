// assets/js/support_chat.js
// Chat support (utilisateur + console staff).
(function () {
  const root = document.querySelector('[data-support-chat]');
  if (!root) return;

  const basePath = root.dataset.basePath || '';
  const mode = root.dataset.supportMode || 'user';
  const currentUserId = parseInt(root.dataset.userId || '0', 10);
  const initialConv = parseInt(root.dataset.initialConv || '0', 10);
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || '';

  const listEl = document.getElementById('support-conv-list');
  const messagesEl = document.getElementById('support-messages');
  const emptyEl = document.getElementById('support-empty');
  const statusEl = document.getElementById('support-status');
  const activeTitle = document.getElementById('support-active-title');
  const activeMeta = document.getElementById('support-active-meta');
  const convInput = document.getElementById('support-conv-id');
  const messageForm = document.getElementById('support-message-form');
  const newForm = document.getElementById('support-new-form');
  const statusFilter = document.getElementById('support-status-filter');
  const assignedFilter = document.getElementById('support-assigned-filter');
  const assignBtn = document.getElementById('support-assign-btn');
  const closeBtn = document.getElementById('support-close-btn');
  const reopenBtn = document.getElementById('support-reopen-btn');
  const newChatBtn = document.getElementById('support-new-chat-btn');
  const resetFiltersBtn = document.getElementById('support-reset-filters');
  const heroNewChatBtn = document.getElementById('support-hero-new-chat');
  const searchInput = document.getElementById('support-search');
  const activeAvatar = document.getElementById('support-active-avatar');
  const activeStatus = document.getElementById('support-active-status');
  const replyInput = document.getElementById('support-reply');
  const replyFiles = document.getElementById('support-reply-files');
  const filesHintEl = document.getElementById('support-files-hint');
  const emptyCtaBtn = document.getElementById('support-empty-cta');
  const detailBadge = document.getElementById('support-detail-badge');
  const detailTitle = document.getElementById('support-detail-title');
  const detailPreview = document.getElementById('support-detail-preview');
  const detailOwner = document.getElementById('support-detail-owner');
  const detailUpdated = document.getElementById('support-detail-updated');
  const paginationEl = document.getElementById('support-conv-pagination');
  const mobileBackLink = document.getElementById('support-mobile-back');
  const tabButtons = Array.from(
    document.querySelectorAll('[data-support-tab]')
  );
  const supportMobileQuery = '(max-width: 767.98px)';
  const conversationPageSize = 8;

  let activeId = null;
  let lastMessageId = 0;
  let pollTimer = null;
  let allConversations = [];
  let userTab = 'open';
  let conversationPage = 1;
  let loadingConversations = false;
  let loadingMessages = false;
  let lastConversationSyncAt = 0;
  let mobileSupportMode = 'desktop';

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function isAdminSupportPage() {
    return document.body && document.body.dataset.view === 'admin/support';
  }

  function isMobileViewport() {
    return typeof window.matchMedia === 'function'
      && window.matchMedia(supportMobileQuery).matches;
  }

  function getMobileSupportMode() {
    if (!isAdminSupportPage() || !isMobileViewport()) {
      return 'desktop';
    }

    return initialConv > 0 ? 'thread' : 'history';
  }

  function applyMobileSupportMode() {
    if (!isAdminSupportPage()) {
      return;
    }

    mobileSupportMode = getMobileSupportMode();
    document.body.classList.toggle('support-mobile-history-view', mobileSupportMode === 'history');
    document.body.classList.toggle('support-mobile-thread-view', mobileSupportMode === 'thread');
    document.body.dataset.supportMobileMode = mobileSupportMode;

    if (mobileBackLink instanceof HTMLAnchorElement) {
      mobileBackLink.href = buildHistoryUrl();
    }
  }

  function isMobileHistoryView() {
    return mobileSupportMode === 'history';
  }

  function buildConversationUrl(conversationId) {
    const numericId = Number.parseInt(String(conversationId || '0'), 10) || 0;
    if (numericId <= 0) {
      return window.location.href;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('conv', String(numericId));
    return url.toString();
  }

  function buildHistoryUrl() {
    const url = new URL(window.location.href);
    url.searchParams.delete('conv');
    return url.toString();
  }

  function openConversationPage(conversationId) {
    window.location.assign(buildConversationUrl(conversationId));
  }

  function setStatus(message, kind) {
    if (!statusEl) return;
    statusEl.textContent = message || '';
    statusEl.className =
      'support-inline-status ' +
      (kind === 'error'
        ? 'support-inline-status--error'
        : kind === 'success'
        ? 'support-inline-status--success'
        : 'support-inline-status--idle');
  }

  function clearStatus() {
    setStatus('', '');
  }

  function formatSize(size) {
    const kb = size / 1024;
    if (kb < 1024) return `${Math.round(kb)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
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

  let imagePreviewModal = null;

  function closeImagePreview() {
    if (!imagePreviewModal) return;
    imagePreviewModal.overlay.classList.add('hidden');
    imagePreviewModal.overlay.classList.remove('flex');
    imagePreviewModal.title.textContent = 'Aperçu de l’image';
    imagePreviewModal.image.src = '';
    imagePreviewModal.image.alt = '';
    document.body.classList.remove('overflow-hidden');
  }

  function ensureImagePreviewModal() {
    if (imagePreviewModal) {
      return imagePreviewModal;
    }

    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    const panel = document.createElement('div');
    panel.className = 'flex w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-slate-950 shadow-2xl ring-1 ring-white/10';
    panel.style.maxHeight = '92vh';

    const header = document.createElement('div');
    header.className = 'flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3 text-white';

    const title = document.createElement('p');
    title.className = 'min-w-0 truncate text-sm font-semibold';
    title.textContent = 'Aperçu de l’image';

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20';
    closeButton.setAttribute('aria-label', 'Fermer l’aperçu');
    closeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
    closeButton.addEventListener('click', closeImagePreview);

    header.appendChild(title);
    header.appendChild(closeButton);

    const body = document.createElement('div');
    body.className = 'flex min-h-0 flex-1 items-center justify-center bg-black/35 p-4';

    const image = document.createElement('img');
    image.className = 'max-w-full rounded-2xl object-contain shadow-2xl';
    image.style.maxHeight = '78vh';
    image.style.maxWidth = '100%';
    body.appendChild(image);

    panel.appendChild(header);
    panel.appendChild(body);
    overlay.appendChild(panel);
    document.body.appendChild(overlay);

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        closeImagePreview();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && imagePreviewModal && !imagePreviewModal.overlay.classList.contains('hidden')) {
        closeImagePreview();
      }
    });

    imagePreviewModal = { overlay, title, image };
    return imagePreviewModal;
  }

  function openImagePreview(src, titleText) {
    const modal = ensureImagePreviewModal();
    modal.title.textContent = titleText || 'Aperçu de l’image';
    modal.image.alt = titleText || 'Aperçu de l’image';
    modal.image.src = src;
    modal.overlay.classList.remove('hidden');
    modal.overlay.classList.add('flex');
    document.body.classList.add('overflow-hidden');
  }

  function parseDateValue(raw) {
    const value = String(raw || '').trim();
    if (!value) return null;
    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    const parsed = new Date(normalized);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function formatConversationTime(raw) {
    const parsed = parseDateValue(raw);
    if (!parsed) return String(raw || '');

    const now = new Date();
    const sameDay = parsed.toDateString() === now.toDateString();
    if (sameDay) {
      return new Intl.DateTimeFormat('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
      }).format(parsed);
    }

    const diffDays = Math.abs(now.getTime() - parsed.getTime()) / 86400000;
    if (diffDays < 7) {
      return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'short',
      }).format(parsed);
    }

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
    }).format(parsed);
  }

  function formatDetailedTime(raw) {
    const parsed = parseDateValue(raw);
    if (!parsed) return String(raw || '');

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    }).format(parsed);
  }

  function buildSubject(bodyText) {
    const cleaned = String(bodyText || '')
      .replace(/\s+/g, ' ')
      .trim();
    if (cleaned) return cleaned.slice(0, 80);
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `Support ${y}-${m}-${d}`;
  }

  function updateFilesHint() {
    if (!filesHintEl) return;
    const files =
      replyFiles && replyFiles.files ? Array.from(replyFiles.files) : [];
    if (!files.length) {
      filesHintEl.textContent = 'Pièces jointes : max. 5 Mo.';
      return;
    }
    const firstName = String(files[0]?.name || 'fichier');
    if (files.length === 1) {
      filesHintEl.textContent = `1 fichier: ${firstName}`;
      return;
    }
    filesHintEl.textContent = `${files.length} fichiers: ${firstName} +${
      files.length - 1
    }`;
  }

  function clearMessageComposer() {
    if (replyInput) replyInput.value = '';
    if (replyFiles) replyFiles.value = '';
    updateFilesHint();
    resizeReplyInput();
  }

  function resizeReplyInput() {
    if (!replyInput) return;
    replyInput.style.height = 'auto';
    const nextHeight = Math.max(replyInput.scrollHeight, 52);
    replyInput.style.height = `${Math.min(nextHeight, 192)}px`;
  }

  function getInitials(name) {
    const clean = String(name || '')
      .replace(/[^A-Za-z0-9 ]+/g, ' ')
      .trim();
    if (!clean) return 'SP';
    const parts = clean.split(/\s+/);
    const first = parts[0] ? parts[0][0] : '';
    const second = parts.length > 1 ? parts[1][0] : '';
    return (first + second).toUpperCase().slice(0, 2);
  }

  function statusLabel(status) {
    if (status === 'waiting_support') return 'En attente support';
    if (status === 'waiting_user') {
      return mode === 'staff'
        ? 'En attente utilisateur'
        : 'Votre réponse est attendue';
    }
    if (status === 'closed') return 'Terminé';
    return 'Ouvert';
  }

  function badgeClass(status) {
    if (status === 'waiting_support') return 'support-status-pill--waiting-support';
    if (status === 'waiting_user') return 'support-status-pill--waiting-user';
    if (status === 'closed') return 'support-status-pill--closed';
    return 'support-status-pill--open';
  }

  function applyStatusPill(element, status, fallbackLabel) {
    if (!(element instanceof HTMLElement)) {
      return;
    }

    const normalizedStatus = String(status || '').trim();
    element.className =
      'support-status-pill ' +
      (normalizedStatus !== '' ? badgeClass(normalizedStatus) : 'support-status-pill--neutral');
    element.textContent =
      normalizedStatus !== ''
        ? statusLabel(normalizedStatus)
        : fallbackLabel || 'Sans statut';
  }

  function renderActiveStatus(conv) {
    if (!(activeStatus instanceof HTMLElement)) {
      return;
    }

    activeStatus.innerHTML = '';
    if (!conv) {
      return;
    }

    const status = document.createElement('span');
    applyStatusPill(status, conv.status || '', 'Brouillon');
    activeStatus.appendChild(status);

    const updated = document.createElement('span');
    updated.className = 'support-thread-meta-pill';
    updated.textContent = conv.last_message_at
      ? `Activité : ${formatDetailedTime(conv.last_message_at)}`
      : 'Prêt à envoyer';
    activeStatus.appendChild(updated);

    const owner = document.createElement('span');
    owner.className = 'support-thread-meta-pill support-thread-meta-pill--soft';
    owner.textContent =
      mode === 'staff'
        ? `Utilisateur : ${conv.created_by_name || 'Utilisateur'}`
        : 'Échange avec le support';
    activeStatus.appendChild(owner);
  }

  function syncConversationDetails(conv) {
    applyStatusPill(detailBadge, conv?.status || '', 'Prêt à démarrer');

    if (detailTitle) {
      detailTitle.textContent = conv?.subject || 'Aucune conversation active';
    }

    if (detailPreview) {
      detailPreview.textContent =
        conv?.last_message_body ||
        'Sélectionnez un fil pour afficher son résumé ici, ou utilisez le compositeur central pour ouvrir une nouvelle demande.';
    }

    if (detailOwner) {
      detailOwner.textContent =
        mode === 'staff'
          ? conv?.created_by_name || 'Utilisateur'
          : 'Support';
    }

    if (detailUpdated) {
      detailUpdated.textContent = conv?.last_message_at
        ? formatDetailedTime(conv.last_message_at)
        : 'Aucune';
    }
  }

  function resetActive() {
    activeId = null;
    lastMessageId = 0;
    if (convInput) convInput.value = '';
    if (messagesEl) {
      messagesEl.innerHTML = '';
      messagesEl.classList.add('hidden');
    }
    if (messageForm) {
      if (mode === 'user' && newForm) {
        messageForm.classList.add('hidden');
      } else if (mode === 'user') {
        messageForm.classList.remove('hidden');
      } else {
        messageForm.classList.add('hidden');
      }
    }
    if (activeTitle)
      activeTitle.textContent = mode === 'user' ? 'Nouveau message' : 'Conversation support';
    if (activeMeta)
      activeMeta.textContent =
        mode === 'user'
          ? 'Écrivez un message pour démarrer.'
          : 'Sélectionnez une conversation.';
    if (activeMeta) {
      activeMeta.textContent =
        mode === 'user'
          ? 'Écrivez votre premier message pour démarrer une nouvelle demande.'
          : 'Sélectionnez une conversation.';
    }
    renderActiveStatus(null);
    if (activeAvatar) activeAvatar.textContent = 'SP';
    if (emptyEl) emptyEl.classList.remove('hidden');
    syncConversationDetails(null);
    clearMessageComposer();
  }

  function syncUserTabs() {
    if (!tabButtons.length) return;
    tabButtons.forEach((btn) => {
      const value = String(btn.dataset.supportTab || 'open');
      const isActive = value === userTab;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
  }

  function setUserTab(nextTab, shouldReload) {
    userTab = nextTab === 'closed' ? 'closed' : 'open';
    if (statusFilter) {
      statusFilter.value = userTab === 'closed' ? 'closed' : '';
    }
    syncUserTabs();
    if (shouldReload) {
      loadConversations(false);
    }
  }

  function applyUserTabFilter(conversations) {
    if (mode !== 'user') {
      return conversations;
    }
    if (userTab === 'closed') {
      return conversations.filter(
        (conv) => String(conv.status || '') === 'closed'
      );
    }
    return conversations.filter((conv) => String(conv.status || '') !== 'closed');
  }

  function updateStaffStats(conversations) {
    if (mode !== 'staff') return;
    const list = Array.isArray(conversations) ? conversations : [];
    const counters = {
      open: 0,
      waiting_support: 0,
      unassigned: 0,
      closed: 0,
    };

    list.forEach((conv) => {
      const status = String(conv?.status || '');
      if (status === 'closed') {
        counters.closed += 1;
      } else {
        counters.open += 1;
      }
      if (status === 'waiting_support') {
        counters.waiting_support += 1;
      }
      if (status !== 'closed' && !conv?.assigned_to) {
        counters.unassigned += 1;
      }
    });

    Object.entries(counters).forEach(([key, value]) => {
      document
        .querySelectorAll(`[data-support-stat-value="${key}"]`)
        .forEach((node) => {
          node.textContent = String(value);
        });
    });

    document.querySelectorAll('[data-support-stat-label="total"]').forEach((node) => {
      node.textContent = `${list.length} ticket${list.length > 1 ? 's' : ''}`;
    });
  }

  function filterConversations() {
    const scopedConversations = applyUserTabFilter(allConversations);
    if (!searchInput) return scopedConversations;
    const q = (searchInput.value || '').trim().toLowerCase();
    if (!q) return scopedConversations;
    return scopedConversations.filter((conv) => {
      const subject = String(conv.subject || '').toLowerCase();
      const creator = String(conv.created_by_name || '').toLowerCase();
      const preview = String(conv.last_message_body || '').toLowerCase();
      return (
        subject.includes(q) || creator.includes(q) || preview.includes(q)
      );
    });
  }

  function getVisibleConversations() {
    return filterConversations();
  }

  function renderConversationPagination(totalItems, totalPages, currentPage) {
    if (!(paginationEl instanceof HTMLElement)) {
      return;
    }

    if (totalPages <= 1) {
      paginationEl.innerHTML = '';
      paginationEl.classList.add('hidden');
      return;
    }

    const startItem = ((currentPage - 1) * conversationPageSize) + 1;
    const endItem = Math.min(totalItems, currentPage * conversationPageSize);
    const windowStart = Math.max(1, Math.min(currentPage - 2, totalPages - 4));
    const windowEnd = Math.min(totalPages, Math.max(5, currentPage + 2));
    const pageButtons = [];

    for (let page = windowStart; page <= windowEnd; page += 1) {
      const isActive = page === currentPage;
      pageButtons.push(
        '<button type="button" data-support-conv-page="' + page + '" class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl border px-3 text-sm font-semibold transition '
          + (isActive
            ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm dark:border-primary-400/50 dark:bg-primary-500/15 dark:text-primary-100'
            : 'border-gray-200 bg-white text-gray-600 hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-500/60 dark:hover:text-primary-200')
          + '"'
          + (isActive ? ' aria-current="page"' : '')
          + '>'
          + page
          + '</button>'
      );
    }

    paginationEl.innerHTML =
      '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">'
        + '<p class="text-sm text-gray-500 dark:text-gray-400">Affichage ' + startItem + ' à ' + endItem + ' sur ' + totalItems + ' ticket(s)</p>'
        + '<div class="flex flex-wrap items-center gap-2">'
          + '<button type="button" data-support-conv-page-action="prev" class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:border-primary-300 hover:text-primary-600 disabled:cursor-not-allowed disabled:opacity-45 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-500/60 dark:hover:text-primary-200"'
          + (currentPage <= 1 ? ' disabled' : '')
          + '>Précédent</button>'
          + pageButtons.join('')
          + '<button type="button" data-support-conv-page-action="next" class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:border-primary-300 hover:text-primary-600 disabled:cursor-not-allowed disabled:opacity-45 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-500/60 dark:hover:text-primary-200"'
          + (currentPage >= totalPages ? ' disabled' : '')
          + '>Suivant</button>'
        + '</div>'
      + '</div>';
    paginationEl.classList.remove('hidden');
  }

  function jumpToConversationPage(conversationId) {
    const numericId = Number.parseInt(String(conversationId || '0'), 10) || 0;
    if (numericId <= 0) {
      return;
    }

    const visibleConversations = getVisibleConversations();
    const visibleIndex = visibleConversations.findIndex((conversation) => Number(conversation.id || 0) === numericId);
    if (visibleIndex < 0) {
      return;
    }

    conversationPage = Math.max(1, Math.ceil((visibleIndex + 1) / conversationPageSize));
  }

  function renderConversationList(keepSelection, options) {
    const snapToPreferredConversation =
      !keepSelection && (!options || options.snapToPreferredConversation !== false);
    if (!listEl) return;
    listEl.innerHTML = '';
    if (!allConversations.length) {
      const empty = document.createElement('div');
      empty.className = 'support-list-empty';
      empty.textContent = 'Aucune conversation pour le moment.';
      listEl.appendChild(empty);
      resetActive();
      if (paginationEl instanceof HTMLElement) {
        paginationEl.innerHTML = '';
        paginationEl.classList.add('hidden');
      }
      return;
    }

    const list = getVisibleConversations();
    if (!list.length) {
      const empty = document.createElement('div');
      empty.className = 'support-list-empty';
      if (mode === 'user' && userTab === 'closed' && !searchInput?.value) {
        empty.textContent = 'Aucune conversation terminée.';
      } else if (mode === 'user' && !searchInput?.value) {
        empty.textContent = 'Aucune conversation ouverte.';
      } else {
        empty.textContent = 'Aucun résultat.';
      }
      listEl.appendChild(empty);
      if (!keepSelection || isMobileHistoryView()) {
        resetActive();
      }
      if (paginationEl instanceof HTMLElement) {
        paginationEl.innerHTML = '';
        paginationEl.classList.add('hidden');
      }
      return;
    }

    const totalPages = Math.max(1, Math.ceil(list.length / conversationPageSize));
    conversationPage = clamp(conversationPage, 1, totalPages);

    const activeConversation = activeId
      ? list.find((c) => c.id === activeId)
      : null;
    const initialConversation = initialConv
      ? list.find((c) => c.id === initialConv)
      : null;
    const preferredConversation = activeConversation || initialConversation;
    if (snapToPreferredConversation && preferredConversation) {
      jumpToConversationPage(preferredConversation.id);
      conversationPage = clamp(conversationPage, 1, totalPages);
    }

    const startIndex = (conversationPage - 1) * conversationPageSize;
    const pageItems = list.slice(startIndex, startIndex + conversationPageSize);

    pageItems.forEach((conv) => listEl.appendChild(renderConversationItem(conv)));
    renderConversationPagination(list.length, totalPages, conversationPage);

    if (isMobileHistoryView()) {
      if (activeId !== null) {
        resetActive();
      }
      return;
    }

    const activeExists = Boolean(activeConversation);
    if (mode === 'user' && keepSelection && activeId === null) {
      return;
    }
    const shouldSelect = !keepSelection || !activeExists;
    if (shouldSelect && list.length) {
      const targetId =
        initialConv && list.some((c) => c.id === initialConv)
          ? initialConv
          : pageItems[0].id;
      selectConversation(targetId, list.find((c) => c.id === targetId) || pageItems[0]);
    }
  }

  async function fetchJson(url, opts) {
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      let error =
        data.error || data.message || 'Erreur lors du chargement.';
      if (error === 'db') {
        error = data.details
          ? `Erreur base de données: ${data.details}`
          : 'Erreur base de données. Vérifiez schema_v30.sql.';
      }
      if (error === 'db_read_only') {
        error =
          data.message
          || 'Base de données en lecture seule. Désactivez le mode recovery MySQL.';
      }
      throw new Error(error);
    }
    return data;
  }

  function renderConversationItem(conv) {
    const item = document.createElement('button');
    item.type = 'button';
    item.className =
      'support-conv-item w-full text-left flex items-start gap-3 px-3 py-3 rounded-2xl transition border ' +
      (conv.id === activeId ? 'is-active' : '');
    item.dataset.convId = conv.id;

    const displayName =
      mode === 'staff' ? conv.created_by_name || 'Utilisateur' : 'Support';
    const initials = getInitials(displayName);

    const avatar = document.createElement('div');
    avatar.className =
      'support-conv-avatar h-11 w-11 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-semibold';
    avatar.textContent = initials;

    const content = document.createElement('div');
    content.className = 'support-conv-content min-w-0 flex-1';

    const topRow = document.createElement('div');
    topRow.className = 'support-conv-top-row flex items-center justify-between gap-2';

    const title = document.createElement('div');
    title.className = 'support-conv-title text-sm font-semibold text-slate-800 truncate';
    title.textContent = conv.subject || 'Demande support';

    const time = document.createElement('div');
    time.className = 'support-conv-time text-[10px] text-slate-400';
    time.textContent = formatConversationTime(conv.last_message_at);

    topRow.appendChild(title);
    topRow.appendChild(time);

    const previewRow = document.createElement('div');
    previewRow.className = 'support-conv-preview-row mt-1 flex items-center justify-between gap-2';

    const preview = document.createElement('div');
    preview.className = 'support-conv-preview text-xs text-slate-500 truncate';
    preview.textContent = conv.last_message_body || '...';

    const unread =
      conv.unread_count && conv.unread_count > 0
        ? document.createElement('span')
        : null;
    if (unread) {
      unread.className =
        'support-unread-pill inline-flex items-center justify-center text-[10px] font-bold rounded-full h-5 min-w-5 px-2';
      unread.textContent = String(conv.unread_count);
    }

    previewRow.appendChild(preview);
    if (unread) {
      previewRow.appendChild(unread);
    }

    const statusRow = document.createElement('div');
    statusRow.className = 'support-conv-status-row mt-1 flex items-center gap-2 text-[10px]';

    const status = document.createElement('span');
    applyStatusPill(status, conv.status || '', 'Ouvert');

    const owner = document.createElement('span');
    owner.className = 'support-conv-owner text-slate-400';
    owner.textContent = displayName;

    statusRow.appendChild(status);
    statusRow.appendChild(owner);

    content.appendChild(topRow);
    content.appendChild(previewRow);
    content.appendChild(statusRow);

    item.appendChild(avatar);
    item.appendChild(content);

    item.addEventListener('click', () => {
      if (isMobileHistoryView()) {
        openConversationPage(conv.id);
        return;
      }

      selectConversation(conv.id, conv);
    });

    return item;
  }

  async function loadConversations(keepSelection, force) {
    if (!listEl) return;
    if (loadingConversations && !force) return;
    loadingConversations = true;
    const params = new URLSearchParams();
    if (mode === 'staff') {
      params.set('scope', 'all');
    } else {
      params.set('scope', 'mine');
    }
    if (statusFilter && statusFilter.value) {
      params.set('status', statusFilter.value);
    }
    if (assignedFilter && assignedFilter.value) {
      params.set('assigned', assignedFilter.value);
    }
    params.set('limit', '500');
    try {
      const data = await fetchJson(
        `${basePath}/api/support_conversations?${params.toString()}`
      );
      allConversations = data.conversations || [];
      updateStaffStats(allConversations);
      renderConversationList(keepSelection);
    } catch (e) {
      setStatus(e.message || 'Erreur chargement.', 'error');
    } finally {
      loadingConversations = false;
    }
  }

  function renderMessage(msg) {
    const wrapper = document.createElement('div');
    const isSelf = currentUserId > 0 && msg.sender_id === currentUserId;
    wrapper.className = `support-msg flex ${isSelf ? 'is-self justify-end' : 'is-peer justify-start'} gap-2`;

    if (!isSelf) {
      const avatar = document.createElement('div');
      avatar.className =
        'support-msg-avatar h-8 w-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-semibold';
      avatar.textContent = getInitials(msg.sender_name || 'US');
      wrapper.appendChild(avatar);
    }

    const stack = document.createElement('div');
    stack.className = 'support-msg-stack max-w-[78%]';

    const meta = document.createElement('div');
    meta.className =
      'support-msg-meta text-[10px] mb-1 ' +
      (isSelf ? 'text-right text-slate-400' : 'text-slate-400');
    const messageTime = formatDetailedTime(msg.created_at);
    meta.textContent = messageTime
      ? `${msg.sender_name || 'Utilisateur'} · ${messageTime}`
      : `${msg.sender_name || 'Utilisateur'}`;

    const bubble = document.createElement('div');
    bubble.className =
      'support-msg-bubble rounded-3xl px-4 py-3 text-sm shadow-sm ' +
      (isSelf
        ? 'is-self bg-sky-500 text-white rounded-br-md'
        : 'is-peer bg-white border border-slate-200 text-slate-900 rounded-bl-md');

    if (msg.body) {
      const body = document.createElement('div');
      body.className = 'whitespace-pre-line';
      body.textContent = msg.body || '';
      bubble.appendChild(body);
    }

    if (msg.attachments && msg.attachments.length) {
      const list = document.createElement('div');
      list.className = 'mt-2 flex flex-wrap gap-2';
      msg.attachments.forEach((att) => {
        const link = document.createElement('a');
        if (isImageAttachment(att)) {
          link.className = 'support-chat-image-link';
          const previewUrl = attachmentUrl(att, true);
          link.href = previewUrl;
          link.target = '_blank';
          link.rel = 'noopener noreferrer';
          link.title = `Aperçu: ${att.name || 'capture'}`;
          link.style.cursor = 'zoom-in';
          link.addEventListener('click', (event) => {
            event.preventDefault();
            openImagePreview(previewUrl, att.name || 'Capture jointe');
          });
          const image = document.createElement('img');
          image.src = previewUrl;
          image.alt = att.name || 'Capture jointe';
          image.loading = 'lazy';
          image.className = 'support-chat-image-thumb';
          link.appendChild(image);
        } else {
          link.href = attachmentUrl(att);
          link.className =
            'support-attachment-link text-xs px-2 py-1 rounded-full border border-slate-200 bg-slate-50 hover:border-slate-300';
          link.textContent = `${att.name || 'fichier'} - ${formatSize(
            att.size || 0
          )}`;
        }
        list.appendChild(link);
      });
      bubble.appendChild(list);
    }

    stack.appendChild(meta);
    stack.appendChild(bubble);

    wrapper.appendChild(stack);
    return wrapper;
  }

  async function loadMessages(reset, force) {
    if (!activeId || !messagesEl) return;
    if (loadingMessages && !force) return;
    loadingMessages = true;
    const params = new URLSearchParams();
    params.set('conversation_id', String(activeId));
    if (!reset && lastMessageId > 0) {
      params.set('since_id', String(lastMessageId));
    }
    try {
      const data = await fetchJson(
        `${basePath}/api/support_messages?${params.toString()}`
      );
      const messages = data.messages || [];
      if (reset) {
        messagesEl.innerHTML = '';
        lastMessageId = 0;
      }
      const emptyThreadHint = messagesEl.querySelector('.support-msg-empty');
      if (emptyThreadHint) {
        emptyThreadHint.remove();
      }
      messages.forEach((msg) => {
        messagesEl.appendChild(renderMessage(msg));
        lastMessageId = Math.max(lastMessageId, msg.id || 0);
      });
      if (!messagesEl.querySelector('.support-msg')) {
        const hint = document.createElement('div');
        hint.className =
          'support-msg-empty rounded-2xl border border-dashed px-4 py-4 text-sm';
        hint.textContent = 'Aucun message dans cette conversation pour le moment.';
        messagesEl.appendChild(hint);
      }
      if (messages.length || reset) {
        messagesEl.scrollTop = messagesEl.scrollHeight;
      }
      if (messages.length > 0) {
        loadConversations(true, true);
      }
      if (emptyEl) {
        emptyEl.classList.toggle('hidden', activeId !== null);
      }
    } catch (e) {
      setStatus(e.message || 'Erreur chargement messages.', 'error');
    } finally {
      loadingMessages = false;
    }
  }

  function selectConversation(id, conv) {
    activeId = id;
    lastMessageId = 0;
    jumpToConversationPage(id);

    if (isMobileHistoryView()) {
      openConversationPage(id);
      return;
    }

    if (convInput) convInput.value = String(id);
    const displayName =
      mode === 'staff' ? conv?.created_by_name || 'Utilisateur' : 'Support';
    if (activeTitle) {
      activeTitle.textContent = conv?.subject || 'Conversation support';
    }
    if (activeMeta) {
      activeMeta.textContent =
        mode === 'staff'
          ? `Utilisateur : ${displayName}`
          : 'Le support suit cette demande dans le même fil.';
    }
    if (activeAvatar) {
      activeAvatar.textContent = getInitials(displayName);
    }
    if (activeStatus) {
      const parts = [];
      if (conv?.status) {
        parts.push(`Statut : ${statusLabel(conv.status)}`);
      }
      if (mode === 'staff' && conv?.assigned_to_name) {
        parts.push(`Assigné : ${conv.assigned_to_name}`);
      }
      activeStatus.textContent = parts.join(' | ');
    }
    renderActiveStatus(conv);
    syncConversationDetails(conv);
    if (emptyEl) {
      emptyEl.classList.add('hidden');
    }
    if (messagesEl) {
      messagesEl.classList.remove('hidden');
    }
    if (messageForm) {
      messageForm.classList.remove('hidden');
    }
    loadMessages(true, true);
    startPolling();
  }

  function startPolling() {
    if (pollTimer) return;
    pollTimer = window.setInterval(() => {
      const now = Date.now();
      if (activeId) {
        loadMessages(false, false);
      }
      const shouldSyncConversations =
        mode === 'staff'
        || activeId !== null
        || (mode === 'user' && !replyInput?.value && (!replyFiles || !replyFiles.files || replyFiles.files.length === 0));
      if (shouldSyncConversations && now - lastConversationSyncAt >= 1800) {
        lastConversationSyncAt = now;
        loadConversations(true, false);
      }
    }, 1200);
  }

  function stopPolling() {
    if (pollTimer) {
      window.clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  async function submitFormData(fd, url) {
    if (csrfToken && !fd.get('_csrf')) {
      fd.append('_csrf', csrfToken);
    }
    const res = await fetch(url, { method: 'POST', body: fd });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      const extra =
        Array.isArray(data.errors) && data.errors.length
          ? data.errors[0]
          : '';
      const rawError = String(data.error || '');
      if (rawError === 'db_read_only') {
        throw new Error(
          data.message ||
            'Base de données en lecture seule (mode recovery MySQL).'
        );
      }
      if (rawError === 'db') {
        throw new Error(
          data.details
            ? `Erreur base de données : ${data.details}`
            : 'Erreur base de données.'
        );
      }
      throw new Error(
        extra ||
          data.message ||
          data.error ||
          "Erreur lors de l'envoi."
      );
    }
    return data;
  }

  async function submitForm(form, url) {
    const fd = new FormData(form);
    return submitFormData(fd, url);
  }

  async function sendMessage(e) {
    e.preventDefault();
    if (!messageForm) return;
    clearStatus();
    try {
      if (!activeId && mode === 'user') {
        const bodyText = replyInput ? replyInput.value.trim() : '';
        const hasFiles = replyFiles && replyFiles.files && replyFiles.files.length > 0;
        if (!bodyText && !hasFiles) {
          setStatus('Écrivez un message pour démarrer.', 'error');
          return;
        }
        const fd = new FormData(messageForm);
        fd.delete('conversation_id');
        fd.append('action', 'create');
        fd.append('subject', buildSubject(bodyText));
        const data = await submitFormData(
          fd,
          `${basePath}/api/support_conversations`
        );
        activeId = data.conversation_id || null;
        if (activeId && convInput) convInput.value = String(activeId);
        clearMessageComposer();
        if (activeId) {
          selectConversation(activeId, {
            subject: buildSubject(bodyText),
            status: 'open',
            last_message_body: bodyText,
          });
          await loadConversations(true, true);
        } else {
          await loadConversations(false, true);
        }
        setStatus('Demande envoyée.', 'success');
        return;
      }

      if (!activeId) {
        setStatus('Sélectionnez une conversation.', 'error');
        return;
      }

      const data = await submitFormData(
        new FormData(messageForm),
        `${basePath}/api/support_messages`
      );
      clearMessageComposer();
      if (convInput) convInput.value = String(activeId);
      await loadMessages(false, true);
      await loadConversations(true, true);
      setStatus('Message envoyé.', 'success');
    } catch (e2) {
      setStatus(e2.message || 'Erreur envoi.', 'error');
    }
  }

  async function createConversation(e) {
    e.preventDefault();
    if (!newForm) return;
    clearStatus();
    try {
      const data = await submitForm(
        newForm,
        `${basePath}/api/support_conversations`
      );
      newForm.reset();
      await loadConversations(false, true);
      setStatus('Demande envoyée.', 'success');
    } catch (e2) {
      setStatus(e2.message || 'Erreur envoi.', 'error');
    }
  }

  async function conversationAction(action) {
    if (!activeId) return;
    clearStatus();
    const fd = new FormData();
    fd.append('action', action);
    fd.append('conversation_id', String(activeId));
    if (csrfToken) fd.append('_csrf', csrfToken);
    try {
      await fetchJson(`${basePath}/api/support_conversations`, {
        method: 'POST',
        body: fd,
      });
      await loadConversations(true, true);
      setStatus('Mise à jour effectuée.', 'success');
    } catch (e) {
      setStatus(e.message || 'Erreur action.', 'error');
    }
  }

  function openFreshConversation() {
    if (mode === 'user' && tabButtons.length) {
      setUserTab('open', false);
    }
    clearStatus();
    resetActive();
    if (newForm) {
      newForm.reset();
      const firstField =
        newForm.querySelector('input[name="subject"], textarea[name="body"]') ||
        newForm.querySelector('input, textarea, select, button');
      if (firstField instanceof HTMLElement) {
        firstField.focus();
        firstField.scrollIntoView({ block: 'center', behavior: 'smooth' });
        return;
      }
    }
    if (replyInput) {
      replyInput.focus();
    }
  }

  if (messageForm) {
    messageForm.addEventListener('submit', sendMessage);
  }
  if (replyInput) {
    replyInput.addEventListener('input', resizeReplyInput);
    replyInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (messageForm && typeof messageForm.requestSubmit === 'function') {
          messageForm.requestSubmit();
        } else if (messageForm) {
          messageForm.dispatchEvent(new Event('submit', { cancelable: true }));
        }
      }
    });
  }
  if (newForm) {
    newForm.addEventListener('submit', createConversation);
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', () => loadConversations(false));
  }
  if (assignedFilter) {
    assignedFilter.addEventListener('change', () => loadConversations(false));
  }
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', () => {
      if (statusFilter) statusFilter.value = '';
      if (assignedFilter) assignedFilter.value = '';
      if (searchInput) searchInput.value = '';
      if (mode === 'user' && tabButtons.length) {
        setUserTab('open', false);
      }
      loadConversations(false);
    });
  }
  if (newChatBtn) {
    newChatBtn.addEventListener('click', openFreshConversation);
  }
  if (heroNewChatBtn) {
    heroNewChatBtn.addEventListener('click', (event) => {
      event.preventDefault();
      openFreshConversation();
    });
  }
  if (emptyCtaBtn) {
    emptyCtaBtn.addEventListener('click', () => {
      if (newChatBtn) {
        newChatBtn.click();
      } else {
        resetActive();
        if (replyInput) replyInput.focus();
      }
    });
  }
  if (tabButtons.length) {
    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const next = String(btn.dataset.supportTab || 'open');
        setUserTab(next, true);
      });
    });
    const activeTab = tabButtons.find((btn) => btn.classList.contains('is-active'));
    setUserTab(String(activeTab?.dataset.supportTab || 'open'), false);
  }
  if (replyFiles) {
    replyFiles.addEventListener('change', updateFilesHint);
  }
  window.addEventListener('app:notifications-live', (event) => {
    const items = Array.isArray(event?.detail?.items) ? event.detail.items : [];
    const hasSupportSignal = items.some((item) => {
      const url = String(item?.url || '').toLowerCase();
      const title = String(item?.title || '').toLowerCase();
      const body = String(item?.body || '').toLowerCase();
      return (
        url.includes('/support')
        || url.includes('/admin/support')
        || title.includes('support')
        || body.includes('support')
      );
    });
    if (!hasSupportSignal) {
      return;
    }
    if (activeId) {
      loadMessages(false, true);
    }
    loadConversations(true, true);
  });
  window.addEventListener('focus', () => {
    loadConversations(true, true);
    if (activeId) {
      loadMessages(false, true);
    }
  });
  updateFilesHint();
  resizeReplyInput();
  if (searchInput) {
    searchInput.addEventListener('input', () => renderConversationList(true));
  }
  if (assignBtn) {
    assignBtn.addEventListener('click', () => conversationAction('assign'));
  }
  if (closeBtn) {
    closeBtn.addEventListener('click', () => conversationAction('close'));
  }
  if (reopenBtn) {
    reopenBtn.addEventListener('click', () => conversationAction('reopen'));
  }

  if (paginationEl instanceof HTMLElement) {
    paginationEl.addEventListener('click', (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-support-conv-page], [data-support-conv-page-action]')
        : null;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const pageValue = Number.parseInt(String(target.dataset.supportConvPage || '0'), 10) || 0;
      if (pageValue > 0) {
        conversationPage = pageValue;
      renderConversationList(true, { snapToPreferredConversation: false });
        return;
      }

      const action = String(target.dataset.supportConvPageAction || '');
      if (action === 'prev' && conversationPage > 1) {
        conversationPage -= 1;
        renderConversationList(true, { snapToPreferredConversation: false });
      } else if (action === 'next') {
        conversationPage += 1;
        renderConversationList(true, { snapToPreferredConversation: false });
      }
    });
  }

  applyMobileSupportMode();

  loadConversations(false, true);
  startPolling();
})();
