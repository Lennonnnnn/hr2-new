// Add event listener to the form submit button
document.getElementById('leave-request-form').addEventListener('submit', function(event) {
    event.preventDefault(); // prevent default form submission
    // Add your form submission logic here
    console.log('Form submitted!');
});