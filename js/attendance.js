/**
 * Attendance Marking JavaScript
 * Handles bulk actions and form interactions
 */

function markAll(status) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radios.forEach(radio => {
        radio.checked = true;
    });
}

// Confirm before submitting
document.getElementById('attendanceForm')?.addEventListener('submit', function (e) {
    if (!confirm('Are you sure you want to save this attendance? This will update existing records if any.')) {
        e.preventDefault();
    }
});

// Highlight changed rows
document.querySelectorAll('.attendance-item input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const item = this.closest('.attendance-item');
        item.style.background = 'rgba(99, 102, 241, 0.1)';
        item.style.borderColor = 'var(--primary-color)';
    });
});
