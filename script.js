(() => {
  const form = document.getElementById('produtoForm');
  if (form) {
    form.addEventListener('submit', (event) => {
      const data = document.getElementById('datavencimento');
      if (data && data.value && data.value < new Date().toISOString().slice(0, 10)) {
        data.setCustomValidity('A data de vencimento não pode estar no passado.');
      } else if (data) {
        data.setCustomValidity('');
      }
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  }
  const input = document.getElementById('fotos');
  const preview = document.getElementById('preview');
  if (input && preview) {
    input.addEventListener('change', () => {
      preview.innerHTML = '';
      [...input.files].forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.alt = file.name;
          img.title = file.name;
          preview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });
  }
})();
