(function () {
  'use strict';

  function normalize(value) {
    return String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var table = document.querySelector('[data-referrals-table]');
    var search = document.querySelector('[data-referrals-search]');
    var filter = document.querySelector('[data-referrals-filter]');
    var emptyState = document.querySelector('[data-referrals-empty]');
    var visibleCount = document.querySelector('[data-referrals-visible-count]');

    if (!table || (!search && !filter)) {
      return;
    }

    var rows = Array.prototype.slice.call(table.querySelectorAll('[data-referral-row]'));
    if (!rows.length) {
      return;
    }

    function update() {
      var query = normalize(search ? search.value : '');
      var status = normalize(filter ? filter.value : 'all');
      var visible = 0;

      rows.forEach(function (row) {
        var rowName = normalize(row.getAttribute('data-referral-name'));
        var rowStatus = normalize(row.getAttribute('data-referral-status'));
        var matchesSearch = !query || rowName.indexOf(query) !== -1 || normalize(row.textContent).indexOf(query) !== -1;
        var matchesStatus = !status || status === 'all' || rowStatus === status;
        var show = matchesSearch && matchesStatus;

        row.classList.toggle('hidden', !show);
        if (show) {
          visible++;
        }
      });

      if (emptyState) {
        emptyState.classList.toggle('hidden', visible > 0);
      }

      if (visibleCount) {
        visibleCount.textContent = String(visible);
      }
    }

    if (search) {
      search.addEventListener('input', update);
    }

    if (filter) {
      filter.addEventListener('change', update);
    }

    update();
  });
})();
