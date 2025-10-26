document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const monthYearEl = document.getElementById('month-year');
    const calendarGrid = document.querySelector('.calendar-grid');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    const appointmentModal = document.getElementById('appointment-modal');

    // Buttons
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const newAppointmentBtn = document.getElementById('new-appointment-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const filterBtns = document.querySelectorAll('.filter-btn');

    // State
    let currentDate = new Date(2024, 9, 1); // October 2024
    let filters = {
        barber: 'all',
        status: 'all'
    };
    let sampleAppointments = {
        '2024-10-06': [
            { name: 'J. Doe', service: 'Pending Cut', status: 'pending', barber: 'John Wick' }
        ],
        '2024-10-07': [
            { name: 'M. Smith', service: 'Beard Trim', status: 'confirmed', barber: 'Mike Ross' },
            { name: 'L. Kane', service: 'Full Shave', status: 'canceled', barber: 'John Wick' }
        ],
        '2024-10-09': [
            { name: 'A. Taylor', service: 'Men\'s Cut', status: 'confirmed', barber: 'John Wick' }
        ]
    };

    // --- CALENDAR LOGIC ---
    function generateCalendar(date) {
        calendarGrid.innerHTML = '<div class="weekday">DOM</div><div class="weekday">SEG</div><div class="weekday">TER</div><div class="weekday">QUA</div><div class="weekday">QUI</div><div class="weekday">SEX</div><div class="weekday">SAB</div>';
        const year = date.getFullYear();
        const month = date.getMonth();
        
        monthYearEl.textContent = `${date.toLocaleString('pt-BR', { month: 'long' })} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
        const prevLastDate = new Date(year, month, 0).getDate();

        for (let i = firstDay; i > 0; i--) {
            createDayElement(prevLastDate - i + 1, true);
        }

        for (let i = 1; i <= lastDate; i++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const appointments = (sampleAppointments[dateStr] || []).filter(apt => 
                (filters.barber === 'all' || apt.barber === filters.barber) &&
                (filters.status === 'all' || apt.status === filters.status)
            );
            createDayElement(i, false, appointments);
        }

        const remainingDays = 42 - (firstDay + lastDate);
        for (let i = 1; i <= remainingDays; i++) {
            createDayElement(i, true);
        }
    }

    function createDayElement(day, isOtherMonth, appointments = []) {
        const dayEl = document.createElement('div');
        dayEl.classList.add('day');
        if (isOtherMonth) dayEl.classList.add('other-month');
        
        dayEl.innerHTML = `<span class="day-number">${day}</span>`;

        appointments.forEach(apt => {
            const aptEl = document.createElement('div');
            aptEl.classList.add('appointment', apt.status);
            aptEl.innerHTML = `<strong>${apt.name}</strong><br>${apt.service}`;
            dayEl.appendChild(aptEl);
        });

        calendarGrid.appendChild(dayEl);
    }

    // --- SIDEBAR & OVERLAY LOGIC ---
    const showSidebar = () => {
        sidebar.classList.add('show');
        overlay.classList.add('show');
    };
    const hideSidebar = () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    };

    // --- MODAL LOGIC ---
    const showModal = () => appointmentModal.classList.add('show');
    const hideModal = () => appointmentModal.classList.remove('show');

    // --- DROPDOWN LOGIC ---
    function toggleDropdown(e) {
        e.stopPropagation();
        const dropdownId = e.currentTarget.dataset.dropdown;
        const dropdownMenu = document.getElementById(dropdownId);
        // Close other dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            if (menu.id !== dropdownId) menu.classList.remove('show');
        });
        dropdownMenu.classList.toggle('show');
    }

    function handleFilterSelection(e) {
        e.preventDefault();
        const value = e.target.dataset.value;
        const dropdownMenu = e.target.closest('.dropdown-menu');
        const filterType = dropdownMenu.id.includes('barber') ? 'barber' : 'status';
        
        filters[filterType] = value;
        generateCalendar(currentDate);

        // Update button text
        const button = document.querySelector(`[data-dropdown="${dropdownMenu.id}"]`);
        button.firstChild.textContent = e.target.textContent + ' ';
    }

    // --- EVENT LISTENERS ---
    hamburgerBtn.addEventListener('click', showSidebar);
    closeSidebarBtn.addEventListener('click', hideSidebar);
    overlay.addEventListener('click', hideSidebar);
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        generateCalendar(currentDate);
    });
    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        generateCalendar(currentDate);
    });

    // Dropdown listeners
    filterBtns.forEach(btn => btn.addEventListener('click', toggleDropdown));
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', handleFilterSelection);
    });
    window.addEventListener('click', () => { // Close dropdowns on outside click
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    });

    // Modal listeners
    newAppointmentBtn.addEventListener('click', showModal);
    closeModalBtn.addEventListener('click', hideModal);
    appointmentModal.addEventListener('click', (e) => { // Close on overlay click
        if (e.target === appointmentModal) hideModal();
    });
    document.getElementById('appointment-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const newAppointment = {
            name: document.getElementById('client-name').value,
            service: document.getElementById('appointment-service').value,
            status: 'confirmed', // New appointments are confirmed by default
            barber: document.getElementById('appointment-barber').value
        };
        const date = document.getElementById('appointment-date').value;

        if (!sampleAppointments[date]) {
            sampleAppointments[date] = [];
        }
        sampleAppointments[date].push(newAppointment);
        
        generateCalendar(currentDate);
        hideModal();
        e.target.reset();
    });

    // Initial Load
    generateCalendar(currentDate);
});
