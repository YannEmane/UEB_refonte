<?php
/**
 * Page d'accueil : les cinq rubriques de l'université.
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();

$arbre = sueb_arbre_formation();

get_template_part( 'templates/accueil/banniere', null, array( 'arbre' => $arbre ) );
get_template_part( 'templates/accueil/decouvrir' );
get_template_part( 'templates/accueil/etablissements', null, array( 'arbre' => $arbre ) );
get_template_part( 'templates/accueil/partenariats' );
get_template_part( 'templates/accueil/actualites' );
get_template_part( 'templates/accueil/personnel' );

get_footer();
