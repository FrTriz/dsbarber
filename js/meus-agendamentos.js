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
        const precoFormatado = parseFloat(apt.valor_total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        // 2. Lógica de Ação (Botões)
        let acaoButtonsHtml = '';
        if (statusClasse === 'pending') {
            // Adiciona ambos os botões "Pagar" e "Cancelar"
            acaoButtonsHtml = `
                <div class="acao-buttons">
                    <a href="#" class="btn-pagar" data-id="${apt.id_agendamento}">Pagar Agora</a>
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
     * (NOVO) Lida com o clique no botão de cancelar
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

    // Listener de eventos (modificado para incluir o .btn-cancelar)
    listaAgendamentosContainer.addEventListener('click', (e) => {
        const payButton = e.target.closest('.btn-pagar');
        const cancelButton = e.target.closest('.btn-cancelar');

        if (payButton) {
            e.preventDefault();
            const id = payButton.dataset.id;
            alert(`Implementação futura: Abrir modal de pagamento para o agendamento #${id}`);
            // Aqui você chamaria a lógica para gerar o PIX/Pagamento
        }
        
        if (cancelButton) {
            e.preventDefault();
            const id = cancelButton.dataset.id;
            handleCancelamento(id, cancelButton); // Chama a nova função
        }
    });

    // Carga Inicial
    carregarAgendamentos();
});