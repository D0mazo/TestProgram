'use strict';

function selectOption(radio) {
    document.querySelectorAll('.option-btn').forEach(function (btn) {
        btn.classList.remove('selected');
    });
    radio.closest('.option-btn').classList.add('selected');

    var btn = document.getElementById('submitBtn');
    if (btn) btn.disabled = false;
}

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('quizForm');
    if (!form) return;

    form.addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Saving…';
        }
    });
});