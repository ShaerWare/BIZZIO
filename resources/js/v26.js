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

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-v26-root]');

    if (!root) {
        return;
    }

    const authState = root.dataset.authState || 'guest';

    root.querySelectorAll('.page').forEach((page) => initPanels(page));
    initInactiveFeatures(root, authState);
    initFutureServices(root, authState);
});
