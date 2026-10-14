<?php
/**
 * Filières des établissements privés (IPES) sous la tutelle académique de
 * l'UEb, d'après le document « Les filières concernées » remis pour la
 * rubrique Conventions et partenariats (octobre 2026).
 *
 * Indexé par sigle (méta « sigle » des partenaires). Pour chaque IPES : son
 * nom, sa ville, les établissements de l'UEb qui assurent la tutelle, ses
 * filières (« SIGLE : filière », une par entrée, comme dans le champ de
 * l'admin), un résumé et le texte de sa fiche. Un IPES absent de la base est
 * créé ; les dates non vérifiées sur les originaux restent dans le texte.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

return array(
	'IUSTY'     => array(
		'titre'    => 'Institut Universitaire des Sciences et Techniques de Yaoundé',
		'ville'    => 'Yaoundé',
		'tutelle'  => 'FMSP, ISABEE, FSEG, FALSH',
		'date'     => '2023-02-03',
		'filieres' => array(
			'FMSP : Filières de médecine',
			'ISABEE : Filières d’agriculture, du bois et de l’environnement',
			'FSEG : Gestion des ressources humaines',
			'FSEG : Gestion logistique et transport',
			'FSEG : Marketing et communication',
			'FSEG : Management des projets',
			'FSEG : Gestion des établissements hospitaliers',
			'FSEG : Banque',
			'FSEG : Finance',
			'FSEG : Contrôle-audit',
			'FSEG : Marketing, commerce et vente',
			'FSEG : Management des opérations de commerce international',
			'FALSH : Création et management des entreprises touristiques et hôtelières',
			'FALSH : Production cinématographique et audiovisuelle',
		),
		'resume'   => 'Premier partenaire privé de l’UEb : accord initial le 12 décembre 2022, cérémonie officielle le 3 février 2023 ; la tutelle s’étend à la FSEG et à la FALSH depuis mars 2025.',
		'texte'    => '<p>L’Institut Universitaire des Sciences et Techniques de Yaoundé (IUSTY) est le premier partenaire privé important de l’Université d’Ebolowa, qui le présente comme son « premier partenaire ».</p>'
			. '<ul><li><strong>12 décembre 2022</strong> : accord initial entre les deux institutions, portant notamment sur la tutelle de filières en médecine, agriculture, bois et environnement ;</li>'
			. '<li><strong>3 février 2023</strong> : cérémonie officielle de signature, sur le campus de l’Université d’Ebolowa ;</li>'
			. '<li><strong>Mars 2025</strong> : deux nouvelles conventions spécifiques, avec la Faculté des Sciences Économiques et de Gestion (FSEG) et la Faculté des Arts, Lettres et Sciences Humaines (FALSH). Elles s’ajoutent aux relations avec la Faculté de Médecine et l’ISABEE.</li></ul>'
			. '<p>Le jour exact de signature des conventions de 2025 reste à confirmer sur les originaux.</p>',
	),
	'HiSCoTS'   => array(
		'titre'    => 'Higher School of Computer and Technology Studies',
		'ville'    => 'Yaoundé',
		'tutelle'  => 'FS',
		'date'     => '',
		'filieres' => array(
			'FS : Robotics',
			'FS : Computer Science and Digitalization',
			'FS : Virtual Reality and Augmented Reality',
			'FS : Data Science',
			'FS : Computer Security',
			'FS : Computer Engineering',
			'FS : Software Engineering',
			'FS : Information and Communication for Development',
		),
		'resume'   => 'Sous la tutelle de la Faculté des Sciences : concours commun UEb–HiSCoTS de 850 places en Bachelor of Engineering.',
		'texte'    => '<p>La Higher School of Computer and Technology Studies (HiSCoTS), à Yaoundé, est placée sous la tutelle académique de la Faculté des Sciences de l’Université d’Ebolowa. Le MINESUP y a autorisé le recrutement de 850 étudiants en Bachelor of Engineering pour 2025-2026.</p>'
			. '<ul><li><strong>2024-2025</strong> : première présence documentée dans les concours sous tutelle de l’UEb ;</li>'
			. '<li><strong>2025-2026</strong> : tutelle confirmée par la décision MINESUP n° 18250474 du 22 juillet 2025 ;</li>'
			. '<li><strong>2026-2027</strong> : tutelle toujours active, concours commun UEb–HiSCoTS de 850 places.</li></ul>',
	),
	'ISLAPE'    => array(
		'titre'    => 'Institut Supérieur La Perle',
		'ville'    => 'Douala',
		'tutelle'  => 'ESTLC',
		'date'     => '',
		'filieres' => array(
			'ESTLC : Gestion Logistique, Transport et Commerce (GLTCO)',
			'ESTLC : Technologies de Transport et de Logistique (TTL)',
		),
		'resume'   => 'Sous la tutelle de l’ESTLC : 100 places au concours d’ingénieurs 2025-2026, en GLTCO et en TTL.',
		'texte'    => '<p>L’Institut Supérieur La Perle (ISLAPE), à Douala, est placé sous la tutelle académique de l’École Supérieure de Transport, de Logistique et de Commerce (ESTLC) de l’Université d’Ebolowa.</p>'
			. '<p>Le communiqué MINESUP n° 25-00550/MINESUP/SG/DAUQ/SDEAC du 14 novembre 2025 a ouvert le concours d’entrée en première année du cursus ingénieur dans les IPES sous tutelle de l’ESTLC, pour le 22 novembre 2025 :</p>'
			. '<ul><li>50 places en Gestion Logistique, Transport et Commerce (GLTCO) ;</li><li>50 places en Technologies de Transport et de Logistique (TTL) ;</li><li>soit 100 places.</li></ul>',
	),
	'BHI'       => array(
		'titre'    => 'Brains Higher Institute',
		'ville'    => 'Douala',
		'tutelle'  => 'ESTLC',
		'date'     => '',
		'filieres' => array(
			'ESTLC : Gestion Logistique, Transport et Commerce (GLTCO)',
			'ESTLC : Technologies de Transport et de Logistique (TTL)',
		),
		'resume'   => 'Sous la tutelle de l’ESTLC : 100 places au concours d’ingénieurs 2025-2026, en GLTCO et en TTL.',
		'texte'    => '<p>Le Brains Higher Institute (BHI), à Douala, est placé sous la tutelle académique de l’ESTLC de l’Université d’Ebolowa. Il figure dans le même communiqué MINESUP n° 25-00550 du 14 novembre 2025 que l’ISLAPE, avec pour 2025-2026 :</p>'
			. '<ul><li>50 places en Gestion Logistique, Transport et Commerce (GLTCO) ;</li><li>50 places en Technologies de Transport et de Logistique (TTL) ;</li><li>soit 100 places.</li></ul>'
			. '<p>Les décisions MINESUP n° 18260617 du 2 septembre 2026 et du 7 septembre 2026 portent sur l’entrée en première année.</p>',
	),
	'ISSSM'     => array(
		'titre'    => 'Institut Supérieur des Sciences de la Santé',
		'ville'    => '',
		'tutelle'  => 'FMSP',
		'date'     => '',
		'filieres' => array(
			'FMSP : BTS dans le domaine de la santé',
			'FMSP : HND dans le domaine de la santé',
			'FMSP : Licences dans le domaine de la santé',
			'FMSP : Masters dans le domaine de la santé',
		),
		'resume'   => 'Établissement de santé qui se dit sous tutelle académique de l’UEb, avec une tutelle technique de la FMSP ; convention à documenter.',
		'texte'    => '<p>L’Institut Supérieur des Sciences de la Santé (ISSSM) indique être sous la tutelle académique de l’Université d’Ebolowa, avec une tutelle technique de la Faculté de Médecine et des Sciences Pharmaceutiques de Sangmélima. Il propose des BTS, HND, licences et masters dans le domaine de la santé.</p>'
			. '<p>Tutelle attestée par l’établissement, convention à documenter : la convention signée avec l’UEb et sa date n’ont pas été retrouvées dans les sources publiques.</p>',
	),
	'ISM'       => array(
		'titre'    => 'Institut Supérieur Makasa',
		'ville'    => 'Yaoundé',
		'tutelle'  => 'ENSET',
		'date'     => '',
		'filieres' => array(
			'ENSET : Communication et médias',
			'ENSET : Gestion et commerce',
			'ENSET : Réseaux et télécommunications',
			'ENSET : Santé et paramédical',
			'ENSET : Sciences et technologies',
			'ENSET : Tourisme, hôtellerie et restauration',
			'ENSET : Licences professionnelles',
			'ENSET : Masters professionnels',
		),
		'resume'   => 'Sous la tutelle académique de l’UEb, par l’ENSET d’Ebolowa ; convention spécifique UEb–ENSET–ISM.',
		'texte'    => '<p>L’Institut Supérieur Makasa (ISM), à Yaoundé, se présente comme un IPES sous la tutelle académique de l’Université d’Ebolowa, par l’ENSET d’Ebolowa, ce que confirme sa propre page. Une convention spécifique UEb–ENSET–ISM a été retrouvée en 2025.</p>'
			. '<p>La tutelle est documentée en 2026 ; la date de signature de la convention reste à obtenir auprès de l’ENSET, de l’UEb ou du MINESUP.</p>',
	),
	'Sapientia' => array(
		'titre'    => 'Institut Supérieur Sapientia',
		'ancien'   => 'Institut Sapientia',
		'ville'    => 'Akonolinga',
		'tutelle'  => 'FS, FSEG, FSJP',
		'date'     => '2026-09-08',
		'filieres' => array(
			'FS, FSEG, FSJP : BTS et HND',
			'FS, FSEG, FSJP : Licences professionnelles',
			'FS, FSEG, FSJP : Masters professionnels',
			'FSJP : Capacité en droit',
		),
		'resume'   => 'Convention du 8 septembre 2026 : la FS, la FSEG et la FSJP encadrent les étudiants de cet institut d’Akonolinga.',
		'texte'    => '<p>L’Institut Supérieur Sapientia, à Akonolinga, a signé une convention avec l’Université d’Ebolowa le <strong>8 septembre 2026</strong>, au rectorat de l’UEb à Ebolowa. C’est le partenariat le plus récent avec un IPES.</p>'
			. '<p>La convention concerne la Faculté des Sciences (FS), la Faculté des Sciences Économiques et de Gestion (FSEG) et la Faculté des Sciences Juridiques et Politiques (FSJP), chargées de son application et de l’encadrement académique des étudiants. Le site du Complexe Sapientia confirme la tutelle et mentionne des formations BTS et HND, des licences et masters professionnels et une capacité en droit.</p>',
	),
);
