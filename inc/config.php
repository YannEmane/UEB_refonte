<?php
/**
 * Données fixes de l'université : identité, établissements, fonctions du
 * rectorat. Le contenu éditorial (noms, photos, textes) vit dans l'admin.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

const SUEB_UNIVERSITE = array(
	'fr'    => "Université d'Ebolowa",
	'en'    => 'The University of Ebolowa',
	'bp'    => 'BP 118 Ebolowa',
	'tel'   => '+237 6 76 29 54 88',
	'email' => 'info@unv-ebolowa.cm',
	'ville' => 'Ebolowa, Région du Sud, Cameroun',
);

/* Liens vers les plateformes existantes. */
const SUEB_LIENS = array(
	'preinscription' => 'https://preinscription.unv-ebolowa.cm/',
	'inscription'    => 'http://inscription.unv-ebolowa.cm/',
	/* Réseaux officiels, repris du site de préinscription. */
	'facebook'       => 'https://facebook.com/share/18i1CQ2QuY',
	'youtube'        => 'https://www.youtube.com/@universiteebolowa',
);

/**
 * Adresse de la page des dirigeants, qui ne figure plus sur l'accueil. Il suffira de
 * créer une page WordPress intitulée « Dirigeants » (adresse /dirigeants/) ; une autre
 * adresse se règle avec le filtre « sueb_url_dirigeants ».
 */
function sueb_url_dirigeants() {
	return apply_filters( 'sueb_url_dirigeants', home_url( '/dirigeants/' ) );
}

/**
 * Les neuf établissements, repris du site des inscriptions.
 * Sert à créer les fiches à l'installation et de repli (logo, couleur).
 * « presentation » : texte de présentation de la page (repli tant que la fiche n'a pas de texte dans l'admin).
 * « responsable » : doyen ou directeur en poste au 11 octobre 2026, avec son
 * portrait dans assets/images/personnel/<sigle>.webp (installation seulement :
 * ensuite, les noms et photos se changent dans Personnel, dans l'admin).
 */
function sueb_etablissements() {
	return array(
		'FS'     => array( 'fr' => 'Faculté des Sciences', 'couleur' => '#1f5aa6', 'ville' => 'Ebolowa', 'photos' => array( 'equipe-fs', 'amphi-cours' ), 'responsable' => 'Pr Carole EDIMA DURANT', 'presentation' => "Laboratoires, travaux pratiques, rigueur expérimentale : la Faculté des Sciences vous donne le socle scientifique solide qu'exigent les métiers techniques, de santé et de recherche. Fidèles à la philosophie « un étudiant, une formation », ses programmes associent fondamentaux, pratique et ouverture vers l'emploi, avec des pôles d'excellence en mathématiques appliquées, sciences biomédicales et agriculture biologique." ),
		'FSJP'   => array( 'fr' => 'Faculté des Sciences Juridiques et Politiques', 'couleur' => '#8B1E1E', 'ville' => 'Ebolowa', 'photos' => array( 'vie-etudiante-4', 'salle' ), 'responsable' => 'Pr MANASSE ABOYA ENDONG', 'presentation' => "Magistrat, avocat, notaire, juriste d’entreprise, cadre de l’administration ou des collectivités territoriales : votre avenir se construit à la Faculté des Sciences Juridiques et Politiques. Au campus de Mekalat, la faculté met l’accent sur le droit des affaires OHADA, la gouvernance et la décentralisation, des compétences recherchées par l’État, les entreprises et les communes."),
		'FSEG'   => array( 'fr' => 'Faculté des Sciences Économiques et de Gestion', 'couleur' => '#16803C', 'ville' => 'Ebolowa', 'photos' => array( 'amphi', 'campus-batiments' ), 'responsable' => 'Pr Sigismond MVELE', 'presentation' => "Vous rêvez de diriger, d’analyser, de financer ou d’entreprendre ? La Faculté des Sciences Économiques et de Gestion forme les économistes, gestionnaires, financiers et managers dont ont besoin les entreprises, les banques, les administrations et les organisations. Fidèle à la vision entrepreneuriale de l’Université d’Ebolowa, elle prépare des diplômés capables de piloter une organisation ou de créer leur propre activité."),
		'FALSH'  => array( 'fr' => 'Faculté des Arts, Lettres et Sciences Humaines', 'couleur' => '#6A1B6D', 'ville' => 'Ebolowa', 'photos' => array( 'vie-etudiante-1', 'bibliotheque' ), 'responsable' => 'Pr NOMO ETABA', 'presentation' => 'Journaliste, traducteur, communicant, guide touristique, artiste ou gestionnaire du patrimoine : la Faculté des Arts, Lettres et Sciences Humaines transforme votre passion en profession. Avec huit départements et des parcours professionnalisants, elle forme des diplômés capables de communiquer, de transmettre et de valoriser les richesses culturelles du Cameroun.'),
		'FMSP'   => array( 'fr' => 'Faculté de Médecine et des Sciences Pharmaceutiques', 'couleur' => '#0077B6', 'ville' => 'Sangmélima', 'photos' => array( 'campus-sangmelima', 'salle' ), 'responsable' => 'Pr Pascal FOUMANE', 'presentation' => "La Faculté de Médecine et des Sciences Pharmaceutiques forme les médecins et les pharmaciens de demain, selon le programme national de formation médicale, pharmaceutique et odontostomatologique. Adossée à l’Hôpital de référence de Sangmélima pour la formation clinique, elle associe enseignements théoriques, apprentissage au chevet du patient et respect de l’éthique des professions de santé."),
		'ENSET'  => array( 'fr' => "École Normale Supérieure d'Enseignement Technique", 'couleur' => '#006B3C', 'ville' => 'Ebolowa', 'photos' => array( 'remise-toges', 'amphi-cours' ), 'responsable' => 'Pr Léandre NNEME NNEME', 'presentation' => "Vous voulez faire de votre passion technique un métier utile et respecté ? L’École Normale Supérieure d’Enseignement Technique d’Ebolowa forme les professeurs de l’enseignement secondaire technique et professionnel dont le Cameroun a besoin pour réussir son industrialisation. Maîtrise de la discipline, pédagogie et pratique en établissement : vous êtes opérationnel dès votre prise de fonction."),
		'ISABEE' => array( 'fr' => "Institut Supérieur d'Agriculture, du Bois, de l'Eau et de l'Environnement", 'couleur' => '#3A7D44', 'ville' => 'Ebolowa', 'photos' => array( 'campus-ebolowa', 'vie-etudiante-3' ), 'responsable' => 'Pr Philippe KOSMA', 'presentation' => "Au cœur d’une région de forêt équatoriale aux ressources exceptionnelles, l’ISABEE forme les ingénieurs qui produiront, transformeront et protégeront les richesses naturelles du Cameroun. Sciences de l’ingénieur, travaux pratiques, stages de terrain et projet de fin d’études : à l’ISABEE, vous apprenez en faisant, au plus près des exploitations, des forêts et des cours d’eau."),
		'ESTLC'  => array( 'fr' => 'École Supérieure de Transport, de Logistique et de Commerce', 'couleur' => '#4E7F1D', 'ville' => 'Ambam', 'photos' => array( 'campus-ambam', 'diplomes' ), 'responsable' => 'Pr Jean Gaston TAMBA', 'presentation' => "Vous êtes jeune, ambitieux et vous souhaitez donner une véritable dimension professionnelle à votre parcours universitaire ? À Ambam, carrefour du Cameroun, du Gabon et de la Guinée équatoriale, l’ESTLC vous forme aux métiers du transport, de la logistique, du commerce et des technologies. Ici, la formation ne se limite pas à la théorie : chaque étudiant développe le savoir, le savoir-faire et le savoir-être qu’exige son futur métier."),
		'ENSTMO' => array( 'fr' => 'École Nationale Supérieure des Sciences et Techniques Maritimes et Océaniques', 'couleur' => '#1b2c5a', 'ville' => 'Kribi', 'photos' => array( 'visite-port', 'vie-etudiante-12' ), 'responsable' => 'Pr Achille PANDONG', 'presentation' => 'Installée à Kribi, ville portuaire en plein essor, l’ENSTMO vous ouvre les métiers de la mer et de l’économie bleue : pêche, construction navale, transport maritime, océanographie, exploitation et gestion portuaire. La proximité du complexe industrialo-portuaire offre aux étudiants un environnement d’apprentissage directement connecté aux réalités du secteur maritime.'),
	);
}

/**
 * Les institutions partenaires de l'UEb : sept universités publiques, et le
 * MINESUP avec lequel l'université a une convention. Le logo de chacune se
 * dépose dans assets/images/partenaires/<id>.svg (ou .png, .webp, .jpg) ;
 * sans fichier, le sigle s'affiche à la place.
 */
function sueb_institutions_partenaires() {
	return array(
		array( 'id' => 'garoua', 'nom' => 'Université de Garoua', 'sigle' => 'UG', 'type' => 'Université', 'ville' => 'Garoua' ),
		array( 'id' => 'bertoua', 'nom' => 'Université de Bertoua', 'sigle' => 'UBe', 'type' => 'Université', 'ville' => 'Bertoua' ),
		array( 'id' => 'yaounde-2', 'nom' => 'Université de Yaoundé II', 'sigle' => 'UY2', 'type' => 'Université', 'ville' => 'Yaoundé' ),
		array( 'id' => 'ngaoundere', 'nom' => 'Université de Ngaoundéré', 'sigle' => 'UN', 'type' => 'Université', 'ville' => 'Ngaoundéré' ),
		array( 'id' => 'yaounde-1', 'nom' => 'Université de Yaoundé I', 'sigle' => 'UY1', 'type' => 'Université', 'ville' => 'Yaoundé' ),
		array( 'id' => 'douala', 'nom' => 'Université de Douala', 'sigle' => 'UDo', 'type' => 'Université', 'ville' => 'Douala' ),
		array( 'id' => 'dschang', 'nom' => 'Université de Dschang', 'sigle' => 'UDs', 'type' => 'Université', 'ville' => 'Dschang' ),
		array( 'id' => 'minesup', 'nom' => 'Ministère de l’Enseignement Supérieur', 'sigle' => 'MINESUP', 'type' => 'Convention', 'ville' => 'Yaoundé' ),
	);
}

/**
 * Villes du Cameroun placées sur la carte des partenariats (latitude, longitude).
 * Une convention dont la ville figure ici apparaît sur la carte.
 */
function sueb_villes() {
	return array(
		'Ebolowa'    => array( 2.9157, 11.1543 ),
		'Yaoundé'    => array( 3.8480, 11.5021 ),
		'Douala'     => array( 4.0511, 9.7679 ),
		'Kribi'      => array( 2.9373, 9.9077 ),
		'Akonolinga' => array( 3.7667, 12.2500 ),
		'Bertoua'    => array( 4.5772, 13.6846 ),
		'Dschang'    => array( 5.4440, 10.0530 ),
		'Bafoussam'  => array( 5.4781, 10.4176 ),
		'Bamenda'    => array( 5.9631, 10.1591 ),
		'Buea'       => array( 4.1527, 9.2410 ),
		'Ngaoundéré' => array( 7.3167, 13.5833 ),
		'Garoua'     => array( 9.3017, 13.3921 ),
		'Maroua'     => array( 10.5956, 14.3247 ),
		'Sangmélima' => array( 2.9333, 11.9833 ),
		'Ambam'      => array( 2.3833, 11.2833 ),
	);
}

/** URL du logo d'une institution partenaire, ou chaîne vide si le fichier n'est pas encore déposé. */
function sueb_logo_partenaire( $id ) {
	foreach ( array( 'svg', 'png', 'webp', 'jpg' ) as $ext ) {
		if ( file_exists( SUEB_DIR . "/assets/images/partenaires/$id.$ext" ) ) {
			return SUEB_URI . "/assets/images/partenaires/$id.$ext";
		}
	}
	return '';
}

/**
 * Les fonctions du rectorat, dans l'ordre protocolaire. Les intitulés
 * longs sont à confirmer par le rectorat (voir le compte rendu).
 */
function sueb_fonctions_rectorat() {
	return array(
		'RECTEUR'  => 'Recteur',
		'VR-CIE'   => 'Vice-Recteur chargé du Contrôle Interne et de l’Évaluation',
		'VR-CRME'  => 'Vice-Recteur chargé de la Coopération, de la Recherche et des Relations avec le Monde de l’Entreprise',
		'VR-PDTIC' => 'Vice-Recteur chargé de la Professionnalisation et du Développement des TIC',
		'SG'       => 'Secrétaire Générale',
		'CT'       => 'Conseiller Technique',
		'DAAC'     => 'Directeur des Affaires Académiques et de la Coopération',
		'DAAF'     => 'Directeur des Affaires Administratives et Financières',
		'DCOU'     => 'Directeur du Centre des Œuvres Universitaires',
		'DIPD'     => 'Directeur des Infrastructures, de la Planification et du Développement',
	);
}

/** Les niveaux de l'offre de formation, du plus large au plus fin. */
function sueb_niveaux() {
	return array(
		0 => array( 'un' => 'Établissement', 'plusieurs' => 'Établissements', 'enfants' => 'Départements' ),
		1 => array( 'un' => 'Département', 'plusieurs' => 'Départements', 'enfants' => 'Filières' ),
		2 => array( 'un' => 'Filière', 'plusieurs' => 'Filières', 'enfants' => 'Unités d’enseignement' ),
		3 => array( 'un' => 'Unité d’enseignement', 'plusieurs' => 'Unités d’enseignement', 'enfants' => '' ),
	);
}

/** Catégories du personnel, créées à l'installation (taxonomie modifiable). */
function sueb_corps_personnel() {
	return array(
		'direction'           => 'Direction de l’université',
		'doyens-directeurs'   => 'Doyens et directeurs',
		'chefs-departement'   => 'Chefs de département',
		'enseignants'         => 'Enseignants',
		'personnel-appui'     => 'Personnel d’appui',
	);
}