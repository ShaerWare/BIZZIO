<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Протокол коммерческого аукциона {{ $auction->number }}</title>
    <style>
        @page { margin: 2cm 2cm 3cm 2cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; padding-bottom: 60px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #28a745; padding-bottom: 10px; }
        .header-logo img { height: 40px; }
        .title { font-size: 15pt; font-weight: bold; margin-bottom: 8px; }
        .info-block { margin-bottom: 15px; }
        .info-row { margin-bottom: 5px; }
        .label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; margin-bottom: 12px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .winner-row { background-color: #d4edda; }
        .num { white-space: nowrap; text-align: right; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 2cm; border-top: 1px solid #28a745; font-size: 9pt; text-align: center; color: #666; }
        .footer .page-number:after { content: counter(page); }
        .footer a { color: #28a745; text-decoration: none; }
    </style>
</head>
<body>
    <div class="footer">
        <span>Bizzio.ru — соединяя бизнес</span> |
        <a href="{{ route('auctions.show', $auction) }}">{{ route('auctions.show', $auction) }}</a>
        <br>
        Стр. <span class="page-number"></span>
    </div>

    <div class="header">
        <div class="header-logo">
            <img src="{{ public_path('images/android-chrome-192x192.png') }}" alt="Bizzio" style="height: 40px;">
        </div>
        <div class="title">ПРОТОКОЛ ПОДВЕДЕНИЯ ПРЕДВАРИТЕЛЬНЫХ ИТОГОВ</div>
        <div>Коммерческий аукцион № {{ $auction->number }}</div>
        <div>от {{ now()->format('d.m.Y H:i') }}</div>
    </div>

    <div class="info-block">
        <div class="info-row"><span class="label">Наименование:</span> {{ $auction->title }}</div>
        <div class="info-row"><span class="label">Организатор:</span> {{ $auction->company->name }}
            @if($auction->company->inn) (ИНН {{ $auction->company->inn }}) @endif
        </div>
        <div class="info-row"><span class="label">Тип процедуры:</span> {{ $auction->type === 'open' ? 'Открытая' : 'Закрытая' }}</div>
        <div class="info-row"><span class="label">Начальная (максимальная) цена:</span>
            <span class="num">{{ number_format($auction->starting_price, 2, ',', ' ') }} {{ $auction->currency_symbol }}</span></div>
        <div class="info-row"><span class="label">Веса критериев:</span>
            цена {{ (float) $auction->weight_price }}% · срок {{ (float) $auction->weight_deadline }}% · аванс {{ (float) $auction->weight_advance }}%</div>
        <div class="info-row"><span class="label">Дата начала торгов:</span>
            {{ optional($auction->trading_start)->format('d.m.Y H:i') }}</div>
        <div class="info-row"><span class="label">Дата завершения:</span>
            {{ $auction->trading_end ? $auction->trading_end->format('d.m.Y H:i') : now()->format('d.m.Y H:i') }}</div>
    </div>

    {{-- #191 Общее количество компаний-участников, сделавших предложения --}}
    <div class="info-row" style="margin-bottom: 12px;">
        <span class="label">Общее количество компаний-участников:</span> {{ $history->pluck('company_id')->unique()->count() }}
    </div>

    @if($winner)
        <div class="info-block">
            <h3>Победитель:</h3>
            <div class="info-row"><span class="label">Компания:</span> {{ $winner->company->legalNameWithInn() }}</div>
            <div class="info-row"><span class="label">Код участника:</span> {{ $winner->anonymous_code }}</div>
            <div class="info-row"><span class="label">Итоговая цена:</span>
                <span class="num">{{ number_format($winner->price, 2, ',', ' ') }} {{ $auction->currency_symbol }}</span></div>
            <div class="info-row"><span class="label">Срок выполнения:</span> {{ $winner->deadline }} дн.</div>
            <div class="info-row"><span class="label">Размер аванса:</span> {{ (float) $winner->advance_percent }}%</div>
            <div class="info-row"><span class="label">Итоговый рейтинг:</span> {{ number_format((float) $winner->total_score, 2, ',', ' ') }}</div>
        </div>
    @else
        <div class="info-block"><p><strong>Победитель не определён (предложения не подавались) — процедура признана несостоявшейся.</strong></p></div>
    @endif

    <h3>История лучших предложений:</h3>
    @if($history->isEmpty())
        <p>Предложения не подавались.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 6%;">№</th>
                    <th style="width: 10%;">Код</th>
                    <th style="width: 30%;">Компания (ИНН)</th>
                    <th class="num" style="width: 18%;">Цена, {{ $auction->currency_symbol }}</th>
                    <th class="num" style="width: 8%;">Срок</th>
                    <th class="num" style="width: 8%;">Аванс</th>
                    <th class="num" style="width: 10%;">Рейтинг</th>
                    <th style="width: 10%;">Время</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $index => $offer)
                    <tr class="{{ $offer->id === $winner?->id ? 'winner-row' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td style="font-weight: bold;">{{ $offer->anonymous_code }}</td>
                        <td>{{ $offer->company->name }}@if($offer->company->inn) <br><small>ИНН {{ $offer->company->inn }}</small>@endif</td>
                        <td class="num" style="font-weight: bold;">{{ number_format($offer->price, 2, ',', ' ') }}</td>
                        <td class="num">{{ $offer->deadline }} дн.</td>
                        <td class="num">{{ (float) $offer->advance_percent }}%</td>
                        <td class="num">{{ number_format((float) $offer->total_score, 2, ',', ' ') }}</td>
                        <td>{{ optional($offer->became_best_at)->format('d.m H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- #191 Оговорка об информационном характере результатов --}}
    <div class="info-block" style="margin-top: 25px; font-size: 9pt; color: #333; text-align: justify;">
        Результаты проведения коммерческого аукциона являются информационными и не обязывают Заказчика
        к заключению договора, окончательное решение о выборе поставщика и заключении договора принимается
        Заказчиком на основе комплексной оценки коммерческих предложений.
    </div>
</body>
</html>
