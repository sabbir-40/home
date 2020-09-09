<?php
	try {
		$record = $reader->city( $ip ) ;
		$geo_country_code = ( isset( $record->country->isoCode ) ) ? $record->country->isoCode : "" ;
		$geo_country = ( $geo_country_code != "" ) ? $record->country->name : "" ;
		$geo_region = ( isset( $record->mostSpecificSubdivision->name ) ) ? $record->mostSpecificSubdivision->name : "" ;
		$geo_city = ( isset( $record->city->name ) ) ? $record->city->name : "" ;
		$geo_lat = ( isset( $record->location->latitude ) ) ? $record->location->latitude : 28.613459424004414 ;
		$geo_long = ( isset( $record->location->longitude ) ) ? $record->location->longitude : -40.4296875 ;
	} catch (Exception $e) {
		$geo_country_code = $geo_region = $geo_city = "" ; $geo_lat = $geo_long = 0 ;
	}
?>