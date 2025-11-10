document.addEventListener('DOMContentLoaded', () => {
    // --- 1. ESTADO DA APLICAÇÃO ---
    const state = {
        currentStep: 1,
        barbeiroId: null,
        barbeiroNome: null,
        diasDeTrabalho: null, // Começa nulo (não carregado)
        servicos: [], 
        date: null,
        time: null, 
        totalPrice: 0,
        totalDuration: 0,
        paymentOption: 'full',
        idAgendamento: null,
        statusCheckInterval: null 
    };

    // --- 2. SELETORES DE ELEMENTOS ---
    const nextBtn = document.getElementById('next-btn');
    const backBtn = document.getElementById('back-btn');
    const summaryBarber = document.getElementById('summary-barber'); 
    const summaryServices = document.getElementById('summary-services');
    const summaryDatetime = document.getElementById('summary-datetime');
    const summaryTotal = document.getElementById('summary-total');
    const paymentTotalFull = document.getElementById('payment-total-full');
    const paymentTotalHalf = document.getElementById('payment-total-half');
    const paymentTabs = document.querySelectorAll('.tab-btn');
    const pixLoading = document.getElementById('pix-loading');
    const pixContainer = document.getElementById('pix-container');
    const qrCodeImg = document.getElementById('pix-qr-code-img');
    const copiaColaTexto = document.getElementById('pix-copia-cola-texto');
    const btnCopiarPix = document.getElementById('btn-copiar-pix');
    const confirmationModal = document.getElementById('confirmation-modal');
    const monthNameEl = document.getElementById('month-name');
    const calendarDaysEl = document.getElementById('calendar-days');
    const timeSlotsContainer = document.getElementById('dynamic-slots-container');
    let currentDate = new Date();
    let expirationTimer = null;
    // --- 3. FUNÇÕES PRINCIPAIS ---

    // (MODIFICADO) Busca os dias de trabalho do barbeiro
    async function fetchDiasDeTrabalho(barbeiroId) {
        if (!barbeiroId) {
            state.diasDeTrabalho = null; // Reseta
            return;
        }
        try {
            //Chama o NOVO script (buscar-dias-barbeiro.php)
            const response = await fetch(`../php/Funcoes/buscar-dias-barbeiro.php?id_barbeiro=${barbeiroId}`);
            if (!response.ok) {
                throw new Error(`Falha ao buscar dias: ${response.statusText}`);
            }
            
            const dias = await response.json(); 
            
            if (dias.erro) throw new Error(dias.erro);

            // Converte para números (garantia)
            state.diasDeTrabalho = dias.map(d => parseInt(d, 10));
            
            if (state.diasDeTrabalho.length === 0) {
                 console.warn("Este barbeiro não tem horários cadastrados (lista de dias vazia).");
            }

        } catch (error) {
            console.error("Erro ao buscar dias de trabalho:", error);
            // Se falhar (ex: cliente não logado), desabilita
            state.diasDeTrabalho = []; 
        }
    }

    // (updateSummary não muda)
    function updateSummary() {
        summaryBarber.textContent = state.barbeiroNome ? state.barbeiroNome : 'Não selecionado';
        if (state.servicos.length > 0) {
            summaryServices.innerHTML = state.servicos.map(s => s.nome).join(', <br>');
        } else {
            summaryServices.textContent = 'Não selecionado';
        }
        state.totalPrice = state.servicos.reduce((acc, service) => acc + service.price, 0);
        state.totalDuration = state.servicos.reduce((acc, service) => acc + service.duration, 0);
        const priceToPay = state.paymentOption === 'half' ? state.totalPrice / 2 : state.totalPrice;
        summaryTotal.textContent = `R$${priceToPay.toFixed(2)}`;
        if (state.date && state.time) {
            const dataFormatada = state.date.split('-').reverse().join('/');
            summaryDatetime.textContent = `${dataFormatada} às ${state.time}`;
        } else if (state.date) {
            const dataFormatada = state.date.split('-').reverse().join('/');
            summaryDatetime.textContent = `${dataFormatada}`;
        } else {
            summaryDatetime.textContent = 'Não selecionado';
        }
        if (state.currentStep === 4) {
            paymentTotalFull.textContent = state.totalPrice.toFixed(2);
            paymentTotalHalf.textContent = (state.totalPrice / 2).toFixed(2);
        }
    }
    
    // (goToStep não muda)
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
        if (step < 4 && state.statusCheckInterval) {
            clearInterval(state.statusCheckInterval);
            state.statusCheckInterval = null;
        }
        if (step === 4) {
            nextBtn.textContent = 'Gerar PIX';
            nextBtn.disabled = true; 
            if(pixLoading) pixLoading.style.display = 'none';
            if(pixContainer) pixContainer.style.display = 'none';
        } else {
            nextBtn.textContent = 'Próximo Passo';
            nextBtn.disabled = false; 
        }
        updateSummary();
    }
    
    // (startPolling não muda)
    function startPolling(agendamentoId) {
        if (state.statusCheckInterval) {
            clearInterval(state.statusCheckInterval);
        }

        const executePoll = async () => {
            // Se o intervalo foi limpo (ex: usuário voltou), não faça nada.
            if (!state.statusCheckInterval) return; 

            try {
                const statusResponse = await fetch(`../php/Funcoes/verificar-status.php?id_agendamento=${agendamentoId}`);
                
                // Só processa se a resposta for OK
                if (statusResponse.ok) {
                    const statusData = await statusResponse.json();
               
                    if (statusData.status === 'confirmado') {
                        // SUCESSO: Limpa o intervalo e mostra o modal
                        clearInterval(state.statusCheckInterval); 
                        state.statusCheckInterval = null;
                        
                        if (expirationTimer) {
                        clearTimeout(expirationTimer);
                        expirationTimer = null;
                        }
                        
                        if(confirmationModal) confirmationModal.classList.add('show');
                        if(pixLoading) pixLoading.style.display = 'none';
                        if(pixContainer) pixContainer.style.display = 'none';
                        if(document.querySelector('.tabs')) document.querySelector('.tabs').style.display = 'none';
                        if(nextBtn) nextBtn.style.display = 'none'; 
                        
                        setTimeout(() => {
                            window.location.href = 'meus-agendamentos.php';
                        }, 4000);
                    }
                    // Se não for 'confirmado', não faz nada. O loop continua.
                } else {
                    // Servidor respondeu 404, 500, etc. Apenas loga, mas NÃO PARA o loop.
                    console.warn(`Polling check falhou com status: ${statusResponse.status}`);
                }

            } catch (err) {
                // ERRO DE REDE (ex: throttling, internet caiu)
                // APENAS LOGA O ERRO, MAS NÃO PARA O LOOP.
                // A próxima tentativa do setInterval (em 5s) vai tentar de novo.
                console.warn(`Polling check com erro de rede: ${err.message}`);
                
                // As linhas que dão 'clearInterval' foram REMOVIDAS daqui.
            }
        };

        // Roda a primeira vez imediatamente, e depois a cada 5 segundos
        executePoll(); 
        state.statusCheckInterval = setInterval(executePoll, 5000);
    }

    // --- 4. EVENT LISTENERS ---

    // Step 1: Barber Selection (MODIFICADO)
    document.querySelectorAll('.barber-card').forEach(card => {
        card.addEventListener('click', async () => { // Adicionado async
            document.querySelectorAll('.barber-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            state.barbeiroId = card.dataset.barbeiroId;
            state.barbeiroNome = card.dataset.barbeiroNome;
            updateSummary();
            
            // (MODIFICADO) Espera os dias serem buscados...
            await fetchDiasDeTrabalho(state.barbeiroId);
            // ...e SÓ ENTÃO redesenha o calendário
            generateCalendar(currentDate);
            
            state.date = null;
            state.time = null;
            timeSlotsContainer.innerHTML = '<p class="no-slots">Por favor, selecione um dia no calendário.</p>';
        });
    });

    // Step 2: Service Selection
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => {
            card.classList.toggle('selected');
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
            if (state.date) {
                fetchHorarios();
            }
        });
    });

    // Step 3: Calendar (MODIFICADO - Lógica de desabilitar)
    function generateCalendar(date) {
        calendarDaysEl.innerHTML = '';
        const year = date.getFullYear();
        const month = date.getMonth();
        const monthName = date.toLocaleString('pt-BR', { month: 'long' });
        monthNameEl.textContent = `${monthName.charAt(0).toUpperCase() + monthName.slice(1)} ${year}`;
        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();
        for (let i = 0; i < firstDay; i++) {
            calendarDaysEl.appendChild(document.createElement('div'));
        }
        const today = new Date();
        today.setHours(0, 0, 0, 0); 

        for (let i = 1; i <= lastDate; i++) {
            const dayEl = document.createElement('div');
            dayEl.textContent = i;

            const dateOfLoop = new Date(year, month, i); 
            const diaDaSemana = dateOfLoop.getDay(); // 0-6
            const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

            if (dateOfLoop.getTime() === today.getTime()) {
                dayEl.classList.add('today');
            }
            let isEnabled = true;

            // Regra 1: Desabilita dias passados
            if (dateOfLoop < today) {
                isEnabled = false;
            }

            // Regra 2: Se os dias de trabalho AINDA NÃO FORAM CARREGADOS (null),
            // desabilita (pois o usuário não selecionou um barbeiro).
            if (state.diasDeTrabalho === null) {
                isEnabled = false;
            } 
            // Regra 3: Se os dias FORAM carregados (é um array)
            else {
                // Desabilita se o barbeiro NÃO trabalha nesse dia
                // (Se a lista for [], `includes` sempre será false, desabilitando)
                if (!state.diasDeTrabalho.includes(diaDaSemana)) {
                    isEnabled = false;
                }
            }
            
            if (!isEnabled) {
                dayEl.classList.add('disabled');
            }
            
            if (state.date === fullDate) {
                dayEl.classList.add('selected');
            }

            dayEl.addEventListener('click', () => {
                if (dayEl.classList.contains('disabled')) return;
                document.querySelectorAll('.days div.selected').forEach(d => d.classList.remove('selected'));
                dayEl.classList.add('selected');
                state.date = fullDate; 
                state.time = null; 
                updateSummary();
                fetchHorarios(); 
            });
            calendarDaysEl.appendChild(dayEl);
        }
    }
    // (Navegação do calendário não muda)
    document.getElementById('prev-month').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        generateCalendar(currentDate);
    });
    document.getElementById('next-month').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        generateCalendar(currentDate);
    });


    // Step 3: Time (MODIFICADO - Leitura do Objeto)
    async function fetchHorarios() {
        if (!state.barbeiroId) {
            alert("Por favor, volte e selecione um barbeiro.");
            goToStep(1); return;
        }
        if (state.totalDuration === 0) {
            alert("Por favor, volte e selecione ao menos um serviço.");
            goToStep(2); return;
        }
        timeSlotsContainer.innerHTML = '<p class="loading">Buscando horários...</p>';
        state.time = null; 
        updateSummary(); 
        try {
            // (Não muda) Chama o script que retorna {time, available}
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
                let algumHorarioDisponivel = false;
                horarios.forEach(horario => { 
                    const slotButton = document.createElement('button');
                    slotButton.className = 'time-slot';
                    
                    // (CORREÇÃO para o bug [object Object])
                    slotButton.textContent = horario.time;
                    slotButton.dataset.time = horario.time;
                    
                    if (!horario.available) {
                        slotButton.classList.add('disabled');
                        slotButton.disabled = true;
                    } else {
                        algumHorarioDisponivel = true;
                    }
                    timeSlotsContainer.appendChild(slotButton);
                });
                if (!algumHorarioDisponivel) {
                    timeSlotsContainer.innerHTML = '<p class="no-slots">Todos os horários para este dia estão ocupados.</p>';
                }
            }
        } catch (error) {
            console.error("Erro ao buscar horários:", error);
            timeSlotsContainer.innerHTML = '<p class="no-slots" style="color: red;">Não foi possível carregar os horários.</p>';
        }
    }
    // (Listener de 'click' do slot não muda)
    timeSlotsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('time-slot') && !e.target.disabled) {
            document.querySelectorAll('.time-slot.selected').forEach(s => s.classList.remove('selected'));
            const slotButton = e.target;
            slotButton.classList.add('selected');
            state.time = slotButton.dataset.time;
            updateSummary();
        }
    });

    // Step 4: Payment Options
    paymentTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            paymentTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            state.paymentOption = tab.textContent.toLowerCase().includes('metade') ? 'half' : 'full';
            updateSummary();
            nextBtn.disabled = false; 
            nextBtn.textContent = 'Gerar PIX'; 
            if(pixContainer) pixContainer.style.display = 'none';
            if(pixLoading) pixLoading.style.display = 'none';
            
            // 1. Limpa todos os timers
            if (expirationTimer) {
                clearTimeout(expirationTimer);
                expirationTimer = null;
            }
            if (state.statusCheckInterval) {
                clearInterval(state.statusCheckInterval);
                state.statusCheckInterval = null;
            }

            // 2. Verifica se um agendamento JÁ FOI CRIADO (e o usuário mudou de ideia)
            if (state.idAgendamento) {
                
                // Sim, um PIX foi gerado. Vamos cancelar esse agendamento.
                const formData = new FormData();
                formData.append('id_agendamento', state.idAgendamento);

                // Chama o script de cancelamento
                fetch('../php/Funcoes/cancelar-agendamento-cliente.php', {
                    method: 'POST',
                    body: formData
                }).catch(err => console.error('Falha ao cancelar agendamento anterior:', err));
                
                // Reseta o estado para permitir um novo agendamento
                state.idAgendamento = null;
            }
        });
    });
    
   // --- Navigation ---
    nextBtn.addEventListener('click', async () => { 
        if (state.statusCheckInterval) return; 
        if (state.currentStep < 4) {
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
            goToStep(state.currentStep + 1);
        
        } else if (state.currentStep === 4) {


            nextBtn.disabled = true; 

            nextBtn.textContent = 'Gerando...';

            if(pixLoading) pixLoading.style.display = 'block';

            if(pixContainer) pixContainer.style.display = 'none';

            try {

                const response = await fetch('../php/Funcoes/criar-agendamento.php', {

                    method: 'POST',

                    headers: { 'Content-Type': 'application/json' },

                    body: JSON.stringify(state) 

                });

                if (!response.ok) throw new Error('Falha ao se comunicar com o servidor.');

                const data = await response.json();

                if (!data.sucesso) throw new Error(data.mensagem || 'Erro desconhecido no back-end.');

                

                state.idAgendamento = data.id_agendamento; 

                

                const dadosPagamento = {

                    id_pagamento: data.id_pagamento,

                    valor_a_pagar: data.valor_a_pagar

                };

                

                const pixResponse = await fetch('../php/Funcoes/gerar-pix-mp.php', {

                    method: 'POST',

                    headers: { 'Content-Type': 'application/json' },

                    body: JSON.stringify(dadosPagamento)

                });

                

                const pixData = await pixResponse.json();

                if (!pixResponse.ok || !pixData.sucesso) {

                    throw new Error(pixData.mensagem || "Não foi possível gerar o PIX.");

                }



                if(pixLoading) pixLoading.style.display = 'none';

                if(pixContainer) pixContainer.style.display = 'block';

                if(qrCodeImg) qrCodeImg.src = "data:image/png;base64," + pixData.qr_code_base64;

                if(copiaColaTexto) copiaColaTexto.textContent = pixData.qr_code_copy_paste;

                if(btnCopiarPix) btnCopiarPix.onclick = () => {

                    navigator.clipboard.writeText(pixData.qr_code_copy_paste);

                    alert('Código PIX copiado!');

                };

                // ---1: INICIAR O TIMER DE 30 MIN ---
                if (expirationTimer) clearTimeout(expirationTimer);

                expirationTimer = setTimeout(() => {
                    // Se o timer estourar, mostra o modal de expiração
                    document.getElementById('expiration-modal').classList.add('show');
                    // Esconde o QR Code
                    if(pixContainer) pixContainer.style.display = 'none';
                    // Para o polling de verificação
                    if (state.statusCheckInterval) {
                        clearInterval(state.statusCheckInterval);
                        state.statusCheckInterval = null;
                    }
                    // Faz o botão de recarregar a página funcionar
                    document.getElementById('btn-reload-page').onclick = () => {
                        location.reload();
                    };
                }, 1800000); // 30 minutos (30 * 60 * 1000)

                startPolling(data.id_agendamento);

                nextBtn.textContent = 'Aguardando Pagamento'; 



            } catch (error) {

                console.error('Erro ao criar agendamento/pix:', error);

                alert('Erro: ' + error.message);

                nextBtn.disabled = false; 

                nextBtn.textContent = 'Gerar PIX';

                if(pixLoading) pixLoading.style.display = 'none';

            }

        }
    });

    backBtn.addEventListener('click', () => {
        if (state.currentStep > 1) {
            
            // 1. Limpa todos os timers, não importa o que aconteça
            if (expirationTimer) {
                clearTimeout(expirationTimer);
                expirationTimer = null;
            }
            if (state.statusCheckInterval) {
                clearInterval(state.statusCheckInterval);
                state.statusCheckInterval = null;
            }

            // 2. Verifica se está voltando da ETAPA 4 e se um agendamento JÁ FOI CRIADO
            if (state.currentStep === 4 && state.idAgendamento) {
                
                // Sim, um PIX foi gerado (state.idAgendamento existe). 
                // Vamos cancelar esse agendamento silenciosamente em segundo plano.
                
                const formData = new FormData();
                formData.append('id_agendamento', state.idAgendamento);

                // Usamos o fetch() "fire-and-forget". Não precisamos esperar a resposta.
                // Isso chama o mesmo script que o botão "Cancelar" usa.
                fetch('../php/Funcoes/cancelar-agendamento-cliente.php', {
                    method: 'POST',
                    body: formData
                }).catch(err => console.error('Falha ao cancelar agendamento anterior:', err));
                
                // Reseta o estado para permitir um novo agendamento
                state.idAgendamento = null;
            }

            // 3. Finalmente, volta para a etapa anterior
            goToStep(state.currentStep - 1);
        }
    });
    
    // --- Initial setup (CORRIGIDO) ---
    
    // 1. Gera o calendário inicial (desabilitado, pois state.diasDeTrabalho = null)
    generateCalendar(currentDate); 
    updateSummary();
    goToStep(1);
    
    // 2. Simula o clique no primeiro barbeiro (se ele existir)
    const defaultBarber = document.querySelector('.barber-card');
    if(defaultBarber) {
        // 3. O .click() vai carregar os dias de trabalho e redesenhar o calendário
        defaultBarber.click();
    }
});