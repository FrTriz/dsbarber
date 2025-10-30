document.addEventListener('DOMContentLoaded', () => {
    // --- 1. ESTADO DA APLICAÇÃO ---
    // (Modifiquei o 'state' original para guardar o que realmente precisamos)
    const state = {
        currentStep: 1,
        barbeiroId: null,
        barbeiroNome: null,
        servicos: [], // Agora um array de objetos {id, nome, price, duration}
        date: null, // Formato YYYY-MM-DD
        time: null, // Formato HH:MM
        totalPrice: 0,
        totalDuration: 0, // <-- IMPORTANTE: Precisamos disso para o back-end
        paymentOption: 'full' 
    };

    // --- 2. SELETORES DE ELEMENTOS (do seu script) ---
    const nextBtn = document.getElementById('next-btn');
    const backBtn = document.getElementById('back-btn');

    const summaryBarber = document.getElementById('summary-barber'); 
    const summaryServices = document.getElementById('summary-services');
    const summaryDatetime = document.getElementById('summary-datetime');
    const summaryTotal = document.getElementById('summary-total');

    const paymentTotalFull = document.getElementById('payment-total-full');
    const paymentTotalHalf = document.getElementById('payment-total-half');
    const summaryStep4 = document.getElementById('summary-step-4');
    const paymentTabs = document.querySelectorAll('.tab-btn');

    const monthNameEl = document.getElementById('month-name');
    const calendarDaysEl = document.getElementById('calendar-days');
    const timeSlotsContainer = document.getElementById('dynamic-slots-container');
    let currentDate = new Date();

    // --- 3. FUNÇÕES PRINCIPAIS (do seu script, com modificações) ---

    function updateSummary() {
        summaryBarber.textContent = state.barbeiroNome ? state.barbeiroNome : 'Não selecionado';

        if (state.servicos.length > 0) {
            summaryServices.innerHTML = state.servicos.map(s => s.nome).join(', <br>');
        } else {
            summaryServices.textContent = 'Não selecionado';
        }

        // Recalcula os totais
        state.totalPrice = state.servicos.reduce((acc, service) => acc + service.price, 0);
        state.totalDuration = state.servicos.reduce((acc, service) => acc + service.duration, 0);

        const priceToPay = state.paymentOption === 'half' ? state.totalPrice / 2 : state.totalPrice;
        summaryTotal.textContent = `R$${priceToPay.toFixed(2)}`;

       // --- Bloco de Data e Hora CORRIGIDO ---
        if (state.date && state.time) {
            // Caso 1: Usuário já selecionou AMBOS
            const dataFormatada = state.date.split('-').reverse().join('/');
            summaryDatetime.textContent = `${dataFormatada} às ${state.time}`;
        
        } else if (state.date) {
            // Caso 2: Usuário selecionou SÓ A DATA
            const dataFormatada = state.date.split('-').reverse().join('/');
            summaryDatetime.textContent = `${dataFormatada}`; // Mostra só a data
        
        } else {
            // Caso 3: Usuário não selecionou nada
            summaryDatetime.textContent = 'Não selecionado';
        }

        if (state.currentStep === 4) {
            paymentTotalFull.textContent = state.totalPrice.toFixed(2);
            paymentTotalHalf.textContent = (state.totalPrice / 2).toFixed(2);
        }
    }

    // A sua função goToStep está perfeita, sem mudanças
    function goToStep(step) {
        state.currentStep = step;
        document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
        document.getElementById(`step-${step}`).classList.add('active');

        document.querySelectorAll('.progress-bar .step').forEach((el, index) => {
            if (index < step - 1) {
                el.classList.add('completed');
                el.classList.remove('active');
            } else if (index === step - 1) {
                el.classList.add('active');
                el.classList.remove('completed');
            } else {
                el.classList.remove('active', 'completed');
            }
        });
        backBtn.disabled = step === 1;
        if (step === 4) {
            nextBtn.textContent = 'Confirmar Agendamento';
            summaryStep4.style.display = 'block';
        } else {
            nextBtn.textContent = 'Próximo Passo';
            summaryStep4.style.display = 'none';
        }
        updateSummary();
    }

    // --- 4. MODIFICAÇÃO DOS EVENT LISTENERS ---

    // Step 1: Barber Selection (MODIFICADO)
    document.querySelectorAll('.barber-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.barber-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            // MODIFICAÇÃO: Lendo os 'data-attributes' corretos
            state.barbeiroId = card.dataset.barbeiroId;
            state.barbeiroNome = card.dataset.barbeiroNome;

            updateSummary();

            // Se o usuário trocar o barbeiro, limpamos os horários
            if (state.date) fetchHorarios();
        });
    });

    // Step 2: Service Selection (MODIFICADO)
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => {
            card.classList.toggle('selected');
            
            // MODIFICAÇÃO: Lendo 'data-attributes' corretos (incluindo ID e DURAÇÃO)
            const serviceId = card.dataset.serviceId;
            const serviceName = card.dataset.serviceNome;
            const servicePrice = parseFloat(card.dataset.price);
            const serviceDuration = parseInt(card.dataset.duration, 10);

            if (card.classList.contains('selected')) {
                state.servicos.push({ id: serviceId, nome: serviceName, price: servicePrice, duration: serviceDuration });
            } else {
                state.servicos = state.servicos.filter(s => s.id !== serviceId);
            }
            updateSummary();

            // Se o usuário mudar os serviços, limpamos os horários
            if (state.date) fetchHorarios();
        });
    });

   // Step 3: Calendar (MODIFICADO para chamar o fetch)
    function generateCalendar(date) {
        calendarDaysEl.innerHTML = '';
        const year = date.getFullYear();
        const month = date.getMonth();
        monthNameEl.textContent = `${date.toLocaleString('pt-BR', { month: 'long' })} ${year}`;
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            calendarDaysEl.appendChild(document.createElement('div'));
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0); // Zera a hora para comparar só o dia

        for (let i = 1; i <= lastDate; i++) {
            const dayEl = document.createElement('div');
            dayEl.textContent = i;

            // --- CORREÇÃO DA LÓGICA DE DATA ---
            // 1. Criamos a data do loop em 'tempo local' (em vez de UTC)
            const dateOfLoop = new Date(year, month, i); 
            
            // 2. Formata a data como YYYY-MM-DD para o back-end
            const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            
            // 3. Comparamos 'local' com 'local', o que funciona
            if (dateOfLoop < today) {
                dayEl.classList.add('disabled');
            }
            // --- FIM DA CORREÇÃO ---
            
            dayEl.addEventListener('click', () => {
                // Esta checagem agora vai funcionar corretamente
                if (dayEl.classList.contains('disabled')) return;

                document.querySelectorAll('.days div.selected').forEach(d => d.classList.remove('selected'));
                dayEl.classList.add('selected');

                state.date = fullDate; 
                state.time = null; // Limpa a hora ao trocar o dia
                updateSummary();
                
                // *** CHAMA O BACK-END ***
                fetchHorarios(); 
            });
            calendarDaysEl.appendChild(dayEl);
        }
    }

    document.getElementById('prev-month').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        generateCalendar(currentDate);
    });
    document.getElementById('next-month').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        generateCalendar(currentDate);
    });

    // Step 3: Time (SUBSTITUÍDO)
    // DELETAMOS o seu 'document.querySelectorAll('.time-slot').forEach...' 
    // ADICIONAMOS a função fetchHorarios
    async function fetchHorarios() {
        // Validação
        if (!state.barbeiroId) {
            alert("Por favor, volte e selecione um barbeiro.");
            goToStep(1);
            return;
        }
        if (state.totalDuration === 0) {
            alert("Por favor, volte e selecione ao menos um serviço.");
            goToStep(2);
            return;
        }

        timeSlotsContainer.innerHTML = '<p class="loading">Buscando horários...</p>';

        try {
            const url = `../php/Funcoes/buscar-horarios.php?id_barbeiro=${state.barbeiroId}&data=${state.date}&duracao=${state.totalDuration}`;
            const response = await fetch(url);
            if (!response.ok) throw new Error("Falha na resposta do servidor.");

            const horarios = await response.json();
            timeSlotsContainer.innerHTML = ''; 

            if (horarios.erro) {
                throw new Error(horarios.erro);
            }
            if (horarios.mensagem) {
                timeSlotsContainer.innerHTML = `<p class="no-slots">${horarios.mensagem}</p>`;
            } else if (horarios.length === 0) {
                timeSlotsContainer.innerHTML = '<p class="no-slots">Nenhum horário disponível para este dia.</p>';
            } else {
                horarios.forEach(horario => {
                    const slotButton = document.createElement('button');
                    slotButton.className = 'time-slot';
                    slotButton.textContent = horario;
                    slotButton.dataset.time = horario;
                    timeSlotsContainer.appendChild(slotButton);
                });
            }
        } catch (error) {
            console.error("Erro ao buscar horários:", error);
            timeSlotsContainer.innerHTML = '<p class="no-slots" style="color: red;">Não foi possível carregar os horários.</p>';
        }
    }

    // ADICIONADO: Listener para os botões de horário (que são criados dinamicamente)
    timeSlotsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('time-slot')) {
            document.querySelectorAll('.time-slot.selected').forEach(s => s.classList.remove('selected'));
            const slotButton = e.target;
            slotButton.classList.add('selected');
            
            state.time = slotButton.dataset.time; // Salva a hora
            updateSummary();
        }
    });

    // Step 4: Payment Options (Seu código está perfeito, sem mudanças)
    paymentTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            paymentTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            state.paymentOption = tab.textContent.toLowerCase().includes('metade') ? 'half' : 'full';
            updateSummary();
        });
    });

   // --- Navigation (MODIFICADO para validar e finalizar) ---
    
    nextBtn.addEventListener('click', async () => { // <-- Tornamos 'async'
        
        if (state.currentStep < 4) {
            
            // --- VALIDAÇÃO (Impede o cliente de avançar sem escolher) ---
            if (state.currentStep === 1 && !state.barbeiroId) {
                alert('Por favor, selecione um barbeiro para continuar.');
                return;
            }
            if (state.currentStep === 2 && state.servicos.length === 0) {
                alert('Por favor, selecione pelo menos um serviço.');
                return;
            }
            if (state.currentStep === 3 && (!state.date || !state.time)) {
                alert('Por favor, selecione uma data e um horário.');
                return;
            }
            
            // Se passou na validação, avança
            goToStep(state.currentStep + 1);
        
        } else if (state.currentStep === 4) {
            
            // --- LÓGICA DE FINALIZAÇÃO (PASSO 4) ---
            // O botão agora é "Confirmar Agendamento"
            
            nextBtn.disabled = true; // Desabilita o botão para evitar clique duplo
            nextBtn.textContent = 'Processando...';

            try {
                // 1. Chamar o 'criar-agendamento.php'
                const response = await fetch('../php/Funcoes/criar-agendamento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    // Envia todo o objeto 'state' como JSON
                    body: JSON.stringify(state) 
                });

                if (!response.ok) {
                    throw new Error('Falha ao se comunicar com o servidor.');
                }

                const data = await response.json();

                if (!data.sucesso) {
                    // Se o PHP deu um erro (ex: "Usuário não logado")
                    throw new Error(data.mensagem || 'Erro desconhecido no back-end.');
                }

                // 2. SUCESSO! O agendamento e o pagamento foram criados no banco.
                console.log('Agendamento criado (ID):', data.id_agendamento);
                console.log('Pagamento criado (ID):', data.id_pagamento);
                console.log('Valor a Pagar:', data.valor_a_pagar);
                
                // Salva os IDs para o próximo passo (API do Mercado Pago)
                state.id_agendamento = data.id_agendamento;
                state.id_pagamento = data.id_pagamento;

                // --- PRÓXIMO PASSO (A SER FEITO): ---
                // Agora é a hora de chamar a API do Mercado Pago
                // Vamos criar uma função para isso.
                
                alert('Agendamento pendente criado! Próximo passo: Gerar o PIX real.');
                
                // (Aqui chamaremos a função para gerar o PIX)
                // gerarPixMercadoPago(data.id_pagamento, data.valor_a_pagar);
                
                nextBtn.textContent = 'Agendamento Realizado';
                // (O botão "Já Paguei" do seu HTML [cite: 88-89] ainda não foi implementado)
                const jaPagueiBtn = document.querySelector('.confirm-payment-btn');
                if(jaPagueiBtn) jaPagueiBtn.style.display = 'block';


            } catch (error) {
                console.error('Erro ao criar agendamento:', error);
                alert('Erro: ' + error.message);
                nextBtn.disabled = false; // Reabilita o botão
                nextBtn.textContent = 'Confirmar Agendamento';
            }
        }
    });

    backBtn.addEventListener('click', () => {
        if (state.currentStep > 1) {
            goToStep(state.currentStep - 1);
        }
    });
    
    // Initial setup (MODIFICADO)
    const defaultBarber = document.querySelector('.barber-card');
    if(defaultBarber) {
        defaultBarber.classList.add('selected');
        // MODIFICAÇÃO: Lendo os 'data-attributes' corretos
        state.barbeiroId = defaultBarber.dataset.barbeiroId;
        state.barbeiroNome = defaultBarber.dataset.barbeiroNome;
    }

    generateCalendar(currentDate);
    updateSummary();
    goToStep(1);
});