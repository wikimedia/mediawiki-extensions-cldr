<?php

// Custom localisation used to display relative times in DiscussionTools.
// CLDR is missing this language.
// https://phabricator.wikimedia.org/T347625
// TODO: Upstream

$languageNames = [
	'ar' => 'Arbi',
	'el' => 'Grik',
	'en' => 'Inglix',
	'es' => 'Ispanhol',
	'fr' => 'Fransez',
	'gom' => 'Gõychi Konknni',
	'kn' => 'Kon\'nodd',
	'kok' => 'Konknni',
	'mr' => 'Moratthi',
	'zh' => 'Chini',
];

$currencySymbols = [
	'EUR' => '€',
	'INR' => '₹',
];

$countryNames = [
	'CN' => 'Chin',
	'CY' => 'Siprus',
	'DE' => 'Jermon',
	'EG' => 'Ejipt',
	'ES' => 'Ispania',
	'FR' => 'Frans',
	'GR' => 'Gres',
	'IN' => 'Bharot',
	'IT' => 'Italia',
	'LY' => 'Libia',
	'MK' => 'Ut\'tor Masedonia',
	'RU' => 'Roxya',
];

$timeUnits = [
	'century-one' => '{0} xekddo',
	'century-other' => '{0} xekdde',
	'day-future-one' => '{0} disan',
	'day-future-other' => '{0} disanim',
	'day-narrow-future-one' => '{0}disan',
	'day-narrow-future-other' => '{0}disanim',
	'day-narrow-past-one' => '{0}d adim',
	'day-narrow-past-other' => '{0}d adim',
	'day-one' => '{0} dis',
	'day-other' => '{0} dis',
	'day-past-one' => '{0} dis adim',
	'day-past-other' => '{0} dis adim',
	'day-short-future-one' => '{0} disan',
	'day-short-future-other' => '{0} disanim',
	'day-short-past-one' => '{0} dis adim',
	'day-short-past-other' => '{0} dis adim',
	'decade-one' => '{0} dosok',
	'decade-other' => '{0} doskam',
	'fri-future-one' => '{0} Sukraran',
	'fri-future-other' => '{0} Sukraranim',
	'fri-narrow-future-one' => '{0} Sukraran',
	'fri-narrow-future-other' => '{0} Sukraranim',
	'fri-narrow-past-one' => '{0} Su adim',
	'fri-narrow-past-other' => '{0} Su adim',
	'fri-past-one' => '{0} Sukrar adim',
	'fri-past-other' => '{0} Sukraram adim',
	'fri-short-future-one' => '{0} Sukraran',
	'fri-short-future-other' => '{0} Sukraranim',
	'fri-short-past-one' => '{0} Suk. adim',
	'fri-short-past-other' => '{0} Suk. adim',
	'hour-future-one' => '{0} voran',
	'hour-future-other' => '{0} voranim',
	'hour-narrow-future-one' => '{0}voran',
	'hour-narrow-future-other' => '{0}voranim',
	'hour-narrow-past-one' => '{0}vor adim',
	'hour-narrow-past-other' => '{0}voram adim',
	'hour-one' => '{0} vor',
	'hour-other' => '{0} voram',
	'hour-past-one' => '{0} vor adim',
	'hour-past-other' => '{0} voram adim',
	'hour-short-future-one' => '{0} voran',
	'hour-short-future-other' => '{0} voranim',
	'hour-short-past-one' => '{0} vor adim',
	'hour-short-past-other' => '{0} voram adim',
	'microsecond-one' => '{0} maikrosekond',
	'microsecond-other' => '{0} maikrosekond',
	'millisecond-one' => '{0} milisekond',
	'millisecond-other' => '{0} milisekond',
	'minute-future-one' => '{0} mintan',
	'minute-future-other' => '{0} mintanim',
	'minute-narrow-future-one' => '{0} mintan',
	'minute-narrow-future-other' => '{0} mintanim',
	'minute-narrow-past-one' => '{0}min adim',
	'minute-narrow-past-other' => '{0}min adim',
	'minute-one' => '{0} minut',
	'minute-other' => '{0} mintam',
	'minute-past-one' => '{0} minut adim',
	'minute-past-other' => '{0} mintam adim',
	'minute-short-future-one' => '{0} mintan',
	'minute-short-future-other' => '{0} mintanim',
	'minute-short-past-one' => '{0} min. adim',
	'minute-short-past-other' => '{0} min. adim',
	'mon-future-one' => '{0} Somaran',
	'mon-future-other' => '{0} Somaranim',
	'mon-narrow-future-one' => '{0} Somaran',
	'mon-narrow-future-other' => '{0} Somaranim',
	'mon-narrow-past-one' => '{0} Sm adim',
	'mon-narrow-past-other' => '{0} Sm adim',
	'mon-past-one' => '{0} Somar adim',
	'mon-past-other' => '{0} Somaram adim',
	'mon-short-future-one' => '{0} Somaran',
	'mon-short-future-other' => '{0} Somaranim',
	'mon-short-past-one' => '{0} Som. adim',
	'mon-short-past-other' => '{0} Som. adim',
	'month-future-one' => '{0} mhoinean',
	'month-future-other' => '{0} mhoineanim',
	'month-narrow-future-one' => '{0}mhoinean',
	'month-narrow-future-other' => '{0}mhoineanim',
	'month-narrow-past-one' => '{0}mh adim',
	'month-narrow-past-other' => '{0}mh adim',
	'month-one' => '{0} mhoino',
	'month-other' => '{0} mhoine',
	'month-past-one' => '{0} mhoino adim',
	'month-past-other' => '{0} mhoine adim',
	'month-short-future-one' => '{0} mhoinean',
	'month-short-future-other' => '{0} mhoineanim',
	'month-short-past-one' => '{0} mho. adim',
	'month-short-past-other' => '{0} mho. adim',
	'nanosecond-one' => '{0} nanosekond',
	'nanosecond-other' => '{0} nanosekond',
	'quarter-future-one' => '{0} timhoinallean',
	'quarter-future-other' => '{0} timhoinalleanim',
	'quarter-narrow-future-one' => '{0}timhoinallean',
	'quarter-narrow-future-other' => '{0}timhoinalleanim',
	'quarter-narrow-past-one' => '{0}timh adim',
	'quarter-narrow-past-other' => '{0}timh adim',
	'quarter-one' => '{0} timhoinallem',
	'quarter-other' => '{0} timhoinalle',
	'quarter-past-one' => '{0} timhoinallem adim',
	'quarter-past-other' => '{0} timhoinalle adim',
	'quarter-short-future-one' => '{0} timhoinallean',
	'quarter-short-future-other' => '{0} timhoinalleanim',
	'quarter-short-past-one' => '{0} timho. adim',
	'quarter-short-past-other' => '{0} timho. adim',
	'sat-future-one' => '{0} Sonvaran',
	'sat-future-other' => '{0} Sonvaranim',
	'sat-narrow-future-one' => '{0} Sonvaran',
	'sat-narrow-future-other' => '{0} Sonvaranim',
	'sat-narrow-past-one' => '{0} Sn adim',
	'sat-narrow-past-other' => '{0} Sn adim',
	'sat-past-one' => '{0} Sonvar adim',
	'sat-past-other' => '{0} Sonvaram adim',
	'sat-short-future-one' => '{0} Sonvaran',
	'sat-short-future-other' => '{0} Sonvaranim',
	'sat-short-past-one' => '{0} Son. adim',
	'sat-short-past-other' => '{0} Son. adim',
	'second-future-one' => '{0} sekondan',
	'second-future-other' => '{0} sekondanim',
	'second-narrow-future-one' => '{0}sekondan',
	'second-narrow-future-other' => '{0}sekondanim',
	'second-narrow-past-one' => '{0}sek adim',
	'second-narrow-past-other' => '{0}sek adim',
	'second-one' => '{0} sekond',
	'second-other' => '{0} sekond',
	'second-past-one' => '{0} sekond adim',
	'second-past-other' => '{0} sekond adim',
	'second-short-future-one' => '{0} sekondan',
	'second-short-future-other' => '{0} sekondanim',
	'second-short-past-one' => '{0} sek. adim',
	'second-short-past-other' => '{0} sek. adim',
	'sun-future-one' => '{0} Aitaran',
	'sun-future-other' => '{0} Aitaranim',
	'sun-narrow-future-one' => '{0} Aitaran',
	'sun-narrow-future-other' => '{0} Aitaranim',
	'sun-narrow-past-one' => '{0} Ai adim',
	'sun-narrow-past-other' => '{0} Ai adim',
	'sun-past-one' => '{0} Aitar adim',
	'sun-past-other' => '{0} Aitaram adim',
	'sun-short-future-one' => '{0} Aitaran',
	'sun-short-future-other' => '{0} Aitaranim',
	'sun-short-past-one' => '{0} Ait. adim',
	'sun-short-past-other' => '{0} Ait. adim',
	'thu-future-one' => '{0} Birestaran',
	'thu-future-other' => '{0} Birestaranim',
	'thu-narrow-future-one' => '{0} Birestaran',
	'thu-narrow-future-other' => '{0} Birestaranim',
	'thu-narrow-past-one' => '{0} Br adim',
	'thu-narrow-past-other' => '{0} Br adim',
	'thu-past-one' => '{0} Birestar adim',
	'thu-past-other' => '{0} Birestaram adim',
	'thu-short-future-one' => '{0} Birestaran',
	'thu-short-future-other' => '{0} Birestaranim',
	'thu-short-past-one' => '{0} Bre. adim',
	'thu-short-past-other' => '{0} Bre. adim',
	'tue-future-one' => '{0} Mongllaran',
	'tue-future-other' => '{0} Mongllaranim',
	'tue-narrow-future-one' => '{0} Mongllaran',
	'tue-narrow-future-other' => '{0} Mongllaranim',
	'tue-narrow-past-one' => '{0} Mg adim',
	'tue-narrow-past-other' => '{0} Mg adim',
	'tue-past-one' => '{0} Mongllar adim',
	'tue-past-other' => '{0} Mongllaram adim',
	'tue-short-future-one' => '{0} Mongllaran',
	'tue-short-future-other' => '{0} Mongllaranim',
	'tue-short-past-one' => '{0} Mon. adim',
	'tue-short-past-other' => '{0} Mon. adim',
	'wed-future-one' => '{0} Budhvaran',
	'wed-future-other' => '{0} Budhvaranim',
	'wed-narrow-future-one' => '{0} Budhvaran',
	'wed-narrow-future-other' => '{0} Budhvaranim',
	'wed-narrow-past-one' => '{0} Bu adim',
	'wed-narrow-past-other' => '{0} Bu adim',
	'wed-past-one' => '{0} Budhvar adim',
	'wed-past-other' => '{0} Budhvaram adim',
	'wed-short-future-one' => '{0} Budhvaran',
	'wed-short-future-other' => '{0} Budhvaranim',
	'wed-short-past-one' => '{0} Bud. adim',
	'wed-short-past-other' => '{0} Bud. adim',
	'week-future-one' => '{0} sumanan',
	'week-future-other' => '{0} sumananim',
	'week-narrow-future-one' => '{0}sumanan',
	'week-narrow-future-other' => '{0}sumananim',
	'week-narrow-past-one' => '{0}sum adim',
	'week-narrow-past-other' => '{0}sum adim',
	'week-one' => '{0} suman',
	'week-other' => '{0} suman',
	'week-past-one' => '{0} suman adim',
	'week-past-other' => '{0} suman adim',
	'week-short-future-one' => '{0} sumanan',
	'week-short-future-other' => '{0} sumananim',
	'week-short-past-one' => '{0} suman adim',
	'week-short-past-other' => '{0} suman adim',
	'year-future-one' => '{0} vorsan',
	'year-future-other' => '{0} vorsanim',
	'year-narrow-future-one' => '{0}vorsan',
	'year-narrow-future-other' => '{0}vorsanim',
	'year-narrow-past-one' => '{0}voros adim',
	'year-narrow-past-other' => '{0}vorsam adim',
	'year-one' => '{0} voros',
	'year-other' => '{0} vorsam',
	'year-past-one' => '{0} voros adim',
	'year-past-other' => '{0} vorsam adim',
	'year-short-future-one' => '{0} vorsan',
	'year-short-future-other' => '{0} vorsanim',
	'year-short-past-one' => '{0} voros adim',
	'year-short-past-other' => '{0} vorsam adim',
];
