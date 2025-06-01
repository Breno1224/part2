<?php
session_start(); 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}

$nome_aluno = $_SESSION['usuario_nome'] ?? 'Aluno(a)';
$currentPageIdentifier = 'calendario'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calendário Pessoal - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="css/calendario_novo.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">

    <header>
        <button id="menu-toggle-page" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Calendário Pessoal</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="mainContainerPage">
        <nav class="sidebar" id="sidebarPage">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align:center; margin-bottom:1.5rem;">
                <h2 class="page-title-calendar">Meu Calendário</h2>
            </div>

            <div class="calendar-view-container dashboard-section">
                <div class="calendar-navigation">
                    <button id="prevMonthCalendarBtn" aria-label="Mês anterior" title="Mês anterior"><i class="fas fa-chevron-left"></i></button>
                    <h3 id="currentMonthYearDisplay">Carregando...</h3>
                    <button id="nextMonthCalendarBtn" aria-label="Próximo mês" title="Próximo mês"><i class="fas fa-chevron-right"></i></button>
                </div>
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>
                        </tr>
                    </thead>
                    <tbody id="calendarTableBody">
                     
                    </tbody>
                </table>
            </div>

            <div class="event-section-container dashboard-section">
                <h3>Lembretes e Eventos</h3>
                <div class="event-form-area">
                    <label for="eventDateSelectedDisplay">Data selecionada: <strong id="eventDateSelectedDisplay">-</strong></label>
                    <input type="text" id="newEventTitleInput" placeholder="Adicionar lembrete (ex: Prova de Álgebra)">
                    <button type="button" id="addEventToCalendarBtn"><i class="fas fa-plus"></i> Adicionar</button>
                </div>
                <div class="event-display-area">
                    <h4>Eventos para <span id="eventDisplayDateSpan">-</span>:</h4>
                    <ul id="eventListForDayUl">
                        <li>Nenhum evento ou dia não selecionado.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        // JAVASCRIPT DO CALENDÁRIO
        document.addEventListener('DOMContentLoaded', function () {
            const calendarTableBody = document.getElementById('calendarTableBody');
            const currentMonthYearDisplay = document.getElementById('currentMonthYearDisplay');
            const prevMonthBtn = document.getElementById('prevMonthCalendarBtn');
            const nextMonthBtn = document.getElementById('nextMonthCalendarBtn');
            
            const eventDateSelectedDisplay = document.getElementById('eventDateSelectedDisplay');
            const newEventTitleInput = document.getElementById('newEventTitleInput');
            const addEventBtn = document.getElementById('addEventToCalendarBtn');
            const eventListForDayUl = document.getElementById('eventListForDayUl');
            const eventDisplayDateSpan = document.getElementById('eventDisplayDateSpan');

            let currentDateInternal = new Date(); 
            let selectedDateKeyInternal = null;   
            let userEventsInternal = JSON.parse(localStorage.getItem('acadmixAlunoCalendarEvents')) || {};

            function formatDateAsKeyInternal(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }
            
            function formatKeyForDisplayInternal(dateKey) {
                if (!dateKey) return '-';
                const [y, m, d] = dateKey.split('-');
                return `${d}/${m}/${y}`;
            }

            function displayEventsForSelectedDateInternal() {
                if (!eventListForDayUl) return;
                eventListForDayUl.innerHTML = '';
                
                const displayDateStr = formatKeyForDisplayInternal(selectedDateKeyInternal) || '-';
                if (eventDateSelectedDisplay) eventDateSelectedDisplay.textContent = displayDateStr;
                if (eventDisplayDateSpan) eventDisplayDateSpan.textContent = displayDateStr;

                const eventsOnDate = userEventsInternal[selectedDateKeyInternal] || [];
                if (eventsOnDate.length === 0) {
                    eventListForDayUl.innerHTML = '<li>Nenhum lembrete para este dia.</li>';
                } else {
                    eventsOnDate.forEach(eventText => {
                        const li = document.createElement('li');
                        li.textContent = eventText;
                        eventListForDayUl.appendChild(li);
                    });
                }
            }

            function renderNewCalendarInternal() {
                if (!calendarTableBody || !currentMonthYearDisplay) {
                    console.error("Elementos HTML do calendário não encontrados para renderNewCalendarInternal.");
                    return;
                }
                calendarTableBody.innerHTML = ''; 
                const year = currentDateInternal.getFullYear();
                const month = currentDateInternal.getMonth();

                currentMonthYearDisplay.textContent = currentDateInternal.toLocaleDateString('pt-BR', {
                    month: 'long', year: 'numeric'
                }).replace(/^\w/, c => c.toUpperCase());

                const firstDayOfMonth = new Date(year, month, 1).getDay(); 
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                
                let dateCounter = 1;
                for (let i = 0; i < 6; i++) { 
                    const row = document.createElement('tr');
                    for (let j = 0; j < 7; j++) {
                        const cell = document.createElement('td');
                        cell.classList.add('day-cell');
                        if (i === 0 && j < firstDayOfMonth) {
                            cell.classList.add('empty');
                        } else if (dateCounter > daysInMonth) {
                            cell.classList.add('empty');
                        } else {
                            const dayNumberSpan = document.createElement('span');
                            dayNumberSpan.classList.add('day-number');
                            dayNumberSpan.textContent = dateCounter;
                            cell.appendChild(dayNumberSpan);
                            
                            const cellDate = new Date(year, month, dateCounter);
                            const cellDateKey = formatDateAsKeyInternal(cellDate);

                            const today = new Date();
                            if (dateCounter === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                                cell.classList.add('today');
                            }
                            if (cellDateKey === selectedDateKeyInternal) {
                                cell.classList.add('selected');
                            }
                            if (userEventsInternal[cellDateKey] && userEventsInternal[cellDateKey].length > 0) {
                                cell.classList.add('has-event');
                                cell.title = "Eventos: \n• " + userEventsInternal[cellDateKey].join('\n• ');
                            }

                            cell.addEventListener('click', function() {
                                selectedDateKeyInternal = cellDateKey;
                                renderNewCalendarInternal(); 
                                displayEventsForSelectedDateInternal();
                            });
                            dateCounter++;
                        }
                        row.appendChild(cell);
                    }
                    calendarTableBody.appendChild(row);
                    if (dateCounter > daysInMonth && i >=3 ) { // Otimização para parar se já passou dos dias e já tem pelo menos 4 semanas (evita excesso de linhas vazias)
                        if ( (i === 3 && daysInMonth + firstDayOfMonth <= 28 ) || // Meses com 28 dias que começam domingo
                             (i === 4 && daysInMonth + firstDayOfMonth <= 35) ) { // Meses que cabem em 5 semanas
                                //  Não quebra ainda se pode precisar de 5 semanas
                        } else if (i >= 4) { // Se já está na quinta semana ou mais e acabou os dias, pode parar.
                             break;
                        }
                    }
                }
            }
            
            function saveEventsToLocalStorageInternal() {
                localStorage.setItem('acadmixAlunoCalendarEvents', JSON.stringify(userEventsInternal));
            }

            if (prevMonthBtn) {
                prevMonthBtn.addEventListener('click', function() {
                    currentDateInternal.setMonth(currentDateInternal.getMonth() - 1);
                    renderNewCalendarInternal();
                    displayEventsForSelectedDateInternal();
                });
            }
            if (nextMonthBtn) {
                nextMonthBtn.addEventListener('click', function() {
                    currentDateInternal.setMonth(currentDateInternal.getMonth() + 1);
                    renderNewCalendarInternal();
                    displayEventsForSelectedDateInternal();
                });
            }

            if (addEventBtn) {
                addEventBtn.addEventListener('click', function() {
                    if (!newEventTitleInput) return;
                    const title = newEventTitleInput.value.trim();
                    if (!title) {
                        alert('Por favor, digite o título do lembrete.');
                        return;
                    }
                    if (!selectedDateKeyInternal) {
                        alert('Por favor, selecione um dia no calendário primeiro.');
                        return;
                    }
                    if (!userEventsInternal[selectedDateKeyInternal]) {
                        userEventsInternal[selectedDateKeyInternal] = [];
                    }
                    userEventsInternal[selectedDateKeyInternal].push(title);
                    saveEventsToLocalStorageInternal();
                    newEventTitleInput.value = '';
                    renderNewCalendarInternal(); 
                    displayEventsForSelectedDateInternal(); 
                });
            }
            
            renderNewCalendarInternal();
            displayEventsForSelectedDateInternal();
        });

        // Script do menu lateral (Toggle)
        const menuToggleButtonPage = document.getElementById('menu-toggle-page');
        const sidebarNavigationPage = document.getElementById('sidebarPage'); 
        const mainContainerPage = document.getElementById('mainContainerPage'); 

        if (menuToggleButtonPage && sidebarNavigationPage && mainContainerPage) {
            menuToggleButtonPage.addEventListener('click', function () {
                sidebarNavigationPage.classList.toggle('hidden'); 
                mainContainerPage.classList.toggle('full-width'); 
            });
        }
    </script>
</body>
</html>
<?php
// if(isset($conn) && $conn) mysqli_close($conn); 
?>