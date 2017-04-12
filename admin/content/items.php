<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="margin-right: 20px; display: inline-block;">
        Items
    </h1>
    <button onclick="$('#adddialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
<!--    <button class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" onclick="exportItems();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button> -->
<!--    <button class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" onclick="show_popup('popup_upload');"><i class="icon-cloud-download align-top bigger-125"></i>Import CSV</button>    -->
    <button class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" onclick="exportAllItems();"><i class="icon-cloud-download align-top bigger-125"></i>Export All Items</button>    
    <button onclick="$('#popup_upload').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Import CSV</button>

    
   

</div><!-- /.page-header -->

<div class="row">
<div class="col-xs-12">
<!-- PAGE CONTENT BEGINS -->

<div class="row">
<div class="col-xs-12">

<div class="table-header">
    Manage your business products
</div>

<table id="itemstable" class="table table-striped table-bordered table-hover">
<thead>
<tr>
    <th class="center hidden-480 hidden-320 hidden-xs noexport">
        <label>
            <input type="checkbox" class="ace" />
            <span class="lbl"></span>
        </label>
    </th>
    <th>ID</th>
    <th style="width: 80px;">Image</th>       
    <th style="width: 100px;">Category</th>   
    <th>Code</th>     
<!--<th>Name</th> -->
    <th>Description</th>
<!--<th>SubLine</th>      -->  
<!--<th>Tax</th> -->
    <th style="width: 70px;">Unit</th>
    <th style="width: 90px;">Price</th>
    <th>%</th>    
    <th style="width: 90px;">Price2</th>    
    <th>%</th>    
    <th style="width: 90px;">Cost</th>
    <th style="width: 75px;">Stock</th>         
<!--<th>Status</th> -->   
<!--<th>Supplier</th> -->
    <th class="noexport" style="width: 90px;"></th>
</tr>
</thead>
<tbody>

</tbody>
</table>

</div>
</div>

</div><!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
<div id="editdialog" class="hide">
    <div class="tabbable" style="min-width: 360px; min-height: 310px;">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#itemdetails" data-toggle="tab">
                    Details
                </a>
            </li>
            <li class="">
                <a href="#itemoptions" data-toggle="tab">
                    Options
                </a>
            </li>
        </ul>
        <div class="tab-content" style="min-height: 320px;">
            <div class="tab-pane active in" id="itemdetails">
                <table>
                    <tr>
                        <td style="text-align: right;"><label>Code:&nbsp;</label></td>
                        <td><input id="itemcode" type="text"/>
                         <input id="itemid" type="hidden"/>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: right;"><label>Category:&nbsp;</label></td>
                        <td><select id="itemcategory" class="catselect">
                            </select></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label>Active:&nbsp;</label></td>
                        <td><select id="itemactive" class="activeselect">
                            </select></td>
                    </tr>

<!--                  
                    <tr>
                        <td style="text-align: right;"><label>Name:&nbsp;</label></td>
                        <td><input id="itemname" type="text"/>
                            <input id="itemid" type="hidden"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label>Alternate Name:&nbsp;</label></td>
                        <td><input id="itemaltname" type="text"/><br/>
                            <small>Alternate language name</small>
                        </td>
                    </tr>
-->                    
                    <tr>
                        <td style="text-align: right;"><label>Description:&nbsp;</label></td>
                        <td><input id="itemdesc" type="text"/></td>
                    </tr>
<!--                <tr>
                        <td style="text-align: right;"><label>Subline:&nbsp;</label></td>
                        <td><select id="itemsubline" class="sublineselect">
                            </select></td>
                    </tr>                    
-->                    
                    <tr>
                        <td style="text-align: right;"><label>Unit:&nbsp;</label></td>
                        <td><select id="itemunit" class="unitselect">
                            </select></td>
                    </tr>
                    
                    <tr>
                        <td style="text-align: right;"><label>Price:&nbsp;</label></td>
                        <td><input id="itemprice" type="text" value="0"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label>Price2:&nbsp;</label></td>
                        <td><input id="itemprice2" type="text" value="0"/></td>
                    </tr>

                    <tr>
                        <td style="text-align: right;"><label>Price3:&nbsp;</label></td>
                        <td><input id="itemprice3" type="text" value="0"/></td>
                    </tr>
<!--
                    <tr>
                        <td style="text-align: right;"><label>Price4:&nbsp;</label></td>
                        <td><input id="itemprice4" type="text" value="0"/></td>
                    </tr>
-->  

                    <tr>
                        <td style="text-align: right;"><label>Cost:&nbsp;</label></td>
                        <td><input id="itemcost" type="text" value="0"/></td>
                    </tr>
<!--
                    <tr>
                        <td style="text-align: right;"><label>Tax:&nbsp;</label></td>
                        <td><select id="itemtax" class="taxselect">
                            </select></td>
                    </tr>
-->                    
                    <tr>
                        <td style="text-align: right;"><label>Stock:&nbsp;</label></td>
                        <td><input id="itemqty" type="text" value="1"/></td>
                    </tr>
<!--                <tr>
                        <td style="text-align: right;"><label>Supplier:&nbsp;</label></td>
                        <td><select id="itemsupplier" class="supselect">
                            </select></td>
                    </tr>
-->
                    <tr>
                        <td style="text-align: right;"><label>Image:&nbsp;</label></td>
                        <td>
                            <div class="col-sm-5">
                            <input type="text" id="itemurlimage" /><br/>
                            <img id="itemurlimageprev" width="128" height="64" src="" />
                            <input type="file" id="itemurlimagefile" name="file" accept="image/png, image/jpeg" />
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="tab-pane" id="itemoptions" style="min-height: 280px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-4"><label>Item Type:</label></div>
                        <div class="col-sm-8">
                            <select id="itemtype">
                                <option value="general">General</option>
                                <option value="food">Food</option>
                                <option value="beverage">Beverage</option>
                            </select>
                            <br/><small>Used for kitchen terminal dispatch</small>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-12"><label>Simple Modifiers:</label></div>
                        <table class="table table-stripped table-responsive" style="margin-bottom: 0; padding-left: 10px; margin-right: 10px;">
                            <thead class="table-header smaller">
                                <tr>
                                    <th><small>Qty</small></th>
                                    <th><small>Min Qty</small></th>
                                    <th><small>Max Qty</small></th>
                                    <th><small>Name</small></th>
                                    <th><small>Price</small></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemmodtable">

                            </tbody>
                        </table>
                        <button style="float: right; margin-right: 8px;" class="btn btn-primary btn-xs" onclick="addItemModifier();">Add</button>
                        <div class="col-sm-12"><label>Select Modifiers:</label></div>
                        <table class="table table-stripped table-responsive" style="margin-bottom: 0; padding-left: 10px; margin-right: 10px;">
                            <tbody id="itemselmodtable">

                            </tbody>
                        </table>
                        <button style="float: right; margin-right: 8px;" class="btn btn-primary btn-xs" onclick="addSelectItemModifier();">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div id="popup_upload" class="hide">
        <div class="form_upload">
<!--            <span class="close" onclick="close_popup('popup_upload')">x</span>-->
            <h2>Upload CSV file</h2>
            <div class="col-sm-5">
            <input type="text" id="csvimporturl" /><br/> 
            <input type="file" id="csvimporturlfile" name="file" accept=".csv"/>
            </div>

        </div>

</div>


<div id="adddialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Code:&nbsp;</label></td>
            <td><input id="newitemcode" type="text"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Category:&nbsp;</label></td>
            <td><select id="newitemcategory" class="catselect">
                </select></td>
        </tr>
        
        <tr>
            <td style="text-align: right;"><label>Active:&nbsp;</label></td>
            <td><select id="newitemactive" class="activeselect">
                </select></td>
        </tr>
        
<!--        
        <tr>
           <td style="text-align: right;"><label>Name:&nbsp;</label></td>
           <td><input id="newitemname" type="text"/><br/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Alternate Name:&nbsp;</label></td>
            <td><input id="newitemaltname" type="text"/><br/>
                <small>Alternate language name</small>
            </td>
        </tr>
-->        
        <tr>
            <td style="text-align: right;"><label>Description:&nbsp;</label></td>
            <td><input id="newitemdesc" type="text"/></td>
        </tr>
<!--        
        <tr>
            <td style="text-align: right;"><label>Subline:&nbsp;</label></td>
                        <td><select id="newitemsubline" class="sublineselect">
                            </select></td>
        </tr>
-->        
        <tr>
            <td style="text-align: right;"><label>Unit:&nbsp;</label></td>
                        <td><select id="newitemunit" class="unitselect">
                            </select></td>
        </tr>


        <tr>
            <td style="text-align: right;"><label>Price:&nbsp;</label></td>
            <td><input id="newitemprice" type="text" value=""/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Price2:&nbsp;</label></td>
            <td><input id="newitemprice2" type="text" value=""/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Price3:&nbsp;</label></td>
            <td><input id="newitemprice3" type="text" value=""/></td>
        </tr>
<!--    <tr>
            <td style="text-align: right;"><label>Price4:&nbsp;</label></td>
            <td><input id="newitemprice4" type="text" value="0"/></td>
        </tr>
-->        
        <tr>
            <td style="text-align: right;"><label>Cost:&nbsp;</label></td>
            <td><input id="newitemcost" type="text" value=""/></td>
        </tr>
<!--
        <tr>
            <td style="text-align: right;"><label>Tax:&nbsp;</label></td>
            <td><select id="newitemtax" class="taxselect">
                </select></td>
        </tr>
-->        
        <tr>
            <td style="text-align: right;"><label>Stock:&nbsp;</label></td>
            <td><input id="newitemqty" type="text" value=""/></td>
        </tr>
<!--        
        <tr>
            <td style="text-align: right;"><label>Supplier:&nbsp;</label></td>
            <td><select id="newitemsupplier" class="supselect">
            </select></td>
        </tr>
-->       
        <tr>
            <td style="text-align: right;"><label>Image:&nbsp;</label></td>
            <td>
                <div class="col-sm-5">
                <input type="text" id="newitemurlimage" /><br/>
                <img id="newitemurlimageprev" width="128" height="64" src="" />
                <input type="file" id="newitemurlimagefile" name="file"  accept="image/png, image/jpeg"/>
                </div>
            </td>
        </tr>        
    </table>
</div>
 
<div id="stockhistdialog" class="hide">

    <div style="width: 100%; overflow-x: auto;height:700px;">
    <table class="table table-responsive table-stripped">
        <thead>
            <tr>
                <th>Item</th>
                <th>Location</th>
                <th>Type</th>
                <th>Amount</th>
                <th>DT</th>
            </tr>
        </thead>
        <tbody id="stockhisttable">

        </tbody>
    </table>
    </div>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var stock = null;
    var items = null;
    var datatable;
    var etime = new Date().getTime();
    var stime = (etime - 604800000); // a week ago
    
    $(function() {
        stock = WPOS.getJsonData("stock/get");
        var stockarray = [];
        var tempstock;
        for (var key in stock){
            tempstock = stock[key];
            stockarray.push(tempstock);
        }
        datatable = $('#stocktable').dataTable(
            { "bProcessing": true,
            "aaData": stockarray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"hidden-480 hidden-320 hidden-xs noexport" },
                { mData:function(data,type,val){return (data.name==null?"Unknown":data.name) } },
                //{ mData:"supplier" },
                { mData:function(data,type,val){return (data.locationid!=='0'?(WPOS.locations.hasOwnProperty(data.locationid)?WPOS.locations[data.locationid].name:'Unknown'):'Warehouse');} },
                { mData:"stocklevel" },
                { mData:function(data,type,val){return '<div class="action-buttons"><a class="green" onclick="openEditStockDialog('+data.id+');"><i class="icon-pencil bigger-130"></i></a><a class="blue" onclick="openTransferStockDialog('+data.id+')"><i class="icon-arrow-right bigger-130"></i></a><a class="red" onclick="getStockHistory('+data.storeditemid+', '+data.locationid+');"><i class="icon-time bigger-130"></i></a></div>'; }, "bSortable": false, sClass: "noexport" }
            ] } );
        // insert table wrapper
        $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");

        $('table th input:checkbox').on('click' , function(){
            var that = this;
            $(this).closest('table').find('tr > td:first-child input:checkbox')
                .each(function(){
                    this.checked = that.checked;
                    $(this).closest('tr').toggleClass('selected');
                });
        });

        // dialogs
        $( "#addstockdialog" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Stock",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                        "class" : "btn btn-success btn-xs",
                        click: function() {
                            saveItem(1);
                        }
                    }
                    ,
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class" : "btn btn-xs",
                        click: function() {
                            $( this ).dialog( "close" );
                        }
                    }
                ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "375px");
            }
        });
        $( "#editstockdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Stock",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveItem(2);
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "375px");
            }
        });
        $( "#transferstockdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Transfer Stock",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveItem(3);
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "375px");
            }
        });
        $( "#stockhistdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            maxWidth: '700px',
            modal: true,
            autoOpen: false,
            title: "Stock History",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "700px");
            }
        });
        // fill location selects
        var locselect = $(".locselect");
        locselect.html('');
        for (key in WPOS.locations){
            if (key == 0){
                locselect.append('<option class="locid-0" value="0">Warehouse</option>');
            } else {
                locselect.append('<option class="locid-'+WPOS.locations[key].id+'" value="'+WPOS.locations[key].id+'">'+WPOS.locations[key].name+'</option>');
            }
        }

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function getStockHistory(id, locationid){
        WPOS.util.showLoader();
        var stockhist = WPOS.sendJsonData("stock/history", JSON.stringify({storeditemid: id, locationid: locationid}));
        // populate stock dialog with list
        $("#stockhisttable").html("");
        var hist;
        for (var i in stockhist){
            hist = stockhist[i];
            $("#stockhisttable").append('<tr><td>'+hist.name+'</td><td>'+hist.location+'</td><td>'+hist.type+(hist.auxid!=-1?(hist.auxdir==1?" from ":" to ")+(hist.auxid==0?"Warehouse":WPOS.locations[hist.auxid].name):"")+'</td><td>'+hist.amount+'</td><td>'+hist.dt+'</td></tr>');
        }
        WPOS.util.hideLoader();
        $("#stockhistdialog").dialog('open');
    }
    function openEditStockDialog(id){
        var item = stock[id];
        $("#setstockitemid").val(item.storeditemid);
        $("#setstocklocid").val(item.locationid);
        $("#setstockqty").val(item.stocklevel);
        $("#editstockdialog").dialog("open");
    }
    function openAddStockDialog(){
        populateItems();
        $("#addstockdialog").dialog("open");
    }
    function openTransferStockDialog(id){
        var item = stock[id];
        $("#tstockitemid").val(item.storeditemid);
        $("#tstocklocid").val(item.locationid);
        $("#transferstockdialog").dialog("open");
    }
    function populateItems(){
        if (items == null){
            WPOS.util.showLoader();
            items = WPOS.sendJsonData("items/get");
            var itemselect = $(".itemselect");
            itemselect.html('');
            for (var i in items){
                itemselect.append('<option class="itemid-'+items[i].id+'" value="'+items[i].id+'">'+items[i].name+'</option>');
            }
            WPOS.util.hideLoader();
        }
    }
    function saveItem(type){
        // show loader
        WPOS.util.showLoader();
        var item = {};
        switch (type){
        case 1:
            // adding new stock
            item.storeditemid = $("#addstockitemid option:selected").val();
            item.locationid = $("#addstocklocid option:selected").val();
            item.amount = $("#addstockqty").val();
            if (WPOS.sendJsonData("stock/add", JSON.stringify(item))!==false){
                reloadTable();
                $("#addstockdialog").dialog("close");
            }
            break;
        case 2:
            // set stock level
            item.storeditemid = $("#setstockitemid").val();
            item.locationid = $("#setstocklocid").val();
            item.amount = $("#setstockqty").val();
            if (WPOS.sendJsonData("stock/set", JSON.stringify(item))!==false){
                reloadTable();
                $("#editstockdialog").dialog("close");
            }
            break;
        case 3:
            // transfer stock
            item.storeditemid = $("#tstockitemid").val();
            item.locationid = $("#tstocklocid").val();
            item.newlocationid = $("#tstocknewlocid").val();
            item.amount = $("#tstockqty").val();
            if (WPOS.sendJsonData("stock/transfer", JSON.stringify(item))!==false){
               reloadTable();
               $("#transferstockdialog").dialog("close");
            }
            break;
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function reloadTable(){
        stock = WPOS.getJsonData("stock/get");
        var stockarray = [];
        var tempstock;
        for (var key in stock){
            tempstock = stock[key];
            stockarray.push(tempstock);
        }
        datatable.fnClearTable();
        datatable.fnAddData(stockarray);
    }
    function exportStock(){
        var data  = WPOS.table2CSV($("#stocktable"));
        var filename = "stock-"+WPOS.util.getDateFromTimestamp(new Date());
        filename = filename.replace(" ", "");
        WPOS.initSave(filename, data);
    }
</script>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<link rel="stylesheet" href="dist/jquery.ezdz.min.css">
<script src="dist/jquery.ezdz.min.js"></script>
<script type="text/javascript">
    var stock = null;
    var suppliers = null;
    var categories = null;
    var datatable;
    
     $('#itemurlimagefile').on('change',uploadUrlImage);
     $('#itemurlimage').on('change',function(e){
     $("#itemurlimageprev").prop("src", $(e.target).val());
     });

     $('#newitemurlimagefile').on('change',uploadNewUrlImage);
     $('#newitemurlimage').on('change',function(e){
     $("#newitemurlimageprev").prop("src", $(e.target).val());
     });

     $('#csvimporturlfile').on('change',uploadCsvImport);

     $('#csvimporturl').on('change',function(e){   
     });


    function uploadUrlImage(event){
        WPOS.uploadFile(event, function(data){
            $("#itemurlimage").val(data.path);
            $("#itemurlimageprev").prop("src", data.path);
            //saveSettings();
        }); // Start file upload, passing a callback to fire if it completes successfully
    }


    function uploadNewUrlImage(event){
        WPOS.uploadFile(event, function(data){
            $("#newitemurlimage").val(data.path);
            $("#newitemurlimageprev").prop("src", data.path);
            //saveSettings();
        }); // Start file upload, passing a callback to fire if it completes successfully
    }


    function uploadCsvImport(event){
        WPOS.uploadFile(event, function(data){
        $("#csvimporturl").val(data.path);
        }); // Start file upload, passing a callback to fire if it completes successfully
    }
    
    $(function() {
//      var data = WPOS.sendJsonData("multi", JSON.stringify({"items/get":"", "suppliers/get":"", "categories/get":"" , "stock/get":""  }));
        var data = WPOS.sendJsonData("multi", JSON.stringify({"items/get":"", "categories/get":"" , "stock/get":""  }));
        stock = data['items/get'];
        stock2 = data['stock/get'];
//      suppliers = data['suppliers/get'];
        categories = data['categories/get'];
        var itemarray = [];
        var tempitem;
//      var taxrules = WPOS.getTaxTable().rules;
        for (var key in stock){
            tempitem = stock[key];
            //console.log(key);
//            var stock2 = WPOS.sendJsonData("stock/level", JSON.stringify({storeditemid: key, locationid: 0 }));
//            tempitem.qty=stock2[0].stocklevel;
            //hjkim
            for (var key2 in stock2){
              if (key==stock2[key2].storeditemid)
              {  
                tempitem.qty=stock2[key2].stocklevel;
                break;
              }            
            }  
/*
            if (taxrules.hasOwnProperty(tempitem.taxid)){
                tempitem.taxname = taxrules[tempitem.taxid].name;
            } else {
                tempitem.taxname = "Not Defined";
            }
*/            
            itemarray.push(tempitem);
        }
        datatable = $('#itemstable').dataTable(
            { "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"hidden-480 hidden-320 hidden-xs noexport" },
                { "sType": "numeric", "mData":"id" },
                { "sType": "html", mData:function(data,type,val){ return "<img width=30 height=20 src="+data.urlimage+">" }},                
                { "sType": "string", "mData":function(data,type,val){return (categories.hasOwnProperty(data.categoryid)?categories[data.categoryid].name:'Misc'); } },                                
                { "sType": "string", "mData":"code" },                
//              { "sType": "string", "mData":"name" },
                { "sType": "string", "mData":"description" },
//              { "sType": "string", "mData":"taxname" },
//              { "sType": "string", "mData":"subline" },
                { "sType": "string", "mData":"unit" },
                { "sType": "currency", "mData":function(data,type,val){return (data['price']==""?"":WPOS.util.currencyFormat(data["price"]));} },
                { "sType": "currency", "mData":function(data,type,val){return ( WPOS.util.decimal2Places(((data["price"] - data["cost"]) / data["price"] )*100));} },
                { "sType": "currency", "mData":function(data,type,val){return (data['price2']==""?"":WPOS.util.currencyFormat(data["price2"]));} }, 
//                { "sType": "currency", "mData":function(data,type,val){return ( ((data["price2"] - data["cost"]) / data["price2"] )*100);} },
                { "sType": "currency", "mData":function(data,type,val){return ( WPOS.util.decimal2Places(((data["price2"] - data["cost"]) / data["price2"] )*100));} },
                { "sType": "currency", "mData":function(data,type,val){return (data['cost']==""?"":WPOS.util.currencyFormat(data["cost"]));} },                              
                { "sType": "numeric", "mData":"qty" },                
//                { "sType": "string", "mData":"active" },
//              { "sType": "string", "mData":function(data,type,val){return (suppliers.hasOwnProperty(data.supplierid)?suppliers[data.supplierid].name:'Misc'); } },
                { "sType": "html", mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="openEditDialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeItem($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a> <a class="red" onclick="getStockHistory($(this).closest(\'tr\').find(\'td\').eq(1).text(), 0);"><i class="icon-time bigger-130"></i></a> </div>', "bSortable": false, sClass: "noexport" }
            ] } );
        // insert table wrapper
        $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");


        $('table th input:checkbox').on('click' , function(){
            var that = this;
            $(this).closest('table').find('tr > td:first-child input:checkbox')
                .each(function(){
                    this.checked = that.checked;
                    $(this).closest('tr').toggleClass('selected');
                });

        });


        $('[data-rel="tooltip"]').tooltip({placement: tooltip_placement});
        function tooltip_placement(context, source) {
            var $source = $(source);
            var $parent = $source.closest('table');
            var off1 = $parent.offset();
            var w1 = $parent.width();

            var off2 = $source.offset();
            var w2 = $source.width();

            if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) return 'right';
            return 'left';
        }
        // dialogs
        $( "#adddialog" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Item",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                        "class" : "btn btn-success btn-xs",
                        click: function() {
                            saveItem(true);
                            $('#newitemcode').val('');
                            $('#newitemcategory').val('0');
                            $('#newitemactive').val('Y');
                            $('#newitemname').val('');
                            $('#newitemaltname').val('');
                            $('#newitemdesc').val('');
                            $('#newitemsubline').val('');
                            $('#newitemunit').val('D');
                            $('#newitemprice').val('');
                            $('#newitemprice2').val('');
                            $('#newitemprice3').val('');
                            $('#newitemcost').val('');                                                        
                            $('#newitemqty').val('');
                            $('#newitemsupplier').val('');
                            $('#newitemurlimage').val('');
                            $('#newitemurlimageprev').val('');
                            $('#newitemurlimagefile').val('');                          
                        }
                    }
                    ,
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class" : "btn btn-xs",
                        click: function() {
                            $( this ).dialog( "close" );
                            $('#newitemcode').val('');
                            $('#newitemcategory').val('0');
                            $('#newitemactive').val('Y');
                            $('#newitemname').val('');
                            $('#newitemaltname').val('');
                            $('#newitemdesc').val('');
                            $('#newitemsubline').val('');
                            $('#newitemunit').val('D');
                            $('#newitemprice').val('');
                            $('#newitemprice2').val('');
                            $('#newitemprice3').val('');
                            $('#newitemcost').val('');                                                        
                            $('#newitemqty').val('');
                            $('#newitemsupplier').val('');
                            $('#newitemurlimage').val('');
                            $('#newitemurlimageprev').val('');
                            $('#newitemurlimagefile').val('');            
                          }
                    }
                ],
                create: function( event, ui ) {
                    // Set maxWidth
                    $(this).css("maxWidth", "460px");
                }
        });
        // dialogs
        $( "#popup_upload" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Item",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; OK",
                        "class" : "btn btn-xs",
                        click: function() {
                        $("#csvimporturl").val('');
                        reloadData();

                        $('#csvimporturlfile').val('');
                        
//                   //     $("#csvimporturlfile").val("");
                  //        $("#csvimporturlfile").val('');                        
                  //document.getElementById("csvimporturlfile").value = "";

                            $( this ).dialog( "close" );
                        }
                    }
                ],
                create: function( event, ui ) {
                    // Set maxWidth
                    $(".ui-dialog-titlebar-close").hide();                    
                    $(this).css("maxWidth", "460px");
                }
        });
        
        $( "#editdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Item",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveItem(false);
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "460px");
            }
        });
        // populate tax records in select boxes
/*      var taxsel = $(".taxselect");
        taxsel.html('');
        for (key in WPOS.getTaxTable().rules){
            taxsel.append('<option class="taxid-'+WPOS.getTaxTable().rules[key].id+'" value="'+WPOS.getTaxTable().rules[key].id+'">'+WPOS.getTaxTable().rules[key].name+'</option>');
        }
*/        
        // populate category & supplier records in select boxes
/*
        var supsel = $(".supselect");
        supsel.html('');
        supsel.append('<option class="supid-0" value="0">None</option>');
        for (key in suppliers){
            supsel.append('<option class="supid-'+suppliers[key].id+'" value="'+suppliers[key].id+'">'+suppliers[key].name+'</option>');
        }
*/
        var unisel = $(".unitselect");
        unisel.html('');

//        unisel.append('<option class="unitsid-1" value="DOC">DOC</option>');
//        unisel.append('<option class="unitsid-2" value="YD">YD</option>');                
//        unisel.append('<option class="unitsid-3" value="MTS">MTS</option>');        
//        unisel.append('<option class="unitsid-4" value="PZA">PZA</option>');        
        unisel.append('<option class="unitsid-1" value="D">D</option>');
        unisel.append('<option class="unitsid-2" value="Y">Y</option>');                
        unisel.append('<option class="unitsid-3" value="M">M</option>');        
        unisel.append('<option class="unitsid-4" value="P">P</option>');        

/*
        var sublinesel = $(".sublineselect");
        sublinesel.html('');
//        unisel.append('<option class="unitsid-0" value="NONE">NONE</option>');
        sublinesel.append('<option class="sublinesid-1" value="COLLAR">COLLAR</option>');
        sublinesel.append('<option class="sublinesid-2" value="MATERIAL">MATERIAL</option>'); 
        sublinesel.append('<option class="sublinesid-3" value="CINTURON">CINTURON</option>'); 
        sublinesel.append('<option class="sublinesid-4" value="GIPIUR/ENCAJE">GIPIUR/ENCAJE</option>');                
        sublinesel.append('<option class="sublinesid-5" value="MESH">MESH</option>');                      
        sublinesel.append('<option class="sublinesid-7" value="PLANILLA">PLANILLA</option>');        
        sublinesel.append('<option class="sublinesid-8" value="CADENA">CADENA</option>');        
        sublinesel.append('<option class="sublinesid-9" value="COLLAR ECONOMICO">COLLAR ECONOMICO</option>');

*/

        var catsel = $(".catselect");
        catsel.html('');
        catsel.append('<option class="catid-0" value="0">None</option>');
        for (key in categories){
            catsel.append('<option class="catid-'+categories[key].id+'" value="'+categories[key].id+'">'+categories[key].name+'</option>');
        }

        var actsel = $(".activeselect");
        actsel.html('');
        actsel.append('<option class="actid-0" value="Y">Yes</option>');
        actsel.append('<option class="actid-1" value="N">No</option>');        

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function openEditDialog(id){
        var item = stock[id];
        $("#itemid").val(item.id);
        $("#itemcategory").val(item.categoryid);        
        $("#itemactive").val(item.active);                
//      $("#itemname").val(item.name);
//      $("#itemaltname").val(item.alt_name);
        $("#itemdesc").val(item.description);
//      $("#itemsubline").val(item.subline);             
        $("#itemunit").val(item.unit);        
        $("#itemqty").val(item.qty);
//      $("#itemtax").val(item.taxid);
        $("#itemcode").val(item.code);
        $("#itemprice").val(item.price);
        $("#itemprice2").val(item.price2);
        $("#itemprice3").val(item.price3);        
//        $("#itemprice4").val(item.price4);                        
        $("#itemcost").val(item.cost);        
//      $("#itemsupplier").val(item.supplierid);
        $("#itemtype").val(item.type);        
        $("#itemurlimage").val(item.urlimage);                                
        $("#itemurlimagefile").val('');
        $("#itemurlimageprev").prop("src", item.urlimage);
        var modtable = $("#itemmodtable");
        var modselecttable = $("#itemselmodtable");
        modtable.html('');
        modselecttable.html('');
        if (item.hasOwnProperty('modifiers')){
            var mod;
            for (var i=0; i<item.modifiers.length; i++){
                mod = item.modifiers[i];
                if (mod.type=='select'){
                    var modopttable = '';
                    for (var o=0; o<mod.options.length; o++){
                        modopttable += '<tr><td><input onclick="handleSelectCheckbox(this);" type="checkbox" class="modoptdefault" '+(mod.options[o].default==true?'checked="checked"':'')+'/></td><td><input style="width: 130px" type="text" class="modoptname" value="'+mod.options[o].name+'"/></td><td><input type="text" style="width: 60px" class="modoptprice" value="'+mod.options[o].price+'"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parent().parent().remove();">X</button></td></tr>';
                    }
                    modselecttable.append('<tr class="selmoditem"><td colspan="4" style="padding-right: 0; padding-left: 0;"><div style="padding-left: 8px; padding-right: 8px;"><label>Name:</label>&nbsp;<input style="width: 130px" type="text" class="modname" value="'+mod.name+'"/><button class="btn btn-danger btn-xs pull-right" style="margin-left: 5px;" onclick="$(this).parents().eq(2).remove();">X</button></div><table class="table" style="margin-top: 5px;">'+modtableheader+'<tbody class="modoptions">'+modopttable+'</tbody></table></td></tr>');
                } else {
                    modtable.append('<tr><td><input type="text" style="width: 40px" class="modqty" value="'+mod.qty+'"/></td><td><input type="text" style="width: 40px" class="modminqty" value="'+mod.minqty+'"/></td><td><input type="text" style="width: 40px" class="modmaxqty" value="'+mod.maxqty+'"/></td><td><input style="width: 130px" type="text" class="modname" value="'+mod.name+'"/></td><td><input type="text" style="width: 60px" class="modprice" value="'+mod.price+'"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parent().parent().remove();">X</button></td></tr>');
                }
            }
        }
        $("#editdialog").dialog("open");
    }
    function addItemModifier(){
        $("#itemmodtable").append('<tr><td><input onchange="var row = $(this).parent().parent(); if ($(this).val()>row.find(\'.modminqty\').val()) row.find(\'.modminqty\').val($(this).val())" type="text" style="width: 40px" class="modqty" value="0"/></td><td><input type="text" style="width: 40px" class="modminqty" value="0"/></td><td><input type="text" style="width: 40px" class="modmaxqty" value="0"/></td><td><input style="width: 130px" type="text" class="modname" value=""/></td><td><input type="text" style="width: 60px" class="modprice" value="0.00"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parent().parent().remove();">X</button></td></tr>');
    }
    function addSelectItemModifier(){
        var modseltable = $("#itemselmodtable");
        var modelem = $('<tr class="selmoditem"><td colspan="4" style="padding-right: 0; padding-left: 0;"><div style="padding-left: 8px; padding-right: 8px;"><label>Name:</label>&nbsp;<input style="width: 130px" type="text" class="modname" value=""/><button class="btn btn-danger btn-xs pull-right" style="margin-left: 5px;" onclick="$(this).parents().eq(2).remove();">X</button></div><table class="table" style="margin-top: 5px;">'+modtableheader+'<tbody class="modoptions">'+modselectoption+'</tbody></table></td></tr>');
        modelem.find('.modoptdefault').prop('checked', true);
        modseltable.append(modelem);
    }
    var modtableheader = '<thead class="table-header smaller"><tr><th><small>Default</small></th><th><small>Name</small></th><th><small>Price</small></th><th><button class="btn btn-primary btn-xs pull-right" onclick="addSelectModItem($(this).parents().eq(3).find(\'.modoptions\'));">Add Option</button></th></tr></thead>';
    var modselectoption = '<tr><td><input onclick="handleSelectCheckbox($(this));" type="checkbox" class="modoptdefault"/></td><td><input style="width: 130px" type="text" class="modoptname" value=""/></td><td><input type="text" style="width: 60px" class="modoptprice" value="0.00"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parents().eq(1).remove();">X</button></td></tr>';
    function addSelectModItem(elem){
        $(elem).append(modselectoption);
        if (elem.find('tr').length==1) $(elem).find('.modoptdefault').prop('checked', true);
    }
    function handleSelectCheckbox(elem){
        var table = $(elem).parent().parent().parent();
        table.find('.modoptdefault').prop('checked', false);
        $(elem).prop('checked', true);
    }
    function saveItem(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var item = {};
        var result;
        if (isnewitem){
            // adding a new item
            item.urlimage = $("#newitemurlimage").val();            
            item.categoryid = $("#newitemcategory").val();            
            item.active = $("#newitemactive").val();                        
            item.code = $("#newitemcode").val();
            item.qty = $("#newitemqty").val();
//          item.name = $("#newitemname").val();
            item.name = $("#newitemcode").val();
//          item.alt_name = $("#newitemaltname").val();
            item.description = $("#newitemdesc").val();
//          item.subline = $("#newitemsubline").val();            
//          item.taxid = $("#newitemtax").val();
            item.unit = $("#newitemunit").val();
            item.price = $("#newitemprice").val();
            item.price2 = $("#newitemprice2").val();    
            item.price3 = $("#newitemprice3").val();            
//          item.price4 = $("#newitemprice4").val();                                                        
            item.cost = $("#newitemcost").val();                        
//          item.supplierid = $("#newitemsupplier").val();
            item.type = "general";
            item.modifiers = [];
            result = WPOS.sendJsonData("items/add", JSON.stringify(item));
            if (result!==false){
                stock[result.id] = result;
                reloadTable();
                $("#adddialog").dialog("close");
            }
        } else {
            // updating an item
            item.id = $("#itemid").val();
            item.urlimage = $("#itemurlimage").val();             
            item.categoryid = $("#itemcategory").val();            
            item.active = $("#itemactive").val();                        
            item.code = $("#itemcode").val();
            item.qty = $("#itemqty").val();
//          item.name = $("#itemname").val();
            item.name = $("#itemcode").val();
//            item.alt_name = $("#itemaltname").val();
            item.description = $("#itemdesc").val();
//          item.taxid = $("#itemtax").val();
//          item.subline = $("#itemsubline").val();
            item.unit = $("#itemunit").val();
            item.price = $("#itemprice").val();
            item.price2 = $("#itemprice2").val();
            item.price3 = $("#itemprice3").val();
//            item.price4 = $("#itemprice4").val();            
            item.cost = $("#itemcost").val();
//          item.supplierid = $("#itemsupplier").val();
            item.type = $("#itemtype").val();                                                       
            item.modifiers = [];
            $("#itemselmodtable .selmoditem").each(function(){
                var mod = {type:"select", options:[]};
                mod.name = $(this).find(".modname").val();
                $(this).find('.modoptions tr').each(function(){
                    var modoption = {};
                    modoption.default = $(this).find(".modoptdefault").is(':checked');
                    modoption.name = $(this).find(".modoptname").val();
                    modoption.price = $(this).find(".modoptprice").val();
                    mod.options.push(modoption);
                });
                item.modifiers.push(mod);
            });
            $("#itemmodtable tr").each(function(){
               var mod = {type:"simple"};
               mod.qty = $(this).find(".modqty").val();
               mod.minqty = $(this).find(".modminqty").val();
               mod.maxqty = $(this).find(".modmaxqty").val();
               mod.name = $(this).find(".modname").val();
               mod.price = $(this).find(".modprice").val();
               item.modifiers.push(mod);
            });
            result = WPOS.sendJsonData("items/edit", JSON.stringify(item));
            if (result!==false){
                stock[result.id] = result;
                reloadTable();
                $("#editdialog").dialog("close");
            }
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function removeItem(id){

        var answer = confirm("Are you sure you want to delete this item?");
        if (answer){
            // show loader
            WPOS.util.hideLoader();
            if (WPOS.sendJsonData("items/delete", '{"id":'+id+'}')){
                delete stock[id];
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function reloadData(){
        stock = WPOS.getJsonData("items/get");
        reloadTable();
    }
    function reloadTable(){
        var itemarray = [];
        var tempitem;
        for (var key in stock){
            tempitem = stock[key];
//          tempitem.taxname = WPOS.getTaxTable().rules[tempitem.taxid].name;
            itemarray.push(tempitem);
        }
        datatable.fnClearTable();
        datatable.fnAddData(itemarray);
    }
    function exportItems(){
        var data  = WPOS.table2CSV($("#itemstable"));
        var filename = "items-"+WPOS.util.getDateFromTimestamp(new Date());
        filename = filename.replace(" ", "");
        WPOS.initSave(filename, data);
    }

 


    function exportAllItems() {
        var items = WPOS.transactions.getItemsAll();
        var csv = "ID, Image, Category, Code, Description, Unit, Price, Price2, Price3, Cost, Stock, Status\n"; // Set header
        var item;
        for (var i in items){
            item = items[i];
            // join items
            s = JSON.stringify(item.urlimage)
            
            csv+=item.id+","+JSON.stringify(item.urlimage)+","+item.categoryid+","+JSON.stringify(item.code)+","+item.description+","+item.unit+","+item.price+","+item.price2+","+item.price3+","+item.cost+","+item.qty+","+item.active+"\n";
        }

        WPOS.initSave("items-all-"+WPOS.util.getDateFromTimestamp(stime)+"-"+WPOS.util.getDateFromTimestamp(etime), csv);
  
    }

    // updating records
    function getStockHistory(id, locationid){
      
        var locationid = 0;
        WPOS.util.showLoader();
        var stockhist = WPOS.sendJsonData("stock/history", JSON.stringify({storeditemid: id, locationid: locationid}));
        var stock2 = WPOS.sendJsonData("stock/level", JSON.stringify({storeditemid: id, locationid: locationid}));
        var i = 0 ; 
        
        // populate stock dialog with list
        $("#stockhisttable").html("");
        var hist;
        for (i in stockhist){
//            if(i == stock2[i].storeditemid) {
//               var kkk=stock2[i].stocklevel;  
//            }

            hist = stockhist[i];
            $("#stockhisttable").append('<tr><td>'+hist.name+'</td><td>'+hist.location+'</td><td>'+hist.type+(hist.auxid!=-1?(hist.auxdir==1?" from ":" to ")+(hist.auxid==0?"Warehouse":WPOS.locations[hist.auxid].name):"")+'</td><td>'+hist.amount+'</td><td>'+hist.dt+'</td></tr>');
        }
        if (i > 0){
            $("#stockhisttable").append('<tr><td></td><td></td><td>Total</td><td>'+stock2[0].stocklevel+'</td><td></td></tr>');        
        }    
        WPOS.util.hideLoader();
        $("#stockhistdialog").dialog('open');
    }


</script>
<style type="text/css">
    #itemstable_processing {
        display: none;
    }
</style><script>
$('[type="file"]').ezdz({
  text: 'drop a file'
});
</script>