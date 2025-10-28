document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar and Overlay Functionality --- //
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');

    const toggleSidebar = () => {
        if(sidebar) sidebar.classList.toggle('show');
        if(overlay) overlay.classList.toggle('show');
    };

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);
    if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // --- Modal and CRUD Functionality --- //

    // Edit/Add Modal Elements
    const serviceModal = document.getElementById('service-modal');
    const addServiceBtn = document.getElementById('add-service-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const modalTitle = document.getElementById('modal-title');
    const serviceForm = document.getElementById('service-form');
    const serviceIdInput = document.getElementById('service-id');
    const serviceNameInput = document.getElementById('service-name');
    const serviceDescriptionInput = document.getElementById('service-description');
    const servicePriceInput = document.getElementById('service-price');
    const serviceDurationInput = document.getElementById('service-duration');
    const servicesContainer = document.querySelector('.services-cards-section');

    // Delete Confirmation Modal Elements
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const closeConfirmModalBtn = document.getElementById('close-confirm-modal-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const itemNameToDelete = document.getElementById('item-name-to-delete');
    let itemIdToDelete = null;

    // --- Data and Helper Functions (CORRIGIDO PARA LER DO BANCO) --- //
    let initialServices = servicosVindosDoBanco;

    const getServiceById = (id) => {
        // CORREÇÃO: Compara com 'id_servico' e usa '==' (para comparar string com número)
        return initialServices.find(s => s.id_servico == id);
    };

    const createServiceCard = (service) => {
        const card = document.createElement('div');
        card.className = 'service-card';
        // CORREÇÃO: Usa 'id_servico'
        card.dataset.id = service.id_servico; 
        updateCard(card, service);
        return card;
    };

    const updateCard = (card, service) => {
        // CORREÇÃO: Todos os nomes de propriedades atualizados para bater com o banco
        card.innerHTML = `
            <div class="service-card-header">
                <h3>${service.nome}</h3>
                <p>${service.descricao}</p>
            </div>
            <div class="service-card-footer">
                <span class="price">R$${parseFloat(service.preco).toFixed(2)}</span>
                
                <span class="duration"><i class="fas fa-clock"></i> ${service.duracao_minutos} min</span>
                
                <div class="card-actions">
                    <button class="edit-btn"><i class="fas fa-pencil-alt"></i></button>
                    <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        `;
    };

    const renderServices = () => {
        if(!servicesContainer) return;
        servicesContainer.innerHTML = '';
        if (initialServices) { // Garante que initialServices não seja nulo
            initialServices.forEach(service => {
                const card = createServiceCard(service);
                servicesContainer.appendChild(card);
            });
        }
    };

   // --- Modal Functions --- //
    const openServiceModal = (isEdit = false, card = null) => {
        serviceForm.reset(); // [cite: 16]
        
        if (isEdit && card) {
            // MODO DE EDIÇÃO
            modalTitle.textContent = 'Editar Serviço'; // [cite: 18]
            // Define o 'action' do formulário para o script de ATUALIZAR
            serviceForm.setAttribute('action', '../php/Funcoes/atualizar-servico.php'); // [cite: 18]

            const service = getServiceById(card.dataset.id); // [cite: 19]
            if(service) {
                // CORREÇÃO: Usar os nomes das colunas do banco
                serviceIdInput.value = service.id_servico;
                serviceNameInput.value = service.nome;
                serviceDescriptionInput.value = service.descricao;
                servicePriceInput.value = service.preco;
                serviceDurationInput.value = service.duracao_minutos;
            }
        } else {
            // MODO DE ADIÇÃO
            modalTitle.textContent = 'Adicionar Novo Serviço'; // [cite: 22]
            // Define o 'action' do formulário para o script de ADICIONAR
            serviceForm.setAttribute('action', '../php/Funcoes/add-servico.php'); // [cite: 22]
            serviceIdInput.value = ''; // 
        }
        
        // CORREÇÃO: Esta linha foi movida para FORA do 'else'
        // Agora o modal vai abrir tanto para "Adicionar" quanto para "Editar"
        if(serviceModal) serviceModal.classList.add('show');
    };

    const closeServiceModal = () => {
        if(serviceModal) serviceModal.classList.remove('show');
    };

    const openDeleteModal = (card) => {
        itemIdToDelete = card.dataset.id;
        const serviceName = card.querySelector('h3').textContent;
        itemNameToDelete.textContent = `"${serviceName}"`;
        if(deleteConfirmModal) deleteConfirmModal.classList.add('show');
    };

    const closeDeleteModal = () => {
        itemIdToDelete = null;
        if(deleteConfirmModal) deleteConfirmModal.classList.remove('show');
    };

    // --- Event Listeners --- //
    if(addServiceBtn) addServiceBtn.addEventListener('click', () => openServiceModal(false));
    if(closeModalBtn) closeModalBtn.addEventListener('click', closeServiceModal);
    if(serviceModal) serviceModal.addEventListener('click', (e) => {
        if (e.target === serviceModal) closeServiceModal();
    });
    
    // Delete Modal Listeners
    if(closeConfirmModalBtn) closeConfirmModalBtn.addEventListener('click', closeDeleteModal);
    if(cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    if(deleteConfirmModal) deleteConfirmModal.addEventListener('click', (e) => {
        if (e.target === deleteConfirmModal) closeDeleteModal();
    });

    // Event Listener do Botão "Confirmar Exclusão" (CORRIGIDO)
    if(confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => { // <-- 1. Tornar async
            if(itemIdToDelete) {

                // 2. Preparar os dados para enviar ao PHP
                const formData = new FormData();
                formData.append('id_servico', itemIdToDelete); // O PHP espera 'id_servico'

                try {
                    // 3. Chamar o script PHP (use o caminho correto)
                    const response = await fetch('../php/Funcoes/excluir-servico.php', {
                        method: 'POST',
                        body: formData
                    });

                    // 4. Ler a resposta JSON
                    const data = await response.json();

                    if (!response.ok || !data.sucesso) {
                        throw new Error(data.mensagem || "Erro ao excluir no servidor.");
                    }

                    // 5. SUCESSO! O PHP excluiu. Agora atualizamos o front-end.
                    
                    // CORREÇÃO: Usar 'id_servico' para filtrar
                    initialServices = initialServices.filter(s => s.id_servico != itemIdToDelete);
                    
                    const cardToRemove = servicesContainer.querySelector(`[data-id='${itemIdToDelete}']`);
                    if(cardToRemove) cardToRemove.remove();
                    
                    closeDeleteModal();

                } catch (error) {
                    // 6. Se o fetch ou o PHP derem erro
                    console.error("Erro ao excluir serviço:", error);
                    alert("Falha ao excluir o serviço: " + error.message);
                }
            }
        });
    }

    // Event delegation for Edit and Delete
    if(servicesContainer) {
        servicesContainer.addEventListener('click', (e) => {
            const target = e.target.closest('button');
            if (!target) return;
            const card = target.closest('.service-card');

            if (target.classList.contains('edit-btn')) {
                openServiceModal(true, card);
            }
            if (target.classList.contains('delete-btn')) {
                openDeleteModal(card);
            }
        });
    }

    // Form Submission (CORRIGIDO PARA ADICIONAR E ATUALIZAR)
   if (serviceForm) {
        serviceForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // 1. Impedir o recarregamento da página 

            // 2. Pega a URL correta (add ou update) que definimos no openServiceModal
            const url = serviceForm.getAttribute('action');
            const formData = new FormData(serviceForm);

            // Pega o ID (se existir) para sabermos o que fazer no front-end
            const idSendoEditado = serviceIdInput.value;

            try {
                // 3. Enviar os dados para o PHP
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                // 4. LER A RESPOSTA COMO JSON (pois o PHP envia JSON)
                const data = await response.json(); 

                // 5. Checar se o PHP retornou sucesso
                if (!response.ok || !data.sucesso) {
                    throw new Error(data.mensagem || "Erro desconhecido do servidor.");
                }

                // 6. SUCESSO! O PHP salvou.
                
                // Pegamos os dados do formulário para atualizar a tela
                const serviceData = {
                    id_servico: idSendoEditado || Date.now().toString(), // Reusa o ID se for edição
                    nome: serviceNameInput.value,
                    descricao: serviceDescriptionInput.value,
                    preco: parseFloat(servicePriceInput.value),
                    duracao_minutos: parseInt(serviceDurationInput.value, 10)
                };
                
                if (idSendoEditado) { 
                    // MODO DE EDIÇÃO: Atualiza o card existente
                    const cardToUpdate = servicesContainer.querySelector(`[data-id='${idSendoEditado}']`); 
                    if (cardToUpdate) {
                        updateCard(cardToUpdate, serviceData);
                    }
                    
                    // Atualiza também o array 'initialServices'
                    const index = initialServices.findIndex(s => s.id_servico == idSendoEditado);
                    if(index > -1) {
                        initialServices[index] = serviceData;
                    }
                } 
                else {
                    // MODO DE ADIÇÃO: Adiciona um novo card
                    const newCard = createServiceCard(serviceData);
                    servicesContainer.appendChild(newCard);
                    initialServices.push(serviceData); // Adiciona no array local
                }

                closeServiceModal();

            } catch (error) {
                // 7. Se o 'fetch' ou o PHP derem erro
                console.error("Erro ao salvar serviço (no catch):", error);
                alert("Falha ao salvar o serviço: " + error.message);
            }
        });
    }

    // Initial Render
    renderServices();
});