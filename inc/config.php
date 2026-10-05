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
	'inscription'    => '#',
);

/**
 * Les neuf établissements, repris du site des inscriptions.
 * Sert à créer les fiches à l'installation et de repli (logo, couleur).
 */
function sueb_etablissements() {
	return array(
		'FS'     => array( 'fr' => 'Faculté des Sciences', 'couleur' => '#1f5aa6', 'ville' => 'Ebolowa', 'photos' => array( 'equipe-fs', 'amphi-cours' ) ),
		'FSJP'   => array( 'fr' => 'Faculté des Sciences Juridiques et Politiques', 'couleur' => '#8B1E1E', 'ville' => 'Ebolowa', 'photos' => array( 'vie-etudiante-4', 'salle' ) ),
		'FSEG'   => array( 'fr' => 'Faculté des Sciences Économiques et de Gestion', 'couleur' => '#16803C', 'ville' => 'Ebolowa', 'photos' => array( 'amphi', 'campus-batiments' ) ),
		'FALSH'  => array( 'fr' => 'Faculté des Arts, Lettres et Sciences Humaines', 'couleur' => '#6A1B6D', 'ville' => 'Ebolowa', 'photos' => array( 'vie-etudiante-1', 'bibliotheque' ) ),
		'FMSP'   => array( 'fr' => 'Faculté de Médecine et des Sciences Pharmaceutiques', 'couleur' => '#0077B6', 'ville' => 'Sangmélima', 'photos' => array( 'campus-sangmelima', 'salle' ) ),
		'ENSET'  => array( 'fr' => "École Normale Supérieure d'Enseignement Technique", 'couleur' => '#006B3C', 'ville' => 'Ebolowa', 'photos' => array( 'remise-toges', 'amphi-cours' ) ),
		'ISABEE' => array( 'fr' => "Institut Supérieur d'Agriculture, du Bois, de l'Eau et de l'Environnement", 'couleur' => '#3A7D44', 'ville' => 'Ebolowa', 'photos' => array( 'campus-ebolowa', 'vie-etudiante-3' ) ),
		'ESTLC'  => array( 'fr' => 'École Supérieure de Transport, de Logistique et de Commerce', 'couleur' => '#4E7F1D', 'ville' => 'Ambam', 'photos' => array( 'campus-ambam', 'diplomes' ) ),
		'ENSTMO' => array( 'fr' => 'École Nationale Supérieure des Sciences et Techniques Maritimes et Océaniques', 'couleur' => '#1b2c5a', 'ville' => 'Kribi', 'photos' => array( 'visite-port', 'vie-etudiante-12' ) ),
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
		array( 'id' => 'garoua', 'nom' => 'Université de Garoua', 'sigle' => 'UG', 'type' => 'Université' ),
		array( 'id' => 'bertoua', 'nom' => 'Université de Bertoua', 'sigle' => 'UBe', 'type' => 'Université' ),
		array( 'id' => 'yaounde-2', 'nom' => 'Université de Yaoundé II', 'sigle' => 'UY2', 'type' => 'Université' ),
		array( 'id' => 'ngaoundere', 'nom' => 'Université de Ngaoundéré', 'sigle' => 'UN', 'type' => 'Université' ),
		array( 'id' => 'yaounde-1', 'nom' => 'Université de Yaoundé I', 'sigle' => 'UY1', 'type' => 'Université' ),
		array( 'id' => 'douala', 'nom' => 'Université de Douala', 'sigle' => 'UDo', 'type' => 'Université' ),
		array( 'id' => 'dschang', 'nom' => 'Université de Dschang', 'sigle' => 'UDs', 'type' => 'Université' ),
		array( 'id' => 'minesup', 'nom' => 'Ministère de l’Enseignement Supérieur', 'sigle' => 'MINESUP', 'type' => 'Convention' ),
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
		'doyens-directeurs'   => 'Doyens et directeurs',
		'chefs-departement'   => 'Chefs de département',
		'enseignants'         => 'Enseignants',
		'personnel-appui'     => 'Personnel d’appui',
	);
}