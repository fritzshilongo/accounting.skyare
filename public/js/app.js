document.addEventListener('DOMContentLoaded', function () {
    function toggleInterestFields() {
        var apply = document.getElementById('apply_interest');
        var fields = document.getElementById('interest_fields');
        if (!apply || !fields) return;
        fields.style.display = apply.value === '1' ? 'block' : 'none';
    }

    var applyInterest = document.getElementById('apply_interest');
    if (applyInterest) {
        applyInterest.addEventListener('change', toggleInterestFields);
        toggleInterestFields();
    }

    var toggleCreditBtn = document.getElementById('toggleCreditForm');
    var creditForm = document.getElementById('creditForm');
    if (toggleCreditBtn && creditForm) {
        toggleCreditBtn.addEventListener('click', function (e) {
            e.preventDefault();
            creditForm.classList.toggle('hidden');
        });
    }
});
