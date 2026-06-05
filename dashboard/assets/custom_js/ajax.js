 
  /*
  |--------------------------------------------------------------------------
  | 	Populate Service
  |--------------------------------------------------------------------------
  |
  */

  function ajax_populate_services(){
      var xhr;
      
      if (window.XMLHttpRequest) { // Mozilla, Safari, ...
        xhr = new XMLHttpRequest();
      
      } else if (window.ActiveXObject) { // IE 8 and older
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
      
      }

      var data = "ajax_action=populate_services";
      xhr.open("POST", "internal_request.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.send(data);

      xhr.onreadystatechange = populate_services_;

    function populate_services_() {
      if (xhr.readyState == 4) {
        if (xhr.status == 200) {
          var response        = xhr.responseText; //alert(xhr.responseText);
            // console.log(xhr.responseText);

            var row_number = document.getElementById('total_rows').value;
            // EMPTY THE DROP DOWN
            document.getElementById("service"+row_number).options.length = 0;

            const data = JSON.parse(xhr.responseText);
            // // console.log (data);

            var len = Object.keys(data).length;
            // // console.log (len);

            let option;
            var select = document.getElementById("service"+row_number);
            // select.options[select.options.length] = new Option('Please select', '0');
            select.options[select.options.length] = new Option('', '0');

            if (len > 0){
              
              for (var i = 0; i < len; i++) {
                
                  var id                = data[i].id;
                  var service_name      = data[i].service_name;
                  
                  select.options[select.options.length] = new Option(service_name, id);
              }
            }

        } else {
          console.log('There was a problem with the request.');

        }
      }
    }

  }
  
  

  /*
  |--------------------------------------------------------------------------
  | 	Populate Item Rate
  |--------------------------------------------------------------------------
  |
  */

  function ajax_populate_item_rate(item_id, row_no){
      var xhr;

      var item_id = item_id;
      var row_no = row_no;

      if (window.XMLHttpRequest) { // Mozilla, Safari, ...
        xhr = new XMLHttpRequest();
      
      } else if (window.ActiveXObject) { // IE 8 and older
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
      
      }

      var data = "ajax_action=populate_item_rate&item_id="+item_id+"&row_no="+row_no;
      xhr.open("POST", "internal_request.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.send(data);

      xhr.onreadystatechange = populate_item_rate_;

    function populate_item_rate_() {
      if (xhr.readyState == 4) {
        if (xhr.status == 200) {
          var response        = xhr.responseText; //alert(xhr.responseText);
            // console.log(xhr.responseText);
            
            const data = JSON.parse(xhr.responseText);
            
            var item_rate = data['item_rate'];
            var row_no = data['row_no'];
            
            if (item_rate == null || item_rate == 'undefined' || item_rate == ''){
              item_rate = 0;
            }
            
            // LOAD SERVICE DETAILS
            document.getElementById('qty' + row_no).value = 1;

            document.getElementById('tax' + row_no).value = '0';
            document.getElementById('tax' + row_no).text = '0%';
            document.getElementById('tax_amount' + row_no).value = '0';
            // document.getElementById('span_tax_amount' + row_no).style.display = 'none';
            document.getElementById('div_tax_amount' + row_no).style.display = 'none';

            // document.getElementById('tax' + row_no).value = '0';
            // var selectElement = document.getElementById('tax' + row_no);
            // selectElement.options[selectElement.selectedIndex].text = '0%';
            
            document.getElementById('sub_total' + row_no).value = item_rate;
            document.getElementById('rate' + row_no).value = item_rate;
            document.getElementById('total' + row_no).value = item_rate;

            calculateItemAmount(row_no);
            // updateQty(row_no);

        } else {
          console.log('There was a problem with the request.');

        }
      }
    }

  }
