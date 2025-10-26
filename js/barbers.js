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
    const barberModal = document.getElementById('barber-modal');
    const addBarberBtn = document.getElementById('add-barber-btn');
    const closeBarberModalBtn = barberModal.querySelector('.close-modal-btn');
    const modalTitle = document.getElementById('modal-title');
    const barberForm = document.getElementById('barber-form');
    const barberIdInput = document.getElementById('barber-id');
    const barberNameInput = document.getElementById('barber-name');
    const barberSpecialtiesInput = document.getElementById('barber-specialties');
    const barberStatusInput = document.getElementById('barber-status');
    const barberPhotoInput = document.getElementById('barber-photo');
    const barbersContainer = document.querySelector('.barbers-cards-section');

    // Delete Confirmation Modal Elements
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const closeConfirmModalBtn = document.getElementById('close-confirm-modal-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const barberNameToDelete = document.getElementById('barber-name-to-delete');
    let barberIdToDelete = null;

    // --- Modal Functions --- //
    const openBarberModal = (isEdit = false, card = null) => {
        barberForm.reset();
        if (isEdit && card) {
            modalTitle.textContent = 'Editar Barbeiro';
            const barber = getBarberById(card.dataset.id);
            if(barber) {
                barberIdInput.value = barber.id;
                barberNameInput.value = barber.name;
                barberSpecialtiesInput.value = barber.specialties.join(', ');
                barberStatusInput.value = barber.status;
                barberPhotoInput.value = barber.photo;
            }
        } else {
            modalTitle.textContent = 'Adicionar Novo Barbeiro';
            barberIdInput.value = '';
        }
        if(barberModal) barberModal.classList.add('show');
    };

    const closeBarberModal = () => {
        if(barberModal) barberModal.classList.remove('show');
    };

    const openDeleteModal = (card) => {
        barberIdToDelete = card.dataset.id;
        const barberName = card.querySelector('h3').textContent;
        barberNameToDelete.textContent = `"${barberName}"`;
        if(deleteConfirmModal) deleteConfirmModal.classList.add('show');
    };

    const closeDeleteModal = () => {
        barberIdToDelete = null;
        if(deleteConfirmModal) deleteConfirmModal.classList.remove('show');
    };

    // --- Event Listeners --- //
    if(addBarberBtn) addBarberBtn.addEventListener('click', () => openBarberModal(false));
    if(closeBarberModalBtn) closeBarberModalBtn.addEventListener('click', closeBarberModal);
    if(barberModal) barberModal.addEventListener('click', (e) => {
        if (e.target === barberModal) closeBarberModal();
    });
    
    // Delete Modal Listeners
    if(closeConfirmModalBtn) closeConfirmModalBtn.addEventListener('click', closeDeleteModal);
    if(cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    if(deleteConfirmModal) deleteConfirmModal.addEventListener('click', (e) => {
        if (e.target === deleteConfirmModal) closeDeleteModal();
    });

    if(confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', () => {
        if(barberIdToDelete) {
            initialBarbers = initialBarbers.filter(b => b.id !== barberIdToDelete);
            const cardToRemove = barbersContainer.querySelector(`[data-id='${barberIdToDelete}']`);
            if(cardToRemove) cardToRemove.remove();
            closeDeleteModal();
        }
    });

    // Event delegation for Edit and Delete
    if(barbersContainer) {
        barbersContainer.addEventListener('click', (e) => {
            const target = e.target;
            const card = target.closest('.barber-card');

            if (target.closest('.edit-btn')) {
                openBarberModal(true, card);
            }

            if (target.closest('.delete-btn')) {
                openDeleteModal(card);
            }
        });
    }

    // Form Submission
    if(barberForm) {
        barberForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const specialties = barberSpecialtiesInput.value.split(',').map(s => s.trim()).filter(s => s);
            
            const barberData = {
                id: barberIdInput.value || Date.now().toString(),
                name: barberNameInput.value,
                specialties: specialties,
                status: barberStatusInput.value,
                photo: barberPhotoInput.value || 'https://i.pravatar.cc/150?u=newbarber'
            };

            if (barberIdInput.value) {
                initialBarbers = initialBarbers.map(b => b.id === barberData.id ? barberData : b);
                const cardToUpdate = barbersContainer.querySelector(`[data-id='${barberData.id}']`);
                if (cardToUpdate) updateCard(cardToUpdate, barberData);
            } else {
                initialBarbers.push(barberData);
                const newCard = createBarberCard(barberData);
                barbersContainer.appendChild(newCard);
            }
            closeBarberModal();
        });
    }

    // --- Data and Helper Functions --- //
    let initialBarbers = [
        { id: '1', name: 'Arthur Morgan', specialties: ['Corte Clássico', 'Barba'], status: 'active', photo: 'https://i.pravatar.cc/150?u=arthurmorgan' },
        { id: '2', name: 'John Marston', specialties: ['Coloração', 'Penteado Moderno'], status: 'active', photo: 'https://i.pravatar.cc/150?u=johnmarston' },
        { id: '3', name: 'Dutch van der Linde', specialties: ['Corte Clássico'], status: 'inactive', photo: 'https://i.pravatar.cc/150?u=dutchvanderlinde' },
    ];

    const getBarberById = (id) => initialBarbers.find(b => b.id === id);

    const createSpecialtyTags = (specialties) => {
        return specialties.map(s => `<span class="tag">${s}</span>`).join('');
    };

    const createBarberCard = (barber) => {
        const card = document.createElement('div');
        card.className = 'barber-card';
        card.dataset.id = barber.id;
        updateCard(card, barber);
        return card;
    };

    const updateCard = (card, barber) => {
        card.innerHTML = `
            <div class="barber-card-header">
                <div class="barber-details">
                    <img src="${barber.photo}" alt="Avatar de ${barber.name}">
                    <div class="barber-info">
                        <h3>${barber.name}</h3>
                        <div class="status-badge ${barber.status}">${barber.status === 'active' ? 'Ativo' : 'Inativo'}</div>
                    </div>
                </div>
                <div class="barber-card-actions">
                    <button class="edit-btn"><i class="fas fa-pencil-alt"></i></button>
                    <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
            <div class="barber-card-body">
                <h4>Especialidades</h4>
                <div class="specialties-tags">
                    ${createSpecialtyTags(barber.specialties)}
                </div>
            </div>
        `;
    };

    const renderBarbers = () => {
        if(!barbersContainer) return;
        barbersContainer.innerHTML = '';
        initialBarbers.forEach(barber => {
            const card = createBarberCard(barber);
            barbersContainer.appendChild(card);
        });
    };

    // Initial Render
    renderBarbers();
});
