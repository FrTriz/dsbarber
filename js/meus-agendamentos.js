document.addEventListener('DOMContentLoaded', () => {
    const listaAgendamentosContainer = document.getElementById('lista-agendamentos');

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
        card.id = `agendamento-${apt.id_agendamento}`; // ID único para o card

        // 1. Formatar dados
        const statusClasse = traduzirStatusParaClasse(apt.status_agendamento);
        const statusTexto = apt.status_agendamento.charAt(0).toUpperCase() + apt.status_agendamento.slice(1);
        
        // USA O valor_a_pagar (que pode ser metade)
        const precoFormatado = parseFloat(apt.valor_a_pagar).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        // 2. Lógica de Ação (Botões) - MODIFICADO
        let acaoButtonsHtml = '';
        if (statusClasse === 'pending') {
            // Adiciona data-attributes ao botão Pagar
            acaoButtonsHtml = `
                <div class="acao-buttons">
                    <a href="#" class="btn-pagar" 
                       data-id-pagamento="${apt.id_pagamento}" 
                       data-valor="${apt.valor_a_pagar}">
                       Pagar Agora
                    </a>
                    <button class="btn-cancelar" data-id="${apt.id_agendamento}">Cancelar</button>
                </div>
            `;
        } else {
            // Deixa o espaço vazio para manter o alinhamento
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
        if (!listaAgendamentosContainer) return;
        
        listaAgendamentosContainer.innerHTML = '<p class="loading-message">Carregando seus agendamentos...</p>';

        try {
            const response = await fetch('../php/Funcoes/buscar-meus-agendamentos.php');
            if (!response.ok) throw new Error('Falha ao conectar com o servidor.');

            const data = await response.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao buscar dados.');

            listaAgendamentosContainer.innerHTML = ''; // Limpa o "carregando"

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
        // Pede confirmação ao usuário
        if (!confirm("Tem certeza que deseja cancelar este agendamento?")) {
            return;
        }

        buttonElement.disabled = true;
        buttonElement.textContent = 'Cancelando...';

        try {
            const formData = new FormData();
            formData.append('id_agendamento', idAgendamento);

            // USA O SCRIPT DE CANCELAMENTO QUE VOCÊ JÁ TINHA
            const response = await fetch('../php/Funcoes/cancelar-agendamento-cliente.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.sucesso) {
                throw new Error(data.mensagem || 'Erro desconhecido no servidor.');
            }

            // Sucesso! Atualizar a UI do card
            const card = document.getElementById(`agendamento-${idAgendamento}`);
            if (card) {
                const badge = card.querySelector('.status-badge');
                const acaoContainer = card.querySelector('.acao-buttons');

                // Atualiza o badge
                badge.textContent = 'Cancelado';
                badge.className = 'status-badge canceled';

                // Remove os botões
                if (acaoContainer) {
                    acaoContainer.innerHTML = '&nbsp;';
                }
            }
            
            alert(data.mensagem); // Mostra "Agendamento cancelado com sucesso!"

        } catch (error) {
            console.error('Erro ao cancelar:', error);
            alert('Falha ao cancelar o agendamento: ' + error.message);
            buttonElement.disabled = false;
            buttonElement.textContent = 'Cancelar';
        }
    }

    // --- (NOVO) LÓGICA DO MODAL DE PAGAMENTO ---
    const pixModal = document.getElementById('pix-modal');
    const closePixModalBtn = document.getElementById('close-pix-modal-btn');
    const pixLoadingModal = document.getElementById('pix-loading-modal');
    const pixContainerModal = document.getElementById('pix-container-modal');
    const qrCodeImgModal = document.getElementById('pix-qr-code-img-modal');
    const copiaColaTextoModal = document.getElementById('pix-copia-cola-texto-modal');
    const btnCopiarPixModal = document.getElementById('btn-copiar-pix-modal');

    const openPixModal = () => {
        // Reseta o modal para o estado "carregando"
        pixLoadingModal.style.display = 'block';
        pixContainerModal.style.display = 'none';
        pixLoadingModal.innerHTML = '<p class="loading">Gerando seu PIX, aguarde...</p>'; // Garante que não mostre erro
        pixModal.classList.add('show');
    };
    const closePixModal = () => pixModal.classList.remove('show');

    // Fechar o modal
    if(closePixModalBtn) closePixModalBtn.addEventListener('click', closePixModal);
    if(pixModal) pixModal.addEventListener('click', (e) => {
        if (e.target === pixModal) closePixModal();
    });

    /**
     * (NOVO) Chama o gerar-pix-mp.php e preenche o modal
     */
    async function handleGerarPix(idPagamento, valorPagar) {
        openPixModal();

        try {
            const dadosPagamento = {
                id_pagamento: idPagamento,
                valor_a_pagar: parseFloat(valorPagar)
            };

            // USA O MESMO SCRIPT 'gerar-pix-mp.php' DA PÁGINA DE AGENDAMENTO
            const response = await fetch('../php/Funcoes/gerar-pix-mp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosPagamento)
            });

            const pixData = await response.json();
            if (!response.ok || !pixData.sucesso) {
                throw new Error(pixData.mensagem || "Não foi possível gerar o PIX.");
            }

            // SUCESSO! Exibe o PIX
            qrCodeImgModal.src = "data:image/png;base64," + pixData.qr_code_base64;
            copiaColaTextoModal.textContent = pixData.qr_code_copy_paste;
            
            btnCopiarPixModal.onclick = () => { // Adiciona evento ao botão copiar
                navigator.clipboard.writeText(pixData.qr_code_copy_paste);
                alert('Código PIX copiado!');
            };

            // Troca a view de "Carregando" para "PIX"
            pixLoadingModal.style.display = 'none';
            pixContainerModal.style.display = 'block';

        } catch (error) {
            console.error('Erro ao gerar PIX:', error);
            pixLoadingModal.innerHTML = `<p class="error-message">Falha ao gerar PIX: ${error.message}</p>`;
        }
    }

    // Listener de eventos (modificado para incluir o .btn-pagar)
    listaAgendamentosContainer.addEventListener('click', (e) => {
        const payButton = e.target.closest('.btn-pagar');
        const cancelButton = e.target.closest('.btn-cancelar');

        if (payButton) {
            e.preventDefault();
            const idPagamento = payButton.dataset.idPagamento;
            const valor = payButton.dataset.valor;
            handleGerarPix(idPagamento, valor); // Chama a nova função
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