const state = {
    token: localStorage.getItem('majilink_token') || '',
    user: JSON.parse(localStorage.getItem('majilink_user') || 'null'),
    boreholes: [],
    outages: [],
};

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

function showToast(message) {
    const toast = $('#toast');
    toast.textContent = message;
    toast.classList.add('show');
    window.setTimeout(() => toast.classList.remove('show'), 3200);
}

function openModal(id) {
    const isAuthModal = id === 'login-modal' || id === 'register-modal';
    if (!state.token && !isAuthModal) {
        openModal('login-modal');
        showToast('Sign in to use this feature.');
        return;
    }
    if (id === 'borehole-modal' && state.user?.role !== 'admin') {
        showToast('Only administrators can add water points.');
        return;
    }
    closeModals();
    document.getElementById(id)?.classList.add('show');
}

function closeModals() {
    $$('.modal-backdrop').forEach((modal) => modal.classList.remove('show'));
}

function setUser(user, token) {
    state.user = user;
    state.token = token;
    localStorage.setItem('majilink_user', JSON.stringify(user));
    localStorage.setItem('majilink_token', token);
    renderUser();
    closeModals();
    loadPrivateData();
}

function clearUser() {
    state.user = null;
    state.token = '';
    localStorage.removeItem('majilink_user');
    localStorage.removeItem('majilink_token');
    renderUser();
    showToast('You have been signed out.');
}

function renderUser() {
    const user = state.user;
    const name = user?.name || 'Guest user';
    $('#user-name').textContent = name;
    $('#user-role').textContent = user ? `${user.role} · ${user.county}` : 'Sign in to continue';
    $('#welcome-name').textContent = user ? name.split(' ')[0] : 'neighbour';
    $('.avatar').textContent = user ? name.split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase() : 'GU';
    const addBoreholeButton = $('#add-borehole-button');
    if (addBoreholeButton) addBoreholeButton.hidden = user?.role !== 'admin';
}

async function api(path, options = {}) {
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    if (options.body && typeof options.body !== 'string') {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }
    if (state.token) headers.Authorization = `Bearer ${state.token}`;
    const response = await fetch(path, { ...options, headers });
    let payload = {};
    try { payload = await response.json(); } catch (error) { /* Non-JSON server response. */ }
    if (!response.ok) throw new Error(payload.error || `Request failed (${response.status})`);
    return payload;
}

async function loadLocationOptions(select, parentId, type) {
    const picker = select.closest('[data-location-picker]');
    try {
        const data = await api(`/api/locations/list.php?type=${encodeURIComponent(type)}${parentId ? `&parent_id=${parentId}` : ''}`);
        const locations = data.locations || [];
        const fallback = type === 'county' && locations.length === 0 ? '<option value="Nairobi">Nairobi</option>' : '';
        select.innerHTML = `<option value="">Select ${type.replace('_', '-')}</option>${fallback}` + locations.map((item) => `<option value="${type === 'county' ? item.name : item.id}" data-id="${item.id}">${item.name}</option>`).join('');
        select.disabled = false;
    } catch (error) {
        if (type !== 'county') select.disabled = true;
    }
    if (picker) picker.querySelector('input[name="administrative_unit_id"]').value = '';
}

function initLocationPicker(picker) {
    const selects = $$('select[data-location-level]', picker);
    const county = $('select[data-location-level="county"]', picker);
    loadLocationOptions(county, null, 'county').catch(() => {});
    selects.forEach((select, index) => select.addEventListener('change', async () => {
        const selected = select.options[select.selectedIndex];
        if (select.dataset.locationLevel === 'county') {
            select.dataset.selectedId = selected?.dataset.id || '';
            const next = selects[index + 1];
            if (next) await loadLocationOptions(next, selected?.dataset.id, next.dataset.locationLevel);
        } else {
            const next = selects[index + 1];
            if (next) await loadLocationOptions(next, selected?.value, next.dataset.locationLevel);
        }
        const hidden = $('input[name="administrative_unit_id"]', picker);
        hidden.value = selected?.dataset.id || selected?.value || '';
    }));
}

function statusBadge(status) {
    const label = (status || 'unknown').replace('_', ' ');
    return `<span class="status-badge status-${status}">${label}</span>`;
}

async function checkApi() {
    const status = $('#api-status');
    try {
        const response = await fetch('/api/auth/me.php', { headers: { Accept: 'application/json' } });
        status.textContent = response.status === 401 || response.ok ? 'online' : 'offline';
        status.previousElementSibling.style.background = response.status === 401 || response.ok ? '#7ed49e' : '#e57c5d';
    } catch (error) {
        status.textContent = 'offline';
        status.previousElementSibling.style.background = '#e57c5d';
    }
}

async function loadBoreholes() {
    if (!state.token) return;
    const list = $('#borehole-list');
    try {
        const data = await api('/api/boreholes/list.php?county=Nairobi');
        state.boreholes = data.boreholes || [];
        renderBoreholes();
    } catch (error) {
        list.innerHTML = `<div class="empty-state">${error.message}</div>`;
    }
}

function renderBoreholes() {
    const query = ($('#borehole-search')?.value || '').toLowerCase();
    const filter = $('#borehole-filter')?.value || '';
    const items = state.boreholes.filter((item) => {
        const matchesQuery = `${item.name} ${item.location_text}`.toLowerCase().includes(query);
        return matchesQuery && (!filter || item.status === filter);
    });
    $('#borehole-list').innerHTML = items.length ? items.map((item) => `<article class="directory-card"><div>${statusBadge(item.status)}</div><h3>${item.name}</h3><p>⌖ ${item.location_text}<br>${item.county} County</p><button class="text-button">View location →</button></article>`).join('') : '<div class="empty-state">No water points match this search.</div>';
}

async function loadVendors() {
    if (!state.token) return;
    try {
        const data = await api('/api/vendors/list.php?county=Nairobi');
        $('#vendor-list').innerHTML = data.vendors?.length ? data.vendors.map((vendor) => `<article class="directory-card"><div>${vendor.verified == 1 ? '<span class="status-badge status-working">Verified</span>' : '<span class="status-badge status-limited">Pending</span>'}</div><h3>${vendor.business_name}</h3><p>☎ ${vendor.phone}<br>Serving ${vendor.county} · ${vendor.service_radius_km} km radius</p><button class="text-button">Contact vendor →</button></article>`).join('') : '<div class="empty-state">No active vendors found in Nairobi.</div>';
    } catch (error) {
        $('#vendor-list').innerHTML = `<div class="empty-state">${error.message}</div>`;
    }
}

async function loadDeliveries() {
    if (!state.token) return;
    try {
        const data = await api('/api/deliveries/list.php');
        const rows = data.deliveries || [];
        $('#delivery-list').innerHTML = rows.length ? rows.map((item) => `<tr><td><strong>#${item.id}</strong></td><td>${item.location_text}</td><td>${item.preferred_date}</td><td>${Number(item.litres).toLocaleString()} L</td><td>${statusBadge(item.status)}</td></tr>`).join('') : '<tr><td colspan="5" class="empty-cell">No delivery requests yet.</td></tr>';
    } catch (error) {
        $('#delivery-list').innerHTML = `<tr><td colspan="5" class="empty-cell">${error.message}</td></tr>`;
    }
}

async function loadOutages() {
    if (!state.token) return;
    try {
        const data = await api('/api/outages/list.php?county=Nairobi');
        state.outages = data.outages || [];
        renderOutages();
    } catch (error) {
        $('#outage-list').innerHTML = `<tr><td colspan="4" class="empty-cell">${error.message}</td></tr>`;
    }
}

function renderOutages() {
    const query = ($('#outage-search')?.value || '').toLowerCase();
    const filter = $('#outage-filter')?.value || '';
    const items = state.outages.filter((item) => `${item.area} ${item.description}`.toLowerCase().includes(query) && (!filter || item.status === filter));
    $('#outage-list').innerHTML = items.length ? items.map((item) => `<tr><td><strong>${item.area}</strong><br><small>${item.county}</small></td><td>${item.description}</td><td>${new Date(item.created_at).toLocaleDateString()}</td><td>${statusBadge(item.status)}</td></tr>`).join('') : '<tr><td colspan="4" class="empty-cell">No reports match this search.</td></tr>';
}

function loadPrivateData() {
    loadBoreholes();
    loadVendors();
    loadDeliveries();
    loadOutages();
}

function activateView(view) {
    $$('.nav-item').forEach((item) => item.classList.toggle('active', item.dataset.view === view));
    $$('.view').forEach((item) => item.classList.toggle('active', item.id === `view-${view}`));
    const active = $(`.nav-item[data-view="${view}"]`);
    $('#page-title').textContent = active?.textContent.trim().replace(/\d+$/, '') || 'Overview';
    $('#sidebar').classList.remove('open');
    if (view === 'boreholes') loadBoreholes();
    if (view === 'vendors') loadVendors();
    if (view === 'deliveries') loadDeliveries();
    if (view === 'outages') loadOutages();
}

async function submitApiForm(form) {
    const type = form.dataset.apiForm;
    const message = $('.form-message', form);
    const button = $('button[type="submit"]', form);
    const values = Object.fromEntries(new FormData(form).entries());
    const routes = { delivery: '/api/deliveries/create.php', outage: '/api/outages/create.php', borehole: '/api/boreholes/create.php' };
    button.disabled = true;
    message.className = 'form-message';
    message.textContent = 'Sending...';
    try {
        await api(routes[type], { method: 'POST', body: values });
        message.className = 'form-message success';
        message.textContent = 'Saved successfully.';
        showToast(type === 'delivery' ? 'Delivery request sent.' : type === 'outage' ? 'Outage report submitted.' : 'Water point published.');
        window.setTimeout(() => { closeModals(); form.reset(); loadPrivateData(); }, 700);
    } catch (error) {
        message.className = 'form-message error';
        message.textContent = error.message;
    } finally {
        button.disabled = false;
    }
}

$$('[data-view]').forEach((button) => button.addEventListener('click', () => activateView(button.dataset.view)));
$$('[data-modal]').forEach((button) => button.addEventListener('click', () => openModal(button.dataset.modal)));
$$('[data-close]').forEach((button) => button.addEventListener('click', closeModals));
$$('.modal-backdrop').forEach((backdrop) => backdrop.addEventListener('click', (event) => { if (event.target === backdrop) closeModals(); }));
$('#mobile-menu').addEventListener('click', () => $('#sidebar').classList.toggle('open'));
$('#user-trigger').addEventListener('click', () => {
    if (!state.user) openModal('login-modal');
    else $('#user-dropdown').classList.toggle('show');
});
$('#sign-out').addEventListener('click', clearUser);
$('#demo-login').addEventListener('click', () => { $('#login-form [name="phone"]').value = '+254700000004'; $('#login-form [name="password"]').value = 'password'; $('#login-form').requestSubmit(); });
$('#login-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = $('#login-message');
    const button = $('button[type="submit"]', event.currentTarget);
    button.disabled = true;
    message.className = 'form-message';
    message.textContent = 'Signing in...';
    try {
        const values = Object.fromEntries(new FormData(event.currentTarget).entries());
        const data = await api('/api/auth/login.php', { method: 'POST', body: values });
        setUser(data.user, data.token);
        showToast(`Welcome, ${data.user.name.split(' ')[0]}.`);
    } catch (error) {
        message.className = 'form-message error';
        message.textContent = error.message;
    } finally { button.disabled = false; }
});
$('#register-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = $('#register-message');
    const button = $('button[type="submit"]', event.currentTarget);
    button.disabled = true;
    message.className = 'form-message';
    message.textContent = 'Creating your account...';
    try {
        const values = Object.fromEntries(new FormData(event.currentTarget).entries());
        const data = await api('/api/auth/register.php', { method: 'POST', body: values });
        setUser(data.user, data.token);
        showToast(`Welcome to MajiLink, ${data.user.name.split(' ')[0]}.`);
    } catch (error) {
        message.className = 'form-message error';
        message.textContent = error.message;
    } finally { button.disabled = false; }
});
$$('[data-api-form]').forEach((form) => form.addEventListener('submit', (event) => { event.preventDefault(); submitApiForm(form); }));
$$('[data-location-picker]').forEach(initLocationPicker);
$('#borehole-search').addEventListener('input', renderBoreholes);
$('#borehole-filter').addEventListener('change', renderBoreholes);
$('#outage-search').addEventListener('input', renderOutages);
$('#outage-filter').addEventListener('change', renderOutages);
$('#refresh-deliveries').addEventListener('click', loadDeliveries);

renderUser();
checkApi();
loadPrivateData();
