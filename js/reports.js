document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar and Overlay Functionality --- //
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

    // --- Chart.js Configuration --- //
    const appointmentsCtx = document.getElementById('appointments-chart')?.getContext('2d');
    const revenueCtx = document.getElementById('revenue-chart')?.getContext('2d');

    // 1. Appointments Over Time Chart (Bar Chart)
    if (appointmentsCtx) {
        new Chart(appointmentsCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Appointments',
                    data: [150, 100, 250, 320],
                    backgroundColor: '#2EB67D',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#aaa' }, grid: { display: false } },
                    y: { ticks: { color: '#aaa' }, grid: { color: '#2a342f' }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Revenue per Barber Chart (Stacked Horizontal Bar Chart)
    if (revenueCtx) {
        const revenueData = [3500, 2250, 4250, 2875];
        const maxRevenue = 5000; // Set a logical maximum for the background track

        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['James', 'Mike', 'Sarah', 'Tom'],
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        backgroundColor: '#2EB67D',
                        borderRadius: 4,
                        order: 1 // Render this dataset on top
                    },
                    {
                        label: 'Track',
                        data: revenueData.map(() => maxRevenue),
                        backgroundColor: '#3a443f', // Darker background for the track
                        borderRadius: 4,
                        order: 2
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { filter: (item) => item.datasetIndex === 0 } // Only show tooltip for the revenue dataset
                },
                scales: {
                    x: {
                        stacked: true,
                        display: false, // Hide the X-axis labels/grid
                        max: maxRevenue
                    },
                    y: {
                        stacked: true,
                        ticks: { color: '#aaa' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
});