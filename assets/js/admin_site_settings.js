(function () {
  var page = document.querySelector('[data-admin-settings-page]');
  if (!page) {
    return;
  }

  var tabs = Array.prototype.slice.call(page.querySelectorAll('[data-settings-tab]'));
  var panels = Array.prototype.slice.call(page.querySelectorAll('[data-settings-panel]'));
  var activeTabInput = page.querySelector('[data-settings-active-tab]');

  function activateTab(tabName) {
    if (activeTabInput) {
      activeTabInput.value = tabName;
    }

    tabs.forEach(function (tab) {
      var active = tab.getAttribute('data-settings-tab') === tabName;
      tab.classList.toggle('admin-settings-tab--active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach(function (panel) {
      var active = panel.getAttribute('data-settings-panel') === tabName;
      panel.classList.toggle('hidden', !active);
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
      activateTab(tab.getAttribute('data-settings-tab') || 'identity');
    });
  });

  activateTab(page.getAttribute('data-default-tab') || 'identity');

  Array.prototype.slice.call(page.querySelectorAll('[data-settings-file]')).forEach(function (input) {
    input.addEventListener('change', function () {
      var key = input.getAttribute('data-settings-file') || '';
      var label = page.querySelector('[data-settings-file-name="' + key + '"]');
      if (!label) {
        return;
      }
      label.textContent = input.files && input.files.length > 0 ? input.files[0].name : 'Aucun fichier choisi';
    });
  });

  Array.prototype.slice.call(page.querySelectorAll('[data-remove-trigger]')).forEach(function (button) {
    button.addEventListener('click', function () {
      var key = button.getAttribute('data-remove-trigger') || '';
      var input = page.querySelector('[data-remove-input="' + key + '"]');
      var note = page.querySelector('[data-remove-note="' + key + '"]');
      var box = page.querySelector('[data-remove-box="' + key + '"]');
      var label = button.querySelector('span');
      var icon = button.querySelector('i');
      var marked = input && input.value === '1';

      if (!input) {
        return;
      }

      input.value = marked ? '0' : '1';
      button.classList.toggle('admin-settings-remove-button--marked', !marked);
      if (box) {
        box.classList.toggle('admin-settings-remove-box--marked', !marked);
      }
      if (note) {
        note.classList.toggle('hidden', marked);
      }
      if (label) {
        label.textContent = marked
          ? (button.getAttribute('data-remove-label') || 'Supprimer')
          : (button.getAttribute('data-cancel-label') || 'Annuler la suppression');
      }
      if (icon) {
        icon.classList.toggle('fa-trash-alt', marked);
        icon.classList.toggle('fa-undo-alt', !marked);
      }
    });
  });
})();
