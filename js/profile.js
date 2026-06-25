$(document).ready(function() {
    const token = localStorage.getItem('token');
    const feedbackAlert = $('#feedback');
    const profileForm = $('#profile-form');
    const submitBtn = $('#submit-btn');
    const logoutBtn = $('#logout-btn');

    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    fetchProfileData();

    profileForm.on('submit', function(e) {
        e.preventDefault();
        
        feedbackAlert.removeClass('error success').hide().text('');

        const age = parseInt($('#age').val(), 10);
        const dob = $('#dob').val();
        const contact = $.trim($('#contact').val());
        const address = $.trim($('#address').val());

        setSubmitting(true);

        $.ajax({
            url: 'php/profile.php',
            type: 'POST',
            dataType: 'json',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            data: {
                age: age,
                dob: dob,
                contact: contact,
                address: address
            },
            success: function(response) {
                if (response.success) {
                    showFeedback('Profile updated successfully.', 'success');
                } else {
                    showFeedback(response.message || 'Failed to update profile.', 'error');
                }
                setSubmitting(false);
            },
            error: function(xhr) {
                setSubmitting(false);
                if (xhr.status === 401) {
                    localStorage.removeItem('token');
                    window.location.href = 'login.html?session_expired=1';
                    return;
                }
                let errorMsg = 'Failed to update profile. Server error.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showFeedback(errorMsg, 'error');
            }
        });
    });

    logoutBtn.on('click', function() {
        $.ajax({
            url: 'php/profile.php?action=logout',
            type: 'POST',
            dataType: 'json',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            complete: function() {
                localStorage.removeItem('token');
                window.location.href = 'login.html';
            }
        });
    });

    function fetchProfileData() {
        $.ajax({
            url: 'php/profile.php',
            type: 'GET',
            dataType: 'json',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    $('#username').val(data.username || '');
                    $('#email').val(data.email || '');

                    $('#age').val(data.age || '');
                    $('#dob').val(data.dob || '');
                    $('#contact').val(data.contact || '');
                    $('#address').val(data.address || '');
                } else {
                    showFeedback('Failed to load profile details.', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    localStorage.removeItem('token');
                    window.location.href = 'login.html?session_expired=1';
                    return;
                }
                showFeedback('Failed to connect to backend server.', 'error');
            }
        });
    }

    function showFeedback(message, type) {
        feedbackAlert.removeClass('error success').addClass(type).text(message).fadeIn();
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    }

    function setSubmitting(isSubmitting) {
        if (isSubmitting) {
            submitBtn.prop('disabled', true).text('Saving Details...');
        } else {
            submitBtn.prop('disabled', false).text('Save Profile Details');
        }
    }
});
