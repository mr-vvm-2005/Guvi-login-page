$(document).ready(function() {
    const registerForm = $('#register-form');
    const feedbackAlert = $('#feedback');
    const submitBtn = $('#submit-btn');

    registerForm.on('submit', function(e) {
        e.preventDefault();

        feedbackAlert.removeClass('error success').hide().text('');

        const username = $.trim($('#username').val());
        const email = $.trim($('#email').val());
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        if (!username || !email || !password || !confirmPassword) {
            showFeedback('All fields are required.', 'error');
            return;
        }

        if (password.length < 6) {
            showFeedback('Password must be at least 6 characters long.', 'error');
            return;
        }

        if (password !== confirmPassword) {
            showFeedback('Passwords do not match.', 'error');
            return;
        }

        setSubmitting(true);

        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            dataType: 'json',
            data: {
                username: username,
                email: email,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    showFeedback(response.message || 'Registration successful! Redirecting to login...', 'success');
                    setTimeout(function() {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showFeedback(response.message || 'An error occurred during registration.', 'error');
                    setSubmitting(false);
                }
            },
            error: function(xhr) {
                let errorMsg = 'Server error. Please try again later.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showFeedback(errorMsg, 'error');
                setSubmitting(false);
            }
        });
    });

    function showFeedback(message, type) {
        feedbackAlert.addClass(type).text(message).fadeIn();
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    }

    function setSubmitting(isSubmitting) {
        if (isSubmitting) {
            submitBtn.prop('disabled', true).text('Creating Account...');
        } else {
            submitBtn.prop('disabled', false).text('Sign Up');
        }
    }
});
