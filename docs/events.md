# События

## Подписка на события

Рекомендуем подписываться на события в методе *onApplicationRun* в модуле.

Подписка на событие (мне кажется, понятно и без объяснений) Ж

```php
Phact::app()->event->on('beforeLogin', function($sender, $user) {
    
});
```

Третьим входящим параметром в метод *on* можно указать класс отправителя события. 

Это может быть удобно, если событие может применяться для различных объектов.


## Вызовы событий

```php
Phact::app()->event->trigger('beforeLogin', [$user]);
```

```php
Phact::app()->event->trigger('someEvent', [$firstParam, $secondParam], $sender);
```