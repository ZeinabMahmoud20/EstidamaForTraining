(function () {
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('backdrop');
  var toggle = document.getElementById('menuToggle');
  function setMenu(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('is-open', open);
    backdrop.classList.toggle('is-open', open);
  }
  if (toggle) toggle.addEventListener('click', function () { setMenu(!sidebar.classList.contains('is-open')); });
  if (backdrop) backdrop.addEventListener('click', function () { setMenu(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });

  // accessibility: font resize (remembered per browser)
  var KEY = 'estidama.fontScale', root = document.documentElement;
  var scale = parseFloat(localStorage.getItem(KEY) || '1');
  function apply() { root.style.fontSize = (16 * scale) + 'px'; }
  apply();
  document.querySelectorAll('[data-font]').forEach(function (b) {
    b.addEventListener('click', function () {
      var d = b.getAttribute('data-font');
      scale = d === 'reset' ? 1 : Math.min(1.3, Math.max(0.85, scale + (d === 'up' ? 0.05 : -0.05)));
      localStorage.setItem(KEY, scale); apply();
    });
  });

  // confirm before destructive actions: <form data-confirm="رسالة">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // repeatable form rows: [data-repeat-group] > [data-repeat] + <template data-repeat-template> + [data-repeat-add]
  document.querySelectorAll('[data-repeat-group]').forEach(function (group) {
    var list = group.querySelector('[data-repeat]');
    var template = group.querySelector('template[data-repeat-template]');
    var addButton = group.querySelector('[data-repeat-add]');
    if (!list) return;

    var counter = list.querySelectorAll('[data-repeat-row]').length;

    if (addButton && template) {
      addButton.addEventListener('click', function () {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(counter++)).trim();
        var row = wrapper.firstElementChild;
        if (!row) return;
        list.appendChild(row);
        var firstField = row.querySelector('input:not([type=hidden]), select, textarea');
        if (firstField) firstField.focus();
      });
    }

    list.addEventListener('click', function (event) {
      var removeButton = event.target.closest('[data-repeat-remove]');
      if (!removeButton) return;
      var row = removeButton.closest('[data-repeat-row]');
      if (row) row.remove();
    });

    var form = group.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        list.querySelectorAll('[data-repeat-row]').forEach(function (row) {
          var hasValue = Array.prototype.some.call(
            row.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), select, textarea'),
            function (field) { return field.value.trim() !== ''; }
          );
          if (!hasValue) row.remove();
        });
      });
    }
  });
})();
