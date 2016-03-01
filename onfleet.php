 <?php
	 
    //Bigcommerce API credentials
    $username = 'zipmate'; 
    $api_bigcommerce = 'f2e6778081753da6f86bffcbea05fbe0d48e1d13'; 
    $api_onfleet = '93863c18ff266929e791955c43ad8afb';
    
    // Onfleet API url  
    $url = "https://onfleet.com/api/v2/tasks";

    // Custom url that filters orders per status = "Awaiting Pickup" | We can sort by date created with '&sort=date_created:{date}'
    $customer_url = "https://store-dvkzl7w.mybigcommerce.com/api/v2/orders.json?status_id=8&limit=250";
    // intiate curl request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $customer_url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $api_bigcommerce);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    
    // execute curl requests
    $curlData = curl_exec($curl);
      
    // Parse requests in Json Format
    $product_rec  = json_decode($curlData); 
    
    //Filter json array with parameters | Check if the order's address is in Australia and if the postcode is in Sydney
    foreach ($product_rec as $entry) {
	    
	    // Do a counting of the orders with the status "Awaiting Pickup 
	    $orders_count = count($product_rec);
        $orders_status = $entry->status;
    
        echo "There are $orders_count orders with the status $orders_status";
        echo '<pre>';  
       
        // GET THE VALUE OF ALL THE PARAMETERS WE NEED TO PASS IN A POST REQUEST
        $recipient_phone = $entry->billing_address->phone;
        $recipient_street1 = $entry->billing_address->street_1;
        $recipient_street2 = $entry->billing_address->street_2;
        //Address has been divided into different variables, need to reconstruct it into a string for Onfleet 
        $destination_notes = $entry->staff_notes;
        $recipient_notes = 'Cloth & Co customer';
        // $complete_before = '1456769460000'; // Timestamp in milliseconds
        $order_id = $entry->id;
        $store_parse_query = parse_url($customer_url);
        $store_id = $store_parse_query[host];
        
        // Custom url that finds the items in an order
        $items_url = "https://store-dvkzl7w.mybigcommerce.com/api/v2/orders/$order_id/products.json";
        // intiate curl request
        $curl_item = curl_init();
        curl_setopt($curl_item, CURLOPT_URL, $items_url);
        curl_setopt($curl_item, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_item, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_item, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_item, CURLOPT_USERPWD, $username . ":" . $api_bigcommerce);
        curl_setopt($curl_item, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl_item, CURLOPT_ENCODING, "");
    
        // execute curl requests
        $curl_itemData = curl_exec($curl_item);
        
        // Parse requests in Json Format
        $product_rec_item  = json_decode($curl_itemData);
        
        //get name of items
        $items_names = $product_rec_item[0]->name;
             
        $order_notes = "Order #$order_id from $store_id, $items_names";
        
        // Get shipping address
        $items_shipping_address = "https://store-dvkzl7w.mybigcommerce.com/api/v2/orders/$order_id/shipping_addresses.json";
        
        // initiate curl request
        $curl_shipping_address = curl_init();
        curl_setopt($curl_shipping_address, CURLOPT_URL, $items_shipping_address);
        curl_setopt($curl_shipping_address, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_shipping_address, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_shipping_address, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_shipping_address, CURLOPT_USERPWD, $username . ":" . $api_bigcommerce);
        curl_setopt($curl_shipping_address, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl_shipping_address, CURLOPT_ENCODING, "");
    
        // execute curl requests
        $curl_shipping_addressData = curl_exec($curl_shipping_address);
      
        // Parse requests in Json Format
        $product_rec_shipping_address  = json_decode($curl_shipping_addressData);
        
        // List all parameters that we need
        $first_name = $product_rec_shipping_address[0]->first_name;
        $last_name = $product_rec_shipping_address[0]->last_name;
        $street1 = $product_rec_shipping_address[0]->street_1;
        $street2 = $product_rec_shipping_address[0]->street_2;
        $city = $product_rec_shipping_address[0]->city;
        $zip = $product_rec_shipping_address[0]->zip;
        $state = $product_rec_shipping_address[0]->state;
        $country = $product_rec_shipping_address[0]->country;
        $recipient_name = "$first_name $last_name";
        $address = "$street1 $street2, $city, $zip, $state, $country";
        
        //Testing if it's working by displaying different infos
        echo "$recipient_first_name $recipient_last_name lives in Australia, address is $address, phone number is $recipient_phone";
        
        // Only posts to Onfleet if the country of the Shipping address is Australia and the zip code is within Sydney area
        if ($country == 'Australia' && ($zip >= '2000') && ($zip <= '2778')){
	                
        // Post a request to Onfleet with all parameters
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $api_onfleet);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_ENCODING, "");  
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"destination":{"address":{"unparsed":"'.$address.'"},"notes":"'.$destination_notes.'"},"recipients":[{"name":"'.$recipient_name.'","phone":"+61'.$recipient_phone.'","notes":"'.$recipient_notes.'"}],"notes":"'.$order_notes.'","autoAssign":{"mode":"distance"}}');

        $result = curl_exec($ch);
        
        echo '<pre>'; 
        echo "Order for $recipient_name created:";
        print_r($result);
        echo '<pre>'; 
        
        // Here we could do another curl request to Onfleet API for the tracking link and ref #, and send it to Cloth&Co by email using sendgrid or via text using twilio
        
        // Close curl connexion
        curl_close($ch);
        
        } else {
	    
	    // The customers that don't have an billing address in Australia
	    $recipient_first_name = $entry->billing_address->first_name;
	    $recipient_last_name = $entry->billing_address->last_name;
	    
	    // echo "$recipient_first_name $recipient_last_name doesn't live in Australia";
	    echo '<pre>';
	    
        }
    }
    
    // Close curl connexion
    curl_close($curl);
    
?>
