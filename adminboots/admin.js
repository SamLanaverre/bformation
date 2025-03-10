function showAddUserForm() {
    const form = document.getElementById('add-user-form');
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
    } else {
        form.classList.add('hidden');
    }
}
