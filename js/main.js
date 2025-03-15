document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('open-btn');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.querySelector('.close-btn');

    openBtn.addEventListener('click', function() {
        sidebar.classList.add('open');
        document.getElementById('main-content').style.marginLeft = '250px';
        openBtn.style.display = 'none'; // Hide open button when sidebar is open
    });

    closeBtn.addEventListener('click', function() {
        sidebar.classList.remove('open');
        document.getElementById('main-content').style.marginLeft = '0';
        openBtn.style.display = 'block'; // Show open button when sidebar is closed
    });
});
