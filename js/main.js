document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('#login-form');
    const signupForm = document.querySelector('#signup-form');

    // Helper function to show error
    const showError = (inputId, message) => {
        const input = document.getElementById(inputId);
        const errorDiv = document.querySelector(`[data-error-for="${inputId}"]`);
        input.parentElement.classList.add('error');
        errorDiv.textContent = message;
    };

    // Helper function to clear error
    const clearError = (inputId) => {
        const input = document.getElementById(inputId);
        const errorDiv = document.querySelector(`[data-error-for="${inputId}"]`);
        if (input.parentElement.classList.contains('error')) {
            input.parentElement.classList.remove('error');
            errorDiv.textContent = '';
        }
    };

    // Email validation regex
    const isEmail = (email) => {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    };

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            let isValid = true;

            const email = document.getElementById('email');
            const password = document.getElementById('password');

            // Clear previous errors
            clearError('email');
            clearError('password');

            // Validate Email
            if (email.value.trim() === '') {
                showError('email', 'O campo E-mail é obrigatório.');
                isValid = false;
            } else if (!isEmail(email.value.trim())) {
                showError('email', 'Por favor, insira um e-mail válido.');
                isValid = false;
            }

            // Validate Password
            if (password.value.trim() === '') {
                showError('password', 'O campo Senha é obrigatório.');
                isValid = false;
            }

            if (isValid) {
                console.log('Login form submitted');
                // Here you would typically send the data to a server
                loginForm.submit();
            }
        });
    }

    if (signupForm) {
        signupForm.addEventListener('submit', (e) => {
            e.preventDefault();
            let isValid = true;

            const fullname = document.getElementById('fullname');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm-password');

            // Clear previous errors
            clearError('fullname');
            clearError('email');
            clearError('password');
            clearError('confirm-password');

            // Validate Full Name
            if (fullname.value.trim() === '') {
                showError('fullname', 'O campo Nome Completo é obrigatório.');
                isValid = false;
            }

            // Validate Email
            if (email.value.trim() === '') {
                showError('email', 'O campo E-mail é obrigatório.');
                isValid = false;
            } else if (!isEmail(email.value.trim())) {
                showError('email', 'Por favor, insira um e-mail válido.');
                isValid = false;
            }

            // Validate Password
            if (password.value.trim() === '') {
                showError('password', 'O campo Senha é obrigatório.');
                isValid = false;
            }

            // Validate Confirm Password
            if (confirmPassword.value.trim() === '') {
                showError('confirm-password', 'O campo Confirme sua senha é obrigatório.');
                isValid = false;
            } else if (password.value.trim() !== confirmPassword.value.trim()) {
                showError('confirm-password', 'As senhas não correspondem.');
                isValid = false;
            }

            if (isValid) {
                console.log('Signup form submitted');
                // Here you would typically send the data to a server
                signupForm.submit();
            }
        });
    }
});