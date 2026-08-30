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
 * #181 Мобильная главная.
 *
 * Мобильная вёрстка эталона живёт на своих классах (скоуп `#bizzio-mobile-v1`), и стоит
 * написать в разметке класс, которого в v26.css нет, как блок остаётся без оформления —
 * внешне это выглядит как «слетевший адаптив». Тест сверяет каждый `bz-*` / `gm-*` класс
 * отрендеренной страницы с таблицей стилей.
 */
class MobileLayoutV26Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    private function member(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id]);
        $company->assignModerator($user, 'owner');

        return $user;
    }

    private function stylesheet(): string
    {
        return file_get_contents(resource_path('css/v26.css'));
    }

    /** @return list<string> классы вида bz-* и gm-*, встреченные в разметке */
    private function prototypeClasses(string $html): array
    {
        preg_match_all('/class="([^"]*)"/', $html, $matches);

        $classes = [];

        foreach ($matches[1] as $attribute) {
            foreach (preg_split('/\s+/', trim($attribute)) as $class) {
                if ($class !== '' && preg_match('/^(bz|gm)-/', $class)) {
                    $classes[$class] = true;
                }
            }
        }

        return array_keys($classes);
    }

    private function assertClassesAreStyled(string $html): void
    {
        $css = $this->stylesheet();
        $missing = [];

        foreach ($this->prototypeClasses($html) as $class) {
            if (! preg_match('/\.'.preg_quote($class, '/').'[^a-zA-Z0-9_-]/', $css)) {
                $missing[] = $class;
            }
        }

        $this->assertSame([], $missing, 'В v26.css нет правил для классов: '.implode(', ', $missing));
    }

    public function test_guest_mobile_markup_uses_only_styled_classes(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertClassesAreStyled($html);
    }

    public function test_authorized_mobile_markup_uses_only_styled_classes(): void
    {
        $html = $this->actingAs($this->member())->get(route('home'))->assertOk()->getContent();

        $this->assertClassesAreStyled($html);
    }

    public function test_mobile_root_keeps_prototype_structure(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // Обёртка .bz-phone обязательна: она компенсирует fixed-шапку и нижнюю навигацию.
        $this->assertStringContainsString('<div id="bizzio-mobile-v1" data-open="none">', $html);
        $this->assertStringContainsString('class="bz-phone"', $html);

        // Панели переключаются атрибутом data-open на корне, а не data-panel, как в десктопе.
        $this->assertStringContainsString('data-open-panel="menu"', $html);
        $this->assertStringContainsString('data-open-panel="services"', $html);
        $this->assertStringContainsString('data-close-panels', $html);
    }

    public function test_bottom_navigation_matches_four_column_grid(): void
    {
        foreach ([null, $this->member()] as $viewer) {
            $request = $viewer ? $this->actingAs($viewer) : $this;
            $html = $request->get(route('home'))->assertOk()->getContent();

            // У гостя нижняя навигация несёт ещё и класс gm-bottom (эталон guest-mobile).
            preg_match('/<nav class="bz-bottom[^"]*".*?<\/nav>/s', $html, $nav);
            $items = substr_count($nav[0] ?? '', 'bz-bottom-item');

            // .bz-bottom — grid из четырёх колонок; пятый пункт ломает сетку.
            $this->assertSame(4, $items, 'В нижней навигации должно быть ровно 4 пункта');
        }
    }
}
