document.addEventListener('DOMContentLoaded', () => {
    const eyeIcon = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
    const eyeOffIcon = '<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.24 4.24M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-3.11 4.24M6.11 6.11A13.2 13.2 0 0 0 2 11s3.5 7 10 7a10.9 10.9 0 0 0 4.11-.8"/>';
    document.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', () => {
        const input = document.querySelector('#' + button.dataset.togglePassword);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        const svg = button.querySelector('svg');
        if (svg) svg.innerHTML = show ? eyeOffIcon : eyeIcon;
        else button.textContent = show ? 'Hide' : 'Show';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    }));

    document.querySelectorAll('[data-auth-tab]').forEach(tab => tab.addEventListener('click', () => {
        const group = tab.closest('.auth-tabs');
        const portal = tab.dataset.authTab;
        group.querySelectorAll('[data-auth-tab]').forEach(item => {
            item.classList.toggle('active', item === tab);
            item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
        });
        const portalField = document.querySelector('#auth-portal');
        if (portalField) portalField.value = portal;
        if (portal === 'employee') notify('Employee portal is coming soon in this prototype.');
    }));

    document.querySelectorAll('form.auth-form').forEach(form => form.addEventListener('submit', () => {
        const submit = form.querySelector('.auth-submit');
        if (!submit || submit.disabled) return;
        submit.disabled = true;
        submit.dataset.originalLabel = submit.textContent;
        submit.textContent = submit.dataset.loadingLabel || 'Please wait…';
    }));

    const modal = document.querySelector('#app-modal');
    const modalContent = document.querySelector('#modal-content');
    const toast = document.querySelector('#app-toast');
    let toastTimer;

    const notify = message => {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    };
    const closeModal = () => { modal?.classList.remove('open'); modal?.setAttribute('aria-hidden', 'true'); };
    const openModal = html => { if (!modal || !modalContent) return; modalContent.innerHTML = html; modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); };
    if (modal) {
        document.querySelector('.modal-close')?.addEventListener('click', closeModal);
        modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', event => { if (event.key === 'Escape') closeModal(); });
    }

    document.querySelectorAll('[data-toast]').forEach(button => button.addEventListener('click', () => notify(button.dataset.toast)));
    document.querySelectorAll('[data-role-select]').forEach(select => select.addEventListener('change', () => {
        select.classList.toggle('admin', select.value === 'Admin');
        select.classList.toggle('user', select.value === 'User');
        notify(`Role changed to ${select.value}.`);
    }));
    document.querySelectorAll('[data-focus-search]').forEach(button => button.addEventListener('click', () => (document.querySelector('[data-table-search]') || document.querySelector('.input'))?.focus()));

    document.querySelectorAll('[data-table-search]').forEach(input => input.addEventListener('input', () => {
        const rows = [...document.querySelectorAll(input.dataset.table + ' tbody tr')];
        const query = input.value.toLowerCase().trim();
        rows.forEach(row => row.classList.toggle('hidden-row', !row.textContent.toLowerCase().includes(query)));
    }));

document.querySelector('#users-table')?.addEventListener('click', event => {
    const editBtn = event.target.closest('[data-edit-user]');
    if (editBtn) {
        const row = editBtn.closest('tr');
        const name = row.children[1].textContent.trim();
        openModal(`<h2>Edit ${name}</h2><p>This prototype keeps user data static. The edit action is ready to be connected to the user API.</p><div class="modal-actions"><button class="button secondary" data-close-modal>Cancel</button><button class="button" data-close-modal>Save changes</button></div>`);
        return;
    }
    const deleteBtn = event.target.closest('[data-delete-user]');
    if (deleteBtn) {
        const row = deleteBtn.closest('tr');
        const name = row.children[1].textContent.trim();
        openModal(`<h2>Delete user?</h2><p>${name} will be removed from this list in the current browser session.</p><div class="modal-actions"><button class="button secondary" data-close-modal>Cancel</button><button class="button" data-confirm-delete>Delete user</button></div>`);
        modalContent.querySelector('[data-confirm-delete]').addEventListener('click', () => { row.remove(); closeModal(); notify(`${name} removed from the list.`); });
    }
});

document.querySelector('#users-table')?.addEventListener('change', event => {
    const select = event.target.closest('[data-role-select]');
    if (!select) return;
    select.classList.toggle('admin', select.value === 'Admin');
    select.classList.toggle('user', select.value === 'User');
    notify(`Role changed to ${select.value}.`);
});

document.querySelectorAll('[data-add-user]').forEach(button => button.addEventListener('click', () => {
    openModal(`<h2>Add User</h2><form data-add-user-form><div class="field"><label>Full Name</label><input class="input" type="text" name="name" required></div><div class="field"><label>Email Address</label><input class="input" type="email" name="email" required></div><div class="field"><label>Unit / Department</label><input class="input" type="text" name="unit" required></div><div class="field"><label>Role</label><select class="input" name="role"><option value="User">User</option><option value="Admin">Admin</option></select></div><div class="modal-actions"><button class="button secondary" type="button" data-close-modal>Cancel</button><button class="button" type="submit">Add User</button></div></form>`);

    modalContent.querySelector('[data-add-user-form]').addEventListener('submit', event => {
        event.preventDefault();
        const form = event.target;
        const name = form.name.value.trim();
        const email = form.email.value.trim();
        const unit = form.unit.value.trim();
        const role = form.role.value;
        if (!name || !email || !unit) return;

        const initials = name.split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
        const row = document.createElement('tr');
        row.innerHTML = `<td><div class="user-avatar">${initials}</div></td><td><strong>${name}</strong></td><td class="tiny">${email}</td><td><span class="tag">${unit}</span></td><td><select class="role-select ${role.toLowerCase()}" data-role-select aria-label="Role for ${name}"><option value="User"${role === 'User' ? ' selected' : ''}>User</option><option value="Admin"${role === 'Admin' ? ' selected' : ''}>Admin</option></select></td><td><div class="row-actions"><button class="action-btn action-icon action-edit" type="button" data-edit-user aria-label="Edit ${name}" title="Edit"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/></svg></button><button class="action-btn action-icon action-delete" type="button" data-delete-user aria-label="Delete ${name}" title="Delete"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v5m4-5v5M9 7l1-2h4l1 2m-9 0 1 13h10l1-13"/></svg></button></div></td>`;
        document.querySelector('#users-table tbody').appendChild(row);

        closeModal();
        notify(`${name} added to the list.`);
    });
}));
    document.addEventListener('click', event => { if (event.target.closest('[data-close-modal]')) closeModal(); });

    const selectAll = document.querySelector('[data-select-all]');
    const checks = [...document.querySelectorAll('[data-participant]')];
    const count = document.querySelector('[data-participant-count]');
    const refreshCount = () => { const selected = checks.filter(check => check.checked).length; if (count) count.textContent = `${selected} of ${checks.length} selected`; if (selectAll) selectAll.checked = selected === checks.length; };
    if (selectAll) selectAll.addEventListener('change', () => { checks.forEach(check => check.checked = selectAll.checked); refreshCount(); });
    checks.forEach(check => check.addEventListener('change', refreshCount));

    document.querySelectorAll('[data-tab-status]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-tab-status]').forEach(item => item.classList.remove('active')); tab.classList.add('active');
        const status = tab.dataset.tabStatus;
        document.querySelectorAll('[data-meeting-row]').forEach(row => row.classList.toggle('hidden-row', status !== 'All' && row.dataset.status !== status));
    }));
    document.addEventListener('click', event => {
        const start = event.target.closest('[data-start-meeting]');
        if (start) {
            start.parentElement.innerHTML = '<button class="meeting-live" type="button" data-toast="Live attendance opened.">Live</button><a class="meeting-minutes" href="/admin/notulen/editor">Minutes</a><button class="meeting-end" type="button" data-end-meeting>End</button>';
            const row = start.closest('[data-meeting-row]');
            if (row) { row.dataset.status = 'Ongoing'; const badge = row.querySelector('.status'); if (badge) { badge.className = 'status ongoing'; badge.textContent = 'Ongoing'; } }
            notify('Meeting started. Live attendance is now available.');
        }
        const live = event.target.closest('.meeting-live');
        if (live && !start) notify('Live attendance opened.');
        const end = event.target.closest('[data-end-meeting]');
        if (end) {
            const row = end.closest('[data-meeting-row]');
            end.parentElement.innerHTML = '<a class="meeting-report" href="/admin/notulen">View Report</a><a class="meeting-minutes" href="/admin/notulen/editor">Edit Minutes</a>';
            if (row) { row.dataset.status = 'Completed'; const badge = row.querySelector('.status'); if (badge) { badge.className = 'status completed'; badge.textContent = 'Completed'; } }
            notify('Meeting completed. Report is now available.');
        }
    });
    document.querySelectorAll('[data-report-action]').forEach(button => button.addEventListener('click', () => window.print()));
    document.querySelectorAll('[data-report-word]').forEach(button => button.addEventListener('click', () => {
        const title = document.querySelector('.report-hero h2')?.textContent.trim() || 'meeting-report';
        const body = document.querySelector('.report-grid')?.innerText || 'Meeting report';
        const file = new Blob(['\ufeff', `<html><head><meta charset="utf-8"><title>${title}</title></head><body><h1>${title}</h1><pre style="white-space:pre-wrap;font-family:Arial">${body}</pre></body></html>`], { type: 'application/msword' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(file);
        link.download = `${title.toLowerCase().replace(/[^a-z0-9]+/gi, '-')}.doc`;
        link.click();
        URL.revokeObjectURL(link.href);
        notify('Word report downloaded.');
    }));
    document.querySelectorAll('[data-file-preview]').forEach(button => button.addEventListener('click', () => {
        const name = button.dataset.filePreview;
        openModal(`<h2>${name}</h2><p>Preview is not available because this demo does not include uploaded files. Use Download after connecting this view to storage.</p><div class="modal-actions"><button class="button" data-close-modal>Close</button></div>`);
    }));
});