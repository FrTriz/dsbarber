document.addEventListener('DOMContentLoaded', () => {
    const state = {
        currentStep: 1,
        barber: null,
        services: [],
        date: null,
        time: null,
        totalPrice: 0,
        paymentOption: 'full' // 'full' or 'half'
    };

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

    function updateSummary() {
        summaryBarber.textContent = state.barber ? state.barber : 'Não selecionado';

        if (state.services.length > 0) {
            summaryServices.innerHTML = state.services.map(s => s.name).join(', <br>');
        } else {
            summaryServices.textContent = 'Não selecionado';
        }

        const basePrice = state.services.reduce((acc, service) => acc + service.price, 0);
        state.totalPrice = basePrice;

        const priceToPay = state.paymentOption === 'half' ? state.totalPrice / 2 : state.totalPrice;
        summaryTotal.textContent = `R$${priceToPay.toFixed(2)}`;

        if (state.date && state.time) {
            summaryDatetime.textContent = `${state.date} às ${state.time}`;
        } else {
            summaryDatetime.textContent = 'Não selecionado';
        }

        if (state.currentStep === 4) {
            paymentTotalFull.textContent = state.totalPrice.toFixed(2);
            paymentTotalHalf.textContent = (state.totalPrice / 2).toFixed(2);
        }
    }

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

    // Step 1: Barber Selection
    document.querySelectorAll('.barber-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.barber-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            state.barber = card.dataset.barber;
            updateSummary();
        });
    });

    // Step 2: Service Selection
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => {
            card.classList.toggle('selected');
            const serviceName = card.dataset.service;
            const servicePrice = parseFloat(card.dataset.price);

            if (card.classList.contains('selected')) {
                state.services.push({ name: serviceName, price: servicePrice });
            } else {
                state.services = state.services.filter(s => s.name !== serviceName);
            }
            updateSummary();
        });
    });

    // Step 3: Calendar & Time
    const monthNameEl = document.getElementById('month-name');
    const calendarDaysEl = document.getElementById('calendar-days');
    let currentDate = new Date();

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

        for (let i = 1; i <= lastDate; i++) {
            const dayEl = document.createElement('div');
            dayEl.textContent = i;
            dayEl.addEventListener('click', () => {
                document.querySelectorAll('.days div.selected').forEach(d => d.classList.remove('selected'));
                dayEl.classList.add('selected');
                state.date = `${i}/${month + 1}/${year}`;
                updateSummary();
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

    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.addEventListener('click', () => {
             document.querySelectorAll('.time-slot.selected').forEach(s => s.classList.remove('selected'));
             slot.classList.add('selected');
             state.time = slot.textContent;
             updateSummary();
        });
    });

    // Step 4: Payment Options
    paymentTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            paymentTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            state.paymentOption = tab.textContent.toLowerCase().includes('metade') ? 'half' : 'full';
            updateSummary();
        });
    });

    // Navigation
    nextBtn.addEventListener('click', () => {
        if (state.currentStep < 4) {
            goToStep(state.currentStep + 1);
        }
    });

    backBtn.addEventListener('click', () => {
        if (state.currentStep > 1) {
            goToStep(state.currentStep - 1);
        }
    });

    // Initial setup
    const defaultBarber = document.querySelector('.barber-card');
    if(defaultBarber) {
        defaultBarber.classList.add('selected');
        state.barber = defaultBarber.dataset.barber;
    }

    generateCalendar(currentDate);
    updateSummary();
    goToStep(1);
});