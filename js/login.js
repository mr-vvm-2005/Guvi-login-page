$(document).ready(function() {
    const loginForm = $('#login-form');
    const feedbackAlert = $('#feedback');
    const submitBtn = $('#submit-btn');

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('session_expired')) {
        showFeedback('Your session has expired. Please sign in again.', 'error');
    }

    if (localStorage.getItem('token') && !urlParams.get('session_expired')) {
        window.location.href = 'profile.html';
    }

    loginForm.on('submit', function(e) {
        e.preventDefault();

        feedbackAlert.removeClass('error success').hide().text('');

        const identity = $.trim($('#identity').val());
        const password = $('#password').val();

        if (!identity || !password) {
            showFeedback('Please enter both identity (username/email) and password.', 'error');
            return;
        }

        setSubmitting(true);

        $.ajax({
            url: 'php/login.php',
            type: 'POST',
            dataType: 'json',
            data: {
                identity: identity,
                password: password
            },
            success: function(response) {
                if (response.success && response.data && response.data.token) {
                    localStorage.setItem('token', response.data.token);
                    showFeedback('Login successful! Redirecting...', 'success');
                    setTimeout(function() {
                        window.location.href = 'profile.html';
                    }, 1000);
                } else {
                    showFeedback(response.message || 'Invalid credentials.', 'error');
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
    }

    function setSubmitting(isSubmitting) {
        if (isSubmitting) {
            submitBtn.prop('disabled', true).text('Signing In...');
        } else {
            submitBtn.prop('disabled', false).text('Sign In');
        }
    }
});
