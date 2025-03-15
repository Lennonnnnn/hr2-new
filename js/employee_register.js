//FOR REGISTRATION FORM
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registrationForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            let formData = new FormData(form);
            
            fetch('../db/registeremployee_db.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  // Ensure response is JSON
            .then(data => {
                let feedback = document.getElementById('form-feedback');
                if (data.error) {
                    feedback.className = 'alert alert-danger text-center';
                    feedback.textContent = data.error;
                    feedback.style.display = 'block';
                } else if (data.success) {
                    feedback.className = 'alert alert-success text-center';
                    feedback.textContent = data.success;
                    feedback.style.display = 'block';
                    form.reset(); // Optionally reset the form after successful registration
                }
            })
            .catch(error => {
                let feedback = document.getElementById('form-feedback');
                feedback.className = 'alert alert-danger text-center';
                feedback.textContent = 'An unexpected error occurred.';
                feedback.style.display = 'block';
            });
        });
    }
});