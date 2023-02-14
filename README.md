# PaykeeperYii2
1) Инициализация:
``` PHP
$class = new Paykeeper('admin', '12345', 'server');
```
Вместо **server** нужно указать тот сервер, что в ЛК paykeeper. Например, test.server.paykeeper.ru

2) Формирование необходимых заголовков
``` PHP
$class->makeHeaders();
```

3) Формирование токена
``` PHP
$class->setSecureToken();
```

4) Получение ссылки на оплату
``` PHP
$link = $class->getInvoiceLink(
    [
        'orderid' => $order_id, // номер заказа
        'clientid'=>Yii::$app->user->identity->username, // ID Клиента, либо его имя
        'client_email'=>Yii::$app->user->identity->email, // Емейл клиента
        'pay_amount' => $sum // сумма платежа
    ]
);
```
![image](https://user-images.githubusercontent.com/10581774/218630510-e78dc2a9-484f-4c25-9909-35e822f77b52.png)


Переключение уведомлений о платежах - в ЛК Paykeeper'а. Если Email, то информация об оплате приходит на указанные в ЛК email'ы.
