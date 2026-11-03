(() => {
    const dialog = document.getElementById('movie-import-dialog');
    const openButton = document.getElementById('update-movies-button');
    const thumbnailButton = document.getElementById('generate-movie-thumbnails-button');
    if (!dialog || !openButton) return;
    const element = id => document.getElementById(`movie-import-${id}`);
    let token = null;
    let busy = false;
    const message = (id, text) => {
        element(id).textContent = text;
        element(id).hidden = !text;
    };
    const notify = (type, text) => {
        const notification = element('notification');
        notification.className = `movie-import-notification alert alert-${type}`;
        notification.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        notification.setAttribute('aria-live', type === 'danger' ? 'assertive' : 'polite');
        element('notification-text').textContent = text;
        notification.hidden = false;
    };
    element('dismiss').addEventListener('click', () => { element('notification').hidden = true; });
    const setBusy = value => {
        busy = value;
        openButton.disabled = value;
        if (thumbnailButton) thumbnailButton.disabled = value;
        document.querySelectorAll('[data-catalogue-update]').forEach(button => {
            button.disabled = value || button.dataset.configured !== 'true';
        });
        ['preview', 'apply', 'close', 'html'].forEach(id => { element(id).disabled = value; });
        dialog.querySelectorAll('input').forEach(input => { input.disabled = value; });
    };
    const request = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join('\n') : data.message;
            throw new Error(errors || 'The request failed. Refresh the page and try again.');
        }
        return data;
    };
    if (thumbnailButton) thumbnailButton.addEventListener('click', async () => {
        if (busy) return;
        setBusy(true);
        notify('info', 'Generating missing thumbnails…');
        try {
            const result = await request(thumbnailButton.dataset.url, {});
            notify('success', `Successfully generated ${result.generated} thumbnails. ${result.remaining} movies with artwork still need thumbnails.`);
        } catch (error) {
            notify('danger', `Thumbnail generation failed: ${error.message}`);
        } finally {
            setBusy(false);
        }
    });
    openButton.addEventListener('click', () => {
        if (busy) return;
        dialog.showModal();
        if (!token) element('html').focus();
    });
    element('close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    element('html').addEventListener('input', () => {
        token = null;
        element('review').hidden = true;
        message('error', '');
        message('status', '');
    });
    element('preview').addEventListener('click', async () => {
        if (busy) return;
        token = null;
        element('review').hidden = true;
        message('error', '');
        if (!element('html').value.trim()) {
            message('error', 'Paste the complete HTML from IMDb’s Top 250 page first.');
            element('html').focus();
            return;
        }
        message('status', 'Reading and checking the pasted Top 250…');
        setBusy(true);
        try {
            const data = await request(dialog.dataset.previewUrl, { html: element('html').value });
            token = data.token;
            element('rows').replaceChildren();
            data.unmatched.forEach(movie => {
                const row = document.createElement('tr');
                const position = document.createElement('td');
                position.textContent = `#${movie.position}`;
                const details = document.createElement('td');
                const link = document.createElement('a');
                link.href = `https://www.imdb.com/title/${movie.imdb_id}/`;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = `${movie.name} (${movie.year})`;
                const reason = document.createElement('small');
                reason.className = 'd-block text-muted';
                reason.textContent = movie.reason;
                details.append(link, reason);
                const field = document.createElement('td');
                const input = document.createElement('input');
                input.className = 'form-control';
                input.dataset.position = movie.position;
                input.placeholder = 'Blank = add new movie';
                input.pattern = 'tt[0-9]{5,12}';
                input.maxLength = 14;
                input.autocomplete = 'off';
                input.setAttribute('aria-label', `Existing IMDb code for ${movie.name}`);
                field.append(input);
                row.append(position, details, field);
                element('rows').append(row);
            });
            element('count').textContent = `${data.matched} of ${data.total} movies matched automatically.`;
            element('missing-title').textContent = `The following ${data.unmatched.length} movies were not found. Please provide their codes if they do already exist`;
            element('missing').hidden = data.unmatched.length === 0;
            element('review').hidden = false;
            message('status', 'Preview ready. No movies have been changed.');
        } catch (error) {
            message('status', '');
            message('error', error.message);
        } finally {
            setBusy(false);
        }
    });
    element('form').addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !token || !element('form').reportValidity()) return;
        const decisions = {};
        element('rows').querySelectorAll('input').forEach(input => {
            decisions[input.dataset.position] = input.value.trim();
        });
        message('error', '');
        message('status', 'Applying movie update…');
        setBusy(true);
        dialog.close();
        notify('info', 'Updating movies…');
        try {
            const result = await request(dialog.dataset.applyUrl, { token, decisions });
            let text = `Successfully updated: ${result.updated} movies updated, ${result.added} added, and ${result.artwork_added} missing artwork URLs added. Top 250 order is now current.`;
            if (result.unrated_added) text += ` Ratings were unavailable for ${result.unrated_added} new movies and are stored as 0 until provided.`;
            message('status', text);
            notify('success', text);
            element('review').hidden = true;
            token = null;
        } catch (error) {
            message('status', '');
            message('error', error.message);
            notify('danger', `Movie update failed: ${error.message}`);
        } finally {
            setBusy(false);
        }
    });
})();
