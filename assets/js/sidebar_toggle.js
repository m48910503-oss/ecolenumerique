(() => {
  const body = document.body;
  const sidebar = document.querySelector('.js-sidebar');
  const toggles = document.querySelectorAll('.js-sidebar-toggle');
  const backdrop = document.querySelector('.js-sidebar-backdrop');
  const closeButtons = document.querySelectorAll('[data-close-sidebar]');

  if (!body || !sidebar || toggles.length === 0) {
    return;
  }

  const desktopToggleEnabled =
    body.classList.contains('app-role-teacher')
    || (
      body.dataset?.sidebarDesktopToggle === '1'
      && !body.classList.contains('app-role-admin')
    );

  const detailSections = Array.from(
    sidebar.querySelectorAll('details.app-shell-sidebar__section')
  );
  const storageKey = `app.sidebar.state.${sidebar.id || body.dataset?.view || 'default'}`;

  const isDesktop = () => window.innerWidth >= 768;

  const readDesktopState = () => {
    try {
      return localStorage.getItem(storageKey);
    } catch (_error) {
      return null;
    }
  };

  const writeDesktopState = (value) => {
    try {
      localStorage.setItem(storageKey, value);
    } catch (_error) {
      /* Storage unavailable. */
    }
  };

  const showSidebarPosition = () => {
    sidebar.classList.add('left-0');
    sidebar.classList.remove('-left-full');
  };

  const hideSidebarPosition = () => {
    sidebar.classList.remove('left-0');
    sidebar.classList.add('-left-full');
  };

  const setExpanded = (isOpen) => {
    toggles.forEach((btn) => {
      const desktopCollapseLabel = btn.dataset.sidebarLabelCollapse || 'Reduire la barre laterale';
      const desktopExpandLabel = btn.dataset.sidebarLabelExpand || 'Deployer la barre laterale';
      const mobileOpenLabel = btn.dataset.sidebarLabelOpen || 'Ouvrir le menu';
      const label = isDesktop() && desktopToggleEnabled
        ? (isOpen ? desktopCollapseLabel : desktopExpandLabel)
        : mobileOpenLabel;

      btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      btn.setAttribute('aria-label', label);
      btn.setAttribute('title', label);
      btn.classList.toggle('is-collapsed', !isOpen && isDesktop() && desktopToggleEnabled);
    });
  };

  const setBackdropClosed = () => {
    if (!backdrop) {
      return;
    }
    backdrop.classList.add('opacity-0', 'pointer-events-none');
  };

  const setBackdropOpen = () => {
    if (!backdrop) {
      return;
    }
    backdrop.classList.remove('opacity-0', 'pointer-events-none');
  };

  const forceDetailsOpen = () => {
    detailSections.forEach((section) => {
      if (section.dataset.sidebarForcedOpen === '1') {
        return;
      }
      section.dataset.sidebarWasOpen = section.open ? '1' : '0';
      section.dataset.sidebarForcedOpen = '1';
      section.open = true;
    });
  };

  const restoreDetailsState = () => {
    detailSections.forEach((section) => {
      if (section.dataset.sidebarForcedOpen !== '1') {
        return;
      }
      section.open = section.dataset.sidebarWasOpen === '1';
      delete section.dataset.sidebarWasOpen;
      delete section.dataset.sidebarForcedOpen;
    });
  };

  const applyDesktopState = (collapsed) => {
    body.classList.toggle('sidebar-collapsed', collapsed);
    body.classList.remove('sidebar-open');
    showSidebarPosition();
    setBackdropClosed();

    if (collapsed) {
      forceDetailsOpen();
    } else {
      restoreDetailsState();
    }

    setExpanded(!collapsed);
  };

  const openSidebar = () => {
    if (isDesktop()) {
      if (desktopToggleEnabled) {
        writeDesktopState('expanded');
        applyDesktopState(false);
        return;
      }
      body.classList.remove('sidebar-open', 'sidebar-collapsed');
      showSidebarPosition();
      setBackdropClosed();
      setExpanded(true);
      return;
    }

    body.classList.add('sidebar-open');
    showSidebarPosition();
    setBackdropOpen();
    setExpanded(true);
  };

  const closeSidebar = () => {
    if (isDesktop()) {
      if (desktopToggleEnabled) {
        writeDesktopState('collapsed');
        applyDesktopState(true);
        return;
      }
      body.classList.remove('sidebar-open', 'sidebar-collapsed');
      restoreDetailsState();
      showSidebarPosition();
      setBackdropClosed();
      setExpanded(true);
      return;
    }

    body.classList.remove('sidebar-open');
    hideSidebarPosition();
    setBackdropClosed();
    setExpanded(false);
  };

  const toggleSidebar = () => {
    if (isDesktop() && desktopToggleEnabled) {
      if (body.classList.contains('sidebar-collapsed')) {
        openSidebar();
        return;
      }
      closeSidebar();
      return;
    }

    if (sidebar.classList.contains('-left-full')) {
      openSidebar();
      return;
    }

    closeSidebar();
  };

  toggles.forEach((btn) => {
    btn.addEventListener('click', toggleSidebar);
  });

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  closeButtons.forEach((btn) => {
    btn.addEventListener('click', closeSidebar);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });

  const syncByViewport = () => {
    if (isDesktop()) {
      const collapsed = desktopToggleEnabled && readDesktopState() === 'collapsed';
      applyDesktopState(collapsed);
      return;
    }

    restoreDetailsState();
    body.classList.remove('sidebar-open', 'sidebar-collapsed');
    hideSidebarPosition();
    setBackdropClosed();
    setExpanded(false);
  };

  syncByViewport();
  window.addEventListener('resize', syncByViewport);

  const navLinks = sidebar.querySelectorAll('a');

  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      if (link.classList.contains('app-nav-link')) {
        navLinks.forEach((node) => {
          if (!node.classList.contains('app-nav-link')) return;
          node.classList.remove('is-active-nav');
          node.removeAttribute('aria-current');
        });
        link.classList.add('is-active-nav');
        link.setAttribute('aria-current', 'page');
      } else if (link.classList.contains('app-shell-sidebar__primary')) {
        navLinks.forEach((node) => {
          if (!node.classList.contains('app-shell-sidebar__primary')) return;
          node.classList.remove('is-active-nav');
          node.removeAttribute('aria-current');
        });
        link.classList.add('is-active-nav');
        link.setAttribute('aria-current', 'page');
      }

      if (!isDesktop()) {
        closeSidebar();
      }
    });
  });
})();

(() => {
  const sourceScriptUrl = document.currentScript?.src || '';

  const loadSupportModal = () => {
    const body = document.body;
    if (!body || !document.querySelector('[data-support-fab]')) {
      return;
    }

    const view = body.dataset?.view || document.documentElement?.dataset?.view || '';
    if (['student/support', 'teacher/support', 'web/support'].includes(view)) {
      return;
    }

    if (
      window.ENSupportModalInitialized
      || window.ENSupportModalScriptRequested
      || document.querySelector('script[src*="support_modal.js"]')
    ) {
      return;
    }

    window.ENSupportModalScriptRequested = true;

    const script = document.createElement('script');
    script.defer = true;
    script.src = (() => {
      const fallbackScript = document.querySelector('script[src*="sidebar_toggle.js"]');
      const source = sourceScriptUrl || (fallbackScript instanceof HTMLScriptElement ? fallbackScript.src : '');
      if (!source) {
        return 'assets/js/support_modal.js';
      }

      const url = new URL(source, window.location.href);
      url.pathname = url.pathname.replace(/sidebar_toggle\.js$/, 'support_modal.js');
      return url.toString();
    })();
    document.head.appendChild(script);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadSupportModal, { once: true });
  } else {
    loadSupportModal();
  }
})();
