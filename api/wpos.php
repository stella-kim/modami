<?php
/**
 * wpos.php is part of Wallace Point of Sale system (WPOS) API
 *
 * wpos.php is used to route incoming API requests and provide access control for API endpoints.
 * It also allows the processing of multiple api requests in one go.
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
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      Class created 15/1/13 12:01 PM
 */
// Set the root of the install
$_SERVER['APP_ROOT'] = "/";

require($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/config.php');
// setup api error handling
set_error_handler("errorHandler", E_ERROR | E_WARNING | E_PARSE);
set_exception_handler("exceptionHandler");

require($_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library/wpos/AutoLoader.php'); //Autoload all the classes.
if (!isset($_REQUEST['a'])) {
    exit;
}
$result = ["errorCode" => "OK", "error" => "OK", "data" => ""];

$auth = new Auth();
// Check for auth request
if ($_REQUEST['a'] == "auth" || $_REQUEST['a'] == "authrenew") {
    $data = json_decode($_REQUEST['data']);
    if ($_REQUEST['a'] == "auth"){
        $authres = $auth->login($data->username, $data->password, isset($data->getsessiontokens));
    } else {
        $authres = $auth->renewTokenSession($data->username, $data->auth_hash);
    }
    if ($data !== false) {
        switch ($authres){
            // will be included when elephantIO is upgraded, no reliable exceptions in current version
            /*case -2: // user authenticated successfully, but could not be authenticated with the feed server, fall through to normal login
                $result['warning'] = "Warning: Feedserver authentication attempt failed.";*/
            case true:
                $result['data'] = $auth->getUser();
                break;

            case -1:
                $result['errorCode'] = "authdenied";
                $result['error'] = "Your account has been disabled, please contact your system administrator!";
                break;

            case false:
            default:
                $result['errorCode'] = "authdenied";
                $result['error'] = "Access Denied!";
        }
    } else {
        $result['errorCode'] = "jsondec";
        $result['error'] = "Error decoding the json request!";
    }
    returnResult($result);
} else if ($_REQUEST['a'] == "logout") {
    $auth->logout();
    returnResult($result);
}
// the hello request checks server connectivity aswell as providing the status of the logged in user
if ($_REQUEST['a'] == "hello") {
    if ($auth->isLoggedIn()) {
        $result['data'] = $auth->getUser();
    } else {
        $result['data'] = false;
    }
    returnResult($result);
}
// check login status and exit if not logged in
if (!$auth->isLoggedIn()) {
    $result['errorCode'] = "auth";
    $result['error'] = "Access Denied!";
    returnResult($result);
}
// Decode JSON data if provided
if (isset($_REQUEST['data']) && $_REQUEST['data']!=""){
    if (($requests=json_decode($_REQUEST['data']))==false){
        $result['error'] = "Could not parse the provided json request";
        returnResult($result);
    }
} else {
    $requests = new stdClass();
}
// Route the provided requests
if ($_REQUEST['a']!=="multi"){
    // route a single api call
    $result = routeApiCall($_REQUEST['a'], $requests, $result);
} else {
    // run a multi api call
    if (empty($requests)){
        $result['error'] = "No API request data provided";
        returnResult($result);
    }
    // loop through each request, stop & return the first error if encountered
    foreach ($requests as $action=>$data){
        if ($data==null) {
            $data = new stdClass();
        }
        $tempresult = routeApiCall($action, $data, $result);
        if ($tempresult['error']=="OK"){
            // set data and move to the next request
            $result['data'][$action] = $tempresult['data'];
        } else {
            $result['error'] = $tempresult['error'];
            break;
        }
    }
}
returnResult($result);

// API FUNCTIONS
/**
 * routes api calls and returns the result, allows for multiple API calls at once
 * @param $action
 * @param $data
 * @param $result
 * @return array|mixed
 */
 
function GenerateThumbnail($im_filename,$th_filename,$max_width,$max_height,$quality = 0.75)
{
// The original image must exist
if(is_file($im_filename))
{
    // Let's create the directory if needed
    $th_path = dirname($th_filename);
    if(!is_dir($th_path))
        mkdir($th_path, 0777, true);
    // If the thumb does not aleady exists
    if(!is_file($th_filename))
    {
        // Get Image size info
        list($width_orig, $height_orig, $image_type) = @getimagesize($im_filename);
        if(!$width_orig)
            return 2;
        switch($image_type)
        {
            case 1: $src_im = @imagecreatefromgif($im_filename);    break;
            case 2: $src_im = @imagecreatefromjpeg($im_filename);   break;
            case 3: $src_im = @imagecreatefrompng($im_filename);    break;
        }
        if(!$src_im)
            return 3;


        $aspect_ratio = (float) $height_orig / $width_orig;

        $thumb_height = $max_height;
        $thumb_width = round($thumb_height / $aspect_ratio);
        if($thumb_width > $max_width)
        {
            $thumb_width    = $max_width;
            $thumb_height   = round($thumb_width * $aspect_ratio);
        }

        $width = $thumb_width;
        $height = $thumb_height;

        $dst_img = @imagecreatetruecolor($width, $height);
        if(!$dst_img)
            return 4;
        $success = @imagecopyresampled($dst_img,$src_im,0,0,0,0,$width,$height,$width_orig,$height_orig);
        if(!$success)
            return 4;
        switch ($image_type) 
        {
            case 1: $success = @imagegif($dst_img,$th_filename); break;
            case 2: $success = @imagejpeg($dst_img,$th_filename,intval($quality*100));  break;
            case 3: $success = @imagepng($dst_img,$th_filename,intval($quality*9)); break;
        }
        if(!$success)
            return 4;
    }
    return 0;
}
return 1;
} 
 
function routeApiCall($action, $data, $result) {
    global $auth;
    $notinprev = false;
    // Check for action in unprotected area (does not require permission)
    switch ($action) {
        // POS Specific
        case "config/get":
            $setup = new WposPosSetup($data);
            $result = $setup->getDeviceRecord($result);
            break;

        case "items/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getItems($result);
            break;

        case "sales/get":
            $jsondata = new WposPosData($data);
            $result = $jsondata->getSales($result);
            break;

        case "tax/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getTaxes($result);
            break;

        case "customers/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getCustomers($result);
            break;

        case "devices/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getPosDevices($result);
            break;

        case "locations/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getPosLocations($result);
            break;

        case "orders/set":
            $sale = new WposPosSale($data);
            $result = $sale->setOrder($result);
            break;

        case "orders/remove":
            $sale = new WposPosSale($data);
            $result = $sale->removeOrder($result);
            break;

        case "sales/add":
            $sale = new WposPosSale($data);
            $result = $sale->insertTransaction($result);
            break;

        case "sales/void": // also used for sale refunds
            $sale = new WposPosSale($data, false);
            $result = $sale->insertVoid($result);
            break;

        case "sales/search":
            $sale = new WposPosData();
            if (isset($data)) {
                $result = $sale->searchSales($data, $result);
            }
            break;

        case "sales/updatenotes":
            $sale = new WposPosSale($data, false);
            $result = $sale->updateTransationNotes($result);
            break;

        case "transactions/get":
            $trans = new WposTransactions($data);
            $result = $trans->getTransaction($result);
            break;

        default:
            $notinprev = true;
    }
    if ($notinprev == false) { // an action has been executed: return the data
        return $result;
    }
    $notinprev = false;

    // Check if user is allowed to use this API request                             
    if ($auth->isUserAllowed($action) === false) {
        $result['errorCode'] = "priv";
        $result['error'] = "You do not have permission to perform this action.";
        return $result;
    }
    // Check in permission protected API calls
    switch ($action) {
    // admin only
        // device setup
        case "devices/setup":
            $setup = new WposPosSetup($data);
            $result = $setup->setupDevice($result);
            break;

        // stored items
        case "adminconfig/get":
            $setupMdl = new WposPosSetup();
            $result = $setupMdl->getAdminConfig($result);
            break;

        case "items/add":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->addStoredItem($result);

            $stockMdl = new WposAdminStock($data);            
            //hjkim
            $result2 = $stockMdl->addStock2($result2);
            break;

        case "items/edit":

            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->updateStoredItem($result);
            
            $stockMdl = new WposAdminStock($data);
            //hjkim
//            $result2 = $stockMdl->addStock2($result2);            
            $result2 = $stockMdl->adjustStockLevel($result);

            break;

        case "items/delete":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->deleteStoredItem($result);
            break;
        // suppliers
        case "suppliers/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getSuppliers($result);
            break;

        case "suppliers/add":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->addSupplier($result);
            break;

        case "suppliers/edit":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->updateSupplier($result);

            break;

        case "suppliers/delete":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->deleteSupplier($result);
            break;
        // categories
        case "categories/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getCategories($result);
            break;

        case "categories/add":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->addCategory($result);
            break;

        case "categories/edit":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->updateCategory($result);
            break;

        case "categories/delete":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->deleteCategory($result);
            break;
        // suppliers
        case "stock/get":
            $jsondata = new WposPosData();
            $result = $jsondata->getStock($result);
            break;
        case "stock/add":
            $stockMdl = new WposAdminStock($data);
            $result = $stockMdl->addStock($result);
            break;
        case "stock/set":
            $stockMdl = new WposAdminStock($data);
            $result = $stockMdl->setStockLevel($result);
            break;
        case "stock/transfer":
            $stockMdl = new WposAdminStock($data);
            $result = $stockMdl->transferStock($result);
            break;
        case "stock/history":
            $stockMdl = new WposAdminStock($data);
            $result = $stockMdl->getStockHistory($result);
            
            break;
        case "stock/level":
            $stockMdl = new WposAdminStock($data);
            $result = $stockMdl->getStock($result);
            
            break;

        // customers
        case "customers/add":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->addCustomer($result);
            break;
        case "customers/edit":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->updateCustomer($result);
            break;
        case "customers/delete":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->deleteCustomer($result);
            break;
        case "customers/contacts/add":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->addContact($result);
            break;
        case "customers/contacts/edit":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->updateContact($result);
            break;
        case "customers/contacts/delete":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->deleteContact($result);
            break;
        case "customers/setaccess":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->setAccess($result);
            break;
        case "customers/setpassword":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->setPassword($result);
            break;
        case "customers/sendreset":
            $custMdl = new WposAdminCustomers($data);
            $result = $custMdl->sendResetEmail($result);
            break;
        // USERS
        case "users/get":
            $data = new WposPosData();
            $result = $data->getUsers($result);
            break;
        case "users/add":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->addUser($result);
            break;
        case "users/edit":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->updateUser($result);
            break;
        case "users/delete":
            $adminMdl = new WposAdminItems($data);
            $result = $adminMdl->deleteUser($result);
            break;
        case "users/disable":
            $setup = new WposAdminItems($data);
            $result = $setup->setUserDisabled($result);
            break;

        // DEVICES
        case "devices/add":
            $setup = new WposPosSetup($data);
            $result = $setup->addDevice($result);
            break;
        case "devices/edit":
            $setup = new WposPosSetup($data);
            $result = $setup->updateDevice($result);
            break;
        case "devices/delete":
            $setup = new WposPosSetup($data);
            $result = $setup->deleteDevice($result);
            break;
        case "devices/disable":
            $setup = new WposPosSetup($data);
            $result = $setup->setDeviceDisabled($result);
            break;

        // LOCATIONS
        case "locations/add":
            $setup = new WposPosSetup($data);
            $result = $setup->addLocation($result);
            break;
        case "locations/edit":
            $setup = new WposPosSetup($data);
            $result = $setup->updateLocationName($result);
            break;
        case "locations/delete":
            $setup = new WposPosSetup($data);
            $result = $setup->deleteLocation($result);
            break;
        case "locations/disable":
            $setup = new WposPosSetup($data);
            $result = $setup->setLocationDisabled($result);
            break;

        // tax
        case "tax/rules/add":
            $tax = new WposAdminItems($data);
            $result = $tax->addTaxRule($result);
            break;
        case "tax/rules/edit":
            $tax = new WposAdminItems($data);
            $result = $tax->updateTaxRule($result);
            break;
        case "tax/rules/delete":
            $tax = new WposAdminItems($data);
            $result = $tax->deleteTaxRule($result);
            break;
        case "tax/items/add":
            $tax = new WposAdminItems($data);
            $result = $tax->addTaxItem($result);
            break;
        case "tax/items/edit":
            $tax = new WposAdminItems($data);
            $result = $tax->updateTaxItem($result);
            break;
        case "tax/items/delete":
            $tax = new WposAdminItems($data);
            $result = $tax->deleteTaxItem($result);
            break;

        // SALES (All transactions)
        case "sales/delete":
            $aSaleMdl = new WposTransactions($data);
            $result = $aSaleMdl->deleteSale($result);
            break;
        case "sales/deletevoid":
            $aSaleMdl = new WposTransactions($data);
            $result = $aSaleMdl->removeVoidRecord($result);
            break;
        case "sales/adminvoid": // the admin add void method, only requires sale id and reason
            $aSaleMdl = new WposTransactions($data);
            $result = $aSaleMdl->voidSale($result);
            break;

        // INVOICES
        case "invoices/get":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->getInvoices($result);
            break;

        case "invoices/add":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->createInvoice($result);
            break;

        case "invoices/edit":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->updateInvoice($result);
            break;

        case "invoices/delete":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->removeInvoice($result);
            break;

        case "invoices/items/add":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->addItem($result);
            break;

        case "invoices/items/edit":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->updateItem($result);
            break;

        case "invoices/items/delete":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->removeItem($result);
            break;

        case "invoices/payments/add":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->addPayment($result);
            break;

        case "invoices/payments/edit":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->updatePayment($result);
            break;

        case "invoices/payments/delete":
            $invMdl = new WposInvoices($data);
            $result = $invMdl->removePayment($result);
            break;

        case "invoices/history/get":
            $invMdl = new WposTransactions($data);
            $result = $invMdl->getTransactionHistory($result);
            break;
        case "invoices/generate":
            $invMdl = new WposTransactions(null, $_REQUEST['id'], false);
            $invMdl->generateInvoice();
            break;
        case "invoices/email":
            $invMdl = new WposTransactions($data);
            $result = $invMdl->emailInvoice($result);
            break;

        // STATS
        case "stats/general": // general overview stats
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getOverviewStats($result);
            break;
        case "stats/takings": // account takings stats, categorized by payment method
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getCountTakingsStats($result);
            break;
        case "stats/itemselling": // whats selling, grouped by stored items
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getWhatsSellingStats($result);
            break;
        case "stats/categoryselling": // whats selling, grouped by categories
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getWhatsSellingStats($result, 1);
            break;
        case "stats/supplyselling": // whats selling, grouped by suppliers
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getWhatsSellingStats($result, 2);
            break;
        case "stats/stock": // current stock levels
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getStockLevels($result);
            break;
        case "stats/devices": // whats selling, grouped by stored items
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getDeviceBreakdownStats($result);
            break;
        case "stats/locations": // whats selling, grouped by stored items
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getDeviceBreakdownStats($result, 'location');
            break;
        case "stats/users": // whats selling, grouped by stored items
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getDeviceBreakdownStats($result, 'user');
            break;
        case "stats/tax": // whats selling, grouped by stored items
            $statsMdl = new WposAdminStats($data);
            $result = $statsMdl->getTaxStats($result);
            break;

        // GRAPH
        case "graph/general": // like the general stats, but in graph form/time.
            $graphMdl = new WposAdminGraph($data);
            $result = $graphMdl->getOverviewGraph($result);
            break;
        case "graph/takings": // like the general stats, but in graph form/time.
            $graphMdl = new WposAdminGraph($data);
            $result = $graphMdl->getMethodGraph($result);
            break;
        case "graph/devices": // like the general stats, but in graph form/time.
            $graphMdl = new WposAdminGraph($data);
            $result = $graphMdl->getDeviceGraph($result);
            break;
        case "graph/locations": // like the general stats, but in graph form/time.
            $graphMdl = new WposAdminGraph($data);
            $result = $graphMdl->getLocationGraph($result);
            break;

        // Admin/Global Config
        case "settings/get":
            $configMdl = new WposAdminSettings();
            $configMdl->setName($data->name);
            $result = $configMdl->getSettings($result);
            break;
        case "settings/general/get":
            $configMdl = new WposAdminSettings();
            $configMdl->setName("general");
            $result = $configMdl->getSettings($result);
            break;
        case "settings/pos/get":
            $configMdl = new WposAdminSettings();
            $configMdl->setName("pos");
            $result = $configMdl->getSettings($result);
            break;
        case "settings/invoice/get":
            $configMdl = new WposAdminSettings();
            $configMdl->setName("invoice");
            $result = $configMdl->getSettings($result);
            break;

        case "settings/set":
            $configMdl = new WposAdminSettings($data);
            $result = $configMdl->saveSettings($result);
            break;
        case "settings/general/set":
            $configMdl = new WposAdminSettings($data);
            $configMdl->setName("general");
            $result = $configMdl->saveSettings($result);

            break;
        case "settings/pos/set":
            $configMdl = new WposAdminSettings($data);
            $configMdl->setName("pos");
            $result = $configMdl->saveSettings($result);
            break;
        case "settings/invoice/set":
            $configMdl = new WposAdminSettings($data);
            $configMdl->setName("invoice");
            $result = $configMdl->saveSettings($result);
            break;
        case "settings/google/authinit":
            GoogleIntegration::initGoogleAuth();
            break;
        case "settings/google/authremove":
            GoogleIntegration::removeGoogleAuth();
            break;
        case "settings/xero/oauthinit":
            XeroIntegration::initXeroAuth();
            break;
        case "settings/xero/oauthcallback":
            XeroIntegration::processCallbackAuthCode();
            break;
        case "settings/xero/oauthremove":
            XeroIntegration::removeXeroAuth();
            break;
        case "settings/xero/configvalues":
            $result = XeroIntegration::getXeroConfigValues($result);
            break;
        case "settings/xero/export":
            $result = XeroIntegration::exportXeroSales($data->stime, $data->etime);
            break;

        case "node/status":
            $Sserver = new WposSocketControl();
            $result = $Sserver->isServerRunning($result);
            break;

        case "node/start":
            $Sserver = new WposSocketControl();
            $result = $Sserver->startSocketServer($result);
            break;

        case "node/stop":
            $Sserver = new WposSocketControl();
            $result = $Sserver->stopSocketServer($result);
            break;

        case "node/restart":
            $Sserver = new WposSocketControl();
            $result = $Sserver->restartSocketServer($result);
            break;

        case "db/backup":
            $util = new WposAdminUtilities();
            $util->backUpDatabase();
            break;

        case "logs/list":
            $result['data'] = Logger::ls();
            break;

        case "logs/read":
            $result['data'] = Logger::read($data->filename);
            break;

        case "file/upload":

            if (isset($_FILES['file'])) {

                $uploaddir = 'docs';
                
                $newpath = $uploaddir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);              
                $ext = substr(strrchr($newpath,"."),1);
                $ext = strtolower($ext);		
                
    
//                Logger::write("newpath", "STOCK",$newpath);                
                if ($ext =="jpg" or $ext =="png"  or $ext =="csv" ){
                  if (move_uploaded_file($_FILES['file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . $newpath) !== false) {
                      $result['data'] = ["path" => "/" . $newpath];
                  } else {
                      $result['error'] = "There was an error uploading the file " . $newpath;
                  }
                }
                else {
                  $result['error'] = "Invalid File Extension. Please Try again.";      
                  break; 
                }  
                
                
                if ($ext =="csv"){
                  $input = fopen($_SERVER['DOCUMENT_ROOT']."/".$newpath, 'a+');
                  $items = [];               
                	// if the csv file contain the table header leave this line
                	$row = fgetcsv($input, 1024, ','); // here you got the header
              		while ($row = fgetcsv($input, 1024, ',')) {
                    $id      =  $row[0];
                    $name    =  $row[3];

                    $dbMdl = new DbConfig();
                    //$arr = array( 'row_id' => '1', 'name' => 'George' );
                    $arr = array();
                    $arr['id']          = $id;
                     $arr['urlimage']    = $row[1];  
                    
                    $arr['categoryid']  = $row[2];
                    $arr['active']      = $row[11];
                    $arr['code']        = $row[3];
                    $arr['qty']         = $row[10];     //???????????????????????????????
                    $arr['name']        = $row[3];
                    $arr['description'] = $row[4];
//                  $arr['subline']     = $row[5];
                    $arr['unit']        = $row[5];
                    $arr['price']       = $row[6];
                    $arr['price2']      = $row[7];
                    $arr['price3']      = $row[8];
//                  $arr['price4']      = $row[9];
                    $arr['cost']        = $row[9];
                    $arr['type']        = "general";
                    $arr['modifiers']   = [];                    

                    $stock              = $row[10];
                                        
                    $data=   json_encode($arr, JSON_UNESCAPED_SLASHES);
                    $sql = "INSERT INTO `stored_items` (`id`,`code`,`name`, `data`,`price`) VALUES (".$id.","."'".$arr['code']."','".$name."','".$data."','".$arr['price']."'".") ON DUPLICATE KEY UPDATE code=VALUES(code) ,name=VALUES(name) ,data =VALUES(data) ";                      
                    $dbMdl->_db->exec($sql);
      
                    $arr['urlimage']    = stripslashes($row[1]);

                    $stockMdl = new WposAdminStock($arr); 
                    $stockMdl->adjustStockLevel2($result);

                  }
                  
              		
   		            $result['error'] = "Your import was successful";

                  break;    		            
             }
                
            } else {
                $result['error'] = "No file selected";
            }
            
            break;

        // device message
        case "message/send":
            $socket = new WposSocketIO();
            if ($data->device === null) {
                if (($error = $socket->sendBroadcastMessage($data->message)) !== true) {
                    $result['error'] = $error;
                }
            } else {
                $devid = intval($data->device);
                $devices = new stdClass();
                $devices->{$devid} = $devid;
                if (($error = $socket->sendMessageToDevices($devices, $data->message)) !== true) {
                    $result['error'] = $error;
                }
            }
            break;
        // device reset
        case "device/reset":
            $socket = new WposSocketIO();
            if ($data->device === null) {
                if (($error = $socket->sendResetCommand()) !== true) {
                    $result['error'] = $error;
                }
            } else {
                $devid = intval($data->device);
                $devices = new stdClass();
                $devices->{$devid} = $devid;
                if (($error = $socket->sendResetCommand($devices)) !== true) {
                    $result['error'] = $error;
                }
            }
            break;

        default:
            $notinprev = true;
            break;
    }
    if ($notinprev == false) { // an action has been executed: return the data
        return $result;
    }

    // Check if user is allowed admin only API calls
    if (!$auth->isAdmin()) {
        $result['errorCode'] = "priv";
        $result['error'] = "You do not have permission to perform this action.";
        return $result;
    }
    // Check in permission protected API calls
    switch ($action) {
        case "devices/registrations":
            $setup = new WposPosSetup($data);
            $result = $setup->getDeviceRegistrations($result);
            break;
        case "devices/registrations/delete":
            $setup = new WposPosSetup($data);
            $result = $setup->deleteDeviceRegistration($result);
            break;
        case "templates/get":
            $result = WposTemplates::getTemplates($result);
            break;
        case "templates/edit":
            $tempMdl = new WposTemplates($data);
            $result = $tempMdl->editTemplate($result);
            break;
        case "templates/restore":
            WposTemplates::restoreDefaults();
            break;

        default:
        $result["error"] = "Action not defined: ".$action;
        break;
    }

    return $result;
}
/**
 * Encodes and returns the json result object
 * @param $result
 */
function returnResult($result){
    if (($resstr = json_encode($result)) === false) {
        echo(json_encode(["error" => "Failed to encode the reponse data into json"]));
    } else {
        echo($resstr);
    }
    die();
}
/**
 * Php error handler, sets & returns json result object
 * @param $errorno
 * @param $errstr
 * @param $errfile
 * @param $errline
 */
function errorHandler($errorno, $errstr, $errfile, $errline){
    global $result;

    $result['errorCode'] = "phperr";

    if ($result['error'] == "OK") $result['error'] = "";

    $result['error'] .= $errorno . ": " . $errstr . " " . $errfile . " on line " . $errline . "\n";

    die(json_encode($result));
}
/**
 * Php exception handler, sets & returns json result object
 * @param Exception $ex
 */
function exceptionHandler(Exception $ex){
    global $result;

    $result['errorCode'] = "phpexc";

    if ($result['error'] == "OK") $result['error'] = "";

    $result['error'] .= $ex->getMessage() . "\nFile: " . $ex->getFile() . " line " . $ex->getLine();

    die(json_encode($result));
}

?>
