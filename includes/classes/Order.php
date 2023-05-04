<?php
namespace MonoGateway;

class Order {

    protected $order_id = 0;
    protected $amount; //number Y Фиксированная сумма оплаты в минимальных единицах $валюты (копейки для гривны)
    protected $ccy = 980; //number N Цифровой ISO-код валюты, по умолчанию 980 (гривна)
    protected $reference = ""; //string N Референс платежа, определяемый мерчантом
    protected $destination = ""; //string N Назначение платежа
    protected $basketOrder = []; //array [object] N Состав заказа
    protected $redirectUrl;
    protected $webHookUrl;

    public function setId($order_id) {
        $this->order_id = $order_id;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
    }

    public function setCurrency($code) {
        $this->ccy = $code;
    }

    public function setReference($str) {
        $this->reference = $str;
    }

    public function setDestination($str) {
        $this->destination = $str;
    }

    public function setBasketOrder($basket_info) {
        $this->basketOrder = $basket_info;
    }

    public function setRedirectUrl($url) {
        $this->redirectUrl = $url;
    }

    public function setWebHookUrl($url) {
        $this->webHookUrl = $url;
    }


    public function getId(): int
    {
        return $this->order_id;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getCurrency(): int
    {
        return $this->ccy;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getBasketOrder(): array
    {
        return $this->basketOrder;
    }

    public function getRedirectUrl() {
        return $this->redirectUrl;
    }

    public function getWebHookUrl() {
        return $this->webHookUrl;
    }

}