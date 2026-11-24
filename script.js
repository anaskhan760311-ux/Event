function handleImageFallback(imgElement) {
            if (!imgElement || imgElement.dataset.fallbackApplied === 'true') return;
            imgElement.dataset.fallbackApplied = 'true';
            const label = (imgElement.alt || 'KhanEvent').replace(/[<>&"]/g, '');
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="600" height="320" viewBox="0 0 600 320">
                    <defs>
                        <linearGradient id="fb" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#4f46e5"/>
                            <stop offset="100%" stop-color="#1e1b4b"/>
                        </linearGradient>
                    </defs>
                    <rect width="600" height="320" fill="url(#fb)"/>
                    <text x="300" y="155" text-anchor="middle" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="24" font-weight="700">${label}</text>
                    <text x="300" y="188" text-anchor="middle" fill="#c7d2fe" font-family="Segoe UI, Arial, sans-serif" font-size="14">KhanEvent</text>
                </svg>
            `.trim();
            imgElement.src = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
        }

        // Events are loaded dynamically from the MySQL database via api/get_events.php (PDO)
        let events = [];

        // DOM Elements
        const exploreView = document.getElementById('exploreView');
        const dashboardView = document.getElementById('dashboardView');
        const navExplore = document.getElementById('navExplore');
        const navEvents = document.getElementById('navEvents');
        const navBookings = document.getElementById('navBookings');
        const navDashboard = document.getElementById('navDashboard');
        const goToDashboardBtn = document.getElementById('goToDashboardBtn');
        const backToExploreBtn = document.getElementById('backToExploreBtn');

        const eventGrid = document.getElementById('eventGrid');
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const sortPrice = document.getElementById('sortPrice');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        const eventCount = document.getElementById('eventCount');

        // Favorites & Recently Viewed DOM Elements
        const favoritesGrid = document.getElementById('favoritesGrid');
        const favoritesCount = document.getElementById('favoritesCount');
        const clearFavoritesBtn = document.getElementById('clearFavoritesBtn');

        const recentGrid = document.getElementById('recentGrid');
        const recentCount = document.getElementById('recentCount');
        const clearRecentBtn = document.getElementById('clearRecentBtn');

        // Task 1: Bookings Management DOM Elements
        const bookingsTableBody = document.getElementById('bookingsTableBody');
        const manageBookingsCount = document.getElementById('manageBookingsCount');
        const clearAllBookingsBtn = document.getElementById('clearAllBookingsBtn');
        const statTotalBookings = document.getElementById('statTotalBookings');
        const statConfirmedBookings = document.getElementById('statConfirmedBookings');
        const statPendingBookings = document.getElementById('statPendingBookings');
        const statCancelledBookings = document.getElementById('statCancelledBookings');
        const statTotalBookingAmount = document.getElementById('statTotalBookingAmount');

        // Task 3: Export CSV, Booking Search & Status Filter DOM Elements
        const exportCsvBtn = document.getElementById('exportCsvBtn');
        const bookingSearchInput = document.getElementById('bookingSearchInput');
        const bookingStatusFilter = document.getElementById('bookingStatusFilter');

        // Task 2: Dashboard DOM Elements
        const dashboardWelcomeSubtitle = document.getElementById('dashboardWelcomeSubtitle');
        const dashTotalBookings = document.getElementById('dashTotalBookings');
        const dashUpcomingEvents = document.getElementById('dashUpcomingEvents');
        const dashCancelledBookings = document.getElementById('dashCancelledBookings');
        const dashTotalSpent = document.getElementById('dashTotalSpent');
        const dashUpcomingBadge = document.getElementById('dashUpcomingBadge');
        const dashUpcomingGrid = document.getElementById('dashUpcomingGrid');
        const dashManageBookingsLink = document.getElementById('dashManageBookingsLink');
        const dashRecentBadge = document.getElementById('dashRecentBadge');
        const dashRecentGrid = document.getElementById('dashRecentGrid');
        const dashClearRecentBtn = document.getElementById('dashClearRecentBtn');

        // Modals DOM
        const eventModal = document.getElementById('eventModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        const modalCategory = document.getElementById('modalCategory');
        const modalDate = document.getElementById('modalDate');
        const modalLocation = document.getElementById('modalLocation');
        const modalPrice = document.getElementById('modalPrice');
        const modalDescription = document.getElementById('modalDescription');
        const modalBookNowBtn = document.getElementById('modalBookNowBtn');

        // Booking Modal DOM
        const bookingModal = document.getElementById('bookingModal');
        const closeBookingBtn = document.getElementById('closeBookingBtn');
        const bookingForm = document.getElementById('bookingForm');
        const bookingEventName = document.getElementById('bookingEventName');
        const bookingTicketPrice = document.getElementById('bookingTicketPrice');
        const bookingName = document.getElementById('bookingName');
        const bookingEmail = document.getElementById('bookingEmail');
        const bookingPhone = document.getElementById('bookingPhone');
        const bookingTickets = document.getElementById('bookingTickets');
        const bookingTotalAmount = document.getElementById('bookingTotalAmount');
        const bookingSuccessMessage = document.getElementById('bookingSuccessMessage');

        const bookingNameError = document.getElementById('bookingNameError');
        const bookingEmailError = document.getElementById('bookingEmailError');
        const bookingPhoneError = document.getElementById('bookingPhoneError');
        const bookingTicketsError = document.getElementById('bookingTicketsError');

        // Task 1: Edit Booking Modal DOM
        const editBookingModal = document.getElementById('editBookingModal');
        const closeEditBookingBtn = document.getElementById('closeEditBookingBtn');
        const editBookingForm = document.getElementById('editBookingForm');
        const editBookingIdLabel = document.getElementById('editBookingIdLabel');
        const editBookingEventName = document.getElementById('editBookingEventName');
        const editBookingTicketPrice = document.getElementById('editBookingTicketPrice');
        const editBookingName = document.getElementById('editBookingName');
        const editBookingEmail = document.getElementById('editBookingEmail');
        const editBookingPhone = document.getElementById('editBookingPhone');
        const editBookingTickets = document.getElementById('editBookingTickets');
        const editBookingStatus = document.getElementById('editBookingStatus');
        const editBookingTotalAmount = document.getElementById('editBookingTotalAmount');

        const editBookingNameError = document.getElementById('editBookingNameError');
        const editBookingEmailError = document.getElementById('editBookingEmailError');
        const editBookingPhoneError = document.getElementById('editBookingPhoneError');
        const editBookingTicketsError = document.getElementById('editBookingTicketsError');

        // Confirmation Modal DOM
        const confirmActionModal = document.getElementById('confirmActionModal');
        const confirmActionTitle = document.getElementById('confirmActionTitle');
        const confirmActionMessage = document.getElementById('confirmActionMessage');
        const confirmActionNoBtn = document.getElementById('confirmActionNoBtn');
        const confirmActionYesBtn = document.getElementById('confirmActionYesBtn');

        // Auth Modals DOM
        const loginModal = document.getElementById('loginModal');
        const signupModal = document.getElementById('signupModal');
        const openLoginBtn = document.getElementById('openLoginBtn');
        const openSignupBtn = document.getElementById('openSignupBtn');
        const closeLoginBtn = document.getElementById('closeLoginBtn');
        const closeSignupBtn = document.getElementById('closeSignupBtn');
        const toastBanner = document.getElementById('toastBanner');
        const authButtonsContainer = document.querySelector('.auth-buttons');

        // LocalStorage Keys
        const FAVORITES_STORAGE_KEY = 'khanevent_favorites';
        const RECENT_STORAGE_KEY = 'khanevent_recently_viewed';
        const CURRENT_USER_KEY = 'khanevent_current_user';
        const REGISTERED_USERS_KEY = 'khanevent_registered_users';
        const BOOKINGS_STORAGE_KEY = 'khanevent_confirmed_bookings';

        function loadFromStorage(key) {
            try {
                const raw = localStorage.getItem(key);
                const parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed : [];
            } catch (err) {
                return [];
            }
        }

        function saveToStorage(key, data) {
            try {
                localStorage.setItem(key, JSON.stringify(data));
            } catch (err) {
                console.error('LocalStorage write failed:', err);
            }
        }

        // Booking records come from MySQL; do not mix in browser-only sample rows.
        function initializeBookingsWithSamples() {
            return [];
        }

        // Initialize recently viewed with 2 sample event IDs if empty so Dashboard "Recently Viewed Events" is populated on first load
        function initializeRecentlyViewedWithSamples() {
            const raw = localStorage.getItem(RECENT_STORAGE_KEY);
            if (raw === null) {
                // Use valid MySQL event IDs (events loaded from database start at id 15)
                const defaults = [15, 16];
                saveToStorage(RECENT_STORAGE_KEY, defaults);
                return defaults;
            }
            return loadFromStorage(RECENT_STORAGE_KEY);
        }

        let favoriteIds = loadFromStorage(FAVORITES_STORAGE_KEY);
        let recentlyViewedIds = initializeRecentlyViewedWithSamples();
        let allBookings = initializeBookingsWithSamples();
        let currentBookingEvent = null;
        let editingBookingId = null;

        // =========================================================
        // VIEW SWITCHER (Explore vs. My Dashboard)
        // =========================================================
        function setActiveView(viewName, scrollTargetId = null) {
            navExplore.classList.remove('active-nav');
            navEvents.classList.remove('active-nav');
            navBookings.classList.remove('active-nav');
            navDashboard.classList.remove('active-nav');

            if (viewName === 'dashboard') {
                exploreView.style.display = 'none';
                dashboardView.style.display = 'block';
                navDashboard.classList.add('active-nav');
                renderDashboard();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                dashboardView.style.display = 'none';
                exploreView.style.display = 'block';

                if (scrollTargetId === 'eventGrid') {
                    navEvents.classList.add('active-nav');
                } else if (scrollTargetId === 'bookingsSection') {
                    navBookings.classList.add('active-nav');
                } else {
                    navExplore.classList.add('active-nav');
                }

                if (scrollTargetId) {
                    const targetEl = document.getElementById(scrollTargetId);
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth' });
                    }
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }

        navExplore.addEventListener('click', (e) => {
            e.preventDefault();
            setActiveView('explore');
        });

        navEvents.addEventListener('click', (e) => {
            e.preventDefault();
            setActiveView('explore', 'eventGrid');
        });

        navBookings.addEventListener('click', (e) => {
            e.preventDefault();
            if (!requireLogin()) return;
            setActiveView('explore', 'bookingsSection');
        });

        navDashboard.addEventListener('click', (e) => {
            e.preventDefault();
            if (!requireLogin()) return;
            setActiveView('dashboard');
        });

        goToDashboardBtn.addEventListener('click', () => {
            if (!requireLogin()) return;
            setActiveView('dashboard');
        });
        backToExploreBtn.addEventListener('click', () => setActiveView('explore'));
        dashManageBookingsLink.addEventListener('click', () => {
            if (!requireLogin()) return;
            setActiveView('explore', 'bookingsSection');
        });

        // =========================================================
        // NON-BLOCKING CONFIRMATION DIALOG HELPER
        // =========================================================
        let pendingConfirmCallback = null;

        function openConfirmDialog(title, message, yesLabel, onConfirm) {
            confirmActionTitle.textContent = title;
            confirmActionMessage.textContent = message;
            confirmActionYesBtn.textContent = yesLabel || 'Confirm';
            pendingConfirmCallback = onConfirm;
            confirmActionModal.style.display = 'flex';
        }

        function closeConfirmDialog() {
            confirmActionModal.style.display = 'none';
            pendingConfirmCallback = null;
        }

        confirmActionNoBtn.addEventListener('click', closeConfirmDialog);
        confirmActionYesBtn.addEventListener('click', () => {
            if (typeof pendingConfirmCallback === 'function') {
                pendingConfirmCallback();
            }
            closeConfirmDialog();
        });

        // =========================================================
        // AUTH HELPERS & UI
        // =========================================================
        let currentUser = null;

        function getCurrentUser() {
            return currentUser;
        }

        function setCurrentUser(user) {
            currentUser = user;
        }

        function formatNameFromEmail(email) {
            const localPart = (email || '').split('@')[0] || 'User';
            return localPart
                .replace(/[._-]+/g, ' ')
                .replace(/\d+/g, '')
                .trim()
                .replace(/\b\w/g, char => char.toUpperCase()) || 'User';
        }

        function updateAuthUI() {
            const user = getCurrentUser();
            document.querySelectorAll('.admin-only').forEach(link => {
                link.hidden = !user || user.role !== 'admin';
                link.style.display = link.hidden ? 'none' : 'inline-block';
            });
            if (!authButtonsContainer) return;

            if (user) {
                authButtonsContainer.innerHTML = `
                    <span style="color: #ffffff; font-weight: 600; font-size: 0.9rem;">Hi, ${user.name}${user.role === 'admin' ? ' · Admin' : ''}</span>
                    <button id="logoutBtn" type="button" class="btn-solid">Logout</button>
                `;
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', handleLogout);
                }
                if (dashboardWelcomeSubtitle) {
                    dashboardWelcomeSubtitle.textContent = `Welcome back, ${user.name}! Here is your booking activity and event history.`;
                }
            } else {
                authButtonsContainer.innerHTML = `
                    <button id="openLoginBtn" type="button" class="btn-outline">Login</button>
                    <button id="openSignupBtn" type="button" class="btn-solid">Sign Up</button>
                `;
                document.getElementById('openLoginBtn').addEventListener('click', () => {
                    clearAuthErrors();
                    loginModal.style.display = 'flex';
                });
                document.getElementById('openSignupBtn').addEventListener('click', () => {
                    clearAuthErrors();
                    signupModal.style.display = 'flex';
                });
                if (dashboardWelcomeSubtitle) {
                    dashboardWelcomeSubtitle.textContent = 'Overview of your bookings, upcoming events, spending, and recently viewed activity.';
                }
            }
        }

        async function handleLogout() {
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Logout failed.');
                setCurrentUser(null);
                updateAuthUI();
                showToast('Logged out successfully');
            } catch (error) {
                showToast(error.message || 'Could not log out. Please try again.');
            }
        }

        // Guard: require login before allowing booking / CRUD actions.
        // Returns true if logged in; otherwise shows a prompt and opens the login modal.
        function requireLogin() {
            const user = getCurrentUser();
            if (user) return true;
            showToast('Please log in to book or manage your bookings.');
            clearAuthErrors();
            loginModal.style.display = 'flex';
            return false;
        }

        // Attach inline error spans for Login & Sign Up
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        const loginEmailInput = document.getElementById('loginEmail');
        const loginPasswordInput = document.getElementById('loginPassword');
        const signupNameInput = document.getElementById('signupName');
        const signupEmailInput = document.getElementById('signupEmail');
        const signupPasswordInput = document.getElementById('signupPassword');

        if (loginForm) loginForm.setAttribute('novalidate', 'true');
        if (signupForm) signupForm.setAttribute('novalidate', 'true');

        function attachAuthErrorSpan(inputEl, spanId) {
            if (!inputEl) return null;
            let span = document.getElementById(spanId);
            if (!span) {
                span = document.createElement('span');
                span.id = spanId;
                span.className = 'error-text';
                inputEl.insertAdjacentElement('afterend', span);
            }
            return span;
        }

        const loginEmailError = attachAuthErrorSpan(loginEmailInput, 'loginEmailError');
        const loginPasswordError = attachAuthErrorSpan(loginPasswordInput, 'loginPasswordError');
        const signupNameError = attachAuthErrorSpan(signupNameInput, 'signupNameError');
        const signupEmailError = attachAuthErrorSpan(signupEmailInput, 'signupEmailError');
        const signupPasswordError = attachAuthErrorSpan(signupPasswordInput, 'signupPasswordError');

        // =========================================================
        // EVENT CARD BUILDER
        // =========================================================
        function createEventCard(event, options = {}) {
            const { showCancelFavorite = false, showCancelRecent = false } = options;
            const isFavorited = favoriteIds.includes(event.id);
            const card = document.createElement('div');
            card.className = 'event-card';
            const imageMarkup = event.image
                ? `<img src="${event.image}" alt="${event.name}" class="event-img" onerror="handleImageFallback(this)">`
                : '';

            let cancelBtnHtml = '';
            if (showCancelFavorite) {
                cancelBtnHtml = `<button type="button" class="btn-cancel-item" onclick="cancelSingleFavorite(${event.id})">Remove</button>`;
            } else if (showCancelRecent) {
                cancelBtnHtml = `<button type="button" class="btn-cancel-item" onclick="cancelSingleRecent(${event.id})">Remove</button>`;
            }

            card.innerHTML = `
                <div class="event-img-wrapper" style="height:${event.image ? '160px' : '36px'}">
                    ${imageMarkup}
                    <button 
                        class="fav-btn ${isFavorited ? 'active' : ''}" 
                        onclick="toggleFavorite(${event.id})" 
                        title="${isFavorited ? 'Remove from Favorites' : 'Add to Favorites'}"
                        aria-label="${isFavorited ? 'Remove from Favorites' : 'Add to Favorites'}"
                    >
                        ${isFavorited ? '♥' : '♡'}
                    </button>
                </div>
                <div class="event-info">
                    <span class="event-category">${event.category}</span>
                    <h3 class="event-title">${event.name}</h3>
                    <p class="event-details">📅 ${event.date}</p>
                    <p class="event-details">📍 ${event.location}</p>
                    <div class="event-footer">
                        <span class="event-price">₹${event.price.toLocaleString('en-IN')}</span>
                        <div class="card-actions">
                            <button class="btn-primary" onclick="openModal(${event.id})">View Details</button>
                            <button class="btn-book" onclick="openBookingModal(${event.id})">Book Now</button>
                            ${cancelBtnHtml}
                        </div>
                    </div>
                </div>
            `;
            return card;
        }

        function cancelSingleFavorite(eventId) {
            favoriteIds = favoriteIds.filter(id => id !== eventId);
            saveToStorage(FAVORITES_STORAGE_KEY, favoriteIds);
            refreshAllViews();
            showToast('Removed event from My Favorites');
        }

        function cancelSingleRecent(eventId) {
            recentlyViewedIds = recentlyViewedIds.filter(id => id !== eventId);
            saveToStorage(RECENT_STORAGE_KEY, recentlyViewedIds);
            renderRecentlyViewed();
            renderDashboard();
            showToast('Removed event from Recently Viewed');
        }

        // =========================================================
        // BOOKING CREATION MODAL & VALIDATION
        // =========================================================
        function openBookingModal(eventId) {
            if (!requireLogin()) return;

            const event = events.find(e => e.id === eventId);
            if (!event) return;

            // Close any open event details modal and open the PHP booking form
            // with the selected event pre-filled via the query string so the
            // booking data can be persisted to Manage Bookings & Dashboard.
            eventModal.style.display = 'none';
            const params = new URLSearchParams({
                event_name: event.name,
                event_date: event.date,
                event_location: event.location,
                unit_price: event.price,
                event_id: event.id
            });
            window.location.href = `php_backend/process_booking.php?${params.toString()}`;
        }

        function closeBookingModal() {
            bookingModal.style.display = 'none';
        }

        function updateBookingTotal() {
            if (!currentBookingEvent) return;
            const qty = parseInt(bookingTickets.value, 10);
            const validQty = !isNaN(qty) && qty > 0 ? qty : 0;
            const total = currentBookingEvent.price * validQty;
            bookingTotalAmount.textContent = total.toLocaleString('en-IN');
        }

        function setFieldError(inputEl, errorEl, message) {
            inputEl.classList.add('input-error');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        function clearFieldError(inputEl, errorEl) {
            inputEl.classList.remove('input-error');
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        }

        function clearBookingErrors() {
            clearFieldError(bookingName, bookingNameError);
            clearFieldError(bookingEmail, bookingEmailError);
            clearFieldError(bookingPhone, bookingPhoneError);
            clearFieldError(bookingTickets, bookingTicketsError);
        }

        const nameOnlyTextRegex = /^[A-Za-z\s]+$/;

        function validateBookingInputs(nameInput, emailInput, phoneInput, ticketsInput, errEls) {
            let isValid = true;
            clearFieldError(nameInput, errEls.name);
            clearFieldError(emailInput, errEls.email);
            clearFieldError(phoneInput, errEls.phone);
            clearFieldError(ticketsInput, errEls.tickets);

            const nameVal = nameInput.value.trim();
            const emailVal = emailInput.value.trim();
            const phoneVal = phoneInput.value.trim();
            const ticketsRaw = ticketsInput.value.trim();
            const ticketsVal = Number(ticketsRaw);

            if (nameVal === '') {
                setFieldError(nameInput, errEls.name, 'Full name is required.');
                isValid = false;
            } else if (!nameOnlyTextRegex.test(nameVal)) {
                setFieldError(nameInput, errEls.name, 'Name should contain only text (no numbers or special characters).');
                isValid = false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailVal === '') {
                setFieldError(emailInput, errEls.email, 'Email address is required.');
                isValid = false;
            } else if (!emailRegex.test(emailVal)) {
                setFieldError(emailInput, errEls.email, 'Please enter a valid email address.');
                isValid = false;
            }

            const normalizedPhone = phoneVal.replace(/[\s\-()]/g, '');
            const phoneRegex = /^(\+91|0)?[6-9]\d{9}$|^\d{10}$/;
            if (phoneVal === '') {
                setFieldError(phoneInput, errEls.phone, 'Phone number is required.');
                isValid = false;
            } else if (!phoneRegex.test(normalizedPhone)) {
                setFieldError(phoneInput, errEls.phone, 'Please enter a valid 10-digit phone number.');
                isValid = false;
            }

            if (ticketsRaw === '' || isNaN(ticketsVal) || !Number.isInteger(ticketsVal) || ticketsVal < 1) {
                setFieldError(ticketsInput, errEls.tickets, 'Ticket quantity must be at least 1.');
                isValid = false;
            }

            return isValid;
        }

        bookingForm.addEventListener('submit', (e) => {
            // NOTE: The form submits to php_backend/process_booking.php (POST).
            // PHP performs server-side validation and displays the confirmation.
            bookingSuccessMessage.style.display = 'none';

            const valid = validateBookingInputs(bookingName, bookingEmail, bookingPhone, bookingTickets, {
                name: bookingNameError,
                email: bookingEmailError,
                phone: bookingPhoneError,
                tickets: bookingTicketsError
            });

            if (!valid || !currentBookingEvent) return;

            const nameVal = bookingName.value.trim();
            const emailVal = bookingEmail.value.trim();
            const phoneVal = bookingPhone.value.trim();
            const qty = parseInt(bookingTickets.value, 10);
            const totalAmount = currentBookingEvent.price * qty;

            const newBooking = {
                bookingId: 'BK-' + Date.now().toString().slice(-6),
                eventId: currentBookingEvent.id,
                eventName: currentBookingEvent.name,
                eventDate: currentBookingEvent.date,
                eventLocation: currentBookingEvent.location,
                unitPrice: currentBookingEvent.price,
                attendeeName: nameVal,
                attendeeEmail: emailVal,
                attendeePhone: phoneVal,
                tickets: qty,
                totalAmount: totalAmount,
                status: 'Confirmed',
                bookedAt: new Date().toLocaleString()
            };

            allBookings.unshift(newBooking);
            saveToStorage(BOOKINGS_STORAGE_KEY, allBookings);
            renderBookingsTable();
            renderDashboard();

            const confirmationText = `Booking Confirmed! Thank you, ${nameVal}. Your ${qty} ticket(s) for "${currentBookingEvent.name}" (Total: ₹${totalAmount.toLocaleString('en-IN')}) have been reserved.`;
            bookingSuccessMessage.textContent = confirmationText;
            bookingSuccessMessage.style.display = 'block';

            showToast(`Booked ${qty} ticket(s) for ${currentBookingEvent.name}!`);
            setTimeout(() => {
                closeBookingModal();
            }, 1100);
        });

        bookingTickets.addEventListener('input', () => {
            updateBookingTotal();
            if (Number(bookingTickets.value) >= 1) {
                clearFieldError(bookingTickets, bookingTicketsError);
            }
        });

        bookingName.addEventListener('input', () => {
            const val = bookingName.value.trim();
            if (val === '') {
                clearFieldError(bookingName, bookingNameError);
            } else if (!nameOnlyTextRegex.test(val)) {
                setFieldError(bookingName, bookingNameError, 'Name should contain only text (no numbers or special characters).');
            } else {
                clearFieldError(bookingName, bookingNameError);
            }
        });

        bookingEmail.addEventListener('input', () => clearFieldError(bookingEmail, bookingEmailError));
        bookingPhone.addEventListener('input', () => clearFieldError(bookingPhone, bookingPhoneError));

        // =========================================================
        // TASK 3: FILTER HELPER & EXPORT CSV FUNCTIONALITY
        // =========================================================
        function getFilteredBookings() {
            const searchTerm = bookingSearchInput ? bookingSearchInput.value.toLowerCase().trim() : '';
            const statusFilterVal = bookingStatusFilter ? bookingStatusFilter.value : 'All';

            return allBookings.filter((booking) => {
                const matchesSearch = searchTerm === '' ||
                    (booking.eventName || '').toLowerCase().includes(searchTerm) ||
                    (booking.attendeeName || '').toLowerCase().includes(searchTerm) ||
                    (booking.attendeeEmail || '').toLowerCase().includes(searchTerm);
                const matchesStatus = statusFilterVal === 'All' || booking.status === statusFilterVal;
                return matchesSearch && matchesStatus;
            });
        }

        function escapeCsvCell(value) {
            const str = value === null || value === undefined ? '' : String(value);
            if (/[",\n\r]/.test(str)) {
                return `"${str.replace(/"/g, '""')}"`;
            }
            return str;
        }

        function exportBookingsToCSV() {
            const recordsToExport = getFilteredBookings();

            if (recordsToExport.length === 0) {
                showToast('No booking records available to export.');
                return;
            }

            const headers = [
                'Booking ID',
                'Event Name',
                'Event Date',
                'Event Location',
                'Attendee Name',
                'Attendee Email',
                'Attendee Phone',
                'Unit Price (INR)',
                'Tickets',
                'Total Amount (INR)',
                'Status',
                'Booked At'
            ];

            const rows = recordsToExport.map(b => [
                b.bookingId,
                b.eventName,
                b.eventDate,
                b.eventLocation,
                b.attendeeName,
                b.attendeeEmail,
                b.attendeePhone,
                b.unitPrice,
                b.tickets,
                b.totalAmount,
                b.status,
                b.bookedAt || ''
            ]);

            const csvContent = [
                headers.map(escapeCsvCell).join(','),
                ...rows.map(row => row.map(escapeCsvCell).join(','))
            ].join('\r\n');

            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.setAttribute('download', 'khanevent_bookings.csv');
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            URL.revokeObjectURL(url);

            showToast(`Exported ${recordsToExport.length} booking record(s) to CSV!`);
        }

        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', exportBookingsToCSV);
        }
        if (bookingSearchInput) {
            bookingSearchInput.addEventListener('input', renderBookingsTable);
        }
        if (bookingStatusFilter) {
            bookingStatusFilter.addEventListener('change', renderBookingsTable);
        }

        // TASK 1: MANAGE EVENT BOOKINGS (TABLE, EDIT, CANCEL, DELETE)
        function renderBookingsTable() {
            if (!bookingsTableBody) return;

            const totalCount = allBookings.length;
            const confirmedList = allBookings.filter(b => b.status === 'Confirmed');
            const pendingList = allBookings.filter(b => b.status === 'Pending');
            const cancelledList = allBookings.filter(b => b.status === 'Cancelled');
            const totalConfirmedAmount = confirmedList.reduce((sum, b) => sum + Number(b.totalAmount || 0), 0);

            // Update Summary Badges & Pills (always reflects full booking records)
            manageBookingsCount.textContent = totalCount;
            statTotalBookings.textContent = totalCount;
            statConfirmedBookings.textContent = confirmedList.length;
            statPendingBookings.textContent = pendingList.length;
            statCancelledBookings.textContent = cancelledList.length;
            statTotalBookingAmount.textContent = `₹${totalConfirmedAmount.toLocaleString('en-IN')}`;
            clearAllBookingsBtn.style.display = totalCount > 0 ? 'inline-block' : 'none';

            bookingsTableBody.innerHTML = '';

            if (totalCount === 0) {
                bookingsTableBody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                            No bookings found. Click <strong>"Book Now"</strong> on any event above to create a reservation!
                        </td>
                    </tr>
                `;
                return;
            }

            const filteredBookings = getFilteredBookings();

            if (filteredBookings.length === 0) {
                bookingsTableBody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                            No bookings match your search or status filter.
                        </td>
                    </tr>
                `;
                return;
            }

            filteredBookings.forEach((booking) => {
                const isCancelled = booking.status === 'Cancelled';
                const bookingStatus = ['Pending', 'Confirmed', 'Cancelled'].includes(booking.status) ? booking.status : 'Confirmed';
                const tr = document.createElement('tr');
                if (isCancelled) {
                    tr.className = 'row-cancelled';
                }

                tr.innerHTML = `
                    <td class="booking-id-cell">#${booking.bookingId}</td>
                    <td>
                        <div class="attendee-cell-name">${booking.eventName}</div>
                        <div class="attendee-cell-sub">📅 ${booking.eventDate} &bull; 📍 ${booking.eventLocation}</div>
                    </td>
                    <td>
                        <div class="attendee-cell-name">${booking.attendeeName}</div>
                        <div class="attendee-cell-sub">${booking.attendeeEmail} &bull; ${booking.attendeePhone}</div>
                    </td>
                    <td><strong>${booking.tickets}</strong> × ₹${Number(booking.unitPrice).toLocaleString('en-IN')}</td>
                    <td><strong style="color: ${isCancelled ? '#6b7280' : '#059669'};">₹${Number(booking.totalAmount).toLocaleString('en-IN')}</strong></td>
                    <td>
                        <span class="status-badge status-${bookingStatus.toLowerCase()}">
                            ● ${bookingStatus}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions-group">
                            <button type="button" class="btn-table-edit" onclick="openEditBookingModal('${booking.bookingId}')">Edit</button>
                            <button type="button" class="btn-table-cancel" ${isCancelled ? 'disabled' : ''} onclick="cancelBookingStatus('${booking.bookingId}')">Cancel</button>
                            <button type="button" class="btn-table-delete" onclick="deleteBookingRecord('${booking.bookingId}')">Delete</button>
                        </div>
                    </td>
                `;
                bookingsTableBody.appendChild(tr);
            });
        }

        // Open Edit Modal for a specific booking
        function openEditBookingModal(bookingId) {
            if (!requireLogin()) return;

            const booking = allBookings.find(b => b.bookingId === bookingId);
            if (!booking) return;

            editingBookingId = bookingId;
            editBookingIdLabel.textContent = `#${booking.bookingId}`;
            editBookingEventName.textContent = booking.eventName;
            editBookingTicketPrice.textContent = Number(booking.unitPrice).toLocaleString('en-IN');

            editBookingName.value = booking.attendeeName;
            editBookingEmail.value = booking.attendeeEmail;
            editBookingPhone.value = booking.attendeePhone;
            editBookingTickets.value = booking.tickets;
            editBookingStatus.value = booking.status;

            clearFieldError(editBookingName, editBookingNameError);
            clearFieldError(editBookingEmail, editBookingEmailError);
            clearFieldError(editBookingPhone, editBookingPhoneError);
            clearFieldError(editBookingTickets, editBookingTicketsError);

            updateEditBookingTotal();
            editBookingModal.style.display = 'flex';
        }

        function closeEditBookingModal() {
            editBookingModal.style.display = 'none';
            editingBookingId = null;
        }

        function updateEditBookingTotal() {
            const booking = allBookings.find(b => b.bookingId === editingBookingId);
            if (!booking) return;
            const qty = parseInt(editBookingTickets.value, 10);
            const validQty = !isNaN(qty) && qty > 0 ? qty : 0;
            const total = Number(booking.unitPrice) * validQty;
            editBookingTotalAmount.textContent = total.toLocaleString('en-IN');
        }

        editBookingTickets.addEventListener('input', updateEditBookingTotal);
        closeEditBookingBtn.addEventListener('click', closeEditBookingModal);

        editBookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!editingBookingId) return;

            const valid = validateBookingInputs(editBookingName, editBookingEmail, editBookingPhone, editBookingTickets, {
                name: editBookingNameError,
                email: editBookingEmailError,
                phone: editBookingPhoneError,
                tickets: editBookingTicketsError
            });

            if (!valid) return;

            const idx = allBookings.findIndex(b => b.bookingId === editingBookingId);
            if (idx === -1) return;

            const qty = parseInt(editBookingTickets.value, 10);
            const updatedTotal = Number(allBookings[idx].unitPrice) * qty;
            const updatedBooking = {
                ...allBookings[idx],
                attendeeName: editBookingName.value.trim(),
                attendeeEmail: editBookingEmail.value.trim(),
                attendeePhone: editBookingPhone.value.trim(),
                tickets: qty,
                status: editBookingStatus.value,
                totalAmount: updatedTotal
            };

            // Save database-backed bookings first so the HTML and PHP pages cannot drift.
            const dbBookingId = updatedBooking.dbBookingId;
            if (dbBookingId) {
                try {
                    const updateResp = await fetch('api/update_booking.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id: dbBookingId,
                            customer_name: editBookingName.value.trim(),
                            email: editBookingEmail.value.trim(),
                            ticket_quantity: qty,
                            status: editBookingStatus.value
                        })
                    });
                    const updateData = await updateResp.json();
                    if (!updateResp.ok || !updateData.success) {
                        showToast(updateData.message || 'Could not save the booking to the database.');
                        return;
                    }
                } catch (err) {
                    console.warn('Could not update booking in MySQL:', err);
                    showToast('Could not connect to the database. Booking was not changed.');
                    return;
                }
            }

            allBookings[idx] = updatedBooking;
            saveToStorage(BOOKINGS_STORAGE_KEY, allBookings);

            renderBookingsTable();
            renderDashboard();
            closeEditBookingModal();
            showToast(`Updated booking #${allBookings[idx].bookingId}`);
        });

        // Mark booking status as Cancelled (keeps record in table & updates Dashboard Cancelled count)
        async function cancelBookingStatus(bookingId) {
            if (!requireLogin()) return;

            const booking = allBookings.find(b => b.bookingId === bookingId);
            if (!booking || booking.status === 'Cancelled') return;

            openConfirmDialog(
                'Cancel Booking',
                `Are you sure you want to cancel booking #${bookingId} for "${booking.eventName}"?`,
                'Yes, Cancel Booking',
                async () => {
                    // Persist cancellation to MySQL via PHP
                    const dbBookingId = booking.dbBookingId;
                    if (dbBookingId) {
                        try {
                            const cancelResp = await fetch('api/update_booking.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    id: dbBookingId,
                                    customer_name: booking.attendeeName,
                                    email: booking.attendeeEmail,
                                    ticket_quantity: booking.tickets,
                                    status: 'Cancelled'
                                })
                            });
                            const cancelData = await cancelResp.json();
                            if (!cancelResp.ok || !cancelData.success) {
                                showToast(cancelData.message || 'Could not cancel the booking in the database.');
                                return;
                            }
                        } catch (err) {
                            console.warn('Could not cancel booking in MySQL:', err);
                            showToast('Could not connect to the database. Booking was not changed.');
                            return;
                        }
                    }

                    booking.status = 'Cancelled';
                    saveToStorage(BOOKINGS_STORAGE_KEY, allBookings);
                    renderBookingsTable();
                    renderDashboard();
                    showToast(`Booking #${bookingId} marked as Cancelled`);
                }
            );
        }

        // Permanently delete a booking row from the table
        function deleteBookingRecord(bookingId) {
            if (!requireLogin()) return;

            const booking = allBookings.find(b => b.bookingId === bookingId);
            if (!booking) return;

            openConfirmDialog(
                'Delete Booking Record',
                `Permanently delete booking #${bookingId} (${booking.eventName})? This cannot be undone.`,
                'Delete',
                () => {
                    allBookings = allBookings.filter(b => b.bookingId !== bookingId);
                    saveToStorage(BOOKINGS_STORAGE_KEY, allBookings);
                    renderBookingsTable();
                    renderDashboard();
                    showToast(`Deleted booking #${bookingId}`);
                }
            );
        }

        clearAllBookingsBtn.addEventListener('click', () => {
            if (!requireLogin()) return;

            openConfirmDialog(
                'Delete All Bookings',
                'Are you sure you want to remove all booking records?',
                'Delete All',
                () => {
                    allBookings = [];
                    saveToStorage(BOOKINGS_STORAGE_KEY, allBookings);
                    renderBookingsTable();
                    renderDashboard();
                    showToast('All bookings deleted');
                }
            );
        });

        // =========================================================
        // TASK 2: MY DASHBOARD RENDERER
        // =========================================================
        function renderDashboard() {
            const totalCount = allBookings.length;
            const confirmedBookings = allBookings.filter(b => b.status === 'Confirmed');
            const cancelledBookings = allBookings.filter(b => b.status === 'Cancelled');
            const totalSpent = confirmedBookings.reduce((sum, b) => sum + Number(b.totalAmount || 0), 0);

            // Upcoming events = Confirmed bookings with event date >= today (or all confirmed if date is valid)
            const todayStr = new Date().toISOString().split('T')[0];
            const upcomingBookings = confirmedBookings.filter(b => !b.eventDate || b.eventDate >= todayStr);

            // Update Dashboard Metric Cards
            dashTotalBookings.textContent = totalCount;
            dashUpcomingEvents.textContent = upcomingBookings.length;
            dashCancelledBookings.textContent = cancelledBookings.length;
            dashTotalSpent.textContent = `₹${totalSpent.toLocaleString('en-IN')}`;

            // Render Upcoming Events List inside Dashboard
            dashUpcomingBadge.textContent = upcomingBookings.length;
            dashUpcomingGrid.innerHTML = '';

            if (upcomingBookings.length === 0) {
                dashUpcomingGrid.innerHTML = `<div class="empty-state">No upcoming confirmed events right now. Book an event from the Explore page to see it here!</div>`;
            } else {
                upcomingBookings.forEach(booking => {
                    const card = document.createElement('div');
                    card.className = 'upcoming-booking-card';
                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="status-badge status-confirmed">● Confirmed</span>
                            <span class="booking-id-cell">#${booking.bookingId}</span>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-top: 0.25rem;">${booking.eventName}</h3>
                        <p class="event-details">📅 <strong>Date:</strong> ${booking.eventDate}</p>
                        <p class="event-details">📍 <strong>Location:</strong> ${booking.eventLocation}</p>
                        <p class="event-details">🎫 <strong>Tickets:</strong> ${booking.tickets} &bull; <strong>Paid:</strong> ₹${Number(booking.totalAmount).toLocaleString('en-IN')}</p>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                            <button type="button" class="btn-primary" onclick="openModal(${booking.eventId})">Event Details</button>
                            <button type="button" class="btn-table-edit" onclick="openEditBookingModal('${booking.bookingId}')">Edit Booking</button>
                        </div>
                    `;
                    dashUpcomingGrid.appendChild(card);
                });
            }

            // Render Recently Viewed Events Grid inside Dashboard
            const recentEvents = recentlyViewedIds
                .map(id => events.find(e => e.id === Number(id)))
                .filter(Boolean);

            dashRecentBadge.textContent = recentEvents.length;
            dashClearRecentBtn.style.display = recentEvents.length > 0 ? 'inline-block' : 'none';
            dashRecentGrid.innerHTML = '';

            if (recentEvents.length === 0) {
                dashRecentGrid.innerHTML = `<div class="empty-state">No recently viewed events yet. Click "View Details" on any event to track it here.</div>`;
            } else {
                recentEvents.forEach(ev => {
                    dashRecentGrid.appendChild(createEventCard(ev, { showCancelRecent: true }));
                });
            }
        }

        dashClearRecentBtn.addEventListener('click', () => {
            recentlyViewedIds = [];
            saveToStorage(RECENT_STORAGE_KEY, recentlyViewedIds);
            renderRecentlyViewed();
            renderDashboard();
            showToast('Cleared recently viewed history');
        });

        // =========================================================
        // FAVORITES, RECENTLY VIEWED & MAIN EVENT GRID
        // =========================================================
        function toggleFavorite(eventId) {
            const index = favoriteIds.indexOf(eventId);
            if (index === -1) {
                favoriteIds.push(eventId);
                showToast('Added to My Favorites ❤️');
            } else {
                favoriteIds.splice(index, 1);
                showToast('Removed from My Favorites');
            }
            saveToStorage(FAVORITES_STORAGE_KEY, favoriteIds);
            refreshAllViews();
        }

        function addToRecentlyViewed(eventId) {
            recentlyViewedIds = recentlyViewedIds.filter(id => id !== eventId);
            recentlyViewedIds.unshift(eventId);
            if (recentlyViewedIds.length > 8) {
                recentlyViewedIds = recentlyViewedIds.slice(0, 8);
            }
            saveToStorage(RECENT_STORAGE_KEY, recentlyViewedIds);
            renderRecentlyViewed();
            renderDashboard();
        }

        function renderEvents(eventsToRender) {
            eventGrid.innerHTML = '';
            eventCount.textContent = eventsToRender.length;

            if (eventsToRender.length === 0) {
                eventGrid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #6b7280; padding: 2rem;">No events found matching your criteria.</p>`;
                return;
            }

            eventsToRender.forEach(event => {
                eventGrid.appendChild(createEventCard(event));
            });
        }

        function renderFavorites() {
            const favEvents = favoriteIds
                .map(id => events.find(e => e.id === Number(id)))
                .filter(Boolean);

            favoritesCount.textContent = favEvents.length;
            clearFavoritesBtn.style.display = favEvents.length > 0 ? 'inline-block' : 'none';
            favoritesGrid.innerHTML = '';

            if (favEvents.length === 0) {
                favoritesGrid.innerHTML = `<div class="empty-state">No favorite events yet. Click the heart icon (♡) on any event card to save it here!</div>`;
                return;
            }

            favEvents.forEach(event => {
                favoritesGrid.appendChild(createEventCard(event, { showCancelFavorite: true }));
            });
        }

        function renderRecentlyViewed() {
            const recentEvents = recentlyViewedIds
                .map(id => events.find(e => e.id === Number(id)))
                .filter(Boolean);

            recentCount.textContent = recentEvents.length;
            clearRecentBtn.style.display = recentEvents.length > 0 ? 'inline-block' : 'none';
            recentGrid.innerHTML = '';

            if (recentEvents.length === 0) {
                recentGrid.innerHTML = `<div class="empty-state">You haven't viewed any events yet. Click "View Details" on an event card to see your history here.</div>`;
                return;
            }

            recentEvents.forEach(event => {
                recentGrid.appendChild(createEventCard(event, { showCancelRecent: true }));
            });
        }

        function refreshAllViews() {
            filterAndSortEvents();
            renderFavorites();
            renderRecentlyViewed();
            renderBookingsTable();
            renderDashboard();
        }

        function filterAndSortEvents() {
            let result = [...events];
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedCategory = categoryFilter.value;
            const selectedSort = sortPrice.value;

            if (searchTerm !== '') {
                result = result.filter(event => 
                    event.name.toLowerCase().includes(searchTerm) || 
                    event.location.toLowerCase().includes(searchTerm)
                );
            }

            if (selectedCategory !== 'All') {
                result = result.filter(event => event.category === selectedCategory);
            }

            if (selectedSort === 'low-high') {
                result.sort((a, b) => a.price - b.price);
            } else if (selectedSort === 'high-low') {
                result.sort((a, b) => b.price - a.price);
            }

            renderEvents(result);
        }

        function clearFilters() {
            searchInput.value = '';
            categoryFilter.value = 'All';
            sortPrice.value = 'default';
            renderEvents(events);
        }

        function openModal(eventId) {
            const id = Number(eventId);
            const event = events.find(e => e.id === id);
            if (!event) return;

            modalImage.dataset.fallbackApplied = 'false';
            modalImage.hidden = !event.image;
            if (event.image) modalImage.src = event.image;
            modalTitle.textContent = event.name;
            modalCategory.textContent = event.category;
            modalDate.textContent = event.date;
            modalLocation.textContent = event.location;
            modalPrice.textContent = event.price.toLocaleString('en-IN');
            modalDescription.textContent = event.description;
            modalBookNowBtn.onclick = () => openBookingModal(event.id);

            eventModal.style.display = 'flex';
            addToRecentlyViewed(eventId);
        }

        function closeModal() {
            eventModal.style.display = 'none';
        }

        let toastTimeout;
        function showToast(message) {
            toastBanner.textContent = message;
            toastBanner.style.display = 'block';
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toastBanner.style.display = 'none';
            }, 2500);
        }

        // Auth Modal Controllers
        function clearAuthErrors() {
            if (loginEmailInput && loginEmailError) clearFieldError(loginEmailInput, loginEmailError);
            if (loginPasswordInput && loginPasswordError) clearFieldError(loginPasswordInput, loginPasswordError);
            if (signupNameInput && signupNameError) clearFieldError(signupNameInput, signupNameError);
            if (signupEmailInput && signupEmailError) clearFieldError(signupEmailInput, signupEmailError);
            if (signupPasswordInput && signupPasswordError) clearFieldError(signupPasswordInput, signupPasswordError);
        }

        openLoginBtn.addEventListener('click', () => {
            clearAuthErrors();
            loginModal.style.display = 'flex';
        });
        openSignupBtn.addEventListener('click', () => {
            clearAuthErrors();
            signupModal.style.display = 'flex';
        });
        closeLoginBtn.addEventListener('click', () => { loginModal.style.display = 'none'; });
        closeSignupBtn.addEventListener('click', () => { signupModal.style.display = 'none'; });
        closeBookingBtn.addEventListener('click', closeBookingModal);

        function validateAuthForm(type) {
            let isValid = true;
            clearAuthErrors();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (type === 'Sign Up') {
                const nameVal = signupNameInput.value.trim();
                const emailVal = signupEmailInput.value.trim();
                const passVal = signupPasswordInput.value;

                if (nameVal === '') {
                    setFieldError(signupNameInput, signupNameError, 'Full name is required.');
                    isValid = false;
                } else if (!nameOnlyTextRegex.test(nameVal)) {
                    setFieldError(signupNameInput, signupNameError, 'Full name must contain only text.');
                    isValid = false;
                }

                if (emailVal === '') {
                    setFieldError(signupEmailInput, signupEmailError, 'Email address is required.');
                    isValid = false;
                } else if (!emailRegex.test(emailVal)) {
                    setFieldError(signupEmailInput, signupEmailError, 'Please enter a valid email address.');
                    isValid = false;
                }

                if (passVal.trim() === '' || passVal.length < 6) {
                    setFieldError(signupPasswordInput, signupPasswordError, 'Password must be at least 6 characters.');
                    isValid = false;
                }
            } else {
                const emailVal = loginEmailInput.value.trim();
                const passVal = loginPasswordInput.value;

                if (emailVal === '' || !emailRegex.test(emailVal)) {
                    setFieldError(loginEmailInput, loginEmailError, 'Please enter a valid email address.');
                    isValid = false;
                }

                if (passVal.trim() === '' || passVal.length < 6) {
                    setFieldError(loginPasswordInput, loginPasswordError, 'Password must be at least 6 characters.');
                    isValid = false;
                }
            }

            return isValid;
        }

        function getLoginDestination() {
            const requested = new URLSearchParams(window.location.search).get('next');
            return ['my_bookings.php', 'admin_events.php', 'admin_dashboard.php', 'view_bookings.php'].includes(requested)
                ? requested
                : 'my_bookings.php';
        }

        async function handleAuth(event, type) {
            event.preventDefault();
            if (!validateAuthForm(type)) return;
            const isSignup = type === 'Sign Up';
            const payload = isSignup
                ? { action: 'signup', name: signupNameInput.value.trim(), email: signupEmailInput.value.trim(), password: signupPasswordInput.value }
                : { action: 'login', email: loginEmailInput.value.trim(), password: loginPasswordInput.value };
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    showToast(result.message || 'Authentication failed.');
                    return;
                }
                setCurrentUser(result.user);
                updateAuthUI();
                showToast(isSignup ? `Welcome, ${result.user.name}! Account created.` : `Logged in as ${result.user.name}`);
                loginModal.style.display = 'none';
                signupModal.style.display = 'none';
                event.target.reset();
                clearAuthErrors();
                if (new URLSearchParams(window.location.search).get('login') === '1') {
                    window.location.href = getLoginDestination();
                }
            } catch (error) {
                showToast('Could not reach the server. Please try again.');
            }
        }

        clearFavoritesBtn.addEventListener('click', () => {
            favoriteIds = [];
            saveToStorage(FAVORITES_STORAGE_KEY, favoriteIds);
            refreshAllViews();
            showToast('Cleared all favorites');
        });

        clearRecentBtn.addEventListener('click', () => {
            recentlyViewedIds = [];
            saveToStorage(RECENT_STORAGE_KEY, recentlyViewedIds);
            renderRecentlyViewed();
            renderDashboard();
            showToast('Cleared recently viewed history');
        });

        searchInput.addEventListener('input', filterAndSortEvents);
        categoryFilter.addEventListener('change', filterAndSortEvents);
        sortPrice.addEventListener('change', filterAndSortEvents);
        clearFiltersBtn.addEventListener('click', clearFilters);
        closeModalBtn.addEventListener('click', closeModal);

        window.addEventListener('click', (e) => {
            if (e.target === eventModal) eventModal.style.display = 'none';
            if (e.target === bookingModal) bookingModal.style.display = 'none';
            if (e.target === editBookingModal) closeEditBookingModal();
            if (e.target === confirmActionModal) closeConfirmDialog();
            if (e.target === loginModal) loginModal.style.display = 'none';
            if (e.target === signupModal) signupModal.style.display = 'none';
        });

        // Load events from MySQL via the PHP PDO API (api/get_events.php)
        async function fetchEvents() {
            try {
                const response = await fetch('api/get_events.php');
                const data = await response.json();
                if (data.success && Array.isArray(data.events)) {
                    events = data.events.map(ev => ({
                        id: ev.id,
                        image: ev.image || '',
                        name: ev.name,
                        category: ev.category,
                        date: ev.event_date,
                        location: ev.location,
                        price: parseFloat(ev.price)
                    }));
                } else {
                    console.error('Failed to load events from API:', data.message);
                }
            } catch (err) {
                console.error('Could not fetch events from MySQL:', err);
            }
            try {
                const authResponse = await fetch('api/auth.php', { cache: 'no-store' });
                const authResult = await authResponse.json();
                if (authResult.success) setCurrentUser(authResult.user);
            } catch (err) {
                console.error('Could not load login session:', err);
            }
            if (new URLSearchParams(window.location.search).get('login') === '1') {
                if (getCurrentUser()) window.location.href = getLoginDestination();
                else loginModal.style.display = 'flex';
            }
            await fetchDatabaseBookings();
            // Render once events are loaded (empty array shows "No events" if fetch failed)
            updateAuthUI();
            renderEvents(events);
            renderFavorites();
            renderRecentlyViewed();
            renderBookingsTable();
            renderDashboard();
        }

        // Refresh database-backed bookings so edits made in view_bookings.php
        // are reflected in Manage Bookings and the dashboard.
        async function fetchDatabaseBookings() {
            try {
                const response = await fetch('api/get_bookings.php', { cache: 'no-store' });
                if (!response.ok) return;
                const data = await response.json();
                if (!data.success || !Array.isArray(data.bookings)) return;

                const databaseBookings = data.bookings.map(row => {
                    const event = events.find(item => Number(item.id) === Number(row.event_id));
                    const ticketPrice = parseFloat(row.ticket_price) || 0;
                    const quantity = parseInt(row.ticket_quantity, 10) || 0;
                    return {
                        bookingId: `DB-${row.id}`,
                        dbBookingId: Number(row.id),
                        eventId: Number(row.event_id),
                        eventName: row.event_name || 'Unknown Event',
                        eventDate: row.event_date || '',
                        eventLocation: row.event_location || '',
                        unitPrice: ticketPrice || (event ? Number(event.price) : 0),
                        attendeeName: row.customer_name,
                        attendeeEmail: row.email,
                        attendeePhone: '',
                        tickets: quantity,
                        totalAmount: ticketPrice * quantity,
                        status: ['Pending', 'Confirmed', 'Cancelled'].includes(row.status) ? row.status : 'Confirmed',
                        bookedAt: row.booked_at || ''
                    };
                });

                allBookings = databaseBookings;
                saveToStorage(BOOKINGS_STORAGE_KEY, allBookings);
            } catch (err) {
                console.warn('Could not refresh bookings from MySQL:', err);
            }
        }

        // Keep an open HTML tab in sync with status or booking edits made on the admin page.
        window.setInterval(async () => {
            if (document.visibilityState !== 'visible' || !getCurrentUser()) return;
            await fetchDatabaseBookings();
            renderBookingsTable();
            renderDashboard();
        }, 15000);

        window.addEventListener('focus', async () => {
            if (!getCurrentUser()) return;
            await fetchDatabaseBookings();
            renderBookingsTable();
            renderDashboard();
        });

        // Initial Render on Page Load (events loaded from MySQL)
        fetchEvents();
