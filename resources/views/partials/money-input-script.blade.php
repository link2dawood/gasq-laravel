<script>
(function () {
    // Formats any <input data-money> as 2,500,000.00 while typing. The "$" lives in the
    // input-group prefix; the server strips commas before validating.
    function clean(value) {
        value = String(value).replace(/[^\d.]/g, '');
        var dot = value.indexOf('.');
        if (dot !== -1) {
            value = value.slice(0, dot + 1) + value.slice(dot + 1).replace(/\./g, '').slice(0, 2);
        }
        return value;
    }

    function group(value) {
        var parts = value.split('.');
        parts[0] = parts[0].replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.length > 1 ? parts[0] + '.' + parts[1] : parts[0];
    }

    function liveFormat(input) {
        var caret = input.selectionStart;
        var digitsBeforeCaret = clean(input.value.slice(0, caret)).length;
        var formatted = group(clean(input.value));
        input.value = formatted;

        var pos = 0, seen = 0;
        while (pos < formatted.length && seen < digitsBeforeCaret) {
            if (/[\d.]/.test(formatted.charAt(pos))) { seen++; }
            pos++;
        }
        try { input.setSelectionRange(pos, pos); } catch (e) {}
    }

    function finalFormat(input) {
        var value = clean(input.value);
        if (value === '' || value === '.') { input.value = ''; return; }
        var number = parseFloat(value);
        input.value = number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.querySelectorAll('input[data-money]').forEach(function (input) {
        if (input.value) { finalFormat(input); }
        input.addEventListener('input', function () { liveFormat(input); });
        input.addEventListener('blur', function () { finalFormat(input); });
    });
})();
</script>
