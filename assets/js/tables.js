// Table controls for .tbl-wrap tables emitted by the render-table.html hook
// (the .tbl-wide class only adds sticky-header/label scrolling for 6+ columns;
// every table gets the controls):
// - column picker chips (persisted per browser)
// - row text search
// - value-based column filter: click a cell to keep only the columns whose cell
//   in that row shares the clicked cell's state glyph (shift-click inverts);
//   click the same cell again, or the filter chip, to clear. No dependencies.
(function () {
  'use strict';

  function cellText(el) {
    return el.textContent.replace(/\s+/g, ' ').trim();
  }

  function stateOf(text) {
    var first = text.charAt(0);
    if (first === '\u2713' || first === '\u2717' || first === '~' || first === '?') return first;
    return text;
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

    var rowFilter = null; // { rowIdx, state, invert }

    var bar = document.createElement('div');
    bar.className = 'tbl-controls';

    var filter = document.createElement('input');
    filter.type = 'search';
    filter.className = 'tbl-filter';
    filter.setAttribute('placeholder', 'Filter rows');
    filter.setAttribute('aria-label', 'Filter table rows');

    var hint = document.createElement('span');
    hint.className = 'tbl-hint';
    hint.textContent = 'Click a cell to keep tools matching its value (Shift-click to invert)';

    var clearChip = document.createElement('button');
    clearChip.type = 'button';
    clearChip.className = 'tbl-chip tbl-rowfilter';
    clearChip.hidden = true;
    clearChip.addEventListener('click', function () {
      rowFilter = null;
      apply();
    });

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

    function columnVisible(i) {
      if (!saved[i]) return false;
      if (!rowFilter || i === 0) return true;
      var cellState = stateOf(cellText(bodyRows[rowFilter.rowIdx].cells[i]));
      return rowFilter.invert ? cellState !== rowFilter.state : cellState === rowFilter.state;
    }

    function apply() {
      Array.prototype.forEach.call(table.rows, function (row) {
        Array.prototype.forEach.call(row.cells, function (cell, i) {
          cell.style.display = columnVisible(i) ? '' : 'none';
        });
      });
      chips.forEach(function (chip, i) {
        chip.setAttribute('aria-pressed', saved[i] ? 'true' : 'false');
      });
      bodyRows.forEach(function (row, idx) {
        var active = rowFilter && rowFilter.rowIdx === idx;
        row.classList.toggle('tbl-rowactive', !!active);
        if (active) {
          clearChip.hidden = false;
          clearChip.textContent = '';
          var label = cellText(row.cells[0]);
          clearChip.appendChild(document.createTextNode(
            (rowFilter.invert ? 'not ' : '') + label + ' = ' + rowFilter.state + ' \u00d7'
          ));
        }
      });
      if (!rowFilter) clearChip.hidden = true;
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

    table.addEventListener('click', function (e) {
      var td = e.target.closest('td');
      if (!td || td.cellIndex === 0) return;
      var tr = td.parentElement;
      var rowIdx = bodyRows.indexOf(tr);
      if (rowIdx === -1) return;
      var state = stateOf(cellText(td));
      var invert = e.shiftKey;
      if (rowFilter && rowFilter.rowIdx === rowIdx && rowFilter.state === state && rowFilter.invert === invert) {
        rowFilter = null; // same cell clicked twice: clear
      } else {
        rowFilter = { rowIdx: rowIdx, state: state, invert: invert };
      }
      apply();
    });

    Array.prototype.forEach.call(table.querySelectorAll('tbody td:not(:first-child)'), function (td) {
      td.title = 'Click to keep columns matching this value (Shift-click for the opposite)';
    });

    filter.addEventListener('input', function () {
      var q = filter.value.toLowerCase();
      bodyRows.forEach(function (row) {
        var text = cellText(row);
        row.style.display = !q || text.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });

    bar.appendChild(filter);
    chips.forEach(function (chip) { bar.appendChild(chip); });
    bar.appendChild(clearChip);
    bar.appendChild(hint);
    wrap.parentNode.insertBefore(bar, wrap);
    apply();
  }

  function start() {
    var wraps = document.querySelectorAll('.tbl-wrap');
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
