document.addEventListener('DOMContentLoaded', () => {
    const listaAgendamentosContainer = document.getElementById('lista-agendamentos');

    // --- (NOVO) LÓGICA DO MODAL DE PAGAMENTO ---
    const pixModal = document.getElementById('pix-modal');
    const closePixModalBtn = document.getElementById('close-pix-modal-btn');
    
    // Seletores dos 3 estados do modal
    const paymentChoiceModal = document.getElementById('payment-choice-modal');
    const pixLoadingModal = document.getElementById('pix-loading-modal');
    const pixContainerModal = document.getElementById('pix-container-modal');
    
    // Seletores dos botões de escolha
    const paymentTotalHalfModal = document.getElementById('payment-total-half-modal');
    const paymentTotalFullModal = document.getElementById('payment-total-full-modal');
    const modalTabs = pixModal.querySelectorAll('.tab-btn');
    const btnGerarPixModal = document.getElementById('btn-gerar-pix-modal');

    // Seletores do PIX
    const qrCodeImgModal = document.getElementById('pix-qr-code-img-modal');
    const copiaColaTextoModal = document.getElementById('pix-copia-cola-texto-modal');
    const btnCopiarPixModal = document.getElementById('btn-copiar-pix-modal');

    // (NOVO) Armazena os dados do agendamento que está sendo pago
    let modalState = {
        idPagamento: null,
        valorPendente: 0,
        valorTotal: 0,
        paymentOption: 'half' // Começa selecionando 'metade'
    };

    /**
     * Traduz o status do banco (PT) para a classe CSS (EN)
     */
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
            case 'concluido':
                return 'concluido'; 
            default:
                return statusLower;
        }
    }

    /**
     * Cria o HTML para um card de agendamento (MODIFICADO)
     */
    function criarCardAgendamento(apt) {
        const card = document.createElement('div');
        card.className = 'agendamento-card';
        card.id = `agendamento-${apt.id_agendamento}`; 

        // 1. Formatar dados
        const statusClasse = traduzirStatusParaClasse(apt.status_agendamento);
        const statusTexto = apt.status_agendamento.charAt(0).toUpperCase() + apt.status_agendamento.slice(1);
        
        // (CORREÇÃO) Usa 'valor_pendente' vindo do PHP
        const precoFormatado = parseFloat(apt.valor_pendente).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        // 2. Lógica de Ação (Botões) - MODIFICADO
        let acaoButtonsHtml = '';
        if (statusClasse === 'pending') {
            // (CORREÇÃO) Adiciona 'data-valor-total' e 'data-valor-pendente'
            acaoButtonsHtml = `
                <div class="acao-buttons">
                    <a href="#" class="btn-pagar" 
                       data-id-pagamento="${apt.id_pagamento}" 
                       data-valor-pendente="${apt.valor_pendente}"
                       data-valor-total="${apt.valor_total}">
                       Pagar Agora
                    </a>
                    <button class="btn-cancelar" data-id="${apt.id_agendamento}">Cancelar</button>
                </div>
            `;
        } else {
            acaoButtonsHtml = '<div class="acao-buttons">&nbsp;</div>';
        }

        // 3. Montar o HTML
        card.innerHTML = `
            <div class="agendamento-info">
                <h3>Agendamento #${apt.id_agendamento}</h3>
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>${apt.data_fmt} às ${apt.hora_fmt}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-concierge-bell"></i>
                    <span>${apt.servicos_agendados}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span>${precoFormatado}</span>
                </div>
            </div>
            
            <div class="agendamento-acao">
                <span class="status-badge ${statusClasse}">${statusTexto}</span>
                ${acaoButtonsHtml}
            </div>
        `;
        return card;
    }

    /**
     * Busca os agendamentos no back-end
     */
    async function carregarAgendamentos() {
        // ... (Esta função continua igual à que você já tinha)
        if (!listaAgendamentosContainer) return;
        
        listaAgendamentosContainer.innerHTML = '<p class="loading-message">Carregando seus agendamentos...</p>';

        try {
            const response = await fetch('../php/Funcoes/buscar-meus-agendamentos.php');
            if (!response.ok) throw new Error('Falha ao conectar com o servidor.');

            const data = await response.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao buscar dados.');

            listaAgendamentosContainer.innerHTML = ''; 

            if (data.agendamentos.length === 0) {
                listaAgendamentosContainer.innerHTML = '<p class="loading-message">Você ainda não possui agendamentos.</p>';
                return;
            }

            data.agendamentos.forEach(apt => {
                const card = criarCardAgendamento(apt);
                listaAgendamentosContainer.appendChild(card);
            });

        } catch (error) {
            console.error('Erro ao carregar agendamentos:', error);
            listaAgendamentosContainer.innerHTML = `<p class="error-message">Não foi possível carregar seus agendamentos: ${error.message}</p>`;
        }
    }

    /**
     * Lida com o clique no botão de cancelar
     */
    async function handleCancelamento(idAgendamento, buttonElement) {
        // ... (Esta função continua igual à que você já tinha)
        if (!confirm("Tem certeza que deseja cancelar este agendamento?")) {
            return;
        }

        buttonElement.disabled = true;
        buttonElement.textContent = 'Cancelando...';

        try {
            const formData = new FormData();
            formData.append('id_agendamento', idAgendamento);

            const response = await fetch('../php/Funcoes/cancelar-agendamento-cliente.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.sucesso) {
                throw new Error(data.mensagem || 'Erro desconhecido no servidor.');
            }
            
            const card = document.getElementById(`agendamento-${idAgendamento}`);
            if (card) {
                const badge = card.querySelector('.status-badge');
                const acaoContainer = card.querySelector('.acao-buttons');

                badge.textContent = 'Cancelado';
                badge.className = 'status-badge canceled';

                if (acaoContainer) {
                    acaoContainer.innerHTML = '&nbsp;';
                }
            }
            
            alert(data.mensagem);

        } catch (error) {
            console.error('Erro ao cancelar:', error);
            alert('Falha ao cancelar o agendamento: ' + error.message);
            buttonElement.disabled = false;
            buttonElement.textContent = 'Cancelar';
        }
    }


    // --- (MODIFICADO) LÓGICA DO MODAL ---

    // Abre a "caixa" do modal
    const openPixModal = () => pixModal.classList.add('show');
    
    // Fecha a "caixa" do modal
    const closePixModal = () => pixModal.classList.remove('show');
    
    // (NOVO) Mostra a tela de escolha de pagamento
    function showPaymentChoice(idPagamento, valorPendente, valorTotal) {
        // 1. Armazena os dados no estado do modal
        modalState.idPagamento = idPagamento;
        modalState.valorPendente = parseFloat(valorPendente);
        modalState.valorTotal = parseFloat(valorTotal);

        // 2. Atualiza os textos dos botões
        paymentTotalHalfModal.textContent = modalState.valorPendente.toFixed(2);
        paymentTotalFullModal.textContent = modalState.valorTotal.toFixed(2);

        // 3. === A NOVA LÓGICA ESTÁ AQUI ===
        // Verifica se a escolha original foi 'metade'
        const escolheuMetade = modalState.valorPendente < modalState.valorTotal;

        if (escolheuMetade) {
            modalState.paymentOption = 'half';
        } else {
            modalState.paymentOption = 'full';
        }

        // 4. Reseta os tabs (deixa o correto como 'ativo')
        modalTabs.forEach(tab => {
            const option = tab.dataset.payOption; // 'half' ou 'full'
            if (option === modalState.paymentOption) {
                tab.classList.add('active'); // Ativa o default
            } else {
                tab.classList.remove('active');
            }
        });
        // === FIM DA NOVA LÓGICA ===

        // 5. Controla a visibilidade (mostra escolha, esconde os outros)
        paymentChoiceModal.style.display = 'block';
        pixLoadingModal.style.display = 'none';
        pixContainerModal.style.display = 'none';

        // 6. Abre o modal
        openPixModal();
    }

    // (NOVO) Função que gera o PIX (antiga handleGerarPix)
    async function executePixGeneration() {
        // 1. Mostra o loading e esconde a escolha
        paymentChoiceModal.style.display = 'none';
        pixLoadingModal.style.display = 'block';
        pixLoadingModal.innerHTML = '<p class="loading">Gerando seu PIX, aguarde...</p>';

        // 2. Decide o valor final baseado na escolha
        const valorFinal = modalState.paymentOption === 'full' 
            ? modalState.valorTotal 
            : modalState.valorPendente;

        try {
            const dadosPagamento = {
                id_pagamento: modalState.idPagamento,
                valor_a_pagar: valorFinal
            };

            // 3. Chama o back-end para gerar o PIX
            const response = await fetch('../php/Funcoes/gerar-pix-mp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosPagamento)
            });

            const pixData = await response.json();
            if (!response.ok || !pixData.sucesso) {
                throw new Error(pixData.mensagem || "Não foi possível gerar o PIX.");
            }

            // 4. SUCESSO! Exibe o PIX
            qrCodeImgModal.src = "data:image/png;base64," + pixData.qr_code_base64;
            copiaColaTextoModal.textContent = pixData.qr_code_copy_paste;
            
            btnCopiarPixModal.onclick = () => {
                navigator.clipboard.writeText(pixData.qr_code_copy_paste);
                alert('Código PIX copiado!');
            };

            // 5. Troca a view de "Carregando" para "PIX"
            pixLoadingModal.style.display = 'none';
            pixContainerModal.style.display = 'block';

        } catch (error) {
            console.error('Erro ao gerar PIX:', error);
            pixLoadingModal.innerHTML = `<p class="error-message">Falha ao gerar PIX: ${error.message}</p>`;
            // (Opcional) Voltar para a tela de escolha
            // paymentChoiceModal.style.display = 'block'; 
        }
    }

    // --- (MODIFICADO) Listeners de Eventos ---

    // Fechar o modal
    if(closePixModalBtn) closePixModalBtn.addEventListener('click', closePixModal);
    if(pixModal) pixModal.addEventListener('click', (e) => {
        if (e.target === pixModal) closePixModal();
    });

    // (NOVO) Listener para os tabs (Metade/Total) dentro do modal
    modalTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            modalTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            modalState.paymentOption = tab.dataset.payOption; // 'half' ou 'full'
        });
    });

    // (NOVO) Listener para o botão "Gerar PIX" de dentro do modal
    if (btnGerarPixModal) {
        btnGerarPixModal.addEventListener('click', executePixGeneration);
    }

    // (MODIFICADO) Listener de eventos principal (Cards)
    listaAgendamentosContainer.addEventListener('click', (e) => {
        const payButton = e.target.closest('.btn-pagar');
        const cancelButton = e.target.closest('.btn-cancelar');

        if (payButton) {
            e.preventDefault();
            // Pega os dados do botão
            const idPagamento = payButton.dataset.idPagamento;
            const valorPendente = payButton.dataset.valorPendente;
            const valorTotal = payButton.dataset.valorTotal;
            
            // (MUDOU) Em vez de gerar o PIX, mostra a tela de escolha
            showPaymentChoice(idPagamento, valorPendente, valorTotal);
        }
        
        if (cancelButton) {
            e.preventDefault();
            const id = cancelButton.dataset.id;
            handleCancelamento(id, cancelButton);
        }
    });

    // Carga Inicial
    carregarAgendamentos();
});