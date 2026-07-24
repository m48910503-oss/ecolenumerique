// assets/js/fullscreen_guard.js
// Bloque les interactions sur la zone video (y compris en plein ecran)
// et ajoute un mini-panneau de controle utilisable en fullscreen.

(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.getElementById('player-wrapper');
    const overlay = document.getElementById('player-overlay');
    if (!wrap || !overlay) return;
    const ALLOW_SELECTOR = '[data-guard-allow="1"]';

    const isAllowed = (target) =>
      !!(
        target &&
        (target.closest?.(ALLOW_SELECTOR) || target.dataset?.guardAllow === '1')
      );

    const block = (e) => {
      if (isAllowed(e.target)) return;
      try {
        e.preventDefault();
        e.stopPropagation();
      } catch (_) {}
      return false;
    };

    const events = [
      'contextmenu',
      'mousedown',
      'mouseup',
      'click',
      'dblclick',
      'dragstart',
      'selectstart',
      'copy',
      'cut',
      'paste',
    ];

    // Applique les listeners sur overlay et wrapper
    function applyGuards(target) {
      if (!target) return;
      events.forEach((evt) => target.addEventListener(evt, block, { capture: true }));
      target.addEventListener('touchstart', block, { passive: false, capture: true });
    }

    const fsControls = buildFullscreenControls();
    wrap.appendChild(fsControls.bar);

    function buildFullscreenControls() {
      const bar = document.createElement('div');
      bar.id = 'fs-player-controls';
      bar.dataset.guardAllow = '1';
      Object.assign(bar.style, {
        position: 'absolute',
        left: '50%',
        bottom: '14px',
        transform: 'translateX(-50%)',
        display: 'none',
        gap: '8px',
        alignItems: 'center',
        padding: '8px 10px',
        background: 'rgba(15, 23, 42, 0.82)',
        border: '1px solid rgba(148, 163, 184, 0.55)',
        borderRadius: '9999px',
        boxShadow: '0 18px 45px rgba(0,0,0,0.45)',
        zIndex: '99999',
        pointerEvents: 'none', // active uniquement quand visible
      });

      const progress = document.createElement('span');
      progress.dataset.guardAllow = '1';
      progress.textContent = '0.00%';
      Object.assign(progress.style, {
        fontSize: '12px',
        color: '#cbd5e1',
        padding: '0 6px',
        borderLeft: '1px solid rgba(148, 163, 184, 0.55)',
      });

      const btnStyle = {
        fontSize: '12px',
        color: '#e2e8f0',
        background: 'rgba(30, 41, 59, 0.9)',
        border: '1px solid rgba(148, 163, 184, 0.7)',
        borderRadius: '10px',
        padding: '6px 10px',
        cursor: 'pointer',
      };

      const actions = [
        { label: 'Prec', handler: () => document.getElementById('btn-prev')?.click() },
        { label: '-10s', handler: () => document.querySelector('[data-action="back10"]')?.click() },
        { label: 'Lecture/Pause', handler: () => document.getElementById('btn-play')?.click() },
        { label: '+10s', handler: () => document.querySelector('[data-action="fwd10"]')?.click() },
        { label: 'Suiv', handler: () => document.getElementById('btn-next')?.click() },
        { label: 'Mute', handler: () => document.getElementById('btn-mute')?.click() },
        {
          label: 'Quitter plein ecran',
          handler: () => {
            if (document.exitFullscreen) document.exitFullscreen();
            else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
          },
        },
      ];

      actions.forEach((action) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = action.label;
        btn.dataset.guardAllow = '1';
        Object.assign(btn.style, btnStyle);
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          action.handler?.();
        });
        btn.addEventListener('mouseenter', () => {
          btn.style.borderColor = '#60a5fa';
        });
        btn.addEventListener('mouseleave', () => {
          btn.style.borderColor = 'rgba(148, 163, 184, 0.7)';
        });
        bar.appendChild(btn);
      });

      bar.appendChild(progress);

      const setProgress = (pct) => {
        if (!Number.isFinite(pct)) return;
        progress.textContent = `${pct.toFixed(2)}%`;
      };

      return { bar, setProgress };
    }

    function isFullscreenOnWrapper() {
      const fsEl =
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement;
      return !!(fsEl && wrap.contains(fsEl));
    }

    function toggleFsControls() {
      const visible = isFullscreenOnWrapper();
      fsControls.bar.style.display = visible ? 'flex' : 'none';
      fsControls.bar.style.pointerEvents = visible ? 'auto' : 'none';
    }

    applyGuards(overlay);
    applyGuards(wrap);
    toggleFsControls();

    // Lien direct avec le slider principal (même progression que hors fullscreen)
    const progressRange = document.getElementById('progress-range');
    const syncFromRange = () => {
      if (!progressRange) return false;
      const v = parseInt(progressRange.value || '0', 10);
      const pct = Math.max(0, Math.min(1000, v)) / 10; // slider 0-1000 -> %
      fsControls.setProgress(pct);
      return true;
    };
    if (progressRange) {
      progressRange.addEventListener('input', syncFromRange, { capture: true });
      // Poll le slider car sa valeur est mise à jour par code (pas d'event input)
      setInterval(syncFromRange, 500);
      syncFromRange();
    }

    // Fallback : si pas de slider, on prend la progression "watch" (mêmes decimales)
    window.addEventListener('lesson:watchProgress', (ev) => {
      if (progressRange) return; // slider prioritaire pour cohérence UI
      const ratio = ev?.detail?.ratio;
      if (Number.isFinite(ratio)) {
        const pct = Math.max(0, Math.min(1, ratio)) * 100;
        fsControls.setProgress(pct);
      }
    });

    // En plein ecran on garde les gardes + le panneau de controle actif
    ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach((evt) => {
      document.addEventListener(evt, () => {
        applyGuards(overlay);
        applyGuards(wrap);
        toggleFsControls();
      });
    });
  });
})();
