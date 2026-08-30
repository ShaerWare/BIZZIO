/**
 * #181 Поведение новой главной (эталон Bizzio_Dashboard_v26).
 *
 * Три обязанности:
 *   1. панели «Меню» и «Сервисы» + меню профиля (как в прототипе);
 *   2. нейтрализация элементов без реализации — клик не должен никуда вести и падать;
 *   3. события Яндекс.Метрики по ТЗ: inactive_feature_click и future_service_interest.
 */

const METRIKA_ID = 106718528;

/** Отправка цели в Метрику. Отсутствие счётчика (блокировщик, локальная среда) не должно ломать клик. */
function reachGoal(goal, params) {
    try {
        if (typeof window.ym === 'function') {
            window.ym(METRIKA_ID, 'reachGoal', goal, params);
        }
    } catch (e) {
        // Аналитика не критична для интерфейса — молча игнорируем.
    }
}

function deviceType() {
    const width = window.innerWidth;

    if (width < 768) {
        return 'mobile';
    }

    return width < 1280 ? 'tablet' : 'desktop';
}

function initPanels(page) {
    if (!page) {
        return;
    }

    page.querySelectorAll('[data-panel-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.panelToggle;
            page.dataset.panel = page.dataset.panel === target ? 'none' : target;
        });
    });

    page.querySelectorAll('[data-panel-close]').forEach((button) => {
        button.addEventListener('click', () => {
            page.dataset.panel = 'none';
        });
    });

    const overlay = page.querySelector('[data-panel-overlay]');
    overlay?.addEventListener('click', () => {
        page.dataset.panel = 'none';
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            page.dataset.panel = 'none';
        }
    });

    const profileMenu = page.querySelector('.auth-profile-menu');
    const profileTrigger = profileMenu?.querySelector('.auth-profile-trigger');

    profileTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = profileMenu.classList.toggle('open');
        profileTrigger.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', (event) => {
        if (profileMenu && !profileMenu.contains(event.target)) {
            profileMenu.classList.remove('open');
            profileTrigger?.setAttribute('aria-expanded', 'false');
        }
    });
}


/**
 * #181 Планшетная раскладка (768–1279px). В эталоне это отдельный файл, где правая колонка
 * разобрана: блок «Актуальное в Bizzio» встаёт сразу после «Добро пожаловать» (у гостя) или
 * после карточки быстрых действий (у авторизованного), а «Реклама» и «Новости» уходят в конец
 * средней колонки. У нас разметка одна на десктоп и планшет, поэтому переносим узлы на лету
 * и возвращаем на место при возврате к десктопу.
 */
function initTabletReflow(page) {
    const content = page.querySelector('.content');
    const columns = content ? content.querySelectorAll(':scope > .column') : [];

    if (columns.length < 3) {
        return;
    }

    const main = columns[1];
    const side = columns[2];
    // Исходный порядок правой колонки — по нему собираем её обратно на десктопе.
    const original = [...side.children];

    const query = window.matchMedia('(min-width: 768px) and (max-width: 1279px)');

    const apply = () => {
        if (query.matches) {
            const events = side.querySelector('.events') || main.querySelector('.events');
            const anchor = main.querySelector('.services-card, .guest-services-card, .guest-welcome');

            if (events && anchor && events.parentElement !== main) {
                anchor.insertAdjacentElement('afterend', events);
            }

            [...side.children].forEach((element) => main.appendChild(element));
            side.style.display = 'none';

            return;
        }

        // Возврат к десктопу: собираем правую колонку в исходном порядке.
        side.style.display = '';
        original.forEach((element) => side.appendChild(element));
    };

    apply();
    query.addEventListener('change', apply);
}

/**
 * Элементы v25/v26 без действующей функции остаются видимыми и кликабельными,
 * но никуда не ведут — по ТЗ это способ измерить спрос, а не заявка на разработку.
 */
function initInactiveFeatures(root, authState) {
    root.querySelectorAll('[data-inactive-feature]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();

            reachGoal('inactive_feature_click', {
                element_id: element.dataset.inactiveFeature,
                element_label: (element.dataset.featureLabel || element.textContent || '').trim().slice(0, 120),
                placement: element.dataset.placement || 'unknown',
                auth_state: authState,
                device_type: deviceType(),
            });
        });
    });
}

/**
 * Интерес к будущему сервису. Кнопка «Мне интересно» лежит внутри карточки,
 * поэтому её клик останавливаем — иначе один физический клик дал бы два события.
 */
function initFutureServices(root, authState) {
    root.querySelectorAll('[data-future-service]').forEach((card) => {
        const send = (interactionType, event) => {
            event.preventDefault();
            event.stopPropagation();

            reachGoal('future_service_interest', {
                service_id: card.dataset.futureService,
                service_name: (card.dataset.serviceName || '').trim(),
                interaction_type: interactionType,
                placement: card.dataset.placement || 'unknown',
                auth_state: authState,
                device_type: deviceType(),
            });
        };

        const button = card.querySelector('[data-interest-button]');
        button?.addEventListener('click', (event) => send('interest_button', event));
        card.addEventListener('click', (event) => send('service_card', event));
    });
}

/**
 * Мобильная версия эталона переключает панели иначе: состояние живёт в атрибуте
 * data-open на #bizzio-mobile-v1, а CSS ловит его селекторами вида
 * #bizzio-mobile-v1[data-open="menu"] .bz-menu-drawer.
 */
function initMobilePanels(mobileRoot) {
    if (!mobileRoot) {
        return;
    }

    mobileRoot.querySelectorAll('[data-open-panel]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.openPanel;
            mobileRoot.dataset.open = mobileRoot.dataset.open === target ? 'none' : target;
        });
    });

    mobileRoot.querySelectorAll('[data-close-panels]').forEach((element) => {
        element.addEventListener('click', () => {
            mobileRoot.dataset.open = 'none';
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            mobileRoot.dataset.open = 'none';
        }
    });

    // Меню профиля в шапке: открытое состояние — класс bz-open (как в эталоне).
    const profileMenu = mobileRoot.querySelector('.bz-profile-menu');
    const profileTrigger = profileMenu?.querySelector('.bz-profile-trigger');

    profileTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = profileMenu.classList.toggle('bz-open');
        profileTrigger.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', (event) => {
        if (profileMenu && !profileMenu.contains(event.target)) {
            profileMenu.classList.remove('bz-open');
            profileTrigger?.setAttribute('aria-expanded', 'false');
        }
    });
}

/**
 * #181 Подпись поиска в шапке. На планшете полная фраза не помещается в поле и вылезает
 * за его границы, поэтому там показываем короткий вариант. Работает и для <input>
 * (placeholder), и для <span> гостевой шапки.
 */
function initSearchPlaceholder(root) {
    const targets = root.querySelectorAll('[data-placeholder-full][data-placeholder-short]');

    if (!targets.length) {
        return;
    }

    const compact = window.matchMedia('(max-width: 1279px)');

    const apply = () => {
        targets.forEach((element) => {
            const text = compact.matches
                ? element.dataset.placeholderShort
                : element.dataset.placeholderFull;

            if (element.tagName === 'INPUT') {
                element.placeholder = text;
            } else {
                element.textContent = text;
            }
        });
    };

    apply();
    compact.addEventListener('change', apply);
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-v26-root]');

    if (!root) {
        return;
    }

    const authState = root.dataset.authState || 'guest';

    root.querySelectorAll('.page').forEach((page) => {
        initPanels(page);
        initTabletReflow(page);
    });
    initSearchPlaceholder(root);
    initMobilePanels(root.querySelector('#bizzio-mobile-v1'));
    initInactiveFeatures(root, authState);
    initFutureServices(root, authState);
});
