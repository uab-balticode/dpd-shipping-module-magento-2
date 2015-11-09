/*
* Order list add js to show call dpd carrier
*/
function showCarrierWindow(chk_arr)
{
    document.getElementById("call_dpd_carrier_popup").style.display = "block";
}

/*
 * Show call carrier box in Sales Order List
 */
function hideCarrierWindow()
{
    document.getElementById("call_dpd_carrier_popup").style.display = "none";
}

/*
 * hide call carrier box from Sales Order List
 */
function callCarrier()
{
    hideCarrierWindow();
}

/*
 * Save Options of carrier Method
 */
function saveShippingMethodOptions(url, quoteId, option)
{
    sendPost(url, 'quoteId='+quoteId+'&option=' + JSON.stringify(option));
}

/*
 * Ajax Function without jQuery
 */
function sendPost(url, params){
    var xmlhttp;
    xmlhttp = new XMLHttpRequest();
    xmlhttp.open("POST", url, true);
    //Send the proper header information along with the request
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
    xmlhttp.setRequestHeader("Connection", "close");
    xmlhttp.onreadystatechange = function() {//Call a function when the state changes.
    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        console.log(xmlhttp.responseText);
        }
    }
    xmlhttp.send(params);
}