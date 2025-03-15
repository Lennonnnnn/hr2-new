/*!
   /* Start Bootstrap - SB Admin v7.0.7 (https://startbootstrap.com/template/sb-admin) */
   /* Copyright 2013-2023 Start Bootstrap */
   /* Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
    */
    // 
// Scripts
// 

//FOR LOGOUT CONFIRMATION
function confirmLogout(event) {
    event.preventDefault();
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "../admin/logout.php";
    }
}

window.addEventListener('DOMContentLoaded', event => {

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        // Uncomment Below to persist sidebar toggle between refreshes
         if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
            document.body.classList.toggle('sb-sidenav-toggled');
         }
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

     document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('sidenavAccordion').classList.toggle('collapsed');
        });

});


document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.loading');

    // Loop through each button and add a click event listener
    buttons.forEach(button => {
        button.addEventListener('click', function(event) {
            // Show the loading modal
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();

            // Disable the button to prevent multiple clicks
            this.classList.add('disabled');

            // Handle form submission buttons
            if (this.closest('form')) {
                event.preventDefault();  // Prevent the default form submit

                // Optionally, submit the form after a short delay
                setTimeout(() => {
                    this.closest('form').submit();
                }, 1500); // Adjust delay as necessary
            } 
            // Handle links
            else if (this.tagName.toLowerCase() === 'a') {
                event.preventDefault();  // Prevent the default link behavior

                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = this.href;
                }, 1500); // Adjust delay as necessary
            }
        });
    });
});














