<?php

class HrxData
{
    /**
     * File info for terminals list
     */
    private static $_terminalsList = array(
        'directory' => 'data',
        'file_name' => 'terminals_%s.json', // %s - for country code
    );

    private static $_courierDeliveryLocations = array(
        'directory' => 'data',
        'file_name' => 'courierDeliveryLocations.json', // %s - for country code
    );

    public static function updateTerminals($page)
    {
        if($page == 1){
            Tools::deleteDirectory(HrxDelivery::$_moduleDir . self::$_terminalsList['directory']);
            mkdir(HrxDelivery::$_moduleDir . self::$_terminalsList['directory'], 0777, true);
        }
        $locations_by_country = [];
        $result = HrxAPIHelper::getDeliveryLocations($page);
        
        if(isset($result['error']))
            return $result;
        
        if(!$result || empty($result))
            return false;
            
        foreach($result as $terminal)
        {
            if(isset($terminal['country']) && (int)$terminal['latitude'] != 0 && (int)$terminal['longitude'] != 0)
            {
                $terminal['coords']['lat'] = $terminal['latitude'];
                $terminal['coords']['lng'] = $terminal['longitude'];
                $locations_by_country[$terminal['country']][$terminal['id']] = $terminal;
            }
        }

        if($locations_by_country)
        {
            foreach($locations_by_country as $country_code => $terminals)
            {
                $file_path = self::getFileDir(self::$_terminalsList['directory'], str_replace('%s', strtoupper($country_code), self::$_terminalsList['file_name']));
                $data_array = [];
                
                if(file_exists($file_path)){
                    $old_data = file_get_contents($file_path);
                    if($old_data){
                        $data_array = json_decode($old_data, true);
                    }
                }
                    
                $all_terminals = array_merge($data_array, $terminals);

                $json_data = json_encode($all_terminals);
                file_put_contents($file_path, $json_data);
            }
        }
        $counter = count($result);

        return ['counter' => $counter];
    }

    public static function updateWarehouses($forced = false)
    {
        $res = true;

        $page = 1;
        $res = Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . HrxWarehouse::$definition['table']);

        while($result = HrxAPIHelper::getPickupLocations($page))
        {
            if(isset($result['error']))
                return $result['error'];
            
            foreach($result as $warehouse)
            {
                $obj = new HrxWarehouse();
                $obj->id_warehouse = $warehouse['id'];
                $obj->name = $warehouse['name'];
                $obj->country = $warehouse['country'];
                $obj->city = $warehouse['city'];
                $obj->zip = self::getZipCode($warehouse['zip']);
                $obj->address = $warehouse['address'];
                $obj->default_warehouse = false;
                $res &= $obj->add();
            }
            
            $page++;
        }
        
        return $res;
    }

    public static function getTerminalsByCountry($country_code)
    {
        $file_path = self::getFileDir(self::$_terminalsList['directory'], str_replace('%s', strtoupper($country_code), self::$_terminalsList['file_name']));
        if(file_exists($file_path)){
            $result = file_get_contents($file_path);
            
            $terminals = json_decode($result, true);

            if($terminals){
                return array_values($terminals);
            }
        }
        return [];
    }

    public static function getCourierDeliveryLocation($country_code)
    {
        $file_path = self::getFileDir(self::$_courierDeliveryLocations['directory'], self::$_courierDeliveryLocations['file_name']);
        
        if(!file_exists($file_path)){
            $delivery_locations = HrxAPIHelper::getCourierDeliveryLocations();
            if(!isset($delivery_locations['errors']))
            {
                $json_data = json_encode($delivery_locations);
                file_put_contents($file_path, $json_data);
            }
        }

        $result = file_get_contents($file_path);
        
        $lcoations = json_decode($result, true);

        foreach($lcoations as $lcoation)
        {
            if($lcoation['country'] == $country_code)
                return $lcoation;
        }

        return [];
    }

    /**
     * @param string $country_code 
     * @param Object $order with package dimensions info
     */
    public static function getTerminalsByDimensionsAndCity($country_code, $order, $selected)
    {
        $terminals = self::getTerminalsByCountry($country_code);
        $filtered_terminals = [];

        foreach($terminals as $terminal)
        {
            if($selected['city'] == $terminal['city'] && self::isFit($terminal, $order)){
                $filtered_terminals[$terminal['id']] = $terminal;
            }
        }
        
        return $filtered_terminals;
    }

    public static function getDeliveryLocationInfo($id, $country_code)
    {
        $terminals = [];
        $file_path = self::getFileDir(self::$_terminalsList['directory'], str_replace('%s', strtoupper($country_code), self::$_terminalsList['file_name']));
        if(file_exists($file_path)){
            $result = file_get_contents($file_path);
            $terminals = json_decode($result, true);
        }
        $terminal = $terminals[$id] ?? false;

        return $terminal;
    }

    private static function getFileDir($directory, $file_name)
    {
        $directory = (substr($directory, 0, 1) === '/') ? substr($directory, 1) : $directory;
        return HrxDelivery::$_moduleDir . $directory . '/' . $file_name;
    }

    public static function getZipCode($str) 
    {
        preg_match('/\d+/', $str, $matches);
        return $matches[0];
    }

    public static function isFit($terminal, $order)
    {
        if(isset($terminal['min_length_cm']) && $order->length < $terminal['min_length_cm']
            || isset($terminal['min_width_cm']) && $order->width < $terminal['min_width_cm'] 
            || isset($terminal['min_height_cm']) && $order->height < $terminal['min_height_cm']
            || isset($terminal['min_weight_kg']) && $order->weight < $terminal['min_weight_kg']
            || isset($terminal['max_length_cm']) && $order->length > $terminal['max_height_cm']
            || isset($terminal['max_width_cm']) && $order->width > $terminal['max_width_cm']
            || isset($terminal['max_height_cm']) && $order->height > $terminal['max_height_cm']
            || isset($terminal['max_weight_kg']) && $order->weight > $terminal['max_weight_kg'])
        {
            return false;
        }
        
        return true;
    }
}