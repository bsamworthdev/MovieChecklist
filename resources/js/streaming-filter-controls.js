module.exports = function initializeStreamingFilterControls() {
    const group = document.getElementById('streamCard');
    const selectAll = document.getElementById('streaming-select-all');
    const unselectAll = document.getElementById('streaming-unselect-all');
    if (!group || !selectAll || !unselectAll) return;

    const services = [...group.querySelectorAll('input[type="checkbox"][id$="_only_checkbox"]')];
    const setSelection = checked => {
        services.forEach(checkbox => { checkbox.checked = checked; });
        // Submit the whole selection once, rather than once per service.
        window.changeSelection();
    };
    selectAll.addEventListener('click', () => setSelection(true));
    unselectAll.addEventListener('click', () => setSelection(false));
};
