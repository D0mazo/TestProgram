'use strict';

document.addEventListener('DOMContentLoaded', function () {

    var searchInput = document.getElementById('sessionSearch');
    var tableBody   = document.getElementById('sessionsBody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            var query = this.value.toLowerCase().trim();
            var rows  = tableBody.querySelectorAll('tr');

            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = (query === '' || text.includes(query)) ? '' : 'none';
            });

            var visible  = Array.from(rows).filter(function (r) { return r.style.display !== 'none'; });
            var noResult = document.getElementById('noResultRow');
            if (noResult) noResult.style.display = visible.length === 0 ? '' : 'none';
        });
    }

    var viewId = document.body.dataset.viewId;
    if (viewId && tableBody) {
        var targetRow = tableBody.querySelector('[data-session-id="' + viewId + '"]');
        if (targetRow) targetRow.style.background = '#eef2ff';
    }
});