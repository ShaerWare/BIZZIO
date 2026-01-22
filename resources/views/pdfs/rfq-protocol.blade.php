<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Протокол подведения итогов</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-block strong {
            display: inline-block;
            width: 200px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .winner {
            background-color: #d4edda;
        }
        .footer {
            margin-top: 40px;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ПРОТОКОЛ ПОДВЕДЕНИЯ ИТОГОВ</h1>
        <p>Запрос котировок № {{ $rfq->number }}</p>
        <p>Дата формирования: {{ $generatedAt }}</p>
    </div>

    <div class="info-block">
        <p><strong>Название:</strong> {{ $rfq->title }}</p>
        <p><strong>Организатор:</strong> {{ $rfq->company->name }} (ИНН: {{ $rfq->company->inn }})</p>
        <p><strong>Дата начала:</strong> {{ $rfq->start_date->format('d.m.Y H:i') }}</p>
        <p><strong>Дата окончания:</strong> {{ $rfq->end_date->format('d.m.Y H:i') }}</p>
        <p><strong>Тип процедуры:</strong> {{ $rfq->type === 'open' ? 'Открытая' : 'Закрытая' }}</p>
    </div>

    <div class="info-block">
        <h3>Критерии оценки:</h3>
        <ul>
            <li>Цена (руб. без НДС) — {{ $rfq->weight_price }}%</li>
            <li>Срок выполнения (календарные дни) — {{ $rfq->weight_deadline }}%</li>
            <li>Размер аванса (%) — {{ $rfq->weight_advance }}%</li>
        </ul>

        {{-- T5: Формула расчёта балла --}}
        <h4 style="margin-top: 15px;">Формула расчёта итогового балла:</h4>
        <ul style="font-size: 11px;">
            <li><em>Балл за цену</em> = 100 × (минимальная цена / цена заявки)</li>
            <li><em>Балл за срок</em> = 100 × (минимальный срок / срок заявки)</li>
            <li><em>Балл за аванс</em> = 100 − (аванс заявки / максимальный аванс) × 100</li>
        </ul>
        <p style="font-size: 11px; margin-top: 5px;">
            <strong>Итоговый балл</strong> = (Б<sub>цена</sub> × {{ $rfq->weight_price }}% + Б<sub>срок</sub> × {{ $rfq->weight_deadline }}% + Б<sub>аванс</sub> × {{ $rfq->weight_advance }}%) / 100
        </p>
    </div>

    @if($rfq->bids->isNotEmpty())
        <h3>Результаты оценки заявок:</h3>
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Компания-участник</th>
                    <th>Цена (руб.)</th>
                    <th>Срок (дн.)</th>
                    <th>Аванс (%)</th>
                    <th>Баллы за цену</th>
                    <th>Баллы за срок</th>
                    <th>Баллы за аванс</th>
                    <th>Итоговый балл</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rfq->bids->sortByDesc('total_score') as $index => $bid)
                    <tr class="{{ $bid->status === 'winner' ? 'winner' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $bid->company->name }}</td>
                        <td>{{ number_format($bid->price, 2, ',', ' ') }}</td>
                        <td>{{ $bid->deadline }}</td>
                        <td>{{ $bid->advance_percent }}</td>
                        <td>{{ number_format($bid->score_price, 2, ',', ' ') }}</td>
                        <td>{{ number_format($bid->score_deadline, 2, ',', ' ') }}</td>
                        <td>{{ number_format($bid->score_advance, 2, ',', ' ') }}</td>
                        <td><strong>{{ number_format($bid->total_score, 2, ',', ' ') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($rfq->winnerBid)
            <div class="info-block">
                <h3>Победитель:</h3>
                <p><strong>Компания:</strong> {{ $rfq->winnerBid->company->name }} (ИНН: {{ $rfq->winnerBid->company->inn }})</p>
                <p><strong>Итоговый балл:</strong> {{ number_format($rfq->winnerBid->total_score, 2, ',', ' ') }}</p>
                <p><strong>Предложение:</strong></p>
                <ul>
                    <li>Цена: {{ number_format($rfq->winnerBid->price, 2, ',', ' ') }} руб.</li>
                    <li>Срок: {{ $rfq->winnerBid->deadline }} дней</li>
                    <li>Аванс: {{ $rfq->winnerBid->advance_percent }}%</li>
                </ul>
            </div>
        @endif
    @else
        <p><em>Заявок не поступило</em></p>
    @endif

    <div class="footer">
        <p>Документ сформирован автоматически системой Bizzio.ru</p>
        <p>Запрос котировок № {{ $rfq->number }} | {{ $generatedAt }}</p>
    </div>
</body>
</html>