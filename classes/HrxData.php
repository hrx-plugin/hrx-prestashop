<?php

use \setasign\Fpdi\Fpdi;
use Mijora\Hrx\DVDoug\BoxPacker\ItemList;
use Mijora\Hrx\DVDoug\BoxPacker\PackedBox;
use Mijora\Hrx\DVDoug\BoxPacker\ParcelBox;
use Mijora\Hrx\DVDoug\BoxPacker\ParcelItem;
use Mijora\Hrx\DVDoug\BoxPacker\VolumePacker;

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
        if ($page == 1) {
            HrxDeliveryTerminal::disableAll();
            Tools::deleteDirectory(HrxDelivery::$_moduleDir . self::$_terminalsList['directory']);
            mkdir(HrxDelivery::$_moduleDir . self::$_terminalsList['directory'], 0777, true);
        }
        $locations_by_country = [];
        $result = HrxAPIHelper::getDeliveryLocations($page);

        if (isset($result['error']))
        return $result;

        if (!$result || empty($result))
            return false;

        $db_push = [];
        foreach ($result as $terminal) {
            if (isset($terminal['country']) && (int)$terminal['latitude'] != 0 && (int)$terminal['longitude'] != 0) {
                $terminal['identifier'] = 'hrx_' . strtolower($terminal['country']);
                $terminal['coords']['lat'] = $terminal['latitude'];
                $terminal['coords']['lng'] = $terminal['longitude'];
                $locations_by_country[$terminal['country']][$terminal['id']] = $terminal;

                $db_push[] = $terminal;
            }
        }

        HrxDeliveryTerminal::massAdd($db_push);

        $counter = count($result);

        return ['counter' => $counter];
    }

    public static function updateCourierPoints($page)
    {
        if ($page == 1) {
            HrxDeliveryCourier::disableAll();
        }

        $result = HrxAPIHelper::getCourierDeliveryLocations();

        if (isset($result['error']))
        return $result;

        if (!$result || empty($result))
            return false;

        $result = array_filter($result, function ($item) {
            return isset($item['country']) && $item['country'];
        });

        HrxDeliveryCourier::massAdd($result);

        return [
            'counter' => count($result)
        ];
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

    public static function getTerminalsByCountry($country_code, $item_list = null)
    {
        return HrxDeliveryTerminal::getLocationListByCountry($country_code);
    }

    /**
     * @param string $country_code
     * @param Object $order with package dimensions info
     */
    public static function getTerminalsByDimensionsAndCity($country_code, $order, $selected = null)
    {
        $terminals = self::getTerminalsByCountry($country_code);
        
        if (!$selected || !isset($selected->latitude) || !isset($selected->longitude)) {
            return $terminals;
        }

        $filtered_terminals = [];

        // look around from currently selected terminal
        $lat_min = $selected->latitude - 0.25;
        $lat_max = $selected->latitude + 0.25;
        $lng_min = $selected->longitude - 0.5;
        $lng_max = $selected->longitude + 0.5;

        foreach($terminals as $terminal)
        {
            if(
                (float) $terminal['latitude'] >= $lat_min && (float) $terminal['latitude'] <= $lat_max
                && (float) $terminal['longitude'] >= $lng_min && (float) $terminal['longitude'] <= $lng_max
            ){
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

    public static function getItemListFromProductList($cart_products)
    {
        $item_list = new ItemList();
        $convert_to_cm = Configuration::get('PS_DIMENSION_UNIT') === 'm' ? 100 : 1;

        $default_dimmensions = HrxData::getDefaultDimmensions();

        foreach ($cart_products as $cart_product) {
            // ignore virtual products
            if (isset($cart_product['is_virtual']) && (bool) $cart_product['is_virtual']) {
                continue;
            }

            $weight = (float) $cart_product['weight'];
            if (isset($cart_product['weight_attribute']) && $cart_product['weight_attribute'] > 0) {
                $weight = (float) $cart_product['weight_attribute'];
            }

            $dimmensions = HrxData::determineProductDimensions([
                ParcelBox::DIMENSION_WIDTH => (float) $cart_product['width'] * $convert_to_cm,
                ParcelBox::DIMENSION_HEIGHT => (float) $cart_product['height'] * $convert_to_cm,
                ParcelBox::DIMENSION_LENGTH => (float) $cart_product['depth'] * $convert_to_cm,
                ParcelBox::DIMENSION_WEIGHT => $weight,
            ], $default_dimmensions);

            $item_list->insert(
                new ParcelItem(
                    $dimmensions[ParcelBox::DIMENSION_LENGTH],
                    $dimmensions[ParcelBox::DIMENSION_WIDTH],
                    $dimmensions[ParcelBox::DIMENSION_HEIGHT],
                    $dimmensions[ParcelBox::DIMENSION_WEIGHT],
                    'product_' . $cart_product['id_product'] . '-' . $cart_product['id_product_attribute']
                ),
                (int) $cart_product['cart_quantity']
            );
        }

        return $item_list;
    }

    public static function getDefaultDimmensions()
    {
        return [
            ParcelBox::DIMENSION_WIDTH => Configuration::get(HrxDelivery::$_configKeys['DELIVERY']['w']), 
            ParcelBox::DIMENSION_HEIGHT => Configuration::get(HrxDelivery::$_configKeys['DELIVERY']['h']), 
            ParcelBox::DIMENSION_LENGTH => Configuration::get(HrxDelivery::$_configKeys['DELIVERY']['l']),
            ParcelBox::DIMENSION_WEIGHT => Configuration::get(HrxDelivery::$_configKeys['DELIVERY']['weight']),
        ];
    }

    public static function determineProductDimensions($product_dimmensions, $default_dimmensions = null)
    {
        if (!$default_dimmensions) {
            $default_dimmensions = self::getDefaultDimmensions();
        }

        return [
            ParcelBox::DIMENSION_WIDTH => $product_dimmensions[ParcelBox::DIMENSION_WIDTH] ? $product_dimmensions[ParcelBox::DIMENSION_WIDTH] : $default_dimmensions[ParcelBox::DIMENSION_WIDTH],
            ParcelBox::DIMENSION_HEIGHT => $product_dimmensions[ParcelBox::DIMENSION_HEIGHT] ? $product_dimmensions[ParcelBox::DIMENSION_HEIGHT] : $default_dimmensions[ParcelBox::DIMENSION_HEIGHT],
            ParcelBox::DIMENSION_LENGTH => $product_dimmensions[ParcelBox::DIMENSION_LENGTH] ? $product_dimmensions[ParcelBox::DIMENSION_LENGTH] : $default_dimmensions[ParcelBox::DIMENSION_LENGTH],
            ParcelBox::DIMENSION_WEIGHT => $product_dimmensions[ParcelBox::DIMENSION_WEIGHT] ? $product_dimmensions[ParcelBox::DIMENSION_WEIGHT] : $default_dimmensions[ParcelBox::DIMENSION_WEIGHT],
        ];
    }

    public static function getMinWeight($location)
    {
        return (float) (isset($location['min_weight_kg']) ? $location['min_weight_kg'] : 0);
    }

    public static function getMaxWeight($location)
    {
        return (float) (isset($location['max_weight_kg']) ? $location['max_weight_kg'] : 0);
    }

    public static function getMinDimensions($location, $formated = false)
    {
        $length = (float) (isset($location['min_length_cm']) ? $location['min_length_cm'] : 0);
        $width = (float) (isset($location['min_width_cm']) ? $location['min_width_cm'] : 0);
        $height = (float) (isset($location['min_height_cm']) ? $location['min_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            ParcelBox::DIMENSION_LENGTH => $length,
            ParcelBox::DIMENSION_WIDTH => $width,
            ParcelBox::DIMENSION_HEIGHT => $height
        ];
    }

    public static function getMaxDimensions($location, $formated = false)
    {
        $length = (float) (isset($location['max_length_cm']) ? $location['max_length_cm'] : 0);
        $width = (float) (isset($location['max_width_cm']) ? $location['max_width_cm'] : 0);
        $height = (float) (isset($location['max_height_cm']) ? $location['max_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            ParcelBox::DIMENSION_LENGTH => $length,
            ParcelBox::DIMENSION_WIDTH => $width,
            ParcelBox::DIMENSION_HEIGHT => $height
        ];
    }

    public static function getPackedBox($delivery_point, ItemList $item_list): PackedBox
    {
        $dimensions_array = self::getMaxDimensions($delivery_point, false);

        $max_length = $dimensions_array[ParcelBox::DIMENSION_LENGTH] === 0.0 ? ParcelBox::UNLIMITED : $dimensions_array[ParcelBox::DIMENSION_LENGTH];
        $max_width = $dimensions_array[ParcelBox::DIMENSION_WIDTH] === 0.0 ? ParcelBox::UNLIMITED : $dimensions_array[ParcelBox::DIMENSION_WIDTH];
        $max_height = $dimensions_array[ParcelBox::DIMENSION_HEIGHT] === 0.0 ? ParcelBox::UNLIMITED : $dimensions_array[ParcelBox::DIMENSION_HEIGHT];
        $max_weight = self::getMaxWeight($delivery_point) === 0.0 ? ParcelBox::UNLIMITED : self::getMaxWeight($delivery_point);

        $box = new ParcelBox(
            $max_length,
            $max_width,
            $max_height,
            $max_weight,
            self::getMaxDimensions($delivery_point, true) . ' ' . $max_weight // using formated dimensions string as reference + max weight
        );

        $packer = new VolumePacker($box, $item_list);

        return $packer->pack();
    }

    public static function doesParcelFitBox($delivery_point, ItemList $item_list)
    {
        $packed_box = self::getPackedBox($delivery_point, $item_list);

        return $packed_box->getItems()->count() === $item_list->count();
    }

    public static function bulkPrintLabels($order_ids, $label_type)
    {
        if(empty($order_ids))
            return;

        $pdfs = [];

        if($label_type == 'shipment')
            $label_directory = HrxDelivery::$_labelPdfDir;
        else
            $label_directory = HrxDelivery::$_returnLabelPdfDir;

        foreach($order_ids as $id)
        {
            $hrxOrder = new HrxOrder($id);
            if(Validate::isLoadedObject($hrxOrder) && $hrxOrder->id_hrx != '')
            {
                $response = HrxAPIHelper::getLabel($label_type, $hrxOrder->id_hrx);

                if(isset($response['error']))
                {
                    $result['errors'] = $response['error'];
                }
                else
                {
                    $file_content = $response['file_content'] ?? '';

                    if($file_content){
                        $pdfs[] = $response['file_content'];
                    }
                }
            }
        }

        $res = self::mergePdf($pdfs);

        $filename = implode(",", $order_ids) . '.pdf';
        $file_path =  _PS_MODULE_DIR_ . $label_directory . $filename;

        self::printPdf($file_path, $filename, $res);
    }

    private static function mergePdf($pdfs) {
        $pageCount = 0;
        // initiate FPDI
        $pdf = new Fpdi();

        foreach ($pdfs as $data) {
            $name = tempnam("/tmp", "tmppdf");
            $handle = fopen($name, "w");
            fwrite($handle, base64_decode($data));
            fclose($handle);

            $pageCount = $pdf->setSourceFile($name);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);

                $pdf->AddPage('P');

                $pdf->useTemplate($templateId, ['adjustPageSize' => true]);
            }
        }
        return $pdf->Output('S');
    }

    private static function printPdf($path, $filename, $pdf_data)
    {
        // make sure there is nothing before headers
        if (ob_get_level()) ob_end_clean();
        header("Content-Type: application/pdf; name=\" " . $filename . ".pdf");
        header("Content-Transfer-Encoding: binary");
        // disable caching on client and proxies, if the download content vary
        header("Expires: 0");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        echo $pdf_data;
    }
}