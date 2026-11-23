<?php
/**
 * La photo du bandeau d'une filière, d'après son domaine (informatique, santé, agriculture…).
 *
 * Demande de l'utilisateur (2026-11-23) : des photos appropriées à chaque filière, montrant des étudiants
 * ou des professionnels africains au travail. Ce sont de vraies photos libres de droits (Openverse, Wikimedia
 * Commons), une par domaine, rangées dans assets/images/filieres/ ; leur crédit s'affiche dans le bandeau.
 *
 * Ordre de choix dans templates/filiere.php : la photo de la filière (gestion), sinon celle de son domaine,
 * sinon celle de l'établissement.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le domaine d'une filière, d'après son intitulé, à défaut celui de son département.
 * Le premier motif qui convient l'emporte : les plus précis d'abord.
 */
function sueb_domaine_filiere( $filiere_id ) {
	$motifs = array(
		'informatique'      => '/informatique|logiciel|data|intelligence artificielle|réseaux et multimédia|numérique|\bTIC\b|information et de la communication|big data|jeux vidéo|e-commerce|digital/iu',
		'maritime'          => '/maritime|océan|naval|portuaire|pêche|maricult|côtier|marine/iu',
		'sante'             => '/médec|santé|infirm|sage-femme|maïeutique|radiolog|bloc opératoire|\bORL\b|biomédic|pharmac(?!ognosie)|épidémiolog|infectiolog|biologie clinique|nutrition|diététique|hospitali|immunolog|microbiolog|médicament/iu',
		'laboratoire'       => '/chimie|biotechnolog|biochim|pharmacognosie|biologi|physique|sciences de base|fondamentales/iu',
		'agriculture'       => '/agricult|agro|élevage|production (animale|végétale)|halieut|génie rural|machinisme|aquacult|agribusiness|rural|bioéconomie/iu',
		'foret-bois'        => '/bois|forest|menuiserie|ébénisterie|biodiversité/iu',
		'genie-civil'       => '/bâtiment|travaux publics|génie civil|structures|chantier|topograph|géomati|géométrie|habitat|construction rurale|aménagement/iu',
		'energie'           => '/énerg|électr|solaire|smart grid|hydroélectricité|photovolta/iu',
		'mecanique'         => '/mécan|mécatron|maintenance|automobile|froid et climatisation|engins|fabrication|instrumentation|automatis/iu',
		'droit'             => '/droit|juridi|politique|English Law|judiciaire/iu',
		'mathematiques'     => '/mathémat|recherche opérationnelle|économétrie|statisti|modélisation|décision/iu',
		'gestion'           => '/économ|gestion|comptab|banque|financ|marketing|commerc|entrepreneur|management|ressources humaines|audit|microfinance|trading|vente/iu',
		'communication'     => '/communication|journalis|organisations/iu',
		'education'         => '/éducation|didactique|orientation|pédagog|technologie éducative/iu',
		'tourisme'          => '/touris|hôtel|hébergement|culinaire|événementiel/iu',
		'textile'           => '/textile|habillement|vestimentaire/iu',
		'logistique'        => '/transport|logisti|supply|douane|transit|flux/iu',
		'eau-environnement' => '/\beaux?\b|hydraul|assainissement|environnement|météorolog|climat|déchets|QHSE/iu',
		'geologie'          => '/géolog|géophys|minière|minier|géotechni|géosciences/iu',
		'arts'              => '/\barts?\b|musique|scène|patrimoine|histoire|littérature|culture/iu',
		'architecture'      => '/architect|urbanisme/iu',
		'lettres'           => '/langues|traduction|lettres|philosoph|psycholog|sociolog|anthropolog|géographie/iu',
	);
	foreach ( array( get_the_title( $filiere_id ), get_the_title( wp_get_post_parent_id( $filiere_id ) ) ) as $texte ) {
		foreach ( $motifs as $domaine => $motif ) {
			if ( preg_match( $motif, (string) $texte ) ) {
				return $domaine;
			}
		}
	}
	return 'general';
}

/**
 * Les photos des domaines et leur crédit : domaine => array( fichier, titre, auteur, licence, page source ).
 * Un domaine absent prend la photo « general ».
 */
function sueb_photos_domaines() {
	/* Choisies le 2026-11-23 sur Wikimedia Commons, Flickr et rawpixel (via Openverse) ; converties en WebP, 1920 px au plus. */
	return array(
		'agriculture'        => array( 'fichier' => 'agriculture.webp', 'titre' => 'Cultivateur de cacao 01', 'auteur' => 'KokoDZ', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Cultivateur_de_cacao_01.jpg' ),
		'architecture'       => array( 'fichier' => 'architecture.webp', 'titre' => 'Nigerian Institute of Architecture 06', 'auteur' => 'Anasskoko', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Nigerian_Institute_of_Architecture_06.jpg' ),
		'arts'               => array( 'fichier' => 'arts.webp', 'titre' => 'Celebrating Folklore- Traditional Drum and Shaker Performance by Mulago Youth', 'auteur' => 'Nambogo Catharine', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Celebrating_Folklore-_Traditional_Drum_and_Shaker_Performance_by_Mulago_Youth.jpg' ),
		'communication'      => array( 'fichier' => 'communication.webp', 'titre' => 'journalism student practices radio presentation', 'auteur' => 'The African Union Mission in Somalia', 'licence' => 'CC0', 'source' => 'https://www.rawpixel.com/image/3373105/free-photo-image-headphones-person-headset-somali' ),
		'droit'              => array( 'fichier' => 'droit.webp', 'titre' => '15th EMC2 - African Regional Round', 'auteur' => 'elsa.org', 'licence' => 'CC BY-SA 2.0', 'source' => 'https://www.flickr.com/photos/147983434@N05/33980327900' ),
		'eau-environnement'  => array( 'fichier' => 'eau-environnement.webp', 'titre' => 'Musoke Deo MDK-MUSO. A SIMPLE PEDAL-WATER PUMP USED IN URBAN & RURAL POOR COMMUNITIES', 'auteur' => 'MDK-MUSO', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=101199867' ),
		'education'          => array( 'fichier' => 'education.webp', 'titre' => 'Primary School Teacher 2', 'auteur' => 'Minette Lontsie', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Primary_School_Teacher_2.jpg' ),
		'energie'            => array( 'fichier' => 'energie.webp', 'titre' => 'Solar installation technician', 'auteur' => 'SAgbley', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Solar_installation_technician.jpg' ),
		'foret-bois'         => array( 'fichier' => 'foret-bois.webp', 'titre' => 'Carpenters at Work 31', 'auteur' => 'Iwuala Lucy', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Carpenters_at_Work_31.jpg' ),
		'general'            => array( 'fichier' => 'general.webp', 'titre' => 'Africa Environnement 2023 Atelier à Dschang', 'auteur' => 'Serieminou', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=131363847' ),
		'genie-civil'        => array( 'fichier' => 'genie-civil.webp', 'titre' => 'Cementing Ethiopia\'s progress', 'auteur' => 'DFID - UK Department for International Development', 'licence' => 'CC BY 2.0', 'source' => 'https://www.flickr.com/photos/14214150@N02/8757865770' ),
		'geologie'           => array( 'fichier' => 'geologie.webp', 'titre' => 'A new generation of industrial leaders', 'auteur' => 'DFID - UK Department for International Development', 'licence' => 'CC BY 2.0', 'source' => 'https://www.flickr.com/photos/14214150@N02/8756741875' ),
		'gestion'            => array( 'fichier' => 'gestion.webp', 'titre' => 'Kenya Office Work', 'auteur' => 'Moritz Poll', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Kenya_Office_Work.jpg' ),
		'informatique'       => array( 'fichier' => 'informatique.webp', 'titre' => 'Students using a computer laptop', 'auteur' => 'Kwameghana(Bright Kwame Ayisi)', 'licence' => 'CC0', 'source' => 'https://commons.wikimedia.org/wiki/File:Students_using_a_computer_laptop.jpg' ),
		'laboratoire'        => array( 'fichier' => 'laboratoire.webp', 'titre' => 'Africa Food Security Research 2 (10665117885)', 'auteur' => 'Kate Holt/AusAID', 'licence' => 'CC BY 2.0', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=32168460' ),
		'lettres'            => array( 'fichier' => 'lettres.webp', 'titre' => 'UFH library', 'auteur' => 'Miyathembani73', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:UFH_library.jpg' ),
		'logistique'         => array( 'fichier' => 'logistique.webp', 'titre' => 'Déplacement de containers au port', 'auteur' => 'houaito affo daniel', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=88019725' ),
		'maritime'           => array( 'fichier' => 'maritime.webp', 'titre' => 'Idenau Cameroon Shipping boats', 'auteur' => 'Blaizo 237', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/w/index.php?curid=107448166' ),
		'mathematiques'      => array( 'fichier' => 'mathematiques.webp', 'titre' => 'AMISOM KDF Donation-5 (24016934072)', 'auteur' => 'AMISOM Public Information', 'licence' => 'CC0', 'source' => 'https://commons.wikimedia.org/wiki/File:AMISOM_KDF_Donation-5_(24016934072).jpg' ),
		'mecanique'          => array( 'fichier' => 'mecanique.webp', 'titre' => 'Auto mechanics class at Chawama Youth Resource Centre', 'auteur' => 'IICD', 'licence' => 'CC BY 2.0', 'source' => 'https://www.flickr.com/photos/32348003@N02/5348624275' ),
		'sante'              => array( 'fichier' => 'sante.webp', 'titre' => 'Clinic visit', 'auteur' => 'Hodge120', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Clinic_visit.jpg' ),
		'textile'            => array( 'fichier' => 'textile.webp', 'titre' => 'Tailor, South Kivu (12188073964)', 'auteur' => 'Oxfam East Africa', 'licence' => 'CC BY 2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Tailor,_South_Kivu_(12188073964).jpg' ),
		'tourisme'           => array( 'fichier' => 'tourisme.webp', 'titre' => 'Kampala Serena Hotel Staff at work', 'auteur' => 'MercyAsiima', 'licence' => 'CC BY-SA 4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Kampala_Serena_Hotel_Staff_at_work.jpg' ),
	);
}

/** La photo du domaine d'une filière, avec son crédit : array( url, credit, source ), ou null. */
function sueb_photo_filiere( $filiere_id ) {
	$photos  = sueb_photos_domaines();
	$domaine = sueb_domaine_filiere( $filiere_id );
	$p       = $photos[ $domaine ] ?? ( $photos['general'] ?? null );
	if ( ! $p || ! file_exists( SUEB_DIR . '/assets/images/filieres/' . $p['fichier'] ) ) {
		return null;
	}
	return array(
		'url'    => SUEB_URI . '/assets/images/filieres/' . $p['fichier'],
		'credit' => sprintf( 'Photo : %s, %s', $p['auteur'], $p['licence'] ),
		'source' => $p['source'],
	);
}
