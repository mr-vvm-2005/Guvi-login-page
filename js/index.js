$(document).ready(function() {
    const token = localStorage.getItem('token');
    if (token) {
        $('#landing-login-btn').addClass('d-none');
        $('#landing-register-btn').addClass('d-none');
        $('#landing-profile-btn').removeClass('d-none');
    }
});
