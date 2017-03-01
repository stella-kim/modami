<?php
/**
 * Test Data is part of Wallace Point of Sale system (WPOS) API
 *
 * Test data is used to generate random sales for testing and demo purposes
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)

 * @link       https://wallacepos.com
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      File available since 19/07/14 5:14 PM
 */
class TestData {

    private $items;
    private $users;
    private $devices;
    private $paymentMethods = ['eftpos','credit','cheque','deposit','cash'];
    private $wposSales;

    public function generateTestData($purge=false){
        if ($purge)
            $this->purgeRecords();
        echo("Purged Data and restored.<br/>");
        $this->insertDemoRecords();
//        $this->generate(200, 'invoice');
//        $this->generate(800);
        echo("Inserted demo transactions.<br/>");
        // remove logs
//        if ($purge)
//            $this->resetDocuments();
    }

    public function resetDocuments(){
        exec("rm -r ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs/*");
        exec("cp -rp ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs-template/* ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs");
        exec("chmod -R 777 ".$_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."docs");
    }

    public function generate($numtransactions, $type='sale'){
        // get dependant record
        $this->getRecords();
        // set cur time
        $curprocessdt = time() * 1000;
        if (date('D', $curprocessdt)>16){
            $curprocessdt = strtotime(date("Y-m-d", ($curprocessdt/1000))." 17:00:00")*1000;
        }
        $initprocessdt = $curprocessdt;

        for ($i = 0; $i < $numtransactions; $i++) {
            // contruct JSON test data
            $saleobj = new stdClass();
            $saleobj->processdt = $curprocessdt;
            // pick a random device if  pos sale
            if ($type=='sale'){
                $device = $this->devices[rand(0, sizeof($this->devices) - 1)];
                $saleobj->devid = $device['id'];
                $saleobj->locid = $device['locationid'];
            }
            $saleobj->ref = $curprocessdt . "-" . ($type=='sale'?$device['id']:0) . "-" . rand(1000, 9999);
            // pick a random user
            $saleobj->userid = $this->users[rand(0, sizeof($this->users) - 1)]['id'];
            // add misc data
            $saleobj->custid = "";
            $saleobj->custemail = "";
            $saleobj->notes = "";
            $saleobj->discount = 0;
            $saleobj->discountval = 0;
            // add random items
            $numitems = (rand(1, 100)>75?(rand(1, 100)>95?rand(7,10):rand(4,6)):rand(1,3));
            $totalitemqty = 0;
            $total = 0.00;
            $totaltax = 0.00;
            $taxes = [];
            $items = [];
            // loop through num items time
            for ($inum=0; $inum<$numitems; $inum++){
                $item = $this->items[rand(0, sizeof($this->items) - 1)];
                // If price is 0 or "" pick a random price
                if ($item['price']=="" || $item['price']==0){
                    $item['price']=rand(1, 100);
                }
                // select random qty and get item total
                $randqty = rand(1, 100);
                $qty = ($randqty>80?($randqty>95?3:2):1);
                $totalitemqty+= $qty;
                $itemtotal = round(($item['price']*$qty), 2);

                // work out tax and add totals
                $itemtax = WposAdminUtilities::calculateTax($item['taxid'], isset($saleobj->locid)?$saleobj->locid:0, $itemtotal);
                if (!$itemtax->inclusive){
                    $itemtotal += $itemtax->total;
                };
                $total+=$itemtotal;
                $totaltax+= $itemtax->total;
                foreach ($itemtax->values as $key=>$value){
                    if (isset($taxes[$key])){
                        $taxes[$key]+= $value;
                    } else {
                        $taxes[$key]= $value;
                    }
                }

                $itemObj = new stdClass();
                $itemObj->ref=$inum+1;
                $itemObj->sitemid=$item['id'];
                $itemObj->qty=$qty;
                $itemObj->name=$item['name'];
                $itemObj->desc=$item['description'];
                $itemObj->unit=$item['price'];
                $itemObj->taxid=$item['taxid'];
                $itemObj->tax=$itemtax;
                $itemObj->price=$itemtotal;
                $items[] = $itemObj;
            }
            $saleobj->items = $items;
            $subtotal = $total - $totaltax;
            // if method cash round the total & add rounding amount, no cash payments for invoices
            if ($type=='sale'){
                $paymethod = $this->paymentMethods[rand(0, sizeof($this->paymentMethods) -1)];
            } else {
                $paymethod = $this->paymentMethods[rand(0, sizeof($this->paymentMethods) -2)];
            }
            if ($type=='sale' && $paymethod=="cash"){
                // round to nearest five cents
                $temptotal = $total;
                $total = round($total / 0.05) * 0.05;
                $saleobj->rounding = number_format($total - $temptotal , 2, '.', '');
                //if (floatval($saleobj->rounding)!=0)
                    //echo($temptotal." ".$total."<br/>");
            } else {
                $saleobj->rounding = 0.00;
            }
            // add payment to the sale
            if ($type=='sale'){ // leave a few invoices unpaid.
                $payment = new stdClass(); $payment->method=$paymethod; $payment->amount=number_format($total, 2, '.', '');
                if ($paymethod=="cash"){
                    $tender = (round($total)%5 === 0) ? round($total) : round(($total+5/2)/5)*5;
                    $payment->tender=number_format($tender, 2, '.', '');
                    $payment->change=number_format($tender-$total, 2, '.', '');
                }
                $saleobj->payments = [$payment];
            } else if ($type=='invoice'){
                if ($i<2 || $i==60){
                    $saleobj->payments = [];
                } else {
                    $payment = new stdClass(); $payment->method=($paymethod=='cash'?'eftpos':$paymethod); $payment->amount=number_format($total, 2, '.', '');
                    $saleobj->payments = [$payment];

                }
            }

            // add totals and tax
            $saleobj->numitems = $totalitemqty;
            $saleobj->taxdata = $taxes;
            $saleobj->tax = number_format($totaltax, 2, '.', '');
            $saleobj->subtotal = number_format($subtotal, 2, '.', '');
            $saleobj->total = number_format($total, 2, '.', '');

            // randomly add a void/refund to the sale
            if ($type=='sale' && (rand(1, 30) == 1)) {
                $voidobj = new stdClass();
                // pick another random device
                $device = $this->devices[rand(0, sizeof($this->devices) - 1)];
                $voidobj->deviceid = $device['id'];
                $voidobj->locationid = $device['locationid'];
                // pick another random user
                $voidobj->userid = $this->users[rand(0, sizeof($this->users) - 1)]['id'];
                // set sometime in the future but do not set before the initial date (now).
                $voidobj->processdt = (($curprocessdt+rand(30, 60*24))>$initprocessdt?$initprocessdt:$curprocessdt+rand(30, 60*24));

                if ((rand(1, 2) == 1)) {
                    // add reason
                    $voidobj->reason = "Faulty Item";
                    // refund, add additional data
                    $voidobj->method = $this->paymentMethods[rand(0, sizeof($this->paymentMethods) - 1)];
                    // pick item to return
                    $retitem = $items[rand(0, sizeof($items) - 1)];
                    $itemdata = new stdClass();
                    $itemdata->numreturned = 1;
                    $itemdata->ref = $retitem->ref;
                    $voidobj->items = [$itemdata];
                    $voidobj->amount = $retitem->unit;
                    // put in array before adding to saleobj
                    $saleobj->refunddata = [$voidobj];
                } else {
                    // add reason
                    $voidobj->reason = "Mistake";
                    // void
                    $saleobj->voiddata = $voidobj;
                }
            }
            // process the sale
            if ($type=='sale'){
                $this->wposSales = new WposPosSale($saleobj);
                $this->wposSales->setNoBroadcast();
                $result = $this->wposSales->insertTransaction(["errorCode" => "OK", "error" => "OK", "data" => ""]);
                //echo("Sale created: ".json_encode($result)."<br/>");
            } else {
                // add invoice only fields
                $saleobj->duedt = $curprocessdt + 1209600000;
                $saleobj->custid = rand(1, 2);
                $saleobj->channel = "manual";

                $this->wposSales = new WposInvoices($saleobj, null, true);
                $result = $this->wposSales->createInvoice(["errorCode" => "OK", "error" => "OK", "data" => ""]);
                //echo("Invoice created: ".json_encode($result));
            }
            // decrement by a random time between 2-40 minutes
            if ($type=='sale'){
                $curprocessdt = $curprocessdt - (rand(2, 40) * 60 * 1000);
            } else {
                $curprocessdt = $curprocessdt - (rand(40, 280) * 60 * 1000);
            }
            // if it's before shop open time, decrement to the last days closing time.
            $hour = date("H", $curprocessdt/1000);
            if ($hour<9){
                $curprocessdt = strtotime(date("Y-m-d", ($curprocessdt/1000)-86400)." 17:00:00")*1000;
            }
        }
        return;
    }

    private function getRecords()
    {
        // get items
        $itemMdl = new StoredItemsModel();
        $this->items = $itemMdl->get();
        // get items
        $authMdl = new AuthModel();
        $this->users = $authMdl->get(null, null, null, false);
        // get locations
        $devMdl = new WposPosData();
        $this->devices = $devMdl->getPosDevices([])['data'];
    }

    // Purge all records and set demo data
    private function purgeRecords(){
        $dbMdl = new DbConfig();
        $sql = file_get_contents($_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/installer/schemas/install.sql");
        if ($sql!=false){
            $dbMdl->_db->exec("ALTER TABLE sales AUTO_INCREMENT = 1; TRUNCATE TABLE sales;");
            $dbMdl->_db->exec("ALTER TABLE sale_items AUTO_INCREMENT = 1; TRUNCATE TABLE sale_items;");
            $dbMdl->_db->exec("ALTER TABLE sale_payments AUTO_INCREMENT = 1; TRUNCATE TABLE sale_payments;");
            $dbMdl->_db->exec("ALTER TABLE sale_voids AUTO_INCREMENT = 1; TRUNCATE TABLE sale_voids;");
            $dbMdl->_db->exec("ALTER TABLE sale_history AUTO_INCREMENT = 1; TRUNCATE TABLE sale_history;");
            $dbMdl->_db->exec("ALTER TABLE stored_items AUTO_INCREMENT = 1; TRUNCATE TABLE stored_items;");
            $dbMdl->_db->exec("ALTER TABLE stored_suppliers AUTO_INCREMENT = 1; TRUNCATE TABLE stored_suppliers;");
            $dbMdl->_db->exec("ALTER TABLE stored_categories AUTO_INCREMENT = 1; TRUNCATE TABLE stored_categories;");
            $dbMdl->_db->exec("ALTER TABLE devices AUTO_INCREMENT = 1; TRUNCATE TABLE devices;");
            $dbMdl->_db->exec("ALTER TABLE device_map AUTO_INCREMENT = 1; TRUNCATE TABLE device_map;");
            $dbMdl->_db->exec("ALTER TABLE locations AUTO_INCREMENT = 1; TRUNCATE TABLE locations;");
            $dbMdl->_db->exec("ALTER TABLE customers AUTO_INCREMENT = 1; TRUNCATE TABLE customers;");
            $dbMdl->_db->exec("ALTER TABLE customer_contacts AUTO_INCREMENT = 1; TRUNCATE TABLE customer_contacts;");
            $dbMdl->_db->exec("ALTER TABLE auth AUTO_INCREMENT = 1; TRUNCATE TABLE auth;");
//            $dbMdl->_db->exec("ALTER TABLE config AUTO_INCREMENT = 1; TRUNCATE TABLE config;");
//            $dbMdl->_db->exec("ALTER TABLE tax_rules AUTO_INCREMENT = 1; TRUNCATE TABLE tax_rules;");
//            $dbMdl->_db->exec("ALTER TABLE tax_items AUTO_INCREMENT = 1; TRUNCATE TABLE tax_items;");
            $dbMdl->_db->exec("ALTER TABLE stock_history AUTO_INCREMENT = 1; TRUNCATE TABLE stock_history;");            
            $dbMdl->_db->exec("ALTER TABLE stock_levels AUTO_INCREMENT = 1; TRUNCATE TABLE stock_levels;");                        
            $dbMdl->_db->exec($sql);
        } else {
            die("Could not import sql.");
        }
    }

    private function insertDemoRecords(){
        $suppliers = json_decode('[{"id": 1, "name":"Joe\'s Fruit&Veg Supplies", "dt":"0000-00-00 00:00:00"},
                        {"id": 2, "name":"Elecsys Electronic Distibution", "dt":"0000-00-00 00:00:00"},
                        {"id": 3, "name":"Fitwear Clothing Wholesale", "dt":"0000-00-00 00:00:00"},
                        {"id": 4, "name":"Yumbox Packaged Goods", "dt":"0000-00-00 00:00:00"},
                        {"id": 5, "name":"No Place Like Home-warehouse", "dt":"0000-00-00 00:00:00"}]');

        if ($suppliers==false){
            die("Failed to add suppliers");
        } else {
            $supMdl = new SuppliersModel();
            foreach($suppliers as $supplier){
//          $supMdl->create($supplier->name);
            }
//            echo("Inserted Suppliers.<br/>");
        }

        $items = json_decode('
                   [
                      {"id":"1","urlimage":"","categoryid":"0","active":"Y","code":"05","qty":"1","name":"05","description":"COLLARECONOMICO05","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"2","urlimage":"","categoryid":"0","active":"Y","code":"06","qty":"1","name":"06","description":"COLLARECONOMICO06","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"3","urlimage":"","categoryid":"0","active":"Y","code":"1025#3COM","qty":"1","name":"1025#3COM","description":"MESHNO.3ENCOMBINADO","subline":"MESH","unit":"PZA","price":"700.00","price2":"690.00","price3":"690.00","price4":"0.00","cost":"350.00","type":"general","modifiers":[]},
                      {"id":"4","urlimage":"","categoryid":"0","active":"Y","code":"1025#3PL","qty":"1","name":"1025#3PL","description":"MESHNO.3ENPLATA","subline":"MESH","unit":"PZA","price":"700.00","price2":"690.00","price3":"690.00","price4":"0.00","cost":"300.00","type":"general","modifiers":[]},
                      {"id":"5","urlimage":"","categoryid":"0","active":"Y","code":"1015#4COM","qty":"1","name":"1015#4COM","description":"MESHNO.4ENCOMBINADO","subline":"MESH","unit":"PZA","price":"850.00","price2":"800.00","price3":"800.00","price4":"0.00","cost":"394.00","type":"general","modifiers":[]},
                      {"id":"6","urlimage":"","categoryid":"0","active":"Y","code":"1025#6COM","qty":"1","name":"1025#6COM","description":"MESHNO.6ENCOMBINADO","subline":"MESH","unit":"PZA","price":"850.00","price2":"800.00","price3":"800.00","price4":"0.00","cost":"380.00","type":"general","modifiers":[]},
                      {"id":"7","urlimage":"","categoryid":"0","active":"Y","code":"APLICACIONPERLA","qty":"1","name":"APLICACIONPERLA","description":"APLICACI?NPERLACOSIDA","subline":"","unit":"PZA","price":"42.00","price2":"42.00","price3":"42.00","price4":"0.00","cost":"20.00","type":"general","modifiers":[]},
                      {"id":"8","urlimage":"","categoryid":"0","active":"Y","code":"ARGOLLA","qty":"1","name":"ARGOLLA","description":"ARGOLLA","subline":"MATERIAL","unit":"PZA","price":"1.60","price2":"1.60","price3":"1.60","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"9","urlimage":"","categoryid":"0","active":"Y","code":"BARRILCHICO","qty":"1","name":"BARRILCHICO","description":"BARRILCHICO","subline":"MATERIAL","unit":"PZA","price":"1.00","price2":"1.00","price3":"1.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"10","urlimage":"","categoryid":"0","active":"Y","code":"CAMPANACHICA","qty":"1","name":"CAMPANACHICA","description":"CAMPANACHICA","subline":"MATERIAL","unit":"PZA","price":"1.00","price2":"1.00","price3":"1.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"11","urlimage":"","categoryid":"0","active":"Y","code":"CAMPANAGRANDE","qty":"1","name":"CAMPANAGRANDE","description":"CAMPANAGRANDE","subline":"MATERIAL","unit":"PZA","price":"1.80","price2":"1.80","price3":"1.80","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"12","urlimage":"","categoryid":"0","active":"Y","code":"CINTURONCADENA","qty":"1","name":"CINTURONCADENA","description":"CINTURONCADENA","subline":"CINTURON","unit":"PZA","price":"18.00","price2":"18.00","price3":"18.00","price4":"0.00","cost":"7.50","type":"general","modifiers":[]},
                      {"id":"13","urlimage":"","categoryid":"0","active":"Y","code":"ENCAJEELSTICO","qty":"1","name":"ENCAJEELSTICO","description":"ENCAJEELASTICO","subline":"","unit":"MTS","price":"14.00","price2":"12.00","price3":"12.00","price4":"0.00","cost":"7.00","type":"general","modifiers":[]},                      
                      {"id":"14","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1001","qty":"1","name":"LAC-1001","description":"GIPIURENCAJELAC-1001","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"15","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1002","qty":"1","name":"LAC-1002","description":"GIPIURENCAJELAC-1002","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"16","urlimage":"","categoryid":"0","active":"N","code":"LAC-1004","qty":"1","name":"LAC-1004","description":"GIPIURENCAJELAC-1004","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"17","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1005","qty":"1","name":"LAC-1005","description":"GIPIURENCAJELAC-1005","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"18","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1006","qty":"1","name":"LAC-1006","description":"GIPIURENCAJELAC-1006","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"19","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1007","qty":"1","name":"LAC-1007","description":"GIPIURENCAJELAC-1007","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"20","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1008","qty":"1","name":"LAC-1008","description":"GIPIURENCAJELAC-1008","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"21","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1010","qty":"1","name":"LAC-1010","description":"GIPIURENCAJELAC-1010","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"22","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1011","qty":"1","name":"LAC-1011","description":"GIPIURENCAJELAC-1011","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"23","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1013","qty":"1","name":"LAC-1013","description":"LAC-101315YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"360.00","price2":"330.00","price3":"330.00","price4":"0.00","cost":"177.24","type":"general","modifiers":[]},
                      {"id":"24","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1016","qty":"1","name":"LAC-1016","description":"GIPIURENCAJELAC-1016","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"25","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1020","qty":"1","name":"LAC-1020","description":"GIPIURENCAJELAC-1020","subline":"GIPIUR/ENCAJE","unit":"YD","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"26","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1021","qty":"1","name":"LAC-1021","description":"GIPIURENCAJELAC-1021","subline":"GIPIUR/ENCAJE","unit":"YD","price":"13.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"27","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1022","qty":"1","name":"LAC-1022","description":"GIPIURENCAJELAC-1022C/15YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"300.00","price2":"285.00","price3":"285.00","price4":"315.00","cost":"139.00","type":"general","modifiers":[]},
                      {"id":"28","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1023","qty":"1","name":"LAC-1023","description":"LAC-102315YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"330.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"142.74","type":"general","modifiers":[]},
                      {"id":"29","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1025","qty":"1","name":"LAC-1025","description":"GIPIURENCAJELAC-1025C/15YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"450.00","price2":"375.00","price3":"375.00","price4":"345.00","cost":"156.00","type":"general","modifiers":[]},
                      {"id":"30","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1026","qty":"1","name":"LAC-1026","description":"GIPIURENCAJELAC-1026C/15YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"360.00","price2":"345.00","price3":"345.00","price4":"0.00","cost":"169.00","type":"general","modifiers":[]},
                      {"id":"31","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1027","qty":"1","name":"LAC-1027","description":"GIPIURENCAJELAC-1027C/20YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"360.00","price2":"340.00","price3":"340.00","price4":"0.00","cost":"139.00","type":"general","modifiers":[]},
                      {"id":"32","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1029","qty":"1","name":"LAC-1029","description":"LAC-102915YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"330.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"142.74","type":"general","modifiers":[]},
                      {"id":"33","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1030","qty":"1","name":"LAC-1030","description":"GIPIURENCAJELAC-1030C/15YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"450.00","price2":"435.00","price3":"435.00","price4":"0.00","cost":"191.00","type":"general","modifiers":[]},
                      {"id":"34","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1032","qty":"1","name":"LAC-1032","description":"GIPIURENCAJELAC-1032C/15YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"450.00","price2":"405.00","price3":"405.00","price4":"0.00","cost":"230.00","type":"general","modifiers":[]},
                      {"id":"35","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1033","qty":"1","name":"LAC-1033","description":"GIPIURENCAJELAC-1033C/15YD","subline":"GIPIUR/ENCAJE","unit":"YD","price":"270.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"186.00","type":"general","modifiers":[]},
                      {"id":"36","urlimage":"","categoryid":"0","active":"Y","code":"N139147","qty":"1","name":"N139147","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"37","urlimage":"","categoryid":"0","active":"Y","code":"N139154","qty":"1","name":"N139154","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"38","urlimage":"","categoryid":"0","active":"Y","code":"N139193","qty":"1","name":"N139193","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"39","urlimage":"","categoryid":"0","active":"Y","code":"N139194","qty":"1","name":"N139194","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"150.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"40","urlimage":"","categoryid":"0","active":"Y","code":"N139241","qty":"1","name":"N139241","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"41","urlimage":"","categoryid":"0","active":"Y","code":"N139253","qty":"1","name":"N139253","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"42","urlimage":"","categoryid":"0","active":"Y","code":"N139254","qty":"1","name":"N139254","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"43","urlimage":"","categoryid":"0","active":"Y","code":"N139258","qty":"1","name":"N139258","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"44","urlimage":"","categoryid":"0","active":"Y","code":"N139315","qty":"1","name":"N139315","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"45","urlimage":"","categoryid":"0","active":"Y","code":"N199001","qty":"1","name":"N199001","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"46","urlimage":"","categoryid":"0","active":"Y","code":"N199017","qty":"1","name":"N199017","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"47","urlimage":"","categoryid":"0","active":"Y","code":"N199026","qty":"1","name":"N199026","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"150.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"48","urlimage":"","categoryid":"0","active":"Y","code":"N199060","qty":"1","name":"N199060","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"49","urlimage":"","categoryid":"0","active":"Y","code":"N199093","qty":"1","name":"N199093","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"50","urlimage":"","categoryid":"0","active":"Y","code":"N199100","qty":"1","name":"N199100","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"51","urlimage":"","categoryid":"0","active":"Y","code":"N39100","qty":"1","name":"N39100","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"52","urlimage":"","categoryid":"0","active":"Y","code":"N999150","qty":"1","name":"N999150","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"150.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"53","urlimage":"","categoryid":"0","active":"Y","code":"N999281","qty":"1","name":"N999281","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"54","urlimage":"","categoryid":"0","active":"Y","code":"NL-1008","qty":"1","name":"NL-1008","description":"PLACALISA","subline":"","unit":"PZA","price":"15.00","price2":"15.00","price3":"15.00","price4":"0.00","cost":"10.00","type":"general","modifiers":[]},
                      {"id":"55","urlimage":"","categoryid":"0","active":"Y","code":"NL-1009","qty":"1","name":"NL-1009","description":"PLACAORIFICIOS","subline":"","unit":"PZA","price":"15.00","price2":"12.00","price3":"12.00","price4":"0.00","cost":"10.00","type":"general","modifiers":[]},
                      {"id":"56","urlimage":"","categoryid":"0","active":"Y","code":"NL-1011","qty":"1","name":"NL-1011","description":"PLACACUELLO","subline":"","unit":"PZA","price":"15.00","price2":"15.00","price3":"15.00","price4":"0.00","cost":"10.00","type":"general","modifiers":[]},
                      {"id":"57","urlimage":"","categoryid":"0","active":"Y","code":"NL-1014","qty":"1","name":"NL-1014","description":"PLACAPERFORADA","subline":"","unit":"PZA","price":"15.00","price2":"15.00","price3":"15.00","price4":"0.00","cost":"10.00","type":"general","modifiers":[]},
                      {"id":"58","urlimage":"","categoryid":"0","active":"Y","code":"NL-1029","qty":"1","name":"NL-1029","description":"COLLARCARONL-1029C/12PZS","subline":"","unit":"DOC","price":"240.00","price2":"360.00","price3":"360.00","price4":"324.00","cost":"224.00","type":"general","modifiers":[]},
                      {"id":"59","urlimage":"","categoryid":"0","active":"Y","code":"NL-1030","qty":"1","name":"NL-1030","description":"COLLARCARONL-1030C/12PZS","subline":"","unit":"DOC","price":"240.00","price2":"360.00","price3":"360.00","price4":"324.00","cost":"212.00","type":"general","modifiers":[]},
                      {"id":"60","urlimage":"","categoryid":"0","active":"Y","code":"NL-1031","qty":"1","name":"NL-1031","description":"COLLARCARONL-1031C/12PZS","subline":"","unit":"DOC","price":"240.00","price2":"324.00","price3":"324.00","price4":"0.00","cost":"254.00","type":"general","modifiers":[]},
                      {"id":"61","urlimage":"","categoryid":"0","active":"Y","code":"NL-1032","qty":"1","name":"NL-1032","description":"COLLARCARONL-1032C/12PZS","subline":"","unit":"DOC","price":"240.00","price2":"360.00","price3":"360.00","price4":"0.00","cost":"232.00","type":"general","modifiers":[]},
                      {"id":"62","urlimage":"","categoryid":"0","active":"Y","code":"NL-1033","qty":"1","name":"NL-1033","description":"COLLARCARONL-1033C/12PZS","subline":"","unit":"DOC","price":"240.00","price2":"384.00","price3":"384.00","price4":"0.00","cost":"295.00","type":"general","modifiers":[]},
                      {"id":"63","urlimage":"","categoryid":"0","active":"Y","code":"PLCABIARAMBAR","qty":"1","name":"PLCABIARAMBAR","description":"PLANILLACAVIARAMBAR","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"85.00","type":"general","modifiers":[]},
                      {"id":"64","urlimage":"","categoryid":"0","active":"Y","code":"PLRESORTESCRISTAL","qty":"1","name":"PLRESORTESCRISTAL","description":"PLANILARESORTECRISTAL","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"100.00","type":"general","modifiers":[]},
                      {"id":"65","urlimage":"","categoryid":"0","active":"Y","code":"PLROMBOAMBAR","qty":"1","name":"PLROMBOAMBAR","description":"PLANILLAROMBOAMBAR","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"85.00","type":"general","modifiers":[]},
                      {"id":"66","urlimage":"","categoryid":"0","active":"Y","code":"PLROMBOPITCH","qty":"1","name":"PLROMBOPITCH","description":"PLANILLAROMBOPITCH","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"85.00","type":"general","modifiers":[]},
                      {"id":"67","urlimage":"","categoryid":"0","active":"Y","code":"BARRILGRANDE","qty":"1","name":"BARRILGRANDE","description":"BARRILGRANDE","subline":"MATERIAL","unit":"PZA","price":"1.60","price2":"1.60","price3":"1.60","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"68","urlimage":"","categoryid":"0","active":"Y","code":"BARRILCALABAZA","qty":"1","name":"BARRILCALABAZA","description":"BARRILCALABAZA","subline":"MATERIAL","unit":"PZA","price":"1.80","price2":"1.80","price3":"1.80","price4":"0.00","cost":"0.00","type":"general","modifiers":[]},
                      {"id":"69","urlimage":"","categoryid":"0","active":"N","code":"LAC-1033C/15YD","qty":"1","name":"LAC-1033C/15YD","description":"GIPIURENCAJELAC-1033","subline":"GIPIUR/ENCAJE","unit":"YD","price":"270.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"186.00","type":"general","modifiers":[]},
                      {"id":"70","urlimage":"","categoryid":"0","active":"Y","code":"N139313","qty":"1","name":"N139313","description":"COLLARECONOMICO","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"162.00","price3":"162.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"71","urlimage":"","categoryid":"0","active":"Y","code":"HF-1007","qty":"1","name":"HF-1007","description":"HF-1007Planilla","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"79.00","type":"general","modifiers":[]},
                      {"id":"72","urlimage":"","categoryid":"0","active":"Y","code":"HF-1001","qty":"1","name":"HF-1001","description":"HF-1001Planilla","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.00","type":"general","modifiers":[]},
                      {"id":"73","urlimage":"","categoryid":"0","active":"Y","code":"HF-1039","qty":"1","name":"HF-1039","description":"HF-1039Planilla","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"96.00","type":"general","modifiers":[]},
                      {"id":"74","urlimage":"","categoryid":"0","active":"Y","code":"HF-1040","qty":"1","name":"HF-1040","description":"HF-1040Planilla","subline":"","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"105.00","type":"general","modifiers":[]},
                      {"id":"75","urlimage":"","categoryid":"0","active":"Y","code":"HF-1038","qty":"1","name":"HF-1038","description":"HF-1038Planilla","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"105.00","type":"general","modifiers":[]},
                      {"id":"76","urlimage":"","categoryid":"0","active":"Y","code":"HF-1029","qty":"1","name":"HF-1029","description":"HF-1029PLANILLA","subline":"","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.00","type":"general","modifiers":[]},
                      {"id":"77","urlimage":"","categoryid":"0","active":"Y","code":"HF-1032","qty":"1","name":"HF-1032","description":"HF-1032PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"70.00","type":"general","modifiers":[]},
                      {"id":"78","urlimage":"","categoryid":"0","active":"Y","code":"HF-1042","qty":"1","name":"HF-1042","description":"HF-1042PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"73.00","type":"general","modifiers":[]},
                      {"id":"79","urlimage":"","categoryid":"0","active":"Y","code":"HF-1021","qty":"1","name":"HF-1021","description":"HF-1021PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"70.00","type":"general","modifiers":[]},
                      {"id":"80","urlimage":"","categoryid":"0","active":"Y","code":"HF-1037","qty":"1","name":"HF-1037","description":"HF-1037PLANILLA","subline":"PLANILLA","unit":"PZA","price":"210.00","price2":"200.00","price3":"200.00","price4":"0.00","cost":"165.00","type":"general","modifiers":[]},
                      {"id":"81","urlimage":"","categoryid":"0","active":"Y","code":"HF-1009","qty":"1","name":"HF-1009","description":"HF-1009PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"70.00","type":"general","modifiers":[]},
                      {"id":"82","urlimage":"","categoryid":"0","active":"Y","code":"CINTURONTRENZA","qty":"1","name":"CINTURONTRENZA","description":"CINTURONTRENZA","subline":"CINTURON","unit":"PZA","price":"14.00","price2":"13.00","price3":"13.00","price4":"0.00","cost":"7.00","type":"general","modifiers":[]},
                      {"id":"83","urlimage":"","categoryid":"0","active":"Y","code":"N69570-MVA","qty":"1","name":"N69570-MVA","description":"CollarEconomicoN69570-MVA","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"84","urlimage":"","categoryid":"0","active":"Y","code":"N139100","qty":"1","name":"N139100","description":"CollarEconomicoN139100","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"85","urlimage":"","categoryid":"0","active":"Y","code":"N199143","qty":"1","name":"N199143","description":"CollarEconomicoN199143","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"86","urlimage":"","categoryid":"0","active":"Y","code":"N199156","qty":"1","name":"N199156","description":"CollarEconomicoN199156","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"87","urlimage":"","categoryid":"0","active":"Y","code":"N199158","qty":"1","name":"N199158","description":"CollarEconomicoN199158","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"88","urlimage":"","categoryid":"0","active":"Y","code":"N199161","qty":"1","name":"N199161","description":"CollarEconomicoN199161","subline":"","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"89","urlimage":"","categoryid":"0","active":"Y","code":"N249001","qty":"1","name":"N249001","description":"CollarEconomicoN249001","subline":"","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"90","urlimage":"","categoryid":"0","active":"Y","code":"N249002","qty":"1","name":"N249002","description":"CollarEconomicoN249002","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"91","urlimage":"","categoryid":"0","active":"Y","code":"N239001","qty":"1","name":"N239001","description":"CollarEconomicoN239001","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"92","urlimage":"","categoryid":"0","active":"Y","code":"N249003","qty":"1","name":"N249003","description":"CollarEconomicoN249003","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"92.00","type":"general","modifiers":[]},
                      {"id":"93","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1026","qty":"1","name":"NLC-1026","description":"CollarEcoNLC-1026","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"60.00","type":"general","modifiers":[]},
                      {"id":"94","urlimage":"","categoryid":"0","active":"Y","code":"HF-1043","qty":"1","name":"HF-1043","description":"HF-1043","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"81.66","type":"general","modifiers":[]},
                      {"id":"95","urlimage":"","categoryid":"0","active":"Y","code":"HF-1002","qty":"1","name":"HF-1002","description":"HF-1002PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"75.50","type":"general","modifiers":[]},
                      {"id":"96","urlimage":"","categoryid":"0","active":"Y","code":"HF-1005","qty":"1","name":"HF-1005","description":"HF-1005PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"69.50","type":"general","modifiers":[]},
                      {"id":"97","urlimage":"","categoryid":"0","active":"Y","code":"HF-1008","qty":"1","name":"HF-1008","description":"HF-1008PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"58.08","type":"general","modifiers":[]},
                      {"id":"98","urlimage":"","categoryid":"0","active":"Y","code":"HF-1011","qty":"1","name":"HF-1011","description":"HF-1011PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"83.95","type":"general","modifiers":[]},
                      {"id":"99","urlimage":"","categoryid":"0","active":"Y","code":"HF-1012","qty":"1","name":"HF-1012","description":"HF-1012PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"66.70","type":"general","modifiers":[]},
                      {"id":"100","urlimage":"","categoryid":"0","active":"Y","code":"HF-1014","qty":"1","name":"HF-1014","description":"HF-1014PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"104.08","type":"general","modifiers":[]},
                      {"id":"101","urlimage":"","categoryid":"0","active":"Y","code":"HF-1017","qty":"1","name":"HF-1017","description":"HF-1017PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"95.45","type":"general","modifiers":[]},
                      {"id":"102","urlimage":"","categoryid":"0","active":"Y","code":"HF-1018","qty":"1","name":"HF-1018","description":"HF-1018PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"72.45","type":"general","modifiers":[]},
                      {"id":"103","urlimage":"","categoryid":"0","active":"Y","code":"HF-1019","qty":"1","name":"HF-1019","description":"HF-1019PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"66.70","type":"general","modifiers":[]},
                      {"id":"104","urlimage":"","categoryid":"0","active":"Y","code":"HF-1023","qty":"1","name":"HF-1023","description":"HF-1023PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"92.58","type":"general","modifiers":[]},
                      {"id":"105","urlimage":"","categoryid":"0","active":"Y","code":"HF-1024","qty":"1","name":"HF-1024","description":"HF-1024PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"75.33","type":"general","modifiers":[]},
                      {"id":"106","urlimage":"","categoryid":"0","active":"Y","code":"HF-1026","qty":"1","name":"HF-1026","description":"HF-1026PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"72.45","type":"general","modifiers":[]},
                      {"id":"107","urlimage":"","categoryid":"0","active":"Y","code":"HF-1034","qty":"1","name":"HF-1034","description":"HF-1034PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"69.58","type":"general","modifiers":[]},
                      {"id":"108","urlimage":"","categoryid":"0","active":"Y","code":"HF-1041","qty":"1","name":"HF-1041","description":"HF-1041PLANILLA","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"81.08","type":"general","modifiers":[]},
                      {"id":"109","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1003","qty":"1","name":"LAC-1003","description":"LAC-100315YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"240.00","price2":"210.00","price3":"210.00","price4":"0.00","cost":"82.37","type":"general","modifiers":[]},
                      {"id":"110","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1012","qty":"1","name":"LAC-1012","description":"LAC-101215YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"210.00","price2":"210.00","price3":"210.00","price4":"0.00","cost":"82.37","type":"general","modifiers":[]},
                      {"id":"111","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1014","qty":"1","name":"LAC-1014","description":"LAC-101415YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"270.00","price2":"270.00","price3":"270.00","price4":"0.00","cost":"114.28","type":"general","modifiers":[]},
                      {"id":"112","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1017","qty":"1","name":"LAC-1017","description":"LAC-101715YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"315.00","price2":"315.00","price3":"315.00","price4":"0.00","cost":"131.53","type":"general","modifiers":[]},
                      {"id":"113","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1018","qty":"1","name":"LAC-1018","description":"LAC-101815YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"240.00","price2":"210.00","price3":"210.00","price4":"0.00","cost":"103.93","type":"general","modifiers":[]},
                      {"id":"114","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1019","qty":"1","name":"LAC-1019","description":"LAC-101915YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"240.00","price2":"210.00","price3":"210.00","price4":"0.00","cost":"99.62","type":"general","modifiers":[]},
                      {"id":"115","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1034","qty":"1","name":"LAC-1034","description":"LAC-103415YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"495.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"203.12","type":"general","modifiers":[]},
                      {"id":"116","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1035","qty":"1","name":"LAC-1035","description":"LAC-103515YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"390.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"160.00","type":"general","modifiers":[]},
                      {"id":"117","urlimage":"","categoryid":"0","active":"Y","code":"4U-04810","qty":"1","name":"4U-04810","description":"4U-04810120YDS","subline":"GIPIUR/ENCAJE","unit":"YD","price":"2160.00","price2":"1920.00","price3":"1920.00","price4":"0.00","cost":"1107.44","type":"general","modifiers":[]},
                      {"id":"118","urlimage":"","categoryid":"0","active":"Y","code":"NL-1035","qty":"1","name":"NL-1035","description":"NL-1035","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"61.24","type":"general","modifiers":[]},
                      {"id":"119","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1019","qty":"1","name":"NLC-1019","description":"NLC-1019","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"59.51","type":"general","modifiers":[]},
                      {"id":"120","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1020","qty":"1","name":"NLC-1020","description":"NLC-1020","subline":"","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"56.06","type":"general","modifiers":[]},
                      {"id":"121","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1023","qty":"1","name":"NLC-1023","description":"NLC-1023","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"59.51","type":"general","modifiers":[]},
                      {"id":"122","urlimage":"","categoryid":"0","active":"Y","code":"cinturonsencillo","qty":"1","name":"cinturonsencillo","description":"cinturonsencillo","subline":"CINTURON","unit":"PZA","price":"14.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"10.00","type":"general","modifiers":[]},
                      {"id":"123","urlimage":"","categoryid":"0","active":"N","code":"cinturoncensillo","qty":"1","name":"cinturoncensillo","description":"cinturonsencillo","subline":"CINTURON","unit":"PZA","price":"0.00","price2":"0.00","price3":"0.00","price4":"0.00","cost":"10.00","type":"general","modifiers":[]},
                      {"id":"124","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1022","qty":"1","name":"NLC-1022","description":"NLC-1022","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"150.00","cost":"66.41","type":"general","modifiers":[]},
                      {"id":"125","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1024","qty":"1","name":"NLC-1024","description":"NLC-1024","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"61.24","type":"general","modifiers":[]},
                      {"id":"126","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1017","qty":"1","name":"NLC-1017","description":"NLC-1017","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"59.51","type":"general","modifiers":[]},
                      {"id":"127","urlimage":"","categoryid":"0","active":"N","code":"NL-1036","qty":"1","name":"NL-1036","description":"NL-1036","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"62.96","type":"general","modifiers":[]},
                      {"id":"128","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1021","qty":"1","name":"NLC-1021","description":"NLC-1021","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"56.06","type":"general","modifiers":[]},
                      {"id":"129","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1036","qty":"1","name":"NLC-1036","description":"NLC-1036","subline":"COLLAR","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"62.96","type":"general","modifiers":[]},
                      {"id":"130","urlimage":"","categoryid":"0","active":"Y","code":"CHN-1001LARGE","qty":"1","name":"CHN-1001LARGE","description":"CHN-1001LARGE","subline":"CADENA","unit":"MTS","price":"500.00","price2":"480.00","price3":"480.00","price4":"0.00","cost":"218.00","type":"general","modifiers":[]},
                      {"id":"131","urlimage":"","categoryid":"0","active":"Y","code":"CHN-1002SMALL","qty":"1","name":"CHN-1002SMALL","description":"CHN-1002SMALL","subline":"CADENA","unit":"MTS","price":"440.00","price2":"400.00","price3":"400.00","price4":"0.00","cost":"174.00","type":"general","modifiers":[]},
                      {"id":"132","urlimage":"","categoryid":"0","active":"Y","code":"HF-1032-C","qty":"1","name":"HF-1032-C","description":"HF-1032-C","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"133","urlimage":"","categoryid":"0","active":"Y","code":"HF-1032-P","qty":"1","name":"HF-1032-P","description":"HF-1032-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"134","urlimage":"","categoryid":"0","active":"Y","code":"HF-1032-T","qty":"1","name":"HF-1032-T","description":"HF-1032-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"135","urlimage":"","categoryid":"0","active":"Y","code":"HF-1043-C","qty":"1","name":"HF-1043-C","description":"HF-1043-C","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"82.77","type":"general","modifiers":[]},
                      {"id":"136","urlimage":"","categoryid":"0","active":"Y","code":"HF-1043-P","qty":"1","name":"HF-1043-P","description":"HF-1043-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"92.12","type":"general","modifiers":[]},
                      {"id":"137","urlimage":"","categoryid":"0","active":"Y","code":"HF-1043-T","qty":"1","name":"HF-1043-T","description":"HF-1043-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"82.77","type":"general","modifiers":[]},
                      {"id":"138","urlimage":"","categoryid":"0","active":"Y","code":"HF-1044-C","qty":"1","name":"HF-1044-C","description":"HF-1044-C","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.54","type":"general","modifiers":[]},
                      {"id":"139","urlimage":"","categoryid":"0","active":"Y","code":"HF-1044-P","qty":"1","name":"HF-1044-P","description":"HF-1044-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"89.00","type":"general","modifiers":[]},
                      {"id":"140","urlimage":"","categoryid":"0","active":"Y","code":"HF-1044-T","qty":"1","name":"HF-1044-T","description":"HF-1044-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.54","type":"general","modifiers":[]},
                      {"id":"141","urlimage":"","categoryid":"0","active":"Y","code":"HF-1045","qty":"1","name":"HF-1045","description":"HF-1045","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"64.08","type":"general","modifiers":[]},
                      {"id":"142","urlimage":"","categoryid":"0","active":"Y","code":"HF-1046-C","qty":"1","name":"HF-1046-C","description":"HF-1046-C","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"82.77","type":"general","modifiers":[]},
                      {"id":"143","urlimage":"","categoryid":"0","active":"Y","code":"HF-1046-P","qty":"1","name":"HF-1046-P","description":"HF-1046-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"89.00","type":"general","modifiers":[]},
                      {"id":"144","urlimage":"","categoryid":"0","active":"Y","code":"HF-1046-T","qty":"1","name":"HF-1046-T","description":"HF-1046-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"82.77","type":"general","modifiers":[]},
                      {"id":"145","urlimage":"","categoryid":"0","active":"Y","code":"HF-1047-P","qty":"1","name":"HF-1047-P","description":"HF-1047-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"73.43","type":"general","modifiers":[]},
                      {"id":"146","urlimage":"","categoryid":"0","active":"Y","code":"HF-1047-T","qty":"1","name":"HF-1047-T","description":"HF-1047-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"73.43","type":"general","modifiers":[]},
                      {"id":"147","urlimage":"","categoryid":"0","active":"Y","code":"HF-1048-P","qty":"1","name":"HF-1048-P","description":"HF-1048-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.54","type":"general","modifiers":[]},
                      {"id":"148","urlimage":"","categoryid":"0","active":"Y","code":"HF-1048-T","qty":"1","name":"HF-1048-T","description":"HF-1048-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"149","urlimage":"","categoryid":"0","active":"Y","code":"HF-1049","qty":"1","name":"HF-1049","description":"HF-1049","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"57.85","type":"general","modifiers":[]},
                      {"id":"150","urlimage":"","categoryid":"0","active":"Y","code":"HF-1050-P","qty":"1","name":"HF-1050-P","description":"HF-1050-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"64.08","type":"general","modifiers":[]},
                      {"id":"151","urlimage":"","categoryid":"0","active":"Y","code":"HF-1050-T","qty":"1","name":"HF-1050-T","description":"HF-1050-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"64.08","type":"general","modifiers":[]},
                      {"id":"152","urlimage":"","categoryid":"0","active":"Y","code":"HF-1051-P","qty":"1","name":"HF-1051-P","description":"HF-1051-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.54","type":"general","modifiers":[]},
                      {"id":"153","urlimage":"","categoryid":"0","active":"Y","code":"HF-1051-T","qty":"1","name":"HF-1051-T","description":"HF-1051-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"76.54","type":"general","modifiers":[]},
                      {"id":"154","urlimage":"","categoryid":"0","active":"Y","code":"HF-1052-P","qty":"1","name":"HF-1052-P","description":"HF-1052-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"73.43","type":"general","modifiers":[]},
                      {"id":"155","urlimage":"","categoryid":"0","active":"Y","code":"HF-1052-T","qty":"1","name":"HF-1052-T","description":"HF-1052-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"73.43","type":"general","modifiers":[]},
                      {"id":"156","urlimage":"","categoryid":"0","active":"Y","code":"HF-1053-P","qty":"1","name":"HF-1053-P","description":"HF-1053-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"157","urlimage":"","categoryid":"0","active":"Y","code":"HF-1053-T","qty":"1","name":"HF-1053-T","description":"HF-1053-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"158","urlimage":"","categoryid":"0","active":"Y","code":"HF-1054-P","qty":"1","name":"HF-1054-P","description":"HF-1054-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"79.66","type":"general","modifiers":[]},
                      {"id":"159","urlimage":"","categoryid":"0","active":"Y","code":"HF-1054-T","qty":"1","name":"HF-1054-T","description":"HF-1054-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"79.66","type":"general","modifiers":[]},
                      {"id":"160","urlimage":"","categoryid":"0","active":"Y","code":"HF-1055-C","qty":"1","name":"HF-1055-C","description":"HF-1055-C","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"161","urlimage":"","categoryid":"0","active":"Y","code":"HF-1055-P","qty":"1","name":"HF-1055-P","description":"HF-1055-P","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"162","urlimage":"","categoryid":"0","active":"Y","code":"HF-1055-T","qty":"1","name":"HF-1055-T","description":"HF-1055-T","subline":"PLANILLA","unit":"PZA","price":"200.00","price2":"180.00","price3":"180.00","price4":"0.00","cost":"67.20","type":"general","modifiers":[]},
                      {"id":"163","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1036Guipur45Yards","qty":"1","name":"LAC-1036Guipur45Yards","description":"LAC-1036Guipur45Yards","subline":"GIPIUR/ENCAJE","unit":"YD","price":"450.00","price2":"360.00","price3":"360.00","price4":"0.00","cost":"194.40","type":"general","modifiers":[]},
                      {"id":"164","urlimage":"","categoryid":"0","active":"Y","code":"LAC-1037Guipur45Yards","qty":"1","name":"LAC-1037Guipur45Yards","description":"LAC-1037Guipur45Yards","subline":"GIPIUR/ENCAJE","unit":"YD","price":"450.00","price2":"360.00","price3":"360.00","price4":"0.00","cost":"185.00","type":"general","modifiers":[]},
                      {"id":"165","urlimage":"","categoryid":"0","active":"Y","code":"NL-1050","qty":"1","name":"NL-1050","description":"NL-1050","subline":"COLLARECONOMICO","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"70.44","type":"general","modifiers":[]},
                      {"id":"166","urlimage":"","categoryid":"0","active":"Y","code":"NL-1053","qty":"1","name":"NL-1053","description":"NL-1053","subline":"COLLARECONOMICO","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"70.44","type":"general","modifiers":[]},
                      {"id":"167","urlimage":"","categoryid":"0","active":"Y","code":"NL-1055","qty":"1","name":"NL-1055","description":"NL-1055","subline":"COLLARECONOMICO","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"76.08","type":"general","modifiers":[]},
                      {"id":"168","urlimage":"","categoryid":"0","active":"Y","code":"NLC-1016","qty":"1","name":"NLC-1016","description":"NLC-1016","subline":"COLLARECONOMICO","unit":"DOC","price":"162.00","price2":"156.00","price3":"156.00","price4":"0.00","cost":"74.28","type":"general","modifiers":[]},
                      {"id":"169","urlimage":"","categoryid":"0","active":"N","code":"LAC-1035-245yards","qty":"1","name":"LAC-1035-245yards","description":"LAC-1035-245yards","subline":"GIPIUR/ENCAJE","unit":"PZA","price":"450.00","price2":"360.00","price3":"360.00","price4":"0.00","cost":"180.45","type":"general","modifiers":[]} 

                     ]');                      
                      


// 


//                   ]');
////                      {"id":"1","urlimage":"image.jpg","categoryid":"1","active":"Y","code":"TEST-1234","qty":"1","name":"TEST-1234","description":"dfdfdGolden","subline":"PLANILLA","unit":"DOZEN","price":"0.99","price2":"1.99","cost":"5","type":"food","modifiers":[]}                                                       
////                    {"id": 1,"urlimage":"\/docs/","categoryid":"1","active":"Y","code": "TEST-1234","qty":"1","name": "TEST-1234","description": "dfdfdGolden","unit":"DOZEN","price": "0.99","price2": "1.99","cost":"5", "type":"general", "modifiers":[]},
////                    {"id": 2,"urlimage":"\/docs/","categoryid":"1","active":"Y","code": "TEST-2345","qty":"1","name": "TEST-2345","description": "","unit":"PIS","price": "600.00","price2": "1.99", "cost":"5", "type":"general", "modifiers":[]},          
////                    {"id": 3,"urlimage":"\/docs/","categoryid":"0","active":"Y","code":"KKK-1234","qty":"100","name":"KKK-1234","description":"111111","unit":"DOZEN","price":"10","price2":"20","cost":"5","type":"general","modifiers":[]}                 
//
////                    {"id": 1,"urlimage":"\/docs/","categoryid":"1","active":"Y","code": "TEST-1234","qty":"1","name": "TEST-1234","description": "dfdfdGolden","unit":"DOZEN","price": "0.99","price2": "1.99","cost":"5", "type":"general", "modifiers":[]},
////                    {"id": 2,"urlimage":"\/docs/","categoryid":"1","active":"Y","code": "TEST-2345","qty":"1","name": "TEST-2345","description": "","unit":"PIS","price": "600.00","price2": "1.99", "cost":"5", "type":"general", "modifiers":[]},
////                    {"id": 3,"urlimage":"\/docs/","categoryid":"0","active":"Y","code":"KKK-1234","qty":"100","name":"KKK-1234","description":"111111","unit":"DOZEN","price":"10","price2":"20","cost":"5","type":"general","modifiers":[]}
/*
                    {"id":1,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,05,"qty:""1""",name:,05,description:,COLLAR ECONOMICO 05 ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":2,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,06,"qty:""1""",name:,06,description:,COLLAR ECONOMICO 06 ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":3,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,1025#3COM,"qty:""1""",name:,1025#3COM,description:,MESH NO. 3 EN COMBINADO,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":4,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,1025#3PL,"qty:""1""",name:,1025#3PL,description:,MESH NO. 3 EN PLATA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":5,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,1015#4COM,"qty:""1""",name:,1015#4COM,description:,MESH NO.4 EN COMBINADO,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":6,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,1025#6COM,"qty:""1""",name:,1025#6COM,description:,MESH NO. 6 EN COMBINADO,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":7,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,APLICACIONPERLA,"qty:""1""",name:,APLICACIONPERLA,description:,APLICACI?N PERLA COSIDA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":8,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,ARGOLLA,"qty:""1""",name:,ARGOLLA,description:,ARGOLLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":9,"urlimage:""\/docs/","categoryid:""1""","active:""Y""",code:,BARRIL CHICO,"qty:""1""",name:,BARRIL CHICO,description:,BARRIL CHICO,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":10,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,CAMPANACHICA,"qty:""1""",name:,CAMPANACHICA,description:,CAMPANA CHICA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":11,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,CAMPANAGRANDE,"qty:""1""",name:,CAMPANAGRANDE,description:,CAMPANA GRANDE,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":12,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,CINTURON CADENA,"qty:""1""",name:,CINTURON CADENA,description:,CINTURON CADENA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":13,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,ENCAJE ELSTICO,"qty:""1""",name:,ENCAJE ELSTICO,description:,ENCAJE ELASTICO,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":14,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1001,"qty:""1""",name:,LAC-1001,description:,GIPIUR ENCAJE LAC-1001,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":15,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1002,"qty:""1""",name:,LAC-1002,description:,GIPIUR ENCAJE LAC-1002,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":16,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1004,"qty:""1""",name:,LAC-1004,description:,GIPIUR ENCAJE LAC-1004,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":17,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1005,"qty:""1""",name:,LAC-1005,description:,GIPIUR ENCAJE LAC-1005,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":18,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1006,"qty:""1""",name:,LAC-1006,description:,GIPIUR ENCAJE LAC-1006,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":19,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1007,"qty:""1""",name:,LAC-1007,description:,GIPIUR ENCAJE LAC-1007,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":20,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1008,"qty:""1""",name:,LAC-1008,description:,GIPIUR ENCAJE LAC-1008,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":21,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1010,"qty:""1""",name:,LAC-1010,description:,GIPIUR ENCAJE LAC-1010,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":22,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1011,"qty:""1""",name:,LAC-1011,description:,GIPIUR ENCAJE LAC-1011,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":23,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1013,"qty:""1""",name:,LAC-1013,description:,LAC-1013 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":24,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1016,"qty:""1""",name:,LAC-1016,description:,GIPIUR ENCAJE LAC-1016,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":25,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1020,"qty:""1""",name:,LAC-1020,description:,GIPIUR ENCAJE LAC-1020,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":26,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1021,"qty:""1""",name:,LAC-1021,description:,GIPIUR ENCAJE LAC-1021,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":27,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1022,"qty:""1""",name:,LAC-1022,description:,GIPIUR ENCAJE LAC-1022 C/15YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":28,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1023,"qty:""1""",name:,LAC-1023,description:,LAC-1023 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":29,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1025,"qty:""1""",name:,LAC-1025,description:,GIPIUR ENCAJE LAC-1025 C/15YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":30,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1026,"qty:""1""",name:,LAC-1026,description:,GIPIUR ENCAJE LAC-1026 C/15YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":31,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1027,"qty:""1""",name:,LAC-1027,description:,GIPIUR ENCAJE LAC-1027 C/20YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":32,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1029,"qty:""1""",name:,LAC-1029,description:,LAC-1029 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":33,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1030,"qty:""1""",name:,LAC-1030,description:,GIPIUR ENCAJE LAC-1030 C/15YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":34,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1032,"qty:""1""",name:,LAC-1032,description:,GIPIUR ENCAJE LAC-1032 C/15YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":35,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1033,"qty:""1""",name:,LAC-1033,description:,GIPIUR ENCAJE LAC-1033 C/15YD,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":36,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139147,"qty:""1""",name:,N139147,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":37,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139154,"qty:""1""",name:,N139154,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":38,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139193,"qty:""1""",name:,N139193,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":39,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139194,"qty:""1""",name:,N139194,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":40,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139241,"qty:""1""",name:,N139241,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":41,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139253,"qty:""1""",name:,N139253,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":42,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139254,"qty:""1""",name:,N139254,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":43,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139258,"qty:""1""",name:,N139258,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":44,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139315,"qty:""1""",name:,N139315,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":45,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199001,"qty:""1""",name:,N199001,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":46,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199017,"qty:""1""",name:,N199017,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":47,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199026,"qty:""1""",name:,N199026,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":48,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199060,"qty:""1""",name:,N199060,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":49,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199093,"qty:""1""",name:,N199093,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":50,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199100,"qty:""1""",name:,N199100,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":51,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N39100,"qty:""1""",name:,N39100,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":52,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N999150,"qty:""1""",name:,N999150,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":53,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N999281,"qty:""1""",name:,N999281,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":54,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1008,"qty:""1""",name:,NL-1008,description:,PLACA LISA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":55,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1009,"qty:""1""",name:,NL-1009,description:,PLACA ORIFICIOS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":56,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1011,"qty:""1""",name:,NL-1011,description:,PLACA CUELLO,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":57,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1014,"qty:""1""",name:,NL-1014,description:,PLACA PERFORADA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":58,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1029,"qty:""1""",name:,NL-1029,description:,COLLAR CARO NL-1029 C/12PZS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":59,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1030,"qty:""1""",name:,NL-1030,description:,COLLAR CARO NL-1030 C/12PZS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":60,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1031,"qty:""1""",name:,NL-1031,description:,COLLAR CARO NL-1031 C/12PZS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":61,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1032,"qty:""1""",name:,NL-1032,description:,COLLAR CARO NL-1032 C/12PZS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":62,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1033,"qty:""1""",name:,NL-1033,description:,COLLAR CARO NL-1033 C/12PZS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":63,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,PLCABIARAMBAR,"qty:""1""",name:,PLCABIARAMBAR,description:,PLANILLA CAVIAR AMBAR,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":64,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,PLRESORTESCRISTAL,"qty:""1""",name:,PLRESORTESCRISTAL,description:,PLANILA RESORTE CRISTAL,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":65,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,PLROMBOAMBAR,"qty:""1""",name:,PLROMBOAMBAR,description:,PLANILLA ROMBO AMBAR,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":66,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,PLROMBOPITCH,"qty:""1""",name:,PLROMBOPITCH,description:,PLANILLA ROMBO PITCH,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":67,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,BARRIL GRANDE,"qty:""1""",name:,BARRIL GRANDE,description:,BARRIL GRANDE,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":68,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,BARRIL CALABAZA,"qty:""1""",name:,BARRIL CALABAZA,description:,BARRIL CALABAZA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":69,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1033 C/15YD,"qty:""1""",name:,LAC-1033 C/15YD,description:,GIPIUR ENCAJE LAC-1033,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":70,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139313,"qty:""1""",name:,N139313,description:,COLLAR ECONOMICO ,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":71,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1007,"qty:""1""",name:,HF-1007,description:,HF-1007 Planilla,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":72,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1001,"qty:""1""",name:,HF-1001,description:,HF-1001 Planilla,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":73,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1039,"qty:""1""",name:,HF-1039,description:,HF-1039 Planilla,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":74,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1040,"qty:""1""",name:,HF-1040,description:,HF-1040 Planilla,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":75,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1038,"qty:""1""",name:,HF-1038,description:,HF-1038 Planilla,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":76,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1029,"qty:""1""",name:,HF-1029,description:,HF-1029 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":77,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1032,"qty:""1""",name:,HF-1032,description:,HF-1032 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":78,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1042,"qty:""1""",name:,HF-1042,description:,HF-1042 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":79,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1021,"qty:""1""",name:,HF-1021,description:,HF-1021 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":80,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1037,"qty:""1""",name:,HF-1037,description:,HF-1037 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":81,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1009,"qty:""1""",name:,HF-1009,description:,HF-1009 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":82,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,CINTURON TRENZA,"qty:""1""",name:,CINTURON TRENZA,description:,CINTURON TRENZA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":83,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N69570-MVA,"qty:""1""",name:,N69570-MVA,description:,Collar Economico N69570-MVA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":84,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N139100,"qty:""1""",name:,N139100,description:,Collar Economico N139100,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":85,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199143,"qty:""1""",name:,N199143,description:,Collar Economico N199143,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":86,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199156,"qty:""1""",name:,N199156,description:,Collar Economico N199156,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":87,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199158,"qty:""1""",name:,N199158,description:,Collar Economico N199158,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":88,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N199161,"qty:""1""",name:,N199161,description:,Collar Economico N199161,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":89,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N249001,"qty:""1""",name:,N249001,description:,Collar Economico N249001,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":90,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N249002,"qty:""1""",name:,N249002,description:,Collar Economico N249002,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":91,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N239001,"qty:""1""",name:,N239001,description:,Collar Economico N239001,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":92,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,N249003,"qty:""1""",name:,N249003,description:,Collar Economico N249003,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":93,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1026,"qty:""1""",name:,NLC-1026,description:,Collar Eco NLC-1026,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":94,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1043,"qty:""1""",name:,HF-1043,description:,HF-1043,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":95,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1002,"qty:""1""",name:,HF-1002,description:,HF-1002 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":96,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1005,"qty:""1""",name:,HF-1005,description:,HF-1005 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":97,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1008,"qty:""1""",name:,HF-1008,description:,HF-1008 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":98,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1011,"qty:""1""",name:,HF-1011,description:,HF-1011 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":99,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1012,"qty:""1""",name:,HF-1012,description:,HF-1012 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":100,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1014,"qty:""1""",name:,HF-1014,description:,HF-1014 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":101,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1017,"qty:""1""",name:,HF-1017,description:,HF-1017 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":102,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1018,"qty:""1""",name:,HF-1018,description:,HF-1018 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":103,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1019,"qty:""1""",name:,HF-1019,description:,HF-1019 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":104,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1023,"qty:""1""",name:,HF-1023,description:,HF-1023 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":105,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1024,"qty:""1""",name:,HF-1024,description:,HF-1024 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":106,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1026,"qty:""1""",name:,HF-1026,description:,HF-1026 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":107,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1034,"qty:""1""",name:,HF-1034,description:,HF-1034 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":108,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1041,"qty:""1""",name:,HF-1041,description:,HF-1041 PLANILLA,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":109,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1003,"qty:""1""",name:,LAC-1003,description:,LAC-1003 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":110,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1012,"qty:""1""",name:,LAC-1012,description:,LAC-1012 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":111,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1014,"qty:""1""",name:,LAC-1014,description:,LAC-1014 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":112,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1017,"qty:""1""",name:,LAC-1017,description:,LAC-1017 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":113,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1018,"qty:""1""",name:,LAC-1018,description:,LAC-1018 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":114,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1019,"qty:""1""",name:,LAC-1019,description:,LAC-1019 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":115,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1034,"qty:""1""",name:,LAC-1034,description:,LAC-1034 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":116,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1035,"qty:""1""",name:,LAC-1035,description:,LAC-1035 15 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":117,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,4U-04810,"qty:""1""",name:,4U-04810,description:,4U-04810 120 YDS,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":118,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1035,"qty:""1""",name:,NL-1035,description:,NL-1035,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":119,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1019,"qty:""1""",name:,NLC-1019,description:,NLC-1019,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":120,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1020,"qty:""1""",name:,NLC-1020,description:,NLC-1020,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":121,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1023,"qty:""1""",name:,NLC-1023,description:,NLC-1023,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":122,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,cinturon sencillo,"qty:""1""",name:,cinturon sencillo,description:,cinturon sencillo,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":123,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,cinturon censillo,"qty:""1""",name:,cinturon censillo,description:,cinturon sencillo,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":124,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1022,"qty:""1""",name:,NLC-1022,description:,NLC-1022,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":125,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1024,"qty:""1""",name:,NLC-1024,description:,NLC-1024,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":126,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1017,"qty:""1""",name:,NLC-1017,description:,NLC-1017,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":127,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1036,"qty:""1""",name:,NL-1036,description:,NL-1036,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":128,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1021,"qty:""1""",name:,NLC-1021,description:,NLC-1021,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":129,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1036,"qty:""1""",name:,NLC-1036,description:,NLC-1036,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":130,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,CHN-1001LARGE,"qty:""1""",name:,CHN-1001LARGE,description:,CHN-1001LARGE,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":131,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,CHN-1002SMALL,"qty:""1""",name:,CHN-1002SMALL,description:,CHN-1002SMALL,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":132,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1032-C,"qty:""1""",name:,HF-1032-C,description:,HF-1032-C,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":133,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1032-P,"qty:""1""",name:,HF-1032-P,description:,HF-1032-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":134,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1032-T,"qty:""1""",name:,HF-1032-T,description:,HF-1032-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":135,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1043-C,"qty:""1""",name:,HF-1043-C,description:,HF-1043-C,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":136,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1043-P,"qty:""1""",name:,HF-1043-P,description:,HF-1043-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":137,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1043-T,"qty:""1""",name:,HF-1043-T,description:,HF-1043-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":138,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1044-C,"qty:""1""",name:,HF-1044-C,description:,HF-1044-C,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":139,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1044-P,"qty:""1""",name:,HF-1044-P,description:,HF-1044-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":140,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1044-T,"qty:""1""",name:,HF-1044-T,description:,HF-1044-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":141,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1045,"qty:""1""",name:,HF-1045,description:,HF-1045,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":142,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1046-C,"qty:""1""",name:,HF-1046-C,description:,HF-1046-C,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":143,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1046-P,"qty:""1""",name:,HF-1046-P,description:,HF-1046-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":144,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1046-T,"qty:""1""",name:,HF-1046-T,description:,HF-1046-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":145,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1047-P,"qty:""1""",name:,HF-1047-P,description:,HF-1047-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":146,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1047-T,"qty:""1""",name:,HF-1047-T,description:,HF-1047-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":147,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1048-P,"qty:""1""",name:,HF-1048-P,description:,HF-1048-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":148,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1048-T,"qty:""1""",name:,HF-1048-T,description:,HF-1048-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":149,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1049,"qty:""1""",name:,HF-1049,description:,HF-1049,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":150,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1050-P,"qty:""1""",name:,HF-1050-P,description:,HF-1050-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":151,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1050-T,"qty:""1""",name:,HF-1050-T,description:,HF-1050-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":152,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1051-P,"qty:""1""",name:,HF-1051-P,description:,HF-1051-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":153,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1051-T,"qty:""1""",name:,HF-1051-T,description:,HF-1051-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":154,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1052-P,"qty:""1""",name:,HF-1052-P,description:,HF-1052-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":155,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1052-T,"qty:""1""",name:,HF-1052-T,description:,HF-1052-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":156,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1053-P,"qty:""1""",name:,HF-1053-P,description:,HF-1053-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":157,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1053-T,"qty:""1""",name:,HF-1053-T,description:,HF-1053-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":158,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1054-P,"qty:""1""",name:,HF-1054-P,description:,HF-1054-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":159,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1054-T,"qty:""1""",name:,HF-1054-T,description:,HF-1054-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":160,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1055-C,"qty:""1""",name:,HF-1055-C,description:,HF-1055-C,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":161,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1055-P,"qty:""1""",name:,HF-1055-P,description:,HF-1055-P,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":162,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,HF-1055-T,"qty:""1""",name:,HF-1055-T,description:,HF-1055-T,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":163,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1036 Guipur 45 Yards,"qty:""1""",name:,LAC-1036 Guipur 45 Yards,description:,LAC-1036 Guipur 45 Yards,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":164,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1037 Guipur 45 Yards,"qty:""1""",name:,LAC-1037 Guipur 45 Yards,description:,LAC-1037 Guipur 45 Yards,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":165,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1050,"qty:""1""",name:,NL-1050,description:,NL-1050,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":166,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1053,"qty:""1""",name:,NL-1053,description:,NL-1053,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":167,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NL-1055,"qty:""1""",name:,NL-1055,description:,NL-1055,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":168,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,NLC-1016,"qty:""1""",name:,NLC-1016,description:,NLC-1016,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]
                    {"id":169,"urlimage:""\/docs/""","categoryid:""1""","active:""Y""",code:,LAC-1035-2 45 yards,"qty:""1""",name:,LAC-1035-2 45 yards,description:,LAC-1035-2 45 yards,"unit:""DOZEN""",price:,0.99,price2:,1.99,"cost:""5""","type:""general""",modifiers:[]                    
*/                         
  //                 ]');  
                         
                         
                         
                         
                         
                         
                         
                         
                         
                         
                         
                         
                         
                         
                         
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                   
                                        
//                  {"urlimage":"\/docs\\","categoryid":"0","active":"Y","code":"TEST-1234","qty":"100","name":"TEST-1234","description":"","unit":"DOZEN","price":"10","price2":"20","cost":"5","type":"general","modifiers":[]}
//                   [{"id": 1,"urlimage":"\/docs\\Desert.jpg","categoryid":0,"active":"Y","code":"TEST-1234","qty":200,"name":"TEST-1234","description":"kjkjk","unit":"DOZEN","price":"20","price2":"10","cost":"5","type":"general","modifiers":[]},                    
//                    {"id": 2,"urlimage":"\/docs\\Desert.jpg","categoryid":0,"active":"Y","code":"TEST-2345","qty":200,"name":"TEST-2345","description":"kjkjk-1122","unit":"PIS","price":"10","price2":"20","cost":"5","type":"general","modifiers":[]}]');
//                  1234","qty":"200","name":"TEST-1234","description":"kjkjk","unit":"DOZEN","price":"20","price2":"10","cost":"5","type":"general","modifiers":[]}                   
/*                         
        if ($items==false){
            die("Failed to add items");
        } else {         
            $itemMdl = new StoredItemsModel();
            foreach($items as $item){
                $itemMdl->create($item);
            }            
            echo("Inserted Items.<br/>");
        }                
  */                       
                         
        $stocks = json_decode('
                         [
                          {"storeditemid":1  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":2  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":3  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":4  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":5  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":6  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":7  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":8  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":9  ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":10 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":11 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":12 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":13 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":14 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":15 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":16 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":17 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":18 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":19 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":20 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":21 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":22 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":23 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":24 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":25 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":26 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":27 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":28 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":29 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":30 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":31 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":32 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":33 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":34 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":35 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":36 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":37 ,"locationid":0,"stocklevel":100 },                          
                          {"storeditemid":38 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":39 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":40 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":41 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":42 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":43 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":44 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":45 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":46 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":47 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":48 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":49 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":50 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":51 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":52 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":53 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":54 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":55 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":56 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":57 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":58 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":59 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":60 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":61 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":62 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":63 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":64 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":65 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":66 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":67 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":68 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":69 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":70 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":71 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":72 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":73 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":74 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":75 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":76 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":77 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":78 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":79 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":80 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":81 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":82 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":83 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":84 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":85 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":86 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":87 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":88 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":89 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":90 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":91 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":92 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":93 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":94 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":95 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":96 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":97 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":98 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":99 ,"locationid":0,"stocklevel":100 },
                          {"storeditemid":100,"locationid":0,"stocklevel":100 },
                          {"storeditemid":101,"locationid":0,"stocklevel":100 },
                          {"storeditemid":102,"locationid":0,"stocklevel":100 },
                          {"storeditemid":103,"locationid":0,"stocklevel":100 },
                          {"storeditemid":104,"locationid":0,"stocklevel":100 },
                          {"storeditemid":105,"locationid":0,"stocklevel":100 },
                          {"storeditemid":106,"locationid":0,"stocklevel":100 },
                          {"storeditemid":107,"locationid":0,"stocklevel":100 },
                          {"storeditemid":108,"locationid":0,"stocklevel":100 },
                          {"storeditemid":109,"locationid":0,"stocklevel":100 },
                          {"storeditemid":110,"locationid":0,"stocklevel":100 },
                          {"storeditemid":111,"locationid":0,"stocklevel":100 },
                          {"storeditemid":112,"locationid":0,"stocklevel":100 },
                          {"storeditemid":113,"locationid":0,"stocklevel":100 },
                          {"storeditemid":114,"locationid":0,"stocklevel":100 },
                          {"storeditemid":115,"locationid":0,"stocklevel":100 },
                          {"storeditemid":116,"locationid":0,"stocklevel":100 },
                          {"storeditemid":117,"locationid":0,"stocklevel":100 },
                          {"storeditemid":118,"locationid":0,"stocklevel":100 },
                          {"storeditemid":119,"locationid":0,"stocklevel":100 },
                          {"storeditemid":120,"locationid":0,"stocklevel":100 },
                          {"storeditemid":121,"locationid":0,"stocklevel":100 },
                          {"storeditemid":122,"locationid":0,"stocklevel":100 },
                          {"storeditemid":123,"locationid":0,"stocklevel":100 },
                          {"storeditemid":124,"locationid":0,"stocklevel":100 },
                          {"storeditemid":125,"locationid":0,"stocklevel":100 },
                          {"storeditemid":126,"locationid":0,"stocklevel":100 },
                          {"storeditemid":127,"locationid":0,"stocklevel":100 },
                          {"storeditemid":128,"locationid":0,"stocklevel":100 },
                          {"storeditemid":129,"locationid":0,"stocklevel":100 },
                          {"storeditemid":130,"locationid":0,"stocklevel":100 },
                          {"storeditemid":131,"locationid":0,"stocklevel":100 },
                          {"storeditemid":132,"locationid":0,"stocklevel":100 },
                          {"storeditemid":133,"locationid":0,"stocklevel":100 },
                          {"storeditemid":134,"locationid":0,"stocklevel":100 },
                          {"storeditemid":135,"locationid":0,"stocklevel":100 },
                          {"storeditemid":136,"locationid":0,"stocklevel":100 },
                          {"storeditemid":137,"locationid":0,"stocklevel":100 },
                          {"storeditemid":138,"locationid":0,"stocklevel":100 },
                          {"storeditemid":139,"locationid":0,"stocklevel":100 },
                          {"storeditemid":140,"locationid":0,"stocklevel":100 },
                          {"storeditemid":141,"locationid":0,"stocklevel":100 },
                          {"storeditemid":142,"locationid":0,"stocklevel":100 },
                          {"storeditemid":143,"locationid":0,"stocklevel":100 },
                          {"storeditemid":144,"locationid":0,"stocklevel":100 },
                          {"storeditemid":145,"locationid":0,"stocklevel":100 },
                          {"storeditemid":146,"locationid":0,"stocklevel":100 },
                          {"storeditemid":147,"locationid":0,"stocklevel":100 },
                          {"storeditemid":148,"locationid":0,"stocklevel":100 },
                          {"storeditemid":149,"locationid":0,"stocklevel":100 },
                          {"storeditemid":150,"locationid":0,"stocklevel":100 },
                          {"storeditemid":151,"locationid":0,"stocklevel":100 },
                          {"storeditemid":152,"locationid":0,"stocklevel":100 },
                          {"storeditemid":153,"locationid":0,"stocklevel":100 },
                          {"storeditemid":154,"locationid":0,"stocklevel":100 },
                          {"storeditemid":155,"locationid":0,"stocklevel":100 },
                          {"storeditemid":156,"locationid":0,"stocklevel":100 },
                          {"storeditemid":157,"locationid":0,"stocklevel":100 },
                          {"storeditemid":158,"locationid":0,"stocklevel":100 },
                          {"storeditemid":159,"locationid":0,"stocklevel":100 },
                          {"storeditemid":160,"locationid":0,"stocklevel":100 },
                          {"storeditemid":161,"locationid":0,"stocklevel":100 },
                          {"storeditemid":162,"locationid":0,"stocklevel":100 },
                          {"storeditemid":163,"locationid":0,"stocklevel":100 },
                          {"storeditemid":164,"locationid":0,"stocklevel":100 },
                          {"storeditemid":165,"locationid":0,"stocklevel":100 },
                          {"storeditemid":166,"locationid":0,"stocklevel":100 },
                          {"storeditemid":167,"locationid":0,"stocklevel":100 },
                          {"storeditemid":168,"locationid":0,"stocklevel":100 },
                          {"storeditemid":169,"locationid":0,"stocklevel":100 }
                         ]');             
/*                                          
        if ($stocks==false){              
            die("Failed to add stocks");  
        } else {                          
            $stockMdl = new StockModel(); 
            foreach($stocks as $stock){   
                $stockMdl->create($stock->storeditemid,$stock->locationid,$stock->stocklevel);
            }                             
            echo("Inserted stock.<br/>"); 
        }                                 
                                          
*/                                          
        $stock_histories = json_decode('  
                         [
                          {"storeditemid":1  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":2  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":3  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":4  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":5  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":6  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":7  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":8  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":9  ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":10 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":11 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":12 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":13 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":14 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":15 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":16 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":17 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":18 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":19 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":20 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":21 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":22 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":23 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":24 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":25 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":26 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":27 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":28 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":29 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":30 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":31 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":32 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":33 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":34 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":35 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":36 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":37 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":38 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":39 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":40 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":41 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":42 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":43 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":44 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":45 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":46 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":47 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":48 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":49 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":50 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":51 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":52 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":53 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":54 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":55 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":56 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":57 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":58 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":59 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":60 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":61 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":62 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":63 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":64 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":65 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":66 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":67 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":68 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":69 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":70 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":71 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":72 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":73 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":74 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":75 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":76 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":77 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":78 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":79 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":80 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":81 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":82 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":83 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":84 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":85 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":86 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":87 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":88 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":89 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":90 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":91 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":92 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":93 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":94 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":95 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":96 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":97 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":98 ,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":99 ,"locationid":0,"type":"Stock Added","amount":100},                                           
                          {"storeditemid":100,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":101,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":102,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":103,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":104,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":105,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":106,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":107,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":108,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":109,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":110,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":111,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":112,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":113,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":114,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":115,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":116,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":117,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":118,"locationid":0,"type":"Stock Added","amount":100},                                           
                          {"storeditemid":119,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":120,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":121,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":122,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":123,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":124,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":125,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":126,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":127,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":128,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":129,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":130,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":131,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":132,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":133,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":134,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":135,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":136,"locationid":0,"type":"Stock Added","amount":100},                                                                                                                                                                                                                                                                                                                        
                          {"storeditemid":137,"locationid":0,"type":"Stock Added","amount":100},                                           
                          {"storeditemid":138,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":139,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":140,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":141,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":142,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":143,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":144,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":145,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":146,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":147,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":148,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":149,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":150,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":151,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":152,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":153,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":154,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":155,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":156,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":157,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":158,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":159,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":160,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":161,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":162,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":163,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":164,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":165,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":166,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":167,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":168,"locationid":0,"type":"Stock Added","amount":100},
                          {"storeditemid":169,"locationid":0,"type":"Stock Added","amount":100}                          
                          ]');                                                             
                                                                                                         
/*                                                                                           
        if ($stock_histories==false){                                                      
            die("Failed to add stock_histories");                                          
        } else {                                                                           
            $stockMdl = new StockHistoryModel();                                           
            foreach($stock_histories as $stock){                                           
                $stockMdl->create($stock->storeditemid,$stock->locationid,$stock->type,$stock->amount);
            }                                                                              
            echo("Inserted stock_histories.<br/>");                                        
        }                                                                                  
                                                                                           
*/                                                                                           
                                                                                           
 $categories = json_decode('[{"id": 1,"name": "MATERIAL","dt": "2016-08-31 04:54:21"},
 {"id": 2,"name": "CINTURON","dt": "2016-08-31 04:54:31"}, 
 {"id": 3,"name": "PLANILLA","dt": "2016-08-31 04:56:32"}, 
 {"id": 4,"name": "CADENA","dt": "2016-08-31 04:57:01"}, 
 {"id": 5,"name": "LACE","dt": "2016-08-31 04:57:01"}, 
 {"id": 6,"name": "MESH","dt": "2016-08-31 04:57:01"}, 
 {"id": 7,"name": "COLLAR","dt": "2016-08-31 04:57:01"}]');
                                                                                           
        if ($categories==false){                                                         
            die("Failed to add categories");                                             
        } else {                                                                         
            $catMdl = new CategoriesModel();                                             
            foreach($categories as $category){                                           
                $catMdl->create($category->name);                                        
            }                                                                            
            echo("Inserted Categories.<br/>");                                           
        }                                                                                
                                                                                           
        $locations = json_decode('[{"id": 1, "name":"Mexico", "dt":"0000-00-00 00:00:00"}]');
                                                                                           
        if ($locations==false){                                                            
            die("Failed to add locations");                                                
        } else {                                                                           
            $locMdl = new LocationsModel();                                                
            foreach($locations as $location){                                              
                $locMdl->create($location->name);                                          
            }                                                                              
            echo("Inserted Locations.<br/>");                                              
        }                                                                                  
                                                                                           
        $devices = json_decode('[{"id": 1, "name":"Register 1", "locationid":1, "type":"general_register", "dt":"0000-00-00 00:00:00"}]');
                                                                                           
        if ($devices==false){                                                              
            die("Failed to add devices");                                                  
        } else {                                                                           
            $devMdl = new DevicesModel();                                                  
            foreach($devices as $device){                                                  
                $devMdl->create($device);                                                  
            }                                                                              
  //          echo("Inserted Devices.<br/>");                                              
        }                                                                                  
                                                                                           
        $customers = json_decode('[{"id":1,"name":"Jo Doe", "email":"jdoe@domainname.com", "address":"10 Fake St", "phone":"99999999", "mobile":"111111111", "suburb":"Faketown", "state":"NSW", "postcode":"2000", "country":"Australia", "notes":"", "dt":"0000-00-00 00:00:00"},
                        {"id": 2, "name":"Jane Doe", "email":"jdoe@domainname.com", "address":"10 Fake St", "phone":"99999999", "mobile":"111111111", "suburb":"Faketown", "state":"NSW", "postcode":"2000", "country":"Australia", "notes":"", "dt":"0000-00-00 00:00:00"}]');
                                                                                           
        if ($customers==false){                                                            
            die("Failed to add customers");                                                
        } else {                                                                           
            $devMdl = new CustomerModel();                                                 
            foreach($customers as $cust){                                                  
//                $devMdl->create($cust->email, $cust->name, $cust->phone, $cust->mobile, $cust->address, $cust->suburb, $cust->postcode, $cust->state, $cust->country);
            }                                                                              
//            echo("Inserted Customers.<br/>");                                            
        }                                                                                  
                                                                                           
    }                                                                                      
}                                                                                          
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
                                                                                           
