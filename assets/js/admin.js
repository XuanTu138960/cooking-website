document.querySelectorAll('form[action*="admin_action.php"] button[value="rejected"]').forEach((button) => {
    button.addEventListener('click', (event) => {
        if (!window.confirm('Bạn có chắc muốn từ chối công thức này?')) event.preventDefault();
    });
});
