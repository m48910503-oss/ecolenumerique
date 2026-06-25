(function () {
  var page = document.querySelector('[data-admin-finance-page]');
  if (!page) {
    return;
  }

  var tabs = Array.prototype.slice.call(page.querySelectorAll('[data-finance-tab]'));
  var panels = Array.prototype.slice.call(page.querySelectorAll('[data-finance-panel]'));

  function activateTab(tabName) {
    tabs.forEach(function (tab) {
      var active = tab.getAttribute('data-finance-tab') === tabName;
      tab.classList.toggle('admin-finance-tab--active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach(function (panel) {
      var active = panel.getAttribute('data-finance-panel') === tabName;
      panel.classList.toggle('hidden', !active);
    });

    Array.prototype.slice.call(page.querySelectorAll('input[name="tab"]')).forEach(function (input) {
      input.value = tabName;
    });

    if (window.history && window.history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('tab', tabName);
      window.history.replaceState({}, '', url.toString());
    }
  }

  tabs.forEach(function (tab) {
    tab.setAttribute('role', 'tab');
    tab.addEventListener('click', function () {
      activateTab(tab.getAttribute('data-finance-tab') || 'home');
    });
  });

  var defaultTab = page.getAttribute('data-default-tab') || 'home';
  activateTab(defaultTab);

  var searchInput = page.querySelector('[data-admin-finance-search]');
  var rows = Array.prototype.slice.call(page.querySelectorAll('[data-finance-search-row]'));
  if (searchInput && rows.length > 0) {
    searchInput.addEventListener('input', function () {
      var query = searchInput.value.trim().toLowerCase();
      rows.forEach(function (row) {
        var haystack = (row.getAttribute('data-finance-search') || '').toLowerCase();
        row.classList.toggle('admin-finance-search-hidden', query !== '' && haystack.indexOf(query) === -1);
      });
    });
  }
})();
