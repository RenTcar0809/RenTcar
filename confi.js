
document.querySelectorAll('.settings-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Quitar active de todos los links
        document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
        this.classList.add('active');
        
        // Ocultar todas las secciones
        document.querySelectorAll('.settings-section').forEach(sec => sec.style.display = 'none');
        
        // Mostrar la sección seleccionada
        const target = this.getAttribute('href');
        document.querySelector(target).style.display = 'block';
    });
});
