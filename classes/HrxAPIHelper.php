<?php

use HrxApi\API;
use HrxApi\Order;
use HrxApi\Receiver;
use HrxApi\Shipment;

class HrxAPIHelper
{
    private static $instance = null;
  
    private function __construct()
    {
        $token = Configuration::get(HrxDelivery::$_configKeys['API']['token']);
        self::$instance = new API($token, true, false);
    }
    
    private static function getInstance()
    {
        if (self::$instance == null)
        {
            new HrxAPIHelper();
        }
    
        return self::$instance;
    }

    public static function getPickupLocations($page = null)
    {
        try {
            $instance = self::getInstance();
            $response = $instance->getPickupLocations($page);
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    public static function getDeliveryLocations($page = null)
    {
        try {
            $instance = self::getInstance();
            $response = $instance->getDeliveryLocations($page);
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    public static function getOrder($id_order)
    {
        try {
            $instance = self::getInstance();
            $response = $instance->getOrder($id_order);
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    public static function cancelOrder($id_order)
    {
        try {
            $instance = self::getInstance();
            $response = $instance->cancelOrder($id_order);
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    public static function updateReadyState($id_order)
    {
        try {
            $instance = self::getInstance();
            $response = $instance->changeOrderReadyState($id_order, true);
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    public static function createOrder($pickup_location_id, $delivery_location, $customerData, $shipmentData, $kind)
    {
        try {
            $receiver = new Receiver();
            $receiver->setName($customerData['name']);
            $receiver->setEmail($customerData['email']);
            $receiver->setPhone($customerData['phone'], $delivery_location['recipient_phone_regexp']);
            $receiver->setPostcode($customerData['postcode']);
            $receiver->setCity($customerData['city']);
            $receiver->setCountry($customerData['country']);
            $receiver->setAddress($customerData['address']);

            $shipment = new Shipment();
            $shipment->setReference($shipmentData['reference']);
            $shipment->setComment($shipmentData['comment']);
            $shipment->setLength($shipmentData['l']);
            $shipment->setWidth($shipmentData['w']);
            $shipment->setHeight($shipmentData['h']);
            $shipment->setWeight($shipmentData['weight']);

            $order = new Order();
            $order->setPickupLocationId($pickup_location_id);

            if($kind == HrxDelivery::$_carriers[HrxDelivery::CARRIER_TYPE_PICKUP]['kind']){
                $order->setDeliveryLocation($delivery_location['id']);
            }
            
            $order->setReceiver($receiver);
            $order->setShipment($shipment);
            
            $order->setDeliveryKind($kind);

            $order_data = $order->prepareOrderData();
            //sending order
        
            $instance = self::getInstance();
            $order_response = $instance->generateOrder($order_data);
            $response['success'] = isset($order_response['id']) ? $order_response : false;
        } 
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    public static function getLabel($type, $id_order)
    {
        try {
            $instance = self::getInstance();
            
            if($type == 'shipment')
                $response = $instance->getLabel($id_order);
            else
                $response = $instance->getReturnLabel($id_order);
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }
    
    public static function getCourierDeliveryLocations()
    {
        try {
            $instance = self::getInstance();
            $response = $instance->getCourierDeliveryLocations();
        }
        catch (Exception $e) 
        {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }
}