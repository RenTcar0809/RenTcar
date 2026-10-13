let dataTransfer = new DataTransfer();
const input = document.getElementById('fotos');
const label = document.querySelector('.upload-label');
const container = document.getElementById('preview-container');
const btnSubmit = document.querySelector('.btn-submit-premium');

input.addEventListener('change', function(event) {
    const files = event.target.files;

    for (let i = 0; i < files.length; i++) {
        if (dataTransfer.items.length >= 4) {
            alert("Has alcanzado el límite máximo de 4 imágenes.");
            break;
        }

        const file = files[i];
        dataTransfer.items.add(file);

        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                <span style="flex-grow: 1; margin-left: 10px;">${file.name}</span>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    }

    input.files = dataTransfer.files;

    if (dataTransfer.items.length >= 4) {
        label.style.display = 'none';
        btnSubmit.disabled = false;
        btnSubmit.style.opacity = '1';
    }
});