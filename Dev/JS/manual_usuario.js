document.addEventListener('DOMContentLoaded', function() {
    const btnManual = document.getElementById('btn-manual-usuario');
    const modalManual = document.getElementById('modalManualUsuario');

    if (btnManual && modalManual) {
        const modal = new bootstrap.Modal(modalManual);
        const modalBody = document.getElementById('manual-content-body');

        btnManual.addEventListener('click', function() {
            const contentContainer = document.getElementById('manual-content-container');
            
            if (contentContainer) 
                modalBody.innerHTML = contentContainer.innerHTML;
            else 
                modalBody.innerHTML = '<p>O manual para esta página ainda não foi escrito.</p>';
            
            modal.show();
        });
    }
});