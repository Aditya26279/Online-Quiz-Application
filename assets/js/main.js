// App Logic and Client-Side Validation

document.addEventListener('DOMContentLoaded', () => {

    // 1. Fade-in elements subtly on load
    const cards = document.querySelectorAll('.card, .stat-card, .quiz-card, .question-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animation = `fadeIn 0.5s ease-out forwards ${index * 0.1}s`;
    });

    // 2. Registration Form Validation
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        const pass = registerForm.querySelector('#password');
        const confirmPass = registerForm.querySelector('#confirm_password');

        registerForm.addEventListener('submit', (e) => {
            if (pass.value !== confirmPass.value) {
                e.preventDefault(); // Stop submission

                // Add error styling
                confirmPass.classList.add('input-error');

                // Show or create error message
                let errorMsg = confirmPass.parentElement.querySelector('.error-message');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message visible';
                    errorMsg.textContent = 'Passwords do not match.';
                    confirmPass.parentElement.appendChild(errorMsg);
                } else {
                    errorMsg.classList.add('visible');
                    errorMsg.textContent = 'Passwords do not match.';
                }
            } else {
                confirmPass.classList.remove('input-error');
                const errorMsg = confirmPass.parentElement.querySelector('.error-message');
                if (errorMsg) errorMsg.classList.remove('visible');

                // Add loading state to button
                const btn = registerForm.querySelector('button[type="submit"]');
                if (btn) btn.classList.add('btn-loading');
            }
        });

        // Clear error on input
        confirmPass.addEventListener('input', () => {
            confirmPass.classList.remove('input-error');
            const errorMsg = confirmPass.parentElement.querySelector('.error-message');
            if (errorMsg) errorMsg.classList.remove('visible');
        });
    }

    // 3. General form submit loading state
    const generalForms = document.querySelectorAll('form:not([action="register.php"])');
    generalForms.forEach(form => {
        form.addEventListener('submit', function () {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && this.checkValidity()) {
                btn.classList.add('btn-loading');
            }
        });
    });

});
