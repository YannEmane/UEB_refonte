<?php
/**
 * Page d'accueil : les rubriques de l'université (découvrir, établissements,
 * nouvelles et évènements, personnel, partenariats).
 *
 * @package Site_UEB
 */

defined( 'ABSPATH' ) || exit;

get_header();

$arbre = sueb_arbre_formation();

get_template_part( 'templates/accueil/banniere', null, array( 'arbre' => $arbre ) );
get_template_part( 'templates/accueil/decouvrir' );
get_template_part( 'templates/accueil/etablissements', null, array( 'arbre' => $arbre ) );
get_template_part( 'templates/accueil/actualites' );
get_template_part( 'templates/accueil/personnel' );
get_template_part( 'templates/accueil/partenariats' );

get_footer();