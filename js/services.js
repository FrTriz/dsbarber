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

    // --- Modal Functions --- //
    const openServiceModal = (isEdit = false, card = null) => {
        serviceForm.reset();
        if (isEdit && card) {
            modalTitle.textContent = 'Editar Serviço';
            const service = getServiceById(card.dataset.id);
            if(service) {
                serviceIdInput.value = service.id;
                serviceNameInput.value = service.name;
                serviceDescriptionInput.value = service.description;
                servicePriceInput.value = service.price;
                serviceDurationInput.value = service.duration;
            }
        } else {
            modalTitle.textContent = 'Adicionar Novo Serviço';
            serviceIdInput.value = '';
        }
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

    if(confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', () => {
        if(itemIdToDelete) {
            initialServices = initialServices.filter(s => s.id !== itemIdToDelete);
            const cardToRemove = servicesContainer.querySelector(`[data-id='${itemIdToDelete}']`);
            if(cardToRemove) cardToRemove.remove();
            closeDeleteModal();
        }
    });

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

    // Form Submission
    if(serviceForm) {
        serviceForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const serviceData = {
                id: serviceIdInput.value || Date.now().toString(),
                name: serviceNameInput.value,
                description: serviceDescriptionInput.value,
                price: parseFloat(servicePriceInput.value),
                duration: parseInt(serviceDurationInput.value, 10)
            };

            if (serviceIdInput.value) {
                initialServices = initialServices.map(s => s.id === serviceData.id ? serviceData : s);
                const cardToUpdate = servicesContainer.querySelector(`[data-id='${serviceData.id}']`);
                if (cardToUpdate) updateCard(cardToUpdate, serviceData);
            } else {
                initialServices.push(serviceData);
                const newCard = createServiceCard(serviceData);
                servicesContainer.appendChild(newCard);
            }
            closeServiceModal();
        });
    }

    // --- Data and Helper Functions --- //
    let initialServices = [
        { id: '1', name: 'Corte de Cabelo', description: 'Estilo personalizado com as últimas tendências.', price: 50, duration: 45 },
        { id: '2', name: 'Barba Tradicional', description: 'Modelagem de barba com toalha quente e navalha.', price: 35, duration: 30 },
        { id: '3', name: 'Penteado', description: 'Finalização com produtos de alta qualidade.', price: 25, duration: 20 },
        { id: '4', name: 'Coloração Masculina', description: 'Cobertura de grisalhos ou mudança de cor.', price: 80, duration: 60 },
    ];

    const getServiceById = (id) => initialServices.find(s => s.id === id);

    const createServiceCard = (service) => {
        const card = document.createElement('div');
        card.className = 'service-card';
        card.dataset.id = service.id;
        updateCard(card, service);
        return card;
    };

    const updateCard = (card, service) => {
        card.innerHTML = `
            <div class="service-card-header">
                <h3>${service.name}</h3>
                <p>${service.description}</p>
            </div>
            <div class="service-card-footer">
                <span class="price">R$${service.price.toFixed(2)}</span>
                <span class="duration"><i class="fas fa-clock"></i> ${service.duration} min</span>
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
        initialServices.forEach(service => {
            const card = createServiceCard(service);
            servicesContainer.appendChild(card);
        });
    };

    // Initial Render
    renderServices();
});