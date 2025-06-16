function toggleZaloOptions(show) {
    const options = document.getElementById('zalo-options');
    const toggle = document.getElementById('zalo-toggle');

    if (options && toggle) {
        options.classList.toggle('active', show);
        toggle.style.display = show ? 'none' : 'block';
    }
}
