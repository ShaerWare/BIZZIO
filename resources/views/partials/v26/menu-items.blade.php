{{-- #181 Состав меню раздела (левая панель «Меню») — единственный источник для всех страниц.

     Сюда же переехали прежние вложенные меню второго уровня из Tailwind-шапки:
     выпадающие «Закупки» и «Новости» стали разделами этого меню.

     Меню персонализировано под сервис (замечание заказчика от 30.08): пункты текущего
     раздела вынесены наверх отдельной группой, ниже идёт полное меню — так при переходе
     между страницами ничего не «пропадает», а активный раздел виден сразу.

     Параметры: $rowClass — класс пункта, $labelClass — класс заголовка группы,
     $iconClass — класс PNG-иконки закупок (в мобильном скоупе эталона она своя). --}}
@php
    $rowClass ??= 'auth-menu-row';
    $labelClass ??= 'auth-drawer-label';
    // Иконка закупок — PNG; в мобильном скоупе эталона у неё свой класс с размерами
    $iconClass ??= 'procurement-icon';

    $menuUser = auth()->user();
    $isCompanyModerator = $menuUser?->isModeratorOfAnyCompany() ?? false;

    // Текущий раздел: по нему группа сервиса поднимается наверх и не дублируется ниже.
    $section = match (true) {
        request()->routeIs('companies.*') => 'companies',
        request()->routeIs('projects.*') => 'projects',
        request()->routeIs('friends.*') => 'friends',
        request()->routeIs('tenders.*', 'rfqs.*', 'auctions.*') => 'tenders',
        request()->routeIs('news.*', 'profile.keywords.*') => 'news',
        default => null,
    };

    $procurementBase = '/images/v26/bizzio-quick-icon-procurement-base-v5.png';
    $procurementCreate = '/images/v26/bizzio-quick-icon-create-procurement-v4.png';

    // Группы сервисов. Пункт: label, url, icon (symbol из спрайта) либо image (PNG), active.
    $groups = [];

    $groups['companies'] = [
        'title' => 'Компании',
        'items' => array_values(array_filter([
            [
                'label' => 'Каталог компаний',
                'url' => route('companies.index'),
                'icon' => 'building',
                'active' => request()->routeIs('companies.index') && ! request()->filled('is_verified'),
            ],
            [
                'label' => 'Верифицированные компании',
                'url' => route('companies.index', ['is_verified' => 1]),
                'icon' => 'auth-shield',
                'active' => request()->routeIs('companies.index') && request()->input('is_verified') == '1',
            ],
            $menuUser ? [
                'label' => 'Создать компанию',
                'url' => route('companies.create'),
                'icon' => 'plus',
                'active' => request()->routeIs('companies.create'),
            ] : null,
        ])),
    ];

    $groups['projects'] = [
        'title' => 'Проекты',
        'items' => array_values(array_filter([
            [
                'label' => 'Все проекты',
                'url' => route('projects.index'),
                'icon' => 'clip',
                'active' => request()->routeIs('projects.index') && ! request()->filled('status'),
            ],
            [
                'label' => 'Активные проекты',
                'url' => route('projects.index', ['status' => 'active']),
                'icon' => 'poll',
                'active' => request()->routeIs('projects.index') && request()->input('status') === 'active',
            ],
            $isCompanyModerator ? [
                'label' => 'Создать проект',
                'url' => route('projects.create'),
                'icon' => 'plus',
                'active' => request()->routeIs('projects.create'),
            ] : null,
        ])),
    ];

    if ($menuUser) {
        $groups['friends'] = [
            'title' => 'Друзья',
            'items' => [
                [
                    'label' => 'Мои друзья',
                    'url' => route('friends.index'),
                    'icon' => 'users',
                    'active' => request()->routeIs('friends.index') && in_array(request('tab', 'friends'), ['friends', null], true),
                ],
                [
                    'label' => 'Входящие заявки',
                    'url' => route('friends.index', ['tab' => 'incoming']),
                    'icon' => 'auth-user',
                    'active' => request()->routeIs('friends.index') && request('tab') === 'incoming',
                ],
                [
                    'label' => 'Отправленные заявки',
                    'url' => route('friends.index', ['tab' => 'outgoing']),
                    'icon' => 'share',
                    'active' => request()->routeIs('friends.index') && request('tab') === 'outgoing',
                ],
                [
                    'label' => 'Рекомендации',
                    'url' => route('friends.index', ['tab' => 'suggestions']),
                    'icon' => 'like',
                    'active' => request()->routeIs('friends.index') && request('tab') === 'suggestions',
                ],
            ],
        ];
    }

    $groups['tenders'] = [
        'title' => 'Закупки',
        'items' => array_values(array_filter([
            [
                'label' => 'Найти закупку',
                'url' => route('tenders.index'),
                'image' => $procurementBase,
                'active' => request()->routeIs('tenders.index'),
            ],
            $isCompanyModerator ? [
                'label' => 'Создать закупку',
                'url' => route('rfqs.create', ['procedure' => 'commercial']),
                'image' => $procurementCreate,
                'active' => request()->routeIs('rfqs.create'),
            ] : null,
            $isCompanyModerator ? [
                'label' => 'Мои закупки',
                'url' => route('tenders.my'),
                'icon' => 'gavel',
                'active' => request()->routeIs('tenders.my'),
            ] : null,
            $menuUser ? [
                'label' => 'Мои приглашения',
                'url' => route('tenders.invitations.my'),
                'icon' => 'clip',
                'active' => request()->routeIs('tenders.invitations.my'),
            ] : null,
            $menuUser ? [
                'label' => 'Мои заявки',
                'url' => route('tenders.bids.my'),
                'icon' => 'file',
                'active' => request()->routeIs('tenders.bids.my'),
            ] : null,
            [
                'label' => 'Правила закупок',
                'url' => route('tenders.rules'),
                'icon' => 'file',
                'active' => request()->routeIs('tenders.rules'),
            ],
        ])),
    ];

    $groups['news'] = [
        'title' => 'Новости',
        'items' => array_values(array_filter([
            [
                'label' => 'Лента новостей',
                'url' => route('news.index'),
                'icon' => 'news',
                'active' => request()->routeIs('news.index'),
            ],
            $menuUser ? [
                'label' => 'Ключевые слова',
                'url' => route('profile.keywords.index'),
                'icon' => 'auth-settings',
                'active' => request()->routeIs('profile.keywords.*'),
            ] : null,
        ])),
    ];

    // «Моя работа» — точки входа во все сервисы, всегда целиком.
    $workItems = array_values(array_filter([
        [
            'label' => 'Главная',
            'url' => route('home'),
            'icon' => 'auth-home',
            'active' => request()->routeIs('home'),
        ],
        [
            'label' => 'Компании',
            'url' => route('companies.index'),
            'icon' => 'building',
            'active' => request()->routeIs('companies.*'),
        ],
        [
            'label' => 'Проекты',
            'url' => route('projects.index'),
            'icon' => 'clip',
            'active' => request()->routeIs('projects.*'),
        ],
        $menuUser ? [
            'label' => 'Друзья',
            'url' => route('friends.index'),
            'icon' => 'users',
            'active' => request()->routeIs('friends.*'),
        ] : null,
        // Подписки переехали сюда из меню «Сервисы» (замечание заказчика от 30.08)
        $menuUser ? [
            'label' => 'Подписки',
            'url' => route('subscriptions.index'),
            'icon' => 'auth-bookmark',
            'active' => request()->routeIs('subscriptions.*'),
        ] : null,
    ]));

    $supportItems = array_values(array_filter([
        $menuUser ? [
            'label' => 'Уведомления',
            'url' => route('notifications.index'),
            'icon' => 'bell',
            'active' => request()->routeIs('notifications.*'),
        ] : null,
        [
            'label' => 'Помощь и обратная связь',
            'url' => $menuUser ? route('profile.edit').'#feedback' : config('app.auth_url'),
            'icon' => 'help-chat',
            'active' => false,
        ],
    ]));

    // Порядок вывода: группа текущего сервиса → «Моя работа» → остальные сервисы → поддержка.
    $rendered = [];

    if ($section !== null && isset($groups[$section])) {
        $rendered[] = $groups[$section];
    }

    $rendered[] = ['title' => 'Моя работа', 'items' => $workItems];

    // Полное меню ниже — ВСЕ сервисы, а не только закупки и новости: иначе при уходе
    // из раздела его пункты пропадали, ровно то, что персонализация должна была
    // предотвратить. `isset` нужен из-за группы «Друзья» — её нет у гостя.
    foreach (['companies', 'projects', 'friends', 'tenders', 'news'] as $key) {
        if ($section !== $key && isset($groups[$key])) {
            $rendered[] = $groups[$key];
        }
    }

    $rendered[] = ['title' => 'Настройки и поддержка', 'items' => $supportItems];
@endphp

@foreach($rendered as $group)
    <div class="{{ $labelClass }}">{{ $group['title'] }}</div>
    @foreach($group['items'] as $item)
        <a class="{{ $rowClass }} {{ $item['active'] ? 'current bz-current' : '' }}" href="{{ $item['url'] }}">
            @isset($item['image'])
                <img class="{{ $iconClass }}" src="{{ $item['image'] }}" alt="">
            @else
                <svg><use href="#{{ $item['icon'] }}"/></svg>
            @endisset
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
@endforeach
