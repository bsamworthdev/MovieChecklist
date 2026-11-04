(function () {
    const preview = document.getElementById('tv-import-preview');
    const apply = document.getElementById('tv-import-apply');
    const html = document.getElementById('tv-import-html');
    const result = document.getElementById('tv-import-result');
    const thumbnails = document.getElementById('generate-tv-thumbnails-button');
    if (thumbnails) thumbnails.addEventListener('click', async () => {
        if (preview.disabled || thumbnails.disabled) return;
        const catalogueButtons = [...document.querySelectorAll('[data-catalogue-update]')];
        thumbnails.disabled = true; preview.disabled = true; apply.disabled = true;
        catalogueButtons.forEach(button => {button.disabled = true;});
        const notice = document.getElementById('movie-import-notification');
        const text = document.getElementById('movie-import-notification-text');
        notice.hidden = false; notice.className = 'alert alert-info'; text.textContent = 'Generating missing TV thumbnails…';
        let generated = 0;
        try {
            let remaining;
            do {
                const data = await request(thumbnails.dataset.url, {});
                generated += data.generated; remaining = data.remaining;
                text.textContent = `Generated ${generated} TV thumbnails; ${remaining} remaining…`;
                if (remaining && !data.generated) throw new Error('Thumbnail generation made no progress. Please retry.');
            } while (remaining);
            notice.className = 'alert alert-success';
            text.textContent = generated ? `Successfully generated ${generated} TV thumbnails.` : 'No missing TV thumbnails were found. All TV shows with artwork already have a thumbnail.';
        } catch (error) {notice.className = 'alert alert-danger'; text.textContent = `Thumbnail generation failed: ${error.message}`;}
        finally {
            thumbnails.disabled = false; preview.disabled = false; apply.disabled = false;
            catalogueButtons.forEach(button => {button.disabled = button.dataset.configured !== 'true';});
        }
    });
    let token = null;
    async function request(url, body) {
        const response = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify(body)});
        const data = await response.json();
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message || 'The update failed. Please try again.');
        return data;
    }
    html.addEventListener('input', () => {token = null; apply.hidden = true; result.textContent = '';});
    preview.addEventListener('click', async () => {
        preview.disabled = true; if (thumbnails) thumbnails.disabled = true; apply.hidden = true; token = null;
        result.textContent = 'Reading TV list…';
        try {
            const data = await request('/admin/tv-series/preview', {html: html.value});
            token = data.token;
            result.textContent = `${data.total} TV shows: ${data.existing} existing, ${data.new} new. First 10: ` + data.titles.map(row => `${row.position}. ${row.name}`).join('; ');
            apply.hidden = false;
        } catch (error) {result.textContent = error.message;}
        finally {preview.disabled = false; if (thumbnails) thumbnails.disabled = false;}
    });
    apply.addEventListener('click', async () => {
        if (!token) return;
        apply.disabled = true; preview.disabled = true; if (thumbnails) thumbnails.disabled = true;
        result.textContent = 'Updating TV shows…';
        try {
            const data = await request('/admin/tv-series/apply', {token});
            result.textContent = `Successfully updated ${data.updated} TV shows; ${data.added} added.`;
            apply.hidden = true; token = null;
        } catch (error) {result.textContent = error.message;}
        finally {apply.disabled = false; preview.disabled = false; if (thumbnails) thumbnails.disabled = false;}
    });
})();
