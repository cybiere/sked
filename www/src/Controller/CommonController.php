<?php

namespace App\Controller;

class CommonController{

	public static function getHolidays($baseYear, $format = "u"){
		$holidays = array();
		for($i=-1;$i<=1;$i++){
			$year=$baseYear+$i;
			$easterDate  = \easter_date($year);
			$easterDay   = date('j', $easterDate);
			$easterMonth = date('n', $easterDate);
			$easterYear  = date('Y', $easterDate);

			// Dates fixes
			$holidays[] = mktime(0, 0, 0, 1,  1,  $year);  // 1er janvier
			$holidays[] = mktime(0, 0, 0, 5,  1,  $year);  // Fête du travail
			$holidays[] = mktime(0, 0, 0, 5,  8,  $year);  // Victoire des alliés
			$holidays[] = mktime(0, 0, 0, 7,  14, $year);  // Fête nationale
			$holidays[] = mktime(0, 0, 0, 8,  15, $year);  // Assomption
			$holidays[] = mktime(0, 0, 0, 11, 1,  $year);  // Toussaint
			$holidays[] = mktime(0, 0, 0, 11, 11, $year);  // Armistice
			$holidays[] = mktime(0, 0, 0, 12, 25, $year);  // Noel

			// Dates variables
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear);
			$holidays[] = mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear);
		}

		sort($holidays);

		array_walk(
			$holidays,
			function(&$value) use ($format) {
				$value = date(
					$format,
					$value
				);
			}
		);

		return $holidays;
	}

	public static function calcOffset($startDate,$user){
		$maxOffset = 0;
		for($sliceIter=1;$sliceIter<=$startDate->format("t")*2;$sliceIter++){
			$curDate = new \Datetime($startDate->format("m")."/".ceil($sliceIter/2)."/".$startDate->format("Y"));
			$hour = $sliceIter%2==1?"am":"pm";
			foreach($user->getPlannings() as $planning){
				if($planning->getStartDate() == $curDate && $planning->getStartHour() == $hour){
					$offCount=0;
					while(isset($offset[$sliceIter][$offCount])) $offCount++;
					//on est sur le slice de départ du projet
					$filledSlices = $planning->getNbSlices();
					$durationDate = clone $curDate;
					for($durationIter=0 ; $durationIter < $filledSlices ; $durationIter++){
						if($durationDate->format('N') >= 6) $filledSlices++;
						$offset[$sliceIter+$durationIter][$offCount] = $planning;
						$durationDate->modify('+12hours');
					}
					$planning->offset = $offCount; 
					if($offCount > $maxOffset) $maxOffset = $offCount; 
				}
			}
		}
		return $maxOffset;
	}
}
