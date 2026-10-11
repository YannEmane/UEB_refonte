<?php
/**
 * Établissements privés d'enseignement supérieur (IPES) sous la tutelle
 * académique de l'UEb, d'après le « Tableau chronologique des conventions et
 * partenariats » remis par l'université (octobre 2026). Villes : répertoire
 * des IPES du MINESUP (édition 2022) et sites des établissements ; laissées
 * vides quand elles ne sont pas confirmées.
 *
 * Même format que $partenaires dans installer.php : titre, type, date de
 * signature (AAAA-MM-JJ si elle est connue au jour près), lien, texte, photo,
 * logo, ville, sigle, tutelle à l'UEb.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

$type = 'Établissement privé sous tutelle académique';

return array(
	array( 'Institut Universitaire des Sciences et Techniques de Yaoundé', $type, '2023-02-03', '', 'Accord initial du 12 décembre 2022, scellé lors d’une cérémonie officielle le 3 février 2023 : tutelle académique de l’UEb pour les formations en médecine, agriculture, bois et environnement. De nouvelles conventions spécifiques, en mars 2025, y associent la FSEG et la FALSH.', '', 'partenaires/iusty.webp', 'Yaoundé', 'IUSTY', 'FSEG, FALSH' ),
	array( 'Institut Panafricain de Formation Supérieure', $type, '2023-04-25', '', 'Tutelle académique depuis le 25 avril 2023 pour les formations en sciences biomédicales et médico-sanitaires.', '', '', '', 'IPAF-SUP', '' ),
	array( 'Higher School of Computer and Technology Studies', $type, '', '', 'Établissement présent à Yaoundé, Douala, Ebolowa et Bafoussam, dont les concours d’entrée sont placés sous la tutelle de la Faculté des Sciences (décision MINESUP de 2024, puis décision n° 18250474 du 22 juillet 2025). Formations d’ingénieurs en informatique et en technologies.', '', 'partenaires/hiscots.webp', 'Yaoundé', 'HiSCoTS', 'FS' ),
	array( 'Institut Universitaire des Grandes Écoles des Tropiques', $type, '', '', 'Tutelle académique et coopération de formation depuis janvier 2026, avec cet institut de Douala (Bonamoussadi).', '', 'partenaires/iuget.webp', 'Douala', 'IUGET', '' ),
	array( 'UNHIMAS Bilingual University Institute', $type, '2026-01-22', '', 'Tutelle académique depuis le 22 janvier 2026 : l’ENSET d’Ebolowa assure la tutelle technique de cet institut bilingue de Yaoundé (Universal Higher Institute of Management and Sciences).', '', 'partenaires/unhimas.webp', 'Yaoundé', 'UNHIMAS', 'ENSET' ),
	array( 'Institut Polytechnique des Métiers du Bâtiment, des Travaux Publics et de l’Entrepreneuriat', $type, '2026-01-22', '', 'Tutelle académique conjointe de l’ENSET d’Ebolowa et de la FSEG depuis le 22 janvier 2026, pour cet institut de Yaoundé reconnu depuis plus de vingt ans dans le bâtiment et les travaux publics.', '', 'partenaires/ipmbtpe-photo.webp', 'Yaoundé', 'IPMBTPE', 'ENSET, FSEG' ),
	array( 'Institut Supérieur La Perle', $type, '', '', 'Formation d’ingénieurs, notamment en logistique et transport, sous la tutelle académique de l’ESTLC : convention antérieure à 2025, active en 2026 (communiqué MINESUP n° 25-00550 du 14 novembre 2025). Institut établi à Douala (Yassa).', '', 'partenaires/islape.webp', 'Douala', 'ISLAPE', 'ESTLC' ),
	array( 'Brains Higher Institute', $type, '', '', 'Technologies du transport et de la logistique, sous la tutelle académique de l’ESTLC depuis 2025 (communiqué MINESUP n° 25-00550 du 14 novembre 2025 ; décisions MINESUP n° 18260617 du 2 septembre 2026 et du 7 septembre 2026 pour l’entrée en première année). Établissement de la région du Littoral.', '', '', '', 'BHI', 'ESTLC' ),
);
