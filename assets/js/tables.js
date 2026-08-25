// Wide-table controls: column picker chips + row filter, progressive enhancement
// for .tbl-wide tables emitted by the render-table.html hook. No dependencies.
(function () {
  'use strict';

  function cellText(el) {
    return el.textContent.replace(/\s+/g, ' ').trim();
  }

  function init(wrap, ordinal) {
    var table = wrap.querySelector('table');
    if (!table || table.rows.length < 2) return;
    var headerCells = Array.prototype.slice.call(table.rows[0].cells);
    var bodyRows = Array.prototype.slice.call(table.rows).slice(1);
    var colCount = headerCells.length;

    var storeKey = 'tblcols:' + location.pathname + ':' + ordinal;
    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(storeKey) || 'null'); } catch (e) { saved = null; }
    if (!Array.isArray(saved) || saved.length !== colCount) {
      saved = headerCells.map(function () { return true; });
    }

    var bar = document.createElement('div');
    bar.className = 'tbl-controls';

    var filter = document.createElement('input');
    filter.type = 'search';
    filter.className = 'tbl-filter';
    filter.setAttribute('placeholder', 'Filter rows');
    filter.setAttribute('aria-label', 'Filter table rows');

    var chips = headerCells.map(function (th, i) {
      var chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'tbl-chip';
      chip.textContent = cellText(th) || ('Col ' + (i + 1));
      chip.addEventListener('click', function () {
        toggle(i, !saved[i]);
      });
      return chip;
    });

    function apply() {
      Array.prototype.forEach.call(table.rows, function (row) {
        Array.prototype.forEach.call(row.cells, function (cell, i) {
          cell.style.display = saved[i] ? '' : 'none';
        });
      });
      chips.forEach(function (chip, i) {
        chip.setAttribute('aria-pressed', saved[i] ? 'true' : 'false');
      });
      try { localStorage.setItem(storeKey, JSON.stringify(saved)); } catch (e) { /* private mode */ }
    }

    function toggle(i, on) {
      if (i === 0 && !on) return; // never hide the label column
      if (!on) {
        var visible = saved.filter(Boolean).length;
        if (visible <= 2) return; // keep the label column plus at least one
      }
      saved[i] = on;
      apply();
    }

    filter.addEventListener('input', function () {
      var q = filter.value.toLowerCase();
      bodyRows.forEach(function (row) {
        var text = cellText(row);
        row.style.display = !q || text.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });

    bar.appendChild(filter);
    chips.forEach(function (chip) { bar.appendChild(chip); });
    wrap.parentNode.insertBefore(bar, wrap);
    apply();
  }

  function start() {
    var wraps = document.querySelectorAll('.tbl-wrap.tbl-wide');
    Array.prototype.forEach.call(wraps, function (wrap, i) {
      init(wrap, i);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
