</main>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== Show/Hide Password =====
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// ===== Submit loading state =====
document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn  = document.getElementById('loginBtn');
    const spin = document.getElementById('loginSpinner');
    const txt  = document.getElementById('loginBtnText');
    if (btn && spin && txt) {
        btn.disabled = true;
        spin.classList.remove('d-none');
        txt.textContent = 'Sedang masuk...';
    }
});
</script>
</body>
</html>