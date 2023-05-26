<?php
//search full address
function ADDRESS($address) {
	include('lib.curl.php');
	
	$result = [];
	
	// search for address from OGCIO
	$data = CURL('https://www.als.ogcio.gov.hk/lookup?n=10&q='.urlencode($address), null, ['Accept: application/json']);
	
	// check if the response is to the request
	if (!strlen($data)) {
		return null;
	}
	
	// convert result to json
	$data = json_decode($data);
	if ($data->RequestAddress->AddressLine[0] != $address) {
		return $result;
	}
	
	// the district code mapping
	$map = [
		'CW' => ['zh' => '中西區', 'en' => 'CENTRAL AND WESTERN'],
		'EST' => ['zh' => '東區', 'en' => 'EASTERN DISTRICT'],
		'STH' => ['zh' => '南區', 'en' => 'SOUTHERN DISTRICT'],
		'WC' => ['zh' => '灣仔', 'en' => 'WAN CHAI'],
		
		'KLC' => ['zh' => '九龍城', 'en' => 'KOWLOON CITY'],
		'KT' => ['zh' => '觀塘', 'en' => 'KWUN TONG'],
		'SSP' => ['zh' => '深水埗', 'en' => 'SHAM SHUI PO'],
		'WTS' => ['zh' => '黃大仙', 'en' => 'WONG TAI SIN'],
		'YTM' => ['zh' => '油尖旺', 'en' => 'YAU TSIM MONG'],
		
		'KC' => ['zh' => '葵青區', 'en' => 'KWAI TSING'],
		'NTH' => ['zh' => '北區', 'en' => 'NORTH DISTRICT'],
		'SK' => ['zh' => '西貢', 'en' => 'SAI KUNG'],
		'ST' => ['zh' => '沙田', 'en' => 'SHA TIN'],
		'TM' => ['zh' => '屯門', 'en' => 'TUEN MUN'],
		'TP' => ['zh' => '大埔', 'en' => 'TAI PO'],
		'TW' => ['zh' => '荃灣', 'en' => 'TSUEN WAN'],
		'YL' => ['zh' => '元朗', 'en' => 'YUEN LONG'],
		
		'ILD' => ['zh' => '離島區', 'en' => 'ISLANDS DISTRICT'],
		
		'HK' => ['zh' => '香港', 'en' => 'HONG KONG ISLAND'],
		'KLN' => ['zh' => '九龍', 'en' => 'KOWLOON'],
		'NT' => ['zh' => '新界', 'en' => 'NEW TERRITORIES']
	];
	
	// loop for the result set
	foreach ($data->SuggestedAddress as $r) {
		$line = ['zh' => [], 'en' => [], 'reg' => null, 'loc' => null];
		$d = "";
		
		// parse chinese address
		$address = $r->Address->PremisesAddress->ChiPremisesAddress;
		
		// parse chinese district
		if (isset($address->ChiDistrict->DcDistrict)) {
			$d = $map[$address->ChiDistrict->DcDistrict];
			$d = [$d? $d['zh']: ''];
			
			// parse sub-district
			if (isset($address->ChiDistrict->{'Sub-district'})) {
				$d[] = $address->ChiDistrict->{'Sub-district'};
			}
			
			$line['zh'][] = implode(' ', $d);
		}
		
		// parse chinese street name
		if (isset($address->ChiStreet->StreetName)) {
			$s = $address->ChiStreet->StreetName;
			$s = (strlen($d[0]) && stripos($s, $d[0]) !== false? trim(substr($s, strlen(d[0]))): $s);
			
			$n = [];
			
			// parse street No.
			if (isset($address->ChiStreet->BuildingNoFrom)) {
				$n[] = $address->ChiStreet->BuildingNoFrom;
			}
			if (isset($address->ChiStreet->BuildingNoTo)) {
				$n[] = $address->ChiStreet->BuildingNoTo;
			}
			
			// normalize street No.
			if (count($n)) {
				$s .= ' '.implode('-', $n).' 號';
			}
			
			$line['zh'][] = $s;
		}
		
		// parse chinese village name
		if (isset($address->ChiVillage->VillageName)) {
			$s = $address->ChiVillage->VillageName;
			
			$n = [];
			
			// parse village No.
			if (isset($address->ChiVillage->BuildingNoFrom)) {
				$n[] = $address->ChiVillage->BuildingNoFrom;
			}
			if (isset($address->ChiVillage->BuildingNoTo)) {
				$n[] = $address->ChiVillage->BuildingNoTo;
			}
			
			// normalize village No.
			if (count($n)) {
				$s .= ' '.implode('-', $n).' 號';
			}
			
			$line['zh'][] = $s;
		}
		
		// parse chinese estate name
		if (isset($address->ChiEstate->EstateName)) {
			$s = $address->ChiEstate->EstateName;
			
			// parse estate phase name
			if (isset($address->ChiEstate->ChiPhase->PhaseName)) {
				$s .= ' '.$address->ChiEstate->ChiPhase->PhaseName;
			}
			
			// parse estate phase No.
			if (isset($address->ChiEstate->ChiPhase->PhaseNo)) {
				$s .= ' '.$address->ChiEstate->ChiPhase->PhaseNo;
			}
			
			$line['zh'][] = $s;
		}
		
		// parse chinese block name
		if (isset($address->ChiBlock->BlockNo)) {
			$s = $address->ChiBlock->BlockNo;
			
			// parse block name descriptor
			if (isset($address->ChiBlock->BlockDescriptor)) {
				$s .= ' '.$address->ChiBlock->BlockDescriptor;
			}
			
			$line['zh'][] = $s;
		}
		
		// parse chinese building name
		if (isset($address->BuildingName)) {
			$line['zh'][] = $address->BuildingName;
		}
		
		// parse english address
		$address = $r->Address->PremisesAddress->EngPremisesAddress;
		
		// parse the block No.
		if (isset($address->EngBlock->BlockNo)) {
			$line['en'][] = $address->EngBlock->BlockNo;
			
			if (isset($address->EngBlock->BlockDescriptor)) {
				$line['en'][] = $address->EngBlock->BlockDescriptor;
			}
			
			$line['en'] = [
				implode(' ', $address->EngBlock->BlockDescriptorPrecedenceIndicator == "Y"?
					array_reverse($line['en']): $line['en']
				)
			];
		}
		
		// parse english building name
		if (isset($address->BuildingName)) {
			$line['en'][] = $address->BuildingName;
		}
		
		// parse english estate name
		if (isset($address->EngEstate->EstateName)) {
			$s = "";
			
			// parse estate phase No.
			if (isset($address->EngEstate->EngPhase->PhaseNo)) {
				$s .= $address->EngEstate->EngPhase->PhaseNo.' ';
			}
			
			// parse estate phase name
			if (isset($address->EngEstate->EngPhase->PhaseName)) {
				$s .= $address->EngEstate->EngPhase->PhaseName.' ';
			}
			
			$line['en'][] = $s.$address->EngEstate->EstateName;
		}
		
		// parse english village name
		if (isset($address->EngVillage->VillageName)) {
			$s = '';
			$n = [];
			
			// parse village No.
			if (isset($address->EngVillage->BuildingNoFrom)) {
				$n[] = $address->EngVillage->BuildingNoFrom;
			}
			if (isset($address->EngVillage->BuildingNoTo)) {
				$n[] = $address->EngVillage->BuildingNoTo;
			}
			
			// normalize village No.
			if (count($n)) {
				$s = implode('-', $n).' ';
			}
			
			$s .= $address->EngVillage->VillageName;
			$d = strripos($s, ',');
			$line['en'][] = ($d === false? $s: substr($s, 0, $d));
		}
		
		// parse english street name
		if (isset($address->EngStreet->StreetName)) {
			$s = '';
			$n = [];
			
			// parse street No.
			if (isset($address->EngStreet->BuildingNoFrom)) {
				$n[] = $address->EngStreet->BuildingNoFrom;
			}
			if (isset($address->EngStreet->BuildingNoTo)) {
				$n[] = $address->EngStreet->BuildingNoTo;
			}
			
			// normalize street No.
			if (count($n)) {
				$s = implode('-', $n).' ';
			}
			
			$s .= $address->EngStreet->StreetName;
			$d = strripos($s, ',');
			$line['en'][] = ($d === false? $s: substr($s, 0, $d));
		}
		
		// parse english district
		if (isset($address->EngDistrict->DcDistrict)) {
			$s = "";
			
			// parse sub-district
			if (isset($address->EngDistrict->{'Sub-district'})) {
				$s .= $address->EngDistrict->{'Sub-district'}.' ';
			}
			
			$d = $map[$address->EngDistrict->DcDistrict];
			$line['en'][] = $s.($d? $d['en']: '');
			
			$line['reg'] = $d;
		}
		
		// parse location
		$line['loc'] = $map[$address->Region];
		
		$result[] = $line;
	};
	
	return $result;
}

print_r(ADDRESS('yuk mei'));