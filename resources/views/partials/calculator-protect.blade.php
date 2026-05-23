{{-- Calculator copy/inspect deterrents.
     NOTE: This stops casual copying only — anyone with DevTools knowledge can bypass it.
     For real formula protection, move calculations server-side. --}}
<style>
  body.calc-protected {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
  }
  body.calc-protected input,
  body.calc-protected textarea,
  body.calc-protected select,
  body.calc-protected [contenteditable="true"] {
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
    user-select: text;
  }
  body.calc-protected img,
  body.calc-protected canvas,
  body.calc-protected svg {
    -webkit-user-drag: none;
    user-drag: none;
    pointer-events: auto;
  }
  @media print {
    body.calc-protected { display: none !important; }
  }
</style>
<script>
(function () {
  document.body.classList.add('calc-protected');

  // Block right-click except inside form fields
  document.addEventListener('contextmenu', function (e) {
    var tag = (e.target && e.target.tagName) || '';
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
    e.preventDefault();
  });

  // Block common copy/save/view-source/devtools shortcuts
  document.addEventListener('keydown', function (e) {
    var k = (e.key || '').toLowerCase();
    var tag = (e.target && e.target.tagName) || '';
    var inField = (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' ||
                   (e.target && e.target.isContentEditable));

    // F12 — devtools
    if (k === 'f12') { e.preventDefault(); return; }

    // Ctrl/Cmd + Shift + I/J/C — devtools / inspect
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i','j','c'].indexOf(k) !== -1) {
      e.preventDefault(); return;
    }

    // Ctrl/Cmd + U — view source
    if ((e.ctrlKey || e.metaKey) && k === 'u') { e.preventDefault(); return; }

    // Ctrl/Cmd + S — save page
    if ((e.ctrlKey || e.metaKey) && k === 's') { e.preventDefault(); return; }

    // Ctrl/Cmd + P — print
    if ((e.ctrlKey || e.metaKey) && k === 'p') { e.preventDefault(); return; }

    // Ctrl/Cmd + A — select all (only outside fields)
    if (!inField && (e.ctrlKey || e.metaKey) && k === 'a') { e.preventDefault(); return; }

    // Ctrl/Cmd + C / X — copy/cut (only outside fields)
    if (!inField && (e.ctrlKey || e.metaKey) && (k === 'c' || k === 'x')) {
      e.preventDefault(); return;
    }
  });

  // Block drag-to-copy outside form fields
  document.addEventListener('dragstart', function (e) {
    var tag = (e.target && e.target.tagName) || '';
    if (tag === 'INPUT' || tag === 'TEXTAREA') return;
    e.preventDefault();
  });

  // Block copy event outside form fields
  document.addEventListener('copy', function (e) {
    var tag = (e.target && e.target.tagName) || '';
    var inField = (tag === 'INPUT' || tag === 'TEXTAREA' ||
                   (e.target && e.target.isContentEditable));
    if (inField) return;
    e.preventDefault();
  });
})();
</script>
