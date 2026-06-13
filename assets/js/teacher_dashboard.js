(function () {
  var storageKey = 'theme';
  var chartState = {
    canvas: null,
    resizeTimer: null
  };
  var scrollTopState = {
    button: null,
    initialized: false,
    root: null
  };

  function redrawChart() {
    if (chartState.canvas) {
      drawChart(chartState.canvas);
    }
  }

  function getScrollTopRoot() {
    var candidate = document.querySelector('[data-scroll-top-root]');
    return candidate instanceof HTMLElement ? candidate : null;
  }

  function updateScrollTopButtonVisibility() {
    var button = scrollTopState.button;
    if (!(button instanceof HTMLElement)) {
      return;
    }

    var root = scrollTopState.root instanceof HTMLElement ? scrollTopState.root : null;
    var doc = document.documentElement;
    var body = document.body;
    var scrollTop = root ? root.scrollTop : (window.scrollY || doc.scrollTop || body.scrollTop || 0);
    var viewportHeight = root ? root.clientHeight : (window.innerHeight || doc.clientHeight || 0);
    var documentHeight = root ? root.scrollHeight : Math.max(
      doc.scrollHeight || 0,
      body.scrollHeight || 0,
      doc.offsetHeight || 0,
      body.offsetHeight || 0
    );
    var canScroll = documentHeight > (viewportHeight + 360);
    var activationDistance = Math.max(360, Math.min(620, Math.round(viewportHeight * 0.55)));
    var showButton = canScroll && scrollTop >= activationDistance;

    button.classList.toggle('is-visible', showButton);
    button.setAttribute('aria-hidden', showButton ? 'false' : 'true');
  }

  function ensureScrollTopButton() {
    if (!document.body) {
      return;
    }

    if (!(scrollTopState.button instanceof HTMLElement) || !document.body.contains(scrollTopState.button)) {
      var button = document.querySelector('[data-scroll-top]');

      if (!(button instanceof HTMLElement)) {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'web-scroll-top';
        button.setAttribute('data-scroll-top', '1');
        button.setAttribute('aria-label', 'Remonter en haut de la page');
        button.setAttribute('aria-hidden', 'true');
        button.setAttribute('title', 'Remonter en haut');
        button.innerHTML = '<i class="fas fa-arrow-up" aria-hidden="true"></i>';

        if (document.querySelector('[data-support-fab]')) {
          button.style.bottom = '5.25rem';
        }

        document.body.appendChild(button);
      }

      scrollTopState.button = button;
    }

    scrollTopState.root = getScrollTopRoot();

    if (scrollTopState.button && scrollTopState.button.dataset.scrollTopBound !== '1') {
      scrollTopState.button.addEventListener('click', function (event) {
        event.preventDefault();

        if (scrollTopState.root instanceof HTMLElement) {
          scrollTopState.root.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
      scrollTopState.button.dataset.scrollTopBound = '1';
    }

    if (!scrollTopState.initialized) {
      if (scrollTopState.root instanceof HTMLElement) {
        scrollTopState.root.addEventListener('scroll', updateScrollTopButtonVisibility, { passive: true });
      } else {
        window.addEventListener('scroll', updateScrollTopButtonVisibility, { passive: true });
      }

      window.addEventListener('resize', updateScrollTopButtonVisibility, { passive: true });
      window.addEventListener('load', updateScrollTopButtonVisibility, { passive: true });
      window.addEventListener('pageshow', updateScrollTopButtonVisibility, { passive: true });
      scrollTopState.initialized = true;
    }

    updateScrollTopButtonVisibility();
  }

  function getStoredTheme() {
    try {
      return localStorage.getItem(storageKey);
    } catch (_error) {
      return null;
    }
  }

  function setStoredTheme(value) {
    try {
      localStorage.setItem(storageKey, value);
    } catch (_error) {
      /* Storage unavailable. */
    }
  }

  function applyTheme(isDark) {
    var html = document.documentElement;
    html.classList.toggle('dark', isDark);
    html.classList.toggle('light', !isDark);
    html.setAttribute('data-theme', isDark ? 'dark' : 'light');
    html.style.colorScheme = isDark ? 'dark' : 'light';
    setStoredTheme(isDark ? 'dark' : 'light');

    var buttons = document.querySelectorAll('[data-theme-toggle], #theme-toggle, #theme-toggle-sidebar');
    buttons.forEach(function (button) {
      button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    });
  }

  function toggleTheme() {
    applyTheme(!document.documentElement.classList.contains('dark'));
  }

  var storedTheme = getStoredTheme();
  applyTheme(storedTheme === 'dark' || (!storedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches));
  ensureScrollTopButton();

  function parseJsonArray(value) {
    if (!value) {
      return [];
    }

    try {
      var parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('fr-FR').format(value);
  }

  function drawRoundedRect(ctx, x, y, width, height, radius) {
    var r = Math.max(0, Math.min(radius || 0, Math.min(width, height) / 2));
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + width, y, x + width, y + height, r);
    ctx.arcTo(x + width, y + height, x, y + height, r);
    ctx.arcTo(x, y + height, x, y, r);
    ctx.arcTo(x, y, x + width, y, r);
    ctx.closePath();
  }

  function drawChart(canvas) {
    if (!canvas) {
      return;
    }

    var labels = parseJsonArray(canvas.dataset.chartLabels);
    var values = parseJsonArray(canvas.dataset.chartValues).map(function (value) {
      var parsed = Number(value);
      return Number.isFinite(parsed) ? parsed : 0;
    });

    var context = canvas.getContext('2d');
    if (!context) {
      return;
    }

    var rect = canvas.getBoundingClientRect();
    var parentWidth = canvas.parentElement ? canvas.parentElement.clientWidth : 320;
    var width = Math.max(320, Math.floor(rect.width || parentWidth || 320));
    var height = Math.max(180, Math.floor(rect.height || 220));
    var dpr = window.devicePixelRatio || 1;

    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    context.setTransform(dpr, 0, 0, dpr, 0, 0);
    context.clearRect(0, 0, width, height);

    var html = document.documentElement;
    var dark = html.classList.contains('dark');
    var tickColor = dark ? '#cbd5e1' : '#64748b';
    var gridColor = dark ? 'rgba(148, 163, 184, 0.14)' : 'rgba(148, 163, 184, 0.18)';
    var barTopColor = dark ? 'rgba(96, 165, 250, 0.96)' : 'rgba(37, 99, 235, 0.96)';
    var barBottomColor = dark ? 'rgba(6, 182, 212, 0.88)' : 'rgba(6, 182, 212, 0.9)';
    var barBorderColor = dark ? 'rgba(96, 165, 250, 0.96)' : 'rgba(37, 99, 235, 0.96)';

    if (!labels.length || !values.length) {
      context.fillStyle = tickColor;
      context.font = '500 14px "Inter", system-ui, sans-serif';
      context.textAlign = 'center';
      context.fillText('Aucune donnée', width / 2, height / 2);
      return;
    }

    var padding = {
      top: 22,
      right: 18,
      bottom: 48,
      left: 44
    };
    var plotWidth = Math.max(1, width - padding.left - padding.right);
    var plotHeight = Math.max(1, height - padding.top - padding.bottom);
    var rawMax = Math.max.apply(Math, values.concat([0]));
    var maxValue = Math.max(5, Math.ceil(rawMax / 5) * 5);
    var gridLines = 5;
    var slotWidth = plotWidth / values.length;
    var minBarWidth = values.length > 18 ? 4 : (values.length > 10 ? 8 : 18);
    var barWidth = Math.max(minBarWidth, Math.min(68, slotWidth * 0.58));

    context.save();
    context.fillStyle = dark ? 'rgba(15, 23, 42, 0.35)' : 'rgba(255, 255, 255, 0.55)';
    drawRoundedRect(context, 0, 0, width, height, 16);
    context.fill();
    context.restore();

    context.save();
    context.strokeStyle = gridColor;
    context.fillStyle = tickColor;
    context.lineWidth = 1;
    context.font = '500 11px "Inter", system-ui, sans-serif';

    for (var i = 0; i <= gridLines; i += 1) {
      var ratio = i / gridLines;
      var y = padding.top + plotHeight - (ratio * plotHeight);
      context.beginPath();
      context.moveTo(padding.left, y);
      context.lineTo(width - padding.right, y);
      context.stroke();

      var value = Math.round(maxValue * ratio);
      context.textAlign = 'right';
      context.textBaseline = 'middle';
      context.fillText(formatNumber(value), padding.left - 8, y);
    }
    context.restore();

    if (values.length > 0) {
      values.forEach(function (value, index) {
        var clampedValue = Math.max(0, Number(value) || 0);
        var ratio = Math.max(0, Math.min(1, clampedValue / maxValue));
        var barHeight = Math.max(2, ratio * plotHeight);
        var x = padding.left + (index * slotWidth) + (slotWidth - barWidth) / 2;
        var y = padding.top + plotHeight - barHeight;
        var radius = Math.min(16, Math.max(6, barWidth / 4));
        var gradient = context.createLinearGradient(0, y, 0, y + barHeight);
        gradient.addColorStop(0, barTopColor);
        gradient.addColorStop(1, barBottomColor);

        context.save();
        context.fillStyle = gradient;
        context.strokeStyle = barBorderColor;
        context.lineWidth = 1;
        drawRoundedRect(context, x, y, barWidth, barHeight, radius);
        context.fill();
        context.stroke();
        context.restore();

        context.save();
        context.fillStyle = dark ? '#e2e8f0' : '#0f172a';
        context.font = '700 11px "Inter", system-ui, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'bottom';
        context.fillText(formatNumber(clampedValue), x + barWidth / 2, Math.max(padding.top + 12, y - 6));
        context.restore();
      });
    }

    context.save();
    context.fillStyle = tickColor;
    context.font = '500 11px "Inter", system-ui, sans-serif';
    context.textAlign = 'center';
    context.textBaseline = 'top';

    var labelStep = Math.max(1, Math.ceil(labels.length / 6));
    labels.forEach(function (label, index) {
      if (index % labelStep !== 0 && index !== labels.length - 1) {
        return;
      }

      var labelText = String(label);
      if (labelText.length > 12) {
        labelText = labelText.slice(0, 11) + '…';
      }
      var x = padding.left + (index * slotWidth) + slotWidth / 2;
      context.fillText(labelText, x, height - padding.bottom + 14);
    });
    context.restore();
  }

  function initDashboard() {
    applyTheme(getStoredTheme() === 'dark' || (!getStoredTheme() && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches));

    var themeButtons = document.querySelectorAll('[data-theme-toggle], #theme-toggle, #theme-toggle-sidebar');
    themeButtons.forEach(function (button) {
      if (button.getAttribute('data-theme-bound') === '1') {
        return;
      }
      button.addEventListener('click', function (event) {
        event.preventDefault();
        toggleTheme();
      });
      button.setAttribute('data-theme-bound', '1');
    });

    var rangeSelect = document.querySelector('[data-dashboard-range-select]');
    if (rangeSelect) {
      rangeSelect.addEventListener('change', function () {
        if (rangeSelect.form) {
          rangeSelect.form.submit();
        }
      });
    }

    chartState.canvas = document.getElementById('teacher-dashboard-chart');
    if (chartState.canvas) {
      drawChart(chartState.canvas);
      if (typeof MutationObserver !== 'undefined') {
        var themeObserver = new MutationObserver(function () {
          redrawChart();
        });
        themeObserver.observe(document.documentElement, {
          attributes: true,
          attributeFilter: ['class', 'data-theme']
        });
      }
      window.addEventListener('resize', function () {
        window.clearTimeout(chartState.resizeTimer);
        chartState.resizeTimer = window.setTimeout(function () {
          redrawChart();
        }, 120);
      });
    }

    if (window.matchMedia) {
      var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      var handleChange = function (event) {
        if (getStoredTheme() === 'dark' || getStoredTheme() === 'light') {
          return;
        }
        applyTheme(!!event.matches);
      };
      if (typeof mediaQuery.addEventListener === 'function') {
        mediaQuery.addEventListener('change', handleChange);
      } else if (typeof mediaQuery.addListener === 'function') {
        mediaQuery.addListener(handleChange);
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
  } else {
    initDashboard();
  }
})();
