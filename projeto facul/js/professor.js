// Botão de logout
document.getElementById('logoutBtn').addEventListener('click', function () {
    alert('Você saiu da conta.');
    window.location.href = 'index.html';
});

// Mostrar/ocultar menu lateral
const sidebar = document.getElementById('sidebar');
const container = document.querySelector('.container');

document.getElementById('menu-toggle').addEventListener('click', function () {
    sidebar.classList.toggle('hidden');
    container.classList.toggle('full-width');
});
