<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sales, Purchase & Ledger System' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            min-height: 42px;
            border-radius: 0.5rem;
            border-color: rgb(209 213 219);
            padding: 0.35rem 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.6rem;
            color: rgb(31 41 55);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 0.5rem;
        }
        [x-cloak] {
            display: none !important;
        }

        .bs-date-picker .bs-calendar-panel {
            position: absolute;
            top: calc(100% + 0.55rem);
            left: 0;
            z-index: 70;
            width: min(21rem, calc(100vw - 3rem));
            border-radius: 0.95rem;
            border: 1px solid rgb(226 232 240);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            padding: 0.85rem;
        }

        .bs-date-picker .bs-calendar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
            margin-bottom: 0.8rem;
        }

        .bs-date-picker .bs-calendar-selectors {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.45rem;
        }

        .bs-date-picker .bs-calendar-select {
            border: 1px solid rgb(203 213 225);
            border-radius: 0.65rem;
            background: #fff;
            padding: 0.38rem 0.5rem;
            font-size: 0.78rem;
            color: rgb(51 65 85);
        }

        .bs-date-picker .bs-calendar-select:focus {
            outline: none;
            border-color: rgb(99 102 241);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .bs-date-picker .bs-calendar-nav {
            width: 2rem;
            height: 2rem;
            border: 1px solid rgb(203 213 225);
            border-radius: 999px;
            background: #fff;
            color: rgb(71 85 105);
            font-size: 1rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .bs-date-picker .bs-calendar-nav:hover {
            border-color: rgb(99 102 241);
            color: rgb(79 70 229);
            background: rgb(238 242 255);
        }

        .bs-date-picker .bs-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.3rem;
        }

        .bs-date-picker .bs-calendar-weekday {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-align: center;
            color: rgb(100 116 139);
            padding-bottom: 0.1rem;
        }

        .bs-date-picker .bs-calendar-day {
            border: 1px solid transparent;
            border-radius: 0.62rem;
            min-height: 2rem;
            font-size: 0.8rem;
            color: rgb(51 65 85);
            background: transparent;
            transition: all 0.15s ease;
        }

        .bs-date-picker .bs-calendar-day:not(.is-empty):hover {
            border-color: rgb(165 180 252);
            background: rgb(238 242 255);
            color: rgb(67 56 202);
        }

        .bs-date-picker .bs-calendar-day.is-empty {
            cursor: default;
            color: transparent;
        }

        .bs-date-picker .bs-calendar-day.is-today {
            border-color: rgb(99 102 241);
            background: rgb(238 242 255);
            color: rgb(67 56 202);
            font-weight: 600;
        }

        .bs-date-picker .bs-calendar-day.is-selected {
            border-color: rgb(67 56 202);
            background: rgb(79 70 229);
            color: white;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(79, 70, 229, 0.35);
        }

        .bs-date-picker .bs-calendar-footer {
            margin-top: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .bs-date-picker .bs-calendar-link {
            border: none;
            background: none;
            padding: 0.2rem 0;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgb(79 70 229);
            transition: color 0.15s ease;
        }

        .bs-date-picker .bs-calendar-link:hover {
            color: rgb(55 48 163);
        }

        @media (max-width: 640px) {
            .bs-date-picker .bs-calendar-panel {
                width: min(21rem, calc(100vw - 2.5rem));
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        <aside class="hidden w-72 shrink-0 border-r border-gray-200 bg-white lg:flex lg:flex-col">
            <div class="border-b border-gray-200 px-6 py-6">
                <a href="{{ route('dashboard') }}" class="text-2xl font-semibold text-indigo-600">LedgerApp</a>
                <p class="mt-2 text-sm text-gray-500">Sales, purchase, and ledger control in one place.</p>
            </div>
            <nav class="flex-1 space-y-2 px-4 py-6 text-sm font-medium">
                <a href="{{ route('dashboard') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Dashboard</a>
                <a href="{{ route('parties.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('parties.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Parties</a>
                <a href="{{ route('accounts.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Accounts</a>
                <a href="{{ route('sales.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('sales.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Sales</a>
                <a href="{{ route('purchases.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('purchases.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Purchases</a>
                <a href="{{ route('payments.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('payments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Payments</a>
            </nav>
        </aside>
        <div class="fixed inset-0 z-40 bg-gray-900/40 lg:hidden" x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"></div>
        <aside class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:hidden" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-5">
                <div>
                    <a href="{{ route('dashboard') }}" class="text-xl font-semibold text-indigo-600">LedgerApp</a>
                    <p class="mt-1 text-sm text-gray-500">Navigate modules</p>
                </div>
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600" @click="sidebarOpen = false">Close</button>
            </div>
            <nav class="flex-1 space-y-2 px-4 py-6 text-sm font-medium">
                <a href="{{ route('dashboard') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Dashboard</a>
                <a href="{{ route('parties.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('parties.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Parties</a>
                <a href="{{ route('accounts.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('accounts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Accounts</a>
                <a href="{{ route('sales.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('sales.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Sales</a>
                <a href="{{ route('purchases.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('purchases.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Purchases</a>
                <a href="{{ route('payments.index') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('payments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' }}">Payments</a>
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur lg:hidden">
                <div class="flex items-center justify-between px-4 py-4 sm:px-6">
                    <div>
                        <a href="{{ route('dashboard') }}" class="text-lg font-semibold text-indigo-600">LedgerApp</a>
                        <p class="text-xs text-gray-500">Sales, Purchase & Ledger System</p>
                    </div>
                    <button type="button" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600" @click="sidebarOpen = true">Menu</button>
                </div>
            </header>
            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 xl:px-8">
                @if (session('success'))
                    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="font-semibold">Please fix the following issues:</p>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <script>
        window.bsDateConfig = @json($bsDateConfig ?? ['years' => [], 'months' => [], 'monthMap' => []]);

        function bsDateSelector(name, initialValue = null) {
            const config = window.bsDateConfig || {};
            const years = (config.years || []).map((year) => String(year));
            const months = config.months || {};
            const monthMap = config.monthMap || {};
            const weekdays = config.weekdays || ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const startEnglishDate = String(config.startEnglishDate || '1943-04-14');
            const startNepaliYear = Number(config.startNepaliYear || (years[0] || 2000));
            const today = String(config.today || '');

            const epochDate = new Date(`${startEnglishDate}T00:00:00Z`);
            const epochWeekday = Number.isNaN(epochDate.getTime()) ? 3 : epochDate.getUTCDay();

            return {
                name,
                year: '',
                month: '',
                day: '',
                viewYear: years[years.length - 1] || '',
                viewMonth: '01',
                inputValue: '',
                isOpen: false,
                years,
                months,
                monthMap,
                weekdays,
                today,
                calendarDays: [],
                init() {
                    if (initialValue) {
                        if (!this.applyDateString(String(initialValue), true)) {
                            this.generateCalendar();
                        }
                    } else {
                        this.generateCalendar();
                    }
                },
                toggleCalendar() {
                    if (this.isOpen) {
                        this.closeCalendar();
                        return;
                    }

                    this.openCalendar();
                },
                openCalendar() {
                    if (this.year && this.month) {
                        this.viewYear = this.year;
                        this.viewMonth = this.month;
                    } else {
                        const parsedToday = this.parseDate(this.today);
                        if (parsedToday) {
                            this.viewYear = String(parsedToday.year);
                            this.viewMonth = this.pad(parsedToday.month);
                        }
                    }

                    this.generateCalendar();
                    this.isOpen = true;
                },
                closeCalendar() {
                    this.isOpen = false;
                },
                handleInput() {
                    const parsed = this.parseDate(this.inputValue);
                    if (!parsed) {
                        this.year = '';
                        this.month = '';
                        this.day = '';

                        return;
                    }

                    this.setSelectedDate(parsed.year, parsed.month, parsed.day, true, false);
                },
                syncFromSelectors() {
                    this.generateCalendar();
                },
                prevMonth() {
                    if (!this.viewYear || !this.viewMonth) {
                        return;
                    }

                    let targetYear = Number(this.viewYear);
                    let targetMonth = Number(this.viewMonth) - 1;

                    if (targetMonth <= 0) {
                        targetMonth = 12;
                        targetYear -= 1;
                    }

                    if (!this.monthMap[String(targetYear)]) {
                        return;
                    }

                    this.viewYear = String(targetYear);
                    this.viewMonth = this.pad(targetMonth);
                    this.generateCalendar();
                },
                nextMonth() {
                    if (!this.viewYear || !this.viewMonth) {
                        return;
                    }

                    let targetYear = Number(this.viewYear);
                    let targetMonth = Number(this.viewMonth) + 1;

                    if (targetMonth > 12) {
                        targetMonth = 1;
                        targetYear += 1;
                    }

                    if (!this.monthMap[String(targetYear)]) {
                        return;
                    }

                    this.viewYear = String(targetYear);
                    this.viewMonth = this.pad(targetMonth);
                    this.generateCalendar();
                },
                selectDay(dayValue) {
                    if (!dayValue || !this.viewYear || !this.viewMonth) {
                        return;
                    }

                    this.setSelectedDate(Number(this.viewYear), Number(this.viewMonth), Number(dayValue), true, true);
                },
                setToday() {
                    const parsed = this.parseDate(this.today);
                    if (!parsed) {
                        return;
                    }

                    this.setSelectedDate(parsed.year, parsed.month, parsed.day, true, true);
                },
                clearDate() {
                    this.year = '';
                    this.month = '';
                    this.day = '';
                    this.inputValue = '';
                    this.closeCalendar();
                },
                setSelectedDate(year, month, day, updateView = true, closeAfter = false) {
                    this.year = String(year);
                    this.month = this.pad(month);
                    this.day = this.pad(day);

                    if (updateView) {
                        this.viewYear = this.year;
                        this.viewMonth = this.month;
                    }

                    this.inputValue = this.formattedDate;
                    this.generateCalendar();

                    if (closeAfter) {
                        this.closeCalendar();
                    }
                },
                applyDateString(value, updateView = true) {
                    const parsed = this.parseDate(value);
                    if (!parsed) {
                        return false;
                    }

                    this.setSelectedDate(parsed.year, parsed.month, parsed.day, updateView, false);
                    return true;
                },
                parseDate(value) {
                    const match = String(value || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (!match) {
                        return null;
                    }

                    const year = Number(match[1]);
                    const month = Number(match[2]);
                    const day = Number(match[3]);

                    if (!this.monthMap[String(year)] || month < 1 || month > 12) {
                        return null;
                    }

                    const daysInMonth = this.getDaysInMonth(year, month);
                    if (day < 1 || day > daysInMonth) {
                        return null;
                    }

                    return { year, month, day };
                },
                getDaysInMonth(year, month) {
                    const monthsOfYear = this.monthMap[String(year)] || [];
                    return Number(monthsOfYear[month - 1] || 0);
                },
                getFirstWeekday(year, month) {
                    let elapsedDays = 0;

                    for (let bsYear = startNepaliYear; bsYear < year; bsYear += 1) {
                        const monthsOfYear = this.monthMap[String(bsYear)] || [];
                        for (const monthDays of monthsOfYear) {
                            elapsedDays += Number(monthDays || 0);
                        }
                    }

                    const monthsOfCurrentYear = this.monthMap[String(year)] || [];
                    for (let bsMonth = 1; bsMonth < month; bsMonth += 1) {
                        elapsedDays += Number(monthsOfCurrentYear[bsMonth - 1] || 0);
                    }

                    return (epochWeekday + elapsedDays) % 7;
                },
                generateCalendar() {
                    this.calendarDays = [];

                    const year = Number(this.viewYear);
                    const month = Number(this.viewMonth);
                    if (!year || !month) {
                        return;
                    }

                    const daysInMonth = this.getDaysInMonth(year, month);
                    if (!daysInMonth) {
                        return;
                    }

                    const firstWeekday = this.getFirstWeekday(year, month);
                    const cells = [];

                    for (let index = 0; index < firstWeekday; index += 1) {
                        cells.push(0);
                    }

                    for (let date = 1; date <= daysInMonth; date += 1) {
                        cells.push(date);
                    }

                    while (cells.length % 7 !== 0) {
                        cells.push(0);
                    }

                    while (cells.length) {
                        this.calendarDays.push(cells.splice(0, 7));
                    }
                },
                monthLabel(monthValue) {
                    return this.months[String(Number(monthValue))] || `Month ${monthValue}`;
                },
                pad(value) {
                    return String(value).padStart(2, '0');
                },
                isToday(dayValue) {
                    if (!dayValue || !this.today) {
                        return false;
                    }

                    const parsedToday = this.parseDate(this.today);
                    if (!parsedToday) {
                        return false;
                    }

                    return Number(this.viewYear) === parsedToday.year
                        && Number(this.viewMonth) === parsedToday.month
                        && Number(dayValue) === parsedToday.day;
                },
                isSelected(dayValue) {
                    if (!dayValue || !this.year || !this.month || !this.day) {
                        return false;
                    }

                    return Number(this.viewYear) === Number(this.year)
                        && Number(this.viewMonth) === Number(this.month)
                        && Number(dayValue) === Number(this.day);
                },
                get formattedDate() {
                    if (!this.year || !this.month || !this.day) {
                        return '';
                    }

                    return `${this.year}-${this.pad(this.month)}-${this.pad(this.day)}`;
                },
            };
        }

        document.addEventListener('alpine:init', () => {
            window.bsDateSelector = bsDateSelector;
        });

        document.addEventListener('DOMContentLoaded', function () {
            $('select.select2').select2({ width: '100%' });
        });
    </script>
</body>
</html>


