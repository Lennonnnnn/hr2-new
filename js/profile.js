//EDIT INFORMATION
let originalValues = []; // Declare this outside the event listener

document.getElementById('editButton').addEventListener('click', function() {
    const inputs = document.querySelectorAll('#infoForm input, #infoForm textarea');
    const saveButton = document.querySelector('#saveButton'); // Target the save button

    // Check if we are entering edit mode
    if (saveButton.classList.contains('d-none')) {
        // Store the original values
        originalValues = Array.from(inputs).map(input => input.value);
        
        // Enable editing
        inputs.forEach(input => {
            input.readOnly = false; // Make inputs editable
        });
        
        saveButton.classList.remove('d-none'); // Show save button
        this.textContent = 'Cancel'; // Change button text to 'Cancel'

        // Change button to outline-secondary (cancel) style
        this.classList.remove('btn-primary'); // Remove primary button style
        this.classList.add('btn-outline-secondary'); // Add outline-secondary style for 'Cancel'

        this.style.color = 'white';
    } else {
        // If canceling, reset to original values
        inputs.forEach((input, index) => {
            input.value = originalValues[index]; // Restore original value
            input.readOnly = true; // Keep inputs readonly
        });
        
        saveButton.classList.add('d-none'); // Hide save button
        this.textContent = 'Update Information'; // Change button text back

        // Restore the original button design
        this.classList.remove('btn-outline-secondary'); // Remove outline-secondary style
        this.classList.add('btn-primary'); // Restore primary style for 'Update Information'
    }
});


//EDIT INFO (END)



// Trigger file input when user clicks on "Change Profile Picture"
        document.getElementById('changePictureOption').addEventListener('click', function() {
            document.getElementById('profilePictureInput').click();
        });



            // Function to show a preview of the selected profile picture
    function readImage(input, previewElementId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById(previewElementId).src = e.target.result;
                document.getElementById(previewElementId).style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]); // Convert image to base64 data
        }
    }

    // Show confirmation modal with preview
    function showConfirmationModal() {
        readImage(document.getElementById('profilePictureInput'), 'profilePicturePreview');  // Display image preview
        readImage(document.getElementById('profilePictureInput'), 'modalProfilePicturePreview'); // Preview in modal

        var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        confirmationModal.show();  // Show confirmation modal
    }

    // Submit the form after confirmation
    function submitProfilePictureForm() {
        document.getElementById('profilePictureForm').submit();  // Submit form
    }

        


        