(function () {
  const doc = document;
  const iframe = doc.getElementById('main-player');
  const wrapper = doc.getElementById('player-wrapper');
  const playOverlay = doc.getElementById('play-overlay');
  if (!iframe || !wrapper) {
    return;
  }

  const REQUIRED_RATIO = 0.85;
  const AUTO_MARK = true;
  const LOCK_MESSAGE =
    'Regardez au moins 85 % de la vidÃ©o avant de passer Ã  la suite ou de la marquer comme vue.';
  const PLAY_FALLBACK_DELAY = 1200;
  const YT_STATE_LABELS = {
    '-1': 'UNSTARTED',
    '0': 'ENDED',
    '1': 'PLAYING',
    '2': 'PAUSED',
    '3': 'BUFFERING',
    '5': 'CUED',
  };

  const state = {
    player: null,
    ready: false,
    eligible: false,
    autoMarked: false,
    watchSeconds: 0,
    lastWallTime: Date.now(),
    lastPlayerTime: 0,
    lastUiTime: 0,
    lastState: -1,
    uiFrame: 0,
    watchTimer: 0,
    pendingPlay: false,
    muted: false,
    playFallbackTimer: 0,
    userInitiatedPlay: false,
    pendingAudiblePlayback: false,
  };

  const $ = (selector) => doc.querySelector(selector);
  const $$ = (selector) => Array.from(doc.querySelectorAll(selector));

  const pageOrigin =
    (window.location && window.location.origin) || '';
  const hostname =
    (window.location && window.location.hostname) || '';
  const protocol =
    (window.location && window.location.protocol) || '';
  const debugEnabled =
    /\bdebug_player=1\b/.test(window.location.search) ||
    window.localStorage.getItem('coursePlayerDebug') === '1';
  const debugLogs = [];
  const debugPanel = debugEnabled ? createDebugPanel() : null;

  function isPrivateIp(host) {
    if (!host) return false;
    if (host === 'localhost' || host === '127.0.0.1' || host === '::1') {
      return true;
    }
    if (/^10\./.test(host)) return true;
    if (/^192\.168\./.test(host)) return true;
    if (/^172\.(1[6-9]|2\d|3[0-1])\./.test(host)) return true;
    return false;
  }

  const isLocalhost = isPrivateIp(hostname);

  function createDebugPanel() {
    const panel = doc.createElement('div');
    panel.id = 'course-player-debug';
    panel.style.cssText =
      'position:fixed;right:12px;bottom:12px;z-index:99999;' +
      'width:360px;max-width:92vw;height:200px;max-height:40vh;' +
      'background:rgba(15,23,42,0.9);color:#e2e8f0;font:11px/1.4 monospace;' +
      'border:1px solid rgba(148,163,184,0.4);border-radius:10px;' +
      'padding:8px;overflow:auto;box-shadow:0 18px 40px rgba(0,0,0,0.35);';
    const title = doc.createElement('div');
    title.textContent = 'Course Player Debug';
    title.style.cssText = 'font-weight:700;margin-bottom:6px;color:#f8fafc;';
    panel.appendChild(title);
    doc.body.appendChild(panel);
    return panel;
  }

  function log(level, message, data) {
    const entry = {
      ts: new Date().toISOString(),
      level,
      message,
      data: data || null,
    };
    debugLogs.push(entry);
    if (debugLogs.length > 200) debugLogs.shift();
    if (debugEnabled) {
      const tag = `[course_player][${level}]`;
      if (level === 'error') console.error(tag, message, data || '');
      else if (level === 'warn') console.warn(tag, message, data || '');
      else console.info(tag, message, data || '');
    }
    if (debugPanel) {
      const line = doc.createElement('div');
      line.textContent = `${entry.ts} ${level.toUpperCase()} ${message}`;
      debugPanel.appendChild(line);
      debugPanel.scrollTop = debugPanel.scrollHeight;
    }
  }

  window.__coursePlayerDebug = {
    logs: debugLogs,
    log,
    enable() {
      window.localStorage.setItem('coursePlayerDebug', '1');
    },
    disable() {
      window.localStorage.removeItem('coursePlayerDebug');
    },
  };

  log('info', 'Init course player', {
    origin: pageOrigin,
    hostname,
    protocol,
    isLocalhost,
    userAgent: navigator.userAgent,
    online: navigator.onLine,
    connection: navigator.connection
      ? {
          effectiveType: navigator.connection.effectiveType,
          downlink: navigator.connection.downlink,
          rtt: navigator.connection.rtt,
        }
      : null,
  });
  if (isLocalhost) {
    log('warn', 'Localhost detected', {
      hint:
        'Local env can block third-party cookies or mixed-content autoplay.',
    });
  }

  function probeConnectivity() {
    const cspMeta = doc.querySelector(
      'meta[http-equiv="Content-Security-Policy"]'
    );
    const cspValue = cspMeta ? cspMeta.getAttribute('content') || '' : '';
    if (!/connect-src/i.test(cspValue)) {
      log('warn', 'connect-src missing in CSP, skip probes', {
        hint:
          'Update CSP to include connect-src youtube domains or restart server cache.',
      });
      return;
    }
    const targets = [
      'https://www.youtube-nocookie.com/generate_204',
      'https://www.youtube.com/generate_204',
    ];
    targets.forEach((url) => {
      fetch(url, { mode: 'no-cors', cache: 'no-store' })
        .then(() => log('info', 'Probe OK', { url }))
        .catch((err) =>
          log('error', 'Probe failed', { url, error: String(err) })
        );
    });
  }

  function notify(message, type) {
    if (typeof window.appShowToast === 'function') {
      window.appShowToast(message, type || 'info');
      return;
    }
    if (typeof window.showToast === 'function') {
      window.showToast(message, type || 'info');
      return;
    }
    alert(message);
  }

  function formatTime(seconds) {
    const safe = Math.max(0, Math.floor(seconds || 0));
    const mins = Math.floor(safe / 60);
    const secs = safe % 60;
    return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
  }

  function setMarkButtonsEnabled(enabled) {
    ['#btn-mark', '[data-action="mark-done"]'].forEach((selector) => {
      const node = $(selector);
      if (!node) return;
      node.classList.toggle('opacity-60', !enabled);
      node.classList.toggle('cursor-not-allowed', !enabled);
      if (!enabled) node.setAttribute('title', LOCK_MESSAGE);
      else node.removeAttribute('title');
    });
  }

  function resetLessonTracking() {
    state.eligible = false;
    state.autoMarked = false;
    state.watchSeconds = 0;
    state.lastWallTime = Date.now();
    state.lastPlayerTime = 0;
    state.lastUiTime = 0;
    setMarkButtonsEnabled(false);
  }

  function currentItem() {
    return $('.queue-item.active') || $('.queue-item');
  }

  function renderQueueItemState(item, isActive) {
    if (!item) return;

    const done = item.dataset.done === '1';
    const chip = item.querySelector('[data-lesson-status-chip]');
    const icon = item.querySelector('[data-lesson-status-icon]');
    const title = item.querySelector('[data-lesson-title-node]');

    item.classList.toggle('active', !!isActive);
    item.classList.toggle('bg-primary-50/50', !!isActive);
    item.classList.toggle('dark:bg-primary-900/10', !!isActive);
    item.classList.toggle('border-l-4', !!isActive);
    item.classList.toggle('border-primary-600', !!isActive);
    item.classList.toggle('text-primary-700', !!isActive);
    item.classList.toggle('dark:text-primary-300', !!isActive);
    item.classList.toggle('text-gray-700', !isActive);
    item.classList.toggle('dark:text-gray-200', !isActive);
    item.classList.toggle('hover:bg-gray-50', !isActive);
    item.classList.toggle('dark:hover:bg-gray-700/50', !isActive);

    if (chip) {
      if (isActive) {
        chip.className =
          'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-primary-600 bg-white text-primary-600 dark:border-primary-400 dark:text-primary-300';
      } else if (done) {
        chip.className =
          'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-green-500 bg-green-500 text-white dark:border-green-500 dark:bg-green-500 dark:text-white';
      } else {
        chip.className =
          'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-400 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-400';
      }
    }

    if (icon) {
      icon.className = isActive
        ? 'fas fa-play text-[10px] text-primary-600 dark:text-primary-300'
        : done
          ? 'fas fa-check text-[10px] text-white'
          : 'far fa-circle text-[10px] text-gray-400';
    }

    if (title) {
      title.classList.toggle('font-medium', !!isActive);
      title.classList.toggle('text-primary-700', !!isActive);
      title.classList.toggle('dark:text-primary-300', !!isActive);
      title.classList.toggle('text-gray-900', !isActive);
      title.classList.toggle('dark:text-white', !isActive);
    }
  }

  function setItemActive(item) {
    $$('.queue-item').forEach((node) => renderQueueItemState(node, node === item));
  }

  function updateMeta(item) {
    if (!item) return;
    const meta = $('#current-lesson-meta');
    const title = $('#player-title');
    const btnMark = $('#btn-mark');
    const markLabel = btnMark ? btnMark.querySelector('[data-mark-label]') : null;
    const lessonTitle = item.dataset.lessonTitle || 'LeÃ§on';
    const chapterPos = item.dataset.chapterPos || '';
    const lessonPos = item.dataset.lessonPos || '';
    const parts = [];
    if (chapterPos) parts.push('Chapitre ' + chapterPos);
    if (lessonPos) parts.push('LeÃ§on ' + lessonPos);
    const label = (parts.length ? parts.join(' - ') + ' : ' : '') + lessonTitle;

    if (meta) meta.textContent = label;
    if (title) title.textContent = lessonTitle;
    if (btnMark) {
      btnMark.dataset.lessonId = item.dataset.lessonId || '';
      btnMark.dataset.done = item.dataset.done || '0';
      const markText =
        item.dataset.done === '1' ? 'Marquée comme vue' : 'Marquer comme vue';
      btnMark.setAttribute('aria-label', markText);
      btnMark.setAttribute('title', markText);
      if (markLabel) {
        markLabel.textContent = markText;
      } else {
        btnMark.textContent = markText;
      }
    }

    doc.body.dataset.currentLessonId = item.dataset.lessonId || '';
    const card = $('#main-player-card');
    if (card) card.dataset.currentLessonId = item.dataset.lessonId || '';

    window.dispatchEvent(
      new CustomEvent('lesson:change', {
        detail: { lessonId: item.dataset.lessonId || '' },
      })
    );
  }

  function setSpeedLabel(value) {
    const labels = $$('[data-player-speed-status]');
    labels.forEach((label) => {
      label.textContent =
        'Vitesse active : ' + Number(value || 1).toFixed(2) + 'x';
    });
    $$('.speed-chip').forEach((chip) => {
      const active =
        Number(chip.dataset.speed || '1') === Number(value || 1);
      chip.classList.toggle('border-blue-500/60', active);
      chip.classList.toggle('bg-blue-500/10', active);
      chip.classList.toggle('text-blue-100', active);
    });
  }

  function setPlayOverlayVisible(visible) {
    if (!playOverlay) return;
    playOverlay.classList.toggle('hidden', !visible);
    playOverlay.classList.toggle('pointer-events-none', !visible);
    playOverlay.setAttribute('aria-hidden', visible ? 'false' : 'true');
  }

  function setPlayButtonLabel(isPlaying) {
    const btnPlay = $('#btn-play');
    if (!btnPlay) return;
    const label = isPlaying ? 'Pause' : 'Lecture';
    const icon = btnPlay.querySelector('[data-play-icon]');
    const labelNode = btnPlay.querySelector('[data-control-label]');
    if (icon) {
      icon.classList.toggle('fa-play', !isPlaying);
      icon.classList.toggle('fa-pause', isPlaying);
      icon.classList.toggle('ml-1', !isPlaying);
    } else {
      btnPlay.textContent = label;
    }
    if (labelNode) {
      labelNode.textContent = label;
    }
    btnPlay.setAttribute('aria-label', label);
  }

  function syncMuteUi() {
    const btnMute = $('#btn-mute');
    if (!btnMute) return;
    const label = state.muted ? 'Activer le son' : 'Couper le son';
    const icon = btnMute.querySelector('[data-mute-icon]');
    const labelNode = btnMute.querySelector('[data-control-label]');
    if (icon) {
      icon.classList.toggle('fa-volume-high', !state.muted);
      icon.classList.toggle('fa-volume-xmark', state.muted);
    } else {
      btnMute.textContent = label;
    }
    if (labelNode) {
      labelNode.textContent = label;
    }
    btnMute.setAttribute('aria-label', label);
  }

  function currentVolumeTarget() {
    const volumeRange = $('#volume-range');
    if (!volumeRange) return 80;
    const raw = parseInt(volumeRange.value || '80', 10);
    return Math.max(0, Math.min(100, raw));
  }

  function requestAudiblePlayback() {
    const volume = currentVolumeTarget();
    if (volume <= 0) {
      syncMuteUi();
      return;
    }

    if (state.ready && state.player) {
      try {
        if (typeof state.player.setVolume === 'function') {
          state.player.setVolume(volume);
        }
      } catch (_) {}
      try {
        if (typeof state.player.unMute === 'function') {
          state.player.unMute();
          state.muted = false;
        }
      } catch (_) {}
      syncMuteUi();
      return;
    }

    postCommand('setVolume', [volume]);
    postCommand('unMute');
    state.muted = false;
    syncMuteUi();
  }

  function syncPlayButtonLabel() {
    if (
      state.ready &&
      state.player &&
      window.YT &&
      window.YT.PlayerState &&
      typeof state.player.getPlayerState === 'function'
    ) {
      const playerState = state.player.getPlayerState();
      setPlayButtonLabel(playerState === window.YT.PlayerState.PLAYING);
      setPlayOverlayVisible(
        playerState !== window.YT.PlayerState.PLAYING &&
          playerState !== window.YT.PlayerState.BUFFERING
      );
      return;
    }
    setPlayButtonLabel(!!state.pendingPlay);
    setPlayOverlayVisible(!state.pendingPlay);
  }

  function updateTimeUi() {
    if (!state.ready || !state.player) return;
    const duration = state.player.getDuration ? state.player.getDuration() : 0;
    const current = state.player.getCurrentTime
      ? state.player.getCurrentTime()
      : 0;
    const playerState =
      state.player.getPlayerState &&
      window.YT &&
      window.YT.PlayerState
        ? state.player.getPlayerState()
        : null;
    const timeAdvanced = current > state.lastUiTime + 0.15;
    if (
      duration > 0 &&
      (playerState === window.YT?.PlayerState?.PLAYING ||
        playerState === window.YT?.PlayerState?.BUFFERING ||
        (state.pendingPlay && current > 0) ||
        timeAdvanced)
    ) {
      setPlayButtonLabel(true);
      setPlayOverlayVisible(false);
    }
    state.lastUiTime = current;
    const progressRange = $('#progress-range');
    const timeLabel = $('#time-label');
    const remainingLabel = $('#remaining-label');

    if (progressRange && duration > 0) {
      progressRange.value = String(Math.round((current / duration) * 1000));
    }
    if (timeLabel) {
      timeLabel.textContent = formatTime(current) + ' / ' + formatTime(duration);
    }
    if (remainingLabel) {
      remainingLabel.textContent =
        'Temps restant : ' + formatTime(Math.max(0, duration - current));
    }
    state.uiFrame = window.requestAnimationFrame(updateTimeUi);
  }

  function tickWatch() {
    if (!state.ready || !state.player || !window.YT || !window.YT.PlayerState) {
      return;
    }
    const duration = state.player.getDuration
      ? state.player.getDuration()
      : 0;
    if (!duration) return;

    const now = Date.now();
    const currentTime = state.player.getCurrentTime
      ? state.player.getCurrentTime()
      : 0;
    const deltaWall = Math.max(0.2, (now - state.lastWallTime) / 1000);
    const deltaPlayer = Math.max(0, currentTime - state.lastPlayerTime);
    const rate = state.player.getPlaybackRate
      ? state.player.getPlaybackRate()
      : 1;
    const maxAllowed = deltaWall * rate * 1.8 + 0.5;

    const isPlaying =
      state.player.getPlayerState &&
      state.player.getPlayerState() === window.YT.PlayerState.PLAYING;
    if (isPlaying && deltaPlayer <= maxAllowed) {
      state.watchSeconds += deltaPlayer;
    }

    state.lastWallTime = now;
    state.lastPlayerTime = currentTime;

    const ratio = Math.max(0, Math.min(1, state.watchSeconds / duration));
    window.dispatchEvent(
      new CustomEvent('lesson:watchProgress', {
        detail: { ratio, watched: state.watchSeconds, duration },
      })
    );

    if (!state.eligible && ratio >= REQUIRED_RATIO) {
      state.eligible = true;
      setMarkButtonsEnabled(true);
      window.dispatchEvent(
        new CustomEvent('lesson:eligibleToComplete', { detail: { ratio } })
      );
      if (AUTO_MARK && !state.autoMarked) {
        state.autoMarked = true;
        window.setTimeout(completeCurrentLesson, 250);
      }
    }
  }

  function guardBeforeNavigate() {
    const item = currentItem();
    if (!item) return true;
    const done = item.dataset.done === '1';
    if (done || state.eligible) return true;
    notify(LOCK_MESSAGE, 'warning');
    return false;
  }

  function withPlayerParams(rawUrl, params) {
    const src = String(rawUrl || '').trim();
    if (!src) return '';
    try {
      const parsed = new URL(src, window.location.href);
      Object.entries(params || {}).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        parsed.searchParams.set(key, String(value));
      });
      return parsed.toString();
    } catch (_) {
      return src;
    }
  }

  function ensureEmbedUrl(rawUrl) {
    return withPlayerParams(rawUrl, {
      enablejsapi: '1',
      origin: pageOrigin || null,
    });
  }

  function postCommand(func, args) {
    if (!iframe || !iframe.contentWindow) return false;
    try {
      iframe.contentWindow.postMessage(
        JSON.stringify({
          event: 'command',
          func,
          args: Array.isArray(args) ? args : [],
        }),
        '*'
      );
      return true;
    } catch (_) {
      log('error', 'postMessage failed', { func });
      return false;
    }
  }

  function schedulePlayFallback() {
    if (state.playFallbackTimer) {
      window.clearTimeout(state.playFallbackTimer);
    }
    state.playFallbackTimer = window.setTimeout(() => {
      if (!state.ready || !state.player || !window.YT || !window.YT.PlayerState) {
        return;
      }
      const currentState = state.player.getPlayerState
        ? state.player.getPlayerState()
        : state.lastState;
      if (currentState !== window.YT.PlayerState.PLAYING) {
        log('warn', 'Play fallback: mute + play', { state: currentState });
        try {
          state.player.mute();
          state.muted = true;
        } catch (_) {}
        try {
          state.player.playVideo();
        } catch (_) {}
      }
    }, PLAY_FALLBACK_DELAY);
  }

  function playWithFallback(fromUserGesture) {
    const userGesture = !!fromUserGesture;
    log('info', 'Play requested', { ready: state.ready, userGesture });
    if (userGesture) {
      state.userInitiatedPlay = true;
      state.pendingAudiblePlayback = true;
      requestAudiblePlayback();
    }
    state.pendingPlay = true;
    setPlayButtonLabel(true);
    setPlayOverlayVisible(false);
    if (state.ready && state.player) {
      try {
        state.player.playVideo();
      } catch (_) {}
    } else {
      postCommand('playVideo');
    }
    schedulePlayFallback();
  }

  function pauseWithFallback() {
    log('info', 'Pause requested', { ready: state.ready });
    state.pendingPlay = false;
    setPlayButtonLabel(false);
    setPlayOverlayVisible(true);
    if (state.ready && state.player && typeof state.player.pauseVideo === 'function') {
      state.player.pauseVideo();
      return;
    }
    postCommand('pauseVideo');
  }

  function seekWithFallback(deltaSeconds) {
    log('info', 'Seek requested', { deltaSeconds, ready: state.ready });
    if (state.ready && state.player && state.player.getCurrentTime) {
      const nextTime = Math.max(0, state.player.getCurrentTime() + deltaSeconds);
      state.player.seekTo(nextTime, true);
      return;
    }
    const base = Number(state.lastPlayerTime || 0);
    const nextTime = Math.max(0, base + deltaSeconds);
    postCommand('seekTo', [nextTime, true]);
  }

  function completeCurrentLesson() {
    const btnMark = $('#btn-mark');
    if (btnMark) btnMark.click();
  }

  function loadItem(item, autoPlay, fromUserGesture) {
    if (!item) return;
    log('info', 'Load item', {
      lessonId: item.dataset.lessonId,
      videoId: item.dataset.videoId,
    });
    setSettingsPanel(false);
    setItemActive(item);
    updateMeta(item);
    resetLessonTracking();

    const videoId = item.dataset.videoId || '';
    const embedUrl = item.dataset.embedUrl || '';

    const userGesture = !!fromUserGesture;

    if (state.ready && state.player && videoId) {
      state.player.loadVideoById(videoId);
      if (autoPlay !== false) playWithFallback(userGesture);
      else setPlayButtonLabel(false);
    } else if (embedUrl) {
      const shouldMuteAutoplay = autoPlay !== false && !userGesture;
      iframe.src =
        autoPlay !== false
          ? withPlayerParams(ensureEmbedUrl(embedUrl), {
              autoplay: '1',
              mute: shouldMuteAutoplay ? '1' : '0',
            })
          : ensureEmbedUrl(embedUrl);
      if (autoPlay !== false) {
        state.pendingPlay = true;
        if (userGesture) {
          state.userInitiatedPlay = true;
          state.pendingAudiblePlayback = true;
        }
        setPlayButtonLabel(true);
        window.setTimeout(() => playWithFallback(userGesture), 420);
      } else {
        state.pendingPlay = false;
        setPlayButtonLabel(false);
      }
    }
  }

  function moveInPlaylist(direction) {
    const items = $$('.queue-item');
    if (!items.length) return;
    if (!guardBeforeNavigate()) return;
    const current = currentItem();
    const index = Math.max(0, items.indexOf(current));
    const nextIndex =
      direction === 'prev'
        ? index > 0
          ? index - 1
          : 0
        : index < items.length - 1
          ? index + 1
          : items.length - 1;
    loadItem(items[nextIndex], true, true);
  }

  function bindControls() {
    const btnPlay = $('#btn-play');
    if (btnPlay) {
      btnPlay.addEventListener('click', () => {
        if (!state.ready || !state.player) {
          playWithFallback(true);
          return;
        }
        const st = state.player.getPlayerState
          ? state.player.getPlayerState()
          : null;
        if (window.YT && window.YT.PlayerState && st === window.YT.PlayerState.PLAYING) {
          pauseWithFallback();
        } else {
          playWithFallback(true);
        }
      });
    }
    const btnPrev = $('#btn-prev');
    if (btnPrev) btnPrev.addEventListener('click', () => moveInPlaylist('prev'));
    const btnNext = $('#btn-next');
    if (btnNext) btnNext.addEventListener('click', () => moveInPlaylist('next'));

    const btnBack = doc.querySelector('[data-action="back10"]');
    if (btnBack) btnBack.addEventListener('click', () => seekWithFallback(-10));
    const btnFwd = doc.querySelector('[data-action="fwd10"]');
    if (btnFwd) btnFwd.addEventListener('click', () => seekWithFallback(10));

    const btnMute = $('#btn-mute');
    if (btnMute) {
      btnMute.addEventListener('click', () => {
        if (state.ready && state.player) {
          try {
            if (state.player.isMuted && state.player.isMuted()) {
              state.player.unMute();
              state.muted = false;
            } else {
              state.player.mute();
              state.muted = true;
            }
          } catch (_) {}
          syncMuteUi();
          return;
        }
        state.muted = !state.muted;
        postCommand(state.muted ? 'mute' : 'unMute');
        syncMuteUi();
      });
    }

    const btnPip = $('#btn-pip');
    const settingsPanel = $('#player-settings-panel');
    const btnSettingsClose = $('#btn-settings-close');

    function setSettingsPanel(open) {
      if (!settingsPanel) return;
      settingsPanel.classList.toggle('hidden', !open);
      settingsPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
      if (btnPip) {
        btnPip.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    }

    if (btnPip && settingsPanel) {
      btnPip.setAttribute('aria-haspopup', 'dialog');
      btnPip.setAttribute('aria-expanded', 'false');
      btnPip.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const isOpen = !settingsPanel.classList.contains('hidden');
        setSettingsPanel(!isOpen);
      });
    }

    if (btnSettingsClose) {
      btnSettingsClose.addEventListener('click', () => {
        setSettingsPanel(false);
      });
    }

    if (settingsPanel) {
      doc.addEventListener('click', (event) => {
        if (settingsPanel.classList.contains('hidden')) return;
        if (settingsPanel.contains(event.target)) return;
        if (btnPip && btnPip.contains(event.target)) return;
        setSettingsPanel(false);
      });

      doc.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          setSettingsPanel(false);
        }
      });
    }

    const volumeRange = $('#volume-range');
    if (volumeRange) {
      volumeRange.addEventListener('input', (event) => {
        const value = parseInt(event.target.value || '80', 10);
        const safe = Math.max(0, Math.min(100, value));
        if (state.ready && state.player && state.player.setVolume) {
          state.player.setVolume(safe);
          if (safe <= 0 && state.player.mute) {
            state.player.mute();
            state.muted = true;
          } else if (state.player.unMute && state.muted) {
            state.player.unMute();
            state.muted = false;
          }
          syncMuteUi();
          return;
        }
        postCommand('setVolume', [safe]);
        if (safe <= 0) {
          state.muted = true;
          postCommand('mute');
        } else if (state.muted) {
          state.muted = false;
          postCommand('unMute');
        }
        syncMuteUi();
      });
    }

    const progressRange = $('#progress-range');
    if (progressRange) {
      progressRange.addEventListener('input', (event) => {
        if (!state.ready || !state.player || !state.player.getDuration) return;
        const duration = state.player.getDuration() || 0;
        const value = parseInt(event.target.value || '0', 10);
        state.player.seekTo((value / 1000) * duration, true);
      });
    }

    $$('.speed-chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        const speed = Number(chip.dataset.speed || '1');
        if (state.ready && state.player && state.player.setPlaybackRate) {
          state.player.setPlaybackRate(speed);
        } else {
          postCommand('setPlaybackRate', [speed]);
        }
        setSpeedLabel(speed);
      });
    });

    $$('.queue-item').forEach((item) => {
      item.addEventListener('click', () => {
        const active = currentItem();
        if (active && active === item) {
          setItemActive(item);
          updateMeta(item);
          return;
        }
        if (!guardBeforeNavigate()) return;
        loadItem(item, true, true);
      });
    });

    const btnBottom = doc.querySelector('[data-action="mark-done"]');
    if (btnBottom) {
      btnBottom.addEventListener('click', (event) => {
        if (!guardBeforeNavigate()) {
          event.preventDefault();
          return;
        }
        completeCurrentLesson();
      });
    }

    const btnMark = $('#btn-mark');
    if (btnMark) {
      btnMark.addEventListener(
        'click',
        (event) => {
          const item = currentItem();
          const done = item && item.dataset.done === '1';
          if (!done && !state.eligible) {
            event.preventDefault();
            event.stopImmediatePropagation();
            notify(LOCK_MESSAGE, 'warning');
          }
        },
        true
      );
    }

    const btnFullscreen = $('#btn-fullscreen');
    if (btnFullscreen) {
      btnFullscreen.addEventListener('click', () => {
        if (!wrapper) return;
        const target = wrapper;
        if (target.requestFullscreen) target.requestFullscreen();
        else if (target.webkitRequestFullscreen) target.webkitRequestFullscreen();
        else if (target.msRequestFullscreen) target.msRequestFullscreen();
        else notify('Plein Ã©cran non disponible sur cet appareil.', 'warning');
      });
    }

  }

  function onReady() {
    state.ready = true;
    log('info', 'YT player ready');
    setSpeedLabel(1);
    syncPlayButtonLabel();
    resetLessonTracking();
    try {
      if (state.player && state.player.isMuted) {
        state.muted = !!state.player.isMuted();
      }
    } catch (_) {}
    syncMuteUi();
    if (state.uiFrame) cancelAnimationFrame(state.uiFrame);
    state.uiFrame = requestAnimationFrame(updateTimeUi);
    if (state.watchTimer) clearInterval(state.watchTimer);
    state.watchTimer = window.setInterval(tickWatch, 1000);
    const firstItem = currentItem();
    if (firstItem) {
      setItemActive(firstItem);
      updateMeta(firstItem);
    }
    if (state.pendingPlay) {
      state.pendingPlay = false;
      playWithFallback(state.userInitiatedPlay);
    }
    syncPlayButtonLabel();
  }

  function onStateChange(event) {
    state.lastState = event.data;
    const label = YT_STATE_LABELS[String(event.data)] || 'UNKNOWN';
    log('info', `YT state change: ${label}`, { state: event.data, label });
    if (window.YT && window.YT.PlayerState) {
      if (event.data === window.YT.PlayerState.PLAYING) {
        state.pendingPlay = false;
        setPlayButtonLabel(true);
        setPlayOverlayVisible(false);
        if (state.pendingAudiblePlayback) {
          window.setTimeout(() => {
            requestAudiblePlayback();
            state.pendingAudiblePlayback = false;
          }, 150);
        }
      } else if (
        event.data === window.YT.PlayerState.PAUSED ||
        event.data === window.YT.PlayerState.UNSTARTED ||
        event.data === window.YT.PlayerState.CUED
      ) {
        state.pendingPlay = false;
        setPlayButtonLabel(false);
        setPlayOverlayVisible(true);
      } else if (event.data === window.YT.PlayerState.BUFFERING) {
        state.pendingPlay = true;
        setPlayButtonLabel(true);
        setPlayOverlayVisible(false);
      } else if (event.data === window.YT.PlayerState.ENDED) {
        state.pendingPlay = false;
        setPlayButtonLabel(false);
        setPlayOverlayVisible(true);
        state.eligible = true;
        setMarkButtonsEnabled(true);
        if (AUTO_MARK && !state.autoMarked) {
          state.autoMarked = true;
          completeCurrentLesson();
        }
        const items = $$('.queue-item');
        const current = currentItem();
        const index = items.indexOf(current);
        if (index >= 0 && index < items.length - 1) {
          window.setTimeout(() => loadItem(items[index + 1], true, false), 1500);
        }
      }
    }
  }

  function onError(event) {
    log('error', 'YT error', { code: event && event.data ? event.data : null });
    notify('Impossible de lire cette video pour le moment.', 'error');
    state.pendingPlay = false;
    setPlayButtonLabel(false);
  }

  function initPlayer() {
    if (!window.YT || !window.YT.Player || state.player) return;
    const host =
      iframe && iframe.src && iframe.src.includes('youtube-nocookie.com')
        ? 'https://www.youtube-nocookie.com'
        : undefined;
    log('info', 'Init YT player', { host });
    state.player = new window.YT.Player('main-player', {
      host,
      events: {
        onReady,
        onStateChange,
        onError,
      },
    });
  }

  bindControls();
  if (iframe) {
    const normalized = ensureEmbedUrl(iframe.src);
    if (iframe.src !== normalized) {
      log('info', 'Normalize iframe src', { from: iframe.src, to: normalized });
    }
    iframe.src = normalized;
  }

  setSpeedLabel(1);
  setPlayButtonLabel(false);
  syncMuteUi();
  setMarkButtonsEnabled(false);

  const initial = currentItem();
  if (initial) {
    setItemActive(initial);
    updateMeta(initial);
  }

  const previousReady = window.onYouTubeIframeAPIReady;
  window.onYouTubeIframeAPIReady = function () {
    if (typeof previousReady === 'function') previousReady();
    log('info', 'onYouTubeIframeAPIReady fired');
    initPlayer();
  };

  if (window.YT && window.YT.Player) {
    initPlayer();
  } else {
    window.setTimeout(() => {
      if (!window.YT || !window.YT.Player) {
        log('error', 'YT API not ready after timeout');
      }
    }, 4000);
  }

  window.setTimeout(probeConnectivity, 1200);
})();

