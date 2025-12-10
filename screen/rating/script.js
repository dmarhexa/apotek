document.addEventListener('DOMContentLoaded', () => {
    const ratingForm = document.getElementById('ratingForm');
    const alertBox = document.getElementById('alert-box');

    ratingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Get Form Data
        const formData = new FormData(ratingForm);
        const submitBtn = ratingForm.querySelector('.btn-submit');
        const originalBtnText = submitBtn.textContent;

        // Basic validation for stars
        const stars = formData.get('bintang');
        if (!stars) {
            showAlert('Mohon pilih jumlah bintang terlebih dahulu.', 'error');
            return;
        }

        // Disable button loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengirim...';

        try {
            const response = await fetch('../../api/submit_rating.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                showAlert(result.message, 'success');
                ratingForm.reset();
            } else {
                showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Terjadi kesalahan koneksi. Silakan coba lagi.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        }
    });

    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.className = 'alert'; // Reset classes
        alertBox.classList.add(type === 'success' ? 'alert-success' : 'alert-error');
        alertBox.style.display = 'block';

        // Auto hide after 5 seconds
        setTimeout(() => {
            alertBox.style.display = 'none';
        }, 5000);
    }
});
