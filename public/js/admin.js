(function () {
  const body = document.body;

  // Menú lateral en mòbil
  document.querySelectorAll('[data-sidebar-toggle]').forEach((el) =>
    el.addEventListener('click', () => body.classList.toggle('sidebar-open'))
  );
  document.querySelectorAll('[data-sidebar-close]').forEach((el) =>
    el.addEventListener('click', () => body.classList.remove('sidebar-open'))
  );

  // Confirmació abans d'accions destructives
  document.querySelectorAll('form[data-confirm]').forEach((form) =>
    form.addEventListener('submit', (e) => {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    })
  );

  // Filtres que s'apliquen en canviar
  document.querySelectorAll('select[data-autosubmit]').forEach((select) =>
    select.addEventListener('change', () => select.form.submit())
  );

  // Selector de color amb el codi hexadecimal a la vista
  document.querySelectorAll('input[type=color][data-hex]').forEach((input) => {
    const label = document.getElementById(input.dataset.hex);
    const sync = () => { if (label) label.textContent = input.value.toUpperCase(); };
    input.addEventListener('input', sync);
    sync();
  });

  // Previsualització d'imatges abans de pujar-les
  document.querySelectorAll('input[type=file][data-preview]').forEach((input) =>
    input.addEventListener('change', () => {
      const img = document.getElementById(input.dataset.preview);
      const file = input.files && input.files[0];
      if (img && file) {
        img.src = URL.createObjectURL(file);
        img.hidden = false;
      }
    })
  );

  // Els avisos desapareixen sols
  document.querySelectorAll('.alert[data-autohide]').forEach((alert) =>
    setTimeout(() => {
      alert.style.transition = 'opacity .4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 450);
    }, 5000)
  );
})();
