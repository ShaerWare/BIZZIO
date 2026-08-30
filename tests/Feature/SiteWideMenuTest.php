<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #181 Новое меню на всех страницах.
 *
 * Заказчик просил распространить шапку v26 на все разделы, а прежние выпадающие меню
 * второго уровня («Закупки», «Новости») перенести в меню раздела. Тест держит это правило:
 * шапка присутствует на внутренних страницах, состав меню одинаков, старой Tailwind-навигации
 * с выпадающими меню больше нет.
 */
class SiteWideMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $company->assignModerator($this->user, 'owner');
    }

    /** @return list<string> страницы разных разделов, включая главную */
    private function pages(): array
    {
        return ['/', '/tenders', '/companies', '/projects', '/news', '/friends'];
    }

    public function test_every_section_renders_v26_chrome(): void
    {
        foreach ($this->pages() as $page) {
            $html = $this->actingAs($this->user)->get($page)->assertOk()->getContent();

            $this->assertStringContainsString('data-panel-toggle="menu"', $html, "Нет кнопки «Меню» на {$page}");
            $this->assertStringContainsString('data-panel-toggle="services"', $html, "Нет кнопки «Сервисы» на {$page}");
            $this->assertStringContainsString('data-open-panel="menu"', $html, "Нет мобильного меню на {$page}");
        }
    }

    public function test_second_level_menus_moved_into_section_menu(): void
    {
        // Пункты прежних выпадающих меню «Закупки» и «Новости» доступны из меню раздела.
        $html = $this->actingAs($this->user)->get('/companies')->assertOk()->getContent();

        foreach (['Найти закупку', 'Создать закупку', 'Мои закупки', 'Мои приглашения', 'Мои заявки', 'Правила закупок', 'Лента новостей', 'Ключевые слова'] as $item) {
            $this->assertStringContainsString($item, $html, "В меню раздела нет пункта «{$item}»");
        }
    }

    public function test_old_tailwind_navigation_is_gone(): void
    {
        $html = $this->actingAs($this->user)->get('/tenders')->assertOk()->getContent();

        // Старая навигация рисовала пункты через x-nav-link и держала выпадающие меню
        // на Alpine внутри <nav x-data="{ open: false }">.
        $this->assertStringNotContainsString('<nav x-data="{ open: false }"', $html);
    }

    public function test_guest_sees_same_menu_on_public_pages(): void
    {
        foreach (['/companies', '/projects', '/news', '/tenders'] as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            $this->assertStringContainsString('data-panel-toggle="menu"', $html, "Нет меню для гостя на {$page}");
            $this->assertStringContainsString('guest-login', $html, "Нет кнопки «Войти» на {$page}");
        }
    }

    public function test_menu_items_are_identical_on_home_and_inner_pages(): void
    {
        $home = $this->actingAs($this->user)->get('/')->assertOk()->getContent();
        $inner = $this->actingAs($this->user)->get('/companies')->assertOk()->getContent();

        // Состав берётся из общего партиала, поэтому пункты должны совпадать.
        foreach (['Найти закупку', 'Мои заявки', 'Правила закупок', 'Лента новостей'] as $item) {
            $this->assertStringContainsString($item, $home, "На главной нет пункта «{$item}»");
            $this->assertStringContainsString($item, $inner, "На внутренней странице нет пункта «{$item}»");
        }
    }

    /**
     * #181 Стили шапки собираются из v26.css выборкой по именам классов, и правило,
     * не попавшее в выборку, ломает вид молча — тестами разметки такое не ловится.
     * Поэтому сверяем каждый класс отрендеренной шапки с таблицей стилей.
     */
    public function test_every_chrome_class_has_styles(): void
    {
        $html = $this->actingAs($this->user)->get('/companies')->assertOk()->getContent();

        $start = strpos($html, '<div class="v26-chrome"');
        $end = strpos($html, '<div class="v26-page-body">');
        $chrome = substr($html, $start, $end - $start);

        $css = file_get_contents(resource_path('css/v26-chrome.css'));

        // Служебные обёртки этого проекта, а не эталона.
        $ownClasses = ['v26-chrome', 'v26-desktop', 'v26-page-body', 'current'];

        preg_match_all('/class="([^"]*)"/', $chrome, $matches);
        $missing = [];

        foreach ($matches[1] as $attribute) {
            foreach (preg_split('/\s+/', trim($attribute)) as $class) {
                if ($class === '' || in_array($class, $ownClasses, true)) {
                    continue;
                }

                if (! preg_match('/\.'.preg_quote($class, '/').'[^a-zA-Z0-9_-]/', $css)) {
                    $missing[$class] = true;
                }
            }
        }

        $this->assertSame([], array_keys($missing), 'В v26-chrome.css нет правил для классов шапки: '.implode(', ', array_keys($missing)));
    }

    /**
     * #181 Базовые правила эталона (обводка иконок, box-sizing, ширина .page) заданы
     * в v26.css глобально и в выборку по именам классов не попадают — они дописаны
     * вручную. Тест держит их на месте: без них иконки заливаются чёрным, а шапка
     * теряет ширину.
     */
    public function test_chrome_keeps_base_rules(): void
    {
        $css = file_get_contents(resource_path('css/v26-chrome.css'));

        $this->assertStringContainsString('.v26-chrome svg{fill:none;stroke:currentColor', $css);
        $this->assertStringContainsString('.v26-chrome *{box-sizing:border-box}', $css);
        $this->assertStringContainsString('.v26-chrome .page{', $css);
        $this->assertStringContainsString('--bz-header-height', $css);
    }
}
