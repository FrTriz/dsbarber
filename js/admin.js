document.addEventListener('DOMContentLoaded', () => {
    // --- 1. SELETORES DE ELEMENTOS ---
    const monthYearEl = document.getElementById('month-year');
    const calendarGrid = document.querySelector('.calendar-grid');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const filterBtns = document.querySelectorAll('.filter-btn');

    // Modais
    const newAppointmentBtn = document.getElementById('new-appointment-btn');
    const appointmentModal = document.getElementById('appointment-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const dayDetailsModal = document.getElementById('day-details-modal');
    const closeDayDetailsBtn = document.getElementById('close-day-details-btn');
    const dayDetailsTitle = document.getElementById('day-details-title');
    const dayDetailsList = document.getElementById('day-details-list');


    // --- 2. ESTADO DA APLICAÇÃO ---
    let currentDate = new Date(); 
    let currentAppointments = []; 
    let filters = {
        barber: 'all',
        status: 'all'
    };
    
    if (typeof idUsuarioLogado !== 'undefined' && idUsuarioLogado !== null) {
        const userTypeElement = document.querySelector('.filter-dropdown [data-dropdown="barber-dropdown"]');
        const userType = userTypeElement ? (userTypeElement.offsetParent ? 'admin' : 'barbeiro') : 'barbeiro'; 
        
        if (userType === 'barbeiro') {
            filters.barber = idUsuarioLogado;
            const barberFilterBtn = document.querySelector('[data-dropdown="barber-dropdown"]');
            if(barberFilterBtn) barberFilterBtn.disabled = true;
        }
    }

    // --- FUNÇÃO DE TRADUÇÃO DE STATUS ---
    function traduzirStatusParaClasse(statusPortugeues) {
        if (!statusPortugeues) return 'default';
        const statusLower = statusPortugeues.toLowerCase();
        
        switch (statusLower) {
            case 'pendente':
                return 'pending';
            case 'confirmado':
                return 'confirmed';
            case 'cancelado':
                return 'canceled';
            default:
                return statusLower;
        }
    }


    // --- 3. LÓGICA DE BUSCA E RENDERIZAÇÃO ---

    async function fetchAndRenderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1; 

        calendarGrid.innerHTML = '<div class="loading-calendar">Carregando...</div>'; 

        try {
            const url = `../php/Funcoes/buscar-agendamentos-admin.php?year=${year}&month=${month}&barber=${filters.barber}&status=${filters.status}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error('Falha ao buscar dados.');
            const data = await response.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro no back-end.');

            currentAppointments = data.agendamentos; 
            generateCalendar(currentDate); 
        } catch (error) {
            console.error("Erro ao buscar agendamentos:", error);
            calendarGrid.innerHTML = '<div class="error-calendar">Não foi possível carregar.</div>';
        }
    }

    function generateCalendar(date) {
        calendarGrid.innerHTML = '<div class="weekday">DOM</div><div class="weekday">SEG</div><div class="weekday">TER</div><div class="weekday">QUA</div><div class="weekday">QUI</div><div class="weekday">SEX</div><div class="weekday">SAB</div>';
        const year = date.getFullYear();
        const month = date.getMonth();
        
        monthYearEl.textContent = `${date.toLocaleString('pt-BR', { month: 'long' })} ${year}`;
        
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
        const prevLastDate = new Date(year, month, 0).getDate();

        for (let i = firstDay; i > 0; i--) {
            createDayElement(prevLastDate - i + 1, month - 1, year, true);
        }

        for (let i = 1; i <= lastDate; i++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const appointmentsForDay = currentAppointments.filter(apt => apt.dia === dateStr); 
            createDayElement(i, month, year, false, appointmentsForDay);
        }

        const nextDays = 42 - (firstDay + lastDate);
        for (let i = 1; i <= nextDays; i++) {
            createDayElement(i, month + 1, year, true);
        }
    }

    function createDayElement(day, month, year, isOtherMonth, appointments = []) {
        const dayEl = document.createElement('div');
        dayEl.classList.add('day');
        if (isOtherMonth) {
            dayEl.classList.add('other-month');
        } else {
            dayEl.dataset.day = day;
            dayEl.dataset.month = month;
            dayEl.dataset.year = year;
            dayEl.dataset.appointments = JSON.stringify(appointments); 
        }
        
        const dayNumberSpan = document.createElement('span');
        dayNumberSpan.className = 'day-number';
        dayNumberSpan.textContent = day;
        dayEl.appendChild(dayNumberSpan);

        const appointmentsContainer = document.createElement('div');
        appointmentsContainer.className = 'appointments';

        const maxAppointmentsToShow = 3;
        
        appointments.slice(0, maxAppointmentsToShow).forEach(apt => {
            const aptEl = document.createElement('div');
            const statusClasse = traduzirStatusParaClasse(apt.status_agendamento);
            aptEl.classList.add('appointment', statusClasse); 
            aptEl.title = `Cliente: ${apt.nome_cliente}\nStatus: ${apt.status_agendamento}`;
            aptEl.innerHTML = `
                <span class="time">${apt.hora_inicio_fmt.substring(0, 5)}</span>
                <span class="client">${apt.nome_cliente}</span>
            `;
            appointmentsContainer.appendChild(aptEl);
        });
        
        if (appointments.length > maxAppointmentsToShow) {
            const remainder = appointments.length - maxAppointmentsToShow;
            const moreLink = document.createElement('div');
            moreLink.className = 'day-more-link';
            moreLink.textContent = `+${remainder} mais`;
            appointmentsContainer.appendChild(moreLink);
        }
        
        dayEl.appendChild(appointmentsContainer);
        calendarGrid.appendChild(dayEl);
    }

    // --- 4. FUNÇÕES DE INTERAÇÃO ---
    
    const showSidebar = () => { sidebar.classList.add('show'); overlay.classList.add('show'); };
    const hideSidebar = () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); };
    
    const showAppointmentModal = () => appointmentModal.classList.add('show');
    const hideAppointmentModal = () => appointmentModal.classList.remove('show');
    
    /**
     * MODIFICADO: Agora inclui o botão "Confirmar"
     */
    const showDayDetailsModal = (day, month, year, appointments) => {
        const date = new Date(year, month, day);
        const titleDate = date.toLocaleDateString('pt-BR', { 
            day: 'numeric', month: 'long', year: 'numeric' 
        });
        dayDetailsTitle.textContent = `Agendamentos (${titleDate})`;
        
        dayDetailsList.innerHTML = ''; 
        
        if (appointments.length === 0) {
            dayDetailsList.innerHTML = '<p>Nenhum agendamento para este dia.</p>';
        } else {
            appointments.forEach(apt => {
                const detailEl = document.createElement('div');
                const statusOriginal = apt.status_agendamento; 
                const statusClasse = traduzirStatusParaClasse(statusOriginal);
                const statusTexto = statusOriginal.charAt(0).toUpperCase() + statusOriginal.slice(1).toLowerCase();
                
                detailEl.className = `appointment-detail status-${statusClasse}`;
                
                const precoFormatado = apt.valor_total ? 
                    parseFloat(apt.valor_total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 
                    'N/A';
                
                // --- (NOVO) Lógica do Botão ---
                let buttonHtml = '';
                if (statusClasse === 'pending') {
                    buttonHtml = `
                        <div class="appointment-detail-footer">
                            <button class="btn-confirm-manual" data-id="${apt.id_agendamento}">
                                Confirmar Pagamento
                            </button>
                        </div>
                    `;
                }
                // --- Fim da Lógica do Botão ---

                detailEl.innerHTML = `
                    <h4>
                        <span>${apt.hora_inicio_fmt.substring(0, 5)} - ${apt.nome_cliente}</span>
                        <span class="status-badge ${statusClasse}">${statusTexto}</span>
                    </h4>
                    <p><i class="fas fa-concierge-bell"></i> ${apt.servicos_agendados || 'N/A'}</p>
                    <p><i class="fas fa-dollar-sign"></i> ${precoFormatado}</p>
                    ${buttonHtml}
                `;
                dayDetailsList.appendChild(detailEl);
            });
        }
        
        dayDetailsModal.classList.add('show');
    };
    
    const hideDayDetailsModal = () => {
        dayDetailsModal.classList.remove('show');
    };
    
    /**
     * (NOVO) Função para lidar com a confirmação manual
     */
    async function handleManualConfirmation(idAgendamento, buttonElement) {
        buttonElement.disabled = true;
        buttonElement.textContent = 'Confirmando...';

        try {
            const formData = new FormData();
            formData.append('id_agendamento', idAgendamento);

            const response = await fetch('../php/Funcoes/confirmar-agendamento.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.sucesso) {
                throw new Error(data.mensagem || 'Erro desconhecido no servidor.');
            }

            // --- SUCESSO! Atualizar a UI ---
            
            // 1. Atualizar o item no modal
            const detailElement = buttonElement.closest('.appointment-detail');
            const badgeElement = detailElement.querySelector('.status-badge');
            
            detailElement.classList.remove('status-pending');
            detailElement.classList.add('status-confirmed');
            
            badgeElement.classList.remove('pending');
            badgeElement.classList.add('confirmed');
            badgeElement.textContent = 'Confirmado';
            
            buttonElement.closest('.appointment-detail-footer').remove(); // Remove o rodapé com o botão

            // 2. Atualizar o estado global (para o calendário)
            const index = currentAppointments.findIndex(apt => apt.id_agendamento == idAgendamento);
            if (index > -1) {
                currentAppointments[index].status_agendamento = 'confirmado';
            }

            // 3. Redesenhar o calendário principal (sem fechar o modal)
            generateCalendar(currentDate);

        } catch (error) {
            console.error('Erro ao confirmar:', error);
            alert('Falha ao confirmar: ' + error.message);
            buttonElement.disabled = false;
            buttonElement.textContent = 'Confirmar Pagamento';
        }
    }

    
    function toggleDropdown(e) {
        e.stopPropagation();
        const dropdownId = e.currentTarget.dataset.dropdown;
        const dropdownMenu = document.getElementById(dropdownId);
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            if (menu.id !== dropdownId) menu.classList.remove('show');
        });
        dropdownMenu.classList.toggle('show');
    }

    function handleFilterSelection(e) {
        e.preventDefault();
        const value = e.target.dataset.value;
        const dropdownMenu = e.target.closest('.dropdown-menu');
        if (!dropdownMenu) return; 
        
        const filterType = dropdownMenu.id.includes('barber') ? 'barber' : 'status';
        filters[filterType] = value; 
        
        fetchAndRenderCalendar(); 
        
        const button = document.querySelector(`[data-dropdown="${dropdownMenu.id}"]`);
        button.firstChild.textContent = e.target.textContent.trim() + ' '; 
        dropdownMenu.classList.remove('show');
    }

    // --- 5. EVENT LISTENERS ---
    hamburgerBtn.addEventListener('click', showSidebar);
    closeSidebarBtn.addEventListener('click', hideSidebar);
    overlay.addEventListener('click', hideSidebar);

    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        fetchAndRenderCalendar();
    });
    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        fetchAndRenderCalendar();
    });

    filterBtns.forEach(btn => btn.addEventListener('click', toggleDropdown));
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', handleFilterSelection);
    });
    window.addEventListener('click', () => { 
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => menu.classList.remove('show'));
    });

    newAppointmentBtn.addEventListener('click', showAppointmentModal);
    closeModalBtn.addEventListener('click', hideAppointmentModal);
    appointmentModal.addEventListener('click', (e) => { 
        if (e.target === appointmentModal) hideAppointmentModal();
    });
    
    closeDayDetailsBtn.addEventListener('click', hideDayDetailsModal);
    dayDetailsModal.addEventListener('click', (e) => {
        if (e.target === dayDetailsModal) hideDayDetailsModal();
    });
    
    calendarGrid.addEventListener('click', (e) => {
        const dayElement = e.target.closest('.day');
        if (!dayElement || dayElement.classList.contains('other-month')) {
            return;
        }
        
        const day = dayElement.dataset.day;
        const month = dayElement.dataset.month;
        const year = dayElement.dataset.year;
        const appointments = JSON.parse(dayElement.dataset.appointments);
        
        showDayDetailsModal(day, month, year, appointments);
    });

    // --- (NOVO) Listener para o clique no botão "Confirmar" ---
    dayDetailsList.addEventListener('click', (e) => {
        const confirmButton = e.target.closest('.btn-confirm-manual');
        if (confirmButton) {
            const id = confirmButton.dataset.id;
            handleManualConfirmation(id, confirmButton);
        }
    });
    
    
    document.getElementById('appointment-form').addEventListener('submit', (e) => {
        e.preventDefault();
        console.warn("Lógica de salvar novo agendamento ainda é front-end!");
        const date = document.getElementById('appointment-date').value;
        currentAppointments.push({ 
            dia: date, 
            id_agendamento: Date.now(), // ID Fictício
            hora_inicio_fmt: '12:00', 
            nome_cliente: document.getElementById('client-name').value, 
            servicos_agendados: document.getElementById('appointment-service').value, 
            status_agendamento: 'pendente', // Mude para 'pendente' para testar
            valor_total: 50
        });
        generateCalendar(currentDate);
        hideAppointmentModal();
        e.target.reset();
    });

    // --- 6. Initial Load ---
    fetchAndRenderCalendar();
});