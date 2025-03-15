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
                }, 500); // Adjust delay as necessary
            } 
            // Handle links
            else if (this.tagName.toLowerCase() === 'a') {
                event.preventDefault();  // Prevent the default link behavior

                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = this.href;
                }, 500); // Adjust delay as necessary
            }
        });
    });
});