<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .hover-scale {
            transition: transform 0.2s;
        }
        .hover-scale:hover {
            transform: scale(1.02);
        }
        .sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s ease;
            background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
        }
        .sidebar-hidden {
            transform: translateX(-100%);
        }
        .sidebar-open {
            transform: translateX(0);
        }
        .sidebar-pinned {
            transform: none !important;
            position: fixed;
        }
        .menu-item {
            position: relative;
            transition: background 0.2s, transform 0.2s;
            border-left: 4px solid transparent;
        }
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
            border-left-color: #3b82f6;
        }
        .menu-item.active {
            background: rgba(59, 130, 246, 0.2);
            border-left-color: #3b82f6;
        }
        .tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #1f2937;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
            transition: opacity 0.2s;
            z-index: 100;
        }
        .menu-item:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }
        .burger-icon {
            transition: transform 0.3s;
        }
        .burger-icon.open {
            transform: rotate(90deg);
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
    @yield('styles')
</head>
<body class="bg-gray-100 font-sans">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 sidebar md:transform-none z-50" id="sidebar">
        <div class="p-4 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-white">Навигация</h2>
            <button id="pinSidebar" class="hidden md:block text-gray-300 hover:text-white" title="Закрепить сайдбар">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h14M5 12h14M5 19h14"></path></svg>
            </button>
        </div>
        <nav class="mt-4">
            <a href="{{ route('document') }}" class="menu-item flex items-center px-4 py-3 text-white hover:text-white transition {{ request()->routeIs('document') ? 'active' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Учебный план
                <span class="tooltip">Просмотр учебного плана</span>
            </a>
            <a href="{{ route('edit_data') }}" class="menu-item flex items-center px-4 py-3 text-white hover:text-white transition {{ request()->routeIs('edit_data') ? 'active' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15.828l-2.828.586.586-2.828L16.586 6.586z"></path></svg>
                Редактирование данных
                <span class="tooltip">Редактировать учебные данные</span>
            </a>
            <a href="{{ route('schedule') }}" class="menu-item flex items-center px-4 py-3 text-white hover:text-white transition {{ request()->routeIs('schedule') ? 'active' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Расписание
                <span class="tooltip">Просмотр расписания</span>
            </a>
            <a href="{{ route('upload') }}" class="menu-item flex items-center px-4 py-3 text-white hover:text-white transition {{ request()->routeIs('upload') ? 'active' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 0115.9 6M9 19h6m-3-3v6"></path></svg>
                Загрузка документа
                <span class="tooltip">Загрузить новый документ</span>
            </a>
            <a href="{{ route('teachers.index') }}" class="menu-item flex items-center px-4 py-3 text-white hover:text-white transition {{ request()->routeIs('teachers.index') ? 'active' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Преподаватели
                <span class="tooltip">Управление преподавателями</span>
            </a>
            <a href="{{ route('cabinets.index') }}" class="menu-item flex items-center px-4 py-3 text-white hover:text-white transition {{ request()->routeIs('cabinets.index') ? 'active' : '' }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h-2m-6 0H3m0 0h2m4 0h2"></path></svg>
                Кабинеты
                <span class="tooltip">Управление кабинетами</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="md:ml-64 p-6 main-content" id="mainContent">
        <div class="container mx-auto fade-in">
            <!-- Burger Menu Button -->
            <button id="toggleSidebar" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-800 text-white rounded">
                <svg class="w-6 h-6 burger-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            @yield('content')
        </div>
    </div>

    <script>
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const pinSidebar = document.getElementById('pinSidebar');
        let isPinned = false;

        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            toggleSidebar.querySelector('.burger-icon').classList.toggle('open');
        });

        pinSidebar.addEventListener('click', () => {
            isPinned = !isPinned;
            sidebar.classList.toggle('sidebar-pinned', isPinned);
            mainContent.classList.toggle('md:ml-64', isPinned);
            pinSidebar.innerHTML = isPinned
                ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"></path></svg>`
                : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h14M5 12h14M5 19h14"></path></svg>`;
        });

        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggleSidebar.contains(e.target) && sidebar.classList.contains('sidebar-open')) {
                sidebar.classList.remove('sidebar-open');
                toggleSidebar.querySelector('.burger-icon').classList.remove('open');
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
