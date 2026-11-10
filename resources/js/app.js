const sidebar = document.querySelector('[data-sidebar]');
const publicMenuToggle = document.querySelector('[data-public-menu-toggle]');
const publicMobileMenu = document.querySelector('[data-public-mobile-menu]');

publicMenuToggle?.addEventListener('click', () => {
    const isOpen = !publicMobileMenu.classList.toggle('hidden');
    publicMenuToggle.setAttribute('aria-expanded', String(isOpen));
});

const publicRevealElements = document.querySelectorAll('.public-reveal');
if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => entries.forEach((entry) => {
        if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
        }
    }), { threshold: 0.12 });
    publicRevealElements.forEach((element) => revealObserver.observe(element));
} else {
    publicRevealElements.forEach((element) => element.classList.add('is-visible'));
}

const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

const closeSidebar = () => {
    sidebar?.classList.add('-translate-x-full');
    sidebarOverlay?.classList.add('hidden');
};

sidebarToggle?.addEventListener('click', () => {
    sidebar?.classList.remove('-translate-x-full');
    sidebarOverlay?.classList.remove('hidden');
});
sidebarOverlay?.addEventListener('click', closeSidebar);

const dashboard = document.querySelector('[data-dashboard]');

class ApiError extends Error {
    constructor(message, status, payload) {
        super(message);
        this.status = status;
        this.payload = payload;
    }
}

const apiRequest = async (url, options = {}) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
            ...options.headers,
        },
    });

    if (response.status === 401) {
        window.location.assign('/cms/login');
        return null;
    }

    const payload = response.status === 204 ? null : await response.json();
    if (!response.ok) throw new ApiError(`Request failed with status ${response.status}`, response.status, payload);

    return payload;
};

const setMetric = (name, value) => {
    document.querySelectorAll(`[data-metric="${name}"]`).forEach((element) => {
        element.textContent = typeof value === 'number' ? new Intl.NumberFormat().format(value) : (value ?? '0');
    });
};

const renderActivity = (activities) => {
    const list = document.querySelector('[data-activity-list]');
    if (!list) return;

    list.replaceChildren();

    if (!activities.length) {
        const empty = document.createElement('p');
        empty.className = 'py-8 text-center text-sm text-cms-navy/45';
        empty.textContent = 'No activity recorded yet.';
        list.append(empty);
        return;
    }

    activities.slice(0, 6).forEach((activity) => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3 border-b border-cms-navy/6 py-3 last:border-0';

        const marker = document.createElement('span');
        marker.className = 'grid size-9 shrink-0 place-items-center rounded-xl bg-cms-canvas text-xs font-bold text-cms-green';
        marker.textContent = activity.event.slice(0, 1).toUpperCase();

        const copy = document.createElement('div');
        copy.className = 'min-w-0 flex-1';
        const event = document.createElement('p');
        event.className = 'truncate text-sm font-semibold text-cms-navy';
        event.textContent = activity.event.replaceAll('.', ' ');
        const meta = document.createElement('p');
        meta.className = 'mt-0.5 truncate text-xs text-cms-navy/45';
        meta.textContent = `${activity.user?.name ?? 'System'} · ${new Date(activity.created_at).toLocaleString()}`;
        copy.append(event, meta);
        row.append(marker, copy);
        list.append(row);
    });
};

if (dashboard) {
    apiRequest('/api/v1/admin/dashboard')
        .then((payload) => {
            if (!payload) return;
            const data = payload.data;
            setMetric('pages', data.content.pages.total);
            setMetric('posts', data.content.posts.total);
            setMetric('portfolio', data.content.portfolio.total);
            setMetric('messages', data.contacts.new);
            setMetric('seo', `${data.seo.average_score}%`);
            setMetric('media', data.media.files);
            renderActivity(data.recent_activity);
        })
        .catch(() => {
            const error = document.querySelector('[data-dashboard-error]');
            if (error) {
                error.textContent = 'Dashboard data is unavailable for this account. Check the dashboard permission and feature flag.';
                error.classList.remove('hidden');
            }
        });
}

const searchInput = document.querySelector('[data-global-search]');
const searchResults = document.querySelector('[data-search-results]');
let searchTimer;

const renderSearchResults = (results) => {
    if (!searchResults) return;
    searchResults.replaceChildren();

    if (!results.length) {
        const empty = document.createElement('p');
        empty.className = 'px-3 py-6 text-center text-sm text-cms-navy/45';
        empty.textContent = 'No matching content found.';
        searchResults.append(empty);
    }

    results.forEach((result) => {
        const item = document.createElement('a');
        item.href = `/cms#${result.type}`;
        item.className = 'flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-cms-canvas';
        const marker = document.createElement('span');
        marker.className = 'grid size-9 shrink-0 place-items-center rounded-xl bg-cms-navy text-xs font-bold text-white';
        marker.textContent = result.type.slice(0, 1).toUpperCase();
        const copy = document.createElement('span');
        copy.className = 'min-w-0';
        const title = document.createElement('span');
        title.className = 'block truncate text-sm font-semibold text-cms-navy';
        title.textContent = result.title;
        const meta = document.createElement('span');
        meta.className = 'block truncate text-xs text-cms-navy/45';
        meta.textContent = result.subtitle || result.type.replaceAll('_', ' ');
        copy.append(title, meta);
        item.append(marker, copy);
        searchResults.append(item);
    });

    searchResults.classList.remove('hidden');
};

searchInput?.addEventListener('input', () => {
    window.clearTimeout(searchTimer);
    const query = searchInput.value.trim();
    if (query.length < 2) {
        searchResults?.classList.add('hidden');
        return;
    }

    searchTimer = window.setTimeout(() => {
        apiRequest(`/api/v1/admin/search?q=${encodeURIComponent(query)}&limit=8`)
            .then((payload) => payload && renderSearchResults(payload.data.results))
            .catch(() => searchResults?.classList.add('hidden'));
    }, 250);
});

document.addEventListener('click', (event) => {
    if (!searchResults?.contains(event.target) && event.target !== searchInput) {
        searchResults?.classList.add('hidden');
    }
});

const cmsLocales = JSON.parse(document.querySelector('[data-cms-locales]')?.textContent || '{"default":"id","options":{}}');
const localeLabel = (locale) => cmsLocales.options[locale]?.label ?? String(locale ?? '').toUpperCase();
const localeName = (locale) => cmsLocales.options[locale]?.name ?? locale;
const localeBadge = (locale) => {
    const badge = document.createElement('span');
    badge.className = 'inline-flex rounded-md bg-cms-navy/8 px-1.5 py-0.5 text-[0.65rem] font-bold tracking-wide text-cms-navy/70';
    badge.textContent = localeLabel(locale);
    badge.title = localeName(locale);
    return badge;
};

const pagesList = document.querySelector('[data-pages-list]');

if (pagesList) {
    const rows = pagesList.querySelector('[data-pages-rows]');
    const search = pagesList.querySelector('[data-pages-search]');
    const status = pagesList.querySelector('[data-pages-status]');
    const locale = pagesList.querySelector('[data-pages-locale]');
    const summary = pagesList.querySelector('[data-pages-summary]');
    const previous = pagesList.querySelector('[data-pages-previous]');
    const next = pagesList.querySelector('[data-pages-next]');
    const error = pagesList.querySelector('[data-pages-error]');
    const canUpdate = pagesList.dataset.canUpdate === 'true';
    let currentPage = 1;
    let debounce;

    const renderPages = (payload) => {
        rows.replaceChildren();
        payload.data.forEach((page) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-cms-canvas/45';

            const pageCell = document.createElement('td');
            pageCell.className = 'px-5 py-4';
            const title = document.createElement('p');
            title.className = 'font-semibold text-cms-navy';
            title.textContent = page.title;
            const slug = document.createElement('p');
            slug.className = 'mt-1 flex items-center gap-2 text-xs text-cms-navy/40';
            slug.append(localeBadge(page.locale), `/${page.slug}`);
            const statusCell = document.createElement('td');
            statusCell.className = 'hidden px-5 py-4 sm:table-cell';
            const badge = document.createElement('span');
            badge.className = page.status === 'published'
                ? 'inline-flex rounded-full bg-cms-green/10 px-2.5 py-1 text-xs font-bold text-cms-green'
                : 'inline-flex rounded-full bg-cms-gold/15 px-2.5 py-1 text-xs font-bold text-cms-navy';
            badge.textContent = page.status;
            statusCell.append(badge);

            // Narrow screens drop the status column, so the badge sits under the title instead.
            const inlineBadge = badge.cloneNode(true);
            inlineBadge.classList.add('mt-2', 'sm:hidden');
            pageCell.append(title, slug, inlineBadge);

            const updatedCell = document.createElement('td');
            updatedCell.className = 'hidden px-5 py-4 text-sm text-cms-navy/50 sm:table-cell';
            updatedCell.textContent = new Date(page.updated_at).toLocaleDateString();

            const actionCell = document.createElement('td');
            actionCell.className = 'px-5 py-4 text-right';
            if (canUpdate) {
                const edit = document.createElement('a');
                edit.href = `/cms/pages/${page.id}/edit`;
                edit.className = 'inline-flex h-9 items-center rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-bold text-cms-navy hover:border-cms-gold';
                edit.textContent = 'Edit';
                actionCell.append(edit);
            } else {
                const restricted = document.createElement('span');
                restricted.className = 'text-xs text-cms-navy/35';
                restricted.textContent = 'View only';
                actionCell.append(restricted);
            }

            row.append(pageCell, statusCell, updatedCell, actionCell);
            rows.append(row);
        });

        if (!payload.data.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'px-5 py-12 text-center text-sm text-cms-navy/45';
            cell.textContent = 'No pages match these filters.';
            row.append(cell);
            rows.append(row);
        }

        summary.textContent = `Showing ${payload.meta.from ?? 0}–${payload.meta.to ?? 0} of ${payload.meta.total} pages`;
        previous.disabled = payload.meta.current_page <= 1;
        next.disabled = payload.meta.current_page >= payload.meta.last_page;
        error.classList.add('hidden');
    };

    const loadPages = () => {
        const params = new URLSearchParams({ page: currentPage, per_page: 15 });
        if (search.value.trim()) params.set('search', search.value.trim());
        if (status.value) params.set('status', status.value);
        if (locale.value) params.set('locale', locale.value);

        apiRequest(`/api/v1/admin/pages?${params}`)
            .then(renderPages)
            .catch(() => {
                error.textContent = 'Pages could not be loaded. Check your permission and the Pages feature flag.';
                error.classList.remove('hidden');
            });
    };

    search.addEventListener('input', () => {
        window.clearTimeout(debounce);
        currentPage = 1;
        debounce = window.setTimeout(loadPages, 250);
    });
    [status, locale].forEach((select) => select.addEventListener('change', () => { currentPage = 1; loadPages(); }));
    previous.addEventListener('click', () => { currentPage -= 1; loadPages(); });
    next.addEventListener('click', () => { currentPage += 1; loadPages(); });
    loadPages();
}

const postsList = document.querySelector('[data-posts-list]');

if (postsList) {
    const rows = postsList.querySelector('[data-posts-rows]');
    const search = postsList.querySelector('[data-posts-search]');
    const type = postsList.querySelector('[data-posts-type]');
    const status = postsList.querySelector('[data-posts-status]');
    const locale = postsList.querySelector('[data-posts-locale]');
    const summary = postsList.querySelector('[data-posts-summary]');
    const previous = postsList.querySelector('[data-posts-previous]');
    const next = postsList.querySelector('[data-posts-next]');
    const error = postsList.querySelector('[data-posts-error]');
    const canUpdate = postsList.dataset.canUpdate === 'true';
    let currentPage = 1;
    let debounce;

    const loadPosts = () => {
        const params = new URLSearchParams({ page: currentPage, per_page: 15 });
        if (search.value.trim()) params.set('search', search.value.trim());
        if (type.value) params.set('type', type.value);
        if (status.value) params.set('status', status.value);
        if (locale.value) params.set('locale', locale.value);
        apiRequest(`/api/v1/admin/posts?${params}`).then((payload) => {
            rows.replaceChildren();
            const dateFormat = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
            const element = (tag, className, text) => { const node = document.createElement(tag); node.className = className; if (text !== undefined) node.textContent = text; return node; };
            payload.data.forEach((post) => {
                const row = element('li', 'flex flex-wrap items-center gap-x-4 gap-y-3 px-4 py-4 hover:bg-cms-canvas/45 sm:flex-nowrap sm:px-5');
                const thumb = element('span', 'grid aspect-video w-24 shrink-0 place-items-center overflow-hidden rounded-lg bg-cms-canvas text-[0.65rem] font-semibold text-cms-navy/30 sm:w-28');
                if (post.cover_image?.url) thumb.append(mediaVisual(post.cover_image)); else thumb.textContent = 'No cover';
                const copy = element('div', 'min-w-0 flex-1 basis-48');
                const title = element('a', 'line-clamp-2 font-semibold text-cms-navy hover:underline', post.title);
                title.href = canUpdate ? `/cms/posts/${post.id}/edit` : (post.canonical_url ?? '#');
                const meta = element('p', 'mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-cms-navy/45');
                const parts = [post.type === 'news' ? 'News' : 'Blog'];
                if (post.categories?.length) parts.push(post.categories.map((category) => category.name).join(', '));
                if (post.status === 'published' && post.published_at) {
                    const publishedAt = new Date(post.published_at);
                    parts.push(publishedAt > new Date() ? `Scheduled ${dateFormat.format(publishedAt)}` : dateFormat.format(publishedAt));
                } else if (post.updated_at) parts.push(`Edited ${dateFormat.format(new Date(post.updated_at))}`);
                if (post.reading_time_minutes) parts.push(`${post.reading_time_minutes} min read`);
                meta.append(localeBadge(post.locale), parts.join(' · '));
                if (post.is_featured) meta.append(element('span', 'rounded-full bg-cms-gold/20 px-2 py-0.5 text-[0.65rem] font-bold text-cms-navy', '★ Featured'));
                copy.append(title, meta);
                const side = element('div', 'flex w-full items-center justify-between gap-3 sm:w-auto sm:justify-end');
                const isScheduled = post.status === 'published' && post.published_at && new Date(post.published_at) > new Date();
                const badge = element('span', post.status === 'published' && !isScheduled
                    ? 'rounded-full bg-cms-green/10 px-2.5 py-1 text-xs font-bold text-cms-green'
                    : 'rounded-full bg-cms-gold/15 px-2.5 py-1 text-xs font-bold text-cms-navy', isScheduled ? 'Scheduled' : (post.status === 'published' ? 'Published' : 'Draft'));
                const actions = element('div', 'flex gap-2');
                if (post.status === 'published' && !isScheduled && post.canonical_url) {
                    const view = element('a', 'inline-flex h-9 items-center rounded-lg px-2.5 text-xs font-bold text-cms-navy/55 hover:text-cms-navy', 'View ↗');
                    view.href = post.canonical_url; view.target = '_blank'; view.rel = 'noopener';
                    actions.append(view);
                }
                if (canUpdate) {
                    const edit = element('a', 'inline-flex h-9 items-center rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-bold', 'Edit');
                    edit.href = `/cms/posts/${post.id}/edit`;
                    actions.append(edit);
                } else actions.append(element('span', 'text-xs text-cms-navy/35', 'View only'));
                side.append(badge, actions);
                row.append(thumb, copy, side);
                rows.append(row);
            });
            if (!payload.data.length) rows.append(element('li', 'px-5 py-12 text-center text-sm text-cms-navy/45', 'No posts match these filters.'));
            summary.textContent = `Showing ${payload.meta.from ?? 0}–${payload.meta.to ?? 0} of ${payload.meta.total} posts`;
            previous.disabled = payload.meta.current_page <= 1; next.disabled = payload.meta.current_page >= payload.meta.last_page; error.classList.add('hidden');
        }).catch(() => { error.textContent = 'Posts could not be loaded.'; error.classList.remove('hidden'); });
    };
    [type, status, locale].forEach((select) => select.addEventListener('change', () => { currentPage = 1; loadPosts(); }));
    search.addEventListener('input', () => { window.clearTimeout(debounce); currentPage = 1; debounce = window.setTimeout(loadPosts, 250); });
    previous.addEventListener('click', () => { currentPage -= 1; loadPosts(); }); next.addEventListener('click', () => { currentPage += 1; loadPosts(); });
    loadPosts();
}

const servicesList = document.querySelector('[data-services-list]');
if (servicesList) {
    const grid = servicesList.querySelector('[data-services-grid]'); const search = servicesList.querySelector('[data-services-search]'); const status = servicesList.querySelector('[data-services-status]'); const summary = servicesList.querySelector('[data-services-summary]'); const previous = servicesList.querySelector('[data-services-previous]'); const next = servicesList.querySelector('[data-services-next]'); const canUpdate = servicesList.dataset.canUpdate === 'true'; let currentPage = 1; let debounce;
    const load = () => { const params = new URLSearchParams({ page: currentPage, per_page: 15 }); if (search.value.trim()) params.set('search', search.value.trim()); if (status.value) params.set('status', status.value); apiRequest(`/api/v1/admin/services?${params}`).then((payload) => { grid.replaceChildren(); payload.data.forEach((service) => { const card = document.createElement('article'); card.className = 'rounded-2xl border border-cms-navy/8 bg-white p-5'; const top = document.createElement('div'); top.className = 'flex items-start gap-3'; const icon = document.createElement('span'); icon.className = 'grid size-10 place-items-center rounded-xl bg-cms-gold/20 font-bold text-cms-navy'; icon.textContent = service.icon?.slice(0, 2).toUpperCase() || 'S'; const copy = document.createElement('div'); copy.className = 'min-w-0 flex-1'; const title = document.createElement('h3'); title.className = 'truncate font-bold'; title.textContent = service.title; const excerpt = document.createElement('p'); excerpt.className = 'mt-2 line-clamp-2 text-xs leading-5 text-cms-navy/45'; excerpt.textContent = service.excerpt || 'No excerpt'; copy.append(title, excerpt); top.append(icon, copy); const footer = document.createElement('div'); footer.className = 'mt-5 flex items-center justify-between'; const badge = document.createElement('span'); badge.className = 'text-xs font-bold text-cms-green'; badge.textContent = service.status; footer.append(badge); if (canUpdate) { const edit = document.createElement('a'); edit.href = `/cms/services/${service.id}/edit`; edit.className = 'text-xs font-bold text-cms-navy'; edit.textContent = 'Edit →'; footer.append(edit); } card.append(top, footer); grid.append(card); }); if (!payload.data.length) { const empty = document.createElement('p'); empty.className = 'col-span-full py-10 text-center text-sm text-cms-navy/45'; empty.textContent = 'No services found.'; grid.append(empty); } summary.textContent = `${payload.meta.total} services`; previous.disabled = payload.meta.current_page <= 1; next.disabled = payload.meta.current_page >= payload.meta.last_page; }); };
    search.addEventListener('input', () => { clearTimeout(debounce); currentPage = 1; debounce = setTimeout(load, 250); }); status.addEventListener('change', () => { currentPage = 1; load(); }); previous.addEventListener('click', () => { currentPage--; load(); }); next.addEventListener('click', () => { currentPage++; load(); }); load();
}

const portfolioList = document.querySelector('[data-portfolio-list]');

if (portfolioList) {
    const grid = portfolioList.querySelector('[data-portfolio-grid]');
    const search = portfolioList.querySelector('[data-portfolio-search]');
    const status = portfolioList.querySelector('[data-portfolio-status]');
    const featured = portfolioList.querySelector('[data-portfolio-featured]');
    const summary = portfolioList.querySelector('[data-portfolio-summary]');
    const previous = portfolioList.querySelector('[data-portfolio-previous]');
    const next = portfolioList.querySelector('[data-portfolio-next]');
    const errorBox = portfolioList.querySelector('[data-portfolio-error]');
    const canUpdate = portfolioList.dataset.canUpdate === 'true';
    let currentPage = 1;
    let debounce;

    const loadPortfolio = () => {
        const params = new URLSearchParams({ page: currentPage, per_page: 15 });
        if (search.value.trim()) params.set('search', search.value.trim());
        if (status.value) params.set('status', status.value);
        if (featured.value) params.set('featured', featured.value);

        apiRequest(`/api/v1/admin/portfolio?${params}`).then((payload) => {
            grid.replaceChildren();
            payload.data.forEach((project) => {
                const card = document.createElement('article');
                card.className = 'overflow-hidden rounded-2xl border border-cms-navy/8 bg-white shadow-sm';
                const visual = document.createElement('div');
                visual.className = 'relative aspect-[16/9] overflow-hidden bg-cms-navy/5';
                if (project.cover_image?.url) {
                    const image = document.createElement('img');
                    image.src = project.cover_image.url;
                    image.alt = project.cover_image.alt_text || project.title;
                    image.loading = 'lazy';
                    image.className = 'h-full w-full object-cover';
                    visual.append(image);
                } else {
                    const placeholder = document.createElement('span');
                    placeholder.className = 'grid h-full place-items-center text-xs font-bold text-cms-navy/30';
                    placeholder.textContent = 'No cover image';
                    visual.append(placeholder);
                }
                if (project.is_featured) {
                    const marker = document.createElement('span');
                    marker.className = 'absolute top-3 left-3 rounded-full bg-cms-gold px-2.5 py-1 text-[0.65rem] font-bold text-cms-navy';
                    marker.textContent = 'Featured';
                    visual.append(marker);
                }
                const body = document.createElement('div');
                body.className = 'p-5';
                const client = document.createElement('p');
                client.className = 'text-xs font-bold uppercase tracking-wide text-cms-green';
                client.textContent = project.client || 'Independent project';
                const title = document.createElement('h3');
                title.className = 'mt-1 truncate font-bold text-cms-navy';
                title.textContent = project.title;
                const excerpt = document.createElement('p');
                excerpt.className = 'mt-2 line-clamp-2 text-xs leading-5 text-cms-navy/45';
                excerpt.textContent = project.excerpt || 'No excerpt';
                const footer = document.createElement('div');
                footer.className = 'mt-5 flex items-center justify-between';
                const badge = document.createElement('span');
                badge.className = project.status === 'published' ? 'text-xs font-bold text-cms-green' : 'text-xs font-bold text-cms-navy/45';
                badge.textContent = project.status;
                footer.append(badge);
                if (canUpdate) {
                    const edit = document.createElement('a');
                    edit.href = `/cms/portfolio/${project.id}/edit`;
                    edit.className = 'text-xs font-bold text-cms-navy';
                    edit.textContent = 'Edit →';
                    footer.append(edit);
                }
                body.append(client, title, excerpt, footer);
                card.append(visual, body);
                grid.append(card);
            });
            if (!payload.data.length) {
                const empty = document.createElement('p');
                empty.className = 'col-span-full py-10 text-center text-sm text-cms-navy/45';
                empty.textContent = 'No portfolio projects found.';
                grid.append(empty);
            }
            summary.textContent = `${payload.meta.total} projects`;
            previous.disabled = payload.meta.current_page <= 1;
            next.disabled = payload.meta.current_page >= payload.meta.last_page;
            errorBox.classList.add('hidden');
        }).catch(() => {
            errorBox.textContent = 'Portfolio could not be loaded. Check your permission and the Portfolio feature flag.';
            errorBox.classList.remove('hidden');
        });
    };

    search.addEventListener('input', () => { clearTimeout(debounce); currentPage = 1; debounce = setTimeout(loadPortfolio, 250); });
    [status, featured].forEach((select) => select.addEventListener('change', () => { currentPage = 1; loadPortfolio(); }));
    previous.addEventListener('click', () => { currentPage -= 1; loadPortfolio(); });
    next.addEventListener('click', () => { currentPage += 1; loadPortfolio(); });
    loadPortfolio();
}

const menuManager = document.querySelector('[data-menu-manager]');

const contactInbox = document.querySelector('[data-contact-inbox]');

const accessManager = document.querySelector('[data-access-manager]');

const settingsManager = document.querySelector('[data-settings-manager]');

if (settingsManager) {
    const notice = settingsManager.querySelector('[data-settings-notice]');
    const websitePanel = settingsManager.querySelector('[data-website-panel]');
    const systemPanel = settingsManager.querySelector('[data-system-panel]');
    const canUpdateWebsite = settingsManager.dataset.canUpdateWebsite === 'true';
    const canUpdateEmail = settingsManager.dataset.canUpdateEmail === 'true';
    const canTestEmail = settingsManager.dataset.canTestEmail === 'true';
    const canUpdateIntegrations = settingsManager.dataset.canUpdateIntegrations === 'true';
    const canUpdateFeatureFlags = settingsManager.dataset.canUpdateFeatureFlags === 'true';

    const showSettingsNotice = (message, type = 'error') => {
        notice.textContent = message;
        notice.className = type === 'success'
            ? 'rounded-xl border border-cms-green/20 bg-cms-green/8 p-4 text-sm text-cms-green'
            : 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
    };

    const activateSettingsTab = (tab) => {
        settingsManager.querySelectorAll('[data-settings-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.settingsPanel !== tab));
        settingsManager.querySelectorAll('[data-settings-tab]').forEach((button) => {
            const active = button.dataset.settingsTab === tab;
            button.classList.toggle('border-cms-green', active);
            button.classList.toggle('text-cms-green', active);
            button.classList.toggle('border-transparent', !active);
            button.classList.toggle('text-cms-navy/45', !active);
        });
    };
    settingsManager.querySelectorAll('[data-settings-tab]').forEach((button) => button.addEventListener('click', () => activateSettingsTab(button.dataset.settingsTab)));

    const fillSettingForm = (group, values) => {
        const form = settingsManager.querySelector(`[data-setting-form="${group}"]`);
        if (!form) return;
        form.querySelectorAll('[data-setting-key]').forEach((input) => {
            let value = values[input.dataset.settingKey];
            // Per-language settings are objects keyed by locale; a legacy plain string belongs to the default language.
            if (input.dataset.settingLocale) {
                value = value && typeof value === 'object' ? value[input.dataset.settingLocale] : (input.dataset.settingLocale === cmsLocales.default ? value : null);
            }
            if (input.type === 'checkbox') input.checked = Boolean(value);
            else input.value = value ?? '';
            input.disabled = !canUpdateWebsite;
            if (input.type === 'hidden' && input.dataset.settingKey.endsWith('_id')) {
                const copy = form.querySelector(`[data-setting-media-copy="${input.dataset.settingKey}"]`);
                if (copy) copy.textContent = value ? 'Media selected' : 'No media selected';
            }
        });
        form.querySelector('[data-setting-save]')?.classList.toggle('hidden', !canUpdateWebsite);
        form.querySelectorAll('[data-setting-media]').forEach((button) => { button.disabled = !canUpdateWebsite; button.classList.toggle('opacity-40', !canUpdateWebsite); });
    };

    const loadWebsiteSettings = () => apiRequest('/api/v1/admin/website-settings').then((payload) => payload.data.forEach((group) => fillSettingForm(group.group, group.settings))).catch(() => showSettingsNotice('Website settings could not be loaded. Check the permission and feature flag.'));

    settingsManager.querySelectorAll('[data-setting-form]').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const group = form.dataset.settingForm;
        const values = {};
        form.querySelectorAll('[data-setting-key]').forEach((input) => {
            const value = input.type === 'checkbox' ? input.checked : (input.value.trim() || null);
            if (input.dataset.settingLocale) {
                values[input.dataset.settingKey] = { ...(values[input.dataset.settingKey] ?? {}), [input.dataset.settingLocale]: value };
            } else {
                values[input.dataset.settingKey] = value;
            }
        });
        const button = form.querySelector('[data-setting-save]');
        button.disabled = true;
        try {
            const payload = await apiRequest(`/api/v1/admin/website-settings/${group}`, { method: 'PUT', body: JSON.stringify(values) });
            fillSettingForm(group, payload.data.settings);
            showSettingsNotice(`${group.slice(0, 1).toUpperCase()}${group.slice(1)} settings saved successfully.`, 'success');
        } catch (error) { showSettingsNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The settings could not be saved.'); }
        finally { button.disabled = false; }
    }));

    const themePanel = settingsManager.querySelector('[data-theme-panel]');

    const renderThemeField = (key, schema, value) => {
        const wrapper = document.createElement('label');
        wrapper.className = 'grid gap-2';
        const label = document.createElement('span');
        label.className = 'text-xs font-semibold';
        label.textContent = schema.label ?? key;

        let input;
        if (schema.type === 'boolean') {
            wrapper.className = 'flex items-center gap-2 text-sm font-semibold';
            input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'size-4 accent-cms-green';
            input.checked = Boolean(value);
        } else if (schema.type === 'select') {
            input = document.createElement('select');
            input.className = 'h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm';
            (schema.options ?? []).forEach((option) => {
                const element = document.createElement('option');
                element.value = option;
                element.textContent = option;
                input.append(element);
            });
            input.value = value ?? schema.default ?? '';
        } else {
            input = document.createElement('input');
            input.type = schema.type === 'integer' ? 'number' : 'text';
            input.className = 'h-11 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm';
            input.value = value ?? '';
        }

        input.dataset.themeKey = key;
        input.dataset.themeType = schema.type;
        input.disabled = !canUpdateWebsite;
        wrapper.append(schema.type === 'boolean' ? input : label, schema.type === 'boolean' ? label : input);
        return wrapper;
    };

    const loadTheme = () => {
        if (!themePanel) return Promise.resolve();
        const fields = themePanel.querySelector('[data-theme-fields]');
        return apiRequest('/api/v1/admin/themes').then((payload) => {
            const { active, settings } = payload.data;
            fields.replaceChildren(...Object.entries(active.settings_schema)
                .map(([key, schema]) => renderThemeField(key, schema, settings[key])));
            themePanel.querySelector('[data-theme-meta]').replaceChildren(...[
                ['Active theme', `${active.name} v${active.version}`],
                ['Slug', active.slug],
                ['Author', active.author ?? '—'],
                ['Installed', `${payload.data.installed.length} theme(s)`],
            ].map(([term, description]) => {
                const row = document.createElement('div');
                row.innerHTML = `<p class="text-xs font-semibold text-cms-navy/45">${term}</p><p class="font-bold">${description}</p>`;
                return row;
            }));
        }).catch(() => showSettingsNotice('The theme could not be loaded.'));
    };

    themePanel?.querySelector('[data-theme-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const values = {};
        themePanel.querySelectorAll('[data-theme-key]').forEach((input) => {
            if (input.dataset.themeType === 'boolean') values[input.dataset.themeKey] = input.checked;
            else if (input.dataset.themeType === 'integer') values[input.dataset.themeKey] = Number(input.value);
            else values[input.dataset.themeKey] = input.value.trim() || null;
        });
        const button = themePanel.querySelector('[data-theme-save]');
        button.disabled = true;
        try {
            await apiRequest('/api/v1/admin/website-settings/theme', { method: 'PUT', body: JSON.stringify(values) });
            showSettingsNotice('Theme settings saved successfully.', 'success');
        } catch (error) { showSettingsNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The theme settings could not be saved.'); }
        finally { button.disabled = false; }
    });

    settingsManager.querySelectorAll('[data-setting-media]').forEach((button) => button.addEventListener('click', () => {
        const key = button.dataset.settingMedia;
        const form = button.closest('[data-setting-form]');
        window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: {
            input: form.querySelector(`[data-setting-key="${key}"]`),
            preview: form.querySelector(`[data-setting-media-preview="${key}"]`),
            copy: form.querySelector(`[data-setting-media-copy="${key}"]`),
        } }));
    }));

    const diagnosticCard = (label, value, status = '') => {
        const card = document.createElement('article');
        card.className = 'rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm';
        const title = document.createElement('p'); title.className = 'text-xs font-bold uppercase tracking-wide text-cms-navy/40'; title.textContent = label.replaceAll('_', ' ');
        const content = document.createElement('p'); content.className = status === 'ok' ? 'mt-2 break-words text-lg font-bold text-cms-green' : (status === 'warning' ? 'mt-2 break-words text-lg font-bold text-cms-gold' : 'mt-2 break-words text-lg font-bold text-cms-navy'); content.textContent = typeof value === 'boolean' ? (value ? 'Yes' : 'No') : String(value ?? 'Unavailable');
        card.append(title, content); return card;
    };

    const loadSystemSettings = () => {
        if (settingsManager.dataset.viewSystemSettings !== 'true') return;
        apiRequest('/api/v1/admin/system/settings').then((payload) => {
            const grid = settingsManager.querySelector('[data-system-runtime]'); grid.replaceChildren();
            Object.entries(payload.data).forEach(([section, entries]) => Object.entries(entries).forEach(([key, value]) => grid.append(diagnosticCard(`${section} · ${key}`, value))));
        }).catch(() => showSettingsNotice('Runtime settings could not be loaded.'));
    };

    const loadSystemHealth = () => {
        if (settingsManager.dataset.viewSystemHealth !== 'true') return;
        apiRequest('/api/v1/admin/system/health').then((payload) => {
            const grid = settingsManager.querySelector('[data-system-health]'); grid.replaceChildren();
            Object.entries(payload.data.checks).forEach(([name, check]) => {
                const details = Object.entries(check).filter(([key]) => key !== 'status').map(([key, value]) => `${key.replaceAll('_', ' ')}: ${value}`).join(' · ');
                const card = diagnosticCard(name, check.status, check.status);
                if (details) { const meta = document.createElement('p'); meta.className = 'mt-2 text-xs leading-5 text-cms-navy/45'; meta.textContent = details; card.append(meta); }
                grid.append(card);
            });
            settingsManager.querySelector('[data-system-checked]').textContent = `Overall status: ${payload.data.status} · Checked ${new Date(payload.data.checked_at).toLocaleString()}`;
        }).catch(() => showSettingsNotice('System health could not be loaded.'));
    };
    settingsManager.querySelector('[data-system-refresh]')?.addEventListener('click', () => { loadSystemSettings(); loadSystemHealth(); });

    const emailForm = settingsManager.querySelector('[data-email-form]');
    const loadEmailSettings = () => {
        if (!emailForm) return;
        apiRequest('/api/v1/admin/email-settings').then((payload) => {
            emailForm.querySelectorAll('[data-email-key]').forEach((input) => {
                const value = payload.data[input.dataset.emailKey];
                if (input.type === 'checkbox') input.checked = Boolean(value);
                else if (!['username', 'password'].includes(input.dataset.emailKey)) input.value = value ?? '';
                input.disabled = !canUpdateEmail;
            });
            const credentialCopy = settingsManager.querySelector('[data-email-credentials]');
            credentialCopy.textContent = payload.data.credentials_configured ? 'Credentials configured. Leave credential fields blank to preserve them.' : 'No SMTP credentials configured.';
            settingsManager.querySelector('[data-email-save]').classList.toggle('hidden', !canUpdateEmail);
        }).catch(() => showSettingsNotice('Email settings could not be loaded. Check the permission and feature flag.'));
    };
    emailForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = {};
        emailForm.querySelectorAll('[data-email-key]').forEach((input) => {
            if (['username', 'password'].includes(input.dataset.emailKey) && !input.value) return;
            body[input.dataset.emailKey] = input.type === 'checkbox' ? input.checked : (input.type === 'number' ? Number(input.value) : (input.value.trim() || null));
        });
        const save = settingsManager.querySelector('[data-email-save]'); save.disabled = true;
        try { await apiRequest('/api/v1/admin/email-settings', { method: 'PUT', body: JSON.stringify(body) }); emailForm.querySelector('[data-email-key="username"]').value = ''; emailForm.querySelector('[data-email-key="password"]').value = ''; loadEmailSettings(); showSettingsNotice('Email settings saved without exposing credentials.', 'success'); }
        catch (error) { showSettingsNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'Email settings could not be saved.'); }
        finally { save.disabled = false; }
    });
    const emailTestForm = settingsManager.querySelector('[data-email-test-form]');
    if (emailTestForm) {
        emailTestForm.classList.toggle('hidden', !canTestEmail);
        emailTestForm.addEventListener('submit', async (event) => {
            event.preventDefault(); const button = settingsManager.querySelector('[data-email-test]'); button.disabled = true;
            try { await apiRequest('/api/v1/admin/email-settings/test', { method: 'POST', body: JSON.stringify({ recipient: settingsManager.querySelector('[data-email-test-recipient]').value.trim() }) }); showSettingsNotice('Test email sent successfully.', 'success'); }
            catch (error) { showSettingsNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The test email could not be sent.'); }
            finally { button.disabled = false; }
        });
    }

    const integrationFields = {
        'google-analytics': [['measurement_id', 'Measurement ID', 'G-XXXXXXXX']],
        'google-search-console': [['verification_code', 'Verification code', 'Content of the google-site-verification meta tag']],
        'google-tag-manager': [['container_id', 'Container ID', 'GTM-XXXXXXX']],
        'meta-pixel': [['pixel_id', 'Pixel ID', 'Numeric ID']],
        'microsoft-clarity': [['project_id', 'Project ID', 'Project ID']],
        'google-maps': [['api_key', 'API key', 'Browser API key']],
        recaptcha: [['site_key', 'Site key', 'Public site key'], ['secret_key', 'Secret key', 'Leave blank to preserve']],
        'custom-scripts': [['head_html', 'Before </head>', '<script>…</script>', 'textarea'], ['body_html', 'Before </body>', '<script>…</script>', 'textarea']],
    };
    const integrationLabels = { 'google-analytics': 'Google Analytics 4', 'google-tag-manager': 'Google Tag Manager', 'meta-pixel': 'Meta Pixel', 'microsoft-clarity': 'Microsoft Clarity', 'google-maps': 'Google Maps', recaptcha: 'Google reCAPTCHA', 'google-search-console': 'Google Search Console', 'custom-scripts': 'Custom scripts' };
    const renderIntegrations = (integrations) => {
        const grid = settingsManager.querySelector('[data-integrations-grid]'); if (!grid) return; grid.replaceChildren();
        // Providers this screen has no form for yet are skipped rather than breaking the whole panel.
        integrations.filter((integration) => integrationFields[integration.provider]).forEach((integration) => {
            const form = document.createElement('form'); form.dataset.integrationProvider = integration.provider; form.className = 'grid content-start gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm';
            const header = document.createElement('div'); header.className = 'flex items-start justify-between gap-3';
            const copy = document.createElement('div'); const title = document.createElement('h4'); title.className = 'font-bold'; title.textContent = integrationLabels[integration.provider]; const updated = document.createElement('p'); updated.className = 'mt-1 text-xs text-cms-navy/40'; updated.textContent = integration.updated_at ? `Updated ${new Date(integration.updated_at).toLocaleDateString()}` : 'Not configured'; copy.append(title, updated);
            const enabledLabel = document.createElement('label'); enabledLabel.className = 'flex items-center gap-2 text-xs font-bold'; const enabled = document.createElement('input'); enabled.type = 'checkbox'; enabled.dataset.integrationKey = 'enabled'; enabled.checked = integration.enabled; enabled.disabled = !canUpdateIntegrations; enabled.className = 'size-4 accent-cms-green'; enabledLabel.append(enabled, document.createTextNode('Enabled')); header.append(copy, enabledLabel); form.append(header);
            integrationFields[integration.provider].forEach(([key, labelText, placeholder, kind]) => { const label = document.createElement('label'); label.className = 'grid gap-2'; const caption = document.createElement('span'); caption.className = 'text-xs font-semibold'; caption.textContent = labelText; const input = document.createElement(kind === 'textarea' ? 'textarea' : 'input'); input.dataset.integrationKey = key; input.placeholder = key === 'secret_key' && integration.configured_secrets.includes(key) ? 'Configured — leave blank to preserve' : placeholder; input.value = key === 'secret_key' ? '' : (integration.config[key] ?? ''); if (kind === 'textarea') { input.rows = 4; input.spellcheck = false; input.className = 'min-h-24 rounded-xl border bg-white px-4 py-3 font-mono text-xs'; } else { input.type = key === 'secret_key' ? 'password' : 'text'; input.className = 'h-11 min-w-0 rounded-xl border bg-white px-4'; } input.disabled = !canUpdateIntegrations; label.append(caption, input); form.append(label); });
            if (integration.configured_secrets.length) { const secret = document.createElement('p'); secret.className = 'text-xs font-semibold text-cms-green'; secret.textContent = `Encrypted secrets configured: ${integration.configured_secrets.join(', ')}`; form.append(secret); }
            if (canUpdateIntegrations) { const save = document.createElement('button'); save.className = 'h-10 justify-self-start rounded-xl bg-cms-green px-4 text-xs font-bold text-white'; save.textContent = 'Save integration'; form.append(save); }
            form.addEventListener('submit', async (event) => { event.preventDefault(); const body = {}; form.querySelectorAll('[data-integration-key]').forEach((input) => { if (input.dataset.integrationKey === 'secret_key' && !input.value) return; body[input.dataset.integrationKey] = input.type === 'checkbox' ? input.checked : (input.value.trim() || null); }); const save = form.querySelector('button'); if (save) save.disabled = true; try { await apiRequest(`/api/v1/admin/integrations/${integration.provider}`, { method: 'PUT', body: JSON.stringify(body) }); loadIntegrations(); showSettingsNotice(`${integrationLabels[integration.provider]} saved securely.`, 'success'); } catch (error) { showSettingsNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The integration could not be saved.'); } finally { if (save) save.disabled = false; } });
            grid.append(form);
        });
    };
    const loadIntegrations = () => { if (!settingsManager.querySelector('[data-integrations-grid]')) return; apiRequest('/api/v1/admin/integrations').then((payload) => renderIntegrations(payload.data)).catch(() => showSettingsNotice('Integrations could not be loaded. Check the permission and feature flag.')); };

    const featureDescriptions = {
        pages: 'Landing pages and reusable content blocks.',
        posts: 'Articles, news, categories, and tags.',
        services: 'Public service catalogue and service pages.',
        portfolio: 'Project showcases and case studies.',
        media: 'Uploads, media library, and media selection.',
        menus: 'Public navigation menus and menu items.',
        'contact-messages': 'Public contact form and CMS inbox.',
        seo: 'SEO metadata, sitemap, and search controls.',
        'website-settings': 'Public identity, branding, and experience settings.',
        email: 'SMTP delivery and automated contact replies.',
        integrations: 'Analytics, maps, pixels, and reCAPTCHA.',
    };
    const renderFeatureFlags = (featureFlags) => {
        const grid = settingsManager.querySelector('[data-feature-flags-grid]');
        if (!grid) return;
        grid.replaceChildren();
        featureFlags.forEach((featureFlag) => {
            const card = document.createElement('article');
            card.className = 'flex items-start gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm';
            const copy = document.createElement('div');
            copy.className = 'min-w-0 flex-1';
            const name = document.createElement('h4');
            name.className = 'font-bold text-cms-navy';
            name.textContent = featureFlag.name.replaceAll('-', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
            const description = document.createElement('p');
            description.className = 'mt-1 text-xs leading-5 text-cms-navy/45';
            description.textContent = featureDescriptions[featureFlag.name] || 'Application module availability.';
            const status = document.createElement('p');
            status.className = featureFlag.enabled ? 'mt-3 text-xs font-bold text-cms-green' : 'mt-3 text-xs font-bold text-cms-navy/35';
            status.textContent = featureFlag.enabled ? 'Enabled' : 'Disabled';
            copy.append(name, description, status);

            const toggleLabel = document.createElement('label');
            toggleLabel.className = 'relative inline-flex shrink-0 items-center';
            const label = document.createElement('span');
            label.className = 'sr-only';
            label.textContent = `Enable ${name.textContent}`;
            const toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = featureFlag.enabled;
            toggle.disabled = !canUpdateFeatureFlags;
            toggle.className = 'peer sr-only';
            const track = document.createElement('span');
            track.className = 'h-7 w-12 rounded-full bg-cms-navy/15 transition peer-checked:bg-cms-green peer-disabled:opacity-45 after:absolute after:start-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5';
            toggleLabel.append(label, toggle, track);
            card.append(copy, toggleLabel);
            grid.append(card);

            toggle.addEventListener('change', async () => {
                const previousValue = !toggle.checked;
                toggle.disabled = true;
                try {
                    const payload = await apiRequest(`/api/v1/admin/feature-flags/${featureFlag.id}`, {
                        method: 'PUT',
                        body: JSON.stringify({ enabled: toggle.checked, metadata: featureFlag.metadata }),
                    });
                    featureFlag.enabled = payload.data.enabled;
                    status.textContent = featureFlag.enabled ? 'Enabled' : 'Disabled';
                    status.className = featureFlag.enabled ? 'mt-3 text-xs font-bold text-cms-green' : 'mt-3 text-xs font-bold text-cms-navy/35';
                    showSettingsNotice(`${name.textContent} is now ${featureFlag.enabled ? 'enabled' : 'disabled'}.`, 'success');
                } catch (error) {
                    toggle.checked = previousValue;
                    showSettingsNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The feature flag could not be updated.');
                } finally {
                    toggle.disabled = !canUpdateFeatureFlags;
                }
            });
        });
    };
    const loadFeatureFlags = () => {
        if (!settingsManager.querySelector('[data-feature-flags-grid]')) return;
        apiRequest('/api/v1/admin/feature-flags')
            .then((payload) => renderFeatureFlags(payload.data))
            .catch(() => showSettingsNotice('Feature flags could not be loaded. Check the view permission.'));
    };

    if (websitePanel) loadWebsiteSettings();
    if (themePanel) loadTheme();
    if (systemPanel) { loadSystemSettings(); loadSystemHealth(); }
    loadEmailSettings();
    loadIntegrations();
    loadFeatureFlags();
}

if (accessManager) {
    const notice = accessManager.querySelector('[data-access-notice]');
    const usersTab = accessManager.querySelector('[data-access-users-tab]');
    const rolesTab = accessManager.querySelector('[data-access-roles-tab]');
    const usersPanel = accessManager.querySelector('[data-users-panel]');
    const rolesPanel = accessManager.querySelector('[data-roles-panel]');
    const canCreateUsers = accessManager.dataset.canCreateUsers === 'true';
    const canUpdateUsers = accessManager.dataset.canUpdateUsers === 'true';
    const canDeleteUsers = accessManager.dataset.canDeleteUsers === 'true';
    const canCreateRoles = accessManager.dataset.canCreateRoles === 'true';
    const canUpdateRoles = accessManager.dataset.canUpdateRoles === 'true';
    const canDeleteRoles = accessManager.dataset.canDeleteRoles === 'true';
    let roles = [];
    let permissions = [];
    let currentUsers = [];
    let userPage = 1;

    const showAccessNotice = (message, type = 'error') => {
        notice.textContent = message;
        notice.className = type === 'success'
            ? 'rounded-xl border border-cms-green/20 bg-cms-green/8 p-4 text-sm text-cms-green'
            : 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
    };

    const activateAccessTab = (tab) => {
        const showUsers = tab === 'users';
        usersPanel?.classList.toggle('hidden', !showUsers);
        rolesPanel?.classList.toggle('hidden', showUsers);
        usersTab?.classList.toggle('border-cms-green', showUsers);
        usersTab?.classList.toggle('text-cms-green', showUsers);
        usersTab?.classList.toggle('border-transparent', !showUsers);
        usersTab?.classList.toggle('text-cms-navy/45', !showUsers);
        rolesTab?.classList.toggle('border-cms-green', !showUsers);
        rolesTab?.classList.toggle('text-cms-green', !showUsers);
        rolesTab?.classList.toggle('border-transparent', showUsers);
        rolesTab?.classList.toggle('text-cms-navy/45', showUsers);
    };
    usersTab?.addEventListener('click', () => activateAccessTab('users'));
    rolesTab?.addEventListener('click', () => activateAccessTab('roles'));

    const userDialog = accessManager.querySelector('[data-user-dialog]');
    const userForm = accessManager.querySelector('[data-user-form]');
    const userId = accessManager.querySelector('[data-user-id]');
    const userName = accessManager.querySelector('[data-user-name]');
    const userEmail = accessManager.querySelector('[data-user-email]');
    const userPassword = accessManager.querySelector('[data-user-password]');
    const userPasswordConfirmation = accessManager.querySelector('[data-user-password-confirmation]');
    const userRoles = accessManager.querySelector('[data-user-roles]');
    const userSave = accessManager.querySelector('[data-user-save]');
    const userDelete = accessManager.querySelector('[data-user-delete]');

    const closeUserDialog = () => { userDialog.classList.add('hidden'); userDialog.classList.remove('flex'); };
    const renderUserRoles = (selected = []) => {
        userRoles.replaceChildren();
        roles.forEach((role) => {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-3 rounded-xl border border-cms-navy/8 bg-white p-3 text-sm font-semibold';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox'; checkbox.value = role.id; checkbox.checked = selected.includes(role.id); checkbox.className = 'size-4 accent-cms-green';
            label.append(checkbox, document.createTextNode(role.label));
            userRoles.append(label);
        });
        if (!roles.length) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-cms-navy/45'; empty.textContent = 'No roles are available to assign.'; userRoles.append(empty);
        }
    };
    const openUserDialog = (user = null) => {
        userForm.reset();
        userId.value = user?.id ?? '';
        userName.value = user?.name ?? '';
        userEmail.value = user?.email ?? '';
        accessManager.querySelector('[data-user-form-title]').textContent = user ? 'Edit user' : 'New user';
        accessManager.querySelectorAll('[data-user-password-field]').forEach((field) => field.classList.toggle('hidden', Boolean(user)));
        userPassword.required = !user; userPasswordConfirmation.required = !user;
        renderUserRoles((user?.roles ?? []).map((role) => role.id));
        userDelete.classList.toggle('hidden', !user || !canDeleteUsers);
        userSave.classList.toggle('hidden', user ? !canUpdateUsers : !canCreateUsers);
        userDialog.classList.remove('hidden'); userDialog.classList.add('flex');
    };

    const renderUsers = (payload) => {
        currentUsers = payload.data;
        const rows = accessManager.querySelector('[data-users-rows]');
        rows.replaceChildren();
        const query = accessManager.querySelector('[data-users-search]').value.trim().toLowerCase();
        const filtered = currentUsers.filter((user) => !query || user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query));
        filtered.forEach((user) => {
            user.roles.forEach((role) => { if (!roles.some((known) => known.id === role.id)) roles.push(role); });
            const row = document.createElement('tr'); row.className = 'border-b border-cms-navy/6 last:border-0';
            const identity = document.createElement('td'); identity.className = 'px-5 py-4';
            const name = document.createElement('p'); name.className = 'font-semibold'; name.textContent = user.name;
            const email = document.createElement('p'); email.className = 'mt-1 text-xs text-cms-navy/40'; email.textContent = user.email; identity.append(name, email);
            const roleCell = document.createElement('td'); roleCell.className = 'px-5 py-4 text-sm text-cms-navy/60'; roleCell.textContent = user.roles.map((role) => role.label).join(', ') || 'No role';
            const updated = document.createElement('td'); updated.className = 'px-5 py-4 text-sm text-cms-navy/45'; updated.textContent = new Date(user.updated_at).toLocaleDateString();
            const action = document.createElement('td'); action.className = 'px-5 py-4 text-right';
            if (canUpdateUsers || canDeleteUsers) { const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'h-9 rounded-lg border bg-white px-3 text-xs font-bold'; edit.textContent = 'Manage'; edit.addEventListener('click', () => openUserDialog(user)); action.append(edit); }
            else action.textContent = 'View only';
            row.append(identity, roleCell, updated, action); rows.append(row);
        });
        if (!filtered.length) { const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 4; cell.className = 'px-5 py-10 text-center text-sm text-cms-navy/45'; cell.textContent = 'No users match this filter.'; row.append(cell); rows.append(row); }
        accessManager.querySelector('[data-users-summary]').textContent = `${payload.meta.total} users`;
        accessManager.querySelector('[data-users-previous]').disabled = payload.meta.current_page <= 1;
        accessManager.querySelector('[data-users-next]').disabled = payload.meta.current_page >= payload.meta.last_page;
    };
    const loadUsers = () => apiRequest(`/api/v1/admin/users?page=${userPage}`).then(renderUsers).catch(() => showAccessNotice('Users could not be loaded.'));
    accessManager.querySelector('[data-users-search]')?.addEventListener('input', () => renderUsers({ data: currentUsers, meta: { total: currentUsers.length, current_page: 1, last_page: 1 } }));
    accessManager.querySelector('[data-users-previous]')?.addEventListener('click', () => { userPage -= 1; loadUsers(); });
    accessManager.querySelector('[data-users-next]')?.addEventListener('click', () => { userPage += 1; loadUsers(); });
    accessManager.querySelector('[data-user-new]')?.addEventListener('click', () => openUserDialog());
    accessManager.querySelector('[data-user-cancel]').addEventListener('click', closeUserDialog);
    userForm.addEventListener('submit', async (event) => {
        event.preventDefault(); const id = userId.value;
        const body = { name: userName.value.trim(), email: userEmail.value.trim(), role_ids: [...userRoles.querySelectorAll('input:checked')].map((input) => input.value) };
        if (!id) { body.password = userPassword.value; body.password_confirmation = userPasswordConfirmation.value; }
        userSave.disabled = true;
        try { await apiRequest(id ? `/api/v1/admin/users/${id}` : '/api/v1/admin/users', { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) }); closeUserDialog(); loadUsers(); showAccessNotice('User saved successfully.', 'success'); }
        catch (error) { showAccessNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The user could not be saved.'); }
        finally { userSave.disabled = false; }
    });
    userDelete.addEventListener('click', async () => {
        if (!userId.value || !window.confirm('Move this user to trash?')) return;
        try { await apiRequest(`/api/v1/admin/users/${userId.value}`, { method: 'DELETE' }); closeUserDialog(); loadUsers(); showAccessNotice('User moved to trash.', 'success'); }
        catch { showAccessNotice('The user could not be deleted. You cannot delete your own account.'); }
    });

    const roleDialog = accessManager.querySelector('[data-role-dialog]');
    const roleForm = accessManager.querySelector('[data-role-form]');
    const roleId = accessManager.querySelector('[data-role-id]');
    const roleName = accessManager.querySelector('[data-role-name]');
    const roleLabel = accessManager.querySelector('[data-role-label]');
    const rolePermissionList = accessManager.querySelector('[data-role-permissions]');
    const roleSave = accessManager.querySelector('[data-role-save]');
    const roleDelete = accessManager.querySelector('[data-role-delete]');
    const closeRoleDialog = () => { roleDialog.classList.add('hidden'); roleDialog.classList.remove('flex'); };
    const renderPermissionChoices = (selected = []) => {
        rolePermissionList.replaceChildren();
        const groups = permissions.reduce((result, permission) => {
            const group = permission.name.split('.')[0];
            (result[group] ??= []).push(permission);
            return result;
        }, {});
        Object.entries(groups).forEach(([group, items]) => {
            const section = document.createElement('div'); section.className = 'rounded-xl border border-cms-navy/8 bg-white p-3';
            const title = document.createElement('p'); title.className = 'mb-2 text-xs font-bold uppercase tracking-wide text-cms-green'; title.textContent = group.replaceAll('-', ' '); section.append(title);
            items.forEach((permission) => { const label = document.createElement('label'); label.className = 'flex items-center gap-2 py-1.5 text-xs'; const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.value = permission.id; checkbox.checked = selected.includes(permission.id); checkbox.className = 'size-4 accent-cms-green'; label.append(checkbox, document.createTextNode(permission.label)); section.append(label); });
            rolePermissionList.append(section);
        });
    };
    const openRoleDialog = (role = null) => {
        roleForm.reset(); roleId.value = role?.id ?? ''; roleName.value = role?.name ?? ''; roleLabel.value = role?.label ?? '';
        accessManager.querySelector('[data-role-form-title]').textContent = role ? 'Edit role' : 'New role';
        renderPermissionChoices((role?.permissions ?? []).map((permission) => permission.id));
        roleDelete.classList.toggle('hidden', !role || !canDeleteRoles); roleSave.classList.toggle('hidden', role ? !canUpdateRoles : !canCreateRoles);
        roleDialog.classList.remove('hidden'); roleDialog.classList.add('flex');
    };
    const renderRoles = () => {
        const grid = accessManager.querySelector('[data-roles-grid]'); if (!grid) return; grid.replaceChildren();
        roles.forEach((role) => { const card = document.createElement('article'); card.className = 'rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm'; const title = document.createElement('h3'); title.className = 'font-bold'; title.textContent = role.label; const name = document.createElement('p'); name.className = 'mt-1 text-xs text-cms-navy/40'; name.textContent = role.name; const count = document.createElement('p'); count.className = 'mt-4 text-sm text-cms-navy/55'; count.textContent = `${role.permissions?.length ?? 0} permissions`; card.append(title, name, count); if (canUpdateRoles || canDeleteRoles) { const manage = document.createElement('button'); manage.type = 'button'; manage.className = 'mt-5 text-xs font-bold text-cms-green'; manage.textContent = 'Manage role →'; manage.addEventListener('click', () => openRoleDialog(role)); card.append(manage); } grid.append(card); });
    };
    const loadRoles = async () => {
        try { const [rolesPayload, permissionsPayload] = await Promise.all([apiRequest('/api/v1/admin/roles'), apiRequest('/api/v1/admin/permissions')]); roles = rolesPayload.data; permissions = permissionsPayload.data; renderRoles(); }
        catch { showAccessNotice('Roles and permissions could not be loaded.'); }
    };
    accessManager.querySelector('[data-role-new]')?.addEventListener('click', () => openRoleDialog());
    accessManager.querySelector('[data-role-cancel]').addEventListener('click', closeRoleDialog);
    roleForm.addEventListener('submit', async (event) => {
        event.preventDefault(); const id = roleId.value; roleSave.disabled = true;
        const body = { name: roleName.value.trim(), label: roleLabel.value.trim(), permission_ids: [...rolePermissionList.querySelectorAll('input:checked')].map((input) => input.value) };
        try { await apiRequest(id ? `/api/v1/admin/roles/${id}` : '/api/v1/admin/roles', { method: id ? 'PUT' : 'POST', body: JSON.stringify(body) }); closeRoleDialog(); await loadRoles(); if (accessManager.dataset.viewUsers === 'true') loadUsers(); showAccessNotice('Role saved successfully.', 'success'); }
        catch (error) { showAccessNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The role could not be saved.'); }
        finally { roleSave.disabled = false; }
    });
    roleDelete.addEventListener('click', async () => {
        if (!roleId.value || !window.confirm('Delete this role? Assigned roles cannot be deleted.')) return;
        try { await apiRequest(`/api/v1/admin/roles/${roleId.value}`, { method: 'DELETE' }); closeRoleDialog(); await loadRoles(); showAccessNotice('Role deleted successfully.', 'success'); }
        catch { showAccessNotice('The role could not be deleted because it may still be assigned to users.'); }
    });

    if (accessManager.dataset.viewRoles === 'true') loadRoles();
    if (accessManager.dataset.viewUsers === 'true') loadUsers();
}

if (contactInbox) {
    const list = contactInbox.querySelector('[data-contact-list]');
    const search = contactInbox.querySelector('[data-contact-search]');
    const status = contactInbox.querySelector('[data-contact-status]');
    const formType = contactInbox.querySelector('[data-contact-form-type]');
    const guestEstimate = contactInbox.querySelector('[data-contact-guest-estimate]');
    const dateFrom = contactInbox.querySelector('[data-contact-from]');
    const dateTo = contactInbox.querySelector('[data-contact-to]');
    const summary = contactInbox.querySelector('[data-contact-summary]');
    const previous = contactInbox.querySelector('[data-contact-previous]');
    const next = contactInbox.querySelector('[data-contact-next]');
    const empty = contactInbox.querySelector('[data-contact-empty]');
    const detail = contactInbox.querySelector('[data-contact-detail]');
    const updateForm = contactInbox.querySelector('[data-contact-update-form]');
    const statusInput = contactInbox.querySelector('[data-contact-detail-status-input]');
    const assigneeInput = contactInbox.querySelector('[data-contact-detail-assignee]');
    const notesInput = contactInbox.querySelector('[data-contact-detail-notes]');
    const saveButton = contactInbox.querySelector('[data-contact-save]');
    const deleteButton = contactInbox.querySelector('[data-contact-delete]');
    const notice = contactInbox.querySelector('[data-contact-notice]');
    const canUpdate = contactInbox.dataset.canUpdate === 'true';
    const canDelete = contactInbox.dataset.canDelete === 'true';
    let currentPage = 1;
    let currentMessage = null;
    let debounce;

    const contactStatusLabel = (value) => value === 'in_progress' ? 'In progress' : `${value.slice(0, 1).toUpperCase()}${value.slice(1)}`;

    const showContactNotice = (message, type = 'error') => {
        notice.textContent = message;
        notice.className = type === 'success'
            ? 'rounded-xl border border-cms-green/20 bg-cms-green/8 p-4 text-sm text-cms-green'
            : 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
    };

    const showMessage = async (id) => {
        try {
            const payload = await apiRequest(`/api/v1/admin/contact-messages/${id}`);
            currentMessage = payload.data;
            const statusBadge = contactInbox.querySelector('[data-contact-detail-status]');
            statusBadge.textContent = contactStatusLabel(currentMessage.status);
            statusBadge.className = currentMessage.status === 'resolved'
                ? 'rounded-full bg-cms-green/10 px-2.5 py-1 text-xs font-bold text-cms-green'
                : (currentMessage.status === 'spam' ? 'rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700' : 'rounded-full bg-cms-gold/15 px-2.5 py-1 text-xs font-bold text-cms-navy');
            contactInbox.querySelector('[data-contact-detail-date]').textContent = new Date(currentMessage.submitted_at).toLocaleString();
            contactInbox.querySelector('[data-contact-detail-subject]').textContent = currentMessage.subject || 'Website enquiry';
            contactInbox.querySelector('[data-contact-detail-sender]').textContent = currentMessage.name;
            const messageBox = contactInbox.querySelector('[data-contact-detail-message]');
            messageBox.textContent = currentMessage.message || '';
            messageBox.classList.toggle('hidden', !currentMessage.message);
            const email = contactInbox.querySelector('[data-contact-detail-email]');
            email.textContent = currentMessage.email || '';
            email.href = currentMessage.email ? `mailto:${currentMessage.email}` : '';
            email.classList.toggle('hidden', !currentMessage.email);
            // Demo requests carry event details; list the ones that were filled in.
            const demoDetails = contactInbox.querySelector('[data-contact-detail-demo]');
            demoDetails.replaceChildren();
            if (currentMessage.form_type === 'demo') {
                const whatsappDigits = (currentMessage.whatsapp || '').replace(/\D+/g, '').replace(/^0/, '62');
                [
                    ['WhatsApp', currentMessage.whatsapp, whatsappDigits ? `https://wa.me/${whatsappDigits}` : null],
                    ['Expected guests', currentMessage.guest_estimate_label],
                    ['Event date', currentMessage.event_date ? new Date(`${currentMessage.event_date}T00:00:00`).toLocaleDateString() : null],
                    ['Interested plan', currentMessage.interested_plan || 'Not sure yet'],
                    ['Language', currentMessage.locale ? localeName(currentMessage.locale) : null],
                    ['Consent given', currentMessage.consented_at ? new Date(currentMessage.consented_at).toLocaleString() : null],
                ].filter(([, value]) => value).forEach(([label, value, href]) => {
                    const term = document.createElement('dt');
                    term.className = 'text-xs font-semibold text-cms-navy/50';
                    term.textContent = label;
                    const description = document.createElement('dd');
                    description.className = 'text-cms-navy';
                    if (href) {
                        const link = document.createElement('a');
                        link.href = href;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'font-semibold text-cms-green';
                        link.textContent = value;
                        description.append(link);
                    } else {
                        description.textContent = value;
                    }
                    demoDetails.append(term, description);
                });
            }
            demoDetails.classList.toggle('hidden', currentMessage.form_type !== 'demo');
            const phone = contactInbox.querySelector('[data-contact-detail-phone]');
            phone.textContent = currentMessage.phone || '';
            phone.href = currentMessage.phone ? `tel:${currentMessage.phone}` : '';
            phone.classList.toggle('hidden', !currentMessage.phone);
            const company = contactInbox.querySelector('[data-contact-detail-company]');
            company.textContent = currentMessage.company ? `Company: ${currentMessage.company}` : '';
            company.classList.toggle('hidden', !currentMessage.company);
            contactInbox.querySelector('[data-contact-detail-technical]').textContent = `IP: ${currentMessage.ip_address || 'Unavailable'} · Browser: ${currentMessage.user_agent || 'Unavailable'}`;
            statusInput.value = currentMessage.status;
            notesInput.value = currentMessage.internal_notes || '';
            if (currentMessage.assignee && ![...assigneeInput.options].some((option) => option.value === currentMessage.assignee.id)) {
                assigneeInput.append(new Option(currentMessage.assignee.name, currentMessage.assignee.id));
            }
            assigneeInput.value = currentMessage.assignee?.id || '';
            [statusInput, assigneeInput, notesInput].forEach((input) => { input.disabled = !canUpdate; });
            saveButton.classList.toggle('hidden', !canUpdate);
            deleteButton.classList.toggle('hidden', !canDelete);
            empty.classList.add('hidden');
            detail.classList.remove('hidden');
            list.querySelectorAll('[data-contact-row]').forEach((row) => row.classList.toggle('bg-cms-canvas', row.dataset.contactRow === id));
        } catch { showContactNotice('The selected message could not be loaded.'); }
    };

    const renderMessages = (payload) => {
        list.replaceChildren();
        payload.data.forEach((message) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.contactRow = message.id;
            button.className = 'grid gap-2 border-b border-cms-navy/8 p-4 text-left hover:bg-cms-canvas/70';
            const top = document.createElement('span');
            top.className = 'flex items-center justify-between gap-3';
            const sender = document.createElement('strong');
            sender.className = 'truncate text-sm text-cms-navy';
            sender.textContent = message.name;
            const time = document.createElement('span');
            time.className = 'shrink-0 text-[0.65rem] text-cms-navy/35';
            time.textContent = new Date(message.submitted_at).toLocaleDateString();
            top.append(sender, time);
            const subject = document.createElement('span');
            subject.className = 'truncate text-xs font-semibold text-cms-navy/65';
            subject.textContent = `${message.form_type === 'demo' ? 'Demo · ' : ''}${message.subject || 'Website enquiry'}`;
            const bottom = document.createElement('span');
            bottom.className = 'flex items-center justify-between gap-3';
            const preview = document.createElement('span');
            preview.className = 'truncate text-xs text-cms-navy/40';
            preview.textContent = message.message || [message.guest_estimate_label, message.interested_plan].filter(Boolean).join(' · ');
            const badge = document.createElement('span');
            badge.className = message.status === 'new' ? 'shrink-0 rounded-full bg-cms-gold/20 px-2 py-0.5 text-[0.65rem] font-bold' : 'shrink-0 text-[0.65rem] font-bold text-cms-green';
            badge.textContent = contactStatusLabel(message.status);
            bottom.append(preview, badge);
            button.append(top, subject, bottom);
            button.addEventListener('click', () => showMessage(message.id));
            list.append(button);
        });
        if (!payload.data.length) {
            const noResults = document.createElement('p');
            noResults.className = 'p-8 text-center text-sm text-cms-navy/45';
            noResults.textContent = 'No messages match these filters.';
            list.append(noResults);
        }
        summary.textContent = `${payload.meta.total} messages`;
        previous.disabled = payload.meta.current_page <= 1;
        next.disabled = payload.meta.current_page >= payload.meta.last_page;
    };

    const loadMessages = () => {
        const params = new URLSearchParams({ page: currentPage, per_page: 15 });
        if (search.value.trim()) params.set('search', search.value.trim());
        if (status.value) params.set('status', status.value);
        if (formType.value) params.set('form_type', formType.value);
        if (guestEstimate.value) params.set('guest_estimate', guestEstimate.value);
        if (dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo.value) params.set('date_to', dateTo.value);
        apiRequest(`/api/v1/admin/contact-messages?${params}`).then(renderMessages).catch(() => showContactNotice('Messages could not be loaded. Check your permission and the Contact Messages feature flag.'));
    };

    search.addEventListener('input', () => { clearTimeout(debounce); currentPage = 1; debounce = setTimeout(loadMessages, 250); });
    [status, formType, guestEstimate, dateFrom, dateTo].forEach((input) => input.addEventListener('change', () => { currentPage = 1; loadMessages(); }));
    previous.addEventListener('click', () => { currentPage -= 1; loadMessages(); });
    next.addEventListener('click', () => { currentPage += 1; loadMessages(); });

    updateForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!currentMessage) return;
        saveButton.disabled = true;
        try {
            await apiRequest(`/api/v1/admin/contact-messages/${currentMessage.id}`, {
                method: 'PUT',
                body: JSON.stringify({ status: statusInput.value, assigned_to: assigneeInput.value || null, internal_notes: notesInput.value.trim() || null }),
            });
            await showMessage(currentMessage.id);
            loadMessages();
            showContactNotice('Message workflow updated successfully.', 'success');
        } catch (error) { showContactNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The message could not be updated.'); }
        finally { saveButton.disabled = false; }
    });

    deleteButton.addEventListener('click', async () => {
        if (!currentMessage || !window.confirm('Move this contact message to trash?')) return;
        try {
            await apiRequest(`/api/v1/admin/contact-messages/${currentMessage.id}`, { method: 'DELETE' });
            currentMessage = null;
            detail.classList.add('hidden');
            empty.classList.remove('hidden');
            loadMessages();
            showContactNotice('Message moved to trash.', 'success');
        } catch { showContactNotice('The message could not be deleted.'); }
    });

    apiRequest('/api/v1/admin/users?per_page=100').then((payload) => payload.data.forEach((user) => assigneeInput.append(new Option(user.name, user.id)))).catch(() => {});
    loadMessages();
}

if (menuManager) {
    const menuList = menuManager.querySelector('[data-menu-list]');
    const workspace = menuManager.querySelector('[data-menu-workspace]');
    const emptyState = menuManager.querySelector('[data-menu-empty]');
    const notice = menuManager.querySelector('[data-menu-notice]');
    const menuForm = menuManager.querySelector('[data-menu-form]');
    const menuId = menuManager.querySelector('[data-menu-id]');
    const menuName = menuManager.querySelector('[data-menu-name]');
    const menuLocation = menuManager.querySelector('[data-menu-location]');
    const menuLocale = menuManager.querySelector('[data-menu-locale]');
    const menuActive = menuManager.querySelector('[data-menu-active]');
    const menuSave = menuManager.querySelector('[data-menu-save]');
    const menuDelete = menuManager.querySelector('[data-menu-delete]');
    const itemsSection = menuManager.querySelector('[data-menu-items-section]');
    const itemsList = menuManager.querySelector('[data-menu-items]');
    const itemForm = menuManager.querySelector('[data-item-form]');
    const itemId = menuManager.querySelector('[data-item-id]');
    const itemLabel = menuManager.querySelector('[data-item-label]');
    const itemType = menuManager.querySelector('[data-item-type]');
    const itemUrl = menuManager.querySelector('[data-item-url]');
    const itemPage = menuManager.querySelector('[data-item-page]');
    const itemParent = menuManager.querySelector('[data-item-parent]');
    const itemTarget = menuManager.querySelector('[data-item-target]');
    const itemPosition = menuManager.querySelector('[data-item-position]');
    const itemUrlField = menuManager.querySelector('[data-item-url-field]');
    const itemPageField = menuManager.querySelector('[data-item-page-field]');
    const canCreate = menuManager.dataset.canCreate === 'true';
    const canUpdate = menuManager.dataset.canUpdate === 'true';
    const canDelete = menuManager.dataset.canDelete === 'true';
    const locationLabels = JSON.parse(menuManager.dataset.menuLocations || '{}');
    const locationLabel = (location) => locationLabels[location] ?? location;
    const ensureLocationOption = (location) => {
        if (!location || [...menuLocation.options].some((option) => option.value === location)) return;
        menuLocation.append(new Option(`${location} (not used by the active theme)`, location));
    };
    let menus = [];
    let activeMenu = null;

    const showMenuNotice = (message, type = 'error') => {
        notice.textContent = message;
        notice.className = type === 'success'
            ? 'rounded-xl border border-cms-green/20 bg-cms-green/8 p-4 text-sm text-cms-green'
            : 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
    };

    const flattenItems = (items, depth = 0) => items.flatMap((item) => [{ ...item, depth }, ...flattenItems(item.children ?? [], depth + 1)]);

    const setItemTypeFields = () => {
        const isPage = itemType.value === 'page';
        itemUrlField.classList.toggle('hidden', isPage);
        itemPageField.classList.toggle('hidden', !isPage);
        itemUrl.required = !isPage;
        itemPage.required = isPage;
    };

    const populateParents = (selectedId = '', excludedId = '') => {
        itemParent.replaceChildren(new Option('No parent', ''));
        flattenItems(activeMenu?.items ?? []).filter((item) => item.id !== excludedId).forEach((item) => {
            const option = new Option(`${'— '.repeat(item.depth)}${item.label}`, item.id);
            option.selected = item.id === selectedId;
            itemParent.append(option);
        });
    };

    const closeItemForm = () => itemForm.classList.add('hidden');

    const openItemForm = (item = null, parentId = '') => {
        itemForm.reset();
        itemId.value = item?.id ?? '';
        itemLabel.value = item?.label ?? '';
        itemType.value = item?.type ?? 'custom';
        itemUrl.value = item?.type === 'custom' ? (item.url ?? '') : '';
        itemPage.value = item?.page?.id ?? '';
        itemTarget.value = item?.target ?? '_self';
        itemPosition.value = item?.position ?? 0;
        menuManager.querySelector('[data-item-form-title]').textContent = item ? 'Edit menu item' : 'Add menu item';
        populateParents(parentId, item?.id ?? '');
        setItemTypeFields();
        itemForm.classList.remove('hidden');
        itemForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const renderItems = () => {
        itemsList.replaceChildren();
        const renderLevel = (items, depth = 0, parentId = '') => items.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-cms-navy/8 bg-white p-3';
            row.style.marginLeft = `${Math.min(depth, 3) * 1.25}rem`;
            const order = document.createElement('span');
            order.className = 'grid size-9 shrink-0 place-items-center rounded-lg bg-cms-canvas text-xs font-bold text-cms-navy/45';
            order.textContent = item.position;
            const copy = document.createElement('div');
            copy.className = 'min-w-0 flex-1';
            const label = document.createElement('p');
            label.className = 'truncate text-sm font-bold text-cms-navy';
            label.textContent = item.label;
            const destination = document.createElement('p');
            destination.className = 'mt-0.5 truncate text-xs text-cms-navy/40';
            destination.textContent = `${item.type === 'page' ? 'Page' : 'Custom'} · ${item.url || 'No destination'}${item.target === '_blank' ? ' · New tab' : ''}`;
            copy.append(label, destination);
            row.append(order, copy);
            if (canUpdate) {
                const addChild = document.createElement('button');
                addChild.type = 'button'; addChild.className = 'text-xs font-bold text-cms-green'; addChild.textContent = '+ Child';
                addChild.addEventListener('click', () => openItemForm(null, item.id));
                const edit = document.createElement('button');
                edit.type = 'button'; edit.className = 'text-xs font-bold text-cms-navy'; edit.textContent = 'Edit';
                edit.addEventListener('click', () => openItemForm(item, parentId));
                const remove = document.createElement('button');
                remove.type = 'button'; remove.className = 'text-xs font-bold text-red-700'; remove.textContent = 'Delete';
                remove.addEventListener('click', async () => {
                    if (!window.confirm(`Delete “${item.label}” and its child items?`)) return;
                    try {
                        await apiRequest(`/api/v1/admin/menus/${activeMenu.id}/items/${item.id}`, { method: 'DELETE' });
                        await selectMenu(activeMenu.id);
                    } catch { showMenuNotice('The menu item could not be deleted.'); }
                });
                // On narrow screens the actions wrap onto their own line instead of being cut off.
                const actions = document.createElement('div');
                actions.className = 'flex w-full justify-end gap-4 border-t border-cms-navy/6 pt-2 sm:w-auto sm:border-0 sm:pt-0';
                actions.append(addChild, edit, remove);
                row.append(actions);
            }
            itemsList.append(row);
            renderLevel(item.children ?? [], depth + 1, item.id);
        });
        renderLevel(activeMenu?.items ?? []);
        if (!activeMenu?.items?.length) {
            const empty = document.createElement('p');
            empty.className = 'py-8 text-center text-sm text-cms-navy/45';
            empty.textContent = 'This menu has no items yet.';
            itemsList.append(empty);
        }
    };

    const renderMenuList = () => {
        menuList.replaceChildren();
        menus.forEach((menu) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = menu.id === activeMenu?.id ? 'rounded-xl bg-cms-navy px-3 py-3 text-left text-white' : 'rounded-xl px-3 py-3 text-left hover:bg-cms-canvas';
            const name = document.createElement('span');
            name.className = 'block truncate text-sm font-bold';
            name.textContent = menu.name;
            const location = document.createElement('span');
            location.className = menu.id === activeMenu?.id ? 'mt-1 block truncate text-xs text-white/55' : 'mt-1 block truncate text-xs text-cms-navy/40';
            location.textContent = `${localeLabel(menu.locale)} · ${locationLabel(menu.location)} · ${menu.is_active ? 'Active' : 'Inactive'}`;
            button.append(name, location);
            button.addEventListener('click', () => selectMenu(menu.id));
            menuList.append(button);
        });
        if (!menus.length) {
            const empty = document.createElement('p');
            empty.className = 'p-3 text-sm text-cms-navy/45';
            empty.textContent = canCreate ? 'No menus yet. Create the first one.' : 'No menus available.';
            menuList.append(empty);
        }
    };

    const showWorkspace = () => {
        workspace.classList.remove('hidden');
        emptyState.classList.add('hidden');
    };

    async function selectMenu(id) {
        try {
            const payload = await apiRequest(`/api/v1/admin/menus/${id}`);
            activeMenu = payload.data;
            menuId.value = activeMenu.id;
            menuName.value = activeMenu.name;
            ensureLocationOption(activeMenu.location);
            menuLocation.value = activeMenu.location;
            menuLocale.value = activeMenu.locale;
            menuLocale.disabled = true;
            menuActive.checked = activeMenu.is_active;
            [menuName, menuLocation, menuActive].forEach((input) => { input.disabled = !canUpdate; });
            menuSave.classList.toggle('hidden', !canUpdate);
            menuDelete.classList.toggle('hidden', !canDelete);
            itemsSection.classList.remove('hidden');
            closeItemForm();
            showWorkspace();
            renderMenuList();
            renderItems();
        } catch { showMenuNotice('The selected menu could not be loaded.'); }
    }

    const loadMenus = async () => {
        try {
            const payload = await apiRequest('/api/v1/admin/menus?per_page=100');
            menus = payload.data;
            renderMenuList();
            if (menus.length && !activeMenu) await selectMenu(menus[0].id);
        } catch { showMenuNotice('Menus could not be loaded. Check your permission and the Menus feature flag.'); }
    };

    menuManager.querySelector('[data-menu-new]')?.addEventListener('click', () => {
        activeMenu = null;
        menuForm.reset();
        menuId.value = '';
        [menuName, menuLocation, menuActive, menuLocale].forEach((input) => { input.disabled = false; });
        menuLocale.value = cmsLocales.default;
        menuActive.checked = true;
        menuSave.classList.toggle('hidden', !canCreate);
        menuDelete.classList.add('hidden');
        itemsSection.classList.add('hidden');
        closeItemForm();
        showWorkspace();
        renderMenuList();
        menuName.focus();
    });

    menuForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = menuId.value;
        menuSave.disabled = true;
        try {
            const payload = await apiRequest(id ? `/api/v1/admin/menus/${id}` : '/api/v1/admin/menus', {
                method: id ? 'PUT' : 'POST',
                body: JSON.stringify({
                    name: menuName.value.trim(),
                    location: menuLocation.value.trim(),
                    is_active: menuActive.checked,
                    ...(id ? {} : { locale: menuLocale.value }),
                }),
            });
            await loadMenus();
            await selectMenu(payload.data.id);
            showMenuNotice('Menu saved successfully.', 'success');
        } catch (error) {
            showMenuNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The menu could not be saved.');
        } finally { menuSave.disabled = false; }
    });

    menuDelete.addEventListener('click', async () => {
        if (!activeMenu || !window.confirm(`Delete the “${activeMenu.name}” menu and all of its items?`)) return;
        try {
            await apiRequest(`/api/v1/admin/menus/${activeMenu.id}`, { method: 'DELETE' });
            activeMenu = null;
            workspace.classList.add('hidden');
            emptyState.classList.remove('hidden');
            await loadMenus();
            showMenuNotice('Menu deleted successfully.', 'success');
        } catch { showMenuNotice('The menu could not be deleted.'); }
    });

    menuManager.querySelector('[data-item-new]')?.addEventListener('click', () => openItemForm());
    menuManager.querySelector('[data-item-cancel]').addEventListener('click', closeItemForm);
    itemType.addEventListener('change', setItemTypeFields);
    itemForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!activeMenu) return;
        const id = itemId.value;
        const payload = {
            label: itemLabel.value.trim(),
            type: itemType.value,
            page_id: itemType.value === 'page' ? itemPage.value : null,
            url: itemType.value === 'custom' ? itemUrl.value.trim() : null,
            target: itemTarget.value,
            parent_id: itemParent.value || null,
            position: Number(itemPosition.value || 0),
        };
        try {
            await apiRequest(id ? `/api/v1/admin/menus/${activeMenu.id}/items/${id}` : `/api/v1/admin/menus/${activeMenu.id}/items`, {
                method: id ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });
            await selectMenu(activeMenu.id);
            showMenuNotice('Menu item saved successfully.', 'success');
        } catch (error) { showMenuNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'The menu item could not be saved.'); }
    });

    apiRequest('/api/v1/public/pages?per_page=100').then((payload) => payload.data.forEach((page) => itemPage.append(new Option(page.title, page.id)))).catch(() => {});
    loadMenus();
}

const pageEditor = document.querySelector('[data-page-editor]');

if (pageEditor) {
    const form = document.querySelector('#page-editor-form');
    const titleInput = pageEditor.querySelector('[data-page-title]');
    const slugInput = pageEditor.querySelector('[data-page-slug]');
    const statusInput = pageEditor.querySelector('[data-page-status]');
    const blockType = pageEditor.querySelector('[data-block-type]');
    const blockList = pageEditor.querySelector('[data-block-list]');
    const blockEmpty = pageEditor.querySelector('[data-block-empty]');
    const notice = pageEditor.querySelector('[data-page-notice]');
    const saveButton = pageEditor.querySelector('[data-save-page]');
    const pageId = pageEditor.dataset.pageId;
    const resource = pageEditor.dataset.resource || 'pages';
    const saveLabel = saveButton.textContent;
    const postExcerpt = pageEditor.querySelector('[data-post-excerpt]');
    const postType = pageEditor.querySelector('[data-post-type]');
    const postCategories = pageEditor.querySelector('[data-post-categories]');
    const postTags = pageEditor.querySelector('[data-post-tags]');
    const postFeatured = pageEditor.querySelector('[data-post-featured]');
    const postCover = pageEditor.querySelector('[data-post-cover]');
    const postCoverPreview = pageEditor.querySelector('[data-post-cover-preview]');
    const postCoverCopy = pageEditor.querySelector('[data-post-cover-copy]');
    const postClearCover = pageEditor.querySelector('[data-post-clear-cover]');
    const postPublishedAt = pageEditor.querySelector('[data-post-published-at]');
    const postSlugPrefix = pageEditor.querySelector('[data-post-slug-prefix]');
    const postExcerptCount = pageEditor.querySelector('[data-post-excerpt-count]');
    const postView = pageEditor.querySelector('[data-post-view]');
    const serviceExcerpt = pageEditor.querySelector('[data-service-excerpt]');
    const serviceIcon = pageEditor.querySelector('[data-service-icon]');
    const servicePosition = pageEditor.querySelector('[data-service-position]');
    const portfolioExcerpt = pageEditor.querySelector('[data-portfolio-excerpt]');
    const portfolioClient = pageEditor.querySelector('[data-portfolio-client]');
    const portfolioDate = pageEditor.querySelector('[data-portfolio-date]');
    const portfolioUrl = pageEditor.querySelector('[data-portfolio-url]');
    const portfolioFeatured = pageEditor.querySelector('[data-portfolio-featured]');
    const portfolioPosition = pageEditor.querySelector('[data-portfolio-position]');
    const portfolioCover = pageEditor.querySelector('[data-portfolio-cover]');
    const portfolioCoverPreview = pageEditor.querySelector('[data-portfolio-cover-preview]');
    const portfolioCoverCopy = pageEditor.querySelector('[data-portfolio-cover-copy]');
    const portfolioCategories = pageEditor.querySelector('[data-portfolio-categories]');
    const portfolioTags = pageEditor.querySelector('[data-portfolio-tags]');
    const contentLocale = pageEditor.querySelector('[data-content-locale]');
    const translationSource = pageEditor.querySelector('[data-translation-source]');
    const translationList = pageEditor.querySelector('[data-translation-list]');
    const editorQuery = new URLSearchParams(window.location.search);
    const translationOf = !pageId && contentLocale ? editorQuery.get('translation_of') : null;
    let slugWasEdited = Boolean(pageId);
    let blockDefinitions = {};

    const showNotice = (message, type = 'error') => {
        notice.textContent = message;
        notice.className = type === 'success'
            ? 'rounded-xl border border-cms-green/20 bg-cms-green/8 p-4 text-sm text-cms-green'
            : 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
        notice.classList.remove('hidden');
        notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    /**
     * Renders a hidden multi-select as a list of toggleable chips; the select stays the source of truth.
     */
    const renderChoiceList = (select) => {
        let list = select.nextElementSibling?.matches('[data-choice-chips]') ? select.nextElementSibling : null;
        if (!list) {
            list = document.createElement('div');
            list.dataset.choiceChips = '';
            list.className = 'flex flex-wrap gap-2';
            select.after(list);
        }
        list.replaceChildren();
        [...select.options].forEach((option) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.setAttribute('aria-pressed', option.selected ? 'true' : 'false');
            chip.className = option.selected
                ? 'inline-flex h-8 items-center gap-1.5 rounded-full border border-cms-green bg-cms-green px-3 text-xs font-semibold text-white'
                : 'inline-flex h-8 items-center gap-1.5 rounded-full border border-cms-navy/12 bg-white px-3 text-xs font-semibold text-cms-navy/70 hover:border-cms-navy/30';
            chip.textContent = `${option.selected ? '✓ ' : ''}${option.textContent}`;
            chip.addEventListener('click', () => { option.selected = !option.selected; renderChoiceList(select); });
            list.append(chip);
        });
        if (!select.options.length) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-cms-navy/40';
            empty.textContent = 'None available for this language yet.';
            list.append(empty);
        }
    };
    const refreshChoiceLists = () => pageEditor.querySelectorAll('select[data-choice-list]').forEach(renderChoiceList);

    const toLocalDateTime = (value) => {
        if (!value) return '';
        const date = new Date(value);
        const pad = (number) => String(number).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };
    const updatePostChrome = () => {
        if (postSlugPrefix) {
            const locale = editorLocale();
            const localePrefix = locale === cmsLocales.default ? '' : `/${locale}`;
            postSlugPrefix.textContent = `${localePrefix}/${postType.value === 'news' ? 'news' : 'blog'}/`;
        }
        if (postExcerptCount) {
            const length = postExcerpt.value.trim().length;
            postExcerptCount.textContent = `${length} / 160`;
            postExcerptCount.classList.toggle('text-red-700', length > 160);
        }
        postClearCover?.classList.toggle('hidden', !postCover.value);
        updatePostSeoPreview();
    };

    // Search & social panel: a quick subset of the post's SEO record, saved right after the post itself.
    const postSeo = pageEditor.querySelector('[data-post-seo]');
    const postSeoEditable = postSeo?.dataset.editable === 'true';
    const postSeoTitle = postSeo?.querySelector('[data-post-seo-title]');
    const postSeoDescription = postSeo?.querySelector('[data-post-seo-description]');
    const postSeoKeyword = postSeo?.querySelector('[data-post-seo-keyword]');
    const postSeoNoindex = postSeo?.querySelector('[data-post-seo-noindex]');
    const postSeoImage = postSeo?.querySelector('[data-post-seo-image]');
    const postSeoImagePreview = postSeo?.querySelector('[data-post-seo-image-preview]');
    const postSeoImageCopy = postSeo?.querySelector('[data-post-seo-image-copy]');
    const postSeoClearImage = postSeo?.querySelector('[data-post-seo-clear-image]');
    let postSeoRecord = null;
    let postSeoDirty = false;

    const withBrand = (title) => {
        const siteName = postSeo?.dataset.siteName || '';
        if (!title || !siteName || title.toLowerCase().includes(siteName.toLowerCase())) return title;
        return `${title} · ${siteName}`;
    };

    const setCount = (element, length, target) => {
        element.textContent = `${length} / ${target}`;
        element.classList.toggle('text-red-700', length > target);
    };

    function updatePostSeoPreview() {
        if (!postSeo) return;
        const title = postSeoTitle.value.trim() || withBrand(titleInput.value.trim()) || 'Post title';
        const description = postSeoDescription.value.trim() || postExcerpt.value.trim() || 'Add an excerpt or a search description so search engines do not pick a random sentence.';
        const host = (postSeo.dataset.siteUrl || '').replace(/^https?:\/\//, '');
        postSeo.querySelector('[data-post-seo-preview-url]').textContent = `${host}${postSlugPrefix?.textContent ?? '/'}${slugInput.value.trim()}`;
        postSeo.querySelector('[data-post-seo-preview-title]').textContent = title;
        postSeo.querySelector('[data-post-seo-preview-description]').textContent = description;
        setCount(postSeo.querySelector('[data-post-seo-title-count]'), postSeoTitle.value.trim().length, 60);
        setCount(postSeo.querySelector('[data-post-seo-description-count]'), postSeoDescription.value.trim().length, 160);
        postSeoClearImage.classList.toggle('hidden', !postSeoImage.value || !postSeoEditable);
    }

    const resetPostSeoImage = () => {
        postSeoImage.value = '';
        postSeoImageCopy.textContent = 'Uses the cover image';
        postSeoImagePreview.replaceChildren('Cover');
    };

    const loadPostSeo = async () => {
        const payload = await apiRequest(`/api/v1/admin/posts/${pageId}/seo`);
        postSeoRecord = payload.data?.id ? payload.data : null;
        postSeoTitle.value = postSeoRecord?.meta_title ?? '';
        postSeoDescription.value = postSeoRecord?.meta_description ?? '';
        postSeoKeyword.value = postSeoRecord?.focus_keyword ?? '';
        postSeoNoindex.checked = postSeoRecord ? !postSeoRecord.robots?.index : false;
        const image = postSeoRecord?.open_graph?.image;
        if (image?.id) {
            postSeoImage.value = image.id;
            postSeoImageCopy.textContent = image.original_name;
            postSeoImagePreview.replaceChildren(mediaVisual(image));
        }
        postSeo.querySelector('[data-post-seo-score]').textContent = postSeoRecord ? `Score ${postSeoRecord.score ?? 0}%` : 'Not set yet';
        updatePostSeoPreview();
    };

    /** PUT replaces the whole record, so fields this panel does not show are carried over unchanged. */
    const savePostSeo = async (postId) => {
        if (!postSeo || !postSeoEditable || !postSeoDirty) return;
        const payload = await apiRequest(`/api/v1/admin/posts/${postId}/seo`, {
            method: 'PUT',
            body: JSON.stringify({
                meta_title: postSeoTitle.value.trim() || null,
                meta_description: postSeoDescription.value.trim() || null,
                focus_keyword: postSeoKeyword.value.trim() || null,
                canonical_url: postSeoRecord?.canonical_url ?? null,
                robots_index: !postSeoNoindex.checked,
                robots_follow: postSeoRecord?.robots?.follow ?? true,
                og_title: postSeoRecord?.open_graph?.title ?? null,
                og_description: postSeoRecord?.open_graph?.description ?? null,
                og_image_id: postSeoImage.value || null,
                twitter_card: postSeoRecord?.twitter_card ?? 'summary_large_image',
                structured_data: postSeoRecord?.structured_data ?? null,
            }),
        });
        postSeoRecord = payload.data;
        postSeoDirty = false;
        postSeo.querySelector('[data-post-seo-score]').textContent = `Score ${postSeoRecord.score ?? 0}%`;
    };

    if (postSeo) {
        if (!postSeoEditable) postSeo.querySelectorAll('input, textarea, button').forEach((control) => { control.disabled = true; });
        [postSeoTitle, postSeoDescription, postSeoKeyword, postSeoNoindex, postSeoImage].forEach((control) => {
            control.addEventListener(control.type === 'checkbox' || control.type === 'hidden' ? 'change' : 'input', () => {
                postSeoDirty = true;
                updatePostSeoPreview();
            });
        });
        [titleInput, slugInput].forEach((input) => input.addEventListener('input', updatePostSeoPreview));
        postSeo.querySelector('[data-post-seo-choose-image]').addEventListener('click', () => window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: { input: postSeoImage, preview: postSeoImagePreview, copy: postSeoImageCopy } })));
        postSeoClearImage.addEventListener('click', () => {
            resetPostSeoImage();
            postSeoDirty = true;
            updatePostSeoPreview();
        });
    }

    const field = (label, key, value = '', multiline = false, type = 'text') => {
        const wrapper = document.createElement('label');
        wrapper.className = 'grid gap-1.5';
        const caption = document.createElement('span');
        caption.className = 'text-xs font-semibold text-cms-navy/60';
        caption.textContent = label;
        const input = document.createElement(multiline ? 'textarea' : 'input');
        if (!multiline) input.type = type;
        input.dataset.field = key;
        input.value = value ?? '';
        input.className = multiline
            ? 'min-h-24 resize-y rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-cms-gold'
            : 'h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold';
        wrapper.append(caption, input);
        return wrapper;
    };

    const mediaField = (label, value = '') => {
        const wrapper = document.createElement('div');
        wrapper.className = 'grid gap-2 sm:col-span-2';
        const caption = document.createElement('span');
        caption.className = 'text-xs font-semibold text-cms-navy/60';
        caption.textContent = label;
        const control = document.createElement('div');
        control.className = 'flex items-center gap-3 rounded-xl border border-cms-navy/10 bg-white p-2';
        const preview = document.createElement('span');
        preview.className = 'grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-cms-canvas text-xs text-cms-navy/35';
        preview.textContent = value ? 'Selected' : 'None';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.dataset.field = 'media_id';
        input.value = value ?? '';
        const copy = document.createElement('span');
        copy.className = 'min-w-0 flex-1 truncate text-xs text-cms-navy/45';
        copy.textContent = value || 'No media selected';
        const choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'h-9 shrink-0 rounded-lg bg-cms-navy px-3 text-xs font-bold text-white';
        choose.disabled = !document.querySelector('[data-media-picker]');
        choose.classList.toggle('opacity-40', choose.disabled);
        choose.textContent = choose.disabled ? 'Unavailable' : 'Choose';
        choose.addEventListener('click', () => window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: { input, preview, copy } })));
        control.append(preview, input, copy, choose);
        wrapper.append(caption, control);
        return wrapper;
    };

    const renderFeatureItem = (container, item = {}) => {
        const row = document.createElement('div');
        row.dataset.featureItem = '';
        row.className = 'grid gap-3 rounded-xl border border-cms-navy/8 bg-cms-canvas/50 p-3 sm:grid-cols-2';
        const title = field('Title', 'title', item.title);
        title.querySelector('input').dataset.itemField = 'title';
        delete title.querySelector('input').dataset.field;
        const icon = field('Icon', 'icon', item.icon);
        icon.querySelector('input').dataset.itemField = 'icon';
        delete icon.querySelector('input').dataset.field;
        const description = field('Description', 'description', item.description, true);
        description.classList.add('sm:col-span-2');
        description.querySelector('textarea').dataset.itemField = 'description';
        delete description.querySelector('textarea').dataset.field;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'justify-self-start text-xs font-bold text-red-700';
        remove.textContent = 'Remove item';
        remove.addEventListener('click', () => row.remove());
        row.append(title, icon, description, remove);
        container.append(row);
    };

    // Schema-driven block forms: blocks with `editor: 'schema'` are built from their field definitions.
    const schemaInputClass = 'h-10 w-full rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold';
    const schemaTextareaClass = 'min-h-24 w-full resize-y rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-cms-gold';
    const wideSchemaTypes = ['text', 'list', 'repeater', 'action', 'media'];

    const schemaCaption = (definition, key) => {
        const caption = document.createElement('span');
        caption.className = 'text-xs font-semibold text-cms-navy/60';
        caption.textContent = `${definition.label ?? key.replaceAll('_', ' ')}${definition.required ? ' *' : ''}`;
        return caption;
    };

    const schemaHelp = (definition) => {
        if (!definition.help) return null;
        const help = document.createElement('span');
        help.className = 'text-[0.7rem] leading-4 text-cms-navy/40';
        help.textContent = definition.help;
        return help;
    };

    const schemaInput = (definition, value) => {
        if (definition.type === 'text' || definition.type === 'list') {
            const textarea = document.createElement('textarea');
            textarea.className = schemaTextareaClass;
            textarea.value = definition.type === 'list' ? (Array.isArray(value) ? value.join('\n') : '') : (value ?? '');
            return textarea;
        }
        if (definition.type === 'select') {
            const select = document.createElement('select');
            select.className = schemaInputClass;
            Object.entries(definition.options ?? {}).forEach(([optionValue, optionLabel]) => select.append(new Option(optionLabel, optionValue)));
            select.value = value ?? definition.default ?? Object.keys(definition.options ?? {})[0] ?? '';
            return select;
        }
        const input = document.createElement('input');
        input.type = 'text';
        input.className = schemaInputClass;
        input.value = value ?? '';
        if (definition.type === 'link') input.placeholder = '/page, #anchor, https://… or whatsapp';
        return input;
    };

    const renderRepeaterRow = (rows, definition, row = {}) => {
        const item = document.createElement('div');
        item.dataset.repeaterRow = '';
        item.className = 'grid gap-3 rounded-xl border border-cms-navy/8 bg-cms-canvas/50 p-3 sm:grid-cols-2';
        Object.entries(definition.fields ?? {}).forEach(([key, child]) => {
            const label = document.createElement('label');
            label.className = `grid gap-1.5${child.type === 'text' ? ' sm:col-span-2' : ''}`;
            const input = schemaInput(child, row[key]);
            input.dataset.rowKey = key;
            label.append(schemaCaption(child, key), input);
            item.append(label);
        });
        const actions = document.createElement('div');
        actions.className = 'flex gap-3 sm:col-span-2';
        [['↑', () => item.previousElementSibling?.before(item)], ['↓', () => item.nextElementSibling?.after(item)], [`Remove ${(definition.item_label ?? 'item').toLowerCase()}`, () => item.remove()]].forEach(([text, onClick]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = text.startsWith('Remove') ? 'text-xs font-bold text-red-700' : 'text-xs font-bold text-cms-navy/45';
            button.textContent = text;
            button.addEventListener('click', onClick);
            actions.append(button);
        });
        item.append(actions);
        rows.append(item);
    };

    const schemaControl = (key, definition, value) => {
        if (definition.type === 'media') {
            const media = mediaField(`${definition.label ?? key}${definition.required ? ' *' : ''}`, value);
            media.dataset.schemaField = key;
            const help = schemaHelp(definition);
            if (help) media.append(help);
            return media;
        }
        const wrapper = document.createElement(['repeater', 'action', 'boolean'].includes(definition.type) ? 'div' : 'label');
        wrapper.dataset.schemaField = key;
        wrapper.className = `grid content-start gap-1.5${wideSchemaTypes.includes(definition.type) ? ' sm:col-span-2' : ''}`;
        if (definition.type === 'boolean') {
            const toggle = document.createElement('label');
            toggle.className = 'flex items-center gap-2 text-sm font-semibold text-cms-navy';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = Boolean(value ?? definition.default);
            checkbox.className = 'size-4 accent-cms-green';
            toggle.append(checkbox, definition.label ?? key);
            wrapper.append(toggle);
        } else if (definition.type === 'action') {
            const pair = document.createElement('div');
            pair.className = 'grid gap-2 sm:grid-cols-2';
            ['label', 'url'].forEach((part) => {
                const input = schemaInput({ type: part === 'url' ? 'link' : 'string' }, value?.[part]);
                input.dataset.actionPart = part;
                input.setAttribute('aria-label', `${definition.label ?? key} ${part === 'url' ? 'link' : 'label'}`);
                if (part === 'label') input.placeholder = 'Button label';
                pair.append(input);
            });
            wrapper.append(schemaCaption(definition, key), pair);
        } else if (definition.type === 'repeater') {
            const rows = document.createElement('div');
            rows.dataset.repeaterRows = '';
            rows.className = 'grid gap-3';
            (Array.isArray(value) && value.length ? value : [{}]).forEach((row) => renderRepeaterRow(rows, definition, row));
            const add = document.createElement('button');
            add.type = 'button';
            add.className = 'justify-self-start rounded-lg border border-cms-green/20 px-3 py-2 text-xs font-bold text-cms-green';
            add.textContent = `+ Add ${(definition.item_label ?? 'item').toLowerCase()}`;
            add.addEventListener('click', () => renderRepeaterRow(rows, definition));
            wrapper.append(schemaCaption(definition, key), rows, add);
        } else {
            wrapper.append(schemaCaption(definition, key), schemaInput(definition, value));
        }
        const help = schemaHelp(definition);
        if (help) wrapper.append(help);
        return wrapper;
    };

    const readSchemaValue = (wrapper, definition) => {
        if (definition.type === 'media') return wrapper.querySelector('input[type="hidden"]').value.trim() || undefined;
        if (definition.type === 'boolean') return wrapper.querySelector('input[type="checkbox"]').checked;
        if (definition.type === 'action') {
            const action = Object.fromEntries([...wrapper.querySelectorAll('[data-action-part]')].map((input) => [input.dataset.actionPart, input.value.trim()]));
            return action.label || action.url ? action : undefined;
        }
        if (definition.type === 'repeater') {
            return [...wrapper.querySelectorAll('[data-repeater-row]')].map((row) => {
                const entry = {};
                row.querySelectorAll('[data-row-key]').forEach((input) => { if (input.value.trim()) entry[input.dataset.rowKey] = input.value.trim(); });
                return entry;
            }).filter((entry) => Object.keys(entry).length);
        }
        const control = wrapper.querySelector('input, textarea, select');
        if (definition.type === 'list') {
            const lines = control.value.split('\n').map((line) => line.trim()).filter(Boolean);
            return lines.length ? lines : undefined;
        }
        return control.value.trim() || undefined;
    };

    const serializeSchemaBlock = (block) => {
        const body = block.querySelector('[data-block-body]');
        const data = {};
        Object.entries(blockDefinitions[block.dataset.blockType]?.fields ?? {}).forEach(([key, definition]) => {
            const wrapper = body.querySelector(`:scope > [data-schema-field="${key}"]`);
            if (!wrapper) return;
            const value = readSchemaValue(wrapper, definition);
            if (value !== undefined) data[key] = value;
        });
        return data;
    };

    const blockFields = (block, type, data) => {
        const body = block.querySelector('[data-block-body]');
        if (blockDefinitions[type]?.editor === 'schema') {
            Object.entries(blockDefinitions[type].fields).forEach(([key, definition]) => body.append(schemaControl(key, definition, data[key])));
            return;
        }
        if (type === 'rich_text') {
            // A div, not the usual <label>: a label wrapping the editable area would steal its clicks.
            const content = document.createElement('div');
            content.className = 'grid gap-1.5 sm:col-span-2';
            const caption = document.createElement('span');
            caption.className = 'text-xs font-semibold text-cms-navy/60';
            caption.textContent = 'Content';
            const textarea = document.createElement('textarea');
            textarea.dataset.field = 'content';
            textarea.value = data.content ?? '';
            textarea.setAttribute('aria-label', 'Content (Markdown)');
            textarea.className = 'min-h-72 resize-y rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-sm leading-6 outline-none focus:border-cms-gold';
            const hint = document.createElement('span');
            hint.className = 'text-xs leading-5 text-cms-navy/45';
            hint.textContent = 'Use H2 / H3 for subheadings: they help readers and search engines scan the article. Pasted formatting is reduced to headings, bold, italic, lists, quotes and links.';
            content.append(caption, textarea, hint);
            body.append(content);
            // Loaded on demand so public pages never download the editor. If it fails, the Markdown textarea still works.
            import('./rich-text-editor.js').then(({ mountRichTextEditor }) => mountRichTextEditor(textarea)).catch(() => {
                hint.textContent = 'Markdown supported: ## Subheading, ### Smaller subheading, **bold**, *italic*, - list item, [link text](https://…).';
            });
        }
        if (type === 'hero') body.append(
            field('Heading', 'heading', data.heading),
            field('Subheading', 'subheading', data.subheading, true),
            mediaField('Background media', data.media_id),
            field('Button label', 'primary_action.label', data.primary_action?.label),
            field('Button URL', 'primary_action.url', data.primary_action?.url, false, 'url'),
        );
        if (type === 'image') body.append(
            mediaField('Image', data.media_id),
            field('Alternative text', 'alt', data.alt),
            field('Caption', 'caption', data.caption, true),
        );
        if (type === 'call_to_action') body.append(
            field('Heading', 'heading', data.heading),
            field('Body', 'body', data.body, true),
            field('Button label', 'label', data.label),
            field('Button URL', 'url', data.url, false, 'url'),
        );
        if (type === 'feature_grid') {
            const items = document.createElement('div');
            items.dataset.featureItems = '';
            items.className = 'grid gap-3 sm:col-span-2';
            (data.items?.length ? data.items : [{}]).forEach((item) => renderFeatureItem(items, item));
            const addItem = document.createElement('button');
            addItem.type = 'button';
            addItem.className = 'justify-self-start rounded-lg border border-cms-green/20 px-3 py-2 text-xs font-bold text-cms-green';
            addItem.textContent = '+ Add feature';
            addItem.addEventListener('click', () => renderFeatureItem(items));
            body.append(items, addItem);
        }
    };

    const addBlock = (type, data = {}, id = '') => {
        const block = document.createElement('article');
        block.dataset.contentBlock = '';
        block.dataset.blockType = type;
        block.dataset.blockId = id ?? '';
        block.className = 'overflow-hidden rounded-2xl border border-cms-navy/10 bg-cms-canvas/35';
        const header = document.createElement('header');
        header.className = 'flex items-center gap-3 border-b border-cms-navy/8 px-4 py-3';
        const handle = document.createElement('span');
        handle.className = 'cursor-grab text-cms-navy/30';
        handle.textContent = '⋮⋮';
        const label = document.createElement('strong');
        label.className = 'flex-1 text-sm text-cms-navy';
        label.textContent = blockDefinitions[type]?.label ?? type.replaceAll('_', ' ');
        const up = document.createElement('button');
        up.type = 'button'; up.className = 'text-xs font-bold text-cms-navy/45'; up.textContent = '↑';
        up.addEventListener('click', () => block.previousElementSibling?.before(block));
        const down = document.createElement('button');
        down.type = 'button'; down.className = 'text-xs font-bold text-cms-navy/45'; down.textContent = '↓';
        down.addEventListener('click', () => block.nextElementSibling?.after(block));
        const remove = document.createElement('button');
        remove.type = 'button'; remove.className = 'text-xs font-bold text-red-700'; remove.textContent = 'Remove';
        remove.addEventListener('click', () => { block.remove(); blockEmpty.classList.toggle('hidden', Boolean(blockList.children.length)); });
        header.append(handle, label, up, down, remove);
        const body = document.createElement('div');
        body.dataset.blockBody = '';
        body.className = 'grid gap-4 p-4 sm:grid-cols-2';
        block.append(header, body);
        blockFields(block, type, data ?? {});
        blockList.append(block);
        blockEmpty.classList.add('hidden');
    };

    const nestedValue = (target, path, value) => {
        const keys = path.split('.');
        let cursor = target;
        keys.forEach((key, index) => {
            if (index === keys.length - 1) cursor[key] = value;
            else cursor = cursor[key] ??= {};
        });
    };

    const serializeBlocks = () => [...blockList.querySelectorAll('[data-content-block]')].map((block) => {
        if (blockDefinitions[block.dataset.blockType]?.editor === 'schema') {
            return {
                ...(block.dataset.blockId ? { id: block.dataset.blockId } : {}),
                type: block.dataset.blockType,
                data: serializeSchemaBlock(block),
            };
        }
        const data = {};
        block.querySelectorAll('[data-field]').forEach((input) => {
            const value = input.value.trim();
            if (value) nestedValue(data, input.dataset.field, value);
        });
        if (block.dataset.blockType === 'feature_grid') {
            data.items = [...block.querySelectorAll('[data-feature-item]')].map((item) => {
                const feature = {};
                item.querySelectorAll('[data-item-field]').forEach((input) => {
                    const value = input.value.trim();
                    if (value) feature[input.dataset.itemField] = value;
                });
                return feature;
            });
        }
        return {
            ...(block.dataset.blockId ? { id: block.dataset.blockId } : {}),
            type: block.dataset.blockType,
            data,
        };
    });

    function editorLocale() {
        if (!contentLocale) return cmsLocales.default;
        if (!pageId && cmsLocales.options[editorQuery.get('locale')]) return editorQuery.get('locale');
        return contentLocale.value || cmsLocales.default;
    }

    // Categories are per-language, so the post's category list follows its language.
    const reloadCategories = async (locale, selectedIds = []) => {
        if (!postCategories) return;
        const payload = await apiRequest(`/api/v1/public/categories?locale=${encodeURIComponent(locale)}`);
        postCategories.replaceChildren();
        payload.data.forEach((category) => {
            const option = new Option(category.name, category.id);
            option.selected = selectedIds.includes(category.id);
            postCategories.append(option);
        });
        refreshChoiceLists();
    };

    const renderTranslations = (page) => {
        if (!translationList) return;
        translationList.querySelectorAll('[data-translation-item]').forEach((item) => item.remove());
        Object.keys(cmsLocales.options).filter((locale) => locale !== page.locale).forEach((locale) => {
            const translation = (page.translations ?? []).find((candidate) => candidate.locale === locale);
            const link = document.createElement('a');
            link.dataset.translationItem = '';
            link.className = 'flex items-center gap-2 rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-xs font-semibold text-cms-navy hover:border-cms-gold';
            if (translation) {
                link.href = `/cms/${resource}/${translation.id}/edit`;
                const title = document.createElement('span');
                title.className = 'min-w-0 flex-1 truncate';
                title.textContent = translation.title;
                const state = document.createElement('span');
                state.className = 'text-cms-navy/40';
                state.textContent = translation.status ?? '';
                link.append(localeBadge(locale), title, state);
            } else {
                link.href = `/cms/${resource}/create?translation_of=${encodeURIComponent(page.id)}&locale=${encodeURIComponent(locale)}`;
                const label = document.createElement('span');
                label.className = 'text-cms-green';
                label.textContent = `+ Add ${localeName(locale)} translation`;
                link.append(localeBadge(locale), label);
            }
            translationList.append(link);
        });
        translationList.classList.remove('hidden');
    };

    const loadEditor = async () => {
        try {
            const [schemaPayload, pagePayload, categoriesPayload, tagsPayload] = await Promise.all([
                apiRequest('/api/v1/admin/content-blocks/schema'),
                pageId ? apiRequest(`/api/v1/admin/${resource}/${pageId}`) : Promise.resolve(null),
                ['posts', 'portfolio'].includes(resource) ? apiRequest(`/api/v1/public/categories?locale=${encodeURIComponent(editorLocale())}`) : Promise.resolve(null),
                ['posts', 'portfolio'].includes(resource) ? apiRequest('/api/v1/public/tags') : Promise.resolve(null),
            ]);
            if (contentLocale) {
                contentLocale.value = editorLocale();
                contentLocale.disabled = Boolean(pageId || translationOf);
            }
            blockDefinitions = schemaPayload.data;
            Object.entries(blockDefinitions).forEach(([type, definition]) => {
                const option = document.createElement('option');
                option.value = type;
                option.textContent = definition.label;
                blockType.append(option);
            });
            const populateTaxonomy = (select, payload) => payload?.data.forEach((taxonomy) => {
                const option = document.createElement('option');
                option.value = taxonomy.id;
                option.textContent = taxonomy.name;
                select.append(option);
            });
            if (postCategories) populateTaxonomy(postCategories, categoriesPayload);
            if (postTags) populateTaxonomy(postTags, tagsPayload);
            if (portfolioCategories) populateTaxonomy(portfolioCategories, categoriesPayload);
            if (portfolioTags) populateTaxonomy(portfolioTags, tagsPayload);
            if (pagePayload) {
                const page = pagePayload.data;
                titleInput.value = page.title;
                slugInput.value = page.slug;
                statusInput.value = page.status;
                if (postExcerpt) postExcerpt.value = page.excerpt ?? '';
                if (postType) postType.value = page.type;
                if (postCategories) [...postCategories.options].forEach((option) => { option.selected = page.categories.some((category) => category.id === option.value); });
                if (postTags) [...postTags.options].forEach((option) => { option.selected = page.tags.some((tag) => tag.id === option.value); });
                if (postFeatured) postFeatured.checked = Boolean(page.is_featured);
                if (postPublishedAt) postPublishedAt.value = toLocalDateTime(page.published_at);
                if (postView && page.status === 'published' && page.canonical_url) {
                    postView.href = page.canonical_url;
                    postView.classList.replace('hidden', 'inline-flex');
                }
                if (postCover) postCover.value = page.cover_image?.id ?? '';
                if (postCoverCopy) postCoverCopy.textContent = page.cover_image?.original_name ?? 'No media selected';
                if (postCoverPreview && page.cover_image?.url) {
                    const image = document.createElement('img');
                    image.src = page.cover_image.url;
                    image.alt = page.cover_image.alt_text || page.title;
                    image.className = 'h-full w-full object-cover';
                    postCoverPreview.replaceChildren(image);
                }
                if (serviceExcerpt) serviceExcerpt.value = page.excerpt ?? '';
                if (serviceIcon) serviceIcon.value = page.icon ?? '';
                if (servicePosition) servicePosition.value = page.position ?? 0;
                if (portfolioExcerpt) portfolioExcerpt.value = page.excerpt ?? '';
                if (portfolioClient) portfolioClient.value = page.client ?? '';
                if (portfolioDate) portfolioDate.value = page.project_date?.slice(0, 10) ?? '';
                if (portfolioUrl) portfolioUrl.value = page.external_url ?? '';
                if (portfolioFeatured) portfolioFeatured.checked = page.is_featured;
                if (portfolioPosition) portfolioPosition.value = page.position ?? 0;
                if (portfolioCover) portfolioCover.value = page.cover_image?.id ?? '';
                if (portfolioCoverCopy) portfolioCoverCopy.textContent = page.cover_image?.original_name ?? 'No media selected';
                if (portfolioCoverPreview && page.cover_image?.url) {
                    const image = document.createElement('img');
                    image.src = page.cover_image.url;
                    image.alt = page.cover_image.alt_text || page.title;
                    image.className = 'h-full w-full object-cover';
                    portfolioCoverPreview.replaceChildren(image);
                }
                if (portfolioCategories) [...portfolioCategories.options].forEach((option) => { option.selected = page.categories.some((category) => category.id === option.value); });
                if (portfolioTags) [...portfolioTags.options].forEach((option) => { option.selected = page.tags.some((tag) => tag.id === option.value); });
                page.blocks.forEach((block) => addBlock(block.type, block.data, block.id));
                if (contentLocale) {
                    contentLocale.value = page.locale;
                    renderTranslations(page);
                    await reloadCategories(page.locale, (page.categories ?? []).map((category) => category.id));
                }
            }
            if (translationOf) {
                // Start the translation from a copy of the source so only the wording needs changing.
                const source = (await apiRequest(`/api/v1/admin/${resource}/${encodeURIComponent(translationOf)}`)).data;
                titleInput.value = source.title;
                if (postExcerpt) postExcerpt.value = source.excerpt ?? '';
                if (postType) postType.value = source.type;
                if (postTags) [...postTags.options].forEach((option) => { option.selected = (source.tags ?? []).some((tag) => tag.id === option.value); });
                source.blocks.forEach((block) => addBlock(block.type, block.data));
                translationSource.textContent = `Translating “${source.title}” (${localeName(source.locale)}) into ${localeName(editorLocale())}. Write the URL slug in the new language.`;
                translationSource.classList.remove('hidden');
            }
            refreshChoiceLists();
            if (resource === 'posts') updatePostChrome();
        } catch (error) {
            showNotice('The page editor could not be loaded. Check your permissions and feature flags.');
            saveButton.disabled = true;
        }
    };

    contentLocale?.addEventListener('change', () => { reloadCategories(contentLocale.value).catch(() => {}); });
    titleInput.addEventListener('input', () => {
        if (!slugWasEdited) slugInput.value = titleInput.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
    slugInput.addEventListener('input', () => { slugWasEdited = true; });
    pageEditor.querySelector('[data-add-block]').addEventListener('click', () => addBlock(blockType.value));
    if (resource === 'posts') {
        [postType, contentLocale].forEach((input) => input?.addEventListener('change', updatePostChrome));
        postExcerpt.addEventListener('input', updatePostChrome);
        postCover.addEventListener('change', updatePostChrome);
        postClearCover.addEventListener('click', () => {
            postCover.value = '';
            postCoverCopy.textContent = 'No media selected';
            postCoverPreview.replaceChildren('No cover yet');
            updatePostChrome();
        });
    }
    pageEditor.querySelector('[data-post-choose-cover]')?.addEventListener('click', () => window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: { input: postCover, preview: postCoverPreview, copy: postCoverCopy } })));
    pageEditor.querySelector('[data-portfolio-choose-cover]')?.addEventListener('click', () => window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: { input: portfolioCover, preview: portfolioCoverPreview, copy: portfolioCoverCopy } })));
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        saveButton.disabled = true;
        saveButton.textContent = 'Saving…';
        notice.classList.add('hidden');
        try {
            const requestPayload = {
                title: titleInput.value.trim(),
                slug: slugInput.value.trim(),
                status: statusInput.value,
                blocks: serializeBlocks(),
            };
            if (contentLocale && !pageId) {
                requestPayload.locale = editorLocale();
                if (translationOf) requestPayload.translation_of = translationOf;
            }
            if (resource === 'posts') {
                requestPayload.type = postType.value;
                requestPayload.excerpt = postExcerpt.value.trim() || null;
                requestPayload.category_ids = [...postCategories.selectedOptions].map((option) => option.value);
                requestPayload.tag_ids = [...postTags.selectedOptions].map((option) => option.value);
                requestPayload.is_featured = postFeatured.checked;
                requestPayload.cover_image_id = postCover.value || null;
                if (postPublishedAt.value) requestPayload.published_at = new Date(postPublishedAt.value).toISOString();
            }
            if (resource === 'services') {
                requestPayload.excerpt = serviceExcerpt.value.trim() || null;
                requestPayload.icon = serviceIcon.value.trim() || null;
                requestPayload.position = Number(servicePosition.value || 0);
            }
            if (resource === 'portfolio') {
                requestPayload.excerpt = portfolioExcerpt.value.trim() || null;
                requestPayload.client = portfolioClient.value.trim() || null;
                requestPayload.project_date = portfolioDate.value || null;
                requestPayload.external_url = portfolioUrl.value.trim() || null;
                requestPayload.is_featured = portfolioFeatured.checked;
                requestPayload.position = Number(portfolioPosition.value || 0);
                requestPayload.cover_image_id = portfolioCover.value || null;
                requestPayload.category_ids = [...portfolioCategories.selectedOptions].map((option) => option.value);
                requestPayload.tag_ids = [...portfolioTags.selectedOptions].map((option) => option.value);
            }
            const payload = await apiRequest(pageId ? `/api/v1/admin/${resource}/${pageId}` : `/api/v1/admin/${resource}`, {
                method: pageId ? 'PUT' : 'POST',
                body: JSON.stringify(requestPayload),
            });
            if (resource === 'posts') {
                try {
                    await savePostSeo(payload.data.id);
                } catch (error) {
                    const messages = Object.values(error.payload?.errors ?? {}).flat();
                    showNotice(`The post was saved, but its search settings were not: ${messages.join(' ') || 'please try again.'}`);
                    if (!pageId) window.setTimeout(() => window.location.assign(`/cms/${resource}/${payload.data.id}/edit`), 2500);
                    return;
                }
            }
            if (!pageId) window.location.assign(`/cms/${resource}/${payload.data.id}/edit`);
            else if (resource === 'posts') {
                postPublishedAt.value = toLocalDateTime(payload.data.published_at);
                postView.href = payload.data.canonical_url ?? '#';
                postView.classList.toggle('hidden', payload.data.status !== 'published');
                postView.classList.toggle('inline-flex', payload.data.status === 'published');
            }
            if (pageId) showNotice(`${resource === 'posts' ? 'Post' : (resource === 'services' ? 'Service' : (resource === 'portfolio' ? 'Project' : 'Page'))} saved successfully.`, 'success');
        } catch (error) {
            const messages = Object.values(error.payload?.errors ?? {}).flat();
            showNotice(messages.length ? messages.join(' ') : 'The page could not be saved.');
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = saveLabel;
        }
    });

    pageEditor.querySelector('[data-delete-page]')?.addEventListener('click', async () => {
        if (!window.confirm(`Delete this ${resource === 'posts' ? 'post' : (resource === 'services' ? 'service' : (resource === 'portfolio' ? 'project' : 'page'))}? You can restore it through the API.`)) return;
        try {
            await apiRequest(`/api/v1/admin/${resource}/${pageId}`, { method: 'DELETE' });
            window.location.assign(pageEditor.dataset.indexUrl);
        } catch (error) {
            showNotice('The page could not be deleted.');
        }
    });
    loadEditor();
    if (postSeo && pageId) loadPostSeo().catch(() => { postSeo.querySelector('[data-post-seo-score]').textContent = 'Could not load'; });
    else if (postSeo) updatePostSeoPreview();
}

const formatBytes = (bytes = 0) => {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const unit = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / (1024 ** unit)).toFixed(unit ? 1 : 0)} ${units[unit]}`;
};

const mediaVisual = (media, className = '') => {
    if (media.mime_type?.startsWith('image/')) {
        const image = document.createElement('img');
        image.src = media.url;
        image.alt = media.alt_text || media.original_name;
        image.loading = 'lazy';
        image.className = `h-full w-full object-cover ${className}`;
        return image;
    }

    const file = document.createElement('span');
    file.className = `grid h-full w-full place-items-center bg-cms-navy text-sm font-bold text-white ${className}`;
    file.textContent = media.extension?.toUpperCase() || 'FILE';
    return file;
};

const mediaPicker = document.querySelector('[data-media-picker]');
let mediaPickerTarget = null;
let mediaPickerDebounce;

if (mediaPicker) {
    const pickerGrid = mediaPicker.querySelector('[data-media-picker-grid]');
    const pickerSearch = mediaPicker.querySelector('[data-media-picker-search]');

    const closePicker = () => {
        mediaPicker.classList.add('hidden');
        mediaPicker.classList.remove('flex');
        mediaPickerTarget = null;
    };

    const loadPicker = () => {
        const params = new URLSearchParams({ per_page: 48 });
        if (pickerSearch.value.trim()) params.set('search', pickerSearch.value.trim());
        pickerGrid.replaceChildren();

        apiRequest(`/api/v1/admin/media?${params}`)
            .then((payload) => {
                payload.data.forEach((media) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'group overflow-hidden rounded-2xl border border-cms-navy/8 bg-white text-left hover:border-cms-gold focus:border-cms-gold focus:outline-none focus:ring-3 focus:ring-cms-gold/20';
                    const visual = document.createElement('span');
                    visual.className = 'block aspect-square overflow-hidden bg-cms-navy/5';
                    visual.append(mediaVisual(media));
                    const name = document.createElement('span');
                    name.className = 'block truncate px-3 py-2.5 text-xs font-semibold text-cms-navy';
                    name.textContent = media.original_name;
                    button.append(visual, name);
                    button.addEventListener('click', () => {
                        if (!mediaPickerTarget) return;
                        mediaPickerTarget.input.value = media.id;
                        mediaPickerTarget.copy.textContent = media.original_name;
                        mediaPickerTarget.preview.replaceChildren(mediaVisual(media));
                        mediaPickerTarget.input.dispatchEvent(new Event('change'));
                        closePicker();
                    });
                    pickerGrid.append(button);
                });

                if (!payload.data.length) {
                    const empty = document.createElement('p');
                    empty.className = 'col-span-full py-12 text-center text-sm text-cms-navy/45';
                    empty.textContent = 'No media found.';
                    pickerGrid.append(empty);
                }
            })
            .catch(() => {
                const error = document.createElement('p');
                error.className = 'col-span-full py-12 text-center text-sm text-red-700';
                error.textContent = 'Media could not be loaded.';
                pickerGrid.replaceChildren(error);
            });
    };

    window.addEventListener('cms:choose-media', (event) => {
        mediaPickerTarget = event.detail;
        mediaPicker.classList.remove('hidden');
        mediaPicker.classList.add('flex');
        pickerSearch.value = '';
        loadPicker();
    });
    mediaPicker.querySelectorAll('[data-media-picker-close]').forEach((button) => button.addEventListener('click', closePicker));
    mediaPicker.addEventListener('click', (event) => { if (event.target === mediaPicker) closePicker(); });
    pickerSearch.addEventListener('input', () => {
        window.clearTimeout(mediaPickerDebounce);
        mediaPickerDebounce = window.setTimeout(loadPicker, 250);
    });
}

const mediaLibrary = document.querySelector('[data-media-library]');

if (mediaLibrary) {
    const grid = mediaLibrary.querySelector('[data-media-grid]');
    const search = mediaLibrary.querySelector('[data-media-search]');
    const folder = mediaLibrary.querySelector('[data-media-folder]');
    const trash = mediaLibrary.querySelector('[data-media-trash]');
    const summary = mediaLibrary.querySelector('[data-media-summary]');
    const previous = mediaLibrary.querySelector('[data-media-previous]');
    const next = mediaLibrary.querySelector('[data-media-next]');
    const errorBox = mediaLibrary.querySelector('[data-media-error]');
    const detail = mediaLibrary.querySelector('[data-media-detail]');
    const uploadModal = mediaLibrary.querySelector('[data-upload-modal]');
    const canUpdate = mediaLibrary.dataset.canUpdate === 'true';
    const canDelete = mediaLibrary.dataset.canDelete === 'true';
    const canRestore = mediaLibrary.dataset.canRestore === 'true';
    let currentPage = 1;
    let currentMedia = null;
    let filterDebounce;

    const detailForm = detail.querySelector('[data-detail-form]');
    const detailFolder = detail.querySelector('[data-detail-folder]');
    const detailAlt = detail.querySelector('[data-detail-alt]');
    const detailCaption = detail.querySelector('[data-detail-caption]');
    const detailSave = detail.querySelector('[data-detail-save]');
    const detailDelete = detail.querySelector('[data-detail-delete]');
    const detailRestore = detail.querySelector('[data-detail-restore]');
    const detailError = detail.querySelector('[data-detail-error]');

    const closeDetail = () => {
        detail.classList.add('hidden');
        detail.classList.remove('flex');
        currentMedia = null;
    };

    const openDetail = (media) => {
        currentMedia = media;
        detail.querySelector('[data-detail-name]').textContent = media.original_name;
        const dimensions = media.metadata?.width ? ` · ${media.metadata.width}×${media.metadata.height}` : '';
        detail.querySelector('[data-detail-meta]').textContent = `${media.extension?.toUpperCase()} · ${formatBytes(media.size)}${dimensions}`;
        detail.querySelector('[data-detail-preview]').replaceChildren(mediaVisual(media, 'max-h-[28rem] object-contain'));
        detailFolder.value = media.folder ?? '';
        detailAlt.value = media.alt_text ?? '';
        detailCaption.value = media.caption ?? '';
        [detailFolder, detailAlt, detailCaption].forEach((input) => { input.disabled = !canUpdate || Boolean(media.deleted_at); });
        detailSave.classList.toggle('hidden', !canUpdate || Boolean(media.deleted_at));
        detailDelete.classList.toggle('hidden', !canDelete || Boolean(media.deleted_at));
        detailRestore.classList.toggle('hidden', !canRestore || !media.deleted_at);
        detailError.classList.add('hidden');
        detail.classList.remove('hidden');
        detail.classList.add('flex');
    };

    const renderLibrary = (payload) => {
        grid.replaceChildren();
        payload.data.forEach((media) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'group overflow-hidden rounded-2xl border border-cms-navy/8 bg-white/70 text-left shadow-sm hover:-translate-y-0.5 hover:border-cms-gold hover:shadow-md';
            const visual = document.createElement('span');
            visual.className = 'relative block aspect-square overflow-hidden bg-cms-navy/5';
            visual.append(mediaVisual(media));
            if (media.deleted_at) {
                const trashed = document.createElement('span');
                trashed.className = 'absolute top-2 left-2 rounded-full bg-red-700 px-2 py-1 text-[0.65rem] font-bold text-white';
                trashed.textContent = 'Trash';
                visual.append(trashed);
            }
            const copy = document.createElement('span');
            copy.className = 'block p-3';
            const name = document.createElement('span');
            name.className = 'block truncate text-sm font-semibold text-cms-navy';
            name.textContent = media.original_name;
            const meta = document.createElement('span');
            meta.className = 'mt-1 block truncate text-xs text-cms-navy/40';
            meta.textContent = `${media.folder || 'Root'} · ${formatBytes(media.size)}`;
            copy.append(name, meta);
            card.append(visual, copy);
            card.addEventListener('click', () => openDetail(media));
            grid.append(card);
        });

        if (!payload.data.length) {
            const empty = document.createElement('div');
            empty.className = 'col-span-full rounded-2xl border border-dashed border-cms-navy/15 px-5 py-14 text-center text-sm text-cms-navy/45';
            empty.textContent = trash?.checked ? 'The trash is empty.' : 'No media matches these filters.';
            grid.append(empty);
        }
        summary.textContent = `Showing ${payload.meta.from ?? 0}–${payload.meta.to ?? 0} of ${payload.meta.total} files`;
        previous.disabled = payload.meta.current_page <= 1;
        next.disabled = payload.meta.current_page >= payload.meta.last_page;
        errorBox.classList.add('hidden');
    };

    const loadLibrary = () => {
        const params = new URLSearchParams({ page: currentPage, per_page: 25 });
        if (search.value.trim()) params.set('search', search.value.trim());
        if (folder.value.trim()) params.set('folder', folder.value.trim());
        if (trash?.checked) params.set('trashed', '1');
        apiRequest(`/api/v1/admin/media?${params}`).then(renderLibrary).catch(() => {
            errorBox.textContent = 'Media could not be loaded. Check your permission and the Media feature flag.';
            errorBox.classList.remove('hidden');
        });
    };

    [search, folder].forEach((input) => input.addEventListener('input', () => {
        window.clearTimeout(filterDebounce);
        currentPage = 1;
        filterDebounce = window.setTimeout(loadLibrary, 250);
    }));
    trash?.addEventListener('change', () => { currentPage = 1; loadLibrary(); });
    previous.addEventListener('click', () => { currentPage -= 1; loadLibrary(); });
    next.addEventListener('click', () => { currentPage += 1; loadLibrary(); });
    detail.querySelector('[data-close-detail]').addEventListener('click', closeDetail);
    detail.addEventListener('click', (event) => { if (event.target === detail) closeDetail(); });

    detailForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!currentMedia) return;
        detailSave.disabled = true;
        try {
            const payload = await apiRequest(`/api/v1/admin/media/${currentMedia.id}`, {
                method: 'PUT',
                body: JSON.stringify({ folder: detailFolder.value.trim() || null, alt_text: detailAlt.value.trim() || null, caption: detailCaption.value.trim() || null }),
            });
            currentMedia = payload.data;
            closeDetail();
            loadLibrary();
        } catch (error) {
            detailError.textContent = Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'Media could not be updated.';
            detailError.classList.remove('hidden');
        } finally {
            detailSave.disabled = false;
        }
    });

    detailDelete.addEventListener('click', async () => {
        if (!currentMedia || !window.confirm('Move this media item to trash?')) return;
        try {
            await apiRequest(`/api/v1/admin/media/${currentMedia.id}`, { method: 'DELETE' });
            closeDetail(); loadLibrary();
        } catch { detailError.textContent = 'Media could not be deleted.'; detailError.classList.remove('hidden'); }
    });
    detailRestore.addEventListener('click', async () => {
        if (!currentMedia) return;
        try {
            await apiRequest(`/api/v1/admin/media/${currentMedia.id}/restore`, { method: 'POST' });
            closeDetail(); loadLibrary();
        } catch { detailError.textContent = 'Media could not be restored.'; detailError.classList.remove('hidden'); }
    });

    if (uploadModal) {
        const uploadForm = uploadModal.querySelector('[data-upload-form]');
        const uploadFile = uploadModal.querySelector('[data-upload-file]');
        const uploadError = uploadModal.querySelector('[data-upload-error]');
        const uploadSubmit = uploadModal.querySelector('[data-upload-submit]');
        const closeUpload = () => { uploadModal.classList.add('hidden'); uploadModal.classList.remove('flex'); uploadForm.reset(); };
        mediaLibrary.querySelector('[data-open-upload]').addEventListener('click', () => { uploadModal.classList.remove('hidden'); uploadModal.classList.add('flex'); });
        uploadModal.querySelectorAll('[data-close-upload]').forEach((button) => button.addEventListener('click', closeUpload));
        uploadFile.addEventListener('change', () => { uploadModal.querySelector('[data-upload-filename]').textContent = uploadFile.files[0]?.name || 'Choose a file'; });
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            uploadSubmit.disabled = true;
            uploadSubmit.textContent = 'Uploading…';
            try {
                await apiRequest('/api/v1/admin/media', { method: 'POST', body: new FormData(uploadForm) });
                closeUpload(); loadLibrary();
            } catch (error) {
                uploadError.textContent = Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'Upload failed.';
                uploadError.classList.remove('hidden');
            } finally {
                uploadSubmit.disabled = false;
                uploadSubmit.textContent = 'Upload';
            }
        });
    }
    loadLibrary();
}

const seoManager = document.querySelector('[data-seo-manager]');

if (seoManager) {
    const modules = JSON.parse(seoManager.dataset.modules || '[]');
    const canUpdate = seoManager.dataset.canUpdate === 'true';
    const moduleSelect = seoManager.querySelector('[data-seo-module]');
    const search = seoManager.querySelector('[data-seo-search]');
    const contentGrid = seoManager.querySelector('[data-seo-content]');
    const notice = seoManager.querySelector('[data-seo-notice]');
    const dialog = seoManager.querySelector('[data-seo-dialog]');
    const form = seoManager.querySelector('[data-seo-form]');
    const save = seoManager.querySelector('[data-seo-save]');
    const imageInput = seoManager.querySelector('[data-seo-key="og_image_id"]');
    const imagePreview = seoManager.querySelector('[data-seo-image-preview]');
    const imageCopy = seoManager.querySelector('[data-seo-image-copy]');
    let contentItems = [];
    let selectedContent = null;

    const showSeoNotice = (message, isError = false) => {
        notice.textContent = message;
        notice.className = `rounded-xl border p-4 text-sm ${isError ? 'border-red-200 bg-red-50 text-red-700' : 'border-cms-green/20 bg-cms-green/5 text-cms-green'}`;
    };

    const closeSeoDialog = () => {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
        selectedContent = null;
    };

    const setSeoField = (key, value) => {
        const input = seoManager.querySelector(`[data-seo-key="${key}"]`);
        if (!input) return;
        if (input.type === 'checkbox') input.checked = Boolean(value);
        else input.value = value ?? '';
    };

    const openSeoDialog = async (content) => {
        selectedContent = content;
        const resource = moduleSelect.value;
        seoManager.querySelector('[data-seo-content-type]').textContent = resource;
        seoManager.querySelector('[data-seo-content-title]').textContent = content.title;
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');

        try {
            const payload = await apiRequest(`/api/v1/admin/${resource}/${content.id}/seo`);
            const seo = payload.data || {};
            setSeoField('meta_title', seo.meta_title);
            setSeoField('meta_description', seo.meta_description);
            setSeoField('focus_keyword', seo.focus_keyword);
            setSeoField('canonical_url', seo.canonical_url);
            setSeoField('robots_index', seo.robots?.index ?? true);
            setSeoField('robots_follow', seo.robots?.follow ?? true);
            setSeoField('og_title', seo.open_graph?.title);
            setSeoField('og_description', seo.open_graph?.description);
            setSeoField('og_image_id', seo.open_graph?.image?.id);
            setSeoField('twitter_card', seo.twitter_card || 'summary');
            setSeoField('structured_data', seo.structured_data ? JSON.stringify(seo.structured_data, null, 2) : '');
            seoManager.querySelector('[data-seo-score]').textContent = `SEO score: ${seo.score ?? 0}%`;
            imageCopy.textContent = seo.open_graph?.image?.original_name || 'No media selected';
            imagePreview.replaceChildren();
            if (seo.open_graph?.image) imagePreview.append(mediaVisual(seo.open_graph.image));
            else imagePreview.textContent = 'None';
        } catch {
            showSeoNotice('SEO metadata could not be loaded.', true);
            closeSeoDialog();
        }
    };

    const renderSeoContent = () => {
        if (!contentGrid) return;
        const query = search.value.trim().toLowerCase();
        const filtered = contentItems.filter((content) => content.title.toLowerCase().includes(query));
        contentGrid.replaceChildren();

        filtered.forEach((content) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rounded-2xl border border-cms-navy/8 bg-white p-5 text-left shadow-sm transition hover:border-cms-gold hover:-translate-y-0.5';
            const heading = document.createElement('span');
            heading.className = 'block truncate font-bold text-cms-navy';
            heading.textContent = content.title;
            const path = document.createElement('span');
            path.className = 'mt-1 block truncate text-xs text-cms-navy/40';
            path.textContent = `/${content.slug}`;
            const score = document.createElement('span');
            score.className = 'mt-4 inline-flex rounded-full bg-cms-green/8 px-2.5 py-1 text-xs font-bold text-cms-green';
            score.textContent = content.seo?.score == null ? 'SEO not configured' : `SEO score ${content.seo.score}%`;
            button.append(heading, path, score);
            button.addEventListener('click', () => openSeoDialog(content));
            contentGrid.append(button);
        });

        if (!filtered.length) {
            const empty = document.createElement('p');
            empty.className = 'col-span-full rounded-2xl border border-dashed p-10 text-center text-sm text-cms-navy/45';
            empty.textContent = 'No content matches this filter.';
            contentGrid.append(empty);
        }
    };

    const loadSeoContent = () => {
        if (!moduleSelect) return;
        contentGrid.innerHTML = '<p class="text-sm text-cms-navy/45">Loading content…</p>';
        apiRequest(`/api/v1/admin/${moduleSelect.value}?per_page=100`)
            .then((payload) => {
                contentItems = payload.data;
                renderSeoContent();
            })
            .catch(() => {
                contentItems = [];
                showSeoNotice('Content could not be loaded. Check the module permission and feature flag.', true);
                renderSeoContent();
            });
    };

    moduleSelect?.addEventListener('change', loadSeoContent);
    search?.addEventListener('input', renderSeoContent);
    seoManager.querySelector('[data-seo-cancel]')?.addEventListener('click', closeSeoDialog);
    dialog?.addEventListener('click', (event) => { if (event.target === dialog) closeSeoDialog(); });
    seoManager.querySelector('[data-seo-image]')?.addEventListener('click', () => window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: { input: imageInput, preview: imagePreview, copy: imageCopy } })));

    if (!canUpdate) {
        save?.classList.add('hidden');
        form?.querySelectorAll('input, textarea, select').forEach((input) => { input.disabled = true; });
    }

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedContent || !canUpdate) return;
        let structuredData = null;
        const structuredDataInput = seoManager.querySelector('[data-seo-key="structured_data"]');
        try {
            structuredData = structuredDataInput.value.trim() ? JSON.parse(structuredDataInput.value) : null;
        } catch {
            showSeoNotice('Structured data must be valid JSON.', true);
            return;
        }

        const value = (key) => seoManager.querySelector(`[data-seo-key="${key}"]`).value.trim() || null;
        save.disabled = true;
        save.textContent = 'Saving…';
        try {
            await apiRequest(`/api/v1/admin/${moduleSelect.value}/${selectedContent.id}/seo`, {
                method: 'PUT',
                body: JSON.stringify({
                    meta_title: value('meta_title'), meta_description: value('meta_description'), focus_keyword: value('focus_keyword'), canonical_url: value('canonical_url'),
                    robots_index: seoManager.querySelector('[data-seo-key="robots_index"]').checked,
                    robots_follow: seoManager.querySelector('[data-seo-key="robots_follow"]').checked,
                    og_title: value('og_title'), og_description: value('og_description'), og_image_id: value('og_image_id'),
                    twitter_card: value('twitter_card'), structured_data: structuredData,
                }),
            });
            closeSeoDialog();
            showSeoNotice('SEO metadata saved successfully.');
            loadSeoContent();
        } catch (error) {
            showSeoNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || 'SEO metadata could not be saved.', true);
        } finally {
            save.disabled = false;
            save.textContent = 'Save SEO';
        }
    });

    if (modules.length) loadSeoContent();
}

const auditLogList = document.querySelector('[data-audit-log-list]');

if (auditLogList) {
    const rows = auditLogList.querySelector('[data-audit-rows]');
    const search = auditLogList.querySelector('[data-audit-search]');
    const summary = auditLogList.querySelector('[data-audit-summary]');
    const previous = auditLogList.querySelector('[data-audit-previous]');
    const next = auditLogList.querySelector('[data-audit-next]');
    let logs = [];
    let currentPage = 1;
    let pagination = null;

    const renderAuditLogs = () => {
        const query = search.value.trim().toLowerCase();
        const filtered = logs.filter((log) => `${log.event} ${log.user?.name ?? ''} ${log.auditable_type ?? ''} ${log.auditable_id ?? ''}`.toLowerCase().includes(query));
        rows.replaceChildren();

        filtered.forEach((log) => {
            const row = document.createElement('tr');
            row.className = 'border-b border-cms-navy/6 last:border-0';
            const eventCell = document.createElement('td');
            eventCell.className = 'px-5 py-4 font-semibold text-cms-navy';
            eventCell.textContent = log.event.replaceAll('.', ' ');
            const actorCell = document.createElement('td');
            actorCell.className = 'px-5 py-4 text-sm text-cms-navy/60';
            actorCell.textContent = log.user?.name ?? 'System';
            const targetCell = document.createElement('td');
            targetCell.className = 'px-5 py-4 text-sm text-cms-navy/60';
            const type = log.auditable_type?.split('\\').pop() ?? 'Application';
            targetCell.textContent = log.auditable_id ? `${type} · ${log.auditable_id.slice(0, 8)}` : type;
            const timeCell = document.createElement('td');
            timeCell.className = 'px-5 py-4 text-sm text-cms-navy/50';
            timeCell.textContent = new Date(log.created_at).toLocaleString();
            row.append(eventCell, actorCell, targetCell, timeCell);
            rows.append(row);
        });

        if (!filtered.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'px-5 py-12 text-center text-sm text-cms-navy/45';
            cell.textContent = 'No activity matches this filter.';
            row.append(cell);
            rows.append(row);
        }

        summary.textContent = pagination ? `Page ${pagination.current_page} of ${pagination.last_page} · ${pagination.total} events` : '';
        previous.disabled = !pagination || pagination.current_page <= 1;
        next.disabled = !pagination || pagination.current_page >= pagination.last_page;
    };

    const loadAuditLogs = () => {
        apiRequest(`/api/v1/admin/audit-logs?page=${currentPage}`)
            .then((payload) => {
                logs = payload.data;
                pagination = payload.meta;
                renderAuditLogs();
            })
            .catch(() => {
                logs = [];
                pagination = null;
                renderAuditLogs();
            });
    };

    search.addEventListener('input', renderAuditLogs);
    previous.addEventListener('click', () => { currentPage -= 1; loadAuditLogs(); });
    next.addEventListener('click', () => { currentPage += 1; loadAuditLogs(); });
    loadAuditLogs();
}

const taxonomyManager = document.querySelector('[data-taxonomy-manager]');

if (taxonomyManager) {
    const canCreate = taxonomyManager.dataset.canCreate === 'true';
    const canUpdate = taxonomyManager.dataset.canUpdate === 'true';
    const canDelete = taxonomyManager.dataset.canDelete === 'true';
    const rows = taxonomyManager.querySelector('[data-taxonomy-rows]');
    const search = taxonomyManager.querySelector('[data-taxonomy-search]');
    const context = taxonomyManager.querySelector('[data-taxonomy-context]');
    const summary = taxonomyManager.querySelector('[data-taxonomy-summary]');
    const previous = taxonomyManager.querySelector('[data-taxonomy-previous]');
    const next = taxonomyManager.querySelector('[data-taxonomy-next]');
    const notice = taxonomyManager.querySelector('[data-taxonomy-notice]');
    const dialog = taxonomyManager.querySelector('[data-taxonomy-dialog]');
    const form = taxonomyManager.querySelector('[data-taxonomy-form]');
    const idInput = taxonomyManager.querySelector('[data-taxonomy-id]');
    const nameInput = taxonomyManager.querySelector('[data-taxonomy-name]');
    const slugInput = taxonomyManager.querySelector('[data-taxonomy-slug]');
    const localeInput = taxonomyManager.querySelector('[data-taxonomy-locale]');
    const localeField = taxonomyManager.querySelector('[data-taxonomy-locale-field]');
    const formTitle = taxonomyManager.querySelector('[data-taxonomy-form-title]');
    const deleteButton = taxonomyManager.querySelector('[data-taxonomy-delete]');
    const saveButton = taxonomyManager.querySelector('[data-taxonomy-save]');
    const newButton = taxonomyManager.querySelector('[data-taxonomy-new]');
    let resource = 'categories';
    let taxonomies = [];
    let pagination = null;
    let currentPage = 1;
    let slugWasEdited = false;

    const singular = () => resource === 'categories' ? 'category' : 'tag';
    const showTaxonomyNotice = (message, isError = false) => {
        notice.textContent = message;
        notice.className = `rounded-xl border p-4 text-sm ${isError ? 'border-red-200 bg-red-50 text-red-700' : 'border-cms-green/20 bg-cms-green/5 text-cms-green'}`;
    };
    const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const closeTaxonomyDialog = () => {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
        form.reset();
        idInput.value = '';
    };
    const openTaxonomyDialog = (taxonomy = null) => {
        const isEditing = Boolean(taxonomy);
        idInput.value = taxonomy?.id || '';
        nameInput.value = taxonomy?.name || '';
        slugInput.value = taxonomy?.slug || '';
        // Categories are written per language; tags are shared by every language.
        localeField.classList.toggle('hidden', resource !== 'categories');
        localeInput.value = taxonomy?.locale || cmsLocales.default;
        localeInput.disabled = isEditing;
        slugWasEdited = isEditing;
        formTitle.textContent = isEditing ? `Edit ${singular()}` : `New ${singular()}`;
        deleteButton.classList.toggle('hidden', !isEditing || !canDelete);
        saveButton.classList.toggle('hidden', (isEditing && !canUpdate) || (!isEditing && !canCreate));
        nameInput.disabled = isEditing && !canUpdate;
        slugInput.disabled = isEditing && !canUpdate;
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        nameInput.focus();
    };

    const renderTaxonomies = () => {
        const query = search.value.trim().toLowerCase();
        const filtered = taxonomies.filter((taxonomy) => `${taxonomy.name} ${taxonomy.slug}`.toLowerCase().includes(query));
        rows.replaceChildren();

        filtered.forEach((taxonomy) => {
            const row = document.createElement('tr');
            row.className = 'border-b border-cms-navy/6 last:border-0 hover:bg-cms-canvas/40';
            const nameCell = document.createElement('td');
            nameCell.className = 'px-5 py-4';
            const name = document.createElement('p');
            name.className = 'font-semibold text-cms-navy';
            name.textContent = taxonomy.name;
            const slug = document.createElement('p');
            slug.className = 'mt-1 flex items-center gap-2 font-mono text-xs text-cms-navy/40';
            if (taxonomy.locale) slug.append(localeBadge(taxonomy.locale));
            slug.append(taxonomy.slug);
            nameCell.append(name, slug);
            const postsCell = document.createElement('td');
            postsCell.className = 'px-5 py-4 text-sm text-cms-navy/60';
            postsCell.textContent = new Intl.NumberFormat().format(taxonomy.posts_count || 0);
            const portfoliosCell = document.createElement('td');
            portfoliosCell.className = 'px-5 py-4 text-sm text-cms-navy/60';
            portfoliosCell.textContent = new Intl.NumberFormat().format(taxonomy.portfolios_count || 0);
            const actionCell = document.createElement('td');
            actionCell.className = 'px-5 py-4 text-right';
            if (canUpdate || canDelete) {
                const edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-bold text-cms-navy hover:border-cms-gold';
                edit.textContent = canUpdate ? 'Edit' : 'Review';
                edit.addEventListener('click', () => openTaxonomyDialog(taxonomy));
                actionCell.append(edit);
            } else {
                const viewOnly = document.createElement('span');
                viewOnly.className = 'text-xs text-cms-navy/35';
                viewOnly.textContent = 'View only';
                actionCell.append(viewOnly);
            }
            row.append(nameCell, postsCell, portfoliosCell, actionCell);
            rows.append(row);
        });

        if (!filtered.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'px-5 py-12 text-center text-sm text-cms-navy/45';
            cell.textContent = `No ${resource} match this filter.`;
            row.append(cell);
            rows.append(row);
        }

        summary.textContent = pagination ? `Page ${pagination.current_page} of ${pagination.last_page} · ${pagination.total} ${resource}` : '';
        previous.disabled = !pagination || pagination.current_page <= 1;
        next.disabled = !pagination || pagination.current_page >= pagination.last_page;
    };

    const loadTaxonomies = () => {
        rows.innerHTML = '<tr><td colspan="4" class="px-5 py-12 text-center text-sm text-cms-navy/45">Loading…</td></tr>';
        apiRequest(`/api/v1/admin/${resource}?page=${currentPage}`)
            .then((payload) => {
                taxonomies = payload.data;
                pagination = payload.meta;
                renderTaxonomies();
            })
            .catch(() => {
                taxonomies = [];
                pagination = null;
                showTaxonomyNotice(`${resource === 'categories' ? 'Categories' : 'Tags'} could not be loaded.`, true);
                renderTaxonomies();
            });
    };

    taxonomyManager.querySelectorAll('[data-taxonomy-tab]').forEach((tab) => tab.addEventListener('click', () => {
        resource = tab.dataset.taxonomyTab;
        currentPage = 1;
        search.value = '';
        search.placeholder = `Filter ${resource} by name or slug…`;
        context.textContent = `Showing ${resource}`;
        if (newButton) newButton.textContent = `+ New ${singular()}`;
        taxonomyManager.querySelectorAll('[data-taxonomy-tab]').forEach((candidate) => {
            const isActive = candidate === tab;
            candidate.classList.toggle('border-cms-green', isActive);
            candidate.classList.toggle('text-cms-green', isActive);
            candidate.classList.toggle('border-transparent', !isActive);
            candidate.classList.toggle('text-cms-navy/45', !isActive);
        });
        loadTaxonomies();
    }));

    newButton?.addEventListener('click', () => openTaxonomyDialog());
    search.addEventListener('input', renderTaxonomies);
    previous.addEventListener('click', () => { currentPage -= 1; loadTaxonomies(); });
    next.addEventListener('click', () => { currentPage += 1; loadTaxonomies(); });
    nameInput.addEventListener('input', () => { if (!slugWasEdited) slugInput.value = slugify(nameInput.value); });
    slugInput.addEventListener('input', () => { slugWasEdited = true; });
    taxonomyManager.querySelector('[data-taxonomy-cancel]').addEventListener('click', closeTaxonomyDialog);
    dialog.addEventListener('click', (event) => { if (event.target === dialog) closeTaxonomyDialog(); });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = idInput.value;
        saveButton.disabled = true;
        saveButton.textContent = 'Saving…';
        try {
            await apiRequest(`/api/v1/admin/${resource}${id ? `/${id}` : ''}`, {
                method: id ? 'PUT' : 'POST',
                body: JSON.stringify({
                    name: nameInput.value.trim(),
                    slug: slugInput.value.trim(),
                    ...(!idInput.value && resource === 'categories' ? { locale: localeInput.value } : {}),
                }),
            });
            closeTaxonomyDialog();
            showTaxonomyNotice(`${singular().replace(/^./, (letter) => letter.toUpperCase())} saved successfully.`);
            loadTaxonomies();
        } catch (error) {
            showTaxonomyNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || `${singular()} could not be saved.`, true);
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Save';
        }
    });

    deleteButton.addEventListener('click', async () => {
        if (!idInput.value || !window.confirm(`Delete this ${singular()}?`)) return;
        try {
            await apiRequest(`/api/v1/admin/${resource}/${idInput.value}`, { method: 'DELETE' });
            closeTaxonomyDialog();
            showTaxonomyNotice(`${singular().replace(/^./, (letter) => letter.toUpperCase())} deleted successfully.`);
            loadTaxonomies();
        } catch (error) {
            showTaxonomyNotice(Object.values(error.payload?.errors ?? {}).flat().join(' ') || `This ${singular()} could not be deleted. It may still be assigned to content.`, true);
        }
    });

    loadTaxonomies();
}

const contactForm = document.querySelector('[data-contact-form]');

if (contactForm) {
    const successBox = contactForm.querySelector('[data-contact-success]');
    const errorBox = contactForm.querySelector('[data-contact-error]');
    const submitButton = contactForm.querySelector('[data-contact-submit]');
    const submitLabel = contactForm.querySelector('[data-contact-submit-label]');
    const requiresRecaptcha = Boolean(contactForm.dataset.recaptchaSiteKey);

    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        successBox.classList.add('hidden');
        errorBox.classList.add('hidden');

        // grecaptcha is injected by Google's widget script; absent until it loads.
        const recaptchaToken = requiresRecaptcha ? window.grecaptcha?.getResponse() ?? '' : null;

        if (requiresRecaptcha && !recaptchaToken) {
            errorBox.textContent = 'Please complete the captcha before sending.';
            errorBox.classList.remove('hidden');
            return;
        }

        const data = new FormData(contactForm);
        const body = {
            name: data.get('name'),
            email: data.get('email'),
            phone: data.get('phone') || null,
            subject: data.get('subject'),
            message: data.get('message'),
            ...(recaptchaToken ? { recaptcha_token: recaptchaToken } : {}),
        };

        submitButton.disabled = true;
        submitLabel.textContent = 'Sending…';

        try {
            await apiRequest('/api/v1/public/contact-messages', { method: 'POST', body: JSON.stringify(body) });
            contactForm.reset();
            window.grecaptcha?.reset();
            successBox.classList.remove('hidden');
        } catch (error) {
            errorBox.textContent = Object.values(error.payload?.errors ?? {}).flat().join(' ')
                || 'Your message could not be sent. Please try again.';
            errorBox.classList.remove('hidden');
        } finally {
            submitButton.disabled = false;
            submitLabel.textContent = 'Send Message';
        }
    });
}

const pricingManager = document.querySelector('[data-pricing-manager]');

if (pricingManager) {
    const localeSelect = pricingManager.querySelector('[data-pricing-locale]');
    const notice = pricingManager.querySelector('[data-pricing-notice]');
    const permissions = {
        create: pricingManager.dataset.canCreate === 'true',
        update: pricingManager.dataset.canUpdate === 'true',
        delete: pricingManager.dataset.canDelete === 'true',
    };
    let plans = [];

    const showNotice = (message, type = 'error') => {
        notice.textContent = message;
        notice.className = type === 'success'
            ? 'rounded-xl border border-cms-green/20 bg-cms-green/8 p-4 text-sm text-cms-green'
            : 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800';
    };

    // A comparison cell is true (included), false (not included) or a short text.
    const comparisonCell = (plan, value) => {
        const wrapper = document.createElement('div');
        wrapper.dataset.planCell = plan.id;
        wrapper.className = 'grid gap-1.5';
        const caption = document.createElement('span');
        caption.className = 'text-xs font-semibold text-cms-navy/60';
        caption.textContent = plan.name;
        const mode = document.createElement('select');
        mode.className = 'h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm';
        [['yes', '✓ Included'], ['no', '– Not included'], ['text', 'Text…']].forEach(([optionValue, label]) => mode.append(new Option(label, optionValue)));
        const text = document.createElement('input');
        text.type = 'text';
        text.maxLength = 60;
        text.placeholder = 'e.g. 2, 200 tamu, Add-on';
        text.className = 'h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm';
        mode.value = value === true ? 'yes' : (typeof value === 'string' && value !== '' ? 'text' : 'no');
        text.value = typeof value === 'string' ? value : '';
        text.classList.toggle('hidden', mode.value !== 'text');
        mode.addEventListener('change', () => text.classList.toggle('hidden', mode.value !== 'text'));
        wrapper.append(caption, mode, text);
        return wrapper;
    };

    const collections = {
        plans: {
            fields: [
                { key: 'name', label: 'Name', required: true },
                { key: 'tagline', label: 'Tagline', placeholder: '200 tamu termasuk · scan QR' },
                { key: 'price', label: 'Starting price (Rp)', type: 'number', required: true },
                { key: 'extra_guest_price', label: 'Price per extra guest (Rp)', type: 'number' },
                { key: 'features', label: 'Features (one per line)', type: 'lines', wide: true },
                { key: 'support_summary', label: 'On-site support summary', placeholder: 'Tim on-site · 2 QR scanner' },
                { key: 'is_highlighted', label: 'Highlight this plan', type: 'checkbox' },
                { key: 'highlight_label', label: 'Highlight badge', placeholder: 'Paling Populer' },
                { key: 'cta_label', label: 'Button label', placeholder: 'Pilih Basic' },
                { key: 'cta_url', label: 'Button link (empty = demo page)', placeholder: '/demo' },
                { key: 'position', label: 'Order', type: 'number' },
            ],
            summary: (plan) => [plan.name, `${plan.price_formatted}${plan.is_highlighted ? ' · highlighted' : ''}`, plan.tagline],
        },
        'comparison-rows': {
            fields: [
                { key: 'group_name', label: 'Group', required: true, placeholder: 'Check-in di venue' },
                { key: 'label', label: 'Feature', required: true },
                { key: 'position', label: 'Order', type: 'number' },
                { key: 'values', type: 'plan-values', wide: true },
            ],
            summary: (row) => [row.label, row.group_name, plans.map((plan) => {
                const value = row.values?.[plan.id];
                return `${plan.name}: ${value === true ? '✓' : (typeof value === 'string' && value ? value : '–')}`;
            }).join(' · ')],
        },
        addons: {
            fields: [
                { key: 'name', label: 'Name', required: true, wide: true },
                { key: 'price', label: 'Price (Rp)', type: 'number', required: true },
                { key: 'unit', label: 'Unit', placeholder: '/ tamu' },
                { key: 'position', label: 'Order', type: 'number' },
            ],
            summary: (addon) => [addon.name, `${addon.price_formatted} ${addon.unit ?? ''}`.trim()],
        },
    };

    const renderForm = (section, name, entry = null) => {
        const form = section.querySelector('[data-pricing-form]');
        const config = collections[name];
        const editable = entry ? permissions.update : permissions.create;
        form.replaceChildren();
        form.dataset.entryId = entry?.id ?? '';
        config.fields.forEach((definition) => {
            if (definition.type === 'plan-values') {
                const cells = document.createElement('div');
                cells.dataset.planValues = '';
                cells.className = 'grid gap-3 sm:col-span-2 sm:grid-cols-3';
                if (!plans.length) cells.textContent = 'Add plans first; each plan gets a column here.';
                plans.forEach((plan) => cells.append(comparisonCell(plan, entry?.values?.[plan.id])));
                form.append(cells);
                return;
            }
            const label = document.createElement('label');
            label.className = `grid gap-1.5${definition.wide ? ' sm:col-span-2' : ''}${definition.type === 'checkbox' ? ' content-end' : ''}`;
            const caption = document.createElement('span');
            caption.className = 'text-xs font-semibold text-cms-navy/60';
            caption.textContent = `${definition.label}${definition.required ? ' *' : ''}`;
            const value = entry?.[definition.key];
            let input;
            if (definition.type === 'lines') {
                input = document.createElement('textarea');
                input.className = 'min-h-28 rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-sm';
                input.value = Array.isArray(value) ? value.join('\n') : '';
            } else {
                input = document.createElement('input');
                input.type = definition.type === 'checkbox' ? 'checkbox' : (definition.type === 'number' ? 'number' : 'text');
                if (definition.type === 'number') input.min = '0';
                if (definition.type === 'checkbox') input.checked = Boolean(value);
                else input.value = value ?? (definition.key === 'position' ? '0' : '');
                input.className = definition.type === 'checkbox' ? 'size-4 accent-cms-green' : 'h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm';
            }
            input.dataset.key = definition.key;
            input.dataset.type = definition.type ?? 'text';
            if (definition.placeholder) input.placeholder = definition.placeholder;
            input.disabled = !editable;
            if (definition.type === 'checkbox') {
                label.className += ' flex items-center gap-2';
                label.append(input, caption);
            } else {
                label.append(caption, input);
            }
            form.append(label);
        });

        const actions = document.createElement('div');
        actions.className = 'flex flex-wrap gap-2 sm:col-span-2';
        if (editable) {
            const save = document.createElement('button');
            save.type = 'submit';
            save.className = 'h-10 rounded-xl bg-cms-green px-4 text-sm font-bold text-white';
            save.textContent = entry ? 'Save changes' : `Create ${section.dataset.singular}`;
            actions.append(save);
        }
        if (entry && permissions.delete) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'h-10 rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700';
            remove.textContent = 'Delete';
            remove.addEventListener('click', async () => {
                if (!window.confirm(`Delete this ${section.dataset.singular}? It can be restored through the API.`)) return;
                try {
                    await apiRequest(`/api/v1/admin/pricing/${name}/${entry.id}`, { method: 'DELETE' });
                    showNotice(`The ${section.dataset.singular} was deleted.`, 'success');
                    await loadAll();
                } catch { showNotice(`The ${section.dataset.singular} could not be deleted.`); }
            });
            actions.append(remove);
        }
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'h-10 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm font-bold text-cms-navy';
        cancel.textContent = 'Close';
        cancel.addEventListener('click', () => form.classList.add('hidden'));
        actions.append(cancel);
        form.append(actions);
        form.classList.remove('hidden');
        form.querySelector('input:not([type="checkbox"]), textarea')?.focus();
    };

    const readForm = (form, name) => {
        const payload = {};
        form.querySelectorAll('[data-key]').forEach((input) => {
            const key = input.dataset.key;
            if (input.dataset.type === 'checkbox') payload[key] = input.checked;
            else if (input.dataset.type === 'number') payload[key] = input.value === '' ? null : Number(input.value);
            else if (input.dataset.type === 'lines') payload[key] = input.value.split('\n').map((line) => line.trim()).filter(Boolean);
            else payload[key] = input.value.trim() || null;
        });
        if (name === 'comparison-rows') {
            payload.values = Object.fromEntries([...form.querySelectorAll('[data-plan-cell]')].map((cell) => {
                const mode = cell.querySelector('select').value;
                const text = cell.querySelector('input').value.trim();
                return [cell.dataset.planCell, mode === 'yes' ? true : (mode === 'text' && text ? text : false)];
            }));
        }
        if (payload.position === null) payload.position = 0;
        return payload;
    };

    const renderList = (section, name, entries) => {
        const list = section.querySelector('[data-pricing-list]');
        list.replaceChildren();
        entries.forEach((entry) => {
            const [title, meta, detail] = collections[name].summary(entry);
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'grid gap-0.5 rounded-xl border border-cms-navy/8 bg-white px-4 py-3 text-left hover:border-cms-gold';
            [[title, 'text-sm font-bold text-cms-navy'], [meta, 'text-xs text-cms-navy/55'], [detail, 'truncate text-xs text-cms-navy/40']].forEach(([text, className]) => {
                if (!text) return;
                const line = document.createElement('span');
                line.className = className;
                line.textContent = text;
                row.append(line);
            });
            row.addEventListener('click', () => renderForm(section, name, entry));
            list.append(row);
        });
        if (!entries.length) {
            const empty = document.createElement('p');
            empty.className = 'p-2 text-sm text-cms-navy/45';
            empty.textContent = `No ${section.dataset.singular}s in ${localeName(localeSelect.value)} yet.`;
            list.append(empty);
        }
    };

    async function loadAll() {
        const locale = encodeURIComponent(localeSelect.value);
        try {
            const [planPayload, rowPayload, addonPayload] = await Promise.all(
                ['plans', 'comparison-rows', 'addons'].map((name) => apiRequest(`/api/v1/admin/pricing/${name}?locale=${locale}`)),
            );
            plans = planPayload.data;
            const payloads = { plans: planPayload, 'comparison-rows': rowPayload, addons: addonPayload };
            pricingManager.querySelectorAll('[data-pricing-collection]').forEach((section) => {
                const name = section.dataset.pricingCollection;
                section.querySelector('[data-pricing-form]').classList.add('hidden');
                renderList(section, name, payloads[name].data);
            });
        } catch {
            showNotice('Pricing could not be loaded. Check your permission and the Pricing feature flag.');
        }
    }

    pricingManager.querySelectorAll('[data-pricing-collection]').forEach((section) => {
        const name = section.dataset.pricingCollection;
        const newButton = section.querySelector('[data-pricing-new]');
        newButton.classList.toggle('hidden', !permissions.create);
        newButton.addEventListener('click', () => renderForm(section, name));
        section.querySelector('[data-pricing-form]').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const id = form.dataset.entryId;
            const payload = readForm(form, name);
            if (!id) payload.locale = localeSelect.value;
            try {
                await apiRequest(id ? `/api/v1/admin/pricing/${name}/${id}` : `/api/v1/admin/pricing/${name}`, {
                    method: id ? 'PUT' : 'POST',
                    body: JSON.stringify(payload),
                });
                showNotice(`The ${section.dataset.singular} was saved.`, 'success');
                await loadAll();
            } catch (error) {
                const messages = Object.values(error.payload?.errors ?? {}).flat();
                showNotice(messages.length ? messages.join(' ') : `The ${section.dataset.singular} could not be saved.`);
            }
        });
    });

    localeSelect.value = cmsLocales.default;
    localeSelect.addEventListener('change', () => { notice.classList.add('hidden'); loadAll(); });
    loadAll();
}

const passwordForm = document.querySelector('[data-password-form]');

if (passwordForm) {
    const notice = passwordForm.querySelector('[data-password-notice]');
    const saveButton = passwordForm.querySelector('[data-password-save]');
    const showPasswordNotice = (message, isError = false) => {
        notice.textContent = message;
        notice.className = `rounded-xl border p-4 text-sm ${isError ? 'border-red-200 bg-red-50 text-red-700' : 'border-cms-green/20 bg-cms-green/5 text-cms-green'}`;
    };

    passwordForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(passwordForm));
        if (payload.password !== payload.password_confirmation) {
            showPasswordNotice('The new password and its confirmation do not match.', true);
            return;
        }

        saveButton.disabled = true;
        try {
            await apiRequest('/api/v1/admin/profile/password', { method: 'PUT', body: JSON.stringify(payload) });
            passwordForm.reset();
            showPasswordNotice('Your password was updated.');
        } catch (error) {
            const messages = Object.values(error.payload?.errors ?? {}).flat();
            showPasswordNotice(messages.length ? messages.join(' ') : 'The password could not be updated.', true);
        } finally {
            saveButton.disabled = false;
        }
    });
}
