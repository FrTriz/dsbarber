document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar (copiado do admin.js) ---
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');

    const toggleSidebar = () => {
        if (sidebar) sidebar.classList.toggle('show');
        if (overlay) overlay.classList.toggle('show');
    };

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);
    if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // --- Lógica da Página de Horários ---
    const barbeiroSelect = document.getElementById('barbeiro-select');
    const horariosForm = document.getElementById('horarios-form');
    const horariosBody = document.getElementById('horarios-body');
    const horariosLoading = document.getElementById('horarios-loading');

    const diasDaSemana = [
        "Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"
    ];

    // Se o usuário for um barbeiro, seleciona ele automaticamente e busca os horários
    if (typeof usuarioLogadoTipo !== 'undefined' && usuarioLogadoTipo === 'barbeiro') {
        barbeiroSelect.disabled = true;
        if (barbeiroSelect.options[1]) {
            barbeiroSelect.value = barbeiroSelect.options[1].value;
            buscarHorarios(barbeiroSelect.value);
        }
    }

    // Evento ao mudar o barbeiro no dropdown
    barbeiroSelect.addEventListener('change', (e) => {
        const barbeiroId = e.target.value;
        if (barbeiroId) {
            buscarHorarios(barbeiroId);
        } else {
            horariosForm.style.display = 'none';
        }
    });

    // Evento ao salvar o formulário
    horariosForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const barbeiroId = barbeiroSelect.value;
        const formData = new FormData(horariosForm);
        const data = {
            id_barbeiro: barbeiroId,
            horarios: []
        };

        // Coleta os dados dos 7 dias
        for (let i = 0; i < 7; i++) {
            const trabalha = formData.get(`trabalha-${i}`) === 'on';
            data.horarios.push({
                dia_semana: i,
                trabalha: trabalha,
                hora_inicio: formData.get(`inicio-${i}`),
                hora_fim: formData.get(`fim-${i}`),
                inicio_pausa: formData.get(`pausa_inicio-${i}`),
                fim_pausa: formData.get(`pausa_fim-${i}`)
            });
        }

        try {
            const response = await fetch('../php/Funcoes/salvar-horarios-barbeiro.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.sucesso) {
                alert('Horários salvos com sucesso!');
            } else {
                throw new Error(result.mensagem || 'Erro ao salvar horários.');
            }
        } catch (error) {
            console.error('Erro ao salvar:', error);
            alert('Falha ao salvar horários: ' + error.message);
        }
    });

    // Função para buscar os horários do barbeiro
    async function buscarHorarios(barbeiroId) {
        horariosLoading.style.display = 'block';
        horariosForm.style.display = 'block';
        horariosBody.innerHTML = '';

        try {
            const response = await fetch(`../php/Funcoes/buscar-horarios-barbeiro.php?id_barbeiro=${barbeiroId}`);
            const horariosSalvos = await response.json();

            if (horariosSalvos.erro) {
                throw new Error(horariosSalvos.erro);
            }
            
            // Converte o array de horários em um "mapa" para fácil acesso
            const horariosMap = new Map();
            horariosSalvos.forEach(h => horariosMap.set(parseInt(h.dia_semana, 10), h));

            renderizarFormulario(horariosMap);

        } catch (error) {
            console.error('Erro ao buscar horários:', error);
            horariosBody.innerHTML = `<p class="error-message">Não foi possível carregar os horários. ${error.message}</p>`;
        } finally {
            horariosLoading.style.display = 'none';
        }
    }

    // Função para criar o HTML do formulário
    function renderizarFormulario(horariosMap) {
        horariosBody.innerHTML = '';
        for (let i = 0; i < 7; i++) { // 0 = Domingo, 1 = Segunda, ..., 6 = Sábado
            const dia = diasDaSemana[i];
            const horario = horariosMap.get(i);
            
            const trabalha = !!horario; // Se existe um registro, ele trabalha
            const hora_inicio = horario ? horario.hora_inicio.substring(0, 5) : '09:00';
            const hora_fim = horario ? horario.hora_fim.substring(0, 5) : '18:00';
            const inicio_pausa = horario && horario.inicio_pausa ? horario.inicio_pausa.substring(0, 5) : '';
            const fim_pausa = horario && horario.fim_pausa ? horario.fim_pausa.substring(0, 5) : '';

            const row = document.createElement('div');
            row.className = `day-row ${trabalha ? '' : 'disabled'}`;
            row.id = `day-row-${i}`;
            row.innerHTML = `
                <div class="day-label">
                    <strong>${dia}</strong>
                    <label class="toggle-trabalha">
                        <input type="checkbox" name="trabalha-${i}" id="trabalha-${i}" ${trabalha ? 'checked' : ''}>
                        <span class="slider"></span>
                        <span>Trabalha</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="inicio-${i}">Início</label>
                    <input type="time" name="inicio-${i}" id="inicio-${i}" value="${hora_inicio}" ${trabalha ? '' : 'disabled'}>
                </div>
                <div class="form-group">
                    <label for="fim-${i}">Fim</label>
                    <input type="time" name="fim-${i}" id="fim-${i}" value="${hora_fim}" ${trabalha ? '' : 'disabled'}>
                </div>
                <div class="form-group">
                    <label for="pausa_inicio-${i}">Início Pausa</label>
                    <input type="time" name="pausa_inicio-${i}" id="pausa_inicio-${i}" value="${inicio_pausa}" ${trabalha ? '' : 'disabled'}>
                </div>
                <div class="form-group">
                    <label for="pausa_fim-${i}">Fim Pausa</label>
                    <input type="time" name="pausa_fim-${i}" id="pausa_fim-${i}" value="${fim_pausa}" ${trabalha ? '' : 'disabled'}>
                </div>
            `;
            horariosBody.appendChild(row);

            // Adiciona o evento para habilitar/desabilitar os inputs
            const checkbox = row.querySelector(`#trabalha-${i}`);
            checkbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                const parentRow = document.getElementById(`day-row-${i}`);
                parentRow.classList.toggle('disabled', !isChecked);
                parentRow.querySelectorAll('input[type="time"]').forEach(input => {
                    input.disabled = !isChecked;
                });
            });
        }
    }
});