<?php

var_dump(get_zip_code('Краснодарский край, г. Сочи, ул. Горького, д.29, кв.15'));
var_dump(get_zip_code('Нижний Новгород, ул.Ковалихинская, д.30'));

function get_zip_code($geocode_text) {
    
    $params = array( 
        'geocode' => $geocode_text,
        'format'  => 'json',
        'results' => 10,
    ); 
    
    $result = array( 
        'status' => "0",
        'geocoder_found_members'  => "",
        'zip_code' => "",
        'postmail_adress' => "",
    );
    
    $response = json_decode(file_get_contents('http://geocode-maps.yandex.ru/1.x/?' . http_build_query($params, '', '&')), true);

    if ($response['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0) 
    { 
        $featureMember = $response['response']['GeoObjectCollection']['featureMember'];
        $geocoder_found_members = array();
        
        foreach($featureMember as $inx => $member) {
            $geocoder_found_members[] = $member['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'];
        }
        
        $first_featureMember = $featureMember[0];
        $yandexAddress = $first_featureMember['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'];
        $geoObject = $first_featureMember['GeoObject']['metaDataProperty']['GeocoderMetaData'];
        $url = 'https://pochta.ru/postoffice-api/method/offices.find.forAddress';
        $params = array('yandexAddress' => $yandexAddress, 'geoObject' => json_encode($geoObject), 'top' => 1);
        $url .= '?' . http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status == 200) {
            
            $json_data = json_decode($data, true);
            $zip_code = $json_data['postOffices'][0];
            
            $lon_lat = explode(" ", $first_featureMember['GeoObject']['Point']['pos']);
            $url = 'https://pochta.ru/postoffice-api/method/offices.find.nearby.details';
            $currentDate = new DateTime(date("Y-m-d H:i:s"));
            var_dump($currentDate);
            $currentDateTimeText = $currentDate->format('Y-n-d')."T".$currentDate->format('H:i:s');
            $params = array('latitude' => $lon_lat[1], 'longitude' => $lon_lat[0], 'top' => 3, 'currentDateTime' => $currentDateTimeText, 'offset' => 0, 'filter' => 'ALL', 'hideTemporaryClosed' => false, 'fullAddressOnly' => true);
            $url .= '?' . http_build_query($params);
            var_dump($url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $json_data = json_decode($data, true);
            $postmail_adress = $json_data[0];
            
            if ($status == 200) {
                
                $result = array( 
                    'status' => "1",
                    'geocoder_found_members'  => $geocoder_found_members,
                    'zip_code' => $zip_code,
                    'postmail_adress' => $postmail_adress,
                );
                
            }
            else {
                
                $result = array( 
                    'status' => "1",
                    'geocoder_found_members'  => $geocoder_found_members,
                    'zip_code' => $zip_code,
                    'postmail_adress' => "",
                );
                
            }
            
        } else {
            $result = array( 
                'status' => "1",
                'geocoder_found_members'  => $geocoder_found_members,
                'zip_code' => "",
                'postmail_adress' => "",
            );
        }
    }
    
    return $result;
}
