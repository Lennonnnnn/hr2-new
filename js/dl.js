document.querySelectorAll('[id^="download-certificate-"]').forEach(button => {
    button.addEventListener('click', () => {
        const employeeName = button.parentElement.previousElementSibling.querySelector('h3').innerText;
        const employeeRole = button.parentElement.previousElementSibling.querySelector('p:nth-of-type(1)').innerText;
        const employeeDepartment = button.parentElement.previousElementSibling.querySelector('p:nth-of-type(2)').innerText;
        const date = button.parentElement.querySelector('span').innerText || new Date().toLocaleDateString();

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setFontSize(22);
        doc.text("Certificate of Recognition", 105, 20, { align: "center" });
        doc.setFontSize(16);
        doc.text(`Awarded to: ${employeeName}`, 10, 40);
        doc.text(`Role: ${employeeRole}`, 10, 50);
        doc.text(`Department: ${employeeDepartment}`, 10, 60);
        doc.text(`Date: ${date}`, 10, 70);
        doc.text("In recognition of outstanding contributions to the company.", 10, 90);
        doc.text("Your dedication, hard work, and commitment to excellence have not gone unnoticed.", 10, 100);
        doc.text("We are grateful for your service and look forward to your continued success.", 10, 110);
        
        doc.save(`${employeeName}_Certificate.pdf`);
    });
});
