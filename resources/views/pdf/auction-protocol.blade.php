<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Протокол аукциона {{ $auction->number }}</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info-block {
            margin-bottom: 15px;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .winner-row {
            background-color: #d4edda;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #000;
            font-size: 10pt;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Заголовок -->
    <div class="header">
        <div class="title">ПРОТОКОЛ ПОДВЕДЕНИЯ ИТОГОВ</div>
        <div>Аукцион № {{ $auction->number }}</div>
        <div>от {{ now()->format('d.m.Y H:i') }}</div>
    </div>

    <!-- Основная информация об аукционе -->
    <div class="info-block">
        <div class="info-row">
            <span class="label">Название аукциона:</span> {{ $auction->title }}
        </div>
        <div class="info-row">
            <span class="label">Организатор:</span> {{ $auction->company->name }}
        </div>
        <div class="info-row">
            <span class="label">Тип процедуры:</span> {{ $auction->type === 'open' ? 'Открытая' : 'Закрытая' }}
        </div>
        <div class="info-row">
            <span class="label">Начальная (максимальная) цена:</span> {{ number_format($auction->starting_price, 2, '.', ' ') }} {{ $auction->currency_symbol }}
        </div>
        <div class="info-row">
            <span class="label">Шаг аукциона:</span> {{ $auction->step_percent }}%
        </div>
        <div class="info-row">
            <span class="label">Дата проведения торгов:</span> {{ $auction->trading_start->format('d.m.Y H:i') }}
        </div>
        <div class="info-row">
            <span class="label">Дата закрытия:</span> {{ $auction->trading_end ? $auction->trading_end->format('d.m.Y H:i') : now()->format('d.m.Y H:i') }}
        </div>
    </div>

    <!-- Таблица результатов -->
    <h3>Результаты торгов:</h3>
    
    @if($bids->isEmpty())
        <p>Ставки не были поданы.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">№</th>
                    <th style="width: 20%;">Код участника</th>
                    <th style="width: 40%;">Компания</th>
                    <th style="width: 20%;">Цена, {{ $auction->currency_symbol }}</th>
                    <th style="width: 10%;">Время ставки</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bids as $index => $bid)
                    <tr class="{{ $bid->id === $winner?->id ? 'winner-row' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td style="font-weight: bold;">{{ $bid->anonymous_code }}</td>
                        <td>{{ $bid->company->name }}</td>
                        <td style="font-weight: bold;">{{ number_format($bid->price, 2, '.', ' ') }}</td>
                        <td>{{ $bid->created_at->format('H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Победитель -->
    @if($winner)
        <div class="info-block" style="margin-top: 20px;">
            <h3>Победитель аукциона:</h3>
            <div class="info-row">
                <span class="label">Компания:</span> {{ $winner->company->name }}
            </div>
            <div class="info-row">
                <span class="label">Код участника:</span> {{ $winner->anonymous_code }}
            </div>
            <div class="info-row">
                <span class="label">Итоговая цена:</span> {{ number_format($winner->price, 2, '.', ' ') }} {{ $auction->currency_symbol }}
            </div>
            <div class="info-row">
                <span class="label">Снижение от начальной цены:</span> {{ number_format($auction->starting_price - $winner->price, 2, '.', ' ') }} {{ $auction->currency_symbol }} 
                ({{ number_format((($auction->starting_price - $winner->price) / $auction->starting_price) * 100, 2) }}%)
            </div>
        </div>
    @else
        <div class="info-block" style="margin-top: 20px;">
            <p><strong>Победитель не определён (отсутствуют ставки).</strong></p>
        </div>
    @endif

    <!-- Футер -->
    <div class="footer">
        <p>Протокол сгенерирован автоматически на платформе Bizzio.ru</p>
        <p>{{ now()->format('d.m.Y H:i:s') }}</p>
    </div>
</body>
</html>