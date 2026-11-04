(() => {
    const section = document.getElementById('uk-catalogues');
    if (!section) return;
    const media = section.dataset.media || 'movie';
    const titles = media === 'tv' ? 'TV shows' : 'movies';
    const buttons = [...section.querySelectorAll('[data-catalogue-update]')];
    const movieButton = document.getElementById('update-movies-button');
    const thumbnailButton = document.getElementById('generate-movie-thumbnails-button') || document.getElementById('generate-tv-thumbnails-button');
    let running = false;
    let active = null;
    const notify = (kind, text) => {
        const box = document.getElementById('movie-import-notification');
        box.className = `movie-import-notification alert alert-${kind}`;
        box.setAttribute('role', kind === 'danger' ? 'alert' : 'status');
        box.setAttribute('aria-live', kind === 'danger' ? 'assertive' : 'polite');
        document.getElementById('movie-import-notification-text').textContent = text;
        box.hidden = false;
    };
    const busy = value => {
        running = value;
        buttons.forEach(button => { button.disabled = value || button.dataset.configured !== 'true'; });
        if (movieButton) movieButton.disabled = value;
        if (thumbnailButton) thumbnailButton.disabled = value;
    };
    const post = async (url, data) => {
        const response = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(data),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(result.errors ? Object.values(result.errors).flat().join('\n')
                : result.message || 'The catalogue request failed. Please try again.');
            error.status = response.status;
            error.restart = Boolean(result.errors && result.errors.token);
            throw error;
        }
        return result;
    };
    const progress = (result, label) => {
        document.getElementById('catalogue-progress').hidden = false;
        document.getElementById('catalogue-progress-text').textContent = `Checking ${label} UK: ${result.processed} of ${result.total} ${titles}.`;
        const percent = Math.round(100 * result.processed / result.total);
        const bar = document.getElementById('catalogue-progress-bar');
        bar.style.width = `${percent}%`;
        bar.setAttribute('aria-valuenow', percent);
    };
    buttons.forEach(button => button.addEventListener('click', async () => {
        if (running || (movieButton && movieButton.disabled)) return;
        busy(true);
        const service = button.dataset.catalogueUpdate;
        const label = button.dataset.label;
        document.getElementById('catalogue-skipped').hidden = true;
        notify('info', `Checking ${label} UK availability… Keep this page open until the update finishes.`);
        try {
            if (!active || active.service !== service) {
                active = null;
                buttons.forEach(other => { other.querySelector('[data-catalogue-label]').textContent = `Update ${other.dataset.label} UK`; });
                document.getElementById('catalogue-progress').hidden = true;
                active = await post(section.dataset.startUrl, { service, media });
            }
            progress(active, label);
            while (!active.complete) {
                active = await post(section.dataset.stepUrl, { token: active.token, processed: active.processed });
                progress(active, label);
                if (!active.complete) await new Promise(resolve => setTimeout(resolve, 500));
            }
            const result = active.result;
            let text = `${label} UK successfully updated. ${result.verified} ${titles} checked; ${result.available} available. ${result.added} newly available, ${result.removed} removed.`;
            if (result.skipped) text += ` ${result.skipped} ${titles} could not be identified; their existing availability was preserved.`;
            if (result.duplicates_removed) text += ` ${result.duplicates_removed} duplicate availability rows removed.`;
            notify('success', text);
            const list = document.getElementById('catalogue-skipped-list');
            list.replaceChildren();
            active.skipped.forEach(movie => {
                const item = document.createElement('li');
                item.textContent = `${movie.name}${movie.imdb_id ? ` (${movie.imdb_id})` : ''}: ${movie.reason}`;
                list.append(item);
            });
            document.getElementById('catalogue-skipped-title').textContent = `${result.skipped} ${titles} were skipped`;
            document.getElementById('catalogue-skipped').hidden = !result.skipped;
            active = null;
            button.querySelector('[data-catalogue-label]').textContent = `Update ${label} UK`;
        } catch (error) {
            notify('danger', `${label} UK update failed: ${error.message}`);
            if (error.restart || [401, 403, 419].includes(error.status)) active = null;
            button.querySelector('[data-catalogue-label]').textContent = active ? `Retry ${label} UK update` : `Update ${label} UK`;
        } finally {
            busy(false);
        }
    }));
})();
