Обратная связь от пользователя Bizzio.ru

ФИО: {{ $senderName }}
Email: {{ $sender->email }}
@if($senderCompany)
Компания: {{ $senderCompany }}
@endif

Сообщение:
{{ $body }}
