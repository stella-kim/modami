<?php
$_SERVER['APP_ROOT'] = "/";
require '../library/wpos/models/TestData.php';
require '../library/wpos/models/db/DbConfig.php';
require '../library/wpos/models/db/AuthModel.php';
require '../library/wpos/models/db/SuppliersModel.php';
require '../library/wpos/models/db/StoredItemsModel.php';
require '../library/wpos/models/db/CategoriesModel.php';
require '../library/wpos/models/db/LocationsModel.php';
require '../library/wpos/models/db/DevicesModel.php';
require '../library/wpos/models/db/CustomerModel.php';
require '../library/wpos/models/WposPosData.php';
require '../library/wpos/models/WposAdminUtilities.php';
require '../library/wpos/models/db/TaxItemsModel.php';
require '../library/wpos/models/db/TaxRulesModel.php';
require '../library/wpos/models/WposInvoices.php';
require '../library/wpos/models/db/TransactionsModel.php';
require '../library/wpos/models/db/InvoicesModel.php';
require '../library/wpos/models/JsonValidate.php';
require '../library/wpos/models/db/SaleItemsModel.php';
require '../library/wpos/models/db/SalePaymentsModel.php';
require '../library/wpos/models/WposPosSale.php';
require '../library/wpos/models/db/SalesModel.php';
require '../library/wpos/models/WposAdminStock.php';
require '../library/wpos/models/db/StockHistoryModel.php';
require '../library/wpos/models/db/StockModel.php';
require '../library/wpos/models/WposTransactions.php';
require '../library/wpos/models/db/TransHistModel.php';
require '../library/wpos/models/Logger.php';
require '../library/wpos/models/db/SaleVoidsModel.php';



$testdata = new TestData;
$testdata->generateTestData(1);
