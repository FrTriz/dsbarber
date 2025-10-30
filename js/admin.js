document.addEventListener('DOMContentLoaded', () => {
    // --- 1. SELETORES DE ELEMENTOS ---
    const monthYearEl = document.getElementById('month-year');
    const calendarGrid = document.querySelector('.calendar-grid');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    const appointmentModal = document.getElementById('appointment-modal');
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const newAppointmentBtn = document.getElementById('new-appointment-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const filterBtns = document.querySelectorAll('.filter-btn');

    // --- 2. ESTADO DA APLICAÇÃO ---
    let currentDate = new Date(); // Usa a data real
    
    // USA OS DADOS VINDOS DO PHP, NÃO DADOS FALSOS
    let currentAppointments = agendamentosIniciais; 
    
    let filters = {
        barber: 'all',
        status: 'all'
    };
    
    // Se o usuário logado NÃO for admin, trava o filtro de barbeiro no ID dele
    // (O 'idUsuarioLogado' foi injetado pelo admin.php)
    if (typeof idUsuarioLogado !== 'undefined' && idUsuarioLogado !== null) {
        const userType = document.querySelector('.filter-dropdown [data-dropdown="barber-dropdown"]') ? 'admin' : 'barbeiro';
        if (userType === 'barbeiro') {
            filters.barber = idUsuarioLogado;
            // Opcional: desabilitar o botão de filtro de barbeiro
            document.querySelector('[data-dropdown="barber-dropdown"]').disabled = true;
        }
    }


    // --- 3. LÓGICA DE BUSCA E RENDERIZAÇÃO (CONECTADA AO BACK-END) ---

    /**
     * Busca agendamentos no back-end (via AJAX/Fetch) e atualiza o calendário.
     */
    async function fetchAndRenderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1; // Mês é 1-12 para o PHP

        calendarGrid.innerHTML = '<div class="loading-calendar">Carregando...</div>'; 

        try {
            const url = `../php/Funcoes/buscar-agendamentos-admin.php?year=${year}&month=${month}&barber=${filters.barber}&status=${filters.status}`;
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('Falha ao buscar dados.');

            const data = await response.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro no back-end.');

            currentAppointments = data.agendamentos; // Atualiza o estado
            generateCalendar(currentDate); // Re-desenha com os novos dados

        } catch (error) {
            console.error("Erro ao buscar agendamentos:", error);
            calendarGrid.innerHTML = '<div class="error-calendar">Não foi possível carregar.</div>';
        }
    }

    /**
     * Desenha o calendário na tela
     */
    function generateCalendar(date) {
        calendarGrid.innerHTML = '<div class="weekday">DOM</div><div class="weekday">SEG</div><div class="weekday">TER</div><div class="weekday">QUA</div><div class="weekday">QUI</div><div class="weekday">SEX</div><div class="weekday">SAB</div>';
        const year = date.getFullYear();
        const month = date.getMonth();
        
        monthYearEl.textContent = `${date.toLocaleString('pt-BR', { month: 'long' })} ${year}`;
        
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
        const prevLastDate = new Date(year, month, 0).getDate();

        // Dias do mês anterior
        for (let i = firstDay; i > 0; i--) {
            createDayElement(prevLastDate - i + 1, true);
        }

        // Dias do mês atual
        for (let i = 1; i <= lastDate; i++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            // Filtra os agendamentos JÁ CARREGADOS para este dia
            const appointmentsForDay = currentAppointments.filter(apt => apt.dia === dateStr); 
            createDayElement(i, false, appointmentsForDay);
        }

        // Dias do próximo mês
        const remainingDays = 42 - (firstDay + lastDate); // 42 = 6 semanas * 7 dias
        for (let i = 1; i <= remainingDays; i++) {
            createDayElement(i, true);
        }
    }

    /**
     * Cria o elemento HTML para um dia (USA DADOS REAIS)
     */
    function createDayElement(day, isOtherMonth, appointments = []) {
        const dayEl = document.createElement('div');
        dayEl.classList.add('day');
        if (isOtherMonth) dayEl.classList.add('other-month');
        
        dayEl.innerHTML = `<span class="day-number">${day}</span>`;

        const appointmentsContainer = document.createElement('div');
        appointmentsContainer.className = 'appointments'; 

        appointments.forEach(apt => {
            const aptEl = document.createElement('div');
            // USA OS NOMES CORRETOS DA VIEW
            aptEl.classList.add('appointment', apt.status_agendamento); 
            aptEl.title = `Cliente: ${apt.nome_cliente}\nServiços: ${apt.servicos_agendados}\nStatus: ${apt.status_agendamento}`; // Tooltip
            aptEl.innerHTML = `
                <span class="time">${apt.hora_inicio_fmt.substring(0, 5)}</span>
                <span class="client">${apt.nome_cliente}</span>
                <span class="services">${apt.servicos_agendados || 'N/A'}</span>
            `;
            appointmentsContainer.appendChild(aptEl);
        });
        
        dayEl.appendChild(appointmentsContainer);
        calendarGrid.appendChild(dayEl);
    }

    // --- 4. FUNÇÕES DE INTERAÇÃO (Sidebar, Modal, Dropdown) ---
    // (Lógica do seu script original)
    const showSidebar = () => { sidebar.classList.add('show'); overlay.classList.add('show'); };
    const hideSidebar = () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); };
    const showModal = () => appointmentModal.classList.add('show');
    const hideModal = () => appointmentModal.classList.remove('show');
    
    function toggleDropdown(e) {
        e.stopPropagation();
        const dropdownId = e.currentTarget.dataset.dropdown;
        const dropdownMenu = document.getElementById(dropdownId);
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            if (menu.id !== dropdownId) menu.classList.remove('show');
        });
        dropdownMenu.classList.toggle('show');
    }

    /**
     * Lida com a seleção de filtro (MODIFICADO para chamar fetch)
     */
    function handleFilterSelection(e) {
        e.preventDefault();
        const value = e.target.dataset.value;
        const dropdownMenu = e.target.closest('.dropdown-menu');
        if (!dropdownMenu) return; // Proteção
        
        const filterType = dropdownMenu.id.includes('barber') ? 'barber' : 'status';
        
        filters[filterType] = value; // Atualiza o filtro no estado
        
        // CHAMA O BACK-END para buscar os dados filtrados
        fetchAndRenderCalendar(); 
        
        const button = document.querySelector(`[data-dropdown="${dropdownMenu.id}"]`);
        button.firstChild.textContent = e.target.textContent.trim() + ' '; 
        dropdownMenu.classList.remove('show'); // Fecha o dropdown
    }

    // --- 5. EVENT LISTENERS ---
    hamburgerBtn.addEventListener('click', showSidebar);
    closeSidebarBtn.addEventListener('click', hideSidebar);
    overlay.addEventListener('click', hideSidebar);

    // Navegação de Mês (MODIFICADO para chamar fetch)
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        fetchAndRenderCalendar(); // Busca dados do novo mês
    });
    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        fetchAndRenderCalendar(); // Busca dados do novo mês
    });

    // Dropdowns
    filterBtns.forEach(btn => btn.addEventListener('click', toggleDropdown));
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', handleFilterSelection);
    });
    window.addEventListener('click', () => { 
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    });

    // Modal
    newAppointmentBtn.addEventListener('click', showModal);
    closeModalBtn.addEventListener('click', hideModal);
    appointmentModal.addEventListener('click', (e) => { 
        if (e.target === appointmentModal) hideModal();
    });
    
    // Formulário do Modal (AINDA NÃO CONECTADO AO BACK-END)
    document.getElementById('appointment-form').addEventListener('submit', (e) => {
        e.preventDefault();
        // --- TODO: Mudar esta lógica para chamar um PHP via fetch ---
        console.warn("Lógica de salvar novo agendamento ainda é front-end!");
        // O código abaixo é só uma simulação
        const date = document.getElementById('appointment-date').value;
        currentAppointments.push({ 
            dia: date, 
            hora_inicio_fmt: '12:00', 
            nome_cliente: document.getElementById('client-name').value, 
            servicos_agendados: document.getElementById('appointment-service').value, 
            status_agendamento: 'confirmed' 
        });
        generateCalendar(currentDate);
        hideModal();
        e.target.reset();
    });

    // --- 6. Initial Load ---
    generateCalendar(currentDate); // Desenha o calendário inicial com os dados do PHP
});